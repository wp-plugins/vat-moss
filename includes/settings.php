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
		'EUR'  => __( 'Euros (&euro;)', 'edd' ),
		'GBP'  => __( 'Pounds Sterling (&pound;)', 'edd' ),
		'USD'  => __( 'US Dollars (&#36;)', 'edd' ),
		'AUD'  => __( 'Australian Dollars (&#36;)', 'edd' ),
		'BRL'  => __( 'Brazilian Real (R&#36;)', 'edd' ),
		'CAD'  => __( 'Canadian Dollars (&#36;)', 'edd' ),
		'CZK'  => __( 'Czech Koruna', 'edd' ),
		'DKK'  => __( 'Danish Krone', 'edd' ),
		'HKD'  => __( 'Hong Kong Dollar (&#36;)', 'edd' ),
		'HUF'  => __( 'Hungarian Forint', 'edd' ),
		'ILS'  => __( 'Israeli Shekel (&#8362;)', 'edd' ),
		'JPY'  => __( 'Japanese Yen (&yen;)', 'edd' ),
		'MYR'  => __( 'Malaysian Ringgits', 'edd' ),
		'MXN'  => __( 'Mexican Peso (&#36;)', 'edd' ),
		'NZD'  => __( 'New Zealand Dollar (&#36;)', 'edd' ),
		'NOK'  => __( 'Norwegian Krone', 'edd' ),
		'PHP'  => __( 'Philippine Pesos', 'edd' ),
		'PLN'  => __( 'Polish Zloty', 'edd' ),
		'SGD'  => __( 'Singapore Dollar (&#36;)', 'edd' ),
		'SEK'  => __( 'Swedish Krona', 'edd' ),
		'CHF'  => __( 'Swiss Franc', 'edd' ),
		'TWD'  => __( 'Taiwan New Dollars', 'edd' ),
		'THB'  => __( 'Thai Baht (&#3647;)', 'edd' ),
		'INR'  => __( 'Indian Rupee (&#8377;)', 'edd' ),
		'TRY'  => __( 'Turkish Lira (&#8378;)', 'edd' ),
		'RIAL' => __( 'Iranian Rial (&#65020;)', 'edd' ),
		'RUB'  => __( 'Russian Rubles', 'edd' )
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
		'de' => __( 'Germany (csv)', 'vat_moss' ),
		'ee' => __( 'Estonia (xml)', 'vat_moss' ),
		'lt' => __( 'Lithuania (xml)', 'vat_moss' ),
		'lu' => __( 'Luxembourg (xml)', 'vat_moss' ),
/*
		'ie' => __( 'Ireland (xml)', 'vat_moss' ),
		'pl' => __( 'Poland (Xml)', 'vat_moss' ),
 */
		'eu' => __( 'EC generic (two/three column csv)', 'vat_moss' )
	));
}

?>