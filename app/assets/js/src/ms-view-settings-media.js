/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_media = function init () {
	jQuery( '#direct_access' ).on( 'ms-ajax-updated', function(){
		//update nginx rules
		var excludedFiles = jQuery( '#direct_access' ).val();
		if(excludedFiles){
			var array = excludedFiles.split(',');
			var $wp_content = jQuery('#wp_content_dir').val();
			var $extensions = array.join("|");
			var newRule = "location ~* ^"+$wp_content+"/.*&#92;.("+$extensions+")$ {"+
					" \n  allow all;"+
					"\n}";
			jQuery('.application-servers-nginx-extra-instructions').html(newRule);
		}
	} );

	jQuery( '#application_server' ).on( 'ms-ajax-updated', function(){
		//show server div
		var $selected = jQuery( '#application_server' ).val();
		jQuery('.application-servers').each(function(){
			jQuery(this).hide();
		});
		jQuery('.application-server-' + $selected).show();
	} );
};