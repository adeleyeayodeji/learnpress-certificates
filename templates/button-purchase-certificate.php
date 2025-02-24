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
		echo '<input type="hidden" name="lp_cert_id" value="' . esc_attr( $cert_id ) . '">';
	}
	?>

	<input type="hidden" name="lp_course_id_of_cert" value="<?php echo esc_attr( $course->get_id() ); ?>"/>

	<button class="lp-button btn-purchase-certificate">
		<?php esc_html_e( 'Buy this certificate', 'learnpress-certificates' ); ?>
	</button>

	<?php do_action( 'learn-press/after-purchase-certificate-button' ); ?>
</form>
