<?php
$id    = uniqid();
$fonts = LP_Certificate::system_fonts();

if ( ! empty( $option['google_font'] ) && ( false !== ( $google_fonts = LP_Certificate::google_fonts() ) ) ) {
	$fonts = array(
		$fonts,
		$google_fonts
	);
}
?>
<select id="<?php echo $id; ?>" name="<?php echo $option['name']; ?>">

	<?php
	if ( ! empty( $google_fonts ) ) {
		?>
        <optgroup label="<?php esc_attr_e( 'System fonts', 'learnpress-certificates' ); ?>"><?php
			foreach ( $fonts[0] as $name => $text ) { ?>
                <option value="<?php echo esc_attr( $name ); ?>" <?php selected( ! empty( $option['std'] ) && ( $option['std'] == $name ) ); ?>><?php echo esc_html( $text ); ?></option>
			<?php } ?>
        </optgroup>
        <optgroup label="<?php esc_attr_e( 'Google fonts', 'learnpress-certificates' ); ?>">
			<?php foreach ( $fonts[1] as $name => $text ) { ?>
                <option value="<?php echo esc_attr( $text ); ?>" <?php selected( ! empty( $option['std'] ) && ( $option['std'] == $text ) ); ?>><?php echo esc_html( $text ); ?></option>
			<?php } ?>
        </optgroup>
		<?php
	} else {
		foreach ( $fonts as $name => $text ) {
			?>
            <option value="<?php echo esc_attr( $name ); ?>" <?php selected( ! empty( $option['std'] ) && ( $option['std'] == $name ) ); ?>><?php echo esc_html( $text ); ?></option>
			<?php
		}
	} ?>

</select>
<script type="text/javascript">
    jQuery(function ($) {
        $('#<?php echo $id;?>').on('change', function () {
            $(document).triggerHandler('certificate/change-layer-option', {
                name: $(this).attr('name'),
                value: this.value
            });
        })
    });
</script>