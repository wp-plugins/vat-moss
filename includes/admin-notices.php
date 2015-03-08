<?php

/**
 * MOSS Admin notices
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

/**
 * Admin Notices
 *
 * Outputs admin notices
 *
 * @package VAT MOSS
 * @since 1.0
*/
function admin_notices() {

	$integrations = MOSS_WP_Integrations::get_integrations_list();
	if (isset( $integrations['wooc'] ) && !class_exists('Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant'))
	{
		echo "<div class='error'><p>" . __("The Aelia EU VAT Assistant or the Simba EU VAT Compliance plug-in must be installed to use the WooCommerce integration.", "vat_moss") . "</p></div>";				
	}

	if (isset( $integrations['edd'] ) && !class_exists('lyquidity\edd_vat\WordPressPlugin'))
	{
		echo "<div class='error'><p>" . __("The Lyquidity VAT plugin for EDD must be installed to use the EDD integration.", "vat_moss") . "</p></div>";				
	}

	if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== 'moss-submissions-settings') return;

	$settings =  vat_moss()->settings;
	$vat_number = $settings->get( 'vat_number', '' );

	$out = new \StdClass();
	$country = get_establishment_country();
	if (!perform_simple_check("$country$vat_number", $out))
	{
		echo "<div class='error'><p>$out->message</p></div>";
	}
	
	$fixed_establishment = vat_moss()->settings->get( 'fixed_establishment', '' );
	if ( !is_bool( $fixed_establishment ) || ( is_bool( $fixed_establishment ) && !$fixed_establishment ))
	{
		echo "<div class='error'><p>" . __("The option to confirm the plugin will be used only for single establishment companies has not been checked. This plug-in cannot be use by companies with registrations in multiple EU states.", "vat_moss") . "</p></div>";		
	}

	$names = array(VAT_MOSS_ACTIVATION_ERROR_NOTICE, VAT_MOSS_ACTIVATION_UPDATE_NOTICE, VAT_MOSS_DEACTIVATION_ERROR_NOTICE, VAT_MOSS_DEACTIVATION_UPDATE_NOTICE);
	array_walk($names, function($name) {

		$message = get_transient($name);
		delete_transient($name);

		if (empty($message)) return;
		$class = strpos($name,"UPDATE") === FALSE ? "error" : "updated";
		echo "<div class='$class'><p>$message</p></div>";

	});

}
add_action('admin_notices', '\lyquidity\vat_moss\admin_notices');