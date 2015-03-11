<?php

/**
 * MOSS WOO Commerce integration
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

class MOSS_Integration_WOOC extends MOSS_Integration_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->source = 'wooc';
		$this->name = __( 'WooCommerce', 'vat_moss' );
		$this->post_type = 'product';

		$instance = $this;
		add_action( 'moss_integration_instance', function( $instance_array ) use($instance)
		{
			if (function_exists('WC'))
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

		$meta_query = array();
		$meta_query[] = array(
			'key'		=> '_completed_date',
			'value'		=> array($startDate, $endDate),
			'compare'	=> 'BETWEEN',
			'type'		=> 'DATE'
		);
		$meta_query[] = array(
			'key'		=> '_order_tax',
			'value'		=> 0,
			'compare'	=> '>',
			'type'		=> 'DECIMAL'
		);

		$meta_query[] = array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'		=> 'VAT Number',
					'compare'	=> 'NOT EXISTS'
				),
				array(
					'key'		=> 'Valid EU VAT Number',
					'value'		=> 'false',
					'compare'	=> '='
				)
			),
			array(
				'relation' => 'OR',
				array(
					'key'		=> 'vat_number',
					'compare'	=> 'NOT EXISTS'
				),
				array(
					'key'		=> 'vat_number',
					'value'		=> '',
					'compare'	=> '='
				)
			)
		);
		
		if (!$includeSubmitted)
		{
			$meta_query[] = array(
				'key'     => 'moss_submission_id',
				'compare' => 'NOT EXISTS'
			);
		}

		else if ($includeSubmittedOnly)
		{
			$meta_query[] = array(
				'key'     => 'moss_submission_id',
				'compare' => 'EXISTS'
			);
		}

		$args = array(
			'post_type' 		=> 'shop_order',
			'posts_per_page' 	=> -1,
			'fields'			=> 'ids',
			'post_status'		=> array( 'wc-completed' ),
			'orderby'			=> array( 'meta_value_num' => 'ASC' ),
			'meta_query'		=> $meta_query
		);

		$payments = new \WP_Query( $args );
		$vat_payments = array();

		if( $payments->posts )
		{
			$eu_states = array_flip( WordPressPlugin::$eu_states );
			$use_all_products = use_all_products();

			foreach( $payments->posts as $payment_id ) {

				$eu_vat_compliance	= false;
				$purchase_key		= get_post_meta( $payment_id, '_order_key',					true );
				$vrn				= get_post_meta( $payment_id, 'VAT Number',					true );
				$date				= get_post_meta( $payment_id, '_completed_date',			true );
				$submission_id		= get_post_meta( $payment_id, 'moss_submission_id', 		true );
				$billing_first_name	= get_post_meta( $payment_id, '_billing_first_name',		true );
				$billing_last_name	= get_post_meta( $payment_id, '_billing_last_name', 		true );
				$order_total		= get_post_meta( $payment_id, '_order_total',				true );
				$vat_paid			= get_post_meta( $payment_id, 'vat_compliance_vat_paid',	true );
				if ($vat_paid)
				{
					$eu_vat_compliance = true;

					// This is evidence from the eu-vat-compliance plugin
					$vat_paid		= maybe_unserialize( $vat_paid );
					$country_info	= get_post_meta( $payment_id, 'vat_compliance_country_info', true );
				
					$country_info	= maybe_unserialize( $country_info );
					$country_code	= $country_info['taxable_address'][0];
					$currency_code	= $vat_paid['currency'];

					/**
						"by_rates" = array(
							[5] => array(
								"items_total" => 2.736,
								"shipping_total" => 0,
								"rate" => "19.0000",
								"name" => "VAT (19%)"
							),
							[31] => array(
								"items_total" => 0.9,
								"shipping_total" => 0,
								"rate" => "10.0000",
								"name" => "Dummy VAT"
							)
						)
					 */
					$rates			= $vat_paid['by_rates'];
				}
				else
				{
					// Is the EUVA plugin being used?
					$euva_evidence	= get_post_meta( $payment_id, '_eu_vat_evidence',	true );
					if (!$euva_evidence)
						continue;

					$euva_data		= get_post_meta( $payment_id, '_eu_vat_data',		true );
					$country_code	= $euva_evidence['location']['billing_country'];
					$currency_code	= $euva_data['vat_currency'];

					/**
						"taxes" = array(
							[72] => array(
								"label" => "20% GB VAT",
								"vat_rate" => "20.0000",
								"country" => "GB",
								"tax_rate_class" => "reduced-rate",
								"amounts" = array(
									"items_total" => 6,
									"shipping_total" => 0
								)
							)
						)
					 */

					$rates			= $euva_data['taxes'];
				}

				if (isset($eu_states[$establishment_country])) // A union
				{
					// Should exclude sale to buyers in the establishment country
					if ($country_code === $establishment_country) continue;
					if (!isset($eu_states[$country_code])) continue;
				}

				$order = wc_get_order( $payment_id );
