<?php
/**
 * MOSS Main controller for submissions
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

if ( ! defined( 'STATE_UNKNOWN' ) )
	define( 'STATE_UNKNOWN', 'unknown' );

if ( ! defined( 'STATE_NOT_GENERATED' ) )
	define( 'STATE_NOT_GENERATED', 'not_generated' );

if ( ! defined( 'STATE_FAILED' ) )
	define( 'STATE_FAILED', 'failed' );

if ( ! defined( 'STATE_GENERATED' ) )
	define( 'STATE_GENERATED', 'generated' );

include VAT_MOSS_INCLUDES_DIR . 'lists/class-submissions.php';
include VAT_MOSS_INCLUDES_DIR . 'lists/class-logs.php';
include VAT_MOSS_INCLUDES_DIR . 'admin/new-submission.php';
include VAT_MOSS_INCLUDES_DIR . 'lists/class-sales.php';
include VAT_MOSS_INCLUDES_DIR . 'admin/edit-submission.php';
include VAT_MOSS_INCLUDES_DIR . 'admin/delete-submission.php';
include VAT_MOSS_INCLUDES_DIR . 'admin/save_submission.php';
include VAT_MOSS_INCLUDES_DIR . 'admin/view_submission.php';
include VAT_MOSS_INCLUDES_DIR . 'admin/submit_submission.php';

$locale = ( isset($_COOKIE['locale']) )
	? $_COOKIE['locale']
	: (isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] )
		? $_SERVER['HTTP_ACCEPT_LANGUAGE']
		: 'en_GB'
	  );
setlocale( LC_ALL, $locale );

function moss_submissions()
{
	global $moss_options;

	 add_thickbox();

	if ( isset( $_REQUEST['action'] ) && 'check_submission' == $_REQUEST['action'] ) {

		if ( ! isset($_REQUEST['id'] ) )
		{
			echo "<div class='error'><p>" . esc_html__( 'There is no id of the submission for which to show logs.', 'vat_moss' ) . "</p></div>";

			show_submissions();
			return;
		}

		check_submission( $_REQUEST['id'] );
		show_submissions();
	}

	else if ( isset( $_REQUEST['action'] ) && 'delete_submission_log' == $_REQUEST['action'] ) {

		if ( ! isset($_REQUEST['submission_id'] ) )
		{
			echo "<div class='error'><p>" . esc_html__( 'There is no id of the submission for which to show logs.', 'vat_moss' ) . "</p></div>";

			show_submissions();
			return;
		}

		if ( ! isset($_REQUEST['id'] ) )
		{
			echo "<div class='error'><p>" . esc_html__( 'There is no id of the submission log to delete.', 'vat_moss' ) . "</p></div>";

			show_submission_logs( $_REQUEST['submission_id'] );
			return;
		}

		delete_submission_log( $_REQUEST['id'] );
		show_submission_logs( $_REQUEST['submission_id'] );

	}

	else if ( isset( $_REQUEST['action'] ) && 'show_submission_logs' == $_REQUEST['action'] ) {

		if ( ! isset( $_REQUEST['id'] ) )
		{
			echo "<div class='error'><p>" . esc_html__( 'There is no id of the submission for which to show logs.', 'vat_moss' ) . "</p></div>";

			show_submissions();
			return;
		}

		show_submission_logs( $_REQUEST['id'] );

	} else if ( isset( $_REQUEST['action'] ) && 'submit_submission' == $_REQUEST['action'] ) {

		if ( ! isset( $_REQUEST['id'] ) )
		{
			echo "<div class='error'><p>" . esc_html__( 'There is no id of the submission to submit.', 'vat_moss' ) . "</p></div>";

			show_submissions();
			return;
		}

		submit_submission( $_REQUEST['id'] );

	} else if ( isset( $_REQUEST['action'] ) && 'view_submission' == $_REQUEST['action'] ) {

		if ( ! isset( $_REQUEST['id'] ) )
		{
			echo "<div class='error'><p>" . esc_html__( 'There is no id of the submission details to view.', 'vat_moss' ) . "</p></div>";

			show_submissions();
			return;
		}

		view_submission( $_REQUEST['id'] );

	} else if ( isset( $_REQUEST['action'] ) && 'new_submission' === $_REQUEST['action'] )  {

		if ( isset( $_REQUEST['save_submission']))
			save_submission();
		else
			new_submission();

	} else if ( isset( $_REQUEST['action'] ) && 'edit_submission' === $_REQUEST['action'] ) {

		if ( ! isset( $_REQUEST['id'] ) && ! isset( $_REQUEST['submission_id'] ) )
		{
			echo "<div class='error'><p>" . esc_html__( 'There is no id of the submission to edit.', 'vat_moss' ) . "</p></div>";

			show_submissions();
			return;
		}

		if ( isset( $_REQUEST['save_submission']))
			save_submission();
		else
			edit_submission( isset( $_REQUEST['id']) ? $_REQUEST['id'] : $_REQUEST['submission_id'] );

	} else if ( isset( $_REQUEST['action'] ) && 'delete_submission' == $_REQUEST['action'] ) {

		if ( ! isset( $_REQUEST['id'] ) )
		{
			echo "<div class='error'><p>" . esc_html__( 'There is no id of the submission to delete.', 'vat_moss' ) . "</p></div>";

			show_submissions();
			return;
		}

		delete_submission( $_REQUEST['id'] );
		show_submissions();

	} else if ( (isset( $_REQUEST['action'] ) && 'save_submission' === $_REQUEST['action'] ) ) {

		save_submission();

	} else {

		show_submissions();

	}
}

function show_submission_logs($submission_id)
{
		$logs_list = new MOSS_logs( $submission_id );
		$logs_list->prepare_items();
?>
		<div class="wrap">
			<a href='?page=moss-submissions' class='button secondary' style='float: right; margin-top: 10px; margin-right: 10px;'><?php esc_html_e( 'Submissions', 'vat_moss' ); ?></a>
			<h2><?php esc_html_e( 'Submission Logs', 'vat_moss' ); ?></h2>
			<?php do_action( 'moss_overview_top' ); ?>

				<input type="hidden" name="page" value="moss-submission-logs" />

				<?php $logs_list->views() ?>
				<?php $logs_list->display() ?>

			<?php do_action( 'moss_submission_logs_page_bottom' ); ?>
		</div>
<?php
}

function show_submissions( $moss_lines = false )
{
		$submissions_list = new MOSS_Submissions();
		$submissions_list->prepare_items();
		$msg10 = __( 'To find information to help you use this plug-in', 'vat_moss' );
		$msg11 = __( 'visit the plug-in page on our site', 'vat_moss' );
		$msg2 = __( 'Please note that to ensure we are able to process any submissions you make, to verify any completed submissions or to be able to answer questions about any submissions you make that fail, details of your submission will be held on our site.', 'vat_moss' );
?>
		<div class="wrap">
			<a href='?page=moss-submissions' class='button secondary' style='float: right; margin-top: 10px; margin-right: 10px;'><?php esc_html_e( 'Refresh', 'vat_moss' ); ?></a>
			<h2><?php esc_html_e( 'Submissions', 'vat_moss' ); ?>
				<a href="?page=moss-submissions&action=new_submission" class="add-new-h2"><?php esc_html_e( 'Add New', 'vat_moss' ); ?></a>
			</h2>


<?php
			// @codingStandardsIgnoreStart
			echo '<p>' . $msg10 . ' <a href="http://www.wproute.com/wordpress-vat-moss-reporting/">' . $msg11 . '></a>.</p>';
			echo '<p>' . $msg2 . '</p>';
			// @codingStandardsIgnoreEnd
if ( function_exists( 'wincache_ucache_get' ) && ini_get( 'wincache.ucenabled' ) )
{
?>
			<p>Wincache is active on this server and user caching is enabled.  This configuration may cause in invalid query results.</p>
<?php
}

			do_action( 'moss_overview_top' ); ?>
			<form id="moss-filter" method="get" action="<?php
			// @codingStandardsIgnoreStart
			echo admin_url( 'admin.php?page=moss-submissions' );
			// @codingStandardsIgnoreEnd
			?>">

				<?php // $submissions_list->search_box( __( 'Search', 'vat_moss' ), 'moss-submissions' ); ?>

				<input type="hidden" name="page" value="moss-submissions" />

				<?php
					$submissions_list->views();
					$submissions_list->display();
				?>

			</form>
			<?php
				do_action( 'moss_submissions_page_bottom' );
			?>

			<div id='moss_summary'><!-- This placeholder will be filled by an ajax call result --></div>

		</div>
<?php
}

function generate_moss_summary_html( $moss_lines, $title )
{
	if ( ! is_array( $moss_lines ) ) return;

	require_once VAT_MOSS_INCLUDES_DIR . 'admin/submit_submission.php';
	require_once('replacement-functions.php');

	include VAT_MOSS_INCLUDES_DIR . 'lists/class-moss-summary.php';
?>

	<h3><?php esc_html_e( 'MOSS Summary for submission ' . $title, 'vat_moss' ); ?></h3>

<?php
	$moss_summary = new MOSS_Summary( $moss_lines );
	$moss_summary->prepare_items();
	$moss_summary->views();
	$moss_summary->display();
}

function generate_moss_summary($id, $vat_records)
{
	try
	{
		$moss_lines = array();

		$vat_payments = $vat_records;
		$moss_lines = array();

		// Now aggregate by CountryCode, Rate Type and Rate
		// The end result is an array like:
		// array(
		//	 [DE] array(
		//		['reduced'] array(
		//			['0.19'] array (
		//				['tax'] => 1.23
		//				['net'] => 6.47
		//			)
		//			['0.15'] array (
		//				['tax'] => 1.5
		//				['net'] => 10.00
		//			)
		//		)
		//	 )
		//	 [BE] array(
		//		['reduced'] array(
		//			['0.21'] array (
		//				['tax'] => 1.23
		//				['net'] => 5.86
		//			)
		//		)
		//		['suppreduced'] array(
		//			['0.10'] array (
		//				['tax'] => 1.5
		//				['net'] => 15.00
		//			)
		//		)
		//	 )
		//)
		//
		foreach ( $vat_payments as $key => $payment )
		{
			// Check the country exists
			$country = isset( $moss_lines[ $payment['country_code'] ] )
				? $moss_lines[ $payment['country_code'] ]
				: array();

			// Check the VAT type exists
			$vat_type = isset( $country[ $payment['vat_type'] ] )
				? $country[ $payment['vat_type'] ]
				: array();

			$index = strval( $payment['vat_rate'] );

			// Check the rate exists
			$vat_rate = isset( $vat_type[ $index ] )
				? $vat_type[ $index ]
				: array( 'tax' => 0, 'net' => 0 );

			// Accumulate the values
			$vat_rate['tax'] += $payment['tax'];
			$vat_rate['net'] += $payment['net'];

			// Re-assign the variables
			$vat_type[ $index ] = $vat_rate;
			$country[ $payment['vat_type'] ] = $vat_type;
			$moss_lines[ $payment['country_code'] ] = $country;
		}

		return $moss_lines;
	}
	catch(Exception $ex)
	{
		report_severe_error( $id, $ex, "An unexpected error occurred creating the MOSS sales summary." );
		return false;
	}
}

/**
 * Creates error messages
 *
 * @since 1.0
 *
 * @array|string errors
 *
 */
function report_errors($errors)
{
	if ( ! is_array( $errors ) ) $errors = array( $errors );

	foreach ( $errors as $source_error )
	{
		if ( ! is_array( $source_error ) ) $source_error = array( $source_error );
		foreach ( $source_error as $error )
			// @codingStandardsIgnoreStart
			echo "<div class='error'><p>$error</p></div>";
			// @codingStandardsIgnoreEnd
	}
}

/**
 * Register an error to be displayed to the user
 */
function add_submission_error( $message ) {

	set_transient( VAT_MOSS_ACTIVATION_ERROR_NOTICE, $message, 10 );

}

/**
 * Register information to be displayed to the user
 */
function add_submission_info( $message ) {

	set_transient( VAT_MOSS_ACTIVATION_UPDATE_NOTICE, $message, 10 );

}

function mossales_count($mosssales)
{
	$result = 0;

	if ( is_array( $mosssales ) )
	{
		foreach ( $mosssales as $key => $integration )
		{
			if ( ! is_array( $integration ) ) continue;
			$result += count( $integration );
		}
	}

	return $result;
}

?>
