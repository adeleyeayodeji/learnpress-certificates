<?php
$id = 'input-' . uniqid();
?>
<input type="text" id="<?php echo $id; ?>" name="<?php echo $option['name']; ?>" value="<?php echo esc_attr( $option['std'] ); ?>" />
<script type="text/javascript">
	jQuery(function ($) {
		var $a = $('#<?php echo $id; ?>').on('change', function () {
			$(document).triggerHandler('certificate/change-layer-option', {
				name: $(this).attr('name'),
				value: this.value
			});
		});
	});
</script>
