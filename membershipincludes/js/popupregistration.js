function m_register_events() {
	jQuery.fancybox.resize();
	jQuery('#reg-form').submit(m_registersubmit);
	jQuery('#login-form').submit(m_loginsubmit);
	jQuery('#extra-form').submit(m_extraform);
	jQuery('.membership-coupon form').submit(m_applycoupon);
	// Additional input for submit button
	jQuery('#signup-form').submit(m_signupform);
}

function m_couponsuccess(data) {
	jQuery.fancybox.hideActivity();

	try
	{
		returned = jQuery.parseJSON(data);
		if(typeof returned.errormsg != 'undefined') {
			// Oops an error
			alert(returned.errormsg);
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
	m_register_events();
}

function m_couponerror(data) {
	jQuery.fancybox.hideActivity();

	alert('Coupon error');
}

function m_applycoupon() {
	jQuery.fancybox.showActivity();

	var _coupon = jQuery('#coupon_code').val();
	var _sub_id = jQuery('#coupon_sub_id').val();

	if( typeof _coupon != 'undefined' ) {

		jQuery.ajax({
			type	: 'POST',
			cache	: false,
			url		: membership.ajaxurl + '?action=buynow&subscription=' + _sub_id,
			data	: {	coupon_code : _coupon },
			success	: m_couponsuccess,
			error	: m_couponerror
		});

	}

	return false;
}

function m_signupform() {


	return false;
}

function m_extraform() {
	jQuery.fancybox.showActivity();

	jQuery.ajax({
		type	: 'POST',
		cache	: false,
		url		: membership.ajaxurl,
		data	: jQuery(this).serialize(),
		success	: m_extraformsuccess,
		error	: m_loginerror
	});

	return false;
}
function m_extraformsuccess(data) {
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
	m_register_events();
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
	m_register_events();
}

function m_registererror(data) {
	jQuery.fancybox.hideActivity();

	jQuery("#reg-error").html(membership.regproblem).show();
}

function m_loginsuccess(data) {

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
	m_register_events();

}

function m_loginerror(data) {
	jQuery.fancybox.hideActivity();

	jQuery("#login-error").html(membership.logpropblem).show();
}

function m_registersubmit() {

	if (jQuery("#reg_user_email").val().length < 1 || jQuery("#reg_password").val().length < 1) {
		    jQuery("#reg-error").html(membership.regmissing).show();
		    jQuery.fancybox.resize();
		    return false;
	}

	if (jQuery("#reg_password").val() != jQuery("#reg_password2").val()) {
		    jQuery("#reg-error").html(membership.regnomatch).show();
		    jQuery.fancybox.resize();
		    return false;
	}

	jQuery.fancybox.showActivity();

	jQuery.ajax({
		type	: 'POST',
		cache	: false,
		url		: membership.ajaxurl,
		data	: {	action : 'register_user', user_login: jQuery("#reg_user_login").val(), email : jQuery("#reg_user_email").val(), password : jQuery("#reg_password").val(), nonce : membership.registernonce, subscription: jQuery('#reg_subscription').val() },
		success	: m_registersuccess,
		error	: m_registererror
	});

	return false;
}

function m_loginsubmit() {

	if (jQuery("#login_user_login").val().length < 1 || jQuery("#login_password").val().length < 1) {
		    jQuery("#login-error").html(membership.logmissing).show();
		    jQuery.fancybox.resize();
		    return false;
	}

	jQuery.fancybox.showActivity();

	jQuery.ajax({
		type	: 'POST',
		cache	: false,
		url		: membership.ajaxurl,
		data	: {	action : 'login_user', user_login : jQuery("#login_user_login").val(), password : jQuery("#login_password").val(), nonce : membership.loginnonce, subscription: jQuery('#login_subscription').val() },
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
			'speedIn'		:	200,
			'speedOut'		:	200,
			'overlayShow'	:	false,
			'padding'		: 	0,
			'scrolling'		:   'no',
			'width'			: 	750,
			'autoDimensions': 	true,
			'onComplete'	:   m_register_events
		});



});