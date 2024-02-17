<?php

/**
 * Class LP_Cert_AJAX
 *
 * Handle ajax for certificates
 *
 * @since 3.1.4
 */
class LP_Cert_AJAX {
	protected static $_instance;
	protected $_hook_arr = array( 'lpCertCreateImage', 'lp_cert_add_to_cart_woo' );

	protected function __construct() {
		foreach ( $this->_hook_arr as $hook ) {
			add_action( 'wp_ajax_' . $hook, array( $this, $hook ) );
			add_action( 'wp_ajax_nopriv_' . $hook, array( $this, $hook ) );
		}
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function lpCertCreateImage() {
		$data = array( 'code' => 0, 'message' => '' );

		try {
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$uploads  = wp_upload_dir();
			$cert_dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'learn-press-cert';

			$img_cert_name = LP_Helper::sanitize_params_submitted( $_POST['name_image'] );
			$file_img_cer  = $cert_dir . DIRECTORY_SEPARATOR . $img_cert_name . '.png';

			fopen( $file_img_cer, 'w' );

			$data64 = str_replace( 'data:image/png;base64,', '', LP_Helper::sanitize_params_submitted( $_POST['data64'] ) );
			$data64 = base64_decode( $data64 );

			//file_put_contents( $file_img_cer, $data64 );
			$wp_filesystem->put_contents( $file_img_cer, $data64, FS_CHMOD_FILE );

			$data['url_cert'] = $uploads['baseurl'] . '/learn-press-cert/' . $img_cert_name . '.png';
			$data['code']     = 1;
			$data['message']  = 'create image cert success';
		} catch ( Exception $e ) {
			$data['message'] = $e->getMessage();
		}

		wp_send_json( $data );
	}

	/**
	 * Add course id as product id to cart | If add cert id as product will trouble create lp order for certificate
	 * Store lp_cert_id key to cart item
	 */
	public function lp_cert_add_to_cart_woo() {
		$result = array( 'code' => 0, 'message' => __( 'error', 'learnpress-certificates' ) );

		if ( ! isset( $_POST['lp_course_id_of_cert'] ) || ! isset( $_POST['lp_cert_id'] ) ) {
			$result['message'] = __( 'Params invalid', 'learnpress-certificates' );

			wp_send_json( $result );
		}

		$course_id = absint( wp_unslash( $_POST['lp_course_id_of_cert'] ) );
		$cert_id   = absint( wp_unslash( $_POST['lp_cert_id'] ) );

		if ( ! isset( $_POST['purchase-certificate-nonce'] ) || ! wp_verify_nonce( $_POST['purchase-certificate-nonce'], 'purchase-cert-' . $cert_id ) ) {
			$result['message'] = 'params invalid';

			wp_send_json( $result );
		}

		$wc_cart       = WC()->cart;
		$cart_item_key = $wc_cart->add_to_cart( $course_id );

		if ( $cart_item_key ) {
			$wc_cart->cart_contents[ $cart_item_key ]['lp_course_id_of_cert'] = $course_id;
			$wc_cart->cart_contents[ $cart_item_key ]['is_lp_cert_product']   = 1;
			$wc_cart->cart_contents[ $cart_item_key ]['lp_cert_id']           = $cert_id;

			$wc_cart->set_session();

			$result['code']             = 1;
			$result['message']          = $cart_item_key;
			$result['button_view_cart'] = '<a class="btn-lp-cert-view-cart" target="_blank" href="' . wc_get_cart_url() . '"><button class="lp-button">' . __( 'View cart certificate', 'learnpress-certificates' ) . '</button></a>';

			if ( 'yes' == LP()->settings->get( 'woo-payment_redirect_to_checkout' ) ) {
				$result['redirect_to'] = wc_get_checkout_url();
			}
		} else {
			$wc_notices = wc_get_notices();

			if ( isset( $wc_notices['error'] ) && ! empty( $wc_notices['error'] ) ) {
				//$message_error = sanitize_text_field($wc_notices['error'][0]['notice']);
				$result['message'] = __( 'You cannot add this certificate to your cart. Maybe certificate exists', 'learnpress-certificates' );
			}
		}

		wp_send_json( $result );
	}
}

LP_Cert_AJAX::getInstance();
