<?php
/**
 * Minimalist email styles.
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

$bg         = '#f4f4f5';
$body       = '#ffffff';
$text       = '#1f2937';
$muted      = '#6b7280';
$border     = '#e5e7eb';
$link_color = '#111827';
?>
body {
	background-color: <?php echo esc_attr( $bg ); ?>;
	margin: 0;
	padding: 0;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
	-webkit-text-size-adjust: 100%;
}
#outer_wrapper {
	background-color: <?php echo esc_attr( $bg ); ?>;
	width: 100%;
}
#wrapper {
	margin: 0 auto;
	padding: 32px 16px;
	width: 100%;
	max-width: 600px;
}
#template_container,
#template_body,
#template_header,
#template_footer {
	background-color: <?php echo esc_attr( $body ); ?>;
	width: 100%;
}
#template_container {
	border: 1px solid <?php echo esc_attr( $border ); ?>;
	border-radius: 6px;
	overflow: hidden;
}
#header_wrapper {
	padding: 28px 32px 16px;
	border-bottom: 1px solid <?php echo esc_attr( $border ); ?>;
}
.wc-imajiner-store {
	margin: 0 0 8px;
	font-size: 13px;
	color: <?php echo esc_attr( $muted ); ?>;
}
#template_header h1 {
	margin: 0;
	font-size: 22px;
	line-height: 1.3;
	color: #111827;
	font-weight: 600;
}
#body_content {
	background-color: <?php echo esc_attr( $body ); ?>;
}
#body_content_inner {
	color: <?php echo esc_attr( $text ); ?>;
	font-size: 15px;
	line-height: 1.6;
}
#body_content_inner p {
	margin: 0 0 14px;
}
#body_content_inner a {
	color: <?php echo esc_attr( $link_color ); ?>;
}
#template_header_image img {
	max-width: 160px;
	height: auto;
	border: 0;
}
#template_footer {
	background: transparent;
}
#credit {
	padding: 16px 8px 0;
	color: <?php echo esc_attr( $muted ); ?>;
	font-size: 12px;
	text-align: center;
}
#body_content_inner table {
	width: 100%;
	border-collapse: collapse;
	border: 0;
}
#body_content_inner th,
#body_content_inner td,
#body_content_inner .td {
	border: 0;
	padding: 0;
	text-align: left;
	font-size: 14px;
	vertical-align: top;
}
#body_content_inner .wc-imajiner-order-heading {
	margin: 24px 0 10px;
	font-size: 14px;
	font-weight: 600;
	color: #111827;
	line-height: 1.4;
}
#body_content_inner .wc-imajiner-order-heading span {
	display: block;
	margin-top: 2px;
	font-size: 12px;
	font-weight: 400;
	color: <?php echo esc_attr( $muted ); ?>;
}
#body_content_inner .wc-imajiner-order-table-wrap {
	margin: 0 0 24px;
}
#body_content_inner .wc-imajiner-order-table thead th {
	padding: 0 0 8px;
	border: 0;
	border-bottom: 1px solid <?php echo esc_attr( $border ); ?>;
	font-size: 11px;
	font-weight: 500;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	color: <?php echo esc_attr( $muted ); ?>;
	background: transparent;
}
#body_content_inner .wc-imajiner-order-table td,
#body_content_inner .wc-imajiner-order-table tfoot th {
	padding: 10px 0;
	border: 0;
	border-bottom: 1px solid <?php echo esc_attr( $border ); ?>;
	font-size: 14px;
	color: <?php echo esc_attr( $text ); ?>;
	background: transparent;
}
#body_content_inner .wc-imajiner-order-table .qty,
#body_content_inner .wc-imajiner-order-table .price {
	width: 72px;
	white-space: nowrap;
	text-align: right;
}
#body_content_inner .wc-imajiner-order-table .item-meta,
#body_content_inner .wc-imajiner-order-table .sku {
	display: block;
	margin-top: 2px;
	font-size: 12px;
	color: <?php echo esc_attr( $muted ); ?>;
	font-weight: 400;
}
#body_content_inner .wc-imajiner-order-table tfoot th {
	font-weight: 400;
	color: <?php echo esc_attr( $muted ); ?>;
	text-align: right;
	padding-right: 16px;
}
#body_content_inner .wc-imajiner-order-table tfoot tr.is-total th,
#body_content_inner .wc-imajiner-order-table tfoot tr.is-total td {
	border-bottom: 0;
	padding-top: 12px;
	font-weight: 600;
	color: #111827;
}
#body_content_inner .wc-imajiner-order-table tfoot tr.customer-note th,
#body_content_inner .wc-imajiner-order-table tfoot tr.customer-note td {
	border-bottom: 0;
	padding-top: 14px;
	font-weight: 400;
	text-align: left;
	color: <?php echo esc_attr( $muted ); ?>;
}
#addresses {
	width: 100%;
	margin: 8px 0 8px;
}
#addresses td,
#addresses .address-col {
	border: 0;
	padding: 0 24px 0 0;
	vertical-align: top;
}
#addresses .address-title {
	margin: 0 0 6px;
	font-size: 11px;
	font-weight: 500;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	color: <?php echo esc_attr( $muted ); ?>;
}
#addresses .address {
	font-style: normal;
	font-size: 13px;
	line-height: 1.5;
	color: <?php echo esc_attr( $text ); ?>;
}
#addresses h2,
#body_content_inner h2 {
	font-size: 14px;
	font-weight: 600;
	margin: 20px 0 8px;
}
#template_header_image {
	padding: 0 0 16px;
	text-align: left;
}
@media screen and (max-width: 600px) {
	#wrapper {
		padding: 16px 8px !important;
		width: 100% !important;
	}
}
