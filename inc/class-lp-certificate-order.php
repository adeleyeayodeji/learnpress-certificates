<?php

use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;

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
		//add_action( 'learn-press/checkout-order-processed', array( $this, 'lp_add_user_items' ), 11, 2 );
		add_action( 'lp/order-completed/update/user-item', array( $this, 'lp_user_cer_update' ), 10, 3 );
		add_action( 'lp/order-pending/update/user-item', array( $this, 'lp_user_cer_update' ), 10, 3 );
		// add_action( 'learn-press/checkout/oder_item_name', array( $this, 'lp_order_cert_item_name' ), 11, 3 );
		// add order meta_data is not course
		add_action( 'learn-press/added-order-item-data', array( $this, 'lp_cert_add_order_meta' ), 10, 3 );

		add_filter( 'learn-press/order-item-not-course', array( $this, 'lp_order_cert_item' ), 10, 1 );
		add_filter( 'learn-press/order-item-not-course-id', array(
			$this,
			'lp_order_cert_item_link_not_course_id'
		), 10, 2 );
		add_filter( 'learn_press/order_detail_item_link', array( $this, 'lp_order_cert_item_link' ), 10, 2 );
		add_filter( 'learn-press/order-item-link', array( $this, 'lp_order_cert_item_link' ), 10, 2 );
		add_filter( 'learn-press/order-received-item-link', array( $this, 'lp_order_cert_item_link' ), 10, 2 );
		add_filter( 'learn-press/order/item-visible', array( $this, 'lp_cert_frontend_item_visible' ), 10, 2 );

		// edit: minhpd : 15-1-2022;
		// add type item can purchase
		add_filter( 'learn-press/purchase/item-types/can-purchase', array(
			$this,
			'lp_cert_add_item_can_purchase'
		), 10, 1 );

		// add item meta order with item is not course
		add_filter( 'learnpress/order/add-item/item_type_lp_cert', array( $this, 'lp_cert_order_add_item' ), 10, 1 );

		// Delete user certificate when delete order has certificate
		add_action( 'learn-press/order/before-delete', [ $this, 'delete_user_certificate' ], 10, 2 );
	}

	/**
	 * @param array $items
	 */
	public function lp_cert_add_item_can_purchase( $items ) {

		$items[] = LP_ADDON_CERTIFICATES_CERT_CPT;

		return $items;
	}

	/**
	 * @param array $items : item meta order
	 */
	public function lp_cert_order_add_item( $item ) {
		if ( get_post_type( $item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$price_cert              = get_post_meta( $item['item_id'], '_lp_certificate_price', true );
			$item['quantity']        = absint( $item['quantity'] );
			$subtotal                = $price_cert * absint( $item['quantity'] );
			$item['item_type']       = get_post_type( $item['item_id'] );
			$item['subtotal']        = $subtotal;
			$item['total']           = $subtotal;
			$item['order_item_name'] = sprintf( '%s %s', __( 'Certificate:', 'learnpress-certificates' ), get_the_title( $item['item_id'] ) );
		}

		return $item;
	}

	/**
	 * @param int $item_id
	 * @param array $item
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function lp_cert_add_order_meta( $item_id = 0, $item = array(), $order_id = 0 ) {
		if ( get_post_type( $item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			if ( isset( $item['course_id'] ) ) {
				learn_press_add_order_item_meta( $item_id, '_lp_cert_id', $item['item_id'] );
				learn_press_add_order_item_meta( $item_id, '_lp_course_id_of_cert', $item['course_id'] );
			}
		}
	}

	/**
	 * Add info certificate to table learnpress_user_items && learnpress_user_itemmeta
	 *
	 * @param int $order_id
	 * @param LP_Checkout $lp_checkout
	 *
	 * @deprecated 4.0.9 instead by lp_add_user_cer
	 */
	public function lp_add_user_items( int $order_id = 0, LP_Checkout $lp_checkout = null ) {
		_deprecated_function( __METHOD__, '4.0.9', 'lp_add_user_cer' );

		return;
		$lp_cart = LP_Cart::instance()->get_cart_from_session();

		$current_user = learn_press_get_current_user_id();

		foreach ( $lp_cart as $cart_item ) {

			if ( get_post_type( $cart_item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
				// Remove action auto enroll course (because it make change status of course to enroll instead of completed)
				// remove_action( 'learn-press/order/status-changed', array( 'LP_User_Factory', 'update_user_items' ), 10 );

				$course_id = $cart_item['course_id'];

				$user_item = learn_press_get_user_item(
					array(
						'user_id'  => $current_user,
						'item_id'  => $course_id,
						'ref_type' => LP_ORDER_CPT,
					)
				);

				$data_user_item_cert = array(
					'user_id'   => $current_user,
					'item_id'   => $cart_item['item_id'],
					'item_type' => 'lp_certificate',
					'status'    => 'completed',
					'ref_id'    => $order_id,
					'ref_type'  => 'lp_order',
					'parent_id' => $user_item->user_item_id,
				);

				LP_Certificate_DB::getInstance()->add_data_cert_to_user_items( $data_user_item_cert );
			}
		}
	}

	/**
	 * Add info certificate to table learnpress_user_items && learnpress_user_itemmeta
	 * Hook use from LP v4.2.6.5
	 *
	 * @param array $item
	 * @param LP_Order $lp_order
	 * @param LP_User $user
	 *
	 * @since 4.0.9
	 * @version 1.0.0
	 */
	public function lp_user_cer_update( array $item, $lp_order, $user ) {
		if ( ! isset( $item['_lp_cert_id'] ) ) {
			return;
		}

		try {
			$course_id = $item['_lp_course_id_of_cert'] ?? 0;

			$filter_user_course            = new LP_User_Items_Filter();
			$filter_user_course->user_id   = $user->get_id();
			$filter_user_course->item_id   = $course_id;
			$filter_user_course->item_type = LP_COURSE_CPT;
			$user_course                   = UserCourseModel::get_user_item_model_from_db( $filter_user_course );
			if ( ! $user_course ) {
				return;
			}

			// Check exists certificate bought
			$filter_user_cer            = new LP_User_Items_Filter();
			$filter_user_cer->user_id   = $user->get_id();
			$filter_user_cer->item_id   = $item['_lp_cert_id'];
			$filter_user_cer->item_type = 'lp_certificate';
			$filter_user_cer->parent_id = $user_course->get_user_item_id();
			$user_cer                   = UserItemModel::get_user_item_model_from_db( $filter_user_cer );

			if ( ! $user_cer ) {
				// Insert data to table learnpress_user_items
				$user_cer_new            = new UserItemModel();
				$user_cer_new->user_id   = $user->get_id();
				$user_cer_new->item_id   = $item['_lp_cert_id'];
				$user_cer_new->item_type = 'lp_certificate';
				$user_cer_new->status    = $lp_order->get_status();
				$user_cer_new->ref_id    = $lp_order->get_id();
				$user_cer_new->ref_type  = LP_ORDER_CPT;
				$user_cer_new->parent_id = $user_course->get_user_item_id();
				$user_cer_new->save();
			} else {
				// If exists certificate bought before, update status and lp order id new
				if ( $lp_order->get_id() !== $user_cer->ref_id && $user_cer->ref_type === LP_ORDER_CPT ) {
					$user_cer->ref_id = $lp_order->get_id();
				}

				$user_cer->status = $lp_order->get_status();
				$user_cer->save();
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
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
		extract( array( 'item' => $item ) );
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
				$edit_post_link = '#';
			}

			if ( ! is_admin() ) {
				$edit_post_link = get_permalink( $item['_lp_course_id_of_cert'] );

				if ( empty( $edit_post_link ) ) {
					$edit_post_link = '#';
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

	/**
	 * Delete user item certificate when delete lp order has certificate
	 *
	 * @param LP_Order $lp_order
	 * @param $user_id
	 *
	 * @return void
	 * @since 4.0.9
	 * @version 1.0.0
	 */
	public function delete_user_certificate( $lp_order, $user_id ) {
		try {
			$items = $lp_order->get_items();
			foreach ( $items as $item ) {
				if ( isset( $item['_lp_cert_id'] ) ) {
					$cert_id = $item['_lp_cert_id'];

					$lp_db                     = LP_Database::getInstance();
					$filter_delete             = new LP_User_Items_Filter();
					$filter_delete->collection = $lp_db->tb_lp_user_items;
					$filter_delete->where[]    = $lp_db->wpdb->prepare(
						'AND `item_id` = %d
						AND `item_type` = %s
						AND `user_id` = %d
						AND `ref_id` = %d
						AND `ref_type` = %s',
						$cert_id,
						'lp_certificate',
						$user_id,
						$lp_order->get_id(),
						LP_ORDER_CPT
					);

					LP_Database::getInstance()->delete_execute( $filter_delete );
				}
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
}

LP_Certificate_Order::getInstance();
