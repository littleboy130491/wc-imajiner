<?php
/**
 * Admin dashboard with tabbed navigation.
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders WooCommerce → Imajiner Extra Settings as a tabbed admin screen.
 */
class WC_Imajiner_Admin {

	const PAGE_SLUG   = 'wc-imajiner';
	const CAPABILITY = 'manage_options';

	/**
	 * Whether the current user may open this screen.
	 *
	 * @return bool
	 */
	public static function user_can_manage() {
		return current_user_can( self::CAPABILITY );
	}

	/**
	 * Register menu and assets.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 60 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WC_IMAJINER_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Available admin tabs.
	 *
	 * @return array<string, string> slug => label
	 */
	public static function tabs() {
		return array(
			'simplify' => __( 'Simplify', 'wc-imajiner' ),
			'emails'   => __( 'Notifikasi Email', 'wc-imajiner' ),
			'invoice'  => __( 'Faktur', 'wc-imajiner' ),
			'cron'     => __( 'Cronjob', 'wc-imajiner' ),
			'seeder'   => __( 'Seeder', 'wc-imajiner' ),
		);
	}

	/**
	 * Current tab slug.
	 *
	 * @return string
	 */
	public static function current_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'simplify';
		return array_key_exists( $tab, self::tabs() ) ? $tab : 'simplify';
	}

	/**
	 * Admin page URL.
	 *
	 * @param string               $tab     Tab slug.
	 * @param array<string,string> $extra   Extra query args.
	 * @return string
	 */
	public static function url( $tab = 'simplify', $extra = array() ) {
		$args = array_merge(
			array(
				'page' => self::PAGE_SLUG,
				'tab'  => $tab,
			),
			$extra
		);

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Add WooCommerce submenu.
	 */
	public function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Imajiner Extra Settings', 'wc-imajiner' ),
			__( 'Imajiner Extra Settings', 'wc-imajiner' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Settings page assets.
	 *
	 * @param string $hook Current admin hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wc-imajiner-admin',
			WC_IMAJINER_URL . 'assets/admin.css',
			array(),
			WC_IMAJINER_VERSION
		);

		wp_enqueue_script(
			'wc-imajiner-admin',
			WC_IMAJINER_URL . 'assets/admin.js',
			array( 'jquery' ),
			WC_IMAJINER_VERSION,
			true
		);

		wp_localize_script(
			'wc-imajiner-admin',
			'wcImajinerAdmin',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'wc_imajiner_preview' ),
				'confirmSeeder'  => __( 'Buat data sampel sekarang? Produk dan pesanan demo akan ditambahkan ke toko.', 'wc-imajiner' ),
				'confirmDelete'  => __( 'Hapus semua produk dan pesanan sampel yang dibuat seeder?', 'wc-imajiner' ),
				'confirmCron'    => __( 'Jalankan pembatalan pesanan kedaluwarsa sekarang?', 'wc-imajiner' ),
				'previewLoading' => __( 'Memuat pratinjau…', 'wc-imajiner' ),
				'previewError'   => __( 'Gagal memuat pratinjau.', 'wc-imajiner' ),
				'previewClose'   => __( 'Tutup', 'wc-imajiner' ),
			)
		);
	}

	/**
	 * Flash notices after custom POST actions.
	 */
	public function maybe_notice() {
		if ( ! $this->is_plugin_screen() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code = isset( $_GET['wc_imajiner_notice'] ) ? sanitize_key( wp_unslash( $_GET['wc_imajiner_notice'] ) ) : '';
		if ( ! $code ) {
			return;
		}

		$messages = array(
			'emails_saved'     => array( 'success', __( 'Template email disimpan.', 'wc-imajiner' ) ),
			'emails_reset'     => array( 'success', __( 'Template email dikembalikan ke bawaan.', 'wc-imajiner' ) ),
			'invoice_saved'    => array( 'success', __( 'Template faktur disimpan.', 'wc-imajiner' ) ),
			'cron_saved'       => array( 'success', __( 'Pengaturan cronjob disimpan.', 'wc-imajiner' ) ),
			'cron_ran'         => array( 'success', __( 'Pemeriksaan pesanan kedaluwarsa sudah dijalankan.', 'wc-imajiner' ) ),
			'seeded'           => array( 'success', __( 'Data sampel berhasil dibuat.', 'wc-imajiner' ) ),
			'seed_cleared'     => array( 'success', __( 'Data sampel berhasil dihapus.', 'wc-imajiner' ) ),
			'seed_exists'      => array( 'warning', __( 'Data sampel sudah ada. Hapus dulu jika ingin membuat ulang.', 'wc-imajiner' ) ),
			'error'            => array( 'error', __( 'Terjadi kesalahan. Silakan coba lagi.', 'wc-imajiner' ) ),
		);

		if ( ! isset( $messages[ $code ] ) ) {
			return;
		}

		list( $type, $text ) = $messages[ $code ];
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $text )
		);
	}

	/**
	 * Plugin list quick link.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url = self::url( 'simplify' );
		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wc-imajiner' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Render settings page.
	 */
	public function render_page() {
		if ( ! self::user_can_manage() ) {
			return;
		}

		$tab = self::current_tab();
		?>
		<div class="wrap wc-imajiner-wrap">
			<h1><?php esc_html_e( 'Imajiner Extra Settings', 'wc-imajiner' ); ?></h1>
			<?php $this->render_tabs( $tab ); ?>
			<div class="wc-imajiner-tab-panel">
				<?php
				switch ( $tab ) {
					case 'emails':
						WC_Imajiner_Emails::render_tab();
						break;
					case 'invoice':
						WC_Imajiner_Invoices::render_tab();
						break;
					case 'cron':
						WC_Imajiner_Cron::render_tab();
						break;
					case 'seeder':
						WC_Imajiner_Seeder::render_tab();
						break;
					default:
						WC_Imajiner_Settings::render_tab();
						break;
				}
				?>
			</div>
			<div id="wc-imajiner-preview-modal" class="wc-imajiner-preview" hidden>
				<div class="wc-imajiner-preview__backdrop" data-preview-close></div>
				<div class="wc-imajiner-preview__dialog" role="dialog" aria-modal="true" aria-labelledby="wc-imajiner-preview-title">
					<div class="wc-imajiner-preview__header">
						<h2 id="wc-imajiner-preview-title" class="wc-imajiner-preview__title"></h2>
						<button type="button" class="wc-imajiner-preview__close" data-preview-close aria-label="<?php esc_attr_e( 'Tutup', 'wc-imajiner' ); ?>">&times;</button>
					</div>
					<div class="wc-imajiner-preview__body">
						<iframe title="<?php esc_attr_e( 'Pratinjau template', 'wc-imajiner' ); ?>"></iframe>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Tab navigation.
	 *
	 * @param string $current Current tab.
	 */
	protected function render_tabs( $current ) {
		echo '<nav class="nav-tab-wrapper wp-clearfix wc-imajiner-tabs">';
		foreach ( self::tabs() as $slug => $label ) {
			printf(
				'<a href="%1$s" class="nav-tab%2$s">%3$s</a>',
				esc_url( self::url( $slug ) ),
				$current === $slug ? ' nav-tab-active' : '',
				esc_html( $label )
			);
		}
		echo '</nav>';
	}

	/**
	 * Whether we are on the plugin screen.
	 *
	 * @return bool
	 */
	protected function is_plugin_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && 'woocommerce_page_' . self::PAGE_SLUG === $screen->id;
	}
}
