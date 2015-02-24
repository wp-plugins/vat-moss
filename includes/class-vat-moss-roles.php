<?php
/**
 * Roles and Capabilities
 *
 * @package     vat-moss
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
*/

namespace lyquidity\vat_moss;

/**
 * MOSS_Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 * These roles let us have MOSS Submitter, MOSS Reviewer, etc, each of whom can do
 * certain things within the VAT MOSS information
 *
 * @since 1.0
 */
class MOSS_Roles {

	/**
	 * Get things going
	 *
	 * @since 1.4.4
	 */
	public function __construct() {

	}

	/**
	 * Add new shop roles with default WP caps
	 *
	 * @access public
	 * @since 1.4.4
	 * @return void
	 */
	public function add_roles() {
		add_role( 'moss_submitter', __( 'MOSS Submitter', 'vat_moss' ), array(
			'read'                   => true,
			'edit_posts'             => true,
			'delete_posts'           => true
		) );

		add_role( 'moss_reviewer', __( 'MOSS Reviewer', 'vat_moss' ), array(
		    'read'                   => true,
		    'edit_posts'             => false,
		    'delete_posts'           => false
		) );

	}

	/**
	 * Add new shop roles with default WP caps
	 *
	 * @access public
	 * @since 1.4.4
	 * @return void
	 */
	public function remove_roles() {
		remove_role( 'moss_submitter' );
		remove_role( 'moss_reviewer' );
	}

	/**
	 * Add new shop-specific capabilities
	 *
	 * @access public
	 * @since  1.4.4
	 * @global WP_Roles $wp_roles
	 * @return void
	 */
	public function add_caps() {
		global $wp_roles;

		if ( class_exists('WP_Roles') ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'moss_submitter', 'view_submissions' );
			$wp_roles->add_cap( 'moss_submitter', 'send_submissions' );
			$wp_roles->add_cap( 'moss_submitter', 'export_submissions' );
			$wp_roles->add_cap( 'moss_submitter', 'edit_submissions' );

			$wp_roles->add_cap( 'administrator', 'view_submissions' );
			$wp_roles->add_cap( 'administrator', 'send_submissions' );
			$wp_roles->add_cap( 'administrator', 'export_submissions' );
			$wp_roles->add_cap( 'administrator', 'edit_submissions' );
			$wp_roles->add_cap( 'administrator', 'delete_submissions' );
			$wp_roles->add_cap( 'administrator', 'delete_submission_logs' );

			$wp_roles->add_cap( 'moss_reviewer', 'view_submissions' );
			$wp_roles->add_cap( 'moss_reviewer', 'export_submissions' );
			$wp_roles->add_cap( 'moss_reviewer', 'edit_submissions' );

			// Add the main post type capabilities
			$capabilities = $this->get_core_caps();
			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'moss_submitter', $cap );
					$wp_roles->add_cap( 'administrator', $cap );
					$wp_roles->add_cap( 'moss_reviewer', $cap );
				}
			}
		}
	}

	/**
	 * Gets the core post type capabilities
	 *
	 * @access public
	 * @since  1.4.4
	 * @return array $capabilities Core post type capabilities
	 */
	public function get_core_caps() {
		$capabilities = array();

		$capability_types = array( 'submission' );

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"send_{$capability_type}",
				"edit_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_{$capability_type}_logs",
				"delete_sent_{$capability_type}s",
				"edit_sent_{$capability_type}s",
			);
		}

		return $capabilities;
	}

	/**
	 * Remove core post type capabilities (called on uninstall)
	 *
	 * @access public
	 * @since 1.5.2
	 * @return void
	 */
	public function remove_caps() {
		
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			/** Shop Manager Capabilities */
			$wp_roles->remove_cap( 'moss_submitter', 'view_submissions' );
			$wp_roles->remove_cap( 'moss_submitter', 'send_submissions' );
			$wp_roles->remove_cap( 'moss_submitter', 'export_submissions' );
			$wp_roles->remove_cap( 'moss_submitter', 'edit_submissions' );

			$wp_roles->remove_cap( 'administrator', 'view_submissions' );
			$wp_roles->remove_cap( 'administrator', 'send_submissions' );
			$wp_roles->remove_cap( 'administrator', 'export_submissions' );
			$wp_roles->remove_cap( 'administrator', 'edit_submissions' );
			$wp_roles->remove_cap( 'administrator', 'delete_submissions' );
			$wp_roles->remove_cap( 'administrator', 'delete_submission_logs' );

			$wp_roles->remove_cap( 'moss_reviewer', 'view_submissions' );
			$wp_roles->remove_cap( 'moss_reviewer', 'export_submissions' );
			$wp_roles->remove_cap( 'moss_reviewer', 'edit_submissions' );

			/** Remove the Main Post Type Capabilities */
			$capabilities = $this->get_core_caps();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->remove_cap( 'moss_submitter', $cap );
					$wp_roles->remove_cap( 'administrator', $cap );
					$wp_roles->remove_cap( 'moss_reviewer', $cap );
				}
			}
		}
	}
}
$vat_moss_roles = new MOSS_Roles;
