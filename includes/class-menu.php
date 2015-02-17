<?php

/**
 * MOSS Main menu class
 *
 * @package     vat-moss
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

 namespace lyquidity\vat_moss;

class MOSS_Admin_Menu {
	
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	public function register_menus() {
	
		// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page( __( 'MOSS Submissions', 'vat_moss' ), __( 'MOSS', 'vat_moss-wp' ), 'view_submissions', 'moss-submissions', '\lyquidity\vat_moss\moss_submissions', 'dashicons-book' );
		// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		add_submenu_page( 'moss-submissions', __( 'Submissions', 'vat_moss' ), __( 'Submissions', 'vat_moss' ), 'view_submissions', 'moss-submissions', '\lyquidity\vat_moss\moss_submissions' );
		add_submenu_page( 'moss-submissions', __( 'Settings', 'vat_moss' ), __( 'Settings', 'vat_moss' ), 'view_submissions', 'moss-submissions-settings', '\lyquidity\vat_moss\moss_submissions_settings' );
	}

}
$moss_menu = new MOSS_Admin_Menu;