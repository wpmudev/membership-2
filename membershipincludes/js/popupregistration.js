function m_register_events() {
	jQuery.fancybox.resize();
	jQuery('#reg-form').submit(m_registersubmit);
	jQuery('#login-form').submit(m_loginsubmit);
}

function m_registersuccess(data) {
	jQuery.fancybox.hideActivity();

	try
	{
		returned = jQuery.parseJSON(data);
		if(typeof returned.errormsg != 'undefined') {
			// Oops an error
			jQuery("#reg-error").html(returned.errormsg).show('fast', function() { jQuery.fancybox.resize(); });
		} else {
			// Content is being passed back so display
			jQuery('#fancybox-content div').html(data);
			jQuery.fancybox.resize();
		}
	}
	catch(e)
	{
		// Content
		jQuery('#fancybox-content div').html(data);
		jQuery.fancybox.resize();
	}
}

function sp_registererror(data) {
	jQuery.fancybox.hideActivity();

	jQuery("#reg-error").html('Problem with registration.').show();
}

function sp_loginsuccess(data) {

	jQuery.fancybox.hideActivity();

	try
	{
		returned = jQuery.parseJSON(data);
		if(typeof returned.errormsg != 'undefined') {
			// Oops an error
			jQuery("#login-error").html(returned.errormsg).show('fast', function() { jQuery.fancybox.resize(); });
		} else {
			// Content is being passed back so display
			jQuery('#fancybox-content div').html(data);
			jQuery.fancybox.resize();
		}
	}
	catch(e)
	{
		// Content
		jQuery('#fancybox-content div').html(data);
		jQuery.fancybox.resize();
	}

}

function sp_loginerror(data) {
	jQuery.fancybox.hideActivity();

	jQuery("#login-error").html('Problem with Login.').show();
}

function sp_registersubmit() {

	if (jQuery("#reg_user_email").val().length < 1 || jQuery("#reg_password").val().length < 1) {
		    jQuery("#reg-error").html('Please enter an email address or password').show();
		    jQuery.fancybox.resize();
		    return false;
	}

	if (jQuery("#reg_password").val() != jQuery("#reg_password2").val()) {
		    jQuery("#reg-error").html('Please ensure passwords match').show();
		    jQuery.fancybox.resize();
		    return false;
	}

	jQuery.fancybox.showActivity();

	jQuery.ajax({
		type	: 'POST',
		cache	: false,
		url		: membership.ajaxurl,
		data	: {	action : 'register_user', email : jQuery("#reg_user_email").val(), password : jQuery("#reg_password").val(), nonce : membership.registernonce, subscription: jQuery('#reg_subscription').val() },
		success	: m_registersuccess,
		error	: m_registererror
	});

	return false;
}

function sp_loginsubmit() {

	if (jQuery("#login_user_email").val().length < 1 || jQuery("#login_password").val().length < 1) {
		    jQuery("#login-error").html('Please enter an email address or password').show();
		    jQuery.fancybox.resize();
		    return false;
	}

	jQuery.fancybox.showActivity();

	jQuery.ajax({
		type	: 'POST',
		cache	: false,
		url		: membership.ajaxurl,
		data	: {	action : 'login_user', email : jQuery("#login_user_email").val(), password : jQuery("#login_password").val(), nonce : membership.loginnonce, subscription: jQuery('#login_subscription').val() },
		success	: m_loginsuccess,
		error	: m_loginerror
	});

	return false;
}

jQuery(document).ready(function() {

	/* This is basic - uses default settings */

	jQuery("a.popover").fancybox({
			'transitionIn'	:	'elastic',
			'transitionOut'	:	'elastic',
			'speedIn'		:	600,
			'speedOut'		:	200,
			'overlayShow'	:	false,
			'padding'		: 	0,
			'scrolling'		:   'no',
			'width'			: 	750,
			'autoDimensions': 	false,
			'onComplete'	:   m_register_events
		});



});