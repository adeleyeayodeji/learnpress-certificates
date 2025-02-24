<?php

/**
 * Class LP_REST_Certificate_Controller
 *
 * @version 1.0.0
 * @author tungnx
 */
class LP_REST_Certificate_Controller extends LP_Abstract_REST_Controller {
	/**
	 * LP_REST_Certificate_Controller constructor.
	 */
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'certificate';
		parent::__construct();
	}

	/**
	 * Register routes API
	 */
	public function register_routes() {

		$this->routes = array(
			'purchase'      => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'purchase' ),
					'permission_callback' => array( $this, 'check_login' ),
				),
			),
			'items-profile' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'items_profile' ),
					'permission_callback' => array( $this, 'check_login' ),
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Check user logged
	 *
	 * @return bool
	 */
	public function check_login(): bool {
		return is_user_logged_in();
	}


	/**
	 * Rest API for Purchase course in single course.
	 *
	 * @param WP_REST_Request $request .
	 *
	 * @return WP_REST_Response|WP_Error
	 * @throws Exception .
	 * @author Nhamdv
	 */
	public function purchase( WP_REST_Request $request ) {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$certificate_id = $request->get_param( 'lp_cert_id' );
			$course_id      = $request->get_param( 'lp_course_id_of_cert' );

			if ( empty( $course_id ) || empty( $certificate_id ) ) {
				throw new Exception( __( 'Invalid params!', 'learnpress-certificates' ) );
			}

			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				throw new Exception( __( 'No Course available.', 'learnpress-certificates' ) );
			}

			$certificate = new LP_Certificate( $certificate_id );
			if ( ! $certificate->data_post ) {
				throw new Exception( __( 'It\'s not type Certificate', 'learnpress-certificates' ) );
			}

			$can_buy_cert = $certificate->can_buy_course( $course_id );

			if ( ! $can_buy_cert['status'] ) {
				throw new Exception( $can_buy_cert['message'] );
			}

			// Add to cart LP.
			$cart = LearnPress::instance()->cart;

			if ( ! learn_press_enable_cart() ) {
				$cart->empty_cart();
			}

			$item_data = array(
				'data'      => get_post( $certificate_id ),
				'course_id' => $course_id,
			);

			$cart_id = $cart->add_to_cart( $certificate_id, 1, $item_data );

			if ( $cart_id ) {
				$redirect = learn_press_get_page_link( 'checkout' );

				if ( empty( $redirect ) ) {
					throw new Exception( __( 'Please setup lp page checkout', 'learnpress-certificates' ) );
				}

				$response->data->redirect = $redirect;
			} else {
				throw new Exception( __( 'Error: Can\'t add Certificate to cart.', 'learnpress-certificates' ) );
			}

			$response->status  = 'success';
			$response->message = sprintf(
				esc_html__( '"%s" has been added to your cart. Redirecting...', 'learnpress-learnpress' ),
				$certificate->data_post->post_title
			);
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	public function items_profile( WP_REST_Request $request ) {
		$response       = new LP_REST_Response();
		$params         = $request->get_params();
		$page           = ! empty( $params['page'] ) ? (int) $params['page'] : 1;
		$user_id        = ! empty( $params['userID'] ) ? (int) $params['userID'] : get_current_user_id();
		$response->data = new stdClass();

		try {

			if ( empty( $user_id ) ) {
				throw new Exception( __( 'User invalid!', 'learnpress-certificates' ) );
			}

			$args_filter = array(
				'page' => $page,
			);

			$certificates = LP_Certificate::get_user_certificates( $user_id, $args_filter );

			if ( ! $certificates['total'] ) {
				throw new Exception( __( 'No Certificate available.', 'learnpress-certificates' ) );
			}

			$certificates['paged'] = $page;

			$response->status        = 'success';
			$response->data->certKey = $certificates['cert_key'];
			ob_start();
			LP_Addon_Certificates_Preload::$addon->get_template(
				'profile/list-certificates.php',
				compact( 'certificates' )
			);
			$response->data->template = ob_get_clean();
		} catch ( Exception $e ) {
			ob_end_clean();
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
