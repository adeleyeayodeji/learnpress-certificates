<?php
$id = uniqid();
?>
<select id="<?php echo $id; ?>" name="<?php echo $option['name']; ?>">

	<?php foreach ( $option['options'] as $k => $v ) { ?>
        <option value="<?php echo $k; ?>"<?php selected( ! empty( $option['std'] ) && ( $option['std'] == $k ) ); ?>><?php echo $v; ?></option>
	<?php } ?>

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