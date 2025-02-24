<?php

class LP_Certificate_DB extends LP_Database {
	public static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function add_data_cert_to_user_items( $data_user_item_cert ) {
		$result = $this->wpdb->insert(
			self::getInstance()->tb_lp_user_items,
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
	 * @deprecated 4.0.9
	 */
	public function get_order_id_of_cert_course( $data = array() ) {
		_deprecated_function( __METHOD__, '4.0.9' );
		return null;
		if ( ! isset( $data['user_id'] ) || ! isset( $data['cert_id'] ) || ! isset( $data['course_id'] ) ) {
			return null;
		}

		$query = $this->wpdb->prepare(
			"SELECT ref_id
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
			       ORDER BY user_item_id DESC
			       LIMIT 1)
			ORDER BY ref_id DESC
			LIMIT 1",
			$data['user_id'],
			$data['cert_id'],
			'lp_certificate',
			LP_ORDER_CPT,
			LP_COURSE_CPT,
			$data['course_id'],
			$data['user_id'],
			'finished'
		);

		$result = $this->wpdb->get_row( $query );

		return $result;
	}

	/**
	 * Get Courses
	 *
	 * @param LP_Certificate_Filter $filter
	 * @param int $total_rows return total_rows
	 *
	 * @return array|null|int|string
	 * @throws Exception
	 * @author tungnx
	 * @version 1.0.1
	 * @since 4.0.2
	 */
	public function query_certificates( LP_Certificate_Filter $filter, int &$total_rows = 0 ) {
		$default_fields = $this->get_cols_of_table( $this->tb_posts );
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_posts;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'cer';
		}

		// Where
		$filter->where[] = $this->wpdb->prepare( 'AND cer.post_type = %s', $filter->post_type );

		// Find certificate publish.
		$filter->where[] = $this->wpdb->prepare( 'AND cer.post_status = %s', 'publish' );

		$filter = apply_filters( 'lp/certificate/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
