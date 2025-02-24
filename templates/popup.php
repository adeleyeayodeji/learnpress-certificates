<?php
/**
 * Template for displaying certificate popup inside course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/popup.php.
 *
 * @package LearnPress/Templates/Certificates
 * @author  ThimPress
 * @version 3.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $certificate ) ) {
	return;
}
?>

<div id="certificate-popup">
	<?php LP_Addon_Certificates_Preload::$addon->get_template( 'details.php', compact( 'certificate' ) ); ?>
	<a href="" class="close-popup"></a>
</div>
