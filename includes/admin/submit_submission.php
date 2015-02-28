<?php

/**
 * MOSS Submit transactions to create MOSS report
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
 * Sends a submission to HMRC and handles any errors
 *
 * @int id The id of the submission being sent
 */
function submit_submission($id)
{
	error_log("submit_submission");
	if (!current_user_can('send_submissions'))
	{
		echo "<div class='error'><p>" . __('You do not have rights to submit an EC MOSS return', 'vat_moss' ) . "</p></div>";
		show_submissions();
		return;
	}

	$post			= get_post($id);
	if ($post->post_status === STATE_GENERATED)
	{
		echo "<div class='updated'><p>" . __('A report for this submission has already been generated', 'vat_moss' ) . "</p></div>";
		show_submissions();
		return;		
	}

	$selected		= maybe_unserialize(get_post_meta($id, 'mosssales', true));
	$moss_lines		= null;

	if ( count($selected) == 0 )
	{
		report_errors( array( __('There are no transactions selected for this submission.', 'vat_moss' ) ) );
	}
	else
	{
		$vat_records	= vat_moss()->integrations->get_vat_record_information($selected);

		if (!$vat_records || !is_array($vat_records) || !isset($vat_records['status']))
		{
			report_errors( array( __('There was an error creating the information to generate a submission request.', 'vat_moss' ) ) );
		}
		else if ($vat_records['status'] === 'error')
		{
			report_errors( $vat_records['messages'] );
		}
		else if (!isset($vat_records['information']) || count($vat_records['information']) == 0 ) 
		{
			report_errors( array( __('There are no transactions associated with this submission.', 'vat_moss' ) ) );
		}
		else
		{
			// If successful $vat_records will be an array of this form:
			/*

					'id'
					'item_id'
					'first'
					'purchase_key'
					'date'
					'submission_id'
					'net'
					'tax'
					'vat_rate'
					'vat_type'
					'country_code'
					'source'

			 */

			$moss_lines = generate_moss_summary( $id, $vat_records['information'] );
			if ($moss_lines === false) return;

			$vrn					= get_post_meta( $id, 'vat_number',			true );
			$submitter				= get_post_meta( $id, 'submitter',			true );
			$email					= get_post_meta( $id, 'email',				true );
			$submission_period		= get_post_meta( $id, 'submission_period',	true );
			$submission_year		= get_post_meta( $id, 'submission_year',	true );
			$submission_key			= get_post_meta( $id, 'submission_key',		true );
			$company_name			= get_company_name();
			$currency				= get_default_currency();
			$establishment_country	= get_establishment_country();
			$output_format			= get_output_format();

			$data = array(
				'edd_action'			=> 'submit_moss',
				'vrn'					=> $vrn,
				'company_name'			=> $company_name,
				'submitter' 			=> $submitter,
				'email'					=> $email,
				'submission_period'		=> $submission_period,
				'submission_year'		=> $submission_year,
				'currency'				=> $currency,
				'establishment_country' => $establishment_country,
				'output_format'			=> $output_format,
				'mosslines'				=> $moss_lines
			);
			
			if ($submission_key) $data['submission_key'] = $submission_key;
			
			$args = array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => $data,
				'cookies' => array()
			);

			process_response( $id, $args );

		}
	}
	
	show_submissions( $moss_lines );
	return;
}

