<?php

/*
 * Part of: VAT-MOSS
 * @Description: Create a report to display the list of summary lines associated with a submission.
 * @Author: Bill Seddon
 * @Author URI: http://www.lyquidity.com
 * @Copyright: Lyquidity Solutions Limited 2013 and later
 * @License:	GNU Version 2 or Any Later Version
 */

namespace lyquidity\vat_moss;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Presents a list of summary lines for a submission
 *
 * Renders the MOSS Submissions table
 *
 * @since 1.0
 */
class MOSS_Summary extends \WP_List_Table {

	/**
	 * A list of the payments to be reported
	 * @var array $lines
	 */
	private $lines;

	/**
	 * A list of MOSS lines to report
	 * @var array $moss_lines
	 */
	private $moss_lines;

	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @see WP_List_Table::__construct()
	 */
	public function __construct($moss_lines) {

		$this->lines = array();

		// Set parent defaults
		parent::__construct( array(
			'singular'  => __( 'MOSS Submission Summary', 'vat_moss' ),	// Singular name of the listed records
			'plural'    => __( 'MOSS Submission Summaries', 'vat_moss' ),	// Plural name of the listed records
			'ajax'      => false,										// Does this table support ajax?
		));
		$this->moss_lines = $moss_lines;
		$this->query();
	}

	/**
	 *
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @param array $item Contains all the data of the downloads
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since 1.0
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = array(
			'country_code'		=> __( 'Country',		'vat_moss' ),
			'vat_type'			=> __( 'VAT Type',		'vat_moss' ),
			'vat_rate'			=> __( 'VAT Rate (%)',	'vat_moss' ),
			'net'				=> __( 'Net',			'vat_moss' ) . " (" . get_currency_symbol() . ")",
			'tax'				=> __( 'Tax',			'vat_moss' ) . " (" . get_currency_symbol() . ")"
		);

		return $columns;
	}

	/**
	 * Retrieve the table's sortable columns
	 *
	 * @access public
	 * @since 1.0
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns()
	{

		return array(
			'country_code'	=> array( 'country_code', true ),
			'vat_type'		=> array( 'vat_type', true ),
			'vat_rate'		=> array( 'vat_rate', true )
		);
	}

	/** ==============================================================
	 *  BEGIN Query support function
	 *  --------------------------------------------------------------
	 *
	 * The following 10 functions provide sorting for the vat payments
	 */
	function sortbycountry_code_asc($a, $b)
	{
		return strcasecmp( $a['country_code'], $b['country_code'] );
	}

	function sortbycountry_code_desc($a, $b)
	{
		return strcasecmp( $b['country_code'], $a['country_code'] );
	}

	function sortbyvat_type_asc($a, $b)
	{
		return strcasecmp( $a['vat_type'], $b['vat_type'] );
	}

	function sortbyvat_type_desc($a, $b)
	{
		return strcasecmp( $b['vat_type'], $a['vat_type'] );
	}

	function sortbyvat_rate_asc($a, $b)
	{
		return $a['id'] -  $b['id'];
	}

	function sortbyvat_rate_desc($a, $b)
	{
		return $b['id'] -  $a['id'];
	}

	/**
	 * Outputs the reporting views
	 *
	 * @access public
	 * @since 1.5
	 * @return void
	 */
	public function bulk_actions() {
		// These aren't really bulk actions but this outputs the markup in the right place
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function display_tablenav( $which ) {

	}

	/**
	 * Performs the products query
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function query() {

		$locale = localeconv();
		// Flatten the moss_lines array
		foreach ( $this->moss_lines as $country_code => $vat_types )
		{
			foreach ( $vat_types as $vat_type => $vat_rates )
			{
				foreach ( $vat_rates as $vat_rate => $values )
				{
					$this->lines[] = array(
						'country_code'	=> $country_code,
						'vat_type'		=> $vat_type,
						'vat_rate'		=> number_format( $vat_rate * 100, 1, $locale['decimal_point'], $locale['thousands_sep'] ) ,
						'tax'			=> number_format( $values['tax'], 2, $locale['decimal_point'], $locale['thousands_sep'] ),
						'net'			=> number_format( $values['net'], 2, $locale['decimal_point'], $locale['thousands_sep'] )
					);
				}
			}
		}

		// Sort them
		$orderby	= isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'country_code';
		$order		= isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$order = strtolower( $order );

		uasort( $this->lines, array( $this, "sortby{$orderby}_{$order}" ) );

		return $this->lines;
	}

	/** --------------------------------------------------------------
	 *  END Query support function
	 *  ==============================================================
	 *
	 * Build all the reports data
	 *
	 * @access public
	 * @since 1.0
	 * @return array $reports_data All the data for customer reports
	 */
	public function reports_data() {

		return $this->lines;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since 1.0
	 * @uses Sales_Report_Table::get_columns()
	 * @uses Sales_Report_Table::get_sortable_columns()
	 * @uses Sales_Report_Table::reports_data()
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->reports_data();

	}
}

?>