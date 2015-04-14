<?php 

ini_set('display_errors', true);

//setup global $_SERVER variables to keep WP from trying to redirect
$_SERVER = array(
  "HTTP_HOST" => "www.wproute.com",
  "SERVER_NAME" => "www.wproute.com",
  "REQUEST_METHOD" => "GET",
  "REMOTE_ADDR" => '127.0.0.1',
  "HTTP_ACCEPT_LANGUAGE" => "en",
  "REQUEST_URI" => "/wp-admin/admin.php?page=moss-submissions&action=new_submission"
);
//require the WP bootstrap
require_once(dirname(__FILE__).'/../../../wp-load.php');

$to_currency = "EUR";

// \lyquidity\vat_moss\vat_moss()->integrations->init_euro_rates();
echo \lyquidity\vat_moss\vat_moss()->integrations->translate_amount( 10, "2015-02-17", "EUR", "GBP", 2056) . "\n";
echo \lyquidity\vat_moss\vat_moss()->integrations->translate_amount(150, "2015-02-17", "USD", $to_currency) . "\n";
echo \lyquidity\vat_moss\vat_moss()->integrations->translate_amount(150, "2014-12-12", "USD", $to_currency) . "\n";
echo \lyquidity\vat_moss\vat_moss()->integrations->translate_amount( 30, "2014-12-12", "EUR", $to_currency) . "\n";

return;

$vat_payments = array(

	array(
		'id'				=> 1,
		'vrn'				=> 'BE897223274',
		'country'			=> 'BE',
		'purchase_key'		=> '1',
		'date'				=> '2015-01-10',
		'correlation_id'	=> '',
		'buyer'				=> 'Belgium Limited',
		'indicator'			=> '3',
		'value'				=> 11,
		'tax'				=> 0,
		'vat_rate'			=> 0,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 2,
		'country'			=> 'BE',
		'purchase_key'		=> '2',
		'date'				=> '2015-01-11',
		'correlation_id'	=> '',
		'buyer'				=> 'Belgium Limited',
		'indicator'			=> '3',
		'value'				=> 12,
		'tax'				=> 2.42,
		'vat_rate'			=> 0.21,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 3,
		'country'			=> 'AT',
		'purchase_key'		=> '3',
		'date'				=> '2015-01-11',
		'correlation_id'	=> '',
		'buyer'				=> 'Austria Limited',
		'indicator'			=> '3',
		'value'				=> 13,
		'tax'				=> 2.6,
		'vat_rate'			=> 0.2,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 4,
		'country'			=> 'AT',
		'purchase_key'		=> '4',
		'date'				=> '2015-01-12',
		'correlation_id'	=> '',
		'buyer'				=> 'Austria Limited',
		'indicator'			=> '3',
		'value'				=> 14,
		'tax'				=> 2.8,
		'vat_rate'			=> 0.2,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 5,
		'country'			=> 'CY',
		'purchase_key'		=> '5',
		'date'				=> '2015-01-12',
		'correlation_id'	=> '',
		'buyer'				=> 'Cyprus Limited',
		'indicator'			=> '3',
		'value'				=> 15,
		'tax'				=> 2.85,
		'vat_rate'			=> 0.19,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 6,
		'country'			=> 'CY',
		'purchase_key'		=> '6',
		'date'				=> '2015-01-12',
		'correlation_id'	=> '',
		'buyer'				=> 'Cyprus Limited',
		'indicator'			=> '3',
		'value'				=> 16,
		'tax'				=> 3.04,
		'vat_rate'			=> 0.19,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 7,
		'country'			=> 'CY',
		'purchase_key'		=> '7',
		'date'				=> '2015-02-12',
		'correlation_id'	=> '',
		'buyer'				=> 'Cyprus Limited',
		'indicator'			=> '3',
		'value'				=> 17,
		'tax'				=> 3.23,
		'vat_rate'			=> 0.19,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 8,
		'country'			=> 'CY',
		'purchase_key'		=> '8',
		'date'				=> '2015-02-12',
		'correlation_id'	=> '',
		'buyer'				=> 'Cyprus Limited',
		'indicator'			=> '3',
		'value'				=> 18,
		'tax'				=> 3.42,
		'vat_rate'			=> 0.19,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 9,
		'country'			=> 'BE',
		'purchase_key'		=> '9',
		'date'				=> '2015-02-01',
		'correlation_id'	=> '',
		'buyer'				=> 'Belgium Limited',
		'indicator'			=> '3',
		'value'				=> 19,
		'tax'				=> 3.99,
		'vat_rate'			=> 0.21,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 10,
		'country'			=> 'BE',
		'vrn'				=> 'BE897223274',
		'purchase_key'		=> '10',
		'date'				=> '2015-02-12',
		'correlation_id'	=> '',
		'buyer'				=> 'Belgium Limited',
		'indicator'			=> '3',
		'value'				=> 20,
		'tax'				=> 0,
		'vat_rate'			=> 0,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 11,
		'country'			=> 'AT',
		'purchase_key'		=> '11',
		'date'				=> '2015-03-12',
		'correlation_id'	=> '',
		'buyer'				=> 'Austria Limited',
		'indicator'			=> '3',
		'value'				=> 21,
		'tax'				=> 4.2,
		'vat_rate'			=> 0.2,
		'vat_type'			=> 'standard',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 12,
		'country'			=> 'AT',
		'vrn'				=> 'ATU28560205',
		'purchase_key'		=> '12',
		'date'				=> '2015-01-02',
		'correlation_id'	=> '',
		'buyer'				=> 'Austria Limited',
		'indicator'			=> '3',
		'value'				=> 22,
		'tax'				=> 0,
		'vat_rate'			=> 0,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 13,
		'country'			=> 'DE',
		'purchase_key'		=> '13',
		'date'				=> '2015-01-02',
		'correlation_id'	=> '',
		'buyer'				=> 'German Gesellschaft',
		'indicator'			=> '3',
		'value'				=> 23,
		'tax'				=> 4.37,
		'vat_rate'			=> 0.19,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 14,
		'country'			=> 'CZ',
		'purchase_key'		=> '14',
		'date'				=> '2015-01-05',
		'correlation_id'	=> '',
		'buyer'				=> 'Czech Company',
		'indicator'			=> '3',
		'value'				=> 24,
		'tax'				=> 5.04,
		'vat_rate'			=> 0.21,
		'vat_type'			=> 'reduced',
		'submission_id'		=> 0
	),

	array(
		'id'				=> 15,
		'country'			=> 'CZ',
		'purchase_key'		=> '15',
		'date'				=> '2015-01-06',
		'correlation_id'	=> '',
		'buyer'				=> 'Czech Company',
		'indicator'			=> '3',
		'value'				=> 25,
		'tax'				=> 5.25,
		'vat_rate'			=> 0.21,
		'vat_type'			=> 'standard',
		'submission_id'		=> 0
	)
);


file_put_contents('C:\LyquidityWeb\site2011\wordpress\blogtest\wp-content\plugins\vat-moss\includes/integrations/test-data.json', json_encode( $vat_payments ) );

?>