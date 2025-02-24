<?php
$id = uniqid();

$option = wp_parse_args(
	$option,
	array(
		'min'  => 0,
		'max'  => 100,
		'step' => 0.1,
	)
);

?>
<input type="number" class="cert-option-slider-value" name="<?php echo $option['name']; ?>"
	   value="<?php echo round( $option['std'], 1 ); ?>" min="<?php echo $option['min']; ?>"
	   max="<?php echo $option['max']; ?>" step="<?php echo $option['step']; ?>"/>
<div id="<?php echo $id; ?>" class="cert-option-slider" data-option-min="<?php echo $option['min']; ?>"
	 data-option-max="<?php echo $option['max']; ?>" data-option-step="<?php echo $option['step']; ?>"
	 data-option-value="<?php echo round( $option['std'], 1 ); ?>">

</div>
<script type="text/javascript">
	jQuery(function ($) {
		var $slider = $('#<?php echo $id; ?>'),
			$input = $slider.prev('.cert-option-slider-value'),
			trigger = function (el) {
				$(document).triggerHandler('certificate/change-layer-option', {
					name: $(el).attr('name'),
					value: $(el).val()
				});
			}
		$slider.slider({
			min: parseFloat($slider.attr('data-option-min')) || 0,
			max: parseFloat($slider.attr('data-option-max')) || 100,
			value: parseFloat($slider.attr('data-option-value')) || 0,
			step: parseFloat($slider.attr('data-option-step')) || 1,
			slide: function (evt, ui) {
				$input.val(Math.ceil(ui.value * 10) / 10);
				trigger($input);
			}
		});
		$input.bind('change keyup', function (e) {
			$slider.slider('option', 'value', this.value);
			e.preventDefault();
			trigger($input);
		}).blur(function () {
			var newValue = this.value > $slider.slider('option', 'max') ? $slider.slider('option', 'max') : ( this.value < $slider.slider('option', 'min') ? $slider.slider('option', 'min') : false );
			if (newValue !== false) {
				newValue = Math.ceil(newValue * 10) / 10
				$slider.slider('option', 'value', newValue);
				$input.val(newValue);
				trigger($input);
			}
		});
	})
</script>
