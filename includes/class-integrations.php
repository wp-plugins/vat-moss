<?php

/**
 * MOSS Integrations controller
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

class MOSS_WP_Integrations {

	public $issues = array();
	private	$last_updated_option_name = "moss_euros_last_updated";
	private	$last_data_option_name = "moss_euros_last_data";

	private	$euro_zone_states = array("AT","BE","CY","EE","FI","FR","DE","GR","IE","IT","LV","LT","LU","MT","NL","PT","SK","SI","ES");
	private $not_euro_zone_states = array(
		"BG" => "BGN",
		"HR" => "HRK",
		"CZ" => "CZK",
		"DK" => "DKK",
		"GB" => "GBP",
		"HU" => "HUF",
		"PL" => "PLN",
		"RO" => "RON",
		"SE" => "SEK"
	);
	public function __construct() {

		$this->load();

	}

	public static function get_integrations() {

		return apply_filters('moss_integration_instance', array());
	}

	public function get_integrations_list() {
		
		return array_reduce(
			MOSS_WP_Integrations::get_integrations(),
			function($carry, $instance)
			{
				$carry[$instance->source] = $instance->name;
				return $carry;
			}
		);
	}

	public static function get_enabled_integrations() {
		return vat_moss()->settings->get( 'integrations', array() );
	}

	public function get_post_types()
	{
		$result = array();

		foreach(MOSS_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_moss\MOSS_Integration_Base') || !isset($this->enabled_integrations[$integration->source])) continue;
			if (!isset($integration->post_type) || empty($integration->post_type)) continue;

			$post_type = $integration->post_type;
			$result[$integration->post_type] = $integration->post_type;
		}
		
		return $result;
	}

	public function load() {

		$integrations_dir = VAT_MOSS_INCLUDES_DIR . 'integrations/';

		// Load each enabled integrations
		require_once $integrations_dir . 'class-base.php';

		if ($handle = opendir($integrations_dir))
		{
			try
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file === "." || $file === ".." || $file === "class-base.php" || strpos($file, 'class') !== 0) continue;
					
					$filename  = $integrations_dir . $file;

					if(!is_file($filename)) continue;

					require_once $filename;
				}
			}
			catch(Exception $ex)
			{}

			closedir($handle);
		} 

		$this->enabled_integrations = apply_filters( 'moss_enabled_integrations', MOSS_WP_Integrations::get_enabled_integrations() );
	}

	/**
	 *	source			The 'context' identifier for the source
	 *	id				Database id for the sale
	 *	purchase_key	Unique purchase identifier (the id if separate unique id does not exist)
	 *	date			Datetime of the completed transaction
	 *	correlation_id	Existing correlation_id (if any)
	 *	indicator		0: Goods, 2: Triangulated sale, 3: Services (reverse charge)
	 *	buyer			The name of the buyer
	 *	value			Amount of sale before any taxes
	 *
	 * If you have more than one sale to a client the value of those sales can be aggregated
	 * If the sale is across two service types (different indicators) then they need to appear as different entries
	 *
	 * @string	startDate				strtotime() compatible date of the earliest record to return
	 * @string	endDate					strtotime() compatible date of the latest record to return.
	 * @boolean	includeSubmitted		True is the results should include previously submitted records (submission_id does not exist in meta-data)
	 * @boolean	includeSubmittedOnly	True if the results should only include selected items
	 */
	public function get_vat_information($startDate, $endDate, $includeSubmitted = false, $includeSubmittedOnly = false)
	{
		require_once(VAT_MOSS_INCLUDES_DIR . 'vatidvalidator.php');

		$vat_info = array();
		$shop_currency = get_default_currency();

		foreach(MOSS_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_moss\MOSS_Integration_Base') || !isset($this->enabled_integrations[$integration->source])) continue;

			if (!strtotime($startDate) || !strtotime($endDate))
				return;

			// Make sure the dates 
			$startDate = date('Y-m-d 00:00:00', strtotime($startDate));
			$endDate   = date('Y-m-d 23:59:59', strtotime($endDate));

			$vat_records = $integration->get_vat_information($startDate, $endDate, $includeSubmitted, $includeSubmittedOnly);
			$vat_type_names = $integration->get_vat_type_names();

			foreach($vat_records as $key => $vat_record)
			{
				$vat_record['vat_type'] = $this->lookup_vat_rate_name( $vat_type_names, $vat_record );
				$vat_record['source'] = $integration->source;

				if ($vat_record['currency_code'] !== $shop_currency)
				{
//					error_log("Transaction currency ({$vat_record['currency_code']}) not the same as the shop currency ($shop_currency)");
					// Need to translate the tax and net amounts
//					error_log("Original: Tax: {$vat_record['tax']} Net: {$vat_record['net']}");
					$vat_record['tax'] = $this->translate_amount( $vat_record['tax'], $vat_record['date'], $vat_record['currency_code'], $shop_currency, $vat_record['id'] );
					$vat_record['net'] = $this->translate_amount( $vat_record['net'], $vat_record['date'], $vat_record['currency_code'], $shop_currency, $vat_record['id'] );
//					error_log("Translated: Tax: {$vat_record['tax']} Net: {$vat_record['net']}");
				}

				$vat_records[$key] = $vat_record;
			}

			$vat_info = array_merge($vat_info, $vat_records);
		}

		if (isset( $this->issues ) && is_array( $this->issues ) && count( $this->issues ) > 0 )
		{
			foreach( $this->issues as $issue)
			{
				echo "<div class='error'><p>$issue</p></div>";		
			}

			$this->issues = array();
		}

		return $vat_info;
	}

	public function lookup_vat_rate_name($vat_type_names, $vat_record)
	{
		return isset($vat_record['vat_type'])
			? (isset( $vat_type_names[$vat_record['vat_type']] )
				? $vat_type_names[$vat_record['vat_type']]
				: $vat_type_names['reduced']
			  )
			: $vat_type_names['reduced'];
		
	}

	/**
	 * Called to allow the integration to update sales records with a submission_id and correlation_id
	 *
	 * @int submission_id The id of the MOSS submission that references the sale record
	 * @string correlation_id The HMRC generated correlation_id of the submission in which the sales record is included
	 * @array source_ids An array of sources and record ids
	 *
	 * @return An error message, an array of messages or FALSE if every thing is OK
	 */
	function update_vat_information($submission_id, $correlation_id, $source_ids)
	{
		$errors = FALSE;

		foreach(MOSS_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_moss\MOSS_Integration_Base') ||
				!isset($this->enabled_integrations[$integration->source])) continue;

			if (!isset($source_ids[$integration->source])) continue;

			$result = $integration->update_vat_information($submission_id, $correlation_id, $source_ids[$integration->source]);
			if (!$result) continue;

			if (!is_array($errors)) $errors = array();
			if (!is_array($result)) $result = array($result);

			array_unshift($result, sprintf( __("Errors occurred update VAT information for records in source '%s'", 'vat_moss'), $integration->source));
			$errors[$integration->source] = $result;			
		}
		
		return $errors;
	}

	/**
	 * Called to allow the integration to retrieve information from specific records
	 *
	 * @array source_ids An array of sources and record ids
	 *
	 * @return An error message, an array of messages or FALSE if everything is OK
	 *
	 * array(
	 *	'status' => 'success',
	 *	'information' => array(
	 *		'sourcexxx' = array(
	 *			'0' => array(
	 *				'id'			=> 1,
	 *				'item_id'		=> 0,
	 *				'first'			=> true,
	 *				'purchase_key'	=> 'xxx',
	 *				'date'			=> '2014-12-12',
	 *				'submission_id'	=> 0,
	 *				'net'			=> 30,
	 *				'tax'			=> 6.0,
	 *				'vat_rate'		=> 0.2,
	 *				'vat_type'		=> 'reduced',
	 *				'country_code'	=> 'GB'
	 *			)
	 *		),
	 *		'sourceyyy' = array(
	 *			'0' => array(
	 *				'id'			=> 1,
	 *				'item_id'		=> 0,
	 *				'first'			=> true,
	 *				'purchase_key'	=> 'xxx',
	 *				'date'			=> '2014-12-12',
	 *				'submission_id'	=> 0,
	 *				'net'			=> 30,
	 *				'tax'			=> 6.0,
	 *				'vat_rate'		=> 0.2,
	 *				'vat_type'		=> 'reduced',
	 *				'country_code'	=> 'GB'
	 *			)
	 *		)
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
	 *
	 *
	 */
	function get_vat_record_information( $source_ids )
	{
		$information = array();
		$shop_currency = get_default_currency();

		foreach(MOSS_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_moss\MOSS_Integration_Base') ||
				!isset($this->enabled_integrations[$integration->source])) continue;

			if (!isset($source_ids[$integration->source])) continue;

			$result = $integration->get_vat_record_information($source_ids[$integration->source]);
			if (!$result || !is_array($result) )
			{
				return array( 'status' => 'error', '' => array(sprintf( __( "Failed to access record information for source '%s'", 'vat_moss'), $integration->source ) ) );
			}

			if ($result['status'] === 'error')
				return $result;

			$vat_type_names = $integration->get_vat_type_names();
			$me = $this;

			$information = array_merge( $information, array_map( function($item) use($me, $integration, $vat_type_names, $shop_currency)
			{
				$item['vat_type'] = $me->lookup_vat_rate_name( $vat_type_names, $item );
				$item['source']  = $integration->source;

				if ($item['currency_code'] !== $shop_currency)
				{
//					error_log("Transaction currency ({$item['currency_code']}) not the same as the shop currency ($shop_currency)");
					// Need to translate the tax and net amounts
//					error_log("Original: Tax: {$item['tax']} Net: {$item['net']}");
					$item['tax'] = $me->translate_amount( $item['tax'], $item['date'], $item['currency_code'], $shop_currency, $item['id'] );
					$item['net'] = $me->translate_amount( $item['net'], $item['date'], $item['currency_code'], $shop_currency, $item['id'] );
//					error_log("Translated: Tax: {$item['tax']} Net: {$item['net']}");
				}

				return $item;
			}, $result['information'] ));
		}

		return array( 'status' => 'success', 'information' => $information );
	}
	
	function translate_amount($amount, $date, $from_currency, $to_currency, $payment_id = 0 )
	{
		if (strcasecmp($from_currency, $to_currency) == 0)
			return $amount;

		$not_euro_zone = in_array( strtoupper( $to_currency ), $this->not_euro_zone_states );

		return $not_euro_zone
			? $this->translate_to_non_euro_currency( $amount, strtoupper( $from_currency ), strtoupper( $to_currency ), $date, $payment_id  )
			: $this->translate_to_euros( $amount, $from_currency, $date, $payment_id  );
			
		return $amount;
	}

	function translate_to_euros( $amount, $from_currency, $time, $payment_id  )
	{
		// Maybe the shop already knows the correct rate to use
		$shop_exchange_rate = apply_filters( 'moss_get_shop_exchange_rate', false, $payment_id );
		
		// If the result is not a positive number then use the ECB rates
		if ($shop_exchange_rate === false || !is_numeric($shop_exchange_rate) || $shop_exchange_rate <= 0 )
		{
			$rates = $this->get_euro_rates_for_date( $time );
			if ($rates === false) return $amount;
			
			// OK, got some rates so convert
			if (!isset( $rates[$from_currency] ))
				return $amount;

			return round( $amount / $rates[$from_currency], 2);
		}
		else
		{
			return round( $amount * $shop_exchange_rate, 2);
		}
	}

	function translate_to_non_euro_currency( $amount, $from_currency, $to_currency, $time, $payment_id  )
	{
		// Maybe the shop already knows the correct rate to use
		$shop_exchange_rate = apply_filters( 'moss_get_shop_exchange_rate', false, $payment_id );
		
		// If the result is not a positive number then use the ECB rates
		if ($shop_exchange_rate === false || !is_numeric($shop_exchange_rate) || $shop_exchange_rate <= 0 )
		{
			$rates = $this->get_euro_rates_for_date( $time );

			if ($rates === false) return $amount;

			// OK, got some rates so convert.  First to EUR then to base
			$euro_amount = $amount;
			if (strcasecmp( $from_currency, 'EUR' ) !== 0)
			{
				$from_currency = strtoupper( $from_currency );
				if (!isset( $rates[$from_currency] ) )
					return $amount;

				$euro_amount = $amount / $rates[$from_currency];
			}

			// Now from EUR to the base currency
			return round( $euro_amount * $rates[$to_currency], 2 );
		}
		else
		{
			return round( $amount * $shop_exchange_rate, 2 );
		}
	}

	/**
	 * Gets the rates array for the day represented in the $timestamp or the next earlier day
	 * @array rates An array of daily rate arrays
	 * @int timestamp A timestamp representing a date/time
	 * @return An array of rates for a date or false
	 */
	function get_rates_for_date( $rates, $timestamp )
	{
		if (!isset( $rates ) || !$rates )
			return false;

		$date = date( "Y-m-d", $timestamp );

		if ( isset( $this->euro_rates[$date] ) )
		{
			// This is the easy case
			return $this->euro_rates[$date];
		}

		// More difficult because the nearest older date is required
		foreach( $this->euro_rates as $key => $rates)
		{
			$date_stamp = strtotime( $key );

			if ($timestamp >= $date_stamp)
				return $rates;
		}
		
		return false;
	}

	/**
	 * Gets the euro rates for a time by calling init_euro_rates() then get_rates_for_date() if there are any rates available
	 * @string $time A time string in the form y-m-d H:i:s
	 * @return a rates array for the time or false
	 */
	function get_euro_rates_for_date( $time )
	{
		// Get the rates
		$rates_ok = $this->init_euro_rates();
		// If there are none and no rates could be retrieved then return false.
		if ($rates_ok === false) return false;

		// Get the best rate for the date
		return $this->get_rates_for_date( $this->euro_rates, strtotime( $time ) );
	}

	/**
	 * Report errors retrieving Euro rates 
	 */
	function handle_euro_error()
	{
		$this->issues[] = empty($this->euro_rates)
			? __( "An error occurred reading Euro exchange rates and no exchange rates have been read.", "vat_moss" )
			: __( "An error occurred reading Euro exchange rates. The existing rates will be used but these may be inaccurate.", "vat_moss" );

		return false;
	}

	/**
	 * Get and/or update the Euro rates
	 * @return True if there is no problem
	 */
	function init_euro_rates()
	{
		// Don't need to read all the rates every time a transaction is to be translated
		if (isset($this->euro_rates) && !empty($this->euro_rates))
			return true;

		$last_updated = get_site_option($this->last_updated_option_name);
		if (empty($last_updated)) $last_updated = 0;
		$this->euro_rates = get_site_option($this->last_data_option_name);

		if (!empty($this->euro_rates))
		{
			$this->euro_rates = $this->euro_rates = maybe_unserialize($this->euro_rates);
		}

		if (!empty($this->euro_rates) && ($last_updated + 60*60*12) > time()) {
			return true;
		}

		if (empty($this->euro_rates) && file_exists( dirname(__FILE__) . "/../assets/ecb-rates-q4-2014.xml" ) )
		{
			// There are no rates at all so begin by loading Q4-2014
			$new_data = simplexml_load_file(dirname(__FILE__) . "/../assets/ecb-rates-q4-2014.xml");
			$this->load_rates( $new_data );
		}

		$fetched = wp_remote_get("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-hist-90d.xml");
		if (is_wp_error($fetched) || 
			empty($fetched['response']) || 
			$fetched['response']['code'] >= 300 || 
			empty( $fetched['body'] ) ||
			strpos( $fetched['body'], '<!DOCTYPE HTML' ) !== false
		)
		{
			return $this->handle_euro_error();
		}

		$new_data = simplexml_load_string( $fetched['body'] );
		return $this->load_rates( $new_data );
	}
	
	function load_rates($new_data)
	{
		if ($new_data === false)
		{
			return $this->handle_euro_error();
		}

		$new_rates = array();
		foreach($new_data->Cube->Cube as $rates)
		{
			$day_rates = array();
			foreach($rates->Cube as $key => $rate)
			{
				$day_rates[(string) $rate['currency']] = floatval($rate['rate']);
			}
			$new_rates[(string)$rates['time']] = $day_rates;
		}

		if (count($new_rates) === 0) return true;

		$this->euro_rates = empty($this->euro_rates) ? $new_rates : array_merge($new_rates, $this->euro_rates );

		update_site_option($this->last_data_option_name, serialize( $this->euro_rates ));
		update_site_option($this->last_updated_option_name, time());

		return true;
	}

	/**
	 * Called by the integration controller to remove MOSS submission references for a set of post ids
	 *
	 * @array source_ids An array of sales record ids
	 *
	 * @return An error message, an array of messages or FALSE if every thing is OK
	 */
	function delete_vat_information($source_ids)
	{
		$errors = FALSE;

		foreach(MOSS_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_moss\MOSS_Integration_Base') ||
				!isset($this->enabled_integrations[$integration->source])) continue;

			if (!isset($source_ids[$integration->source])) continue;

			$result = $integration->delete_vat_information($source_ids[$integration->source]);
			if (!$result) continue;

			if (!is_array($errors)) $errors = array();
			if (!is_array($result)) $result = array($result);

			array_unshift($result, sprintf( __( "Errors occurred removing VAT information from records in source '%1s'", 'vat_moss'), $integration->source));
			$errors[$integration->source] = $result;
		}

		return $errors;
	}
	
	/**
	 * Flattens an array generated by get_vat_information() or get_vat_record_information()
	 *
	 */
	function flatten_vat_information($hierarchical_vat_payments)
	{
		$vat_payments = array();

		if (is_array($hierarchical_vat_payments))
		{
			foreach($hierarchical_vat_payments as $key => $payment)
			{
				$new_payment = array();

				// Only the first instance of an expanded payment should be included
				// This flag is used to indicate this condition
				$first = true;

				foreach($payment['values'] as $indicator => $amount)
				{
					foreach($payment as $key => $value)
					{
						if ($key === 'values') continue;
						$new_payment[$key] = $value;
					}

					$new_payment['indicator'] = $indicator;
					$new_payment['value'] = $amount;
					$new_payment['first'] = $first;

					$vat_payments[] = $new_payment;

					$first = false;
				}
			}
		}

		return $vat_payments;
	}

}