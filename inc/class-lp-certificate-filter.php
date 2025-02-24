<?php
/**
 * Class LP_Certificate_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 1.0.0
 * @since 4.0.2
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Certificate_Filter' ) ) {
	return;
}

class LP_Certificate_Filter extends LP_Post_Type_Filter {
	/**
	 * @var string
	 */
	public $post_type = LP_ADDON_CERTIFICATES_CERT_CPT;
	/**
	 * @var string
	 */
	public $field_count = 'ID';
}
