<?php
/**
 * Template for displaying button add certificate to cart woocommerce
 *
 * @author  tungnx
 * @package learnpress-certificates/templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $course ) ) {
	return;
}

$course_id  = $course->get_id();
$woopayment = LearnPress::instance()->settings()->get( 'woo-payment' );
$cert_id    = (int) get_post_meta( $course->get_id(), '_lp_cert', true );

if ( ! $cert_id ) {
	return;
}
?>

<div class="wrapper-lp-cert-add-to-cart-woo">
	<form name="form-lp-cert-add-to-cart-woo">
		<button class="lp-button btn-add-cert-to-cart-woo">
			<?php esc_html_e( 'Add certificate to cart', 'learnpress-certificates' ); ?>
		</button>

		<input type="hidden" name="lp_course_id_of_cert" value="<?php echo esc_attr( $course_id ); ?>"/>
		<input type="hidden" name="lp_cert_id" value="<?php echo esc_attr( $cert_id ); ?>">
		<input type="hidden" name="purchase-certificate-nonce" value="<?php echo esc_attr( wp_create_nonce( 'purchase-cert-' . $cert_id ) ); ?>"/>
	</form>
</div>
