<?php
/**
 * Minimalist email header.
 *
 * @package WC_Imajiner
 * @var string $email_heading
 */

defined( 'ABSPATH' ) || exit;

$store_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
$logo       = get_option( 'woocommerce_email_header_image' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $store_name ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
<table id="outer_wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
	<tr>
		<td></td>
		<td width="600">
			<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
				<table id="inner_wrapper" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
					<tr>
						<td align="center" valign="top">
							<?php if ( $logo ) : ?>
								<div id="template_header_image">
									<p style="margin-top:0;">
										<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $store_name ); ?>" />
									</p>
								</div>
							<?php endif; ?>
							<table id="template_container" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
								<tr>
									<td align="center" valign="top">
										<table id="template_header" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
											<tr>
												<td id="header_wrapper">
													<p class="wc-imajiner-store"><?php echo esc_html( $store_name ); ?></p>
													<h1><?php echo esc_html( $email_heading ); ?></h1>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td align="center" valign="top">
										<table id="template_body" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
											<tr>
												<td id="body_content" valign="top">
													<table border="0" cellpadding="20" cellspacing="0" width="100%" role="presentation">
														<tr>
															<td id="body_content_inner_cell" valign="top">
																<div id="body_content_inner">
