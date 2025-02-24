<?php $id = 'input-' . uniqid(); ?>

<input type="text" id="<?php echo $id; ?>" class="cert-color-option" name="<?php echo $option['name']; ?>" value="<?php echo esc_attr( $option['std'] ); ?>" />

<script type="text/javascript">
	jQuery('#<?php echo $id; ?>').wpColorPicker({
		change      : function (hsb, hex, rgb, el) {
			var $input = jQuery(this),
				timer = $input.data('timer');
			timer && clearTimeout(timer);
			timer = setTimeout(function () {
				jQuery(document).triggerHandler('certificate/change-layer-option', {
					name: $input.attr('name'),
					value: $input.val()
				});
			}, 300)
		},
		onChange    : function (hsb, hex, rgb, el) {
		},
		onBeforeShow: function (cal) {
		}
	});
</script>
