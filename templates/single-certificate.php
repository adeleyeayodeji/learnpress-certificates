<?php

/**
 * Template for displaying single certificate.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/single-certificate.php.
 *
 * @package LearnPress/Templates/Certificates
 * @author  ThimPress
 * @version 3.0.0
 */

defined('ABSPATH') or die();

if (!isset($cert) || !$cert instanceof LP_Certificate) {
    die();
}

$course = learn_press_get_course($cert->get_data('course_id'));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta property="og:url" content="<?php echo home_url() ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php esc_attr_e($cert->get_title() . ' &rsaquo; ' . $course->get_title()); ?>" />
    <meta property="og:description" content="<?php esc_attr_e($cert->get_desc()); ?>" />
    <meta property="og:image" content="<?php esc_attr_e($cert->get_preview()); ?>" />
    <title><?php echo $course->get_title(), '&lsaquo;', $cert->get_title(); ?></title>

    <link rel="stylesheet" id="open-sans-css" href="//fonts.googleapis.com/css?family=Open+Sans%3A300italic%2C400italic%2C600italic%2C300%2C400%2C600&amp;subset=latin%2Clatin-ext&amp;" type="text/css" media="all">
    <?php do_action('wp_enqueue_scripts'); ?>
    <?php wp_print_styles('certificates-css'); ?>
    <?php wp_print_scripts('pdfjs'); ?>
    <?php wp_print_scripts('fabric'); ?>
    <?php wp_print_scripts('downloadjs'); ?>
    <?php wp_print_scripts('certificates-js'); ?>
    <?php wp_print_scripts('learn-press-global'); ?>
    <?php LP_Addon_Certificates::instance()->header_google_fonts(); ?>
    <script src="https://files.codepedia.info/files/uploads/iScripts/html2canvas.js"></script>

<body>
    <div class="single-certificate-content">
        <?php learn_press_certificate_get_template('details.php', array('certificate' => $cert)); ?>
    </div>
</body>

</html>