<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Product' ) ) {
	return;
}

global $woocommerce;

include_once 'class-wc-order-item-cert.php';

/**
 * Class WC_Product_LP_Course
 */
class WC_Product_LP_Certificate extends WC_Product {
	/**
	 * @var array|bool|null|WP_Post
	 */
	public $post = false;

	public function __construct( $product = 0 ) {
		if ( is_numeric( $product ) && $product > 0 ) {
			$this->set_id( $product );
		} elseif ( $product instanceof self ) {
			$this->set_id( absint( $product->get_id() ) );
		} elseif ( ! empty( $product->ID ) ) {
			$this->set_id( absint( $product->ID ) );
		}
		$this->post = get_post( $this->id );
	}

	public function __get( $key ) {
		if ( $key === 'id' ) {
			return $this->get_id();
		} else if ( $key === 'post' ) {
			return get_post( $this->get_id() );
		}

		return parent::__get( $key );
	}

	/**
	 * Get Price Description
	 *
	 * @param string $context
	 *
	 * @return int
	 */
	public function get_price( $context = 'view' ) {
		$price_cert = get_post_meta( $this->post->ID, '_lp_certificate_price', true );

		return ! empty( $price_cert ) ? apply_filters( 'learn-press/woo-cert-product-price', $price_cert ) : 0;
	}

	/**
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return get_the_title( $this->id );
	}

	/**
	 * @param string $context
	 *
	 * @return bool
	 */
	public function exists( $context = 'view' ) {
		return $this->post && ( 'lp_cert' == get_post_type( $this->post->ID ) ) && ( $this->post->post_status == 'publish' );
	}

	/**
	 * Check if a product is purchasable
	 */
	public function is_purchasable() {
		$cert = get_post( $this->post->ID );

		return $cert instanceof WP_Post && 'lp_cert' == $cert->post_type;
	}

	public function is_sold_individually() {
		return true;
	}

	/**
	 * Set product cert is virtual (quantity always only = 1)
	 *
	 * @return bool|mixed|void
	 */
	public function is_virtual() {
		return apply_filters( 'lp_cert_woo_product_is_virtual', false, $this );
	}

	public function is_downloadable() {
		return apply_filters( 'lp_cert_woo_product_is_downloadable', true, $this );
	}

	/**
	 * Set Image
	 *
	 * @param string $size
	 * @param array  $attr
	 * @param bool   $placeholder
	 *
	 * @return mixed|string|void
	 */
	public function get_image( $size = 'woocommerce_thumbnail', $attr = array(), $placeholder = true ) {
		$cert_bg_img = get_post_meta( $this->id, '_lp_cert_template', true );

		if ( ! empty( $cert_bg_img ) ) {
			$image = '<img src="' . $cert_bg_img . '" width="300" height="300" />';
		} elseif ( $placeholder ) {
			$image = wc_placeholder_img( $size );
		} else {
			$image = '';
		}

		return apply_filters( 'woocommerce_product_get_image', $image, $this, $size, $attr, $placeholder, $image );
	}

	public function get_status( $context = 'view' ) {
		return $this->post->post_status;
	}
}
