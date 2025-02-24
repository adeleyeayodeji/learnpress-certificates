<?php
/**
 * Class AdminCourseCertificates
 * Handle template show list Certificates when editing Course on the Backend.
 *
 * @since 4.0.9
 * @version 1.0.0
 */

namespace LearnPress\Certificates;

use Exception;
use LP_Addon_Certificates_Preload;
use LP_Certificate;
use LP_Certificate_DB;
use LP_Certificate_Filter;
use LP_Database;
use LP_Settings;
use stdClass;
use Throwable;

class AdminCourseCertificates {
	public static function render_certificates( $args = [] ): stdClass {
		$limit       = (int) LP_Settings::get_option( 'lp_cert_per_page', 10 );
		$total_cer   = 0;
		$total_pages = 0;
		$paged       = 1;

		try {
			$course_id = (int) $args['course_id'];
			$paged     = (int) ( $args['paged'] ?? $paged );
			if ( empty( $course_id ) ) {
				throw new Exception( __( 'Course is invalid!', 'learnpress-certificates' ) );
			}

			$cert_id_of_course = LP_Certificate::get_course_certificate( $course_id );

			$filter        = new LP_Certificate_Filter();
			$filter->limit = $limit;
			if ( $limit === 0 ) {
				$filter->limit = - 1;
			}
			$filter->page        = $paged;
			$filter->only_fields = [ 'ID', 'post_date' ];
			$filter->where[]     = LP_Certificate_DB::getInstance()->wpdb->prepare( 'AND cer.ID != %d', $cert_id_of_course );
			$filter->order_by    = 'cer.post_date';

			$certificates = LP_Certificate::query_certificates( $filter, $total_cer );
			$user_id      = learn_press_get_current_user_id();
			$total_pages  = LP_Database::get_total_pages( $limit, $total_cer );

			if ( $paged === 1 && $cert_id_of_course ) {
				$cer_activated     = new stdClass();
				$cer_activated->ID = $cert_id_of_course;

				$certificates = array_merge(
					[ $cer_activated ],
					$certificates
				);
			}

			ob_start();
			LP_Addon_Certificates_Preload::$addon->admin_view(
				'course-certificates.php',
				compact( 'course_id', 'certificates', 'user_id', 'cert_id_of_course' )
			);

			if ( $total_pages > $paged ) {
				echo sprintf(
					'<button class="button button-primary lp-cer-btn-load-more">%s</button>',
					__( 'Load more', 'learnpress-certificates' )
				);
			}
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			$content = $e->getMessage();
		}

		$contentObj              = new stdClass();
		$contentObj->content     = $content;
		$contentObj->total_pages = $total_pages;
		$contentObj->paged       = $paged;

		return $contentObj;
	}
}
