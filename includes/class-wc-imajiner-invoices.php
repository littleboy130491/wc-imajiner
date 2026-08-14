<?php
/**
 * Invoice template editor for WP Overnight PDF Invoices.
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Invoice tab: require plugin, then edit a simple Indonesian invoice.
 */
class WC_Imajiner_Invoices {

	const OPTION_KEY   = 'wc_imajiner_invoice';
	const PLUGIN_FILE  = 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php';
	const PLUGIN_SLUG  = 'woocommerce-pdf-invoices-packing-slips';

	/**
	 * Temporary field values used while rendering a preview.
	 *
	 * @var array<string, string>
	 */
	public static $preview_override = array();

	/**
	 * Wire filters and save handler.
	 */
	public function hooks() {
		add_action( 'admin_post_wc_imajiner_save_invoice', array( $this, 'handle_save' ) );
		add_action( 'wp_ajax_wc_imajiner_preview_invoice', array( $this, 'handle_preview' ) );
		add_filter( 'wpo_wcpdf_template_paths', array( $this, 'register_template_path' ) );
		add_filter( 'wpo_wcpdf_shop_name_settings_text', array( $this, 'filter_shop_name' ), 20, 2 );
		add_filter( 'wpo_wcpdf_shop_address_settings_text', array( $this, 'filter_shop_address' ), 20, 2 );
		add_filter( 'wpo_wcpdf_get_shop_address', array( $this, 'filter_formatted_shop_address' ), 20, 3 );
		add_filter( 'wpo_wcpdf_shop_address', array( $this, 'filter_formatted_shop_address' ), 20, 2 );
		add_filter( 'wpo_wcpdf_shop_phone_number_settings_text', array( $this, 'filter_shop_phone' ), 20, 2 );
		add_filter( 'wpo_wcpdf_shop_email_address_settings_text', array( $this, 'filter_shop_email' ), 20, 2 );
		add_filter( 'wpo_wcpdf_footer_settings_text', array( $this, 'filter_footer' ), 20, 2 );
		add_filter( 'wpo_wcpdf_document_title', array( $this, 'filter_document_title' ), 20, 2 );
		add_filter( 'wpo_wcpdf_document_number_title', array( $this, 'filter_number_title' ), 20, 2 );
		add_filter( 'wpo_wcpdf_document_date_title', array( $this, 'filter_date_title' ), 20, 2 );
		add_filter( 'wpo_wcpdf_simple_template_default_table_headers', array( $this, 'filter_table_headers' ), 20, 2 );
		add_filter( 'wpo_wcpdf_template_custom_styles', array( $this, 'filter_custom_styles' ), 20, 2 );
		add_filter( 'wpo_wcpdf_header_logo_id', array( $this, 'filter_header_logo_id' ), 20, 2 );
		add_action( 'wpo_wcpdf_after_document_label', array( $this, 'render_intro' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_order_details', array( $this, 'render_notes' ), 10, 2 );
		add_action( 'init', array( $this, 'maybe_configure_customer_access' ), 20 );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'render_my_account_invoice_download' ), 20 );
		add_filter( 'wpo_wcpdf_myaccount_button_text', array( $this, 'filter_my_account_button_text' ), 20, 2 );
	}

	/**
	 * Default Indonesian invoice copy.
	 *
	 * @return array<string, string>
	 */
	public static function defaults() {
		return array(
			'shop_name'       => '',
			'shop_address'    => '',
			'shop_phone'      => '',
			'shop_email'      => '',
			'document_title'  => 'Faktur',
			'intro'           => 'Terima kasih telah berbelanja. Berikut rincian pesanan Anda.',
			'notes'           => "Pembayaran via transfer bank.\nPesanan hanya berlaku 24 jam. Jika belum dibayar, pesanan akan dibatalkan otomatis.",
			'footer'          => '',
		);
	}

	/**
	 * Settings merged with defaults.
	 *
	 * @return array<string, string>
	 */
	public static function all() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$settings = wp_parse_args( $stored, self::defaults() );
		if ( ! empty( self::$preview_override ) && is_array( self::$preview_override ) ) {
			$settings = wp_parse_args( self::$preview_override, $settings );
		}
		foreach ( $settings as $key => $value ) {
			$settings[ $key ] = is_string( $value ) ? $value : '';
		}