function process_response($id, $args)
{
	$json = remote_get_handler( wp_remote_post( VAT_MOSS_STORE_API_URL, $args ) );
	$error = "";
	$result = json_decode($json);

	// switch and check possible JSON errors
	switch (json_last_error()) {
		case JSON_ERROR_NONE:
			$error = ''; // JSON is valid
			break;
		case JSON_ERROR_DEPTH:
			$error = 'Maximum stack depth exceeded.';
			break;
		case JSON_ERROR_STATE_MISMATCH:
			$error = 'Underflow or the modes mismatch.';
			break;
		case JSON_ERROR_CTRL_CHAR:
			$error = 'Unexpected control character found.';
			break;
		case JSON_ERROR_SYNTAX:
			$error = 'Syntax error, malformed JSON.';
			break;
		// only PHP 5.3+
		case JSON_ERROR_UTF8:
			$error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
			break;
		default:
			$error = 'Unknown JSON error occured.';
			break;
	}

	if($error !== '') {
		report_severe_error( $id, $result, $error );
	}
	else if (!is_object( $result ))
	{
		report_severe_error( $id, $result, "The response from the request to process the submission is not an array and this should never happen." );
	}
	else if(!isset( $result->status ))
	{
		report_severe_error(  $id, $result, "The response from the request to process the submission is an array but it does not contain a 'status' element" );
	}
	else
	{
		// The sources of error are:
		//	the failure to complete the wp_remote_post ('status' === 'failed' + 'message')
		//	an error processing the post (e.g. missing request data) ('status' === 'error' + 'message')
		//	an error reported by the gateway ('status' === 'success' + 'error_message' in the submission log)

		if (true)
		{
			if ( $result->status === 'failed' )
			{
				report_severe_error( $id, $result, isset( $result->message ) ? $result->message : "An error posting the submission has occurred but the reason is unknown" );
			}
			else
			{

				if ($result->status === 'error'  )
				{
					report_severe_error( $id, $result, isset( $result->message ) ? $result->message : "An error has occurred validating the submission on the remote server but the reason is unknown" );
				}
				else
				if ($result->status !== 'valid' && $result->status !== 'success'  ) // Licence issue
				{
					report_severe_error( $id, $result, isset( $result->message ) ? $result->message : "An error has occurred validating the license key" );
				}
				else
				{
					process_submission_status( isset( $result->state ) ? $result->state : STATE_FAILED);

					// Copy the results of the 'submission' and 'submission_log' arrays to posts on this site
					$submission_log_id = wp_insert_post(
						array(
							'post_title'	=> isset( $result->submission_log->title ) ? $result->submission_log->title : "Submission error log ($id)",
							'post_type'		=> 'moss_submission_log',
							'post_status'	=> property_exists( $result, 'state' ) ? $result->state : STATE_FAILED,
							'post_parent'	=> $id,
							'post_content'	=> isset( $result->submission_log->content ) ? $result->submission_log->content : ""
						)
					);

					update_post_meta( $submission_log_id, 'result', $result );

					wp_update_post( array(
						'ID'				=> $id,
						'post_status'		=> property_exists( $result, 'state' ) ? $result->state : STATE_FAILED,
						'post_modified'		=> date('Y-m-d H:i:s'),
						'post_modified_gmt'	=> gmdate('Y-m-d H:i:s')
					));

					if ( property_exists( $result, 'body' ) )
					{
						update_post_meta( $id, 'report', $result->body );
						update_post_meta( $id, 'output_format', $args['body']['output_format'] );
					}
				}
			}
		}
	}
}

function report_severe_error($submission_id, $result, $message)
{
	if (is_array($message))
		$message = implode('<br/>', $message);

	report_errors( "Severe error. $message" );

	// Create a log post of the submission
	$submission_log_id = wp_insert_post(
		array(
			'post_title'	=> "Submission log ($submission_id)",
			'post_type'		=> 'moss_submission_log',
			'post_status'	=> STATE_FAILED,
			'post_parent'	=> $submission_id
		 )
	);

	update_post_meta( $submission_log_id, 'error_information', serialize($result) );
	update_post_meta( $submission_log_id, 'error_message', $message );

	wp_update_post( array(
		'ID'				=> $submission_id,
		'post_status'		=> STATE_FAILED,
		'post_modified'		=> date('Y-m-d H:i:s'),
		'post_modified_gmt'	=> gmdate('Y-m-d H:i:s')
	));
}

function process_submission_status($submission_state)
{
	switch($submission_state)
	{
		case STATE_GENERATED:
			echo "<div class='updated'><p>" . __('The EC MOSS submission has been successful.', 'vat_moss' ) . "</p></div>";
			break;
			
		default:
			echo "<div class='error'><p>" . __('The attempt to submit the EC MOSS return failed. See the log for more information.', 'vat_moss' ) . "</p></div>";
			break;
	}
}

function format_xml($xml)
{
	$domxml = new \DOMDocument('1.0');
	$domxml->preserveWhiteSpace = false;
	$domxml->formatOutput = true;
	$domxml->loadXML($xml);
	return $domxml->saveXML();
}

function remote_get_handler($response, $message = 'Error processing submission')
{
	if (is_a($response,'WP_Error'))
	{
		error_log(print_r($response,true));
		$error = array(
			'status' => 'failed',
			'message' => $response->get_error_message()
		);

		return json_encode($error);
	}
	else
	{
		$code = isset( $response['response']['code'] ) && isset( $response['response']['code'] )
			? $response['response']['code']
			: 'Unknown';

		if ( $code == 200 && isset( $response['body'] ))
		{
			return $response['body'];
		}
		else
		{
			$error = array(
				'status' => 'failed',
				'message' => "$message ($code)"
			);

			return json_encode($error);
		}
	}
}

?>
