<div v-show="!template" id="no-template-selected">
	<h4 class="no-template-text"><?php _e( 'No template selected.', 'learnpress-certificates' ); ?></h4>
	<button class="button button-hero learn-press-select-template-button" @click.prevent="selectTemplate"><?php _e( 'Select Template', 'learnpress-certificates' ); ?></button>
	<h4 class="no-template-text-2"><?php _e( 'to starts design your favorite certificate now', 'learnpress-certificates' ); ?></h4>
</div>