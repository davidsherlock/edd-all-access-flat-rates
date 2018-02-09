<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The All Access Commission Flat Rates Class
 *
 * @since  1.0.0
 */

class EDD_All_Access_Commission_Flat_Rates {


	/**
	 * Get things started
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct() {

		if ( ! defined( 'EDD_COMMISSIONS_VERSION' ) ){
			return;
		}

		// Register our new commission tab
		add_filter( 'eddc_commission_tabs', array( $this, 'edd_all_access_flat_rates_commissions_tab') );

		// Register our new commission view
		add_filter( 'eddc_commission_views', array( $this, 'edd_all_access_flat_rates_commissions_view') );

		// Triggered when an All Access payment is expired
		add_action( 'edd_all_access_expired', array( $this, 'edd_all_access_flat_rates_calculate_commissions' ), 5, 2 );

		// Filter the commission amount
		add_filter( 'edd_commission_info', array( $this, 'edd_all_access_flat_rates_record_commission' ), 5, 4 );

		// Filter the commission amount if flat rates are enabled
		add_action( 'eddc_insert_commission', array( $this, 'edd_all_access_flat_rates_record_commission_meta' ), 10, 6 );

	}

	/**
	 * Register a tab in the single commission view for All Access Flat Rate information.
	 *
	 * @since  1.0.0
	 * @param  array $views An array of existing views
	 * @return array        The altered list of views
	 */
	public function edd_all_access_flat_rates_commissions_tab( $tabs ) {

		// This makes it so former commission recievers get the tab and new commission users with no sales see it
		$tabs['edd-all-access-flat-rates'] = array( 'dashicon' => 'dashicons-welcome-widgets-menus', 'title' => __( 'All Access Flat', 'edd-all-access-flat-rates' ) );

		return $tabs;
	}


	/**
	 * Register a view in the single commission view for All Access Flat Rate information.
	 *
	 * @since  1.0.0
	 * @param  array $views An array of existing views
	 * @return array        The altered list of views
	 */
	public function edd_all_access_flat_rates_commissions_view( $views ) {

		$views['edd-all-access-flat-rates'] = 'edd_all_access_flat_rates_commissions_single_view';

		return $views;
	}


	/**
	 * Triggered when an All Access payment is expired, we use this function to capture the freshly_expired_all_access_pass object
	 *
	 * @since       1.0.0
	 * @param       object $freshly_expired_all_access_pass The All Access Pass Object that has just expired.
	 * @param       array $args The array of args that were passed to the maybe_expire method.
	 * @return      void
	 */
	public function edd_all_access_flat_rates_calculate_commissions( $freshly_expired_all_access_pass, $args ){
		$this->freshly_expired_all_access_pass = $freshly_expired_all_access_pass;
	}


