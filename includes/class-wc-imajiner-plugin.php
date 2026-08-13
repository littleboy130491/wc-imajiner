<?php
/**
 * Plugin bootstrap singleton.
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin orchestrator.
 */
class WC_Imajiner_Plugin {

	/**
	 * Instance.
	 *
	 * @var WC_Imajiner_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings handler.
	 *
	 * @var WC_Imajiner_Settings
	 */
	public $settings;

	/**
	 * Admin dashboard.
	 *
	 * @var WC_Imajiner_Admin
	 */
	public $admin;

	/**
	 * Features handler.
	 *
	 * @var WC_Imajiner_Features
	 */
	public $features;

	/**
	 * Emails handler.
	 *
	 * @var WC_Imajiner_Emails
	 */
	public $emails;

	/**
	 * Invoices handler.
	 *
	 * @var WC_Imajiner_Invoices
	 */
	public $invoices;

	/**
	 * Cron handler.
	 *
	 * @var WC_Imajiner_Cron
	 */
	public $cron;

	/**
	 * Seeder handler.
	 *
	 * @var WC_Imajiner_Seeder
	 */
	public $seeder;

	/**
	 * Get singleton.
	 *
	 * @return WC_Imajiner_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init subsystems.
	 */
	public function init() {
		load_plugin_textdomain( 'wc-imajiner', false, dirname( plugin_basename( WC_IMAJINER_FILE ) ) . '/languages' );

		$this->settings = new WC_Imajiner_Settings();
		$this->admin    = new WC_Imajiner_Admin();
		$this->features = new WC_Imajiner_Features();
		$this->emails   = new WC_Imajiner_Emails();
		$this->invoices = new WC_Imajiner_Invoices();
		$this->cron     = new WC_Imajiner_Cron();
		$this->seeder   = new WC_Imajiner_Seeder();

		$this->settings->hooks();
		$this->admin->hooks();
		$this->features->hooks();
		$this->emails->hooks();
		$this->invoices->hooks();
		$this->cron->hooks();
		$this->seeder->hooks();
	}
}
