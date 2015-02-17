<?php

/**
 * MOSS Settings class
 *
 * @package     vat-moss
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

namespace lyquidity\vat_moss;

class MOSS_WP_Settings {

	private $options;

	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @return void
	*/
	public function __construct() {

		$this->options = get_option( 'moss_settings', array() );

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Get the value of a specific setting
	 *
	 * @since 1.0
	 * @return mixed
	*/
	public function get( $key, $default = false ) {
		$value = ! empty( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
		return $value;
	}

	/**
	 * Get all settings
	 *
	 * @since 1.0
	 * @return array
	*/
	public function get_all() {
		return $this->options;
	}

	/**
	 * Add all settings sections and fields
	 *
	 * @since 1.0
	 * @return void
	*/
	function register_settings() {

		if ( false == get_option( 'moss_settings' ) ) {
			add_option( 'moss_settings' );
		}

		foreach( $this->get_registered_settings() as $tab => $settings ) {

			add_settings_section(
				'moss_settings_' . $tab,
				__return_null(),
				'__return_false',
				'moss_settings_' . $tab
			);

			foreach ( $settings as $key => $option ) {

				$name = isset( $option['name'] ) ? $option['name'] : '';

				add_settings_field(
					'moss_settings[' . $key . ']',
					$name,
					is_callable( array( $this, $option[ 'type' ] . '_callback' ) ) ? array( $this, $option[ 'type' ] . '_callback' ) : array( $this, 'missing_callback' ),
					'moss_settings_' . $tab,
					'moss_settings_' . $tab,
					array(
						'id'      => $key,
						'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
						'name'    => isset( $option['name'] ) ? $option['name'] : null,
						'section' => $tab,
						'size'    => isset( $option['size'] ) ? $option['size'] : null,
						'max'     => isset( $option['max'] ) ? $option['max'] : null,
						'min'     => isset( $option['min'] ) ? $option['min'] : null,
						'step'    => isset( $option['step'] ) ? $option['step'] : null,
						'options' => isset( $option['options'] ) ? $option['options'] : '',
						'std'     => isset( $option['std'] ) ? $option['std'] : ''
					)
				);
			}

		}

		// Creates our settings in the options table
		register_setting( 'moss_settings', 'moss_settings', array( $this, 'sanitize_settings' ) );

	}

	/**
	 * Retrieve the array of plugin settings
	 *
	 * @since 1.0
	 * @return array
	*/
	function sanitize_settings( $input = array() ) {

		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}

		parse_str( $_POST['_wp_http_referer'], $referrer );

		$saved    = get_option( 'moss_settings', array() );
		if( ! is_array( $saved ) ) {
			$saved = array();
		}
		$settings = $this->get_registered_settings();
		$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';

		$input = $input ? $input : array();
		$input = apply_filters( 'moss_settings_' . $tab . '_sanitize', $input );

		// Ensure a value is always passed for every checkbox
		if( ! empty( $settings[ $tab ] ) ) {
			foreach ( $settings[ $tab ] as $key => $setting ) {

				// Single checkbox
				if ( isset( $settings[ $tab ][ $key ][ 'type' ] ) && 'checkbox' == $settings[ $tab ][ $key ][ 'type' ] ) {
					$input[ $key ] = ! empty( $input[ $key ] );
				}

				// Multicheck list
				if ( isset( $settings[ $tab ][ $key ][ 'type' ] ) && 'multicheck' == $settings[ $tab ][ $key ][ 'type' ] ) {
					if( empty( $input[ $key ] ) ) {
						$input[ $key ] = array();
					}
				}
			}
		}
		
		// Loop through each setting being saved and pass it through a sanitization filter
		foreach ( $input as $key => $value ) {

			// Get the setting type (checkbox, select, etc)
			$type = isset( $settings[ $tab ][ $key ][ 'type' ] ) ? $settings[ $tab ][ $key ][ 'type' ] : false;

			if ( $type ) {
				// Field type specific filter
				$input[$key] = apply_filters( 'moss_settings_sanitize_' . $type, $value, $key );
			}

			// General filter
			$input[ $key ] = apply_filters( 'moss_settings_sanitize', $value, $key );
		}

		add_settings_error( 'moss-notices', '', __( 'Settings updated.', 'vat_moss' ), 'updated' );

		return array_merge( $saved, $input );
	}

	/**
	 * Retrieve the array of plugin settings
	 *
	 * @since 1.0
	 * @return array
	*/
	function get_registered_settings() {

		$settings = array(
			/** General Settings */
			'general' => apply_filters( 'moss_settings_general',
				array(
					'submission_details' => array(
						'name' => '<strong>' . __( 'Submission Details', 'vat_moss' ) . '</strong>',
						'desc' => '',
						'type' => 'header'
					),
					'vat_number' => array(
						'name' => __( 'Your VAT Number', 'vat_moss' ),
						'desc' => '<p class="description">' . __( 'Enter your VAT number without country code.', 'vat_moss' ) . '</p>',
						'type' => 'text'
					),
					'submitter' => array(
						'name' => __( 'Submitter\'s Name', 'vat_moss' ),
						'desc' => '<p class="description">' . __( 'The default name of the submitter.', 'vat_moss' ) . '</p>',
						'type' => 'text'
					),
					'email' => array(
						'name' => __( 'Submitter\'s Email Address', 'vat_moss' ),
						'desc' => '<p class="description">' . __( 'Enter a default email address for the submission.', 'vat_moss' ) . '</p>',
						'type' => 'text'
					),
					'country' => array(
						'id' => 'country',
						'name' => __( 'Registered Member State', 'vat_moss' ),
						'desc' => __( 'Where is your company registered?  If your company is not an EU company select the \'Non-EU\' option.', 'vat_moss' ),
						'type' => 'select',
						'options' => $this->eu_country_list(),
						'select2' => true,
						'placeholder' => __( 'Select a country', 'vat_moss' )
					),
					'currency' => array(
						'id' => 'currency',
						'name' => __( 'Currency', 'vat_moss' ),
						'desc' => __( 'Select your shop currency.', 'vat_moss' ),
						'type' => 'select',
						'options' => get_currencies(),
						'select2' => true
					),
					'fixed_establishment' => array(
						'name' => __( 'Only fixed establishment', 'vat_moss' ),
						'desc' => '<p class="description">' . __( 'Please confirm this is your only \'fixed establishment\'.', 'vat_moss' ) . '</p>' .
								  '<p class="description">' . __( 'This plugin is only suitable for use by companies that are registered for VAT in only one member state.', 'vat_moss' ) . '</p>',
						'type' => 'checkbox'
					),
					'output_format' => array(
						'name' => __( 'Output format', 'vat_moss' ),
						'desc' => __( 'Select the output format you require', 'vat_moss' ),
						'type' => 'select',
						'options' => supported_formats(),
						'select2' => true,
						'placeholder' => __( 'Select a format', 'vat_moss' )
					)
				)
			),
			/** Integration Settings */
			'integrations' => apply_filters( 'moss_settings_integrations',
				array(
					'integrations' => array(
						'name' => __( 'Integrations', 'vat_moss' ),
						'desc' => __( 'Choose the integrations to enable.', 'vat_moss' ),
						'type' => 'multicheck',
						'options' => MOSS_WP_Integrations::get_integrations_list()
					)
				)
			)
		);

		return apply_filters( 'moss_settings', $settings );
	}

	/**
	 * Header Callback
	 *
	 * Renders the header.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	function header_callback( $args ) {
		echo '<hr/>';
	}

	/**
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function checkbox_callback( $args ) {

		$checked = isset($this->options[$args['id']]) ? checked(1, $this->options[$args['id']], false) : '';
		$html = '<input type="checkbox" id="moss_settings[' . $args['id'] . ']" name="moss_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
		$html .= '<label for="moss_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function multicheck_callback( $args ) {

		if ( ! empty( $args['options'] ) ) {
			foreach( $args['options'] as $key => $option ) {
				if( isset( $this->options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
				echo '<input name="moss_settings[' . $args['id'] . '][' . $key . ']" id="moss_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
				echo '<label for="moss_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
			}
			echo '<p class="description">' . $args['desc'] . '</p>';
		}
	}

	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function radio_callback( $args ) {

		foreach ( $args['options'] as $key => $option ) :
			$checked = false;

			if ( isset( $this->options[ $args['id'] ] ) && $this->options[ $args['id'] ] == $key )
				$checked = true;
			elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( $this->options[ $args['id'] ] ) )
				$checked = true;

			echo '<input name="moss_settings[' . $args['id'] . ']"" id="moss_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
			echo '<label for="moss_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
		endforeach;

		echo '<p class="description">' . $args['desc'] . '</p>';
	}

	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function text_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="moss_settings[' . $args['id'] . ']" name="moss_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<label for="moss_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * License Callback
	 *
	 * Renders license key fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function license_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="moss_settings[' . $args['id'] . ']" name="moss_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$license_status = get_option('vat_moss_license_active');

		$license_key = ! empty( $value ) ? $value : false;

		if( 'valid' === $license_status && ! empty( $license_key ) ) {
			$html .= '<input type="submit" class="button" name="moss_deactivate_license" value="' . esc_attr__( 'Deactivate License', 'vat_moss' ) . '"/>';
			$html .= '<span style="color:green;">&nbsp;' . __( 'Your license is valid!', 'vat_moss' ) . '</span>';
		} elseif( 'expired' === $license_status && ! empty( $license_key ) ) {
			$renewal_url = add_query_arg( array( 'edd_license_key' => $license_key, 'download_id' => 17 ), 'https://plugin.com/checkout' );
			$html .= '<a href="' . esc_url( $renewal_url ) . '" class="button-primary">' . __( 'Renew Your License', 'vat_moss' ) . '</a>';
			$html .= '<br/><span style="color:red;">&nbsp;' . __( 'Your license has expired, renew today to continue getting updates and support!', 'vat_moss' ) . '</span>';
		} else {
			$html .= '<input type="submit" class="button" name="moss_activate_license" value="' . esc_attr__( 'Activate License', 'vat_moss' ) . '"/>';
		}

		$html .= '<br/><label for="moss_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function number_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$max  = isset( $args['max'] ) ? $args['max'] : 999999;
		$min  = isset( $args['min'] ) ? $args['min'] : 0;
		$step = isset( $args['step'] ) ? $args['step'] : 1;

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="moss_settings[' . $args['id'] . ']" name="moss_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<label for="moss_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function textarea_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<textarea class="large-text" cols="50" rows="5" id="moss_settings[' . $args['id'] . ']" name="moss_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		$html .= '<label for="moss_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function password_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="password" class="' . $size . '-text" id="moss_settings[' . $args['id'] . ']" name="moss_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
		$html .= '<label for="moss_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';
		$html .= "<input type=\"hidden\" name=\"_wp_nonce\" value=\"" . wp_create_nonce( 'moss_settings' ) . "\" >";

		echo $html;
	}

	/**
	 * Missing Callback
	 *
	 * If a function is missing for settings callbacks alert the user.
	 *
	 * @since 1.3.1
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	function missing_callback($args) {
		printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'vat_moss' ), $args['id'] );
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function select_callback($args) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$html = '<select id="moss_settings[' . $args['id'] . ']" name="moss_settings[' . $args['id'] . ']"/>';

		foreach ( $args['options'] as $option => $name ) :
			$selected = selected( $option, $value, false );
			$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
		endforeach;

		$html .= '</select>';
		$html .= '<label for="moss_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Rich Editor Callback
	 *
	 * Renders rich editor fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @global $wp_version WordPress Version
	 */
	function rich_editor_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		ob_start();
		wp_editor( stripslashes( $value ), 'moss_settings[' . $args['id'] . ']', array( 'textarea_name' => 'moss_settings[' . $args['id'] . ']' ) );
		$html = ob_get_clean();

		$html .= '<br/><label for="moss_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	function validate_callback( $args ) {
?>
		<button id="validate_credentials" password_id="moss_settings\[password\]" sender_id="moss_settings\[sender_id\]" value="Validate Credentials" class="button button-primary" >Validate Credentials</button>
		<img src="<?php echo VAT_MOSS_PLUGIN_URL . "images/loading.gif" ?>" id="moss-loading" style="display:none; margin-left: 10px; margin-top: 8px;" />
		<input type="hidden" name="_validate_credentials_nonce" value="<?php echo wp_create_nonce( 'validate_credentials' ); ?>" >
<?php
	}
	
	function eu_country_list() {
		$countries = array(
			'XX'   => 'Non-EU country',
			'GB' => 'United Kingdom',
			'AT' => 'Austria',
			'BE' => 'Belgium',
			'BG' => 'Bulgaria',
			'HR' => 'Croatia/Hrvatska',
			'CY' => 'Cyprus Island',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'EE' => 'Estonia',
			'FI' => 'Finland',
			'FR' => 'France',
			'DE' => 'Germany',
			'GR' => 'Greece',
			'HU' => 'Hungary',
			'IE' => 'Ireland',
			'IT' => 'Italy',
			'LV' => 'Latvia',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MT' => 'Malta',
			'NL' => 'Netherlands',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'RO' => 'Romania',
			'SK' => 'Slovak Republic',
			'SI' => 'Slovenia',
			'ES' => 'Spain',
			'SE' => 'Sweden',
		);

		return apply_filters( 'edd_countries', $countries );
	}

}
