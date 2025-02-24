<?php

use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;

/**
 * Class LP_Certificate
 */
class LP_Certificate {

	/**
	 * Certificate post ID
	 *
	 * @var int
	 */
	protected $_id = 0;

	/**
	 * Layers
	 *
	 * @var null
	 */
	protected $_layers = null;

	protected $template = '';

	/**
	 * @var array|WP_Post|null
	 */
	public $data_post = null;

	/**
	 * LP_Certificate constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {

		// Validation
		if ( LP_ADDON_CERTIFICATES_CERT_CPT !== get_post_type( $id ) ) {
			return;
		}

		$this->data_post = get_post( $id );
		$this->_id       = $id;
		$this->get_layers();
	}

	/**
	 * Get certificate id.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->_id;
	}

	public function get_template() {

		if ( ! $this->template ) {
			$template = LP_Addon_Certificates::get_link_cert_bg_by_course( $this->get_id() );
			//$template = preg_replace( '~^https?://~', is_ssl() ? 'https://' : 'http://', $template );

			if ( ! $template ) {
				$template = plugins_url( '/assets/images/template-default.png', LP_ADDON_CERTIFICATES_FILE );
			}
			$this->template = $template;
		}

		return $this->template;
	}

	public function get_cert_thumbnail() {
		$thumbnail = get_post_meta( $this->get_id(), '_lp_cert_thumbnail', true );
		if ( ! $thumbnail ) {
			$thumbnail = $this->get_template();
		}

		return $thumbnail;
	}

	/**
	 * Get all layers of certificate.
	 *
	 * @return array
	 */
	public function get_layers() {
		if ( ! $this->_layers ) {
			$layers = $this->get_raw_layers();

			if ( $layers ) {
				foreach ( $layers as $layer ) {
					$layer = $this->get_layer( $layer );

					if ( $layer ) {
						$this->_layers[ $layer->get_name() ] = $layer;
					}
				}
			}
		}

		return $this->_layers;
	}

	/**
	 * Get raw layer from post meta.
	 *
	 * @return mixed
	 */
	public function get_raw_layers() {
		$layers = wp_cache_get( 'certificate-' . $this->get_id(), 'certificates' );

		if ( false === $layers ) {
			$layers = get_post_meta( $this->_id, '_lp_cert_layers', true );

			if ( $layers ) {
				if ( is_array( $layers ) ) {
					foreach ( $layers as $k => $layer ) {
						$layer = (array) $layer;
						if ( array_key_exists( 'variable', $layer ) && $layer['variable'] ) {
							$layers[ $k ]['text'] = $layer['variable'];
						}
					}
				} else {
					$layers = array();
				}
			}
		}

		return $layers;
	}

	/**
	 * Get thumbnail preview of a certificate.
	 *
	 * @return string
	 */
	public function get_preview() {
		//$preview = get_post_meta( $this->get_id(), '_lp_cert_preview', true );

		//if ( ! $preview ) {
			$preview = LP_Addon_Certificates::get_link_cert_bg_by_course( $this->get_id() );
		//}

		if ( ! $preview ) {
			$preview = plugins_url( '/assets/images/no-image.png', LP_ADDON_CERTIFICATES_FILE );
		}

		return $preview;
	}

	/**
	 * Get name's certificate.
	 *
	 * @return string|bool
	 */
	public function get_name() {
		$post = get_post( $this->get_id() );

		return $post ? $post->post_name : false;
	}

	/**
	 * Get title's certificate.
	 *
	 * @return string
	 */
	public function get_title() {
		return get_the_title( $this->get_id() );
	}

	/**
	 * Get description of certificate.
	 *
	 * @return bool|string
	 */
	public function get_desc() {
		$post = get_post( $this->get_id() );
		$desc = $post ? $post->post_excerpt : '';

		return $desc;
	}

	public function get_author() {
		$author = new WP_User( get_post_field( 'post_author', $this->get_id() ) );

		return $author->display_name;
	}

