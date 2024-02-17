<?php

/**
 * Class LP_Certificate_Order
 *
 * @author  tungnx
 * @version 1.0
 * @since   3.1.4
 */
class LP_Certificate_Order {

	protected static $_instance;

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'learn-press/checkout-order-processed', array( $this, 'lp_add_user_items' ), 11, 2 );
		add_action( 'learn-press/checkout/oder_item_name', array( $this, 'lp_order_cert_item_name' ), 11, 3 );
		add_action( 'learn-press/added-order-item-data', array( $this, 'lp_cert_add_order_meta' ), 10, 3 );

		add_filter( 'learn-press/order-item-not-course', array( $this, 'lp_order_cert_item' ), 10, 1 );
		add_filter( 'learn-press/order-item-not-course-id', array( $this, 'lp_order_cert_item_link_not_course_id' ), 10, 2 );
		add_filter( 'learn_press/order_detail_item_link', array( $this, 'lp_order_cert_item_link' ), 10, 2 );
		add_filter( 'learn-press/order-item-link', array( $this, 'lp_order_cert_item_link' ), 10, 2 );
		add_filter( 'learn-press/order-received-item-link', array( $this, 'lp_order_cert_item_link' ), 10, 2 );
		add_filter( 'learn-press/order/item-visible', array( $this, 'lp_cert_frontend_item_visible' ), 10, 2 );

		//add_action( 'learn-press/order/status-completed', array( $this, 'lp_order_status_change_to_completed' ) );
	}

	public function lp_order_status_change_to_completed( $lp_order_id ) {

		$lp_order = learn_press_get_order( $lp_order_id );
		$items    = $lp_order->get_items();

		foreach ( $items as $item ) {
			$cert_id = learn_press_get_order_item_meta( $item['id'], '_lp_cert_id', true );

			// Check is cert
			$cert = get_post( $cert_id );

			if ( $cert->post_type == 'lp_cert' ) {
				$course_id = learn_press_get_order_item_meta( $item['id'], '_course_id', true );
				learn_press_update_order_item_meta( $item['id'], '_lp_course_id_of_cert', $course_id );
				global $wpdb;

				$query = $wpdb->prepare( "
					DELETE FROM wp_learnpress_order_itemmeta 
					WHERE learnpress_order_item_id = %s
						AND meta_key = '_course_id'
						AND meta_value = %s",
					$item['id'], $course_id );

				$result = $wpdb->query( $query );

				//remove_action( 'learn-press/order/status-changed', array( 'LP_User_Factory', 'update_user_items' ), 10, 3 );
			}
		}
	}

	/**
	 * @param string $order_item_title
	 * @param array $cart_item
	 *
	 * @return string
	 */
	public function lp_order_cert_item_name( $order_item_title = '', $cart_item = array() ) {
		if ( isset( $cart_item['_learnpress_certificate_id'] ) ) {
			$order_item_title = sprintf( '%s %s', __( 'Certificate:', 'learnpress-certificates' ), get_the_title( $cart_item['_learnpress_certificate_id'] ) );
		}

		return $order_item_title;
	}

	/**
	 * @param int $item_id
	 * @param array $item
	 * @param int $order_id
	 *
	 * @return int|mixed
	 */
	public function lp_cert_add_order_meta( $item_id = 0, $item = array(), $order_id = 0 ) {
		if ( isset( $item['_learnpress_certificate_id'] ) ) {

			learn_press_add_order_item_meta( $item_id, '_lp_cert_id', $item['_learnpress_certificate_id'] );

			/**
			 * Remove meta_key _course_id and add meta_key _lp_course_id_of_cert
			 *
			 * Reason: 'auto_enroll' function hook on action 'learn-press/order/status-completed
			 * will will get key 'course_id' to set row item_type lp_course and set status to 'enrolled' on table learnpress_user_items
			 */
			$course_id = learn_press_get_order_item_meta( $item_id, '_course_id', true );
			learn_press_update_order_item_meta( $item_id, '_lp_course_id_of_cert', $course_id );
			global $wpdb;

			$query = $wpdb->prepare( "
				DELETE FROM wp_learnpress_order_itemmeta 
				WHERE learnpress_order_item_id = %s
					AND meta_key = '_course_id'
					AND meta_value = %s",
				$item_id, $course_id );

			$result = $wpdb->query( $query );
			// End
		}

		return $item_id;
	}

	/**
	 * Add info certificate to table learnpress_user_items && learnpress_user_itemmeta
	 *
	 * @param int $order_id
	 * @param LP_Checkout $lp_checkout
	 */
	public function lp_add_user_items( $order_id = 0, $lp_checkout = null ) {
		$lp_cart = LP_Cart::instance()->get_cart();

		$current_user = learn_press_get_current_user_id();

		foreach ( $lp_cart as $cart_item ) {

			if ( isset( $cart_item['_learnpress_certificate_id'] ) ) {
				// Remove action auto enroll course (because it make change status of course to enroll instead of completed)
				//remove_action( 'learn-press/order/status-changed', array( 'LP_User_Factory', 'update_user_items' ), 10 );

				$course_id = $cart_item['item_id'];

				$user_item = learn_press_get_user_item( array(
					'user_id'  => $current_user,
					'item_id'  => $course_id,
					'ref_type' => LP_ORDER_CPT
				), true );

				$data_user_item_cert = array(
					'user_id'   => $current_user,
					'item_id'   => $cart_item['_learnpress_certificate_id'],
					'item_type' => 'lp_certificate',
					'ref_id'    => $order_id,
					'ref_type'  => 'lp_order',
					'parent_id' => $user_item->user_item_id
				);

				LP_Certificate_DB::getInstance()->add_data_cert_to_user_items( $data_user_item_cert );
			}
		}
	}

	/**
	 * Template order certificate item
	 *
	 * @param array $item
	 *
	 * @return void
	 */
	public function lp_order_cert_item( $item = array() ) {
		//extract( array( 'item' => $item ) );
		include_once LP_ADDON_CERTIFICATES_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'order' . DIRECTORY_SEPARATOR . 'order-item.php';
	}

	/**
	 * Link certificate item LP Order if not meta_key _course_id
	 *
	 * @param string $link
	 * @param array $item
	 *
	 * @return string
	 */
	public function lp_order_cert_item_link_not_course_id( $link, $item ) {
		$user = wp_get_current_user();

		if ( ! $user ) {
			return $link;
		}

		if ( isset( $item['_lp_cert_id'] ) && isset( $item['_lp_course_id_of_cert'] ) ) {
			$edit_post_link = get_edit_post_link( $item['_lp_cert_id'] );
			$cert_title     = get_the_title( $item['_lp_cert_id'] );
			$course_title   = get_the_title( $item['_lp_course_id_of_cert'] );

			if ( empty( $edit_post_link ) ) {
				$cert_title = "(#{$item['_lp_cert_id']}) not exits";
			}

			if ( ! is_admin() ) {
				$edit_post_link = get_permalink( $item['_lp_course_id_of_cert'] );

				if ( empty( $edit_post_link ) ) {
					$course_title = "(#{$item['_lp_course_id_of_cert']}) not exits";
				}
			}

			$title = sprintf( '%s: %s - %s', __( 'Certificate', 'learnpress-certificates' ), $cert_title, $course_title );
			$link  = '<a href="' . $edit_post_link . '">' . $title . '</a>';
		}

		// For old version < 3.1.4
		if ( isset( $item['learnpress_certificate_bought'] ) && get_post_type( $item['learnpress_certificate_bought'] ) === LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$edit_post_link = get_edit_post_link( $item['learnpress_certificate_bought'] );
			$title          = sprintf( '%s: %s', __( 'Certificate', 'learnpress-certificates' ), get_the_title( $item['learnpress_certificate_bought'] ) );
			$link           = '<a href="' . $edit_post_link . '">' . $title . '</a>';
		}

		return $link;
	}

	/**
	 * Link certificate item LP Order
	 *
	 * @param string $link
	 * @param array $item
	 *
	 * @return string
	 */
	public function lp_order_cert_item_link( $link, $item ) {
		if ( isset( $item['_lp_cert_id'] ) ) {
			$edit_post_link = get_edit_post_link( $item['_lp_cert_id'] );
			$cert_title     = get_the_title( $item['_lp_cert_id'] );
			$course_title   = get_the_title( $item['course_id'] );
			$title          = sprintf( '%s %s - %s %s', __( 'Certificate:', 'learnpress-certificates' ), $cert_title, __( 'Course:', 'learnpress-certificates' ), $course_title );
			$link           = '<a href="' . $edit_post_link . '">' . $title . '</a>';
		}

		// For old version < 3.1.4
		if ( isset( $item['learnpress_certificate_bought'] ) && get_post_type( $item['learnpress_certificate_bought'] ) === LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$edit_post_link = get_edit_post_link( $item['learnpress_certificate_bought'] );
			$title          = sprintf( '%s %s - %s %s', __( 'Certificate:', 'learnpress-certificates' ), get_the_title( $item['learnpress_certificate_bought'] ), __( 'Course:', 'learnpress-certificates' ), get_the_title( $item['course_id'] ) );
			$link           = '<a href="' . $edit_post_link . '">' . $title . '</a>';
		}

		return $link;
	}

	public function lp_cert_frontend_item_visible( $return, $item ) {
		if ( isset( $item['learnpress_certificate_bought'] ) && get_post_type( $item['learnpress_certificate_bought'] ) === LP_ADDON_CERTIFICATES_CERT_CPT ) {
			return false;
		}

		return $return;
	}
}

LP_Certificate_Order::getInstance();