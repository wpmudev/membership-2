/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_hustle = function init() {
	//Show prcessing on change
	jQuery( '#hustle_provider' ).on( 'ms-ajax-start', function(){
		jQuery( '.ms-hustle-provider-details' ).addClass( 'ms-processing wpmui-loading' );
	});
	//reload the page
	jQuery( '#hustle_provider' ).on( 'ms-ajax-updated', ms_functions.reload );

	//Fetch lists
	jQuery( document ).on('click','input.ms_optin_refresh_provider_details', function(e){
		var $container = jQuery( '.ms-hustle-provider-details' ),
			$inputs = $container.find("input"),
			$message = jQuery('.ms-hustle-response'),
			$messagetext = jQuery('.ms-hustle-response p'),
			$nonce = jQuery(this).attr('data-nonce'),
			$provider = jQuery(this).attr('data-provider'),
			$errors = [],
			data = {};
		//Make sure all inputs are not epmty
		$messagetext.html('');
		$message.hide();
		$inputs.each(function(){
			var $this = jQuery(this),
				$name = $this.attr('name');
			if ( !$this.val() ) {
				$errors.push( $this );
				$this.focus();
			} else {
				data[$name] = $this.val();
			}
		});
		if( $errors.length === 0 ){
			data['action'] = 'ms_hustle_get_lists';
			data['_wpnonce'] = $nonce;
			data['optin_provider_name'] = $provider;
			
			$container.addClass( 'ms-processing wpmui-loading' ); //show processing
			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					$container.removeClass( 'ms-processing wpmui-loading' );
					if ( response.success === true ) {
						jQuery('.ms-hustle-provider-list-details').html(response.data);
						jQuery('.wpmudev-select').wpmuiSelect( {
							minimumResultsForSearch: 6,
							width: '100%'
						} );
					} else {
						$message.show();
						$messagetext.html(response.data);
					}
					
				}
			);
		}
	});
};