	 /**
	 * The main function for processing the All Access flat rate amount
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $commission_args The commission args
	 * @param       int $commission_id The commission ID
	 * @param       int $payment_id The payment ID
	 * @param       int $download_id The download ID
	 * @return      array $commission_args The maybe updated commission args
	 */
	public function edd_all_access_flat_rates_record_commission( $commission_args, $commission_id, $payment_id, $download_id ) {

		// Get the "Type" that commissions have been set to use for this All Access product
		$commissions_meta_settings = get_post_meta( $download_id, '_edd_commission_settings', true );
		$type = isset( $commissions_meta_settings['type'] ) ? $commissions_meta_settings['type'] : 'percentage';

		// If download does not have "all access" is the commission type, bail and don't modify the $args
		if ( 'all_access' != $type ) {
			return $commission_args;
		}

		// Get the default flat rate for all access commissions
		$rate = (float) edd_all_access_flat_rates_get_default_rate();

		if ( false == $rate ) {
			return $commission_args;
		}

		// Calculate the recipients and their rates for this expired All Access purchase.
		$downloaded_products = array();

		// Add this All Access Pass to the list of Payments we need to check for file downloads.
		$all_access_pass_payments_to_check = array( $this->freshly_expired_all_access_pass->id );

		// Check if this All Access Pass has been upgraded. If so, include the payments containing all "prior" All Access Passes as well.
 		if ( is_array( $this->freshly_expired_all_access_pass->prior_all_access_passes ) ) {
			foreach( $this->freshly_expired_all_access_pass->prior_all_access_passes as $prior_all_access_pass_id ) {

				// Add this All Access Pass ID to the list of All Access Payments we need to check for file download logs
				$all_access_pass_payments_to_check[] = $prior_all_access_pass_id;

			}
		}

		// Get the file downloads this all access product has been used for
		$file_download_query_args = array(
			'post_type'				 => 'edd_log',
			'posts_per_page'         => -1,
			'meta_query'             => array(
				'relation' => 'AND',
				array(
					'key' => '_edd_log_all_access_pass_id',
					'value' => $all_access_pass_payments_to_check,
					'compare' => 'IN',
				),
			)
		);

		$file_download_logs = get_posts( $file_download_query_args );

		$total_value_of_downloaded_items = 0;

		// We need to find out how much each downloaded item costs and add them together
		foreach( $file_download_logs as $log ) {

			// Get the post object for the product that was downloaded
			$downloaded_product = get_post( $log->post_parent );

			// If commissions are not enabled for this product, skip it.
			if ( ! get_post_meta( $downloaded_product->ID, '_edd_commisions_enabled', true ) ) {
				// Skip this cart item - Commissions are not enabled.
				continue;
			}

			// Get the price of this product
			$variable_prices = edd_get_variable_prices( $log->post_parent );

			// Get the value of the product by getting its price - whether variably-priced or not.
			if ( $variable_prices ){
				$price_id = get_post_meta( $log->ID, '_edd_log_price_id', true );
				$price_of_download = edd_get_price_option_amount( $log->post_parent, $price_id );
				$price_key = $log->post_parent . '-' . $price_id;
			} else {
				$price_id = 0;
				$price_of_download = edd_get_download_price( $log->post_parent );
				$price_key = $log->post_parent;
			}

			// Skip (0.00) free downloads
			if ( $price_of_download <= 0 ) {
				continue;
			}

			// Only calculate if we have not already calculated the value for a product. This happens if the user has downloaded it more than once.
			if ( ! array_key_exists( $price_key, $downloaded_products ) ) {
				$downloaded_products[ $price_key ] = array(
					'price' 	  => $price_of_download,
					'download_id' => $downloaded_product->ID,
					'price_id'    => $price_id,
					'amount'     => (float) $rate
				);

				// Add the price of this product to the total value of all downloaded items in this file download log.
				$total_value_of_downloaded_items = $total_value_of_downloaded_items + $price_of_download;

				// Calculate the total commission amount
				$total_commission_amount = $total_commission_amount + $rate;
			}

		}

		// Calculate the flat rate commission amount
		if ( ! empty( $downloaded_products ) ) {

			// Make our flat rates easily filterable
			$updated_commission_args = apply_filters( 'edd_all_access_flat_rates_record_commission_args', array(
				'type'		=> 'flat',
				'rate'    	=> $rate,
				'amount'    => $total_commission_amount
			) );

			// Update the commission args array to our new values
			$commission_args['type']   = $updated_commission_args['type'];
			$commission_args['rate']   = $updated_commission_args['rate'];
			$commission_args['amount'] = $updated_commission_args['amount'];

			// Setup our commission meta args and make it filterable
			$commission_meta_args = apply_filters( 'edd_all_access_flat_rates_record_commission_meta_args', array(
				'products' => $downloaded_products,
				'total'    => $total_value_of_downloaded_items
			) );

			// Store commission meta args as an object variable for later use
			$this->commission_meta_args = $commission_meta_args;

		}

		return $commission_args;
	}


	/**
	* The main function for recording the All Access flat rates commission meta
	*
	* @access      public
	* @since       1.0.0
	* @param       int $recipient The User ID
	* @param       float $commission_amount The maybe updated commission amount
	* @param       float $rate The commission rate
	* @param       int $download_id The download ID
	* @param       int $commission_id The commission ID
	* @param       int $payment_id The payment ID
	* @return      void
	*/
	public function edd_all_access_flat_rates_record_commission_meta( $recipient, $commission_amount, $rate, $download_id, $commission_id, $payment_id ) {

		// Get the "Type" that commissions have been set to use for this All Access product
		$commissions_meta_settings = get_post_meta( $download_id, '_edd_commission_settings', true );
		$type = isset( $commissions_meta_settings['type'] ) ? $commissions_meta_settings['type'] : 'percentage';

		// If download does not have "all access" is the commission type, bail and don't modify the $args
		if ( 'all_access' != $type ) {
			return;
		}

		// Get the default flat rate for all access commissions
		$rate = (float) edd_all_access_flat_rates_get_default_rate();

		if ( false == $rate ) {
			return;
		}

		// Get the commission object
		$commission = eddc_get_commission( $commission_id );

		// Store the commission meta
		$commission->update_meta( '_edd_all_access_flat_rates', $this->commission_meta_args );
	}

}
