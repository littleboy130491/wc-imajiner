<?php
/**
 * Minimalist order details table for emails.
 *
 * @package WC_Imajiner
 * @var WC_Order $order
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 * @var WC_Email $email
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );
?>

<h2 class="wc-imajiner-order-heading">
	<?php
	if ( $sent_to_admin ) {
		echo '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">';
	}
	printf(
		/* translators: %s: order number */
		esc_html__( 'Pesanan #%s', 'wc-imajiner' ),
		esc_html( $order->get_order_number() )
	);
	if ( $sent_to_admin ) {
		echo '</a>';
	}
	?>
	<span><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
</h2>

<div class="wc-imajiner-order-table-wrap">
	<table class="wc-imajiner-order-table" cellspacing="0" cellpadding="0" border="0" width="100%" role="presentation">
		<thead>
			<tr>
				<th class="product" scope="col"><?php esc_html_e( 'Produk', 'wc-imajiner' ); ?></th>
				<th class="qty" scope="col"><?php esc_html_e( 'Jml', 'wc-imajiner' ); ?></th>
				<th class="price" scope="col"><?php esc_html_e( 'Harga', 'wc-imajiner' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			echo wc_get_email_order_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$order,
				array(
					'show_sku'      => false,
					'show_image'    => false,
					'image_size'    => array( 32, 32 ),
					'plain_text'    => $plain_text,
					'sent_to_admin' => $sent_to_admin,
				)
			);
			?>
		</tbody>
		<tfoot>
			<?php
			$item_totals = $order->get_order_item_totals();
			if ( $item_totals ) {
				$last = array_key_last( $item_totals );
				foreach ( $item_totals as $key => $total ) {
					$row_class = 'order_total' === $key ? ' is-total' : '';
					if ( $key === $last ) {
						$row_class .= ' is-last';
					}
					?>
					<tr class="totals-row<?php echo esc_attr( $row_class ); ?>">
						<th scope="row" colspan="2"><?php echo wp_kses_post( $total['label'] ); ?></th>
						<td class="price"><?php echo wp_kses_post( $total['value'] ); ?></td>
					</tr>
					<?php
				}
			}

			if ( $order->get_customer_note() ) {
				?>
				<tr class="customer-note">
					<th scope="row"><?php esc_html_e( 'Catatan', 'wc-imajiner' ); ?></th>
					<td colspan="2"><?php echo wp_kses( nl2br( wc_wptexturize_order_note( $order->get_customer_note() ) ), array( 'br' => array() ) ); ?></td>
				</tr>
				<?php
			}
			?>
		</tfoot>
	</table>
</div>

<?php
do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );
