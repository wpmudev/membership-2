<?php

/**
 * The members tax settings editor
 */
class MS_Addon_Taxamo_Userprofile extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();

		ob_start();
		?>
		<div class="ms-wrap">
			<div class="modal-header">
				<button type="button" class="close">&times;</button>
				<h4 class="modal-title"><?php _e( 'Tax Settings', MS_TEXT_DOMAIN ); ?></h4>
			</div>
			<div class="modal-body">
settings here
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default close"><?php _e( 'Cancel', MS_TEXT_DOMAIN ); ?></button>
				<button type="button" class="btn btn-primary"><?php _e( 'Save Changes', MS_TEXT_DOMAIN ); ?></button>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_addon_taxamo_userprofile',
			$html
		);
	}

	public function prepare_fields() {
		$fields = array();

		return apply_filters(
			'ms_addon_taxamo_userprofile_fields',
			$fields
		);
	}
}