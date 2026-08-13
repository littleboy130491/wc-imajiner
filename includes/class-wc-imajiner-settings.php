<?php
/**
 * Settings storage and admin page.
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin options and the WooCommerce settings screen.
 */
class WC_Imajiner_Settings {

	const OPTION_KEY = 'wc_imajiner_settings';

	/**
	 * Default settings — all simplifications enabled.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'limit_product_types'         => 'yes',
			'disable_digital_products'    => 'yes',
			'disable_brands'              => 'yes',
			'disable_reviews'             => 'yes',
			'disable_coupons'             => 'yes',
			'disable_reports'             => 'yes',
			'disable_payments_menu'       => 'yes',
			'disable_marketing_menu'      => 'yes',
			'disable_extensions_menu'     => 'yes',
			'disable_status_menu'         => 'yes',
			'disable_home_menu'           => 'yes',
			'disable_pos_settings'         => 'yes',
			'disable_integration_settings' => 'yes',
			'disable_linked_products_tab' => 'yes',
			'disable_advanced_tab'        => 'yes',
			'disable_marketplace_tab'     => 'yes',
			'require_login_checkout'      => 'yes',
			'default_bacs_payment'        => 'yes',
			'enabled_order_statuses'      => self::default_enabled_order_statuses(),
		);
	}

	/**
	 * Default visible order statuses (all core statuses).
	 *
	 * @return string[]
	 */
	public static function default_enabled_order_statuses() {
		return array(
			'wc-pending',
			'wc-processing',
			'wc-on-hold',
			'wc-completed',
			'wc-cancelled',
			'wc-refunded',
			'wc-failed',
		);
	}

