<div class="cert-toolbar">
	<ul class="cert-fields">
		<?php foreach ( LP_Addon_Certificates::get_fields() as $field ) : ?>
			<li class="cert-field <?php echo $field['name']; ?>" data-type="<?php echo $field['name']; ?>">
				<span class="<?php echo ! empty( $field['icon'] ) ? $field['icon'] : ''; ?>"></span>
				<p><?php echo $field['title']; ?></p>
			</li>
		<?php endforeach; ?>
	</ul>

	<p class="buttons">
		<button type="button" class="button" v-show="!!template" @click="selectTemplate"><?php esc_html_e( 'Choose background image', 'learnpress-certificate' ); ?></button>
	</p>
</div>