	/**
	 * Get layer by options.
	 *
	 * @param array|string $options
	 *
	 * @return bool|LP_Certificate_Layer
	 */
	public function get_layer( $options ) {
		if ( is_string( $options ) ) {
			return ! empty( $this->_layers[ $options ] ) ? $this->_layers[ $options ] : false;
		}

		settype( $options, 'array' );

		$name = ! empty( $options['fieldType'] ) ? $options['fieldType'] : 'custom';
		if ( ! $name ) {
			return false;
		}
		$class_file = str_replace( '_', '-', $name );
		$class      = ucwords( preg_replace( '/_|-/', ' ', $name ) );
		$class      = 'LP_Certificate_' . str_replace( ' ', '_', $class ) . '_Layer';

		if ( ! class_exists( $class ) ) {
			if ( file_exists( LP_ADDON_CERTIFICATES_PATH . '/inc/layers/class-lp-' . $class_file . '-layer.php' ) ) {
				include_once LP_ADDON_CERTIFICATES_PATH . '/inc/layers/class-lp-' . $class_file . '-layer.php';
			}
		}

		if ( ! class_exists( $class ) ) {
			$class = 'LP_Certificate_Layer';
		}

		return new $class( $options );
	}

	/**
	 * Remove a layer by name.
	 *
	 * @param $name
	 */
	public function remove_layer( $name ) {
		$layers = $this->get_layers();

		if ( ! $layers ) {
			return;
		}

		if ( empty( $layers[ $name ] ) ) {
			return;
		}

		unset( $layers[ $name ] );

		$layer_data = array();

		if ( $layers ) {
			foreach ( $layers as $layer ) {
				$layer_data[ $layer->get_name() ] = $layer->options;
			}
		}

		update_post_meta( $this->get_id(), '_lp_cert_layers', $layer_data );

	}

	/**
	 * @param $layer_id
	 */
	public function layer_options( $layer_id ) {
		LP_Addon_Certificates_Preload::$addon->admin_view(
			'editor-layer-options',
			array(
				'certificate' => $this,
				'layer_id'    => $layer_id,
			)
		);
	}

	/**
	 * @param string $context
	 *
	 * @return bool|string
	 */
	public function get_permalink( $context = 'profile' ) {
		$permalink = false;

		switch ( $context ) {
			case 'profile':
				$profile = LP_Profile::instance();

				$permalink = add_query_arg(
					array(
						'cert-id' => $this->get_id(),
					),
					$profile->get_current_url()
				);

				$permalink = $profile->get_current_url() . 'view/' . $this->get_name();

				break;
		}

		return $permalink;
	}

	public function get_sharable_permalink() {
		return $this->get_permalink( '' );
	}

	public function get_uni_id() {
		return 'certificate-' . md5( $this->get_id() );// uniqid( 'certificate-' );
	}

	/**
	 * Get certificate of a course.
	 *
	 * @param $course_id
	 *
	 * @return int
	 */
	public static function get_course_certificate( $course_id ): int {
		$cert_id_of_course = 0;

		if ( LP_COURSE_CPT === get_post_type( $course_id ) ) {
			$cert_id_of_course = get_post_meta( $course_id, '_lp_cert', true );
		}

		return (int) $cert_id_of_course;
	}

	/**
	 * Get all certificates.
	 *
	 * @param int $current
	 *
	 * @return array
	 * @deprecated 4.0.9
	 */
	public static function get_certificates( $current = 0 ) {
		_deprecated_function( __METHOD__, '4.0.9' );
		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = %s AND post_status = %s
		",
			LP_ADDON_CERTIFICATES_CERT_CPT,
			'publish'
		);

		$ids = $wpdb->get_col( $query );

