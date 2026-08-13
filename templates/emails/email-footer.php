<?php
/**
 * Minimalist email footer.
 *
 * @package WC_Imajiner
 * @var WC_Email|null $email
 */

defined( 'ABSPATH' ) || exit;

$email             = $email ?? null;
$email_footer_text = get_option( 'woocommerce_email_footer_text' );
?>
																</div>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td align="center" valign="top">
							<table id="template_footer" border="0" cellpadding="10" cellspacing="0" width="100%" role="presentation">
								<tr>
									<td valign="top">
										<table border="0" cellpadding="10" cellspacing="0" width="100%" role="presentation">
											<tr>
												<td id="credit" colspan="2" valign="middle">
													<?php
													echo wp_kses_post(
														wpautop(
															wptexturize(
																apply_filters( 'woocommerce_email_footer_text', $email_footer_text, $email )
															)
														)
													);
													?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
		</td>
		<td></td>
	</tr>
</table>
</body>
</html>
