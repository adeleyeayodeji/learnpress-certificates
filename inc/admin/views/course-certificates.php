<?php
/**
 * Template for displaying all certificates in course editor.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @since   3.0.0
 * @version 1.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $certificates ) || ! isset( $user_id ) || ! isset( $course_id ) || ! isset( $cert_id_of_course ) ) {
	return;
}
?>

<div class="themes wp-clearfix lp-certificates">

	<?php if ( $certificates ) : ?>
		<?php
		foreach ( $certificates as $certificate ) {
			$id               = (int) $certificate->ID;
			$certificate      = new LP_Certificate( $id );
			$certificate_data = new LP_User_Certificate( $user_id, $course_id, $id );
			$template_id      = uniqid( $certificate->get_uni_id() );
			$thumbnail        = $certificate->get_template();
			?>

			<div class="theme<?php echo $id === $cert_id_of_course ? ' active' : ''; ?>"
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
						   href="<?php echo esc_url( admin_url( 'post.php?post=' . $certificate->get_id() . '&action=edit' ) ); ?>">
							<?php esc_html_e( 'Edit', 'learnpress-certificate' ); ?>
						</a>
					</div>
				</div>
			</div>
		<?php } ?>
	<?php endif; ?>

	<div class="theme add-new-theme">
		<a target="_blank"
		   href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . LP_ADDON_CERTIFICATES_CERT_CPT ) ); ?>">
			<div class="theme-screenshot"><span></span></div>
			<h2 class="theme-name"><?php esc_html_e( 'Add new Certificate', 'learnpress-certificates' ); ?></h2>
		</a>
	</div>
</div>
