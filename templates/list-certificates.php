<?php
/**
 * Template for displaying user's certificates in profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/list-certificates.php.
 *
 * @author  ThimPress
 * @package LearnPress/Certificates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

if ( isset( $certificates ) ) {
	?>
	<h3 class="profile-heading"><?php esc_html_e( 'Certificates', 'learnpress-certificates' ); ?></h3>
	<ul class="learn-press-courses profile-certificates">
		<?php foreach ( $certificates as $course_id => $data ) {
			$course                = learn_press_get_course( $course_id );
			$_lp_certificate_price = get_post_meta( $data['cert_id'], '_lp_certificate_price', true );
			$cert_id               = get_post_meta( $data['course_id'], '_lp_cert', true );
			$cert                  = get_post( $cert_id );

			if ( empty( $cert ) || $cert->post_type != 'lp_cert' || $cert->post_status != 'publish' ) {
				return;
			}

			$can_get_cert = LP_Certificate::can_get_certificate( $data['course_id'], $data['user_id'] );

			// Check certificate has data
			//$certificate_data = LP_Certificate::user_achieved_certificate( $data['user_id'], $data['course_id'] );
			$certificate_data = $certificate = new LP_User_Certificate( $data['user_id'], $data['course_id'], $cert_id );

			if ( ! $certificate_data ) {
				continue;
			}

			if ( $can_get_cert['flag'] ) { ?>
				<li class="course">
					<?php
					$certificate = new LP_User_Certificate( $data );
					$template_id = uniqid( $certificate->get_uni_id() );
					$link_cert   = $certificate->get_sharable_permalink();
					?>
					<a href="<?php echo $link_cert ?>" class="course-permalink">
						<div class="course-thumbnail">
							<div id="<?php echo $template_id; ?>" class="certificate-preview">
								<div class="certificate-preview-inner">
									<canvas></canvas>
								</div>

								<input class="lp-data-config-cer" type="hidden"
									   value="<?php echo htmlspecialchars( $certificate ) ?>">
							</div>
						</div>
					</a>
					<h4 class="course-title">
						<a href="<?php echo $course->get_permalink(); ?>"><?php echo $course->get_title(); ?></a>
					</h4>
				</li>
			<?php } else if ( ! $can_get_cert['flag'] && $can_get_cert['reason'] == 'not_buy' ) { ?>
				<li class="course">
					<p><?php echo sprintf( __( 'In order to get the certificate of the %s course, please pay first!', 'learnpress-certificates' ), '<a href="' . $course->get_permalink() . '">' . $course->get_title() . '</a>' ); ?></p>
					<?php learn_press_certificate_buy_button( $course ); ?>
				</li>
			<?php }
		} ?>
	</ul>
<?php } else {
	learn_press_display_message( __( 'No certificates!', 'learnpress-certificates' ) );
} ?>
