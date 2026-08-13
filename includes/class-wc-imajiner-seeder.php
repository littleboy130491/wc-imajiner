<?php
/**
 * Sample products and orders seeder.
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seeder tab: create demo catalog data without images.
 */
class WC_Imajiner_Seeder {

	const META_KEY = '_wc_imajiner_seeded';
	const USER_KEY = '_wc_imajiner_seed_user';

	/**
	 * Wire POST handlers.
	 */
	public function hooks() {
		add_action( 'admin_post_wc_imajiner_seed', array( $this, 'handle_seed' ) );
		add_action( 'admin_post_wc_imajiner_clear_seed', array( $this, 'handle_clear' ) );
	}

	/**
	 * Create sample products + orders.
	 */
	public function handle_seed() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin.', 'wc-imajiner' ) );
		}

		check_admin_referer( 'wc_imajiner_seed' );

		if ( $this->seeded_product_count() > 0 || $this->seeded_order_count() > 0 ) {
			wp_safe_redirect( WC_Imajiner_Admin::url( 'seeder', array( 'wc_imajiner_notice' => 'seed_exists' ) ) );
			exit;
		}

		$GLOBALS['wc_imajiner_seeding'] = true;
		$this->silence_emails();

		$products = $this->create_products();
		$user_id  = $this->ensure_demo_customer();
		$this->create_orders( $products, $user_id );

		unset( $GLOBALS['wc_imajiner_seeding'] );

		wp_safe_redirect( WC_Imajiner_Admin::url( 'seeder', array( 'wc_imajiner_notice' => 'seeded' ) ) );
		exit;
	}

	/**
	 * Delete seeded data.
	 */
	public function handle_clear() {
		if ( ! WC_Imajiner_Admin::user_can_manage() ) {
			wp_die( esc_html__( 'Anda tidak memiliki izin.', 'wc-imajiner' ) );
		}

		check_admin_referer( 'wc_imajiner_clear_seed' );

		$this->delete_seeded_orders();
		$this->delete_seeded_products();
		$this->delete_demo_customer();

		wp_safe_redirect( WC_Imajiner_Admin::url( 'seeder', array( 'wc_imajiner_notice' => 'seed_cleared' ) ) );
		exit;
	}

	/**
	 * Prevent transactional emails while seeding.
	 */
	protected function silence_emails() {
		if ( ! function_exists( 'WC' ) || ! WC()->mailer() ) {
			return;
		}

		foreach ( WC()->mailer()->get_emails() as $email ) {
			if ( ! empty( $email->id ) ) {
				add_filter( 'woocommerce_email_enabled_' . $email->id, '__return_false', 100 );
			}
		}
	}

	/**
	 * Create simple + variable products without images.
	 *
	 * @return WC_Product[]
	 */
	protected function create_products() {
		$created = array();

		$simple = array(
			array(
				'name'        => 'Kaos Polos Katun',
				'price'       => 89000,
				'short'       => 'Kaos katun nyaman untuk pemakaian sehari-hari.',
				'description' => 'Kaos polos berbahan katun combed. Cocok untuk kerja santai atau aktivitas harian.',
			),
			array(
				'name'        => 'Celana Jeans Slim Fit',
				'price'       => 249000,
				'short'       => 'Jeans slim fit dengan potongan rapi.',
				'description' => 'Celana jeans warna biru tua, bahan denim tebal, potongan slim fit.',
			),
			array(
				'name'        => 'Topi Baseball',
				'price'       => 79000,
				'short'       => 'Topi baseball adjustable.',
				'description' => 'Topi baseball dengan tali belakang. Tidak termasuk foto produk.',
			),
			array(
				'name'        => 'Tas Kanvas',
				'price'       => 159000,
				'short'       => 'Tas kanvas ringan untuk sehari-hari.',
				'description' => 'Tas kanvas dengan resleting dan saku dalam.',
			),
			array(
				'name'        => 'Botol Minum Stainless',
				'price'       => 99000,
				'short'       => 'Botol minum 500ml tahan panas dan dingin.',
				'description' => 'Botol stainless steel 500ml, cocok dibawa ke kantor atau olahraga.',
			),
		);

		foreach ( $simple as $item ) {
			$product = new WC_Product_Simple();
			$product->set_name( $item['name'] );
			$product->set_regular_price( (string) $item['price'] );
			$product->set_short_description( $item['short'] );
			$product->set_description( $item['description'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_stock_status( 'instock' );
			$product->set_manage_stock( true );
			$product->set_stock_quantity( 25 );
			$product->update_meta_data( self::META_KEY, '1' );
			$product->save();
			$created[] = $product;
		}

		$created[] = $this->create_variable_product(
			'Kemeja Flanel',
			'Kemeja flanel dengan pilihan ukuran dan warna.',
			'Ukuran',
			array( 'S', 'M', 'L', 'XL' ),
			'Warna',
			array( 'Merah', 'Navy' ),
			189000
		);

		$created[] = $this->create_variable_product(
			'Sneakers Casual',
			'Sneakers kasual dengan beberapa ukuran.',
			'Ukuran',
			array( '39', '40', '41', '42', '43' ),
			'',
			array(),
			329000
		);

		return $created;
	}

	/**
	 * Create one variable product and its variations.
	 *
	 * @param string   $name       Name.
	 * @param string   $short      Short description.
	 * @param string   $attr_a     First attribute name.
	 * @param string[] $opts_a     First attribute options.
	 * @param string   $attr_b     Second attribute name.
	 * @param string[] $opts_b     Second attribute options.
	 * @param int      $price      Regular price.
	 * @return WC_Product_Variable
	 */
	protected function create_variable_product( $name, $short, $attr_a, $opts_a, $attr_b, $opts_b, $price ) {
		$product = new WC_Product_Variable();
		$product->set_name( $name );
		$product->set_short_description( $short );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_stock_status( 'instock' );
		$product->update_meta_data( self::META_KEY, '1' );

		$attributes = array();
		$attributes[] = $this->make_attribute( $attr_a, $opts_a );

		if ( $attr_b && ! empty( $opts_b ) ) {
			$attributes[] = $this->make_attribute( $attr_b, $opts_b );
		}

		$product->set_attributes( $attributes );
		$product_id = $product->save();

		$combinations = array();
		if ( $attr_b && ! empty( $opts_b ) ) {
			foreach ( $opts_a as $a ) {
				foreach ( $opts_b as $b ) {
					$combinations[] = array( $attr_a => $a, $attr_b => $b );
				}
			}
		} else {
			foreach ( $opts_a as $a ) {
				$combinations[] = array( $attr_a => $a );
			}
		}

		foreach ( $combinations as $combo ) {
			$variation_attrs = array();
			foreach ( $combo as $attr_name => $attr_value ) {
				$variation_attrs[ sanitize_title( $attr_name ) ] = $attr_value;
			}

			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_regular_price( (string) $price );
			$variation->set_status( 'publish' );
			$variation->set_stock_status( 'instock' );
			$variation->set_manage_stock( true );
			$variation->set_stock_quantity( 10 );
			$variation->set_attributes( $variation_attrs );
			$variation->update_meta_data( self::META_KEY, '1' );
			$variation->save();
		}

		WC_Product_Variable::sync( $product_id );

		$fresh = wc_get_product( $product_id );
		return $fresh instanceof WC_Product_Variable ? $fresh : $product;
	}

	/**
	 * Build a custom product attribute.
	 *
	 * @param string   $name    Name.
	 * @param string[] $options Options.
	 * @return WC_Product_Attribute
	 */
	protected function make_attribute( $name, $options ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( $name );
		$attribute->set_options( $options );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		return $attribute;
	}

	/**
	 * Demo customer used by sample orders.
	 *
	 * @return int
	 */
	protected function ensure_demo_customer() {
		$email = 'demo.pelanggan@example.com';
		$user  = get_user_by( 'email', $email );

		if ( $user ) {
			update_user_meta( $user->ID, self::USER_KEY, '1' );
			return (int) $user->ID;
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => 'demo_pelanggan',
				'user_pass'    => wp_generate_password( 16, true ),
				'user_email'   => $email,
				'first_name'   => 'Budi',
				'last_name'    => 'Santoso',
				'display_name' => 'Budi Santoso',
				'role'         => 'customer',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return 0;
		}

		update_user_meta( $user_id, self::USER_KEY, '1' );
		update_user_meta( $user_id, 'billing_first_name', 'Budi' );
		update_user_meta( $user_id, 'billing_last_name', 'Santoso' );
		update_user_meta( $user_id, 'billing_email', $email );
		update_user_meta( $user_id, 'billing_phone', '081234567890' );
		update_user_meta( $user_id, 'billing_address_1', 'Jl. Melati No. 10' );
		update_user_meta( $user_id, 'billing_city', 'Jakarta' );
		update_user_meta( $user_id, 'billing_postcode', '12110' );
		update_user_meta( $user_id, 'billing_country', 'ID' );

		return (int) $user_id;
	}

	/**
	 * Create a few sample orders in useful statuses.
	 *
	 * @param WC_Product[] $products Products.
	 * @param int          $user_id  Customer id.
	 */
	protected function create_orders( $products, $user_id ) {
		if ( empty( $products ) ) {
			return;
		}

		$simple = array();
		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product_Simple ) {
				$simple[] = $product;
			}
		}

		if ( empty( $simple ) ) {
			$simple = $products;
		}

		$plans = array(
			array( 'status' => 'pending', 'items' => 1 ),
			array( 'status' => 'on-hold', 'items' => 2 ),
			array( 'status' => 'processing', 'items' => 1 ),
			array( 'status' => 'completed', 'items' => 2 ),
		);

		foreach ( $plans as $index => $plan ) {
			$order = wc_create_order(
				array(
					'customer_id' => $user_id,
					'created_via' => 'wc-imajiner-seeder',
				)
			);

			if ( is_wp_error( $order ) ) {
				continue;
			}

			for ( $i = 0; $i < $plan['items']; $i++ ) {
				$product = $simple[ ( $index + $i ) % count( $simple ) ];
				$order->add_product( $product, 1 );
			}

			$order->set_address(
				array(
					'first_name' => 'Budi',
					'last_name'  => 'Santoso',
					'email'      => 'demo.pelanggan@example.com',
					'phone'      => '081234567890',
					'address_1'  => 'Jl. Melati No. 10',
					'city'       => 'Jakarta',
					'postcode'   => '12110',
					'country'    => 'ID',
				),
				'billing'
			);

			$order->set_payment_method( 'bacs' );
			$order->set_payment_method_title( __( 'Transfer Bank', 'wc-imajiner' ) );
			$order->calculate_totals();
			$order->update_meta_data( self::META_KEY, '1' );
			$order->save();
			$order->update_status( $plan['status'], __( 'Pesanan sampel dari WC Imajiner seeder.', 'wc-imajiner' ), false );
		}
	}

	/**
	 * Meta query matching seeded flags.
	 *
	 * @return array<string, mixed>
	 */
	protected static function seeded_meta_query() {
		return array(
			array(
				'key'   => self::META_KEY,
				'value' => '1',
			),
		);
	}

	/**
	 * @return int
	 */
	protected function seeded_product_count() {
		$ids = wc_get_products(
			array(
				'limit'      => 1,
				'return'     => 'ids',
				'status'     => array( 'publish', 'draft', 'private' ),
				'meta_query' => self::seeded_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		return is_array( $ids ) ? count( $ids ) : 0;
	}

	/**
	 * Count using a broader query for the UI.
	 *
	 * @return int
	 */
	public static function count_seeded_products() {
		$ids = wc_get_products(
			array(
				'limit'      => -1,
				'return'     => 'ids',
				'status'     => array( 'publish', 'draft', 'private' ),
				'type'       => array( 'simple', 'variable' ),
				'meta_query' => self::seeded_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		return is_array( $ids ) ? count( $ids ) : 0;
	}

	/**
	 * @return int
	 */
	protected function seeded_order_count() {
		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'return'     => 'ids',
				'meta_query' => self::seeded_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		return is_array( $orders ) ? count( $orders ) : 0;
	}

	/**
	 * @return int
	 */
	public static function count_seeded_orders() {
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'return'     => 'ids',
				'meta_query' => self::seeded_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		return is_array( $orders ) ? count( $orders ) : 0;
	}

	/**
	 * Delete seeded orders.
	 */
	protected function delete_seeded_orders() {
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'return'     => 'objects',
				'meta_query' => self::seeded_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order ) {
				$order->delete( true );
			}
		}
	}

	/**
	 * Delete seeded products and variations.
	 */
	protected function delete_seeded_products() {
		$ids = wc_get_products(
			array(
				'limit'      => -1,
				'return'     => 'ids',
				'status'     => 'any',
				'type'       => array( 'simple', 'variable', 'variation' ),
				'meta_query' => self::seeded_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		foreach ( (array) $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product ) {
				$product->delete( true );
			}
		}
	}

	/**
	 * Remove demo customer if we created it.
	 */
	protected function delete_demo_customer() {
		$users = get_users(
			array(
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::USER_KEY,
						'value' => '1',
					),
				),
				'number'     => 5,
				'fields'     => 'ID',
			)
		);

		foreach ( $users as $user_id ) {
			wp_delete_user( (int) $user_id );
		}
	}

	/**
	 * Render Seeder tab.
	 */
	public static function render_tab() {
		$products = self::count_seeded_products();
		$orders   = self::count_seeded_orders();
		$has_data = $products > 0 || $orders > 0;
		?>
		<p class="wc-imajiner-intro">
			<?php esc_html_e( 'Buat produk dan pesanan sampel tanpa gambar untuk keperluan demo atau pengujian toko.', 'wc-imajiner' ); ?>
		</p>

		<div class="wc-imajiner-card">
			<h2 class="wc-imajiner-card__title"><?php esc_html_e( 'Data sampel', 'wc-imajiner' ); ?></h2>
			<p class="description wc-imajiner-card__intro">
				<?php esc_html_e( 'Akan dibuat 5 produk simple, 2 produk variable, dan 4 pesanan dengan status berbeda. Tidak ada gambar yang diunggah.', 'wc-imajiner' ); ?>
			</p>
			<ul class="wc-imajiner-seed-stats">
				<li><?php echo esc_html( sprintf( /* translators: %d: count */ __( 'Produk sampel: %d', 'wc-imajiner' ), $products ) ); ?></li>
				<li><?php echo esc_html( sprintf( /* translators: %d: count */ __( 'Pesanan sampel: %d', 'wc-imajiner' ), $orders ) ); ?></li>
			</ul>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wc-imajiner-seeder-form" data-confirm="seeder">
				<input type="hidden" name="action" value="wc_imajiner_seed">
				<?php wp_nonce_field( 'wc_imajiner_seed' ); ?>
				<?php
				submit_button(
					__( 'Buat produk & pesanan sampel', 'wc-imajiner' ),
					'primary',
					'submit',
					false,
					$has_data ? array( 'disabled' => 'disabled' ) : array()
				);
				?>
			</form>
		</div>

		<div class="wc-imajiner-card">
			<h2 class="wc-imajiner-card__title"><?php esc_html_e( 'Hapus data sampel', 'wc-imajiner' ); ?></h2>
			<p class="description wc-imajiner-card__intro">
				<?php esc_html_e( 'Hanya menghapus produk, pesanan, dan akun demo yang dibuat seeder. Data toko lain tidak diubah.', 'wc-imajiner' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wc-imajiner-seeder-form" data-confirm="delete">
				<input type="hidden" name="action" value="wc_imajiner_clear_seed">
				<?php wp_nonce_field( 'wc_imajiner_clear_seed' ); ?>
				<?php
				submit_button(
					__( 'Hapus data sampel', 'wc-imajiner' ),
					'delete',
					'submit',
					false,
					$has_data ? array() : array( 'disabled' => 'disabled' )
				);
				?>
			</form>
		</div>
		<?php
	}
}
