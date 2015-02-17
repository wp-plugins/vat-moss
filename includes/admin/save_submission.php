<?php

/**
 * MOSS Save submission definition
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

function save_submission()
{
	error_log("Save Submission");

	$submission_id = isset($_REQUEST['submission_id']) ? $_REQUEST['submission_id'] : 0;

	if ( !isset($_REQUEST['_wp_nonce']) ||
		 !wp_verify_nonce( $_REQUEST['_wp_nonce'], 'moss_submission' ) )
	{
		echo "<div class='error'><p>" . __('The attempt to save the submission is not valid.  The nonce does not exist or cannot be verified.', 'vat_moss' ) . "</p></div>";
		if ($submission_id)
			edit_submission($submission_id);
		else
			new_submission();
		return;
	}

 	if (!isset($_REQUEST['mosssale']))
	{
		echo "<div class='error'><p>" . __('There are no selected sales from which to create a submission', 'vat_moss' ) . "</p></div>";
		if ($submission_id)
			edit_submission($submission_id);
		else
			new_submission();
		return;
	}

	if (!isset($_REQUEST['moss_settings_title']) || !$_REQUEST['moss_settings_title'])
	{
		echo "<div class='error'><p>" . __('The submission does not have a title', 'vat_moss' ) . "</p></div>";
		if ($submission_id)
			edit_submission($submission_id);
		else
			new_submission();
		return;		
	}

	global $selected;
	$selected = array();
	$totalnetvalue = 0;
	$totaltaxvalue = 0;

	foreach(array_keys( MOSS_WP_Integrations::get_enabled_integrations() ) as $integration)
	{
		if (!isset($_REQUEST['mosssale'][$integration]['id'])) continue;

		$sales = array();

		foreach($_REQUEST['mosssale'][$integration]['id'] as $key => $value)
		{
			$sales[$value] = $value;
			$totalnetvalue += isset( $_REQUEST['mosssale'][$integration]['net'][$value] )
				? array_sum( $_REQUEST['mosssale'][$integration]['net'][$value] )
				: 0;
			$totaltaxvalue += isset( $_REQUEST['mosssale'][$integration]['tax'][$value] )
				? array_sum( $_REQUEST['mosssale'][$integration]['tax'][$value] )
				: 0;
		}

//		error_log(print_r($sales,true));
		$selected[$integration] = $sales;
	}

	// Grab the post information
	$vrn		= isset($_REQUEST['moss_settings_vat_number'])	? $_REQUEST['moss_settings_vat_number']	: vat_moss()->settings->get('vat_number');
	$submitter	= isset($_REQUEST['moss_settings_submitter'])	? $_REQUEST['moss_settings_submitter']	: vat_moss()->settings->get('submitter');
	$email		= isset($_REQUEST['moss_settings_email'])		? $_REQUEST['moss_settings_email']		: vat_moss()->settings->get('email');
	$title		= isset($_REQUEST['moss_settings_title'])		? $_REQUEST['moss_settings_title']		: vat_moss()->settings->get('title');

	$from_year	= isset( $_REQUEST[ 'from_year'  ] )			? $_REQUEST[ 'from_year' ]				: date('Y');
	$from_month	= isset( $_REQUEST[ 'from_month' ] )			? $_REQUEST[ 'from_month' ]				: date('m');
	$to_year	= isset( $_REQUEST[ 'to_year'    ] )			? $_REQUEST[ 'to_year' ]				: date('Y');
	$to_month	= isset( $_REQUEST[ 'to_month'   ] )			? $_REQUEST[ 'to_month' ]				: date('m');

	$submission_period	= isset($_REQUEST['submission_period'])	? $_REQUEST['submission_period']		: floor((date('n') - 1) / 3) + 1;
	$submission_year	= isset($_REQUEST['submission_year'])	? $_REQUEST['submission_year']			: date('Y');
	$submission_key		= isset($_REQUEST['submission_key'])	? $_REQUEST['submission_key']			: '';

	if ($submission_id)
	{
		// Begin by deleting the records associated with the submission
		if (!delete_submission($submission_id, false))
		{
			edit_submission($submission_id);
			return;
		}

		wp_update_post(
			array(
				'ID'				=> $submission_id,
				'post_title'		=> $title,
				'post_modified'		=> date('Y-m-d H:i:s'),
				'post_modified_gmt'	=> gmdate('Y-m-d H:i:s')
			 )
		);
	}
	else
	{
		// Create a post 
		$submission_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_type'	   => 'moss_submission',
				'post_content' => '',
				'post_status'  => STATE_NOT_GENERATED
			 )
		);
	}

	update_post_meta( $submission_id, 'vat_number',			$vrn				);
	update_post_meta( $submission_id, 'submitter', 			$submitter			);
	update_post_meta( $submission_id, 'email',				$email				);

	update_post_meta( $submission_id, 'from_year',			$from_year			);
	update_post_meta( $submission_id, 'from_month',			$from_month			);
	update_post_meta( $submission_id, 'to_year',			$to_year			);
	update_post_meta( $submission_id, 'to_month',			$to_month			);

	update_post_meta( $submission_id, 'submission_period',	$submission_period	);
	update_post_meta( $submission_id, 'submission_year',	$submission_year	);
	delete_post_meta( $submission_id, 'totalvalue'		);
	update_post_meta( $submission_id, 'totalnetvalue',		$totalnetvalue		);
	update_post_meta( $submission_id, 'totaltaxvalue',		$totaltaxvalue		);

	update_post_meta( $submission_id, 'mosssales',			serialize($selected)	);

	// Update the sales records
	$errors = vat_moss()->integrations->update_vat_information($submission_id, '', $selected);
	if ($errors)
	{
		report_errors($errors);

		// If there were errors rollback the update
		error_log("delete_submission");
		delete_submission($submission_id);
		new_submission();
		return;
	}

	if ($submission_key)
		update_post_meta( $submission_id, 'submission_key',	$submission_key		);
	else
		delete_post_meta( $submission_id, 'submission_key'						);

	$message = __( "Submission details saved", 'vat_moss' );
	echo "<div class='updated'><p>$message</p></div>";

	if ($submission_id)
		edit_submission($submission_id);
	else
		show_submissions();
}

?>