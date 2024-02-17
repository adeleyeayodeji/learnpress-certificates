<?php

/**
 * Template for displaying certificate in user profile.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/details.php.
 *
 * @package LearnPress/Templates/Certificates
 * @author  ThimPress
 * @version 3.0.0
 */

defined('ABSPATH') or die();

/**
 * @var LP_User_Certificate $certificate
 */
if (!isset($certificate)) {
	return;
}
$template_id = $certificate->get_uni_id();

$can_get_certificate = LP_Certificate::can_get_certificate($certificate->get_course_id(), $certificate->get_user_id());

if (!$can_get_certificate['flag']) {
	echo __('You can\'t get this certificate', 'learnpress-certificates');

	return;
}
?>
<div class="certificate">
	<?php //do_action('learn-press/certificates/before-certificate-content', $certificate); 
	?>

	<div id="<?php echo $template_id; ?>" class="certificate-preview">
		<div class="certificate-preview-inner">
			<canvas></canvas>
		</div>

		<input class="lp-data-config-cer" type="hidden" value="<?php echo htmlspecialchars($certificate); ?>">

		<?php
		// $share_social_setting = LP()->settings->get( 'certificates.socials', array() );

		// if ( ! empty( $share_social_setting ) ) {
		// 	echo '<input type="hidden" name="need_upload_cert_img_to_server">';
		// }
		?>

	</div>

	<?php //do_action('learn-press/certificates/after-certificate-content', $certificate); 
	?>
</div>