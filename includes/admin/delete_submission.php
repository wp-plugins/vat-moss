<?php

/**
 * MOSS Delete a definition
 *
 * @package     vat-moss
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

namespace lyquidity\vat_moss;

function delete_submission($id, $delete_post = true)
{
	if (!current_user_can('delete_submissions'))
	{
		echo "<div class='error'><p>" . __('You do not have rights to delete a submission.', 'vat_moss' ) . "</p></div>";
		return;
	}

	// Begin by deleting the records associated with the submission
	$mosssales = maybe_unserialize(get_post_meta($id, 'mosssales', true));

	if ( mossales_count($mosssales) == 0 )
	{
		report_errors( array( __('There are no transactions selected for this submission.', 'vat_moss' ) ) );
	}
	else
	{
		$errors = vat_moss()->integrations->delete_vat_information($mosssales);
		if ($errors)
		{
			report_errors($errors);
			return false;
		}
	}
	
	if ($delete_post)
	{
		$args = array(
			'post_parent' => $id,
			'post_type'   => 'moss_submission_log', 
			'posts_per_page' => -1,
			'post_status' => 'any' 
		);

		$logs = get_children( $args );

		foreach($logs as $log){		
			delete_submission_log($log->ID);
		}

		wp_delete_post( $id, $delete_post );

		delete_post_meta( $id, 'vat_number' );
		delete_post_meta( $id, 'submitter' );
		delete_post_meta( $id, 'email' );
		delete_post_meta( $id, 'from_year' );
		delete_post_meta( $id, 'from_month' );
		delete_post_meta( $id, 'to_year' );
		delete_post_meta( $id, 'to_month' );
		delete_post_meta( $id, 'submission_period' );
		delete_post_meta( $id, 'submission_year' );
		delete_post_meta( $id, 'mosssales' );
		delete_post_meta( $id, 'output_format' );
	}

	return true;
}

function delete_submission_log($id, $delete_post = true)
{
	if (!current_user_can('delete_submission_logs'))
	{
		echo "<div class='error'><p>" . __('You do not have rights to delete a submission log.', 'vat_moss' ) . "</p></div>";
		return;
	}

	delete_post_meta( $id, 'xml_request' );
	delete_post_meta( $id, 'xml_final_request' );
	delete_post_meta( $id, 'xml_initial_request' );
	delete_post_meta( $id, 'xml_initial_response' );
	
	delete_post_meta( $id, 'error_information' );
	delete_post_meta( $id, 'correlationid' );
	delete_post_meta( $id, 'error_message' );
	delete_post_meta( $id, 'submission_due_date' );
	delete_post_meta( $id, 'result' );

	wp_delete_post( $id, $delete_post );
	
	return true;
}

?>