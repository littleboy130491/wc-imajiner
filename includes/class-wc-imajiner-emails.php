<?php
/**
 * Indonesian email templates and admin editor.
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lets merchants edit customer emails from the Imajiner dashboard.
 */
class WC_Imajiner_Emails {

	const OPTION_KEY = 'wc_imajiner_emails';

	/**
	 * Temporary template values used while rendering a preview.
	 *
	 * @var array<string, array<string, string>>
	 */
	public static $preview_override = array();

	/**
	 * Wire filters and save handler.
	 */
	public function hooks() {
		add_action( 'admin_post_wc_imajiner_save_emails', array( $this, 'handle_save' ) );
		add_action( 'admin_post_wc_imajiner_reset_email', array( $this, 'handle_reset' ) );
		add_action( 'wp_ajax_wc_imajiner_preview_email', array( $this, 'handle_preview' ) );
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 20, 3 );
		add_filter( 'woocommerce_email_footer_text', array( $this, 'filter_footer_text' ), 20, 2 );

		foreach ( array_keys( self::definitions() ) as $email_id ) {
			add_filter( 'woocommerce_email_subject_' . $email_id, array( $this, 'filter_subject' ), 20, 3 );
			add_filter( 'woocommerce_email_heading_' . $email_id, array( $this, 'filter_heading' ), 20, 3 );
			add_filter( 'woocommerce_email_additional_content_' . $email_id, array( $this, 'filter_additional' ), 20, 3 );
		}

