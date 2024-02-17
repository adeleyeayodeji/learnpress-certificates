<?php
/**
 * Template for displaying Purchase button.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/buttons-purchase-certificate.php.
 *
 * @author   ThimPress
 * @package  learnpress-certificates/Templates
 * @version  1.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $course ) ) {
	return;
}
?>
<form name="form-lp-cert-purchase" method="post" enctype="multipart/form-data">
	<?php
	do_action( 'learn-press/before-purchase-certificate-button' );
	$cert_id = (int) get_post_meta( $course->get_id(), '_lp_cert', true );
	if ( $cert_id ) {
		echo '<input type="hidden" name="_learnpress_certificate_id" value="' . $cert_id . '">';
	}
	?>
	<input type="hidden" name="purchase-course" value="<?php echo esc_attr( $course->get_id() ); ?>"/>
	<input type="hidden" name="purchase-course-nonce"
		   value="<?php echo esc_attr( LP_Nonce_Helper::create_course( 'purchase' ) ); ?>"/>

	<button class="lp-button btn-purchase-certificate">
		<?php echo __( 'Buy this certificate', 'learnpress' ); ?>
	</button>

	<?php do_action( 'learn-press/after-purchase-certificate-button' ); ?>
</form>