// error_log(print_r($order,true));
				$line_items = $order->get_items( 'line_item' );
				$index			= 0;

				foreach ( $line_items as $item_id => $item ) {

					 /*
						Each of these is an array
						_qty
						_tax_class
						_product_id
						_variation_id
						_line_subtotal
						_line_total
						_line_subtotal_tax
						_line_tax
						_line_tax_data
					 */
					$item_meta = $order->get_item_meta( $item_id );

					if ( array_sum($item_meta['_line_tax']) == 0 ) continue;
					$product    = wc_get_product( $item_meta['_product_id'][0] );
					if (!$use_all_products && !$product->is_virtual()) continue;

					$tax_data = maybe_unserialize( $item_meta['_line_tax_data'][0] );

					/*
						Array
						(
							[total] => Array
								(
									[5] => 2.736
								)

							[subtotal] => Array
								(
									[5] => 3.8
								)

						)
					 */

					$rate_index = key($tax_data['total']);

					// What's the rate for this sale?
					$rate = round( isset($rates[$rate_index]) ? $rates[$rate_index][$eu_vat_compliance ? 'rate' : 'vat_rate'] : 20, 3 );
					$rate /= 100;

					/*
						'id'			=> $payment->id,
						'item_id'		=> 0,
						'first'			=> true,
						'purchase_key'	=> $payment->purchase_key,
						'date'			=> $payment->date,
						'submission_id'	=> isset($payment->submission_id) ? $payment->submission_id : 0,
						'net'			=> apply_filters( 'moss_get_net_transaction_amount', $payment->value - $payment->tax, $payment->id ),
						'tax'			=> $payment->tax,
						'vat_rate'		=> $payment->vat_rate,
						'vat_type'		=> $payment->vat_type,
						'country_code'	=> $payment->country
					 */

					$vat_payment = array();

					$vat_payment['id']				= $payment_id;
					$vat_payment['item_id']			= $item_id;
					$vat_payment['first']			= $index == 0;
					$vat_payment['purchase_key']	= $purchase_key;
					$vat_payment['date']			= $date;
					$vat_payment['submission_id']	= $submission_id;
					$vat_payment['net']				= round( apply_filters( 'moss_get_net_transaction_amount', array_sum($item_meta['_line_total']), $order_total, $payment_id), 2 );
					$vat_payment['tax']				= round( array_sum($item_meta['_line_tax']), 2 );
					$vat_payment['vat_rate']		= $rate;
					$vat_payment['vat_type']		= isset( $item_meta['_tax_class'] ) && count( $item_meta['_tax_class'] ) > 0 && $item_meta['_tax_class'][0] ? $item_meta['_tax_class'][0] : 'reduced';
					$vat_payment['country_code']	= $country_code;
					$vat_payment['currency_code']	= $currency_code;

					$vat_payments[] = $vat_payment;
					$index++;

				}

				// Is there a discount rate to apply?
			}
		}
		
