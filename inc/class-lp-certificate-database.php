<?php

class LP_Certificate_DB {
	public static $_instance;
	public $wpdb;
	public $tb_lp_user_items;
	public $tb_lp_user_itemmeta;
	public $tb_lp_order_items;
	public $tb_postmeta;
	public $tb_posts;

	private function __construct() {
		/**
		 * @global wpdb
		 */
		global $wpdb;

		$this->wpdb                 = $wpdb;
		$this->tb_posts             = $this->wpdb->posts;
		$this->tb_postmeta          = $this->wpdb->postmeta;
		$this->tb_lp_user_items     = $this->wpdb->prefix . 'learnpress_user_items';
		$this->tb_lp_user_itemmeta  = $this->wpdb->prefix . 'learnpress_user_itemmeta';
		$this->tb_lp_order_items    = $this->wpdb->prefix . 'learnpress_order_items';
		$this->tb_lp_order_itemmeta = $this->wpdb->prefix . 'learnpress_order_itemmeta';
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function add_data_cert_to_user_items( $data_user_item_cert ) {
		$result = $this->wpdb->insert(
			LP_Certificate_DB::getInstance()->tb_lp_user_items,
			$data_user_item_cert,
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result;
	}

	/**
	 * Get lp order id of certificate
	 *
	 * @param array $data
	 *
	 * @return array|object|void|null
	 */
	public function get_order_id_of_cert_course( $data = array() ) {
		if ( ! isset( $data['user_id'] ) || ! isset( $data['cert_id'] ) || ! isset( $data['course_id'] ) ) {
			return null;
		}

		$query = $this->wpdb->prepare( "
					SELECT ref_id
					FROM {$this->tb_lp_user_items}
					WHERE user_id = %d
					  AND item_id = %d
					  AND item_type = %s
					  AND ref_type = %s
					  AND parent_id =
						  (SELECT user_item_id
						   FROM {$this->tb_lp_user_items}
						   WHERE item_type = %s
							 AND item_id = %d
							 AND user_id = %d
							 AND status = %s
							 ORDER BY user_item_id DESC LIMIT 1)
					ORDER BY ref_id DESC
					LIMIT 1",
			$data['user_id'], $data['cert_id'], 'lp_certificate',
			LP_ORDER_CPT, LP_COURSE_CPT, $data['course_id'], $data['user_id'], 'finished' );

		$result = $this->wpdb->get_row( $query );

		return $result;
	}
}

LP_Certificate_DB::getInstance();