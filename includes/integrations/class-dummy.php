<?php

/**
 * MOSS Dummy source integration
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

class MOSS_Integration_Dummy extends MOSS_Integration_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->source = 'dummy';
		$this->name = 'Dummy (Lyquidity Solutions)';
		$instance = $this;
		add_action( 'moss_integration_instance', function( $instance_array ) use($instance)
		{
			$instance_array[$instance->source] = $instance;
			return $instance_array;
		}, 10 );

	}

	/**
	 * Returns an array of VAT information:
	 *	id				Database id for the sale
	 *	purchase_key	Unique purchase identifier
	 *  vrn				The VAT number of the buyer
	 *	date			DateTime of the completed transaction
	 *	correlation_id	Existing correlation_id (if any)
	 *	buyer			The name of the buyer
	 *	values			An array of sale values before any taxes indexed by the indicator.  
	 *						0: Goods, 2: Triangulated sale, 3: Services (reverse charge)
	 *						Values with the same indicator will be accumulated
	 *
	 * If you have more than one sale to a client the value of those sales can be aggregated
	 * If the sale is across two service types (different indicators) then they need to appear as different entries
	 *
	 * @string	startDate				strtotime() compatible date of the earliest record to return
	 * @string	endDate					strtotime() compatible date of the latest record to return
	 * @boolean	includeSubmitted		True is the results should include previously submitted records (submission_id does not exist in meta-data)
	 * @boolean	includeSubmittedOnly	True if the results should only include selected items
	 */
	public function get_vat_information($startDate, $endDate, $includeSubmitted = false, $includeSubmittedOnly = false)
	{
		$establishment_country = \lyquidity\vat_moss\get_establishment_country(); 

		$vat_payments = json_decode(file_get_contents( VAT_MOSS_INCLUDES_DIR . 'integrations/test-data.json' ) );

		$vat_payments = array_filter($vat_payments, function($payment) use ($startDate, $endDate, $includeSubmitted, $includeSubmittedOnly, $establishment_country )
		{
			return	( strtotime( $payment->date ) >= strtotime( $startDate ) ) &&
					( strtotime( $payment->date ) <= strtotime( $endDate ) ) &&
					( $includeSubmitted && $payment->submission_id !== 0 || ( !$includeSubmittedOnly && $payment->submission_id == 0 ) ) &&
					( !property_exists( $payment, 'vrn' ) || empty( $payment->vrn ) ) &&
					( property_exists( $payment, 'country' ) ) &&
					( !isset($eu_states[$establishment_country]) /* non-Union reporting */ || $payment->country !== $establishment_country );
		});

		/*
			'id'				=> 12,
			'item_id'			=> 0,
			'first'				=> true,
			'purchase_key'		=> '12',
			'date'				=> '2015-01-02',
			'submission_id'		=> 0,
			'net'				=> 22,
			'tax'				=> 0,
			'vat_rate'			=> 0.2,
			'vat_type'			=> 'reduced',
			'country_code'		=> 'AT'
		 */

		return array_map( function($payment)
		{
			return array(
				'id'			=> $payment->id,
				'item_id'		=> 0,
				'first'			=> true,
				'purchase_key'	=> $payment->purchase_key,
				'date'			=> $payment->date,
				'submission_id'	=> isset($payment->submission_id) ? $payment->submission_id : 0,
				'net'			=> round( apply_filters( 'moss_get_net_transaction_amount', $payment->value - $payment->tax, $payment->id ), 2 ),
				'tax'			=> round( $payment->tax, 2 ),
				'vat_rate'		=> $payment->vat_rate,
				'vat_type'		=> $payment->vat_type,
				'country_code'	=> $payment->country,
				'currency_code'	=> isset($payment->currency_code) ? $payment->currency_code : 'GBP'
			);
		}, $vat_payments );

		return $vat_payments;
	}

	/**
	 * Called by the integration controller to allow the integration to update sales records with
	 *
	 * @int submission_id The id of the MOSS submission that references the sale record
	 * @string correlation_id The HMRC generated correlation_id of the submission in which the sales record is included
	 * @array ids An array of sales record ids
	 *
	 * @return An error message, an array of messages or FALSE if every thing is OK
	 */
	function update_vat_information($submission_id, $correlation_id, $ids)
	{
		if (!$submission_id || !is_numeric($submission_id))
		{
			return __('The submission id is not valid', 'vat_moss');
		}

		if (!$ids || !is_array($ids))
		{
			return __('The VAT sales records passed are not an array', 'vat_moss');
		}
		
		try
		{
			$vat_payments = json_decode(file_get_contents( VAT_MOSS_INCLUDES_DIR . 'integrations/test-data.json' ) );

			foreach($vat_payments as $key => $payment)
			{
				if (!isset($ids[$payment->id])) continue;

				$payment->submission_id = $submission_id;

				if (!empty($correlation_id))
					$payment->correlation_id = $correlation_id;
			}

			file_put_contents( VAT_MOSS_INCLUDES_DIR . 'integrations/test-data.json', json_encode($vat_payments) );
		}
		catch(Exception $ex)
		{
			return array(__('An error occurred updating MOSS sales record meta data', 'vat_moss'), $ex->getMessage());
		}

		return false;
	}
	
	/**
	 * Called to allow the integration to retrieve information from specific records
	 *
	 * @array source_ids An array of sources and record ids
	 *
	 * @return An error message, an array of messages or of payments if everything is OK
	 */
	function get_vat_record_information($source_ids)
	{
		if (!is_array($source_ids))
		{
			return array('status' => 'error', 'messages' => array( __( 'Invalid source', 'vat_moss' ) ) );
		}

		$vat_payments = json_decode(file_get_contents( VAT_MOSS_INCLUDES_DIR . 'integrations/test-data.json' ) );
		$vat_payments = array_filter( $vat_payments, function($payment) use($source_ids)
		{
			return isset($source_ids[$payment->id]);
		});

		$vat_payments = array_map( function($payment)
		{
			return array(

				'id'			=> $payment->id,
				'item_id'		=> 0,
				'first'			=> true,
				'purchase_key'	=> $payment->purchase_key,
				'date'			=> $payment->date,
				'submission_id'	=> isset($payment->submission_id) ? $payment->submission_id : 0,
				'net'			=> round( apply_filters( 'moss_get_net_transaction_amount', $payment->value - $payment->tax, $payment->id ), 2 ),
				'tax'			=> round( $payment->tax, 2 ),
				'vat_rate'		=> $payment->vat_rate,
				'vat_type'		=> $payment->vat_type,
				'country_code'	=> $payment->country,
				'currency_code'	=> isset($payment->currency_code) ? $payment->currency_code : 'GBP'

			);
		}, $vat_payments);
		
		return array( 'status' => 'success', 'information' => $vat_payments );
	}

	/**
	 * Called by the integration controller to remove MOSS submission references for a set of post ids
	 *
	 * @array ids An array of sales record ids
	 *
	 * @return An error message, an array of messages or FALSE if every thing is OK
	 */
	 function delete_vat_information($ids)
	 {
		if (!$ids || !is_array($ids))
		{
			return __("The VAT sales records passed are not an array", 'vat_moss');
		}

		try
		{
			$vat_payments = json_decode(file_get_contents( VAT_MOSS_INCLUDES_DIR . 'integrations/test-data.json' ) );

			$vat_payments = array_map(function($payment) use($ids)
			{
				if (isset($ids[$payment->id]))
				{
					$payment->submission_id = 0;					
				}
				unset($payment->correlation_id);

				return $payment;
			}, $vat_payments);

			file_put_contents( VAT_MOSS_INCLUDES_DIR . 'integrations/test-data.json', json_encode($vat_payments) );
		}
		catch(Exception $ex)
		{
			return array(__('An error occurred deleting MOSS sales record meta data', 'vat_moss'), $ex->getMessage());
		}
		
	 }
}
new MOSS_Integration_Dummy;
