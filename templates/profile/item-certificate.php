<?php
/**
 * Template for displaying detail item in profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/profile/item-certificate.php.
 *
 * @author  ThimPress
 * @package LearnPress/Certificates
 * @version 4.0.2
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $item ) || empty( $course ) || empty( $cert_id ) ) {
	return;
}
?>

<li class="certificate-item">
	<?php
	$user_certificate = new LP_User_Certificate( $item['user_id'], $item['course_id'], $cert_id );
	$template_id      = uniqid( $user_certificate->get_uni_id() );
	$link_cert        = $user_certificate->get_permalink();
	?>

	<a href="<?php echo esc_url( $link_cert ); ?>" class="course-permalink">
		<div class="certificate-thumbnail">
			<div id="<?php echo esc_attr( $template_id ); ?>" class="certificate-preview"
				data-key ="<?php echo LP_Certificate::get_cert_key( $item['user_id'], $item['course_id'], $cert_id, false ); ?>">
				<div class="certificate-preview-inner">
					<canvas></canvas>
				</div>

				<input class="lp-data-config-cer" type="hidden" value="<?php echo htmlspecialchars( $user_certificate ); ?>">
			</div>
		</div>
	</a>

	<h4 class="course-title">
		<a href="<?php echo esc_url( $course->get_permalink() ); ?>"><?php echo esc_html( $course->get_title() ); ?></a>
	</h4>
</li>
