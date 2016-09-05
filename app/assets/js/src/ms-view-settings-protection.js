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

	function toggle_override() {
		var toggle = jQuery( this ),
			block = toggle.closest( '.inside' ),
			content = block.find( '.wp-editor-wrap' ),
			button = block.find( '.button-primary' );

		if ( toggle.hasClass( 'on' ) ) {
			button.show();
			content.show();
		} else {
			button.hide();
			content.hide();
		}
	}

	jQuery( '.button-primary.wpmui-ajax-update' ).data( 'before_ajax', before_ajax );

	jQuery( '.override-slider' )
		.each(function() { toggle_override.apply( this ); })
		.on( 'ms-ajax-done', toggle_override );
};