		self::maybe_remove_on_hold_payment_url_from_stored_template();
	}

	/**
	 * Drop the leftover pay-now line from a previously saved on-hold template.
	 */
	protected static function maybe_remove_on_hold_payment_url_from_stored_template() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) || empty( $stored['customer_on_hold_order']['body'] ) || ! is_string( $stored['customer_on_hold_order']['body'] ) ) {
			return;
		}

		$body    = $stored['customer_on_hold_order']['body'];
		$updated = preg_replace( '/\n?Bayar sekarang:\s*\{payment_url\}/u', '', $body );
		if ( ! is_string( $updated ) || $updated === $body ) {
			return;
		}

		$stored['customer_on_hold_order']['body'] = trim( $updated );
		update_option( self::OPTION_KEY, $stored );
	}

	/**
	 * Email types we manage.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		return array(
			'customer_on_hold_order'    => array(
				'label'       => __( 'Pesanan menunggu pembayaran', 'wc-imajiner' ),
				'description' => __( 'Dikirim setelah checkout transfer bank, status On hold.', 'wc-imajiner' ),
				'has_order'   => true,
			),
			'customer_processing_order' => array(
				'label'       => __( 'Pembayaran diterima', 'wc-imajiner' ),
				'description' => __( 'Dikirim saat pesanan masuk status Processing.', 'wc-imajiner' ),
				'has_order'   => true,
			),
			'customer_completed_order'  => array(
				'label'       => __( 'Pesanan selesai', 'wc-imajiner' ),
				'description' => __( 'Dikirim saat pesanan selesai.', 'wc-imajiner' ),
				'has_order'   => true,
			),
			'customer_cancelled_order'  => array(
				'label'       => __( 'Pesanan dibatalkan', 'wc-imajiner' ),
				'description' => __( 'Dikirim saat pesanan dibatalkan, termasuk otomatis 24 jam.', 'wc-imajiner' ),
				'has_order'   => true,
			),
			'customer_failed_order'     => array(
				'label'       => __( 'Pembayaran gagal', 'wc-imajiner' ),
				'description' => __( 'Dikirim jika pembayaran gagal.', 'wc-imajiner' ),
				'has_order'   => true,
			),
			'customer_refunded_order'   => array(
				'label'       => __( 'Dana dikembalikan', 'wc-imajiner' ),
				'description' => __( 'Dikirim saat pengembalian dana diproses.', 'wc-imajiner' ),
				'has_order'   => true,
			),
			'customer_invoice'          => array(
				'label'       => __( 'Tagihan pesanan', 'wc-imajiner' ),
				'description' => __( 'Tagihan / invoice yang dikirim manual ke pelanggan.', 'wc-imajiner' ),
				'has_order'   => true,
			),
			'customer_note'             => array(
				'label'       => __( 'Catatan pesanan', 'wc-imajiner' ),
				'description' => __( 'Catatan dari toko yang dikirim ke pelanggan.', 'wc-imajiner' ),
				'has_order'   => true,
			),
			'customer_new_account'      => array(
				'label'       => __( 'Akun baru', 'wc-imajiner' ),
				'description' => __( 'Dikirim saat pelanggan membuat akun.', 'wc-imajiner' ),
				'has_order'   => false,
			),
			'customer_reset_password'   => array(
				'label'       => __( 'Atur ulang kata sandi', 'wc-imajiner' ),
				'description' => __( 'Dikirim saat pelanggan meminta reset kata sandi.', 'wc-imajiner' ),
				'has_order'   => false,
			),
			'new_order'                 => array(
				'label'       => __( 'Pesanan baru (admin)', 'wc-imajiner' ),
				'description' => __( 'Notifikasi internal untuk toko saat ada pesanan baru.', 'wc-imajiner' ),
				'has_order'   => true,
			),
		);
	}

	/**
	 * Indonesian defaults.
	 *
	 * @return array<string, array{subject:string,heading:string,body:string,additional:string}>
	 */
	public static function defaults() {
		$site = '{site_title}';

		return array(
			'customer_on_hold_order'    => array(
				'subject'     => 'Pesanan #{order_number} menunggu pembayaran',
				'heading'     => 'Menunggu pembayaran',
				'body'        => "Halo {customer_first_name},\n\nTerima kasih sudah berbelanja di {$site}. Pesanan #{order_number} sudah kami terima dan menunggu pembayaran.\n\nSilakan selesaikan pembayaran dalam 24 jam agar pesanan tetap diproses. Setelah 24 jam, pesanan akan dibatalkan otomatis.\n\nTotal: {order_total}",
				'additional'  => 'Jika sudah transfer, simpan bukti pembayaran. Hubungi kami jika butuh bantuan.',
			),
			'customer_processing_order' => array(
				'subject'     => 'Pembayaran pesanan #{order_number} sudah kami terima',
				'heading'     => 'Pembayaran diterima',
				'body'        => "Halo {customer_first_name},\n\nPembayaran untuk pesanan #{order_number} sudah kami terima. Pesanan Anda sedang kami proses.",
				'additional'  => 'Kami akan mengabari Anda lagi setelah pesanan selesai.',
			),
			'customer_completed_order'  => array(
				'subject'     => 'Pesanan #{order_number} sudah selesai',
				'heading'     => 'Pesanan selesai',
				'body'        => "Halo {customer_first_name},\n\nPesanan #{order_number} sudah selesai. Terima kasih telah berbelanja di {$site}.",
				'additional'  => 'Simpan email ini sebagai bukti transaksi Anda.',
			),
			'customer_cancelled_order'  => array(
				'subject'     => 'Pesanan #{order_number} dibatalkan',
				'heading'     => 'Pesanan dibatalkan',
				'body'        => "Halo {customer_first_name},\n\nPesanan #{order_number} telah dibatalkan.\n\nJika Anda sudah melakukan pembayaran, silakan hubungi kami agar kami bantu cek.",
				'additional'  => 'Anda bisa membuat pesanan baru kapan saja di toko kami.',
			),
			'customer_failed_order'     => array(
				'subject'     => 'Pembayaran pesanan #{order_number} gagal',
				'heading'     => 'Pembayaran gagal',
				'body'        => "Halo {customer_first_name},\n\nPembayaran untuk pesanan #{order_number} tidak berhasil. Silakan coba lagi atau pilih metode pembayaran lain.\n\nBayar ulang: {payment_url}",
				'additional'  => 'Jika masalah berlanjut, hubungi tim toko kami.',
			),
			'customer_refunded_order'   => array(
				'subject'     => 'Pengembalian dana pesanan #{order_number}',
				'heading'     => 'Dana dikembalikan',
				'body'        => "Halo {customer_first_name},\n\nDana untuk pesanan #{order_number} sudah kami proses untuk dikembalikan.",
				'additional'  => 'Waktu masuknya dana tergantung bank atau metode pembayaran Anda.',
			),
			'customer_invoice'          => array(
				'subject'     => 'Tagihan pesanan #{order_number}',
				'heading'     => 'Tagihan pesanan',
				'body'        => "Halo {customer_first_name},\n\nBerikut tagihan untuk pesanan #{order_number}.\n\nTotal: {order_total}\nBayar: {payment_url}",
				'additional'  => 'Harap selesaikan pembayaran sesuai instruksi di toko.',
			),
			'customer_note'             => array(
				'subject'     => 'Catatan baru untuk pesanan #{order_number}',
				'heading'     => 'Catatan pesanan',
				'body'        => "Halo {customer_first_name},\n\nAda catatan baru untuk pesanan #{order_number}:\n\n{customer_note}",
				'additional'  => '',
			),
			'customer_new_account'      => array(
				'subject'     => "Akun {$site} Anda sudah siap",
				'heading'     => 'Selamat datang',
				'body'        => "Halo {customer_first_name},\n\nAkun Anda di {$site} sudah dibuat.\n\nNama pengguna: {user_login}\nMasuk ke akun: {my_account_url}\n\nAtur kata sandi: {set_password_url}",
				'additional'  => 'Simpan data akun Anda dan jangan bagikan kepada siapa pun.',
			),
			'customer_reset_password'   => array(
				'subject'     => "Atur ulang kata sandi {$site}",
				'heading'     => 'Atur ulang kata sandi',
				'body'        => "Halo,\n\nKami menerima permintaan untuk mengatur ulang kata sandi akun Anda.\n\nBuat kata sandi baru: {set_password_url}\n\nJika Anda tidak meminta ini, abaikan email ini.",
				'additional'  => '',
			),
			'new_order'                 => array(
				'subject'     => 'Pesanan baru #{order_number}',
				'heading'     => 'Pesanan baru',
				'body'        => "Ada pesanan baru #{order_number} dari {customer_name}.\n\nTotal: {order_total}",
				'additional'  => 'Silakan cek dashboard WooCommerce untuk memproses pesanan.',
			),
		);
	}

	/**
	 * Stored templates merged with defaults.
	 *
	 * @return array<string, array{subject:string,heading:string,body:string,additional:string}>
	 */
	public static function all() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$merged = self::defaults();
		foreach ( $merged as $id => $fields ) {
			if ( empty( $stored[ $id ] ) || ! is_array( $stored[ $id ] ) ) {
				continue;
			}
			foreach ( $fields as $key => $default ) {
				if ( isset( $stored[ $id ][ $key ] ) && is_string( $stored[ $id ][ $key ] ) ) {
					$merged[ $id ][ $key ] = $stored[ $id ][ $key ];
				}
			}
		}

		return $merged;
	}

	/**
	 * One email template.
	 *
	 * @param string $email_id Email id.
	 * @return array{subject:string,heading:string,body:string,additional:string}
	 */
	public static function get_template( $email_id ) {
		$all = self::all();
		$base = isset( $all[ $email_id ] ) ? $all[ $email_id ] : array(
			'subject'    => '',
			'heading'    => '',
			'body'       => '',
			'additional' => '',
		);

		if ( ! empty( self::$preview_override[ $email_id ] ) && is_array( self::$preview_override[ $email_id ] ) ) {
			return wp_parse_args( self::$preview_override[ $email_id ], $base );
		}

		return $base;
	}

	/**
	 * Selected email on the editor screen.
	 *
	 * @return string
	 */
	public static function current_email_id() {
		$ids = array_keys( self::definitions() );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$email = isset( $_GET['email'] ) ? sanitize_key( wp_unslash( $_GET['email'] ) ) : $ids[0];
		return in_array( $email, $ids, true ) ? $email : $ids[0];
	}

	/**
	 * Map WC template files we override.
	 *
	 * @return array<string, string>
	 */
	protected function template_map() {
		$map = array(
			'emails/email-header.php'        => 'emails/email-header.php',
			'emails/email-footer.php'        => 'emails/email-footer.php',
			'emails/email-styles.php'        => 'emails/email-styles.php',
			'emails/email-order-details.php' => 'emails/email-order-details.php',
			'emails/email-order-items.php'   => 'emails/email-order-items.php',
			'emails/email-addresses.php'     => 'emails/email-addresses.php',
		);

		$file_by_id = array(
			'customer_on_hold_order'    => 'emails/customer-on-hold-order.php',
			'customer_processing_order' => 'emails/customer-processing-order.php',
			'customer_completed_order'  => 'emails/customer-completed-order.php',
			'customer_cancelled_order'  => 'emails/customer-cancelled-order.php',
			'customer_failed_order'     => 'emails/customer-failed-order.php',
			'customer_refunded_order'   => 'emails/customer-refunded-order.php',
			'customer_invoice'          => 'emails/customer-invoice.php',
			'customer_note'             => 'emails/customer-note.php',
			'customer_new_account'      => 'emails/customer-new-account.php',
			'customer_reset_password'   => 'emails/customer-reset-password.php',
			'new_order'                 => 'emails/admin-new-order.php',
		);

		foreach ( $file_by_id as $file ) {
			$map[ $file ] = 'emails/generic.php';
		}

		return $map;
	}

	/**
	 * Use plugin email templates.
	 *
	 * @param string $template      Located template.
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 * @return string
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		unset( $template_path );

		$map  = $this->template_map();
		$file = isset( $map[ $template_name ] ) ? WC_IMAJINER_PATH . 'templates/' . $map[ $template_name ] : '';

		return ( $file && file_exists( $file ) ) ? $file : $template;
	}

	/**
	 * Replace subject.
	 *
	 * @param string         $subject Subject.
	 * @param mixed          $object  Email object.
	 * @param WC_Email|false $email   Email.
	 * @return string
	 */
	public function filter_subject( $subject, $object, $email = false ) {
		return $this->filter_field( 'subject', $subject, $object, $email );
	}

	/**
	 * Replace heading.
	 *
	 * @param string         $heading Heading.
	 * @param mixed          $object  Email object.
	 * @param WC_Email|false $email   Email.
	 * @return string
	 */
	public function filter_heading( $heading, $object, $email = false ) {
		return $this->filter_field( 'heading', $heading, $object, $email );
	}

	/**
	 * Replace additional content.
	 *
	 * @param string         $content Content.
	 * @param mixed          $object  Email object.
	 * @param WC_Email|false $email   Email.
	 * @return string
	 */
	public function filter_additional( $content, $object, $email = false ) {
		return $this->filter_field( 'additional', $content, $object, $email );
	}

	/**
	 * Replace a text field and apply placeholders.
	 *
	 * @param string         $field   Field key.
	 * @param string         $current Current value.
	 * @param mixed          $object  Object.
	 * @param WC_Email|false $email   Email.
	 * @return string
	 */
	protected function filter_field( $field, $current, $object, $email ) {
		$email_id = $this->email_id_from_filter( $email );
		if ( ! $email_id ) {
			return $current;
		}

		$template = self::get_template( $email_id );
		$value    = isset( $template[ $field ] ) ? $template[ $field ] : $current;

		return self::replace_placeholders( $value, $email, $object );
	}

	/**
	 * Detect email id from filter callback context.
	 *
	 * @param WC_Email|false $email Email.
	 * @return string
	 */
	protected function email_id_from_filter( $email ) {
		if ( $email instanceof WC_Email && ! empty( $email->id ) ) {
			return $email->id;
		}

		$filter = current_filter();
		foreach ( array_keys( self::definitions() ) as $id ) {
			if ( false !== strpos( $filter, '_' . $id ) ) {
				return $id;
			}
		}

		return '';
	}

	/**
	 * Keep WC default footer if empty; otherwise leave as-is.
	 *
	 * @param string         $text  Footer text.
	 * @param WC_Email|false $email Email.
	 * @return string
	 */
	public function filter_footer_text( $text, $email = null ) {
		unset( $email );

		if ( '' === trim( wp_strip_all_tags( (string) $text ) ) ) {
			return sprintf(
				/* translators: %s: site name */
				__( '%s — email otomatis, mohon tidak membalas.', 'wc-imajiner' ),
				wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
			);
		}

		return $text;
	}

	/**
	 * Rendered body for the generic template.
	 *
	 * @param WC_Email $email Email instance.
	 * @return string
	 */
	public static function get_rendered_body( $email ) {
		$id       = ( $email instanceof WC_Email ) ? $email->id : '';
		$template = self::get_template( $id );
		$object   = ( $email instanceof WC_Email ) ? $email->object : null;

		return self::replace_placeholders( $template['body'], $email, $object );
	}

	/**
	 * Replace WooCommerce + custom placeholders.
	 *
	 * @param string         $text   Text.
	 * @param WC_Email|false $email  Email.
	 * @param mixed          $object Object.
	 * @return string
	 */
	public static function replace_placeholders( $text, $email = false, $object = null ) {
		$text = (string) $text;

		$replacements = array(
			'{site_title}'          => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'{site_url}'            => home_url(),
			'{my_account_url}'      => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
			'{customer_first_name}' => '',
			'{customer_name}'       => '',
			'{order_number}'        => '',
			'{order_date}'          => '',
			'{order_total}'         => '',
			'{payment_url}'         => '',
			'{user_login}'          => '',
			'{set_password_url}'    => '',
			'{customer_note}'       => '',
		);

		$order = null;
		if ( $object instanceof WC_Order ) {
			$order = $object;
		} elseif ( $email instanceof WC_Email && $email->object instanceof WC_Order ) {
			$order = $email->object;
		}

		if ( $order ) {
			$replacements['{customer_first_name}'] = $order->get_billing_first_name();
			$replacements['{customer_name}']       = trim( $order->get_formatted_billing_full_name() );
			$replacements['{order_number}']        = $order->get_order_number();
			$replacements['{order_date}']          = wc_format_datetime( $order->get_date_created() );
			$replacements['{order_total}']         = wp_strip_all_tags( $order->get_formatted_order_total() );
			$replacements['{payment_url}']         = $order->get_checkout_payment_url();
			$replacements['{customer_note}']       = $order->get_customer_note();
		}

		if ( $email instanceof WC_Email ) {
			if ( ! empty( $email->user_login ) ) {
				$replacements['{user_login}'] = $email->user_login;
			}
			if ( ! empty( $email->user_id ) ) {
				$user = get_user_by( 'id', $email->user_id );
				if ( $user ) {
					$replacements['{user_login}']          = $user->user_login;
					$replacements['{customer_first_name}'] = $user->first_name ? $user->first_name : $user->display_name;
					$replacements['{customer_name}']       = $user->display_name;
				}
			}
			if ( ! empty( $email->set_password_url ) ) {
				$replacements['{set_password_url}'] = $email->set_password_url;
			}
			if ( isset( $email->customer_note ) && is_string( $email->customer_note ) && '' !== $email->customer_note ) {
				$replacements['{customer_note}'] = $email->customer_note;
			}

			if ( method_exists( $email, 'format_string' ) ) {
				$text = $email->format_string( $text );
			}
		}

		$text = str_replace( array_keys( $replacements ), array_values( $replacements ), $text );

		return $text;
	}

	/**
	 * Save email templates.
	 */
	public function handle_save() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin.', 'wc-imajiner' ) );
		}

		check_admin_referer( 'wc_imajiner_save_emails' );

		$email_id = isset( $_POST['email_id'] ) ? sanitize_key( wp_unslash( $_POST['email_id'] ) ) : '';
		if ( ! array_key_exists( $email_id, self::definitions() ) ) {
			wp_safe_redirect( WC_Imajiner_Admin::url( 'emails', array( 'wc_imajiner_notice' => 'error' ) ) );
			exit;
		}

		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$stored[ $email_id ] = array(
			'subject'    => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
			'heading'    => isset( $_POST['heading'] ) ? sanitize_text_field( wp_unslash( $_POST['heading'] ) ) : '',
			'body'       => isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : '',
			'additional' => isset( $_POST['additional'] ) ? sanitize_textarea_field( wp_unslash( $_POST['additional'] ) ) : '',
		);

		update_option( self::OPTION_KEY, $stored );

		wp_safe_redirect(
			WC_Imajiner_Admin::url(
				'emails',
				array(
					'email'               => $email_id,
					'wc_imajiner_notice' => 'emails_saved',
				)
			)
		);
		exit;
	}

	/**
	 * Reset one template to Indonesian defaults.
	 */
	public function handle_reset() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin.', 'wc-imajiner' ) );
		}

		check_admin_referer( 'wc_imajiner_reset_email' );

		$email_id = isset( $_GET['email'] ) ? sanitize_key( wp_unslash( $_GET['email'] ) ) : '';
		if ( ! array_key_exists( $email_id, self::definitions() ) ) {
			wp_safe_redirect( WC_Imajiner_Admin::url( 'emails', array( 'wc_imajiner_notice' => 'error' ) ) );
			exit;
		}

		$stored = get_option( self::OPTION_KEY, array() );
		if ( is_array( $stored ) && isset( $stored[ $email_id ] ) ) {
			unset( $stored[ $email_id ] );
			update_option( self::OPTION_KEY, $stored );
		}

		wp_safe_redirect(
			WC_Imajiner_Admin::url(
				'emails',
				array(
					'email'               => $email_id,
					'wc_imajiner_notice' => 'emails_reset',
				)
			)
		);
		exit;
	}

	/**
	 * AJAX preview of the current email template.
	 */
	public function handle_preview() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Anda tidak memiliki izin.', 'wc-imajiner' ) ), 403 );
		}

		check_ajax_referer( 'wc_imajiner_preview', 'nonce' );

		$email_id = isset( $_POST['email_id'] ) ? sanitize_key( wp_unslash( $_POST['email_id'] ) ) : '';
		if ( ! array_key_exists( $email_id, self::definitions() ) ) {
			wp_send_json_error( array( 'message' => __( 'Template email tidak ditemukan.', 'wc-imajiner' ) ) );
		}

		self::$preview_override[ $email_id ] = array(
			'subject'    => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
			'heading'    => isset( $_POST['heading'] ) ? sanitize_text_field( wp_unslash( $_POST['heading'] ) ) : '',
			'body'       => isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : '',
			'additional' => isset( $_POST['additional'] ) ? sanitize_textarea_field( wp_unslash( $_POST['additional'] ) ) : '',
		);

		try {
			$preview = $this->render_preview_html( $email_id );
		} catch ( Exception $e ) {
			self::$preview_override = array();
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		self::$preview_override = array();

		wp_send_json_success(
			array(
				'title'   => sprintf(
					/* translators: %s: email subject */
					__( 'Pratinjau: %s', 'wc-imajiner' ),
					$preview['subject']
				),
				'html'    => $preview['html'],
				'subject' => $preview['subject'],
			)
		);
	}

	/**
	 * Build HTML for one email type using WooCommerce dummy data.
	 *
	 * @param string $email_id Email id.
	 * @return array{html:string,subject:string}
	 */
	protected function render_preview_html( $email_id ) {
		if ( ! function_exists( 'WC' ) || ! WC()->mailer() ) {
			throw new Exception( __( 'WooCommerce email belum siap.', 'wc-imajiner' ) );
		}

		$class_name = $this->get_email_class( $email_id );
		if ( ! $class_name ) {
			throw new Exception( __( 'Jenis email tidak tersedia.', 'wc-imajiner' ) );
		}

		if ( class_exists( '\Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview' ) ) {
			$preview = wc_get_container()->get( \Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview::class );
			$preview->set_email_type( $class_name );

			return array(
				'html'    => $preview->render(),
				'subject' => $preview->get_subject(),
			);
		}

		$emails = WC()->mailer()->get_emails();
		$email  = isset( $emails[ $class_name ] ) ? $emails[ $class_name ] : null;
		if ( ! $email instanceof WC_Email ) {
			foreach ( $emails as $candidate ) {
				if ( $candidate instanceof WC_Email && $candidate->id === $email_id ) {
					$email = $candidate;
					break;
				}
			}
		}

		if ( ! $email instanceof WC_Email ) {
			throw new Exception( __( 'Jenis email tidak tersedia.', 'wc-imajiner' ) );
		}

		$order = $this->get_preview_order();
		if ( $order ) {
			$email->object    = $order;
			$email->recipient = $order->get_billing_email();
		}

		return array(
			'html'    => $email->style_inline( $email->get_content_html() ),
			'subject' => $email->get_subject(),
		);
	}

	/**
	 * WC email class name for an id.
	 *
	 * @param string $email_id Email id.
	 * @return string
	 */
	protected function get_email_class( $email_id ) {
		foreach ( WC()->mailer()->get_emails() as $email ) {
			if ( $email instanceof WC_Email && $email->id === $email_id ) {
				return get_class( $email );
			}
		}
		return '';
	}

	/**
	 * Latest real order for fallback preview.
	 *
	 * @return WC_Order|null
	 */
	protected function get_preview_order() {
		$orders = wc_get_orders(
			array(
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
				'return'  => 'objects',
			)
		);

		return ( ! empty( $orders[0] ) && $orders[0] instanceof WC_Order ) ? $orders[0] : null;
	}

	/**
	 * Render Email Notification tab.
	 */
	public static function render_tab() {
		$email_id    = self::current_email_id();
		$definitions = self::definitions();
		$template    = self::get_template( $email_id );
		$meta        = $definitions[ $email_id ];
		?>
		<p class="wc-imajiner-intro">
			<?php esc_html_e( 'Ubah teks email pelanggan di sini. Default berbahasa Indonesia, dengan layout sederhana dan mudah dibaca.', 'wc-imajiner' ); ?>
		</p>

		<ul class="subsubsub wc-imajiner-email-nav">
			<?php
			$i     = 0;
			$total = count( $definitions );
			foreach ( $definitions as $id => $def ) :
				++$i;
				?>
				<li>
					<a href="<?php echo esc_url( WC_Imajiner_Admin::url( 'emails', array( 'email' => $id ) ) ); ?>" class="<?php echo $id === $email_id ? 'current' : ''; ?>">
						<?php echo esc_html( $def['label'] ); ?>
					</a>
					<?php echo $i < $total ? ' |' : ''; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="clear"></div>

		<div class="wc-imajiner-card">
			<h2 class="wc-imajiner-card__title"><?php echo esc_html( $meta['label'] ); ?></h2>
			<p class="description wc-imajiner-card__intro"><?php echo esc_html( $meta['description'] ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wc-imajiner-form">
				<input type="hidden" name="action" value="wc_imajiner_save_emails">
				<input type="hidden" name="email_id" value="<?php echo esc_attr( $email_id ); ?>">
				<?php wp_nonce_field( 'wc_imajiner_save_emails' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="wc_imajiner_email_subject"><?php esc_html_e( 'Subjek', 'wc-imajiner' ); ?></label></th>
						<td>
							<input type="text" class="large-text" id="wc_imajiner_email_subject" name="subject" value="<?php echo esc_attr( $template['subject'] ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wc_imajiner_email_heading"><?php esc_html_e( 'Judul', 'wc-imajiner' ); ?></label></th>
						<td>
							<input type="text" class="large-text" id="wc_imajiner_email_heading" name="heading" value="<?php echo esc_attr( $template['heading'] ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wc_imajiner_email_body"><?php esc_html_e( 'Isi email', 'wc-imajiner' ); ?></label></th>
						<td>
							<textarea id="wc_imajiner_email_body" name="body" class="large-text" rows="10"><?php echo esc_textarea( $template['body'] ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Placeholder: {customer_first_name}, {customer_name}, {order_number}, {order_date}, {order_total}, {payment_url}, {site_title}, {user_login}, {my_account_url}, {set_password_url}, {customer_note}', 'wc-imajiner' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wc_imajiner_email_additional"><?php esc_html_e( 'Catatan tambahan', 'wc-imajiner' ); ?></label></th>
						<td>
							<textarea id="wc_imajiner_email_additional" name="additional" class="large-text" rows="4"><?php echo esc_textarea( $template['additional'] ); ?></textarea>
						</td>
					</tr>
				</table>

				<p class="submit">
					<?php submit_button( __( 'Simpan template', 'wc-imajiner' ), 'primary', 'submit', false ); ?>
					<button type="button" class="button wc-imajiner-preview-btn" data-preview="email">
						<?php esc_html_e( 'Pratinjau', 'wc-imajiner' ); ?>
					</button>
					<a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wc_imajiner_reset_email&email=' . rawurlencode( $email_id ) ), 'wc_imajiner_reset_email' ) ); ?>">
						<?php esc_html_e( 'Kembalikan bawaan', 'wc-imajiner' ); ?>
					</a>
				</p>
			</form>
		</div>
		<?php
	}
}