		return $settings;
	}

	/**
	 * Whether the PDF Invoices plugin is active.
	 *
	 * @return bool
	 */
	public static function is_plugin_active() {
		if ( class_exists( 'WPO_WCPDF' ) || function_exists( 'WPO_WCPDF' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( self::PLUGIN_FILE );
	}

	/**
	 * Whether the plugin is installed but inactive.
	 *
	 * @return bool
	 */
	public static function is_plugin_installed() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		return isset( $plugins[ self::PLUGIN_FILE ] );
	}

	/**
	 * Register our invoice template folder for WPO.
	 *
	 * @param array $paths Paths.
	 * @return array
	 */
	public function register_template_path( $paths ) {
		$paths['wc-imajiner'] = WC_IMAJINER_PATH . 'templates/pdf/';
		return $paths;
	}

	/**
	 * Enable invoice PDF on customer emails and My Account when WPO defaults hide it.
	 *
	 * WPO "Allow My Account invoice download" defaults to "only when already created".
	 * Empty "Attach to" means no PDF on emails. This fills those once.
	 *
	 * @param bool $force Rewrite even if previously configured.
	 */
	public function maybe_configure_customer_access( $force = false ) {
		if ( ! self::is_plugin_active() ) {
			return;
		}

		$option_key = 'wpo_wcpdf_documents_settings_invoice';
		$settings   = get_option( $option_key, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$changed = false;

		if ( empty( $settings['enabled'] ) ) {
			$settings['enabled'] = 1;
			$changed             = true;
		}

		$current_buttons = isset( $settings['my_account_buttons'] ) ? (string) $settings['my_account_buttons'] : '';
		if ( $force || '' === $current_buttons || 'available' === $current_buttons ) {
			if ( 'always' !== $current_buttons ) {
				$settings['my_account_buttons'] = 'always';
				$changed                        = true;
			}
		}

		$attach = isset( $settings['attach_to_email_ids'] ) && is_array( $settings['attach_to_email_ids'] )
			? $settings['attach_to_email_ids']
			: array();

		if ( $force || empty( array_filter( $attach ) ) ) {
			foreach ( $this->default_invoice_email_ids() as $email_id ) {
				$attach[ $email_id ] = '1';
			}
			$settings['attach_to_email_ids'] = $attach;
			$changed                         = true;
		}

		if ( $changed ) {
			update_option( $option_key, $settings );
		}
	}

	/**
	 * Customer emails that should include the invoice PDF (BACS + paid).
	 *
	 * @return string[]
	 */
	protected function default_invoice_email_ids() {
		return array(
			'customer_on_hold_order',
			'customer_processing_order',
			'customer_completed_order',
			'customer_invoice',
		);
	}

	/**
	 * Label for the My Account orders-list action.
	 *
	 * @param string $text     Button text.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_my_account_button_text( $text, $document = null ) {
		if ( is_object( $document ) && method_exists( $document, 'get_type' ) && 'invoice' !== $document->get_type() ) {
			return $text;
		}

		return __( 'Faktur PDF', 'wc-imajiner' );
	}

	/**
	 * Download button on My Account → view order.
	 *
	 * @param WC_Order $order Order.
	 */
	public function render_my_account_invoice_download( $order ) {
		if ( ! is_account_page() || ! $order instanceof WC_Order ) {
			return;
		}

		if ( ! self::is_plugin_active() || ! function_exists( 'wcpdf_get_document' ) || ! function_exists( 'WPO_WCPDF' ) ) {
			return;
		}

		$invoice = wcpdf_get_document( 'invoice', $order );
		if ( ! $invoice || ! method_exists( $invoice, 'is_allowed_in_my_account' ) || ! $invoice->is_allowed_in_my_account( 'always' ) ) {
			return;
		}

		$url = WPO_WCPDF()->endpoint->get_document_link( $order, 'invoice', array( 'my-account' => 'true' ) );
		if ( ! $url ) {
			return;
		}

		echo '<p class="wc-imajiner-invoice-download">';
		echo '<a class="button" href="' . esc_url( $url ) . '">';
		echo esc_html__( 'Unduh Faktur PDF', 'wc-imajiner' );
		echo '</a></p>';
	}

	/**
	 * @param string $text Text.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_shop_name( $text, $document = null ) {
		unset( $document );
		return self::resolved_value( 'shop_name', array( __CLASS__, 'wc_store_name' ), $text );
	}

	/**
	 * @param string $text Text.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_shop_address( $text, $document = null ) {
		unset( $document );
		return $this->resolved_shop_address( $text );
	}

	/**
	 * Override the assembled shop address string.
	 *
	 * @param string $text     Address.
	 * @param mixed  $address  Address parts or document.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_formatted_shop_address( $text, $address = null, $document = null ) {
		unset( $address, $document );
		return $this->resolved_shop_address( $text );
	}

	/**
	 * @param string $text Text.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_shop_phone( $text, $document = null ) {
		unset( $document );
		return self::resolved_value( 'shop_phone', array( __CLASS__, 'wc_store_phone' ), $text );
	}

	/**
	 * @param string $text Text.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_shop_email( $text, $document = null ) {
		unset( $document );
		return self::resolved_value( 'shop_email', array( __CLASS__, 'wc_store_email' ), $text );
	}

	/**
	 * Use the WordPress site logo when PDF Invoices has no header logo.
	 *
	 * @param int|string $logo_id  Attachment ID.
	 * @param mixed      $document Document.
	 * @return int
	 */
	public function filter_header_logo_id( $logo_id, $document = null ) {
		unset( $document );

		$logo_id = absint( $logo_id );
		if ( $logo_id > 0 ) {
			return $logo_id;
		}

		return self::site_logo_id();
	}

	/**
	 * WordPress custom logo attachment ID.
	 *
	 * @return int
	 */
	public static function site_logo_id() {
		$logo_id = absint( get_theme_mod( 'custom_logo' ) );
		if ( $logo_id > 0 ) {
			return $logo_id;
		}

		if ( function_exists( 'get_option' ) ) {
			$custom_logo = absint( get_option( 'site_logo', 0 ) );
			if ( $custom_logo > 0 ) {
				return $custom_logo;
			}
		}

		return 0;
	}

	/**
	 * Whether the invoice should print a logo.
	 *
	 * @param mixed $document WPO document.
	 * @return bool
	 */
	public static function document_has_logo( $document = null ) {
		if ( is_object( $document ) && method_exists( $document, 'has_header_logo' ) && $document->has_header_logo() ) {
			return true;
		}

		return self::site_logo_id() > 0;
	}

	/**
	 * @param string $text     Text.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_footer( $text, $document = null ) {
		if ( ! $this->is_invoice_document( $document ) ) {
			return $text;
		}

		$value = trim( self::all()['footer'] );
		if ( '' === $value || self::is_legacy_footer( $value ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * @param string $title Title.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_document_title( $title, $document = null ) {
		if ( ! $this->is_invoice_document( $document ) ) {
			return $title;
		}

		$value = self::all()['document_title'];
		return '' !== trim( $value ) ? $value : $title;
	}

	/**
	 * @param string $title Title.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_number_title( $title, $document = null ) {
		if ( ! $this->is_invoice_document( $document ) ) {
			return $title;
		}
		return __( 'No. Faktur', 'wc-imajiner' );
	}

	/**
	 * @param string $title Title.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_date_title( $title, $document = null ) {
		if ( ! $this->is_invoice_document( $document ) ) {
			return $title;
		}
		return __( 'Tanggal', 'wc-imajiner' );
	}

	/**
	 * Indonesian table headers.
	 *
	 * @param array $headers Headers.
	 * @param mixed $document Document.
	 * @return array
	 */
	public function filter_table_headers( $headers, $document = null ) {
		unset( $document );

		if ( isset( $headers['product'] ) ) {
			$headers['product'] = __( 'Produk', 'wc-imajiner' );
		}
		if ( isset( $headers['quantity'] ) ) {
			$headers['quantity'] = __( 'Jumlah', 'wc-imajiner' );
		}
		if ( isset( $headers['price'] ) ) {
			$headers['price'] = __( 'Harga', 'wc-imajiner' );
		}

		return $headers;
	}

	/**
	 * Minimal invoice CSS extras.
	 *
	 * @param string $css CSS.
	 * @param mixed  $document Document.
	 * @return string
	 */
	public function filter_custom_styles( $css, $document = null ) {
		unset( $document );

		$css .= '
			table.head td.shop-info {
				width: 48%;
				text-align: right;
			}
			table.head .shop-name h3 {
				margin: 0 0 2mm;
				font-size: 11pt;
			}
			table.head .shop-address,
			table.head .shop-phone-number,
			table.head .shop-email-address {
				font-size: 8.5pt;
				line-height: 1.45;
				color: #333;
			}
			table.head .shop-address {
				white-space: pre-line;
				margin-bottom: 1.5mm;
			}
			.wc-imajiner-invoice-intro {
				margin: 0 0 8mm;
				font-size: 9pt;
				line-height: 1.45;
				color: #222;
			}
			.wc-imajiner-invoice-notes {
				margin: 10mm 0 0;
				padding: 0;
				border: 0;
				background: none;
				font-size: 8.5pt;
				line-height: 1.5;
				color: #444;
			}
			.wc-imajiner-invoice-notes strong {
				display: block;
				margin-bottom: 2mm;
				font-size: 8pt;
				letter-spacing: 0.04em;
				text-transform: uppercase;
				color: #666;
			}
			.wc-imajiner-invoice-notes p {
				margin: 0 0 1.5mm;
			}
		';

		return $css;
	}

	/**
	 * Intro under the invoice title.
	 *
	 * @param string $type  Document type.
	 * @param mixed  $order Order.
	 */
	public function render_intro( $type, $order = null ) {
		unset( $order );

		if ( 'invoice' !== $type ) {
			return;
		}

		$intro = trim( self::all()['intro'] );
		if ( '' === $intro ) {
			return;
		}

		echo '<div class="wc-imajiner-invoice-intro">' . wp_kses_post( wpautop( $intro ) ) . '</div>';
	}

	/**
	 * Extra payment / validity notes.
	 *
	 * @param string $type  Document type.
	 * @param mixed  $order Order.
	 */
	public function render_notes( $type, $order = null ) {
		unset( $order );

		if ( 'invoice' !== $type ) {
			return;
		}

		$notes = trim( self::all()['notes'] );
		if ( '' === $notes ) {
			return;
		}

		echo '<div class="wc-imajiner-invoice-notes">';
		echo '<strong>' . esc_html__( 'Catatan', 'wc-imajiner' ) . '</strong>';
		echo wp_kses_post( wpautop( $notes ) );
		echo '</div>';
	}

	/**
	 * @param mixed $document Document.
	 * @return bool
	 */
	protected function is_invoice_document( $document ) {
		return is_object( $document ) && method_exists( $document, 'get_type' ) && 'invoice' === $document->get_type();
	}

	/**
	 * Shop address from the invoice field, or WooCommerce store settings.
	 *
	 * @param string $fallback Fallback text.
	 * @return string
	 */
	protected function resolved_shop_address( $fallback = '' ) {
		$fallback = trim( wp_strip_all_tags( preg_replace( '/<br\s*\/?>/i', "\n", (string) $fallback ) ) );
		return self::resolved_value( 'shop_address', array( __CLASS__, 'wc_store_address' ), $fallback );
	}

	/**
	 * Invoice field, then WooCommerce, then leftover text.
	 *
	 * @param string   $key       Setting key.
	 * @param callable $wc_source WooCommerce fallback.
	 * @param string   $fallback  Last resort.
	 * @return string
	 */
	protected static function resolved_value( $key, $wc_source, $fallback = '' ) {
		$value = trim( (string) self::all()[ $key ] );
		if ( '' !== $value ) {
			return $value;
		}

		$from_wc = trim( (string) call_user_func( $wc_source ) );
		if ( '' !== $from_wc ) {
			return $from_wc;
		}

		return trim( (string) $fallback );
	}

	/**
	 * Store name from WooCommerce / site title.
	 *
	 * @return string
	 */
	public static function wc_store_name() {
		$from_email = trim( (string) get_option( 'woocommerce_email_from_name', '' ) );
		if ( '' !== $from_email ) {
			return wp_specialchars_decode( $from_email, ENT_QUOTES );
		}

		return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	}

	/**
	 * Store email from WooCommerce email settings.
	 *
	 * @return string
	 */
	public static function wc_store_email() {
		$email = sanitize_email( (string) get_option( 'woocommerce_email_from_address', '' ) );
		if ( $email ) {
			return $email;
		}

		return sanitize_email( (string) get_bloginfo( 'admin_email' ) );
	}

	/**
	 * Store phone if WooCommerce has one.
	 *
	 * @return string
	 */
	public static function wc_store_phone() {
		$phone = trim( (string) get_option( 'woocommerce_pos_store_phone', '' ) );
		if ( '' !== $phone ) {
			return $phone;
		}

		return trim( (string) get_option( 'woocommerce_store_phone', '' ) );
	}

	/**
	 * WooCommerce store address from Settings → General.
	 *
	 * @return string
	 */
	public static function wc_store_address() {
		$parts = array(
			'address_1' => '',
			'address_2' => '',
			'city'      => '',
			'state'     => '',
			'postcode'  => '',
			'country'   => '',
		);

		if ( function_exists( 'WC' ) && WC() && isset( WC()->countries ) && is_object( WC()->countries ) ) {
			$countries = WC()->countries;
			$parts     = array(
				'address_1' => (string) $countries->get_base_address(),
				'address_2' => (string) $countries->get_base_address_2(),
				'city'      => (string) $countries->get_base_city(),
				'state'     => (string) $countries->get_base_state(),
				'postcode'  => (string) $countries->get_base_postcode(),
				'country'   => (string) $countries->get_base_country(),
			);

			$formatted = $countries->get_formatted_address( $parts, "\n" );
			$formatted = preg_replace( '/<br\s*\/?>/i', "\n", (string) $formatted );
			$formatted = trim( wp_strip_all_tags( html_entity_decode( $formatted, ENT_QUOTES, 'UTF-8' ) ) );
			if ( '' !== $formatted ) {
				return $formatted;
			}
		}

		$location = function_exists( 'wc_get_base_location' ) ? wc_get_base_location() : array();
		$parts    = array(
			'address_1' => (string) get_option( 'woocommerce_store_address', '' ),
			'address_2' => (string) get_option( 'woocommerce_store_address_2', '' ),
			'city'      => (string) get_option( 'woocommerce_store_city', '' ),
			'state'     => isset( $location['state'] ) ? (string) $location['state'] : '',
			'postcode'  => (string) get_option( 'woocommerce_store_postcode', '' ),
			'country'   => isset( $location['country'] ) ? (string) $location['country'] : '',
		);

		$lines = array_filter( array_map( 'trim', $parts ) );
		return implode( "\n", $lines );
	}

	/**
	 * Old auto-generated footer copy that should no longer print.
	 *
	 * @param string $value Footer text.
	 * @return bool
	 */
	protected static function is_legacy_footer( $value ) {
		return 'Dokumen ini dibuat otomatis dan sah tanpa tanda tangan.' === trim( $value );
	}

	/**
	 * Save invoice template fields.
	 */
	public function handle_save() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin.', 'wc-imajiner' ) );
		}

		check_admin_referer( 'wc_imajiner_save_invoice' );

		$clean = self::defaults();
		foreach ( array_keys( $clean ) as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				$clean[ $key ] = '';
				continue;
			}

			$raw = wp_unslash( $_POST[ $key ] );
			$clean[ $key ] = in_array( $key, array( 'shop_address', 'intro', 'notes' ), true )
				? sanitize_textarea_field( $raw )
				: sanitize_text_field( $raw );
		}
		$clean['footer'] = '';

		update_option( self::OPTION_KEY, $clean );
		$this->maybe_select_template();
		$this->maybe_configure_customer_access( true );

		wp_safe_redirect( WC_Imajiner_Admin::url( 'invoice', array( 'wc_imajiner_notice' => 'invoice_saved' ) ) );
		exit;
	}

	/**
	 * AJAX preview of the invoice template.
	 */
	public function handle_preview() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Anda tidak memiliki izin.', 'wc-imajiner' ) ), 403 );
		}

		check_ajax_referer( 'wc_imajiner_preview', 'nonce' );

		if ( ! self::is_plugin_active() || ! function_exists( 'wcpdf_get_document' ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin PDF Invoices belum aktif.', 'wc-imajiner' ) ) );
		}

		$clean = self::defaults();
		foreach ( array_keys( $clean ) as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw           = wp_unslash( $_POST[ $key ] );
			$clean[ $key ] = in_array( $key, array( 'shop_address', 'intro', 'notes' ), true )
				? sanitize_textarea_field( $raw )
				: sanitize_text_field( $raw );
		}
		$clean['footer']        = '';
		self::$preview_override = $clean;

		$order = $this->get_preview_order();
		if ( ! $order ) {
			self::$preview_override = array();
			wp_send_json_error(
				array(
					'message' => __( 'Belum ada pesanan untuk pratinjau. Buat pesanan sampel di tab Seeder, atau selesaikan satu checkout.', 'wc-imajiner' ),
				)
			);
		}

		try {
			$document = wcpdf_get_document( 'invoice', $order, false );
			if ( ! $document ) {
				throw new Exception( __( 'Dokumen faktur tidak bisa dibuat untuk pesanan ini.', 'wc-imajiner' ) );
			}

			if ( method_exists( $document, 'exists' ) && ! $document->exists() && method_exists( $document, 'set_date' ) ) {
				$document->set_date( current_time( 'timestamp', true ) );
			}

			$html = $document->get_html();
		} catch ( Exception $e ) {
			self::$preview_override = array();
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		self::$preview_override = array();

		$title = isset( $clean['document_title'] ) && '' !== $clean['document_title']
			? $clean['document_title']
			: __( 'Faktur', 'wc-imajiner' );

		wp_send_json_success(
			array(
				'title' => sprintf(
					/* translators: 1: document title, 2: order number */
					__( 'Pratinjau %1$s — pesanan #%2$s', 'wc-imajiner' ),
					$title,
					$order->get_order_number()
				),
				'html'  => $html,
			)
		);
	}

	/**
	 * Latest order for invoice preview.
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
				'type'    => 'shop_order',
			)
		);

		return ( ! empty( $orders[0] ) && $orders[0] instanceof WC_Order ) ? $orders[0] : null;
	}

	/**
	 * Point WPO at our Simple-compatible template if possible.
	 */
	protected function maybe_select_template() {
		if ( ! self::is_plugin_active() ) {
			return;
		}

		$general = get_option( 'wpo_wcpdf_settings_general', array() );
		if ( ! is_array( $general ) ) {
			$general = array();
		}

		$relative = str_replace( '\\', '/', str_replace( ABSPATH, '', WC_IMAJINER_PATH . 'templates/pdf/WC-Imajiner' ) );
		$general['template_path'] = $relative;
		update_option( 'wpo_wcpdf_settings_general', $general );
	}

	/**
	 * Render Invoice tab.
	 */
	public static function render_tab() {
		$active     = self::is_plugin_active();
		$installed  = self::is_plugin_installed();
		$plugin_url = 'https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/';
		$install    = wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=' . self::PLUGIN_SLUG ),
			'install-plugin_' . self::PLUGIN_SLUG
		);
		$activate   = wp_nonce_url(
			self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( self::PLUGIN_FILE ) ),
			'activate-plugin_' . self::PLUGIN_FILE
		);

		if ( ! $active ) {
			?>
			<div class="wc-imajiner-card wc-imajiner-card--warning">
				<h2 class="wc-imajiner-card__title"><?php esc_html_e( 'Plugin faktur belum aktif', 'wc-imajiner' ); ?></h2>
				<p>
					<?php esc_html_e( 'Tab Faktur membutuhkan plugin PDF Invoices & Packing Slips for WooCommerce by WP Overnight.', 'wc-imajiner' ); ?>
				</p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( $plugin_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Lihat plugin di WordPress.org', 'wc-imajiner' ); ?>
					</a>
					<?php if ( $installed ) : ?>
						<a class="button" href="<?php echo esc_url( $activate ); ?>"><?php esc_html_e( 'Aktifkan plugin', 'wc-imajiner' ); ?></a>
					<?php elseif ( current_user_can( 'install_plugins' ) ) : ?>
						<a class="button" href="<?php echo esc_url( $install ); ?>"><?php esc_html_e( 'Pasang plugin', 'wc-imajiner' ); ?></a>
					<?php endif; ?>
				</p>
			</div>
			<?php
			return;
		}

		$settings = self::all();
		?>
		<p class="wc-imajiner-intro">
			<?php esc_html_e( 'Ubah teks faktur PDF di sini. Default berbahasa Indonesia, dengan layout sederhana dan mudah dibaca.', 'wc-imajiner' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Faktur PDF dilampirkan ke email pelanggan (menunggu pembayaran, diproses, selesai, dan invoice manual), dan bisa diunduh dari Akun Saya → Pesanan.', 'wc-imajiner' ); ?>
			<?php
			$wpo_url = admin_url( 'admin.php?page=wpo_wcpdf_options_page&tab=documents&section=invoice' );
			?>
			<a href="<?php echo esc_url( $wpo_url ); ?>">
				<?php esc_html_e( 'Pengaturan lampiran WP Overnight', 'wc-imajiner' ); ?>
			</a>
		</p>

		<div class="wc-imajiner-card">
			<h2 class="wc-imajiner-card__title"><?php esc_html_e( 'Template faktur', 'wc-imajiner' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wc-imajiner-form">
				<input type="hidden" name="action" value="wc_imajiner_save_invoice">
				<?php wp_nonce_field( 'wc_imajiner_save_invoice' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="wc_imajiner_inv_shop_name"><?php esc_html_e( 'Nama toko', 'wc-imajiner' ); ?></label></th>
						<td>
							<input type="text" class="large-text" id="wc_imajiner_inv_shop_name" name="shop_name" value="<?php echo esc_attr( $settings['shop_name'] ); ?>" placeholder="<?php echo esc_attr( self::wc_store_name() ); ?>">
							<p class="description"><?php esc_html_e( 'Kosongkan untuk memakai nama toko dari WooCommerce / judul situs.', 'wc-imajiner' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wc_imajiner_inv_shop_address"><?php esc_html_e( 'Alamat toko', 'wc-imajiner' ); ?></label></th>
						<td>
							<textarea class="large-text" id="wc_imajiner_inv_shop_address" name="shop_address" rows="3" placeholder="<?php echo esc_attr( self::wc_store_address() ); ?>"><?php echo esc_textarea( $settings['shop_address'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Kosongkan untuk memakai alamat toko dari WooCommerce → Settings → General.', 'wc-imajiner' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wc_imajiner_inv_shop_phone"><?php esc_html_e( 'Telepon', 'wc-imajiner' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="wc_imajiner_inv_shop_phone" name="shop_phone" value="<?php echo esc_attr( $settings['shop_phone'] ); ?>" placeholder="<?php echo esc_attr( self::wc_store_phone() ); ?>">
							<p class="description"><?php esc_html_e( 'Kosongkan untuk memakai nomor telepon WooCommerce jika tersedia.', 'wc-imajiner' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wc_imajiner_inv_shop_email"><?php esc_html_e( 'Email toko', 'wc-imajiner' ); ?></label></th>
						<td>
							<input type="email" class="regular-text" id="wc_imajiner_inv_shop_email" name="shop_email" value="<?php echo esc_attr( $settings['shop_email'] ); ?>" placeholder="<?php echo esc_attr( self::wc_store_email() ); ?>">
							<p class="description"><?php esc_html_e( 'Kosongkan untuk memakai email pengirim WooCommerce → Settings → Emails.', 'wc-imajiner' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wc_imajiner_inv_title"><?php esc_html_e( 'Judul dokumen', 'wc-imajiner' ); ?></label></th>
						<td><input type="text" class="regular-text" id="wc_imajiner_inv_title" name="document_title" value="<?php echo esc_attr( $settings['document_title'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="wc_imajiner_inv_intro"><?php esc_html_e( 'Teks pembuka', 'wc-imajiner' ); ?></label></th>
						<td><textarea class="large-text" id="wc_imajiner_inv_intro" name="intro" rows="3"><?php echo esc_textarea( $settings['intro'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="wc_imajiner_inv_notes"><?php esc_html_e( 'Catatan / instruksi bayar', 'wc-imajiner' ); ?></label></th>
						<td><textarea class="large-text" id="wc_imajiner_inv_notes" name="notes" rows="5"><?php echo esc_textarea( $settings['notes'] ); ?></textarea></td>
					</tr>
				</table>

				<p class="submit">
					<?php submit_button( __( 'Simpan template faktur', 'wc-imajiner' ), 'primary', 'submit', false ); ?>
					<button type="button" class="button wc-imajiner-preview-btn" data-preview="invoice">
						<?php esc_html_e( 'Pratinjau', 'wc-imajiner' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
