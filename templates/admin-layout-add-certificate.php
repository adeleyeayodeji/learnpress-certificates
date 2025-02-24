<?php
/**
 * Template for displaying admin layout add certificate
 *
 *
 * @author  ThimPress
 * @package LearnPress/Templates/Certificates
 * @version 4.0.9
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="theme add-new-theme">
	<a target="_blank"
		href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . LP_ADDON_CERTIFICATES_CERT_CPT ) ); ?>">
		<div class="theme-screenshot"><span></span></div>
		<h2 class="theme-name"><?php esc_html_e( 'Add new Certificate', 'learnpress-certificates' ); ?></h2>
	</a>
</div>