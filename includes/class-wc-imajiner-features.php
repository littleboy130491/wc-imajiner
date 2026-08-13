<?php
/**
 * Feature implementations driven by settings toggles.
 *
 * @package WC_Imajiner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Applies WooCommerce simplifications when enabled.
 */
class WC_Imajiner_Features {

	/**
	 * Wire feature hooks.
	 */
	public function hooks() {
		// Product types.
		add_filter( 'product_type_selector', array( $this, 'filter_product_types' ), 100 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'guard_product_type_on_save' ), 5 );
		add_filter( 'woocommerce_rest_pre_insert_product_object', array( $this, 'guard_rest_product_type' ), 10, 3 );

		// Digital products (virtual / downloadable).
		add_filter( 'product_type_options', array( $this, 'filter_product_type_options' ), 100 );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'guard_digital_product_object' ), 20 );
		add_action( 'woocommerce_admin_process_variation_object', array( $this, 'guard_digital_variation_object' ), 20, 2 );
		add_filter( 'woocommerce_rest_pre_insert_product_object', array( $this, 'guard_rest_digital_product' ), 20, 3 );
		add_action( 'admin_head', array( $this, 'hide_digital_product_admin_ui' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'remove_downloads_account_menu_item' ), 100 );
		add_action( 'template_redirect', array( $this, 'redirect_downloads_endpoint' ) );
		add_filter( 'woocommerce_order_downloads_table_show_downloads', array( $this, 'hide_order_downloads_table' ), 100 );
		add_filter( 'woocommerce_customer_available_downloads', array( $this, 'clear_customer_downloads' ), 100 );

		// Product data tabs.
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'filter_product_data_tabs' ), 100 );
		add_filter( 'woocommerce_allow_marketplace_suggestions', array( $this, 'filter_marketplace_suggestions' ) );

		// Brands.
		add_filter( 'register_taxonomy_product_brand', array( $this, 'filter_brand_taxonomy_args' ), 100 );
		add_action( 'widgets_init', array( $this, 'unregister_brand_widgets' ), 20 );
		add_action( 'wp_loaded', array( $this, 'disable_brand_frontend' ), 20 );
		add_action( 'admin_menu', array( $this, 'remove_brands_admin_menu' ), 999 );
		add_action( 'add_meta_boxes', array( $this, 'remove_brand_metabox' ), 100 );
		add_filter( 'manage_product_posts_columns', array( $this, 'remove_brand_column' ), 100 );
		add_filter( 'woocommerce_products_admin_list_table_filters', array( $this, 'remove_brand_list_filter' ), 100 );

		// Reviews.
		add_filter( 'pre_option_woocommerce_enable_reviews', array( $this, 'filter_reviews_option' ) );
		add_filter( 'woocommerce_product_tabs', array( $this, 'remove_reviews_tab' ), 98 );
		add_filter( 'woocommerce_register_post_type_product', array( $this, 'filter_product_post_type_args' ) );
		add_action( 'admin_menu', array( $this, 'remove_reviews_admin_menu' ), 999 );
		add_filter( 'comments_open', array( $this, 'close_product_comments' ), 20, 2 );

		// Coupons.
		add_filter( 'pre_option_woocommerce_enable_coupons', array( $this, 'filter_coupons_option' ) );
		add_filter( 'woocommerce_coupons_enabled', array( $this, 'filter_coupons_enabled' ) );
		add_filter( 'woocommerce_register_post_type_shop_coupon', array( $this, 'filter_coupon_post_type_args' ) );
		add_action( 'admin_menu', array( $this, 'remove_coupons_admin_menu' ), 999 );

		// Reports & Analytics.
		add_filter( 'pre_option_woocommerce_analytics_enabled', array( $this, 'filter_analytics_option' ) );
		add_action( 'admin_menu', array( $this, 'remove_reports_admin_menu' ), 999 );
		add_action( 'admin_init', array( $this, 'block_reports_screens' ) );

		// Payments & Marketing top-level menus.
		add_action( 'admin_menu', array( $this, 'remove_payments_admin_menu' ), 999 );
		add_action( 'admin_menu', array( $this, 'remove_marketing_admin_menu' ), 999 );
		add_action( 'admin_init', array( $this, 'block_marketing_screens' ) );

		// Extensions, Status & Home submenus.
		add_action( 'admin_menu', array( $this, 'remove_extensions_admin_menu' ), 999 );
		add_action( 'admin_menu', array( $this, 'remove_status_admin_menu' ), 999 );
		add_action( 'admin_menu', array( $this, 'remove_home_admin_menu' ), 999 );
		add_action( 'admin_init', array( $this, 'block_extensions_screens' ) );
		add_action( 'admin_init', array( $this, 'block_status_screens' ) );
		add_action( 'admin_init', array( $this, 'block_home_screens' ) );

		// WooCommerce Settings tabs.
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'filter_settings_tabs' ), 100 );
		add_action( 'admin_init', array( $this, 'block_settings_tabs' ) );

		// Order statuses visibility.
		add_filter( 'wc_order_statuses', array( $this, 'filter_order_statuses' ), 100 );
		add_filter( 'woocommerce_register_shop_order_post_statuses', array( $this, 'filter_registered_order_post_statuses' ), 100 );

		// Checkout requires an account.
		add_filter( 'pre_option_woocommerce_enable_guest_checkout', array( $this, 'filter_guest_checkout_option' ) );
		add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', array( $this, 'filter_checkout_signup_option' ) );
		add_filter( 'pre_option_woocommerce_enable_myaccount_registration', array( $this, 'filter_checkout_signup_option' ) );
		add_filter( 'woocommerce_checkout_registration_required', array( $this, 'filter_checkout_registration_required' ) );

		// Bank transfer only.
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_available_gateways' ), 100 );
	}

	/**
	 * Keep only simple + variable product types.
	 *
	 * @param array $types Product types.
	 * @return array
	 */
	public function filter_product_types( $types ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'limit_product_types' ) ) {
			return $types;
		}

		$allowed = array( 'simple', 'variable' );
		return array_intersect_key( (array) $types, array_flip( $allowed ) );
	}

	/**
	 * Prevent saving disallowed product types from classic editor.
	 *
	 * @param int $post_id Product ID.
	 */
	public function guard_product_type_on_save( $post_id ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'limit_product_types' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC core verifies nonce earlier.
		$type = isset( $_POST['product-type'] ) ? sanitize_title( wp_unslash( $_POST['product-type'] ) ) : '';
		if ( $type && ! in_array( $type, array( 'simple', 'variable' ), true ) ) {
			$_POST['product-type'] = 'simple';
		}
	}

	/**
	 * Remove Virtual / Downloadable checkboxes from Product data.
	 *
	 * @param array $options Product type options.
	 * @return array
	 */
	public function filter_product_type_options( $options ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_digital_products' ) ) {
			return $options;
		}

		unset( $options['virtual'], $options['downloadable'] );
		return $options;
	}

	/**
	 * Force physical product flags while saving in admin.
	 *
	 * @param WC_Product $product Product object.
	 */
	public function guard_digital_product_object( $product ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_digital_products' ) || ! $product instanceof WC_Product ) {
			return;
		}

		$product->set_virtual( false );
		$product->set_downloadable( false );
		$product->set_downloads( array() );
	}

	/**
	 * Force physical flags on variations.
	 *
	 * @param WC_Product_Variation $variation Variation object.
	 * @param int                  $index     Loop index.
	 */
	public function guard_digital_variation_object( $variation, $index ) {
		unset( $index );

		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_digital_products' ) || ! $variation instanceof WC_Product ) {
			return;
		}

		$variation->set_virtual( false );
		$variation->set_downloadable( false );
		$variation->set_downloads( array() );
	}

	/**
	 * Block enabling digital flags via REST / block editor.
	 *
	 * @param WC_Product      $product  Product.
	 * @param WP_REST_Request $request  Request.
	 * @param bool            $creating Creating.
	 * @return WC_Product
	 */
	public function guard_rest_digital_product( $product, $request, $creating ) {
		unset( $request, $creating );

		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_digital_products' ) ) {
			return $product;
		}

		if ( ! $product instanceof WC_Product ) {
			return $product;
		}

		$product->set_virtual( false );
		$product->set_downloadable( false );
		$product->set_downloads( array() );

		return $product;
	}

	/**
	 * Hide leftover digital UI in the classic product editor.
	 */
	public function hide_digital_product_admin_ui() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_digital_products' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'product', 'edit-product' ), true ) ) {
			return;
		}

		echo '<style id="wc-imajiner-hide-digital">
			#woocommerce-product-data .product_data_tabs .general_tab ~ .inventory_options + .options_group label[for="_virtual"],
			label[for="_virtual"],
			label[for="_downloadable"],
			.variable_is_virtual,
			.variable_is_downloadable,
			.show_if_downloadable,
			.show_if_variation_downloadable,
			#woocommerce-product-data .downloadable_files {
				display: none !important;
			}
			.woocommerce_variation .tips:has(input.variable_is_virtual),
			.woocommerce_variation .tips:has(input.variable_is_downloadable) {
				display: none !important;
			}
		</style>';
	}

	/**
	 * Remove Downloads from My Account navigation.
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public function remove_downloads_account_menu_item( $items ) {
		if ( WC_Imajiner_Settings::is_enabled( 'disable_digital_products' ) ) {
			unset( $items['downloads'] );
		}
		return $items;
	}

	/**
	 * Redirect My Account Downloads endpoint to the dashboard.
	 */
	public function redirect_downloads_endpoint() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_digital_products' ) ) {
			return;
		}

		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}

		global $wp;
		if ( isset( $wp->query_vars['downloads'] ) ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
			exit;
		}
	}

	/**
	 * Hide downloads table on order details / thank you pages.
	 *
	 * @param bool $show Whether to show.
	 * @return bool
	 */
	public function hide_order_downloads_table( $show ) {
		if ( WC_Imajiner_Settings::is_enabled( 'disable_digital_products' ) ) {
			return false;
		}
		return $show;
	}

	/**
	 * Clear customer downloadable products list.
	 *
	 * @param array $downloads Downloads.
	 * @return array
	 */
	public function clear_customer_downloads( $downloads ) {
		if ( WC_Imajiner_Settings::is_enabled( 'disable_digital_products' ) ) {
			return array();
		}
		return $downloads;
	}

	/**
	 * Block disallowed types via REST / block product editor.
	 *
	 * @param WC_Product      $product  Product object.
	 * @param WP_REST_Request $request  Request.
	 * @param bool            $creating Creating.
	 * @return WC_Product|WP_Error
	 */
	public function guard_rest_product_type( $product, $request, $creating ) {
		unset( $request, $creating );

		if ( ! WC_Imajiner_Settings::is_enabled( 'limit_product_types' ) ) {
			return $product;
		}

		if ( ! $product instanceof WC_Product ) {
			return $product;
		}

		$type = $product->get_type();
		if ( in_array( $type, array( 'simple', 'variable', 'variation' ), true ) ) {
			return $product;
		}

		return new WP_Error(
			'wc_imajiner_invalid_product_type',
			__( 'Hanya produk Simple dan Variable yang diizinkan.', 'wc-imajiner' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Remove selected Product data tabs.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function filter_product_data_tabs( $tabs ) {
		if ( WC_Imajiner_Settings::is_enabled( 'disable_linked_products_tab' ) ) {
			unset( $tabs['linked_product'] );
		}

		if ( WC_Imajiner_Settings::is_enabled( 'disable_advanced_tab' ) ) {
			unset( $tabs['advanced'] );
		}

		if ( WC_Imajiner_Settings::is_enabled( 'disable_marketplace_tab' ) ) {
			unset( $tabs['marketplace-suggestions'] );
		}

		return $tabs;
	}

	/**
	 * Disable marketplace suggestions (removes “Get more options” registration path too).
	 *
	 * @param bool $allow Whether allowed.
	 * @return bool
	 */
	public function filter_marketplace_suggestions( $allow ) {
		if ( WC_Imajiner_Settings::is_enabled( 'disable_marketplace_tab' ) ) {
			return false;
		}
		return $allow;
	}

	/**
	 * Hide brand taxonomy from UI / REST / menus.
	 *
	 * @param array $args Taxonomy args.
	 * @return array
	 */
	public function filter_brand_taxonomy_args( $args ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_brands' ) ) {
			return $args;
		}

		$args['public']              = false;
		$args['publicly_queryable']  = false;
		$args['show_ui']             = false;
		$args['show_in_menu']        = false;
		$args['show_in_nav_menus']   = false;
		$args['show_admin_column']   = false;
		$args['show_in_quick_edit']  = false;
		$args['show_in_rest']        = false;
		$args['rewrite']             = false;

		return $args;
	}

	/**
	 * Unregister brand widgets.
	 */
	public function unregister_brand_widgets() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_brands' ) ) {
			return;
		}

		unregister_widget( 'WC_Widget_Brand_Description' );
		unregister_widget( 'WC_Widget_Brand_Nav' );
		unregister_widget( 'WC_Widget_Brand_Thumbnails' );
	}

	/**
	 * Remove brand output on the storefront.
	 */
	public function disable_brand_frontend() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_brands' ) ) {
			return;
		}

		if ( empty( $GLOBALS['WC_Brands'] ) || ! $GLOBALS['WC_Brands'] instanceof WC_Brands ) {
			return;
		}

		$brands = $GLOBALS['WC_Brands'];
		remove_action( 'woocommerce_product_meta_end', array( $brands, 'show_brand' ) );
		remove_action( 'woocommerce_archive_description', array( $brands, 'brand_description' ) );
		remove_filter( 'woocommerce_structured_data_product', array( $brands, 'add_structured_data' ), 20 );
		remove_filter( 'template_include', array( $brands, 'template_loader' ) );
	}

	/**
	 * Remove Brands submenu if it still appears.
	 */
	public function remove_brands_admin_menu() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_brands' ) ) {
			return;
		}

		remove_submenu_page( 'edit.php?post_type=product', 'edit-tags.php?taxonomy=product_brand&post_type=product' );
	}

	/**
	 * Remove brand metabox from product editor.
	 */
	public function remove_brand_metabox() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_brands' ) ) {
			return;
		}

		remove_meta_box( 'product_branddiv', 'product', 'side' );
		remove_meta_box( 'tagsdiv-product_brand', 'product', 'side' );
	}

	/**
	 * Remove brand column from products list.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function remove_brand_column( $columns ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_brands' ) ) {
			return $columns;
		}

		unset( $columns['taxonomy-product_brand'], $columns['product_brand'] );
		return $columns;
	}

	/**
	 * Remove brand filter from products list table.
	 *
	 * @param array $filters Filters.
	 * @return array
	 */
	public function remove_brand_list_filter( $filters ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_brands' ) ) {
			return $filters;
		}

		unset( $filters['product_brand'] );
		return $filters;
	}

	/**
	 * Force reviews option off when toggle enabled.
	 *
	 * @param mixed $value Current value.
	 * @return string|mixed
	 */
	public function filter_reviews_option( $value ) {
		unset( $value );

		if ( WC_Imajiner_Settings::is_enabled( 'disable_reviews' ) ) {
			return 'no';
		}

		return false; // Fall through to real option.
	}

	/**
	 * Remove reviews tab on single product.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function remove_reviews_tab( $tabs ) {
		if ( WC_Imajiner_Settings::is_enabled( 'disable_reviews' ) ) {
			unset( $tabs['reviews'] );
		}
		return $tabs;
	}

	/**
	 * Ensure product CPT does not advertise comments when reviews disabled.
	 *
	 * @param array $args Post type args.
	 * @return array
	 */
	public function filter_product_post_type_args( $args ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_reviews' ) ) {
			return $args;
		}

		if ( empty( $args['supports'] ) || ! is_array( $args['supports'] ) ) {
			return $args;
		}

		$args['supports'] = array_values( array_diff( $args['supports'], array( 'comments' ) ) );
		return $args;
	}

	/**
	 * Remove Product Reviews admin page.
	 */
	public function remove_reviews_admin_menu() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_reviews' ) ) {
			return;
		}

		remove_submenu_page( 'edit.php?post_type=product', 'product-reviews' );
	}

	/**
	 * Close comments on products when reviews disabled.
	 *
	 * @param bool $open    Whether open.
	 * @param int  $post_id Post ID.
	 * @return bool
	 */
	public function close_product_comments( $open, $post_id ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_reviews' ) ) {
			return $open;
		}

		if ( 'product' === get_post_type( $post_id ) ) {
			return false;
		}

		return $open;
	}

	/**
	 * Force coupons option off.
	 *
	 * @param mixed $value Current value.
	 * @return string|false
	 */
	public function filter_coupons_option( $value ) {
		unset( $value );

		if ( WC_Imajiner_Settings::is_enabled( 'disable_coupons' ) ) {
			return 'no';
		}

		return false;
	}

	/**
	 * Hard-disable coupons for cart/checkout/API checks.
	 *
	 * @param bool $enabled Whether coupons enabled.
	 * @return bool
	 */
	public function filter_coupons_enabled( $enabled ) {
		if ( WC_Imajiner_Settings::is_enabled( 'disable_coupons' ) ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Hide coupon CPT UI if it still registers.
	 *
	 * @param array $args Post type args.
	 * @return array
	 */
	public function filter_coupon_post_type_args( $args ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_coupons' ) ) {
			return $args;
		}

		$args['show_ui']      = false;
		$args['show_in_menu'] = false;
		$args['show_in_rest'] = false;

		return $args;
	}

	/**
	 * Remove Coupons admin menus / redirects.
	 */
	public function remove_coupons_admin_menu() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_coupons' ) ) {
			return;
		}

		remove_submenu_page( 'woocommerce', 'edit.php?post_type=shop_coupon' );
		remove_submenu_page( 'woocommerce', 'coupons-moved' );
		remove_menu_page( 'edit.php?post_type=shop_coupon' );
	}

	/**
	 * Force Analytics feature off.
	 *
	 * @param mixed $value Current value.
	 * @return string|false
	 */
	public function filter_analytics_option( $value ) {
		unset( $value );

		if ( WC_Imajiner_Settings::is_enabled( 'disable_reports' ) ) {
			return 'no';
		}

		return false;
	}

	/**
	 * Remove classic Reports menus.
	 */
	public function remove_reports_admin_menu() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_reports' ) ) {
			return;
		}

		remove_submenu_page( 'woocommerce', 'wc-reports' );
		remove_menu_page( 'wc-reports' );

		// Modern Analytics pages under WooCommerce Admin.
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/overview' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/products' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/revenue' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/orders' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/variations' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/categories' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/coupons' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/taxes' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/downloads' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/stock' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/settings' );
	}

	/**
	 * Block direct access to report/analytics admin screens.
	 */
	public function block_reports_screens() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_reports' ) || ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';

		if ( 'wc-reports' === $page ) {
			wp_safe_redirect( $this->get_admin_fallback_url() );
			exit;
		}

		if ( 'wc-admin' === $page && 0 === strpos( $path, '/analytics' ) ) {
			wp_safe_redirect( $this->get_admin_fallback_url() );
			exit;
		}
	}

	/**
	 * Remove the top-level Payments admin menu.
	 *
	 * Payment gateways remain available under WooCommerce → Settings → Payments.
	 */
	public function remove_payments_admin_menu() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_payments_menu' ) ) {
			return;
		}

		global $menu;

		$known_slugs = array(
			'wc-admin&path=/payments/connect',
			'wc-admin&path=/payments/overview',
			'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM',
		);

		foreach ( $known_slugs as $slug ) {
			remove_menu_page( $slug );
		}

		if ( ! is_array( $menu ) ) {
			return;
		}

		foreach ( $menu as $item ) {
			if ( empty( $item[2] ) || ! is_string( $item[2] ) ) {
				continue;
			}

			$slug = $item[2];
			if (
				false !== strpos( $slug, 'path=/payments' )
				|| false !== strpos( $slug, 'tab=checkout&from=PAYMENTS_MENU_ITEM' )
				|| false !== strpos( $slug, 'wc-settings&tab=checkout&from=' )
			) {
				remove_menu_page( $slug );
			}
		}
	}

	/**
	 * Remove the top-level Marketing admin menu.
	 */
	public function remove_marketing_admin_menu() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_marketing_menu' ) ) {
			return;
		}

		remove_menu_page( 'woocommerce-marketing' );
		remove_submenu_page( 'woocommerce-marketing', 'wc-admin&path=/marketing' );
		remove_submenu_page( 'woocommerce-marketing', 'edit.php?post_type=shop_coupon' );
		remove_submenu_page( 'woocommerce-marketing', 'coupons-moved' );
	}

	/**
	 * Block direct access to Marketing admin screens.
	 */
	public function block_marketing_screens() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_marketing_menu' ) || ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';

		if ( 'woocommerce-marketing' === $page ) {
			wp_safe_redirect( $this->get_admin_fallback_url() );
			exit;
		}

		if ( 'wc-admin' === $page && 0 === strpos( $path, '/marketing' ) ) {
			wp_safe_redirect( $this->get_admin_fallback_url() );
			exit;
		}
	}

	/**
	 * Remove WooCommerce → Extensions.
	 */
	public function remove_extensions_admin_menu() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_extensions_menu' ) ) {
			return;
		}

		remove_submenu_page( 'woocommerce', 'wc-addons' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/extensions' );
		remove_submenu_page( 'woocommerce', 'woocommerce-marketplace' );

		global $submenu;
		if ( empty( $submenu['woocommerce'] ) || ! is_array( $submenu['woocommerce'] ) ) {
			return;
		}

		foreach ( $submenu['woocommerce'] as $item ) {
			if ( empty( $item[2] ) || ! is_string( $item[2] ) ) {
				continue;
			}

			$slug = $item[2];
			if ( false !== strpos( $slug, 'path=/extensions' ) || 'wc-addons' === $slug ) {
				remove_submenu_page( 'woocommerce', $slug );
			}
		}
	}

	/**
	 * Remove WooCommerce → Status.
	 */
	public function remove_status_admin_menu() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_status_menu' ) ) {
			return;
		}

		remove_submenu_page( 'woocommerce', 'wc-status' );
	}

	/**
	 * Block direct access to Extensions screens.
	 */
	public function block_extensions_screens() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_extensions_menu' ) || ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';

		if ( 'wc-addons' === $page ) {
			wp_safe_redirect( $this->get_admin_fallback_url() );
			exit;
		}

		if ( 'wc-admin' === $page && 0 === strpos( $path, '/extensions' ) ) {
			wp_safe_redirect( $this->get_admin_fallback_url() );
			exit;
		}
	}

	/**
	 * Block direct access to Status screens.
	 */
	public function block_status_screens() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_status_menu' ) || ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'wc-status' === $page ) {
			wp_safe_redirect( $this->get_admin_fallback_url() );
			exit;
		}
	}

	/**
	 * Remove WooCommerce → Home.
	 */
	public function remove_home_admin_menu() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_home_menu' ) ) {
			return;
		}

		remove_submenu_page( 'woocommerce', 'wc-admin' );
		remove_submenu_page( 'woocommerce', 'woocommerce-home' );
		remove_menu_page( 'wc-admin' );

		global $submenu;
		if ( empty( $submenu['woocommerce'] ) || ! is_array( $submenu['woocommerce'] ) ) {
			return;
		}

		foreach ( $submenu['woocommerce'] as $item ) {
			if ( empty( $item[2] ) || ! is_string( $item[2] ) ) {
				continue;
			}

			$slug = $item[2];
			if ( 'wc-admin' === $slug || 'woocommerce-home' === $slug ) {
				remove_submenu_page( 'woocommerce', $slug );
			}
		}
	}

	/**
	 * Block direct access to the WooCommerce Home screen.
	 */
	public function block_home_screens() {
		if ( ! WC_Imajiner_Settings::is_enabled( 'disable_home_menu' ) || ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';

		if ( 'wc-admin' !== $page ) {
			return;
		}

		if ( '' !== $path && '/' !== $path ) {
			return;
		}

		wp_safe_redirect( $this->get_admin_fallback_url() );
		exit;
	}

	/**
	 * Hide Point of Sale and Integration from WooCommerce Settings.
	 *
	 * @param array $tabs Settings tabs.
	 * @return array
	 */
	public function filter_settings_tabs( $tabs ) {
		if ( WC_Imajiner_Settings::is_enabled( 'disable_pos_settings' ) ) {
			unset( $tabs['point-of-sale'] );
		}

		if ( WC_Imajiner_Settings::is_enabled( 'disable_integration_settings' ) ) {
			unset( $tabs['integration'] );
		}

		return $tabs;
	}

	/**
	 * Block direct access to hidden WooCommerce Settings tabs.
	 */
	public function block_settings_tabs() {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'wc-settings' !== $page ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		$hide_pos         = WC_Imajiner_Settings::is_enabled( 'disable_pos_settings' ) && 'point-of-sale' === $tab;
		$hide_integration = WC_Imajiner_Settings::is_enabled( 'disable_integration_settings' ) && 'integration' === $tab;

		if ( ! $hide_pos && ! $hide_integration ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings' ) );
		exit;
	}

	/**
	 * Fallback admin URL when WooCommerce Home is hidden.
	 *
	 * @return string
	 */
	protected function get_admin_fallback_url() {
		if ( WC_Imajiner_Settings::is_enabled( 'disable_home_menu' ) ) {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				return admin_url( 'admin.php?page=wc-orders' );
			}
			return admin_url( 'edit.php?post_type=shop_order' );
		}

		return admin_url( 'admin.php?page=wc-admin' );
	}

	/**
	 * Keep only selected order statuses in admin dropdowns / lists.
	 *
	 * Current order status is always kept so existing orders remain editable.
	 *
	 * @param array $statuses Statuses.
	 * @return array
	 */
	public function filter_order_statuses( $statuses ) {
		if ( ! empty( $GLOBALS['wc_imajiner_bypass_status_filter'] ) ) {
			return $statuses;
		}

		$enabled = WC_Imajiner_Settings::get_enabled_order_statuses();
		if ( empty( $enabled ) ) {
			return $statuses;
		}

		$filtered = array_intersect_key( (array) $statuses, array_flip( $enabled ) );

		// Preserve the status of the order currently being viewed/edited.
		$current = $this->get_current_admin_order_status_key();
		if ( $current && isset( $statuses[ $current ] ) && ! isset( $filtered[ $current ] ) ) {
			$filtered[ $current ] = $statuses[ $current ];
		}

		return ! empty( $filtered ) ? $filtered : $statuses;
	}

	/**
	 * Hide unselected statuses from admin status view tabs.
	 *
	 * @param array $statuses Registered post statuses.
	 * @return array
	 */
	public function filter_registered_order_post_statuses( $statuses ) {
		$enabled = WC_Imajiner_Settings::get_enabled_order_statuses();
		if ( empty( $enabled ) || ! is_array( $statuses ) ) {
			return $statuses;
		}

		foreach ( $statuses as $slug => $args ) {
			if ( in_array( $slug, $enabled, true ) ) {
				continue;
			}

			if ( ! is_array( $args ) ) {
				continue;
			}

			$statuses[ $slug ]['show_in_admin_status_list'] = false;
		}

		return $statuses;
	}

	/**
	 * Detect order status key for the order currently open in admin.
	 *
	 * @return string Empty string or wc- status key.
	 */
	protected function get_current_admin_order_status_key() {
		if ( ! is_admin() ) {
			return '';
		}

		$order_id = 0;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id = absint( $_GET['id'] );
		} elseif ( isset( $GLOBALS['theorder'] ) && $GLOBALS['theorder'] instanceof WC_Order ) {
			$order_id = $GLOBALS['theorder']->get_id();
		} elseif ( ! empty( $GLOBALS['post']->ID ) && 'shop_order' === $GLOBALS['post']->post_type ) {
			$order_id = (int) $GLOBALS['post']->ID;
		}

		if ( ! $order_id ) {
			return '';
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return '';
		}

		$status = $order->get_status();
		return ( 0 === strpos( $status, 'wc-' ) ) ? $status : 'wc-' . $status;
	}

	/**
	 * Force guest checkout off.
	 *
	 * @param mixed $value Current value.
	 * @return string|false
	 */
	public function filter_guest_checkout_option( $value ) {
		unset( $value );

		if ( WC_Imajiner_Settings::is_enabled( 'require_login_checkout' ) ) {
			return 'no';
		}

		return false;
	}

	/**
	 * Allow registration on checkout / My Account when login is required.
	 *
	 * @param mixed $value Current value.
	 * @return string|false
	 */
	public function filter_checkout_signup_option( $value ) {
		unset( $value );

		if ( WC_Imajiner_Settings::is_enabled( 'require_login_checkout' ) ) {
			return 'yes';
		}

		return false;
	}

	/**
	 * Require registration at checkout.
	 *
	 * @param bool $required Whether required.
	 * @return bool
	 */
	public function filter_checkout_registration_required( $required ) {
		if ( WC_Imajiner_Settings::is_enabled( 'require_login_checkout' ) ) {
			return true;
		}

		return $required;
	}

	/**
	 * Keep only BACS on the storefront.
	 *
	 * @param array $gateways Gateways.
	 * @return array
	 */
	public function filter_available_gateways( $gateways ) {
		if ( ! WC_Imajiner_Settings::is_enabled( 'default_bacs_payment' ) ) {
			return $gateways;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $gateways;
		}

		if ( isset( $gateways['bacs'] ) ) {
			return array( 'bacs' => $gateways['bacs'] );
		}

		return $gateways;
	}
}
