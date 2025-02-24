<?php
/**
 * Template for displaying single certificate.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/single-certificate.php.
 *
 * @package LearnPress/Templates/Certificates
 * @author  ThimPress
 * @version 3.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $cert ) || ! $cert instanceof LP_Certificate ) {
	die();
}

$course = learn_press_get_course( $cert->get_data( 'course_id' ) );
if ( ! $course ) {
	return;
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta property="og:url" content="<?php echo home_url(); ?>"/>
	<meta property="og:type" content="website"/>
	<meta property="og:title" content="<?php echo esc_attr( $cert->get_title() . ' &rsaquo; ' . $course->get_title() ); ?>"/>
	<meta property="og:description" content="<?php echo esc_attr( $cert->get_desc() ); ?>"/>
	<meta property="og:image" content="<?php echo esc_attr( $cert->get_preview() ); ?>"/>
	<title><?php echo $course->get_title(), '&lsaquo;', $cert->get_title(); ?></title>

	<?php do_action( 'wp_enqueue_scripts' ); ?>
	<?php wp_print_styles( 'certificates-css' ); ?>
	<?php wp_print_scripts( 'pdfjs' ); ?>
	<?php wp_print_scripts( 'fabric' ); ?>
	<?php wp_print_scripts( 'downloadjs' ); ?>
	<?php wp_print_scripts( 'certificates-js' ); ?>
	<?php wp_print_scripts( 'learn-press-global' ); ?>
	<?php //LP_Addon_Certificates_Preload::$addon->header_google_fonts(); ?>
	<body>
		<div class="single-certificate-content">
			<?php LP_Addon_Certificates_Preload::$addon->get_template( 'details.php', array( 'certificate' => $cert ) ); ?>
		</div>
	</body>
</html>
