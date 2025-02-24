<?php
/**
 * Template for displaying user's certificates in profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/profile/list-certificates.php.
 *
 * @author  ThimPress
 * @package LearnPress/Certificates
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $certificates ) ) {
	return;
}
$paged = $certificates['paged'];
$items = $certificates['items'];
$pages = $certificates['pages'];

?>
<?php if ( $paged === 1 ) : ?>
	<div class="wrap-content-certificates">
	<ul class="profile-certificates">
<?php endif; ?>
<?php
foreach ( $items as $item ) {
	$course = learn_press_get_course( $item['course_id'] );
	if ( ! $course ) {
		continue;
	}

	$_lp_certificate_price = get_post_meta( $item['cert_id'], '_lp_certificate_price', true );
	$cert_id               = get_post_meta( $item['course_id'], '_lp_cert', true );
	$cert                  = get_post( $cert_id );

	if ( empty( $cert ) || $cert->post_type != 'lp_cert' || $cert->post_status != 'publish' ) {
		return;
	}

	$can_get_cert = LP_Certificate::can_get_certificate( $item['course_id'], $item['user_id'] );

	if ( $can_get_cert['flag'] ) {
		LP_Addon_Certificates_Preload::$addon->get_template(
			'profile/item-certificate.php',
			array(
				'item'    => $item,
				'course'  => $course,
				'cert_id' => $cert_id,
			)
		);
	} elseif ( $can_get_cert['reason'] == 'not_buy' ) {
		?>
		<li class="course">
			<p><?php echo sprintf( __( 'In order to get the certificate of the %s course, please pay first!', 'learnpress-certificates' ), '<a href="' . $course->get_permalink() . '">' . $course->get_title() . '</a>' ); ?>
			</p>
			<?php learn_press_certificate_buy_button( $course ); ?>
		</li>
		<?php
	}
}
?>
<?php if ( $paged === 1 ) : ?>
	</ul>
<?php endif; ?>
<?php if ( $pages > 1 && $paged < $pages && $paged === 1 ) { ?>
	<button class="lp-button" id="certificates-load-more"
			data-paged="<?php echo absint( $paged + 1 ); ?>"
			data-number="<?php echo absint( $pages ); ?>">
		<?php esc_html_e( ' View more ', 'learnpress-certificate' ); ?>
	</button>
<?php } ?>
<?php if ( $paged === 1 ) : ?>
	</div>
<?php endif; ?>
