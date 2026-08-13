<?php
/**
 * Generic WC Imajiner email body.
 *
 * @package WC_Imajiner
 * @var WC_Email $email
 * @var string   $email_heading
 * @var string   $additional_content
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 * @var mixed    $order
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$body = WC_Imajiner_Emails::get_rendered_body( $email );
if ( $body ) {
	echo wp_kses_post( wpautop( wptexturize( $body ) ) );
}

$order_obj = isset( $order ) && $order instanceof WC_Order ? $order : null;
if ( ! $order_obj && isset( $email->object ) && $email->object instanceof WC_Order ) {
	$order_obj = $email->object;
}

if ( $order_obj ) {
	do_action( 'woocommerce_email_order_details', $order_obj, $sent_to_admin, $plain_text, $email );
	do_action( 'woocommerce_email_order_meta', $order_obj, $sent_to_admin, $plain_text, $email );
	do_action( 'woocommerce_email_customer_details', $order_obj, $sent_to_admin, $plain_text, $email );
}

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
