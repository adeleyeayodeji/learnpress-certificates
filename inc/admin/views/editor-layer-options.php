<?php
if ( ! isset( $certificate ) ) {
	global $post;

	$certificate = new LP_Certificate( $post->ID );
}

$layer = $certificate->get_layer( $layer_id );

if ( ! $layer ) {
	return;
}

$options = $layer->get_options();
?>

<div class="cert-layer-options">
	<button class="button remove-layer" type="button" onclick="$LP_Certificates.deleteLayer();">
		<?php esc_html_e( 'Delete layer', 'learnpress-certificates' ); ?>
	</button>

	<ul>
		<?php foreach ( $options as $option ) : ?>
			<li>
				<label><?php echo $option['title']; ?></label>
				<?php
				$type = preg_replace( '!_!', '-', $option['type'] );
				include 'layer-options/' . $type . '.php';
				?>
			</li>
		<?php endforeach; ?>
	</ul>

	<button type="button" class="button layer-center" data-center="center"><?php esc_html_e( 'Center', 'learnpress-certificates' ); ?></button>
	<button type="button" class="button layer-center" data-center="center-h"><?php esc_html_e( 'Center Horizontal', 'learnpress-certificates' ); ?></button>
	<button type="button" class="button layer-center" data-center="center-v"><?php esc_html_e( 'Center Vertical', 'learnpress-certificates' ); ?></button>
</div>
