<?php
/**
 * Use in Frontend Editor
 */
class LP_REST_Admin_Certificate_Controller {
	protected static $_instance = null;

	const NAMESPACE = 'lp/certificate/admin/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/course-metabox',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'course_metabox' ),
				'permission_callback' => function() {
					return $this->is_admin_or_instructor();
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/course-metabox/assign',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'assign_course_metabox' ),
				'permission_callback' => function() {
					return $this->is_admin_or_instructor();
				},
			)
		);
	}

	public function course_metabox( $request ) {
		$course_id = $request->get_param( 'course_id' );

		try {
			$course_id = absint( $course_id );

			$output = $this->get_list_certificates( $course_id );

			if ( is_wp_error( $output ) ) {
				throw new Exception( $output->get_error_message() );
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $output,
					'active'  => LP_Certificate::get_course_certificate( $course_id ),
				)
			);
		} catch ( \Throwable $th ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $th->getMessage(),
				)
			);
		}
	}

	public function assign_course_metabox( $request ) {
		$course_id      = ! empty( $request['course_id'] ) ? absint( $request['course_id'] ) : 0;
		$certificate_id = ! empty( $request['certificate_id'] ) ? absint( $request['certificate_id'] ) : 0;

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( __( 'Course ID is required', 'learnpress-certificates' ) );
			}

			if ( LP_COURSE_CPT !== get_post_type( $course_id ) ) {
				throw new Exception( __( 'Course ID is invalid', 'learnpress-certificates' ) );
			}

			if ( ! empty( $certificate_id ) ) {
				update_post_meta( $course_id, '_lp_cert', $certificate_id );
			} else {
				delete_post_meta( $course_id, '_lp_cert' );
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => ! empty( $certificate_id ) ? __( 'Certificate assigned successfully', 'learnpress-certificates' ) : __( 'Certificate removed successfully', 'learnpress-certificates' ),
					'active'  => LP_Certificate::get_course_certificate( $course_id ),
				)
			);
		} catch ( \Throwable $th ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $th->getMessage(),
				)
			);
		}
	}

	protected function get_list_certificates( $course_id ) {
		//$course_cert  = LP_Certificate::get_course_certificate( $course_id );
		$filter       = new LP_Certificate_Filter();
		$certificates = LP_Certificate::query_certificates( $filter );

		if ( empty( $certificates ) ) {
			return new WP_Error( 'no_certificate', __( 'No certificate available', 'learnpress-certificates' ) );
		}

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return new WP_Error( 'no_user', __( 'No user available', 'learnpress-certificates' ) );
		}

		$output = array();
		foreach ( $certificates as $certificate ) {
			$id               = $certificate->ID;
			$certificate      = new LP_Certificate( $id );
			$user_certificate = new LP_User_Certificate( $user_id, $course_id, $id );

			$output[] = array(
				'id'        => absint( $id ),
				'title'     => $certificate->get_title(),
				'data'      => json_decode( htmlspecialchars_decode( htmlspecialchars( $user_certificate ) ) ),
				'edit_link' => admin_url( 'post.php?post=' . $certificate->get_id() . '&action=edit' ),
			);
		}

		return $output;
	}

	public function is_admin_or_instructor() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$user = learn_press_get_user( $user_id );

		if ( ! $user ) {
			return false;
		}

		if ( $user->is_instructor() || $user->is_admin() ) {
			return true;
		}

		return false;
	}

	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

LP_REST_Admin_Certificate_Controller::instance();
