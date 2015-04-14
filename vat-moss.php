<?php

/*
Plugin Name: WordPress VAT MOSS Returns
Plugin URI: http://www.wproute.com/downloads/vat-moss/
Description: Management and submission of VAT sales to EU consumers.
Version: 1.0.14
Tested up to: 4.1
Author: Lyquidity Solutions
Author URI: http://www.wproute.com/
Contributors: Bill Seddon
Copyright: Lyquidity Solutions Limited
License: GNU Version 2 or Any Later Version
Updateable: true
Text Domain: vat-moss
Domain Path: /languages
*/

namespace lyquidity\vat_moss;

/* -----------------------------------------------------------------
 *
 * -----------------------------------------------------------------
 */

// Uncomment this line to test
//set_site_transient( 'update_plugins', null );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* -----------------------------------------------------------------
 * Plugin class
 * -----------------------------------------------------------------
 */
class WordPressPlugin {

	/**
	 * @var WordPressPlugin The one true WordPressPlugin
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * Main WordPressPlugin instance
	 *
	 * Insures that only one instance of WordPressPlugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0
	 * @static
	 * @staticvar array $instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WordPressPlugin ) ) {
			self::$instance = new WordPressPlugin;
			self::$instance->actions();
		}
		return self::$instance;
	}

	/**
	 * @var Array or EU states
	 * @since 1.0
	 */
	public static $eu_states		= array("AT","BE","BG","HR","CY","CZ","DK","EE","FI","FR","DE","GB","GR","HU","IE","IT","LV","LT","LU","MT","NL","PL","PT","RO","SK","SI","ES","SE");

	/**
	 * Public settings object
	 */
	public $settings;

	/**
	 * Public integrations object
	 */
	public $integrations;

	/**
	 * Public html object
	 */
	public $html;

	/**
	* PHP5 constructor method.
	*
	* @since 1.0
	*/
	public function __construct() {

		/* Internationalize the text strings used. */
		$this->i18n();

		/* Set the constants needed by the plugin. */
		$this->constants();

		require_once VAT_MOSS_INCLUDES_DIR . 'post-types.php';
		require_once VAT_MOSS_INCLUDES_DIR . 'class-vat-moss-roles.php';
	}

	/**
	 * Setup any actions
	 */
	function actions()
	{
		global $moss_options;
		$moss_options = get_option( 'moss_settings' );

		// The supported ajax calls. 'action' parameter should be 'vat_moss_action'
		add_action( 'vat_moss_generate_summary_html',		array( $this, 'generate_summary_html' ) );
		add_action( 'vat_moss_check_submission_license',	array( $this, 'check_submission_license' ) );
//		add_action( 'vat_moss_generate_report',				array( $this, 'generate_report' ) );
		add_action( 'vat_moss_download_report',				array( $this, 'download_report' ) );

		// Allow the get_version request to obtain a response
		add_action( 'edd_sl_license_response', array(&$this, 'sl_license_response'));

		/* Load the functions files. */
		add_action( 'plugins_loaded', array( &$this, 'includes' ), 3 );

		/* Perform actions on admin initialization. */
		add_action( 'admin_init', array( &$this, 'admin_init') );
		add_action( 'init', array( &$this, 'init' ), 3 );

//		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		register_activation_hook( __FILE__, array($this, 'plugin_activation' ) );
		register_deactivation_hook( __FILE__, array($this, 'plugin_deactivation' ) );

		if (function_exists('vat_moss_submissions_settings'))
		{
			// These three lines allow for the plugin folder name to be something other than vat-moss
			$plugin = plugin_basename(__FILE__);
			$basename = strtolower( dirname($plugin) );
			add_filter( 'sl_updater_' . $basename, array(&$this, 'sl_updater_vat_moss'), 10, 2);

			// These two lines ensure the must-use update is able to access the credentials
			require_once 'edd_mu_updater.php';
			$this->updater = init_lsl_mu_updater2(__FILE__,$this);
		}
	}