	/**
	 * Yes/no toggle field definitions.
	 *
	 * @return array<string, array{label:string,description:string}>
	 */
	public static function fields() {
		return array(
			'limit_product_types'         => array(
				'label'       => __( 'Hanya produk Simple & Variable', 'wc-imajiner' ),
				'description' => __( 'Sembunyikan tipe produk lain (Grouped, External/Affiliate, dan tipe kustom).', 'wc-imajiner' ),
			),
			'disable_digital_products'    => array(
				'label'       => __( 'Nonaktifkan produk digital', 'wc-imajiner' ),
				'description' => __( 'Sembunyikan opsi Virtual & Downloadable di editor produk, serta tab Downloads di My Account.', 'wc-imajiner' ),
			),
			'disable_brands'              => array(
				'label'       => __( 'Nonaktifkan Brands', 'wc-imajiner' ),
				'description' => __( 'Sembunyikan taksonomi Brand di admin, editor produk, dan tampilan toko.', 'wc-imajiner' ),
			),
			'disable_reviews'             => array(
				'label'       => __( 'Nonaktifkan Reviews', 'wc-imajiner' ),
				'description' => __( 'Matikan ulasan produk di toko, tab produk, dan dukungan komentar pada produk.', 'wc-imajiner' ),
			),
			'disable_coupons'             => array(
				'label'       => __( 'Nonaktifkan Coupons', 'wc-imajiner' ),
				'description' => __( 'Matikan kupon di toko, checkout, dan menu admin Coupons.', 'wc-imajiner' ),
			),
			'disable_reports'             => array(
				'label'       => __( 'Nonaktifkan Reports & Analytics', 'wc-imajiner' ),
				'description' => __( 'Sembunyikan menu Reports klasik dan fitur Analytics WooCommerce.', 'wc-imajiner' ),
			),
			'disable_payments_menu'       => array(
				'label'       => __( 'Sembunyikan menu Payments', 'wc-imajiner' ),
				'description' => __( 'Hapus menu utama Payments di admin. Gateway tetap bisa dikelola lewat WooCommerce → Settings → Payments.', 'wc-imajiner' ),
			),
			'disable_marketing_menu'      => array(
				'label'       => __( 'Sembunyikan menu Marketing', 'wc-imajiner' ),
				'description' => __( 'Hapus menu utama Marketing di admin beserta submenu-nya.', 'wc-imajiner' ),
			),
			'disable_extensions_menu'     => array(
				'label'       => __( 'Sembunyikan menu Extensions', 'wc-imajiner' ),
				'description' => __( 'Hapus menu WooCommerce → Extensions (marketplace plugin).', 'wc-imajiner' ),
			),
			'disable_status_menu'         => array(
				'label'       => __( 'Sembunyikan menu Status', 'wc-imajiner' ),
				'description' => __( 'Hapus menu WooCommerce → Status (laporan sistem, log, dan tools).', 'wc-imajiner' ),
			),
			'disable_home_menu'           => array(
				'label'       => __( 'Sembunyikan menu Home', 'wc-imajiner' ),
				'description' => __( 'Hapus menu WooCommerce → Home (dashboard WooCommerce).', 'wc-imajiner' ),
			),
			'disable_pos_settings'         => array(
				'label'       => __( 'Sembunyikan Settings → Point of Sale', 'wc-imajiner' ),
				'description' => __( 'Hapus tab WooCommerce → Settings → Point of Sale.', 'wc-imajiner' ),
			),
			'disable_integration_settings' => array(
				'label'       => __( 'Sembunyikan Settings → Integration', 'wc-imajiner' ),
				'description' => __( 'Hapus tab WooCommerce → Settings → Integration.', 'wc-imajiner' ),
			),
			'disable_linked_products_tab' => array(
				'label'       => __( 'Sembunyikan tab Linked Products', 'wc-imajiner' ),
				'description' => __( 'Hapus tab Linked Products dari panel Product data.', 'wc-imajiner' ),
			),
			'disable_advanced_tab'        => array(
				'label'       => __( 'Sembunyikan tab Advanced', 'wc-imajiner' ),
				'description' => __( 'Hapus tab Advanced dari panel Product data.', 'wc-imajiner' ),
			),
			'disable_marketplace_tab'     => array(
				'label'       => __( 'Sembunyikan tab “Get more options”', 'wc-imajiner' ),
				'description' => __( 'Hapus tab marketplace suggestions dari panel Product data.', 'wc-imajiner' ),
			),
			'require_login_checkout'      => array(
				'label'       => __( 'Wajib login sebelum checkout', 'wc-imajiner' ),
				'description' => __( 'Tamu tidak bisa checkout. Pembeli harus masuk atau membuat akun terlebih dahulu.', 'wc-imajiner' ),
			),
			'default_bacs_payment'        => array(
				'label'       => __( 'Hanya Transfer Bank (BACS)', 'wc-imajiner' ),
				'description' => __( 'Aktifkan transfer bank sebagai satu-satunya metode pembayaran di toko.', 'wc-imajiner' ),
			),
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$settings = wp_parse_args( $stored, self::defaults() );

		if ( empty( $settings['enabled_order_statuses'] ) || ! is_array( $settings['enabled_order_statuses'] ) ) {
			$settings['enabled_order_statuses'] = self::default_enabled_order_statuses();
		}

		return $settings;
	}

	/**
	 * Whether a yes/no toggle is enabled.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function is_enabled( $key ) {
		$settings = self::all();
		return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
	}

	/**
	 * Enabled order status slugs (with wc- prefix).
	 *
	 * @return string[]
	 */
	public static function get_enabled_order_statuses() {
		$settings = self::all();
		$enabled  = isset( $settings['enabled_order_statuses'] ) ? $settings['enabled_order_statuses'] : array();

		if ( ! is_array( $enabled ) || empty( $enabled ) ) {
			return self::default_enabled_order_statuses();
		}

		return array_values( array_unique( array_map( 'strval', $enabled ) ) );
	}

	/**
	 * All known order statuses for the settings UI (unfiltered by our hide logic).
	 *
	 * @return array<string, string> slug => label
	 */
	public static function get_all_order_statuses() {
		$fallback = array(
			'wc-pending'    => __( 'Pending payment', 'woocommerce' ),
			'wc-processing' => __( 'Processing', 'woocommerce' ),
			'wc-on-hold'    => __( 'On hold', 'woocommerce' ),
			'wc-completed'  => __( 'Completed', 'woocommerce' ),
			'wc-cancelled'  => __( 'Cancelled', 'woocommerce' ),
			'wc-refunded'   => __( 'Refunded', 'woocommerce' ),
			'wc-failed'     => __( 'Failed', 'woocommerce' ),
		);

		if ( ! function_exists( 'wc_get_order_statuses' ) ) {
			return $fallback;
		}

		$GLOBALS['wc_imajiner_bypass_status_filter'] = true;
		$statuses = wc_get_order_statuses();
		unset( $GLOBALS['wc_imajiner_bypass_status_filter'] );

		return ! empty( $statuses ) ? $statuses : $fallback;
	}

	/**
	 * Sanitize settings on save.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		$clean   = self::defaults();
		$toggles = array_keys( self::fields() );

		foreach ( $toggles as $key ) {
			$clean[ $key ] = ( is_array( $input ) && ! empty( $input[ $key ] ) ) ? 'yes' : 'no';
		}

		$allowed  = array_keys( self::get_all_order_statuses() );
		$selected = array();

		if ( is_array( $input ) && ! empty( $input['enabled_order_statuses'] ) && is_array( $input['enabled_order_statuses'] ) ) {
			foreach ( $input['enabled_order_statuses'] as $status ) {
				$status = sanitize_text_field( wp_unslash( $status ) );
				if ( in_array( $status, $allowed, true ) ) {
					$selected[] = $status;
				}
			}
		}

		$clean['enabled_order_statuses'] = ! empty( $selected ) ? array_values( array_unique( $selected ) ) : self::default_enabled_order_statuses();

		if ( 'yes' === $clean['default_bacs_payment'] ) {
			self::ensure_bacs_enabled();
		}

		return $clean;
	}

	/**
	 * Enable the BACS gateway if it is currently off.
	 */
	public static function ensure_bacs_enabled() {
		$bacs = get_option( 'woocommerce_bacs_settings', array() );
		if ( ! is_array( $bacs ) ) {
			$bacs = array();
		}
		if ( empty( $bacs['enabled'] ) || 'yes' !== $bacs['enabled'] ) {
			$bacs['enabled'] = 'yes';
			update_option( 'woocommerce_bacs_settings', $bacs );
		}
	}

	/**
	 * Register Settings API option.
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
	}

	/**
	 * Register option with Settings API.
	 */
	public function register_setting() {
		register_setting(
			'wc_imajiner_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Render Simplify tab.
	 */
	public static function render_tab() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			return;
		}

		$settings         = self::all();
		$fields           = self::fields();
		$all_statuses     = self::get_all_order_statuses();
		$enabled_statuses = self::get_enabled_order_statuses();
		?>
			<p class="wc-imajiner-intro">
				<?php esc_html_e( 'Sederhanakan pengalaman WooCommerce. Aktifkan atau nonaktifkan setiap opsi sesuai kebutuhan toko Anda.', 'wc-imajiner' ); ?>
			</p>

			<form method="post" action="options.php" class="wc-imajiner-form">
				<?php settings_fields( 'wc_imajiner_settings_group' ); ?>

				<div class="wc-imajiner-card">
					<h2 class="wc-imajiner-card__title"><?php esc_html_e( 'Fitur toko', 'wc-imajiner' ); ?></h2>
					<table class="form-table" role="presentation">
						<tbody>
							<?php foreach ( $fields as $key => $field ) : ?>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( 'wc_imajiner_' . $key ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
										</label>
									</th>
									<td>
										<label class="wc-imajiner-toggle">
											<input
												type="checkbox"
												id="<?php echo esc_attr( 'wc_imajiner_' . $key ); ?>"
												name="<?php echo esc_attr( self::OPTION_KEY . '[' . $key . ']' ); ?>"
												value="yes"
												<?php checked( $settings[ $key ], 'yes' ); ?>
											>
											<span class="wc-imajiner-toggle__ui" aria-hidden="true"></span>
											<span class="screen-reader-text"><?php echo esc_html( $field['label'] ); ?></span>
										</label>
										<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="wc-imajiner-card wc-imajiner-card--statuses">
					<h2 class="wc-imajiner-card__title"><?php esc_html_e( 'Status pesanan yang digunakan', 'wc-imajiner' ); ?></h2>
					<p class="description wc-imajiner-card__intro">
						<?php esc_html_e( 'Centang status yang ingin ditampilkan di dashboard admin (filter daftar pesanan & dropdown ubah status). Status yang tidak dicentang akan disembunyikan.', 'wc-imajiner' ); ?>
					</p>
					<ul class="wc-imajiner-status-list">
						<?php foreach ( $all_statuses as $slug => $label ) : ?>
							<li>
								<label>
									<input
										type="checkbox"
										name="<?php echo esc_attr( self::OPTION_KEY . '[enabled_order_statuses][]' ); ?>"
										value="<?php echo esc_attr( $slug ); ?>"
										<?php checked( in_array( $slug, $enabled_statuses, true ) ); ?>
									>
									<span class="wc-imajiner-status-list__label"><?php echo esc_html( $label ); ?></span>
									<code class="wc-imajiner-status-list__slug"><?php echo esc_html( $slug ); ?></code>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<?php submit_button( __( 'Simpan pengaturan', 'wc-imajiner' ) ); ?>
			</form>
		<?php
	}
}
