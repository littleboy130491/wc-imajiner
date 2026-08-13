<?php
/**
 * Minimalist email order items.
 *
 * @package WC_Imajiner
 * @var WC_Order $order
 * @var array    $items
 * @var bool     $show_sku
 * @var bool     $show_image
 * @var array    $image_size
 * @var bool     $plain_text
 * @var bool     $sent_to_admin
 * @var bool     $show_purchase_note
 */

defined( 'ABSPATH' ) || exit;

foreach ( $items as $item_id => $item ) {
	$product       = $item->get_product();
	$sku           = '';
	$purchase_note = '';

	if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		continue;
	}

	if ( is_object( $product ) ) {
		$sku           = $product->get_sku();
		$purchase_note = $product->get_purchase_note();
	}

	$qty          = $item->get_quantity();
	$refunded_qty = $order->get_qty_refunded_for_item( $item_id );
	if ( $refunded_qty ) {
		$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
	} else {
		$qty_display = esc_html( $qty );
	}
	?>
	<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
		<td class="product">
			<?php
			echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );

			if ( $show_sku && $sku ) {
				echo ' <span class="sku">#' . esc_html( $sku ) . '</span>';
			}

			do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );

			$item_meta = wc_display_item_meta(
				$item,
				array(
					'before'       => '',
					'after'        => '',
					'separator'    => ', ',
					'echo'         => false,
					'label_before' => '',
					'label_after'  => ': ',
				)
			);

			if ( $item_meta ) {
				echo '<div class="item-meta">' . wp_kses(
					$item_meta,
					array(
						'br'   => array(),
						'span' => array(),
						'a'    => array(
							'href'   => true,
							'target' => true,
							'rel'    => true,
							'title'  => true,
						),
					)
				) . '</div>';
			}

			do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );
			?>
		</td>
		<td class="qty">
			<?php echo wp_kses_post( apply_filters( 'woocommerce_email_order_item_quantity', $qty_display, $item ) ); ?>
		</td>
		<td class="price">
			<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
		</td>
	</tr>
	<?php
	if ( $show_purchase_note && $purchase_note ) {
		?>
		<tr class="purchase-note">
			<td colspan="3"><?php echo wp_kses_post( wpautop( do_shortcode( $purchase_note ) ) ); ?></td>
		</tr>
		<?php
	}
}
