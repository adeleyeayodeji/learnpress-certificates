<?php
$id = uniqid();
$option = wp_parse_args(
	$option,
	array(
		'min'  => -2147483647,
		'max'  => 2147483647,
		'step' => 1
	)
)
?>
<input type="number" id="<?php echo $id; ?>" name="<?php echo $option['name']; ?>"
       value="<?php echo esc_attr( intval( $option['std'] ) ); ?>"
       min="<?php echo $option['min']; ?>"
       max="<?php echo $option['max']; ?>"
       step="<?php echo $option['step']; ?>"
/>
<script type="text/javascript">
    jQuery(function ($) {
        var $a = $('#<?php echo $id;?>').on('change keyup mouseup input', function () {
            $(document).triggerHandler('certificate/change-layer-option', {
                name: $(this).attr('name'),
                value: this.value
            });
        });
    });
</script>