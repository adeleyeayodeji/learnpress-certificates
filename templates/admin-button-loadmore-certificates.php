<?php
/**
 * Template for displaying admin button load-more certificates.
 *
 *
 * @author  ThimPress
 * @package LearnPress/Templates/Certificates
 * @version 4.0.9
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="btn-click-load-more"
	class="button button-primary button-loadmore-certificate">
	<?php echo esc_html__( 'Load More', 'learnpress-certificates' ); ?>
	<span class="lp-loading-circle lp-loading-no-css hide"></span>
</div>
