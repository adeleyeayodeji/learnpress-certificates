<?php
/**
 * Template for displaying item certificate.
 *
 *
 * @author  ThimPress
 * @package LearnPress/Templates/Certificates
 * @version 4.0.9
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $id ) || ! isset( $certificate ) || ! isset( $course_cert ) || ! isset( $template_id ) || ! isset( $certificate_data ) ) {
	return;
}
?>
<div class="theme<?php echo $id == $course_cert ? ' active' : ''; ?>"
	data-id="<?php echo esc_attr( $id ); ?>">
	<div class="theme-screenshot">
		<div id="<?php echo esc_attr( $template_id ); ?>" class="certificate-preview">
			<input class="lp-data-config-cer" type="hidden"
					value="<?php echo htmlspecialchars( $certificate_data ); ?>">
		</div>
	</div>

	<div class="theme-author">
		<?php echo $certificate->get_author(); ?>
	</div>

	<div class="theme-id-container">
		<h2 class="theme-name" id="twentysixteen-name">
			<span><?php esc_html_e( 'Active:', 'learnpress-certificates' ); ?></span>
			<?php echo $certificate->get_title(); ?>
		</h2>

		<div class="theme-actions">
			<a class="button button-primary button-remove-certificate" href="">
				<?php esc_html_e( 'Remove', 'learnpress-certificate' ); ?>
			</a>
			<a class="button button-primary button-assign-certificate" href="">
				<?php esc_html_e( 'Assign', 'learnpress-certificate' ); ?>
			</a>
			<a class="button" target="_blank"
				href="<?php echo esc_url( admin_url( 'post.php?post=' . $certificate->get_id() . '&action=edit' ) ); ?> ">
				<?php esc_html_e( 'Edit', 'learnpress-certificate' ); ?>
			</a>
		</div>
	</div>
</div>
