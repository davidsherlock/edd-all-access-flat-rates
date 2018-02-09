<?php
/**
 * Helper Functions
 *
 * @package     EDD\All_Access_Flat_Rates\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Gets the default all access commission flat rate
 *
 * @access      public
 * @since       1.0.0
 * @return      float
 */
function edd_all_access_flat_rates_get_default_rate() {
	global $edd_options;

	$rate = isset( $edd_options['edd_all_access_flat_rates_default_rate'] ) ? $edd_options['edd_all_access_flat_rates_default_rate'] : false;

	return apply_filters( 'edd_all_access_flat_rates_get_default_rate', $rate );
}


/**
 * View All Access Flat Rate data for a single Commission
 *
 * @since  1.0.0
 * @param  $commission The commission object being displayed
 * @return void
 */
function edd_all_access_flat_rates_commissions_single_view( $commission ) {
	if ( ! $commission ) {
		echo '<div class="info-wrapper item-section">' . __( 'Invalid commission specified.', 'eddc' ) . '</div>';
		return;
	}

	$base                 		= admin_url( 'edit.php?post_type=download&page=edd-commissions&view=overview&commission=' . $commission->ID );
	$base                 		= wp_nonce_url( $base, 'eddc_commission_nonce' );
	$commission_id        		= $commission->ID;

	// Get Commission Meta data
	$all_all_access_flat_rates  = $commission->get_meta( '_edd_all_access_flat_rates', true );
	$all_access_info      		= $commission->get_meta( '_edd_all_access_info', true );

	// Setup our data for display
	$downloaded_products 		= $all_all_access_flat_rates['products'];

	// If there is no All Access Data attached to this commission, this is a non-All Access commission (a normal commission).
    if( empty( $downloaded_products ) ){

        ?><div id="customer-tables-wrapper" class="customer-section">

		<h3><?php echo __( 'All Access Pass Data', 'edd-all-access-flat-rates' ); ?></h3>

        <p><?php echo __( 'This commission is not for an All Access Flat Rates product.', 'edd-all-access-flat-rates' ); ?></p>
        </div>

        <?php return;
    }

	// Setup our values for output
	$total_price          		= $all_access_info['all_access_total_price'];
	$total_price_display  		= edd_currency_filter( edd_format_amount( $total_price ) );
	$total_cost 		    	= $all_all_access_flat_rates['total'];
	$total_cost_display  		= edd_currency_filter( edd_format_amount( $total_cost ) );
	$total_commission_amount  	= edd_currency_filter( edd_format_amount( $commission->amount ) );

	do_action( 'edd_all_access_flat_rates_commission_flat_card_top', $commission_id );
	?>
	<div id="customer-tables-wrapper" class="customer-section">

		<h3><?php echo __( 'All Access Pass Data', 'edd-all-access-flat-rates' ); ?></h3>

		<table class="wp-list-table widefat striped downloads">
			<thead>
				<tr>
					<th><?php echo __( 'Total Cost of All Access', 'edd-all-access-flat-rates' ); ?></th>
					<th><?php echo __( 'Total Cost of Products', 'edd-all-access-flat-rates' ); ?></th>
					<th><?php echo __( 'Total Commission Amount', 'edd-all-access-flat-rates' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo $total_price_display; ?></td>
					<td><?php echo $total_cost_display; ?></td>
					<td><?php echo $total_commission_amount; ?></td>
				</tr>
			</tbody>
		</table>

		<table class="wp-list-table widefat striped downloads">
			<thead>
				<tr>
					<th style="text-align:center;"><?php echo __( 'Product', 'edd-all-access-flat-rates' ); ?></th>
					<th style="text-align:center;"><?php echo __( 'Price', 'edd-all-access-flat-rates' ); ?></th>
					<th style="text-align:center;"><?php echo __( 'Commission', 'edd-all-access-flat-rates' ); ?></th>
				</tr>
			</thead>
			<tbody style="text-align:center;">
				<?php if ( ! empty( $downloaded_products ) ) : ?>
					<?php foreach ( $downloaded_products as $price_key => $product_info ) {
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $price_key ) ); ?>"><?php echo get_the_title( $price_key ); ?></a>
							</td>

							<td>
								<?php echo edd_currency_filter( edd_format_amount( $product_info['price'] ) ); ?>
							</td>

							<td>
								<?php echo edd_currency_filter( edd_format_amount( $product_info['amount'] ) ); ?>
							</td>

						</tr>
						<?php

						}
				else: ?>
					<tr><td colspan="2"><?php _e( 'No downloaded products found.', 'edd-all-access-flat-rates' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>

	</div>

	<?php
	do_action( 'edd_all_access_flat_rates_commission_flat_card_bottom', $commission_id );
}