		return $ids;
	}

	/**
	 * Get certificates
	 *
	 * @param LP_Certificate_Filter $filter
	 * @param int $total_rows
	 *
	 * @return array|null|int|string
	 * @since 4.0.2
	 * @version 1.0.0
	 */
	public static function query_certificates( LP_Certificate_Filter $filter, int &$total_rows = 0 ) {
		try {
			$courses = LP_Certificate_DB::getInstance()->query_certificates( $filter, $total_rows );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
			$courses = null;
		}

		return $courses;
	}

	/**
	 * Get User's Certificate
	 *
	 * @param int $user_id
	 * @param array $args_filter - params with filter items
	 *
	 * @editor tungnx
	 * @modify 4.0.2
	 * @return array
	 */
	public static function get_user_certificates( $user_id = 0, $args_filter = array() ): array {
		$lp_db        = LP_Database::getInstance();
		$certificates = array();
		$total_rows   = 0;
		$limit        = (int) LP_Settings::get_option( 'lp_cert_per_page', 10 );

		$filter             = new LP_User_Items_Filter();
		$filter->user_id    = $user_id;
		$filter->graduation = LP_COURSE_GRADUATION_PASSED;
		$filter->join[]     = "INNER JOIN $lp_db->tb_postmeta AS pm ON ui.item_id = pm.post_id";
		$filter->where[]    = $lp_db->wpdb->prepare( 'AND pm.meta_key = %s', '_lp_cert' );

		//if per page = 0 then get all
		if ( $limit > 0 ) {
			$filter->limit = $limit;
			//check params args_filter
			if ( ! empty( $args_filter['page'] ) ) {
				$filter->page = $args_filter['page'];
			}
		} elseif ( $limit === 0 ) {
			$filter->limit = - 1;
		}

		$filter->fields = array( 'meta_value' );
		$courses        = LP_User_Item_Course::get_user_courses( $filter, $total_rows );

		foreach ( $courses as $course ) {
			$certificates['items'][ $course->item_id ] = array(
				'cert_id'   => $course->meta_value,
				'user_id'   => $course->user_id,
				'course_id' => $course->item_id,

			);
			$certificates['cert_key'][]                = self::get_cert_key( $course->user_id, $course->item_id, $course->meta_value, false );
		}

		$certificates['pages'] = LP_Database::get_total_pages( $limit, $total_rows );
		$certificates['total'] = count( $courses );

		return $certificates;
	}

	public static function get_cert_key( $user_id, $course_id, $cert_id = 0, $prefix = true ) {
		if ( ! $cert_id ) {
			$cert_id = self::get_course_certificate( $course_id );
		}

		return ( $prefix ? 'user_cert_' : '' ) . md5( $user_id . ':' . $course_id . ':' . $cert_id );
	}

	/**
	 * @param $key
	 *
	 * @return bool|LP_User_Certificate
	 */
	public static function get_cert_by_key( $key ) {
		$data = get_option( 'user_cert_' . $key );

		if ( ! $data ) {
			return false;
		}

		$cert = new LP_User_Certificate( $data['user_id'], $data['course_id'], $data['cert_id'] );

		return $cert;
	}

	/**
	 * @return array
	 */
	public static function google_fonts() {
		$fonts    = false;
		$settings = LearnPress::instance()->settings()->get( 'certificates.google_fonts' );

		if ( $settings ) {
			if ( ! empty( $settings['families'] ) ) {
				$fonts = explode( '|', $settings['families'] );
			}
		}

		return $fonts;
	}

	public static function system_fonts() {
		$names = array( 'Arial', 'Georgia', 'Helvetica', 'Verdana' );
		$fonts = array_combine( $names, $names );

		return $fonts;
	}

	public function get_price() {
		return (float) get_post_meta( $this->_id, '_lp_certificate_price', true );
	}

	/**
	 * Check user can buy certificate.
	 *
	 * @param int $course_id
	 *
	 * @return array
	 */
	public function can_buy_course( int $course_id = 0 ): array {
		$flag = array(
			'status'  => false,
			'message' => '',
			'code'    => '',
		);

		try {
			if ( ! $this->data_post ) {
				throw new Exception( __( 'Certificate not available', 'learnpress-certificates' ) );
			}

			if ( empty( $this->get_price() ) ) {
				$flag['code'] = 'free';
				throw new Exception( __( 'Certificate is free. So you do not need to buy', 'learnpress-certificates' ) );
			}

			$user_item_query = learn_press_get_user_item(
				array(
					'user_id'  => get_current_user_id(),
					'item_id'  => $course_id,
					'ref_type' => LP_ORDER_CPT,
				)
			);

			if ( empty( $user_item_query ) ) {
				throw new Exception( __( 'You still didn\'t passed course', 'learnpress-certificates' ) );
			}

			$user_item = new LP_User_Item( $user_item_query );

			$course_grade = $user_item->get_status( 'graduation' );

			if ( 'finished' === $user_item->get_status() && 'passed' === $course_grade ) {
				$flag['status'] = true;
			}
		} catch ( Exception $e ) {
			$flag['message'] = $e->getMessage();
		}

		return $flag;
	}

	/*
	public static function user_achieved_certificate( $user_id, $course_id ) {
		$user = learn_press_get_user( $user_id );
		if ( $user ) {
			$course_item = $user->get_course_data( $course_id );
			if ( $course_item && $course_item->is_passed() ) {
				$key = self::get_cert_key( $user_id, $course_id, null, false );

				return self::get_cert_by_key( $key );
			}
		}

		return false;
	}*/

	/**
	 * Check certificate can show
	 *
	 * @param int $course_id
	 * @param int $user_id
	 *
	 * @return array
	 * @since 4.0.0
	 * @version 1.0.1
	 */
	public static function can_get_certificate( int $course_id = 0, int $user_id = 0 ): array {
		$data = array(
			'flag'   => 0,
			'reason' => '',
		);

		try {
			$filter_user_course          = new LP_User_Items_Filter();
			$filter_user_course->user_id = $user_id;
			$filter_user_course->item_id = $course_id;
			$user_course                 = UserCourseModel::get_user_item_model_from_db( $filter_user_course );

			if ( empty( $user_course ) ) {
				$data['reason'] = 'not data user course';

				return $data;
			}

			if ( $user_course->status != LP_COURSE_FINISHED
			     || $user_course->graduation != LP_COURSE_GRADUATION_PASSED ) {
				$data['reason'] = 'not_passed';

				return $data;
			}

			// get certificate id assign in course
			$cert_id_assigned = (int) get_post_meta( $course_id, '_lp_cert', true );

			// Check certificate not set price or price = 0
			$cert_price = (int) get_post_meta( $cert_id_assigned, '_lp_certificate_price', true );

			if ( ! $cert_price ) {
				$data['flag'] = 1;
			} else {
				/*** Case buy certificate - Check Status LP Order certificate in course of user */
				$data['reason'] = 'not_buy';

				$filter_user_cert            = new LP_User_Items_Filter();
				$filter_user_cert->user_id   = $user_id;
				$filter_user_cert->item_id   = $cert_id_assigned;
				$filter_user_cert->item_type = 'lp_certificate';
				$filter_user_cert->ref_type  = LP_ORDER_CPT;
				$filter_user_cert->parent_id = $user_course->get_user_item_id();
				$user_cert                   = UserItemModel::get_user_item_model_from_db( $filter_user_cert );

				if ( ! empty( $user_cert ) ) {
					if ( $user_cert->status === LP_ORDER_COMPLETED ) {
						$data['flag']   = 1;
						$data['reason'] = '';
					}
				}
				/*** End case buy certificate */
			}

			// Update info cert key md5 to get user_id, course_id, cert_id
			$key                 = self::get_cert_key( $user_id, $course_id, $cert_id_assigned, false );
			$data_user_cert_info = array(
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'cert_id'   => $cert_id_assigned,
			);
			update_option( "user_cert_{$key}", $data_user_cert_info );
		} catch ( Throwable $e ) {
			$data['reason'] = $e->getMessage();
		}

		return apply_filters( 'learn-press/certificate/can-get-certificate', $data, $course_id, $user_id );
	}
}