	/**
	 * Called by the client pressing the check license button. This request is passed onto the Lyquidity server.
	 * 
	 * @since 1.0
	 */
	function check_submission_license($data)
	{
		require_once VAT_MOSS_INCLUDES_DIR . 'admin/submit_submission.php';

		$response = array(
			'version' => VAT_MOSS_VERSION,
			'status' => 'error',
			'message' => array( 'An unexpected error occurred' )
		);
		
		if (!isset($data['submission_key']) || empty($data['submission_key']))
		{
			$response['message'][] = "No submission key supplied";
			$response = json_encode( $response );
		}
		else if (!isset($data['url']) || empty($data['url']))
		{
			$response['message'][] = "No url supplied";	
			$response = json_encode( $response );
		}
		else
		{
			$args = array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array(
					'edd_action'		=> 'moss_check_submission_license',
					'submission_key'	=> $data['submission_key'],
					'url'				=> $data['url']
				),
				'cookies' => array()
			);

			$response = remote_get_handler( wp_remote_post( VAT_MOSS_STORE_API_URL, $args ) );
		}

		echo $response;

		exit();
	}
	
	function download_report( $data )
	{
		ob_clean();

		if (!current_user_can('send_submissions'))
		{
			echo "<div class='error'><p>" . __('You do not have rights to download an EC MOSS return', 'vat_moss' ) . "</p></div>";
			exit;
		}

		try
		{
			if (!isset($data['submission_id']))
			{
				echo __( 'There is no submission id', 'vat_moss' );
				exit;
			}

			$id = $data['submission_id'];
			if ( get_post_status( $id ) !== STATE_GENERATED)
			{
				echo __( 'There is no submission id', 'vat_moss' );
				exit;
			}

			$report = get_post_meta($id, 'report', true);
			if (!$report)
			{
				echo __( 'There is no report to download', 'vat_moss' );
				exit;
			}

			$title = get_the_title( $id );
			$submission_period = get_post_meta( $id, 'submission_period', true );
			$submission_year = get_post_meta( $id, 'submission_year', true );

			$extensions = array(
				'gb' => 'ods',
				'at' => 'xml',
				'be' => 'xml',
				'de' => 'csv',
				'ee' => 'xml',
				'ie' => 'xml',
				'lt' => 'xml',
				'lu' => 'xml',
				'pl' => 'xml'
			);

			$output_format = get_post_meta( $id, 'output_format', true );
			$extension = isset( $extensions[$output_format] )
				? $extensions[$output_format]
				: 'csv';

			$binary	= $output_format === 'gb'
				? base64_decode( $report )
				: $report;

			// Redirect output to a client’s web browser (Excel2007)
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="' . "$title Q$submission_period-$submission_year.$extension");
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');

			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0

			echo $binary;

		}
		catch(\Exception $ex)
		{
			error_log($ex->getMessage());
			echo "Download failed: " . $ex->getMessage();
		}

		exit();
	}

	/**
	 * Called by the client pressing the validate credentials button. This request is passed onto the Lyquidity server.
	 *
	 * @since 1.0
	 */
	function generate_summary_html( $data )
	{
		$this->generate_functions_common( $data, function($id, $moss_lines) 
		{
			ob_start();
			generate_moss_summary_html( $moss_lines, get_the_title( $id ) );
			$result = ob_get_clean();
			
			return array(
				'status'	=> 'success',
				'body'		=> $result,
				'message'	=> vat_moss()->integrations->issues
			);
		});
	}

	/**
	 * Common function to process the records for an id and provide processing of the records by a callback.
	 *
	 * @since 1.0
	 */
	function generate_functions_common( $data, $callback )
	{
		$response = array(
			'version' => VAT_MOSS_VERSION,
			'status' => 'error',
			'message' => array( 'An unexpected error occurred' )
		);

		try
		{
			if (!isset($data['submission_id']))
			{
				$response['message'] = array( __( 'There is no submission id', 'vat_moss' ) );
			}
			else
			{
				$id = $data['submission_id'];
				$selected		= maybe_unserialize(get_post_meta($id, 'mosssales', true));
				if (mossales_count($selected) == 0)
				{
					$response['message'] = array( __('There are no transaction selections assigned to this submission.', 'vat_moss' ) );
				}
				else
				{
					$vat_records	= $this->integrations->get_vat_record_information($selected);

					if (!$vat_records || !is_array($vat_records) || !isset($vat_records['status']))
					{
						$response['message'] = array( __('There was an error creating the information to generate a summary report.', 'vat_moss' ) );
					}
					else if ($vat_records['status'] === 'error')
					{
						$response['message'] = $vat_records['messages'];
					}
					else if ( !isset($vat_records['information']) || count($vat_records['information']) == 0 )
					{
						$response['message'] = array( __( 'There are no transactions assigned to this submission.', 'vat_moss' ) );
					}
					else
					{
						$moss_lines = generate_moss_summary( $id, $vat_records['information'] );

						$result = $callback( $id, $moss_lines );

						unset( $response['message'] );
						$response = array_merge( $response, $result );
						$response['status'] = 'success';
//						$response['body'] = $result;
					}
				}
			}
		}
		catch(\Exception $ex)
		{
			$response['message'][] = $ex->getMessage();
		}

		echo json_encode( $response );

		exit();
	}
	
	/**
	 * Take an action when the plugin is activated
	 */
	function plugin_activation()
	{
		try
		{
			setup_vat_moss_post_types();

			// Clear the permalinks
			flush_rewrite_rules();

			$roles = new MOSS_Roles;
			$roles->add_caps();
			$roles->add_roles();
		}
		catch(Exception $e)
		{
			set_transient(VAT_MOSS_ACTIVATION_ERROR_NOTICE, __("An error occurred during plugin activation: ", 'vat_moss') . $e->getMessage(), 10);
		}
	}

	/**
	 * Take an action when the plugin is activated
	 */
	function plugin_deactivation()
	{
		try
		{
			$roles = new MOSS_Roles;
			$roles->remove_roles();
			$roles->remove_caps();
		}
		catch(Exception $e)
		{
			set_transient(VAT_MOSS_DEACTIVATION_ERROR_NOTICE, __("An error occurred during plugin deactivation: ", 'vat_moss') . $e->getMessage(), 10);
		}
	}

	/**
	* Defines constants used by the plugin.
	*
	* @since 1.0
	*/
	function constants()
	{
		if ( ! defined( 'VAT_MOSS_PLUGIN_DIR' ) ) {
			define( 'VAT_MOSS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'VAT_MOSS_INCLUDES_DIR' ) ) {
			define( 'VAT_MOSS_INCLUDES_DIR', VAT_MOSS_PLUGIN_DIR . "includes/" );
		}

		if ( ! defined( 'VAT_MOSS_TEMPLATES_DIR' ) ) {
			define( 'VAT_MOSS_TEMPLATES_DIR', VAT_MOSS_PLUGIN_DIR . "templates/" );
		}

		if ( ! defined( 'VAT_MOSS_PLUGIN_URL' ) ) {
			define( 'VAT_MOSS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( 'VAT_MOSS_PLUGIN_FILE' ) ) {
			define( 'VAT_MOSS_PLUGIN_FILE', __FILE__ );
		}

		if ( ! defined( 'VAT_MOSS_VERSION' ) )
			define( 'VAT_MOSS_VERSION',							'1.0.13' );

		if ( ! defined( 'VAT_MOSS_WORDPRESS_COMPATIBILITY' ) )
			define( 'VAT_MOSS_WORDPRESS_COMPATIBILITY',			'4.1' );

		if ( ! defined( 'VAT_MOSS_STORE_API_URL' ) )
			define( 'VAT_MOSS_STORE_API_URL',					'https://www.wproute.com/' );

		if ( ! defined( 'VAT_MOSS_PRODUCT_NAME' ) )
			define( 'VAT_MOSS_PRODUCT_NAME',					'WP VAT MOSS Management' );

		if (!defined('VAT_MOSS_ACTIVATION_ERROR_NOTICE'))
			define('VAT_MOSS_ACTIVATION_ERROR_NOTICE',			'VAT_MOSS_ACTIVATION_ERROR_NOTICE');

		if (!defined('VAT_MOSS_ACTIVATION_UPDATE_NOTICE'))
			define('VAT_MOSS_ACTIVATION_UPDATE_NOTICE',			'VAT_MOSS_ACTIVATION_UPDATE_NOTICE');

		if (!defined('VAT_MOSS_DEACTIVATION_ERROR_NOTICE'))
			define('VAT_MOSS_DEACTIVATION_ERROR_NOTICE',		'VAT_MOSS_DEACTIVATION_ERROR_NOTICE');

		if (!defined('VAT_MOSS_DEACTIVATION_UPDATE_NOTICE'))
			define('VAT_MOSS_DEACTIVATION_UPDATE_NOTICE',		'VAT_MOSS_DEACTIVATION_UPDATE_NOTICE');

		if (!defined('VAT_MOSS_REASON_TOOSHORT'))
			define('VAT_MOSS_REASON_TOOSHORT',					__('The VAT number supplied is too short', 'vat_moss'));

		if (!defined('VAT_MOSS_REASON_INVALID_FORMAT'))
			define('VAT_MOSS_REASON_INVALID_FORMAT',			__('The VAT number supplied does not have a valid format', 'vat_moss'));

		if (!defined('VAT_MOSS_REASON_SIMPLE_CHECK_FAILS'))
			define('VAT_MOSS_REASON_SIMPLE_CHECK_FAILS',		__('Simple check failed', 'vat_moss'));

		if (!defined('VAT_MOSS_ERROR_VALIDATING_VAT_ID'))
			define('VAT_MOSS_ERROR_VALIDATING_VAT_ID',			__('An error occurred validating the VAT number supplied', 'vat_moss'));

	}

	/*
	|--------------------------------------------------------------------------
	| INTERNATIONALIZATION
	|--------------------------------------------------------------------------
	*/

	/**
	* Load the translation of the plugin.
	*
	* @since 1.0
	*/
	public function i18n() {

		/* Load the translation of the plugin. */
		load_plugin_textdomain( 'vat_moss', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/*
	|--------------------------------------------------------------------------
	| INCLUDES
	|--------------------------------------------------------------------------
	*/

	/**
	* Loads the initial files needed by the plugin.
	*
	* @since 1.0
	*/
	public function includes() {

		if (!isset($_REQUEST['vat_moss_action']) && !is_admin() && php_sapi_name() !== "cli") return;

		require_once VAT_MOSS_INCLUDES_DIR . 'admin-notices.php';

		// The SL plugin will not be available while at the network level
		// unless the SL is active in blog #1.
		if (is_network_admin()) return;

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

		require_once VAT_MOSS_INCLUDES_DIR . 'class-menu.php';
		require_once VAT_MOSS_INCLUDES_DIR . 'settings.php';
		require_once VAT_MOSS_INCLUDES_DIR . 'submissions.php';
		require_once VAT_MOSS_INCLUDES_DIR . 'class-settings.php';
		require_once VAT_MOSS_INCLUDES_DIR . 'class-integrations.php';
		require_once VAT_MOSS_INCLUDES_DIR . 'settings.php';
		require_once VAT_MOSS_INCLUDES_DIR . 'vatidvalidator.php';
		require_once(VAT_MOSS_INCLUDES_DIR . 'class-html-elements.php');
//		require_once(VAT_MOSS_INCLUDES_DIR . 'meta-box.php');

		$this->settings = new MOSS_WP_Settings;
		$this->integrations = new MOSS_WP_Integrations;
		$this->html = new MOSS_HTML_Elements;
	}

	/**
	 * Enqueue scripts and styles
	 */

	function enqueue_scripts()
	{
		wp_enqueue_style("vat_moss_style",  VAT_MOSS_PLUGIN_URL . "assets/css/vat_moss.css", null, null, "screen");

		wp_enqueue_script ("vat_moss_script", VAT_MOSS_PLUGIN_URL . "assets/js/vat_moss.js", array( 'jquery' ));
		wp_localize_script("vat_moss_script", 'vat_moss_vars', array(
			'ajaxurl'            			=> $this->get_ajax_url(),
			'lyquidity_server_url'			=> VAT_MOSS_STORE_API_URL
		));

		wp_enqueue_script('jquery-ui-dialog', false, array('jquery-ui-core','jquery-ui-button', 'jquery') );

	} // end vat_enqueue_scripts

	function admin_enqueue_scripts()
	{
		$suffix = '';

		wp_enqueue_style  ("vat_moss_admin_style",  VAT_MOSS_PLUGIN_URL . "assets/css/vat_moss_admin.css", null, null, "screen");

//		wp_enqueue_script ("vat_moss_admin_validation", VAT_MOSS_PLUGIN_URL . "js/vatid_validation.js");
		wp_enqueue_script ("vat_moss_admin_script", VAT_MOSS_PLUGIN_URL . "assets/js/vat_moss_admin.js", array( 'jquery' ), VAT_MOSS_VERSION);

		wp_localize_script("vat_moss_admin_script", 'vat_moss_vars', array(
			'ajaxurl'            			=> $this->get_ajax_url(),
			'url'							=> home_url( '/' ),
			'lyquidity_server_url'			=> VAT_MOSS_STORE_API_URL,
			'ReasonNoLicenseKey'			=> __( 'There is no license key to check', 'vat_moss' ),
			'ReasonNoSenderId'				=> __( 'There is no sender id to test', 'vat_moss' ),
			'ReasonNoPassword'				=> __( 'There is no password', 'vat_moss' ),
			'ReasonSimpleCheckFails'		=> VAT_MOSS_REASON_SIMPLE_CHECK_FAILS,
			'ErrorValidatingCredentials'	=> 'An error occurred validating the credentials',
			'ErrorCheckingLicense'			=> 'An error occurred checking the license',
			'CredentialsValidated'			=> 'Credentials are valid',
			'LicenseChecked'				=> 'The license check is complete. There are {credits} remaining credits with this submission license key.',
			'UnexpectedErrorSummary'		=> 'An unexpected error occurred displaying MOSS summary.  If this error persists, contact the administrator.',
			'UnexpectedErrorCredentials'	=> 'An unexpected error occurred validating the credentials.  If this error persists, contact the administrator.'
		));

		wp_enqueue_script('jquery-ui-dialog', false, array('jquery-ui-core','jquery-ui-button', 'jquery') );
		wp_enqueue_script('jquery-tiptip', VAT_MOSS_PLUGIN_URL . 'assets/js/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), VAT_MOSS_VERSION);
	}

	/*
	|--------------------------------------------------------------------------
	| Perform actions on frontend initialization.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Hooks EDD actions, when present in the $_POST superglobal. Every edd_action
	 * present in $_POST is called using WordPress's do_action function. These
	 * functions are called on init.
	 *
	 * @since 1.0
	 * @return void
	*/
	function init()
	{
		if ( isset( $_GET['vat_moss_action'] ) ) {
			error_log("get - do_action( 'vat_moss_{$_GET['vat_moss_action']}'");
			do_action( 'vat_moss_' . $_GET['vat_moss_action'], $_GET );
		}

		if ( isset( $_POST['vat_moss_action'] ) ) {
			error_log("post - do_action( 'vat_moss_{$_POST['vat_moss_action']}'");
			do_action( 'vat_moss_' . $_POST['vat_moss_action'], $_POST );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Add compatibility information the get_version response.
	|--------------------------------------------------------------------------
	*/
	function sl_license_response($response)
	{
		$response['tested'] = VAT_MOSS_WORDPRESS_COMPATIBILITY;
		$response['compatibility'] = serialize( array( VAT_MOSS_WORDPRESS_COMPATIBILITY => array( VAT_MOSS_VERSION => array("100%", "5", "5") ) ) );
		return $response;
	}

	/*
	|--------------------------------------------------------------------------
	| Perform actions on admin initialization.
	|--------------------------------------------------------------------------
	*/
	function admin_init()
	{
	}

	/**
	 * Callback to return plugin values to the updater
	 *
	 */
	function sl_updater_vat_moss($data, $required_fields)
	{
		// Can't rely on the global $edd_options (if your license is stored as an EDD option)
		$license_key = get_option('vat_moss_license_key');

		$data['license']	= $license_key;				// license key (used get_option above to retrieve from DB)
		$data['item_name']	= VAT_MOSS_PRODUCT_NAME;	// name of this plugin
		$data['api_url']	= VAT_MOSS_STORE_API_URL;
		$data['version']	= VAT_MOSS_VERSION;			// current version number
		$data['author']		= 'Lyquidity Solutions';	// author of this plugin

		return $data;
	}

	/**
	 * Get the current page URL
	 *
	 * @since 1.0.1
	 * @object $post
	 * @return string $page_url Current page URL
	 */
	function get_current_page_url() {
		global $post;

		if ( is_front_page() ) :
			$page_url = home_url( '/' );
		else :
			$page_url = 'http';

		if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" )
			$page_url .= "s";

		$page_url .= "://";

		if ( isset( $_SERVER["SERVER_PORT"] ) && $_SERVER["SERVER_PORT"] != "80" )
			$page_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		else
			$page_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		endif;

		return apply_filters( 'vat_moss_get_current_page_url', esc_url( $page_url ) );
	}

	/**
	 * Get AJAX URL
	 *
	 * @since 1.0.1
	 * @return string
	*/
	function get_ajax_url() {
		$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

		$current_url = $this->get_current_page_url();
		$ajax_url    = admin_url( 'admin-ajax.php', $scheme );

		if ( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
			$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
		}

		return apply_filters( 'edd_ajax_url', $ajax_url );
	}
}

/**
 * The main function responsible for returning the one true example plug-in
 * instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: &lt;?php $plugin = initialize(); ?&gt;
 *
 * @since 1.0
 * @return object The one true WordPressPlugin Instance
 */
function vat_moss() {
	return WordPressPlugin::instance();
}

// Get EDD SL Change Expiry Date Running
vat_moss();

?>
