<?php
global $post;
// Nonce field to validate form request came from current site
wp_nonce_field( 'lp-cert-settings-backend', 'certificates_fields' );
// Get the location data if it's already been entered
$price = (int) get_post_meta( $post->ID, '_lp_certificate_price', true );
// Output the field
?>
<div class="rwmb-field rwmb-number-wrapper  required">
	<div class="rwmb-label">
		<label for="_lp_certificate_price"><?php esc_html_e( 'Price', 'learnpress-certificates' );?></label>
	</div>
	<div class="rwmb-input">
		<input step="0.1" min="0" type="number" size="30" id="_lp_certificate_price" name="_lp_certificate_price" value="<?php echo esc_textarea( $price ); ?>">
		<p id="_lp_certificate_price-description" class="description"><?php esc_html_e( 'The price of this certificate. Default is 0, means this certificate is free to download. Currency is what you chose in Learn Press settings.', 'learnpress-certificates' );?></p>
	</div>
	<input type="hidden" class="rwmb-field-name" value="_lp_certificate_price">
	<div class="field-overlay"></div>
</div>