// error_log(print_r($vat_payments,true));
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
			foreach($ids as $id => $value)
			{
				update_post_meta($id, 'moss_submission_id', $submission_id);

				if (!empty($correlation_id))
					update_post_meta($id, 'correlation_id', $submission_id);
			}
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
	 *
	 * array(
	 *	'status' => 'success',
	 *	'information' => array(
	 *		'id'			=> 0,
	 *		'vrn'			=> 'GB123456789',
	 *		'purchase_key'	=> '...',
	 *		'values'		=> array(
	 *							  'indicator' (0|2|3) => sale amounts accumulated
	 *						   )
	 *	)
	 * )
	 *
	 * array(
	 *	'status' => 'error',
	 *	'messages' => array(
	 *		'',
	 *		''
	 *	)
	 * )
	 */
	function get_vat_record_information($source_ids)
	{
		if (!is_array($source_ids))
		{
			return array('status' => 'error', 'messages' => array( __( 'Invalid source', 'vat_moss' ) ) );
		}

		$vat_payments = array();

		foreach( $source_ids as $key => $payment_id ) {
		
			$eu_vat_compliance	= false;

			$purchase_key		= get_post_meta( $payment_id, '_order_key',					true );
			$vrn				= get_post_meta( $payment_id, 'VAT Number',					true );
			$date				= get_post_meta( $payment_id, '_completed_date',			true );
			$submission_id		= get_post_meta( $payment_id, 'moss_submission_id', 		true );
			$order_total		= get_post_meta( $payment_id, '_order_total',				true );
			$vat_paid			= get_post_meta( $payment_id, 'vat_compliance_vat_paid',	true );

			if ($vat_paid)
			{
				$eu_vat_compliance = true;

				// This is evidence from the eu-vat-compliance plugin
				$vat_paid		= maybe_unserialize( $vat_paid );
				$country_info	= get_post_meta( $payment_id, 'vat_compliance_country_info', true );
				$country_info	= maybe_unserialize( $country_info );
				$country_code	= $country_info['taxable_address'][0];
				$currency_code	= $vat_paid['currency'];

				/**
					"by_rates" = array(
						[5] => array(
							"items_total" => 2.736,
							"shipping_total" => 0,
							"rate" => "19.0000",
							"name" => "VAT (19%)"
						),
						[31] => array(
							"items_total" => 0.9,
							"shipping_total" => 0,
							"rate" => "10.0000",
							"name" => "Dummy VAT"
						)
					)
				 */

				$rates				= $vat_paid['by_rates'];
			}
			else
			{
				// Is the EUVA plugin being used?
				$euva_evidence	= get_post_meta( $payment_id, '_eu_vat_evidence',	true );
				if (!$euva_evidence) continue;
				
				$euva_data		= get_post_meta( $payment_id, '_eu_vat_data',		true );
				$country_code	= $euva_evidence['location']['billing_country'];
				$currency_code	= $euva_data['vat_currency'];

				/**
					"taxes" = array(
						[72] => array(
							"label" => "20% GB VAT",
							"vat_rate" => "20.0000",
							"country" => "GB",
							"tax_rate_class" => "reduced-rate",
							"amounts" = array(
								"items_total" => 6,
								"shipping_total" => 0
							)
						)
					)
				 */

				$rates			= $euva_data['taxes'];
			}

			$order = wc_get_order( $payment_id );
			$line_items = $order->get_items( 'line_item' );
			$index			= 0;

			$vat_payment = array();

			foreach ( $line_items as $item_id => $item ) {

				 /*
					Each of these is an array
					_qty
					_tax_class
					_product_id
					_variation_id
					_line_subtotal
					_line_total
					_line_subtotal_tax
					_line_tax
					_line_tax_data
				 */

				$item_meta = $order->get_item_meta( $item_id );
				if ( array_sum($item_meta['_line_tax']) == 0 ) continue;

				$tax_data = maybe_unserialize( $item_meta['_line_tax_data'][0] );

				/*
					Array
					(
						[total] => Array
							(
								[5] => 2.736
							)

						[subtotal] => Array
							(
								[5] => 3.8
							)

					)
				 */

				$rate_index = key($tax_data['total']);

				// What's the rate for this sale?
				$rate = round( isset($rates[$rate_index]) ? $rates[$rate_index][$eu_vat_compliance ? 'rate' : 'vat_rate'] : 20, 3 );
				$rate /= 100;

				/*
					Each of these is an array
					_qty
					_tax_class
					_product_id
					_variation_id
					_line_subtotal
					_line_total
					_line_subtotal_tax
					_line_tax
					_line_tax_data
				 */

				$vat_payment = array();

				$vat_payment['id']				= $payment_id;
				$vat_payment['item_id']			= $item_id;
				$vat_payment['first']			= $index == 0;
				$vat_payment['purchase_key']	= $purchase_key;
				$vat_payment['date']			= $date;
				$vat_payment['submission_id']	= $submission_id;
				$vat_payment['net']				= round( apply_filters( 'moss_get_net_transaction_amount', array_sum($item_meta['_line_total']), $order_total, $payment_id), 2 );
				$vat_payment['tax']				= round( array_sum($item_meta['_line_tax']), 2 );
				$vat_payment['vat_rate']		= $rate;
				$vat_payment['vat_type']		= isset( $item_meta['_tax_class'] ) && count( $item_meta['_tax_class'] ) > 0 && $item_meta['_tax_class'][0] ? $item_meta['_tax_class'][0] : 'reduced';
				$vat_payment['country_code']	= $country_code;
				$vat_payment['currency_code']	= $currency_code;

				$vat_payments[] = $vat_payment;
				$index++;
			}
		}
		
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
			foreach($ids as $id => $value)
			{
				delete_post_meta($id, 'moss_submission_id');
				delete_post_meta($id, 'correlation_id');
			}
		}
		catch(Exception $ex)
		{
			return array(__('An error occurred deleting MOSS sales record meta data', 'vat_moss'), $ex->getMessage());
		}
		
	 }

}
new MOSS_Integration_WOOC;
