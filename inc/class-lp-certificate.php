<?php

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
	 * LP_Certificate constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {

		// Validation
		if ( LP_ADDON_CERTIFICATES_CERT_CPT !== get_post_type( $id ) ) {
			return;
		}

		$this->_id = $id;
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
			$template = get_post_meta( $this->_id, '_lp_cert_template', true );
			$template = preg_replace( '~^https?://~', is_ssl() ? 'https://' : 'http://', $template );

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

			if ( $layers = $this->get_raw_layers() ) {
				foreach ( $layers as $layer ) {
					if ( $layer = $this->get_layer( $layer ) ) {
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
		if ( false === ( $layers = wp_cache_get( 'certificate-' . $this->get_id(), 'certificates' ) ) ) {
			if ( $layers = get_post_meta( $this->_id, '_lp_cert_layers', true ) ) {
				if ( is_array( $layers ) ) {
					foreach ( $layers as $k => $layer ) {

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
		$preview = get_post_meta( $this->get_id(), '_lp_cert_preview', true );

		if ( ! $preview ) {
			$preview = get_post_meta( $this->get_id(), '_lp_cert_template', true );
		}

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
				include_once( LP_ADDON_CERTIFICATES_PATH . '/inc/layers/class-lp-' . $class_file . '-layer.php' );
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
		if ( ! $layers = $this->get_layers() ) {
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
		$factory = LP_Addon_Certificates::instance();
		$factory->admin_view( 'editor-layer-options', array( 'certificate' => $this, 'layer_id' => $layer_id ) );
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
						'cert-id' => $this->get_id()
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

	/*** HELPER FUNCTIONS ***/

	/**
	 * Get certificate of a course.
	 *
	 * @param $course_id
	 *
	 * @return bool|mixed
	 */
	public static function get_course_certificate( $course_id ) {
		return ( LP_COURSE_CPT === get_post_type( $course_id ) ) ? get_post_meta( $course_id, '_lp_cert', true ) : false;
	}

	public static function get_course_certificates( $course_id ) {
		global $wpdb;
		//$query = $wpdb->prepare()
	}

	/**
	 * Get all certificates.
	 *
	 * @param int $current
	 *
	 * @return array
	 */
	public static function get_certificates( $current = 0 ) {
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = %s AND post_status = %s
		", LP_ADDON_CERTIFICATES_CERT_CPT, 'publish' );

		if ( ( $ids = $wpdb->get_col( $query ) ) && $current ) {
			if ( false !== ( $index = array_search( $current, $ids ) ) ) {
				array_splice( $ids, $index, 1 );
				array_unshift( $ids, $current );
			}
		}

		return $ids;
	}

	public static function get_user_certificates( $user_id ) {
		if ( false === ( $certificates = wp_cache_get( 'user-' . $user_id, 'certificates' ) ) ) {
			$user          = learn_press_get_user( $user_id );
			$query_courses = $user->get_purchased_courses( array( 'status' => 'finished' ) );
			$certificates  = array();

			if ( $query_courses->get_items() ) {
				foreach ( $query_courses->get_items() as $course_item ) {
					$course_id = $course_item->get_id();
					if ( $cert_id = self::get_course_certificate( $course_id ) ) {
						if ( $course_item->is_passed() ) {
							$certificates[ $course_id ] = array(
								//'course_data' => $course_item->get_results( false ),
								'cert_id'   => $cert_id,
								'user_id'   => $user_id,
								'course_id' => $course_id
							);
							//learn_press_update_user_item_meta( $course_item->get_user_item_id(), '_lp_cert', $certificates[ $course_id ] );
							//LP_Addon_Certificates::instance()->update_user_certificate( $course_id, $user_id, false );
						}
					}
				}
			}
			wp_cache_set( 'user-' . $user_id, $certificates, 'certificates' );
		}

		return $certificates;
	}

	public static function get_cert_key( $user_id, $course_id, $cert_id = 0, $prefix = true ) {
		if ( ! $cert_id ) {
			$cert_id = self::get_course_certificate( $course_id );
		}

		return ( $prefix ? 'user_cert_' : '' ) . md5( $user_id . ":" . $course_id . ":" . $cert_id );
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
		$fonts = false;
		if ( $settings = LP()->settings->get( 'certificates.google_fonts' ) ) {
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

	/*public static function user_achieved_certificate( $user_id, $course_id ) {
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
	 * @throws Exception
	 */
	public static function can_get_certificate( $course_id = 0, $user_id = 0 ) {
		$data = array( 'flag' => 0, 'reason' => '' );

		$user_item_query = learn_press_get_user_item( array(
			'user_id'  => $user_id,
			'item_id'  => $course_id,
			'ref_type' => LP_ORDER_CPT
		), true );

		if ( empty( $user_item_query ) ) {
			return $data;
		}

		$user_item = new LP_User_Item( $user_item_query );

		// Check if course not finish or not passed
		$course_grade = $user_item->get_meta( 'grade' );
		if ( $user_item->get_status() != 'finished' || $course_grade != 'passed' ) {
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
			/*** Case buy certificate - Check Status LP Order certificate in course of user ***/
			$data['reason'] = 'not_buy';

			$data_query = array(
				'user_id'   => $user_id,
				'cert_id'   => $cert_id_assigned,
				'course_id' => $course_id
			);

			$order_of_cert_query = LP_Certificate_DB::getInstance()->get_order_id_of_cert_course( $data_query );

			if ( ! empty( $order_of_cert_query ) ) {
				$order_of_cert = new LP_Order( $order_of_cert_query->ref_id );

				if ( $order_of_cert->get_status() == 'completed' ) {
					$data['flag']   = 1;
					$data['reason'] = '';
				}
			}

			// For old version < 3.1.4
			$lp_cert_order = learn_press_get_user_item_meta( $user_item_query->user_item_id, '_lp_certificate_order_id', true );
			if ( $lp_cert_order && 'completed' == learn_press_get_order_status( $lp_cert_order ) ) {
				$data['flag']   = 1;
				$data['reason'] = '';
			}
			// End
			/*** End case buy certificate ***/
		}

		// Update info cert key md5 to get user_id, course_id, cert_id
		$key                 = LP_Certificate::get_cert_key( $user_id, $course_id, $cert_id_assigned, false );
		$data_user_cert_info = array(
			'user_id'   => $user_id,
			'course_id' => $course_id,
			'cert_id'   => $cert_id_assigned
		);
		update_option( "user_cert_{$key}", $data_user_cert_info );

		return $data;
	}
}