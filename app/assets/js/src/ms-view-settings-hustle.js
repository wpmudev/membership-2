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
				data
			).done( function( response ) {
				$container.removeClass( 'ms-processing wpmui-loading' );
				if ( response.success === true ) {
					jQuery('.ms-hustle-provider-list-details').html(response.data);
					jQuery('.wpmudev-select').wpmuiSelect( {
						minimumResultsForSearch: 6,
						width: '100%'
					} );
				} else {
					$messagetext.html(response.data);
					$message.show();
				}
					
			}).fail(function(xhr, status, error) {
				$container.removeClass( 'ms-processing wpmui-loading' );
			});
		}else{
			$messagetext.html(ms_data.error_fetching);
			$message.show();
		}
	});

	//Save provider details
	jQuery( document ).on('click','button.ms_optin_save_provider_details', function(e){
		var $container = jQuery( '.ms-hustle-provider-details' ),
			$inputs = $container.find("input,select"),
			$message = jQuery('.ms-hustle-response'),
			$messagetext = jQuery('.ms-hustle-response p'),
			$nonce = jQuery(this).val(),
			$provider = jQuery('#hustle_provider').val(),
			$errors = [],
			data = {};
		
		$messagetext.html(''); 
		$message.hide();
		$inputs.each(function(){
			var $this = jQuery(this),
				$name = $this.attr('name');
			if ( $this.attr('type') !== 'submit' || $this.attr('type') !== 'button') {
				if ( !$this.val() && !$this.is("select") ) {
					$errors.push( $this );
					$this.focus();
				}
			
				if ( $this.is("select") ) {
					data[$name] = {};
					data[$name]['value'] = $this.val();
					data[$name]['text'] =  jQuery("option:selected", $this).text();
				} else {
					data[$name] = $this.val();
				}
			}
			
		});
		if( $errors.length === 0 ){
			data['action'] = 'ms_hustle_save_provider';
			data['_wpnonce'] = $nonce;
			data['optin_provider_name'] = $provider;

			$container.addClass( 'ms-processing wpmui-loading' ); //show processing
			jQuery.post(
				window.ajaxurl,
				data
			).done( function( response ) {
				$container.removeClass( 'ms-processing wpmui-loading' );
				$messagetext.html(response.data);
				$message.show();
			}).fail(function(xhr, status, error) {
				$container.removeClass( 'ms-processing wpmui-loading' );
			});
		}else{
			$messagetext.html(ms_data.error_saving);
			$message.show();
		}
	});
};
