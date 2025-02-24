<?php

/**
 * Class LP_Certificate
 */
class LP_User_Certificate extends LP_Certificate {

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

	/**
	 * @var string
	 */
	protected $_data_key = '';

	protected $_data = null;

	/**
	 * LP_Certificate constructor.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param int $certificate_id
	 */
	public function __construct( $user_id = 0, $course_id = 0, $certificate_id = 0 ) {
		$this->_data = array(
			'user_id'   => $user_id,
			'course_id' => $course_id,
			'cert_id'   => $certificate_id,
		);
		//$this->_data = get_option( self::get_cert_key( $user_id, $course_id, $certificate_id ) );
		parent::__construct( $certificate_id );
	}

	public function get_data( $key = false ) {
		if ( empty( $this->_data ) ) {
			return false;
		}

		return false !== $key && array_key_exists( $key, $this->_data ) ? $this->_data[ $key ] : $this->_data;
	}

	/**
	 * Get link of user's certificate
	 * @param string $context
	 *
	 * @return bool|string
	 * @since 4.0.0
	 * @version 1.0.2
	 */
	public function get_permalink( $context = 'profile' ) {
		$user_id = $this->get_user_id();
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$key       = self::get_cert_key( $user_id, $this->get_data( 'course_id' ), $this->get_data( 'cert_id' ), false );
		$permalink = trailingslashit( get_home_url() ) . urlencode( LP_Settings::get_option( 'lp_cert_slug', 'certificates' ) ) . '/' . $key;

		return apply_filters( 'learn-press/certificates/permalink', $permalink, $user_id, $this->get_data( 'course_id' ), $this->get_data( 'cert_id' ), $context );
	}

	public function get_layers( $json = false ) {
		$layers = parent::get_layers();
		if ( $layers ) {
			$data = $this->get_data();
			foreach ( $layers as $k => $layer ) {
				$layers[ $k ]->apply( $data );

				if ( $json ) {
					$layers[ $k ] = $layers[ $k ]->options;
				}
			}
		}

		return $layers;
	}

	public function get_user_id() {
		return ! empty( $this->_data['user_id'] ) ? $this->_data['user_id'] : false;
	}

	public function get_course_id() {
		return ! empty( $this->_data['course_id'] ) ? $this->_data['course_id'] : false;
	}

	/**
	 *
	 */
	public function _get_cert_key( $prefix = true ) {
		return self::get_cert_key( $this->get_data( 'user_id' ), $this->get_data( 'course_id' ), $this->get_data( 'cert_id' ), $prefix );
	}

	/**
	 *
	 */
	public function get_file_path() {
		$cert_key = $this->_get_cert_key( false );
		$lp_certs = get_user_meta( $this->get_user_id(), '_lp_certs', true );
		$lp_certs = (array) $lp_certs;
		$res      = false;

		if ( isset( $lp_certs[ $cert_key ] ) ) {
			$res = $lp_certs[ $cert_key ];
		}

		return $res;
	}

	public function get_json_data() {
		$json = array(
			'id'          => $this->get_id(),
			'name'        => $this->get_name(),
			'layers'      => $this->get_layers( true ),
			'template'    => $this->get_template(),
			'preview'     => $this->get_preview(),
			'systemFonts' => LP_Certificate::system_fonts(),
			'user_id'     => $this->get_user_id(),
			'course_id'   => $this->get_course_id(),
			'key_cer'     => LP_Certificate::get_cert_key( $this->get_user_id(), $this->get_course_id(), $this->get_id(), false ),
		);

		return apply_filters( 'learn-press/certificate/user-json-data', $json, $this->get_user_id(), $this->get_course_id(), $this->get_id() );
	}

	public function __toString() {
		return LP_Helper::json_encode( $this->get_json_data() );
	}
}
