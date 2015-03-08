<?php
/**
 * MOSS Settings Functions
 *
 * @package     vat-moss
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

namespace lyquidity\vat_moss;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function moss_submissions_settings()
{
	$active_tab = isset( $_GET[ 'tab' ] ) && array_key_exists( $_GET['tab'], moss_get_settings_tabs() ) ? $_GET[ 'tab' ] : 'general';

	ob_start();
?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php
			foreach( moss_get_settings_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab' => $tab_id
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
					echo esc_html( $tab_name );
				echo '</a>';
			}
			?>
		</h2>
		<div id="tab_container">
			<form method="post" action="options.php">
				<table class="form-table">
				<?php
				settings_fields( 'moss_settings' );
				do_settings_fields( 'moss_settings_' . $active_tab, 'moss_settings_' . $active_tab );
				?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div><!-- #tab_container-->
	</div><!-- .wrap -->
	<?php
	echo ob_get_clean();
}


/**
 * Retrieve settings tabs
 *
 * @since 1.0
 * @return array $tabs
 */
function moss_get_settings_tabs() {

	$tabs                 = array();
	$tabs['general']      = __( 'General', 'vat_moss' );
	$tabs['integrations'] = __( 'Integrations', 'vat_moss' );

	return apply_filters( 'moss_settings_tabs', $tabs );
}

/**
 * Get Currencies
 *
 * @since 1.0
 * @return array $currencies A list of the available currencies
 */
function get_currencies() {
	$currencies = array(
		'EUR'  => __( 'Euros (&euro;)', 'vat_moss' ),
		'GBP'  => __( 'Pounds Sterling (&pound;)', 'vat_moss' ),
		'USD'  => __( 'US Dollars (&#36;)', 'vat_moss' ),
		'AUD'  => __( 'Australian Dollars (&#36;)', 'vat_moss' ),
		'BRL'  => __( 'Brazilian Real (R&#36;)', 'vat_moss' ),
		'CAD'  => __( 'Canadian Dollars (&#36;)', 'vat_moss' ),
		'CZK'  => __( 'Czech Koruna', 'vat_moss' ),
		'DKK'  => __( 'Danish Krone', 'vat_moss' ),
		'HKD'  => __( 'Hong Kong Dollar (&#36;)', 'vat_moss' ),
		'HUF'  => __( 'Hungarian Forint', 'vat_moss' ),
		'ILS'  => __( 'Israeli Shekel (&#8362;)', 'vat_moss' ),
		'JPY'  => __( 'Japanese Yen (&yen;)', 'vat_moss' ),
		'MYR'  => __( 'Malaysian Ringgits', 'vat_moss' ),
		'MXN'  => __( 'Mexican Peso (&#36;)', 'vat_moss' ),
		'NZD'  => __( 'New Zealand Dollar (&#36;)', 'vat_moss' ),
		'NOK'  => __( 'Norwegian Krone', 'vat_moss' ),
		'PHP'  => __( 'Philippine Pesos', 'vat_moss' ),
		'PLN'  => __( 'Polish Zloty', 'vat_moss' ),
		'SGD'  => __( 'Singapore Dollar (&#36;)', 'vat_moss' ),
		'SEK'  => __( 'Swedish Krona', 'vat_moss' ),
		'CHF'  => __( 'Swiss Franc', 'vat_moss' ),
		'TWD'  => __( 'Taiwan New Dollars', 'vat_moss' ),
		'THB'  => __( 'Thai Baht (&#3647;)', 'vat_moss' ),
		'INR'  => __( 'Indian Rupee (&#8377;)', 'vat_moss' ),
		'TRY'  => __( 'Turkish Lira (&#8378;)', 'vat_moss' ),
		'RIAL' => __( 'Iranian Rial (&#65020;)', 'vat_moss' ),
		'RUB'  => __( 'Russian Rubles', 'vat_moss' )
	);

	return apply_filters( 'moss_currencies', $currencies );
}

/**
 * Given a currency determine the symbol to use. If no currency given, site default is used.
 * If no symbol is determine, the currency string is returned.
 *
 * @since  1.0
 * @param  string $currency The currency string
 * @return string           The symbol to use for the currency
 */
function get_currency_symbol( $currency = '' ) {
	global $edd_options;

	if ( empty( $currency ) ) {
		$currency = get_default_currency();
	}

	switch ( $currency ) :
		case "GBP" :
			$symbol = '&pound;';
			break;
		case "BRL" :
			$symbol = 'R&#36;';
			break;
		case "EUR" :
			$symbol = '&euro;';
			break;
		case "USD" :
		case "AUD" :
		case "NZD" :
		case "CAD" :
		case "HKD" :
		case "MXN" :
		case "SGD" :
			$symbol = '&#36;';
			break;
		case "JPY" :
			$symbol = '&yen;';
			break;
		default :
			$symbol = $currency;
			break;
	endswitch;

	return apply_filters( 'edd_currency_symbol', $symbol, $currency );
}

function get_default_currency()
{
	return vat_moss()->settings->get( 'currency', 'EUR' );
}

function get_establishment_country()
{
	return vat_moss()->settings->get( 'country', 'GB' );
}

function get_output_format()
{
	return vat_moss()->settings->get(
		'output_format', 
		in_array( strtolower( get_establishment_country() ), supported_formats() )
			? strtolower( get_establishment_country() )
			: 'eu'
	);
}

function supported_formats()
{
	return apply_filters( 'maoss_supported_formats', array(
		'gb' => __( 'UK (spreadsheet)', 'vat_moss' ),
		'at' => __( 'Austria (xml)', 'vat_moss' ),
		'be' => __( 'Belgium (xml)', 'vat_moss' ),
		'dk' => __( 'Denmark (csv)', 'vat_moss' ),
		'de' => __( 'Germany (csv)', 'vat_moss' ),
		'ee' => __( 'Estonia (xml)', 'vat_moss' ),
		'lt' => __( 'Lithuania (xml)', 'vat_moss' ),
		'lu' => __( 'Luxembourg (xml)', 'vat_moss' ),
		'se' => __( 'Sweden (csv)', 'vat_moss' ),
/*
		'ie' => __( 'Ireland (xml)', 'vat_moss' ),
		'pl' => __( 'Poland (Xml)', 'vat_moss' ),
 */
		'eu' => __( 'EC generic (two/three column csv)', 'vat_moss' )
	));
}

function get_company_name()
{
	return vat_moss()->settings->get(
		'company_name', 
		\get_bloginfo( 'name' )
	);
}

function use_all_products()
{
	return vat_moss()->settings->get( 'all_products', false );
}

?>