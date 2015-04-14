<?php

/**
 * MOSS Create a new definition (also used by 'edit' and 'view')
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

function get_setting($id, $key)
{
	$result = '';

	if ($id)
		$result = get_post_meta( $id, $key, true );
		
	if (!empty($result)) return $result;

	return isset($_REQUEST[$key]) ? $_REQUEST[$key] : vat_moss()->settings->get($key);
}

function new_submission($from_year = null, $from_month = null, $to_year = null, $to_month = null, $submission_id = 0, $read_only = false)
{
	global $selected;
	$locale = localeconv();

	if (!current_user_can('edit_submissions'))
	{
		echo "<div class='error'><p>" . __('You do not have rights to perform this action.', 'vat_moss' ) . "</p></div>";
		show_submissions();
		return;
	}

	$state = get_post_status($submission_id);
	if (($state === STATE_SUBMITTED || $state === STATE_ACKNOWLEDGED) && !$read_only)
	{
		echo "<div class='error'><p>" . __('This action is not valid on a submission that is complete or acknowledged.', 'vat_moss' ) . "</p></div>";
		show_submissions();
		return;
	}

	$title = $submission_id
		? ($read_only
			? __( 'View Submission', 'vat_moss' )
			: __( 'Edit Submission', 'vat_moss' )
		  )
		: __( 'New Submission', 'vat_moss' );
	$title .= $submission_id ? " ($submission_id)" : "";

	$sales_list = new MOSS_Sales_List($from_year, $from_month, $to_year, $to_month, $submission_id, $read_only);
	$sales_list->prepare_items();

	$vrn			= get_setting( $submission_id, 'vat_number');
	$submitter		= get_setting( $submission_id, 'submitter');
	$email			= get_setting( $submission_id, 'email');

	$submission_key	= get_setting( $submission_id, 'submission_key');
	
	$submission_period = ($submission_id)
		? $result = get_post_meta( $submission_id, 'submission_period', true )
		: floor((date('n') - 1) / 3) + 1;

	$submission_year = ($submission_id)
		? $result = get_post_meta( $submission_id, 'submission_year', true )
		: 0;

	$totalnetvalue = get_post_meta( $submission_id, 'totalnetvalue', true );
	$totaltaxvalue = get_post_meta( $submission_id, 'totaltaxvalue', true );

	$submission = $submission_id ? get_post($submission_id) : null;
	$post_title	= $submission_id ? $submission->post_title : '';

	$test_mode = get_post_meta( $submission_id, 'test_mode', true );
?>

	<style>
		.moss-submission-header-details td span {
			line-height: 29px;
		}
	</style>

	<div class="wrap">

<?php	do_action( 'moss_overview_top' ); ?>

		<form id="vat-moss-sales" method="post">

<?php		submit_button( __( 'Save', 'vat_moss' ), 'primary', 'save_submission', false, array( 'style' => 'float: right; margin-top: 10px;' ) ); ?>
			<a href='?page=moss-submissions' class='button secondary' style='float: right; margin-top: 10px; margin-right: 10px;'><?php _e('Submissions', 'vat_moss'); ?></a>
			<h2><?php echo $title; if ($submission_id) { ?>
				<a href="?page=moss-submissions&action=new_submission" class="add-new-h2"><?php _e( 'Add New', 'vat_moss' ); ?></a>
			<?php } ?>
			</h2>

			<input type="hidden" name="post_type" value="submission"/>
			<input type="hidden" name="page" value="moss-submissions"/>
			<input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>"/>
			<input type="hidden" name="_wp_nonce" value="<?php echo wp_create_nonce( 'moss_submission' ); ?>" />

			<div id="poststuff" >
				<div id="moss_submission_header" class="postbox ">
					<h3 class="hndle ui-sortable-handle"><span><?php _e( 'Details', 'vat_moss' ); ?></span></h3>
					<div class="inside">
						<table width="100%" class="moss-submission-header-details">
							<colgroup>
								<col width="200px">
							</colgroup>
							<tbody>
								<tr>
									<td scope="row" style="200px"><b><?php _e( 'Submission Title', 'vat_moss' ); ?></b></td>
									<td style="200px">
<?php	if ($read_only) { ?>
										<span><?php echo $post_title; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="moss_settings_title" name="moss_settings_title" value="<?php echo $post_title; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td style="vertical-align: top;" scope="row"><span><b><?php _e( 'Test mode', 'vat_moss' ); ?></b></span></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $test_mode ? "Yes" : "No"; ?></span>&nbsp;-&nbsp;
										<input type="hidden" id="ecsl_settings_test_mode" value="<?php echo $test_mode; ?>">
<?php	} else { ?>
										<input type="checkbox" class="checkbox" id="test_mode" name="test_mode" <?php echo $test_mode ? "checked='on'" : ""; ?>">
<?php	} ?>
										<span><?php echo __( "Use the test mode to check the generation of an upload file.", 'vat_moss' ); ?></span>
										<p style="margin-top: 0px; margin-bottom: 0px;"><?php echo __( "In test mode a license key is not required and an upload file will be generated but the sales values in the generated file will be zero.", 'vat_moss' ); ?></p>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Submission license key', 'vat_moss' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $submission_key; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="submission_key" name="submission_key" value="<?php echo $submission_key; ?>">
<?php	} ?>
									</td>
								</tr>
<?php	if (!$read_only) { ?>
								<tr>
									<td></td>
									<td>
										<button id="check_moss_license" submission_key_id="submission_key" value="<?php _e( 'Check License', 'vat_moss' ); ?>" class="button button-primary" ><?php _e( 'Check License', 'vat_moss' ); ?></button>
										<img src="<?php echo VAT_MOSS_PLUGIN_URL . "images/loading.gif" ?>" id="license-checking" style="display:none; margin-left: 10px; margin-top: 8px;" />
									</td>
								</tr>
<?php	}
		if ($submission_id) { ?>
								<tr>
									<td scope="row"><b><?php _e( 'Creation date', 'vat_moss' ); ?></b></td>
									<td>
										<span><?php echo $submission->post_date; ?></span>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Last modified date', 'vat_moss' ); ?></b></td>
									<td>
										<span><?php echo $submission->post_modified; ?></span>
									</td>
								</tr>
<?php	} ?>
								<tr>
									<td scope="row"><b><?php _e( 'Your MS ID', 'vat_moss' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $vrn; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="moss_settings_vat_number" name="moss_settings_vat_number" value="<?php echo $vrn; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td></td>
									<td><?php _e( 'The MS ID is the id issued by your member state tax authority and may be the same as your VAT/TVA number.', 'vat_moss' ); ?></td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Submitters Name', 'vat_moss' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $submitter; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="moss_settings_submitter" name="moss_settings_submitter" value="<?php echo $submitter; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Submitters Email Address', 'vat_moss' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $email; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="moss_settings_email" name="moss_settings_email" value="<?php echo $email; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Submission Period', 'vat_moss' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo "Q$submission_period $submission_year"; ?></span>
<?php	} else { ?>
<?php
										echo vat_moss()->html->quarter_dropdown( 'submission_period', $submission_period );
										echo vat_moss()->html->year_dropdown( 'submission_year', $submission_year );
		}
?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Total transactions selected', 'vat_moss' ); ?></b></td>
									<td>
										<span><?php echo mossales_count($selected); ?></span>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Total net value of selected lines', 'vat_moss' ); ?></b></td>
									<td>
										<span><?php echo number_format( $totalnetvalue ? $totalnetvalue : 0, 2, $locale['decimal_point'], $locale['thousands_sep'] ); ?></span>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Total tax value of selected lines', 'vat_moss' ); ?></b></td>
									<td>
										<span><?php echo number_format( $totaltaxvalue ? $totaltaxvalue : 0, 2, $locale['decimal_point'], $locale['thousands_sep'] ); ?></span>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			
			<p><strong><?php _e('It may be that not all the lines shown below are selectable.', 'vat_moss'); ?></strong></p>
			<p><?php
				_e('This will occur if a transaction includes more than one item as only one of the items will be selectable for all the items.', 'vat_moss');
				echo '&nbsp;';
				_e('All items in a transaction must be considered in case they used different VAT rates and, so, must be reported seperately.', 'vat_moss')
			?></p>
<?php
			$sales_list->views();
			$sales_list->display();
			do_action( 'moss_submissions_page_bottom' );
			
			$selected = array();
?>

		</form>

	</div>
<?php
}
?>
