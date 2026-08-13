<?php
/**
 * Minimalist billing / shipping addresses.
 *
 * @package WC_Imajiner
 * @var WC_Order $order
 * @var bool     $sent_to_admin
 */

defined( 'ABSPATH' ) || exit;

$address  = $order->get_formatted_billing_address();
$shipping = $order->get_formatted_shipping_address();
?>
<table id="addresses" cellspacing="0" cellpadding="0" border="0" width="100%" role="presentation">
	<tr>
		<td class="address-col" valign="top" width="50%">
			<p class="address-title"><?php esc_html_e( 'Alamat penagihan', 'wc-imajiner' ); ?></p>
			<address class="address">
				<?php echo wp_kses_post( $address ? $address : esc_html__( 'Tidak ada', 'wc-imajiner' ) ); ?>
				<?php if ( $order->get_billing_phone() ) : ?>
					<br><?php echo wc_make_phone_clickable( $order->get_billing_phone() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
				<?php if ( $order->get_billing_email() ) : ?>
					<br><?php echo esc_html( $order->get_billing_email() ); ?>
				<?php endif; ?>
				<?php do_action( 'woocommerce_email_customer_address_section', 'billing', $order, $sent_to_admin, false ); ?>
			</address>
		</td>
		<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && $shipping ) : ?>
			<td class="address-col" valign="top" width="50%">
				<p class="address-title"><?php esc_html_e( 'Alamat pengiriman', 'wc-imajiner' ); ?></p>
				<address class="address">
					<?php echo wp_kses_post( $shipping ); ?>
					<?php if ( $order->get_shipping_phone() ) : ?>
						<br><?php echo wc_make_phone_clickable( $order->get_shipping_phone() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
					<?php do_action( 'woocommerce_email_customer_address_section', 'shipping', $order, $sent_to_admin, false ); ?>
				</address>
			</td>
		<?php endif; ?>
	</tr>
</table>
