<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Certificate_WC
 */
class LP_Certificate_WC {
	protected static $_instance;

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		// check plugin LP - Woo installed
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( is_plugin_active( 'learnpress-woo-payment/learnpress-woo-payment.php' )
			&& is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			if ( LP()->settings->get( 'woo-payment.enable' ) ) {
				add_filter( 'learn-press/woo-course-price', array( $this, 'lp_cert_set_price_woo' ), 11, 2 );

				//dd_action( 'woocommerce_add_to_cart', array( $this, 'add_to_cart' ), 11, 6 );

				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_certificate_info_to_order_meta' ), 10, 2 );
				add_action( 'learn-press/woo-checkout-create-lp-order-processed', array( $this, 'lp_add_user_items' ), 11, 2 );
				add_action( 'learn-press/added-order-item-data', array( $this, 'lp_cert_add_order_meta' ), 10, 3 );
				add_action( 'woocommerce_cart_item_name', array( $this, 'lp_certificate_title_cart_woo' ), 10, 2 );
				add_action( 'woocommerce_cart_item_thumbnail', array( $this, 'lp_certificate_image_cart_woo' ), 10, 2 );

				/*** Apply for add cert_id to cart ***/
				//add_filter( 'woocommerce_product_class', array( $this, 'product_class' ), 10, 4 );
				//add_filter( 'woocommerce_get_product_from_item', array( $this, 'learnpress_woo_payment_woocommerce_get_product_from_item_callback' ), 10, 3 );
				/*** End apply for add cert_id to cart ***/

				//add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'create_lp_order' ), 10, 2 );
			}
		}
	}

	public function lp_cert_set_price_woo( $price, $course ) {
		$wc_cart = WC()->cart;

		if ( empty( $wc_cart ) ) {
			return $price;
		}

		$cart_arr = $wc_cart->get_cart();

		foreach ( $cart_arr as $key => $cart_item ) {
			if ( $cart_item['product_id'] == $course->get_id() && isset( $cart_item['lp_cert_id'] ) ) {

				$price = get_post_meta( $cart_item['lp_cert_id'], '_lp_certificate_price', true );
			}
		}

		return $price;
	}

	public function add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		$wc_cart = WC()->cart;

		if ( isset( $_REQUEST['lp_cert_id'] ) ) {
			$wc_cart->cart_contents[ $cart_item_key ]['lp_cert_id'] = absint( wp_unslash( $_REQUEST['lp_cert_id'] ) );

			$wc_cart->set_session();
		}
	}

	/**
	 * @param $order_id
	 * @param $data
	 */
	public function update_certificate_info_to_order_meta( $order_id, $data ) {
		$wc_cart = WC()->cart->get_cart();
		$order   = new WC_Order( $order_id );
		$items   = $order->get_items();

		try {
			foreach ( $items as $item ) {
				$product       = $item->get_data();
				$product_id    = $product['product_id'];
				$cart_item_key = WC()->cart->generate_cart_id( $product_id );

				if ( array_key_exists( $cart_item_key, $wc_cart ) ) {
					$cart_item = $wc_cart[ $cart_item_key ];

					if ( isset( $cart_item['is_lp_cert_product'] ) ) {
						wc_add_order_item_meta( $item->get_id(), '_is_lp_cert_product', $cart_item['is_lp_cert_product'] );
					}

					if ( isset( $cart_item['lp_course_id_of_cert'] ) ) {
						wc_add_order_item_meta( $item->get_id(), '_lp_course_id_of_cert', $cart_item['lp_course_id_of_cert'] );
					}

					if ( isset( $cart_item['lp_cert_id'] ) ) {
						wc_add_order_item_meta( $item->get_id(), '_lp_cert_id', $cart_item['lp_cert_id'] );
					}
				}
			}
		}
		catch ( Exception $e ) {

		}
	}

	/**
	 * Add info certificate to table learnpress_user_items && learnpress_user_itemmeta
	 *
	 * @param int              $lp_order_id
	 * @param LP_Checkout|null $lp_checkout
	 */
	public function lp_add_user_items( $lp_order_id = 0, $lp_checkout = null ) {
		$woo_order_id = get_post_meta( $lp_order_id, '_woo_order_id', true );

		if ( ! empty( $woo_order_id ) ) {
			$wc_oder = wc_get_order( $woo_order_id );
			$items   = $wc_oder->get_items();

			$current_user = wp_get_current_user()->ID;

			foreach ( $items as $item ) {

				if ( isset( $item['_lp_cert_id'] ) ) {
					//remove_action( 'learn-press/order/status-changed', array( 'LP_User_Factory', 'update_user_items' ), 10 );

					$product   = $item->get_data();
					$course_id = $product['product_id'];

					$user_item = learn_press_get_user_item( array(
						'user_id'  => $current_user,
						'item_id'  => $course_id,
						'ref_type' => LP_ORDER_CPT
					), true );

					$data_user_item_cert = array(
						'user_id'   => $current_user,
						'item_id'   => $item['_lp_cert_id'],
						'item_type' => 'lp_certificate',
						'ref_id'    => $lp_order_id,
						'ref_type'  => 'lp_order',
						'parent_id' => $user_item->user_item_id
					);

					LP_Certificate_DB::getInstance()->add_data_cert_to_user_items( $data_user_item_cert );
				}
			}
		}
	}

	public function lp_cert_add_order_meta( $item_id = 0, $item = array(), $lp_order_id = 0 ) {
		$woo_order_id = get_post_meta( $lp_order_id, '_woo_order_id', true );

		if ( ! empty( $woo_order_id ) ) {
			$wc_oder = wc_get_order( $woo_order_id );
			$items   = $wc_oder->get_items();

			foreach ( $items as $item ) {
				if ( isset( $item['_lp_cert_id'] ) ) {

					$product       = $item->get_data();
					$woo_course_id = $product['product_id'];

					$course_id = learn_press_get_order_item_meta( $item_id, '_course_id', true );

					if ( $woo_course_id == $course_id ) {
						learn_press_add_order_item_meta( $item_id, '_lp_cert_id', $item['_lp_cert_id'] );

						/**
						 * Remove meta_key _course_id and add meta_key _lp_course_id_of_cert
						 *
						 * Reason: 'auto_enroll' function hook on action 'learn-press/order/status-completed
						 * will will get key 'course_id' to set row item_type lp_course and set status to 'enrolled' on table learnpress_user_items
						 */
						$course_id = learn_press_get_order_item_meta( $item_id, '_course_id', true );
						learn_press_update_order_item_meta( $item_id, '_lp_course_id_of_cert', $course_id );
						global $wpdb;

						$query = $wpdb->prepare( "DELETE FROM wp_learnpress_order_itemmeta WHERE
										learnpress_order_item_id = %s
										AND meta_key = '_course_id'
										AND meta_value = %s", $item_id, $course_id );

						$result = $wpdb->query( $query );
						// End
					}
				}
			}
		}
	}

	public function product_class( $classname, $product_type, $post_type, $product_id ) {
		if ( 'lp_cert' == get_post_type( $product_id ) ) {
			$classname = 'WC_Product_LP_Certificate';
		}

		return $classname;
	}

	function learnpress_woo_payment_woocommerce_get_product_from_item_callback( $product, $item, $order ) {
		if ( get_class( $item ) !== 'WC_Order_Item_LP_Cert' ) {
			$cert_id = wc_get_order_item_meta( $item->get_id(), '_lp_cert_id', true );

			if ( $cert_id && 'lp_cert' == get_post_type( $cert_id ) ) {
				$product = new WC_Product_LP_Certificate( $cert_id );
			}
		}

		return $product;
	}

	public function lp_certificate_title_cart_woo( $product_link, $cart_item ) {
		if ( isset( $cart_item['lp_cert_id'] ) && isset( $cart_item['lp_course_id_of_cert'] ) ) {
			$cert_title   = get_the_title( $cart_item['lp_cert_id'] );
			$course_title = get_the_title( $cart_item['lp_course_id_of_cert'] );

			$product_title = sprintf( '%s: %s - %s', __( 'Certificate', 'learnpress-certificates' ), $cert_title, $course_title );
			$product_link  = apply_filters( 'learn-press/lp-cert-woo-link-product', '<a href="' . get_permalink( $cart_item['lp_course_id_of_cert'] ) . '">' . $product_title . '</a>', $cart_item );
		}

		return $product_link;
	}

	public function lp_certificate_image_cart_woo( $image, $cart_item ) {
		if ( isset( $cart_item['lp_cert_id'] ) ) {
			$cert_bg_img = get_post_meta( $cart_item['lp_cert_id'], '_lp_cert_template', true );

			if ( ! empty( $cert_bg_img ) ) {
				$image = '<img src="' . $cert_bg_img . '" width="300" height="300" />';
			} else {
				$image = wc_placeholder_img();
			}
		}

		return apply_filters( 'woocommerce_product_get_image', $image, $cart_item );
	}
}

LP_Certificate_WC::getInstance();
