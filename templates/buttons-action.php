<?php
/**
 * Template for displaying download button.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/buttons.php.
 *
 * @author  ThimPress
 * @package LearnPress/Templates/Certificates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $certificate ) ) {
	return;
}
?>

<ul class="certificate-actions">
	<li class="download" data-type-download="<?php echo get_option( 'learn_press_lp_cer_down_type', 'image' ); ?>">
		<a href="javascript:void(0)" class="social-download-svg" data-cert="<?php echo $certificate->get_uni_id(); ?>">
			<img src="<?php echo LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/download.svg' ); ?>"
			alt="download-certificate">
		</a>
	</li>

	<?php
	if ( isset( $socials ) && $socials ) {
		foreach ( $socials as $social ) {
			?>
			<li class="share-social-cert">
				<?php echo $social; ?>
			</li>
			<?php
		}
	}
	?>
</ul>

