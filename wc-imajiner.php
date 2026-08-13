<?php
/**
 * Plugin Name: WC Imajiner
 * Plugin URI:  https://imajiner.id
 * Description: Extra Settings for WooCommerce.
 * Version:     1.0.0
 * Author:      Imajiner
 * Text Domain: wc-imajiner
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 10.0
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

define( 'WC_IMAJINER_VERSION', '1.0.0' );
define( 'WC_IMAJINER_FILE', __FILE__ );
define( 'WC_IMAJINER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_IMAJINER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare HPOS / features compatibility.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WC_IMAJINER_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WC_IMAJINER_FILE, true );
		}
	}
);

/**
 * Bootstrap after plugins load.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'WC Imajiner membutuhkan WooCommerce yang aktif.', 'wc-imajiner' );
					echo '</p></div>';
				}
			);
			return;
		}

		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-settings.php';
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-admin.php';
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-features.php';
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-emails.php';
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-invoices.php';
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-cron.php';
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-seeder.php';
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-plugin.php';

		WC_Imajiner_Plugin::instance()->init();
	},
	20
);

/**
 * Activation defaults.
 */
register_activation_hook(
	__FILE__,
	static function () {
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-settings.php';
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-cron.php';

		$existing = get_option( WC_Imajiner_Settings::OPTION_KEY, null );
		if ( null === $existing ) {
			update_option( WC_Imajiner_Settings::OPTION_KEY, WC_Imajiner_Settings::defaults() );
		}

		if ( null === get_option( WC_Imajiner_Cron::OPTION_KEY, null ) ) {
			update_option( WC_Imajiner_Cron::OPTION_KEY, WC_Imajiner_Cron::defaults() );
		}

		if ( ! wp_next_scheduled( WC_Imajiner_Cron::HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', WC_Imajiner_Cron::HOOK );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		require_once WC_IMAJINER_PATH . 'includes/class-wc-imajiner-cron.php';
		WC_Imajiner_Cron::unschedule();
	}
);
