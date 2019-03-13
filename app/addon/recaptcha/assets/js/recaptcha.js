// Only when ready.
if ( grecaptcha && ms_addon_recaptcha ) {
	grecaptcha.ready( function () {
		// After recaptcha execution.
		grecaptcha.execute( ms_addon_recaptcha.site_key, {action: 'login'} ).then( function ( token ) {
			// Get the recaptcha response field.
			var recaptcha = document.getElementById( 'ms_recaptcha_response' );
			// Continue only when field exist.
			if ( recaptcha ) {
				// Set the value.
				recaptcha.value = token;
			}
		} );
	} );
}