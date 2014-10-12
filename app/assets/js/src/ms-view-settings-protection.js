/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_protection = function init () {
	function before_ajax( data, el ) {
		var textarea = jQuery( '#' + data.type ),
			container = textarea.closest( '.wp-editor-wrap' ),
			editor = window.tinyMCE.get( data.type );

		if ( editor && container.hasClass( 'tmce-active' ) ) {
			editor.save(); // Update the textarea content.
		}

		data.value = textarea.val();

		return data;
	}

	jQuery( '.button-primary.ms-ajax-update' ).data( 'before_ajax', before_ajax );
};
