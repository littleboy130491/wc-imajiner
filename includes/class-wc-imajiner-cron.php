<?php
/**
 * Cancel orders after a configurable number of hours.
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cronjob tab and scheduled cancellation.
 */
class WC_Imajiner_Cron {

	const OPTION_KEY  = 'wc_imajiner_cron';
	const HOOK        = 'wc_imajiner_cancel_expired_orders';
	const META_SINCE  = '_wc_imajiner_status_since';

	/**
	 * Wire schedule, runner, and save handler.
	 */
	public function hooks() {
		add_action( 'admin_post_wc_imajiner_save_cron', array( $this, 'handle_save' ) );
		add_action( 'admin_post_wc_imajiner_run_cron', array( $this, 'handle_run' ) );
		add_action( self::HOOK, array( $this, 'cancel_expired_orders' ) );
		add_action( 'init', array( $this, 'maybe_schedule' ) );
		add_action( 'woocommerce_new_order', array( $this, 'record_status_since' ), 20, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'record_status_changed' ), 20, 4 );
	}

	/**
	 * Defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'cancel_unpaid_orders' => 'yes',
			'expiry_hours'         => 24,
			'cancel_statuses'      => array( 'wc-pending', 'wc-on-hold' ),
		);
	}

	/**
	 * Settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function all() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$settings = wp_parse_args( $stored, self::defaults() );
		$settings['expiry_hours'] = self::sanitize_hours( $settings['expiry_hours'] );

		if ( empty( $settings['cancel_statuses'] ) || ! is_array( $settings['cancel_statuses'] ) ) {
			$settings['cancel_statuses'] = self::defaults()['cancel_statuses'];
		} else {
			$settings['cancel_statuses'] = array_values( array_unique( array_map( 'strval', $settings['cancel_statuses'] ) ) );
		}

		return $settings;
	}

	/**
	 * Whether auto-cancel is on.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$settings = self::all();
		return isset( $settings['cancel_unpaid_orders'] ) && 'yes' === $settings['cancel_unpaid_orders'];
	}

	/**
	 * Expiry in hours.
	 *
	 * @return int
	 */
	public static function get_expiry_hours() {
		return self::all()['expiry_hours'];
	}

	/**
	 * Status slugs (with wc- prefix) that should auto-cancel.
	 *
	 * @return string[]
	 */
	public static function get_cancel_statuses() {
		return self::all()['cancel_statuses'];
	}

	/**
	 * Status slugs without wc- prefix, for wc_get_orders().
	 *
	 * @return string[]
	 */
	public static function get_cancel_status_keys() {
		$keys = array();
		foreach ( self::get_cancel_statuses() as $status ) {
			$keys[] = ( 0 === strpos( $status, 'wc-' ) ) ? substr( $status, 3 ) : $status;
		}
		return array_values( array_filter( $keys ) );
	}

	/**
	 * Clamp hours to a sane range.
	 *
	 * @param mixed $hours Raw hours.
	 * @return int
	 */
	public static function sanitize_hours( $hours ) {
		$hours = absint( $hours );
		if ( $hours < 1 ) {
			return 24;
		}
		return min( 8760, $hours );
	}

	/**
	 * Statuses that can be selected for auto-cancel.
	 *
	 * @return array<string, string> slug => label
	 */
	public static function get_selectable_statuses() {
		$statuses = WC_Imajiner_Settings::get_all_order_statuses();
		unset( $statuses['wc-cancelled'], $statuses['wc-refunded'] );
		return $statuses;
	}

	/**
	 * Record first status timestamp on new orders.
	 *
	 * @param int           $order_id Order ID.
	 * @param WC_Order|null $order    Order.
	 */
	public function record_status_since( $order_id, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( (int) $order->get_meta( self::META_SINCE ) ) {
			return;
		}

		$created = $order->get_date_created();
		$stamp   = $created ? $created->getTimestamp() : time();
		$order->update_meta_data( self::META_SINCE, $stamp );
		$order->save_meta_data();
	}

	/**
	 * Reset the timer whenever order status changes.
	 *
	 * @param int      $order_id   Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order      Order.
	 */
	public function record_status_changed( $order_id, $old_status, $new_status, $order ) {
		unset( $order_id, $old_status, $new_status );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$order->update_meta_data( self::META_SINCE, time() );
		$order->save_meta_data();
	}

	/**
	 * Unix timestamp to measure expiry from (status change, else created).
	 *
	 * @param WC_Order $order Order.
	 * @return int
	 */
	protected function get_status_since_timestamp( $order ) {
		$since = (int) $order->get_meta( self::META_SINCE );
		if ( $since > 0 ) {
			return $since;
		}

		$created = $order->get_date_created();
		return $created ? $created->getTimestamp() : 0;
	}

	/**
	 * Schedule or clear the hourly event.
	 */
	public function maybe_schedule() {
		$scheduled = wp_next_scheduled( self::HOOK );

		if ( self::is_enabled() ) {
			if ( ! $scheduled ) {
				wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', self::HOOK );
			}
			return;
		}

		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, self::HOOK );
		}
	}

	/**
	 * Clear scheduled event (deactivation).
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Cancel selected statuses after the configured hours.
	 *
	 * Timer starts at order creation, and resets when status changes.
	 *
	 * @return int Number of cancelled orders.
	 */
	public function cancel_expired_orders() {
		if ( ! self::is_enabled() || ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$statuses = self::get_cancel_status_keys();
		$hours    = self::get_expiry_hours();
		if ( empty( $statuses ) || $hours < 1 ) {
			return 0;
		}

		$cutoff = time() - ( $hours * HOUR_IN_SECONDS );
		$orders = wc_get_orders(
			array(
				'status'       => $statuses,
				'date_created' => '<' . gmdate( 'Y-m-d H:i:s', $cutoff ),
				'limit'        => 100,
				'orderby'      => 'date',
				'order'        => 'ASC',
				'return'       => 'objects',
			)
		);

		if ( empty( $orders ) ) {
			return 0;
		}

		$cancelled = 0;
		$note      = sprintf(
			/* translators: %d: hours */
			__( 'Dibatalkan otomatis karena melewati batas waktu %d jam sejak dibuat atau status terakhir diubah.', 'wc-imajiner' ),
			$hours
		);

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			if ( 'cancelled' === $order->get_status() ) {
				continue;
			}

			if ( ! in_array( $order->get_status(), $statuses, true ) ) {
				continue;
			}

			$since = $this->get_status_since_timestamp( $order );
			if ( $since <= 0 || $since > $cutoff ) {
				continue;
			}

			$order->update_status( 'cancelled', $note );
			++$cancelled;
		}

		return $cancelled;
	}

	/**
	 * Save cron settings.
	 */
	public function handle_save() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin.', 'wc-imajiner' ) );
		}

		check_admin_referer( 'wc_imajiner_save_cron' );

		$enabled  = ( isset( $_POST['cancel_unpaid_orders'] ) && 'yes' === $_POST['cancel_unpaid_orders'] ) ? 'yes' : 'no';
		$hours    = isset( $_POST['expiry_hours'] ) ? self::sanitize_hours( wp_unslash( $_POST['expiry_hours'] ) ) : 24;
		$allowed  = array_keys( self::get_selectable_statuses() );
		$selected = array();

		if ( ! empty( $_POST['cancel_statuses'] ) && is_array( $_POST['cancel_statuses'] ) ) {
			foreach ( $_POST['cancel_statuses'] as $status ) {
				$status = sanitize_text_field( wp_unslash( $status ) );
				if ( in_array( $status, $allowed, true ) ) {
					$selected[] = $status;
				}
			}
		}

		if ( empty( $selected ) ) {
			$selected = self::defaults()['cancel_statuses'];
		}

		update_option(
			self::OPTION_KEY,
			array(
				'cancel_unpaid_orders' => $enabled,
				'expiry_hours'         => $hours,
				'cancel_statuses'      => array_values( array_unique( $selected ) ),
			)
		);

		$this->maybe_schedule();

		wp_safe_redirect( WC_Imajiner_Admin::url( 'cron', array( 'wc_imajiner_notice' => 'cron_saved' ) ) );
		exit;
	}

	/**
	 * Manual run.
	 */
	public function handle_run() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin.', 'wc-imajiner' ) );
		}

		check_admin_referer( 'wc_imajiner_run_cron' );

		$count = $this->cancel_expired_orders();

		wp_safe_redirect(
			WC_Imajiner_Admin::url(
				'cron',
				array(
					'wc_imajiner_notice' => 'cron_ran',
					'cancelled'          => (string) $count,
				)
			)
		);
		exit;
	}

	/**
	 * Render Cronjob tab.
	 */
	public static function render_tab() {
		$settings         = self::all();
		$enabled          = self::is_enabled();
		$hours            = (int) $settings['expiry_hours'];
		$selected         = $settings['cancel_statuses'];
		$all_statuses     = self::get_selectable_statuses();
		$next             = wp_next_scheduled( self::HOOK );
		$next_text        = $next
			? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next )
			: __( 'Belum dijadwalkan', 'wc-imajiner' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cancelled = isset( $_GET['cancelled'] ) ? absint( $_GET['cancelled'] ) : null;
		?>
		<p class="wc-imajiner-intro">
			<?php esc_html_e( 'Batalkan pesanan otomatis setelah X jam. Waktu dihitung sejak pesanan dibuat, dan dihitung ulang jika statusnya berubah.', 'wc-imajiner' ); ?>
		</p>

		<div class="wc-imajiner-card">
			<h2 class="wc-imajiner-card__title"><?php esc_html_e( 'Pembatalan otomatis', 'wc-imajiner' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wc-imajiner-form">
				<input type="hidden" name="action" value="wc_imajiner_save_cron">
				<?php wp_nonce_field( 'wc_imajiner_save_cron' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Aktifkan pembatalan otomatis', 'wc-imajiner' ); ?></th>
						<td>
							<label class="wc-imajiner-toggle">
								<input type="checkbox" name="cancel_unpaid_orders" value="yes" <?php checked( $enabled ); ?>>
								<span class="wc-imajiner-toggle__ui" aria-hidden="true"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Aktifkan pembatalan otomatis', 'wc-imajiner' ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wc_imajiner_expiry_hours"><?php esc_html_e( 'Batalkan setelah (jam)', 'wc-imajiner' ); ?></label>
						</th>
						<td>
							<input
								type="number"
								id="wc_imajiner_expiry_hours"
								name="expiry_hours"
								class="small-text"
								min="1"
								max="8760"
								step="1"
								value="<?php echo esc_attr( (string) $hours ); ?>"
							>
							<span><?php esc_html_e( 'jam', 'wc-imajiner' ); ?></span>
							<p class="description">
								<?php esc_html_e( 'Dihitung sejak pesanan dibuat. Jika status berubah, hitungan dimulai lagi dari perubahan status itu.', 'wc-imajiner' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status yang dibatalkan', 'wc-imajiner' ); ?></th>
						<td>
							<p class="description wc-imajiner-card__intro">
								<?php esc_html_e( 'Centang status yang akan diubah menjadi Dibatalkan setelah melewati batas waktu.', 'wc-imajiner' ); ?>
							</p>
							<ul class="wc-imajiner-status-list">
								<?php foreach ( $all_statuses as $slug => $label ) : ?>
									<li>
										<label>
											<input
												type="checkbox"
												name="cancel_statuses[]"
												value="<?php echo esc_attr( $slug ); ?>"
												<?php checked( in_array( $slug, $selected, true ) ); ?>
											>
											<span class="wc-imajiner-status-list__label"><?php echo esc_html( $label ); ?></span>
											<code class="wc-imajiner-status-list__slug"><?php echo esc_html( $slug ); ?></code>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Jadwal berikutnya', 'wc-imajiner' ); ?></th>
						<td><code><?php echo esc_html( $next_text ); ?></code></td>
					</tr>
				</table>

				<?php submit_button( __( 'Simpan pengaturan cronjob', 'wc-imajiner' ) ); ?>
			</form>
		</div>

		<div class="wc-imajiner-card">
			<h2 class="wc-imajiner-card__title"><?php esc_html_e( 'Jalankan sekarang', 'wc-imajiner' ); ?></h2>
			<p class="description wc-imajiner-card__intro">
				<?php esc_html_e( 'Periksa pesanan kedaluwarsa tanpa menunggu jadwal hourly.', 'wc-imajiner' ); ?>
			</p>
			<?php if ( null !== $cancelled ) : ?>
				<p><strong>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: cancelled count */
							_n( '%d pesanan dibatalkan.', '%d pesanan dibatalkan.', $cancelled, 'wc-imajiner' ),
							$cancelled
						)
					);
					?>
				</strong></p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wc-imajiner-cron-run">
				<input type="hidden" name="action" value="wc_imajiner_run_cron">
				<?php wp_nonce_field( 'wc_imajiner_run_cron' ); ?>
				<?php submit_button( __( 'Jalankan pembatalan sekarang', 'wc-imajiner' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}
}
