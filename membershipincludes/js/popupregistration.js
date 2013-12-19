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
        if (typeof returned.errormsg != 'undefined') {
            // Oops an error
            alert(returned.errormsg);
        } else {
            // Content is being passed back so display
            jQuery('#fancybox-content div').html(data);
            jQuery.fancybox.resize();
        }
    }
    catch (e)
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

    if (typeof _coupon != 'undefined') {

        jQuery.ajax({
            type: 'POST',
            cache: false,
            url: membership.ajaxurl + '?action=buynow&subscription=' + _sub_id,
            data: {coupon_code: _coupon},
            success: m_couponsuccess,
            error: m_couponerror
        });

    }

    return false;
}

function m_signupsuccess(data) {
    jQuery.fancybox.hideActivity();

    try
    {
        returned = jQuery.parseJSON(data);
        if (typeof returned.errormsg != 'undefined') {
            // Oops an error
            alert(returned.errormsg);
        } else {
            // Content is being passed back so display
            jQuery('#fancybox-content div').html(data);
            jQuery.fancybox.resize();
        }
    }
    catch (e)
    {
        // Content
        jQuery('#fancybox-content div').html(data);
        jQuery.fancybox.resize();
    }
    m_register_events();
}

function m_signupeerror(data) {
    jQuery.fancybox.hideActivity();

    alert('Purchase error');
}

function m_signupform() {
    jQuery.fancybox.showActivity();

    var _coupon = jQuery('#subscription_coupon_code').val();
    var _sub_id = jQuery('#subscription_id').val();
    var _user_id = jQuery('#subscription_user_id').val();
    var _gateway = jQuery('#subscription_gateway').val();

    jQuery.ajax({
        type: 'POST',
        cache: false,
        url: membership.ajaxurl + '?action=purchaseform&subscription=' + _sub_id,
        data: {coupon_code: _coupon, user: _user_id, gateway: _gateway, subscription: _sub_id},
        success: m_signupsuccess,
        error: m_signupeerror
    });

    return false;
}

function m_extraform() {
    jQuery.fancybox.showActivity();

    jQuery.ajax({
        type: 'POST',
        cache: false,
        url: membership.ajaxurl,
        data: jQuery(this).serialize(),
        success: m_extraformsuccess,
        error: m_loginerror
    });

    return false;
}
function m_extraformsuccess(data) {
    jQuery.fancybox.hideActivity();

    try
    {
        returned = jQuery.parseJSON(data);
        if (typeof returned.errormsg != 'undefined') {
            // Oops an error
            jQuery("#reg-error").html(returned.errormsg).show('fast', function() {
                jQuery.fancybox.resize();
            });
        } else {
            // Content is being passed back so display
            jQuery('#fancybox-content div').html(data);
            jQuery.fancybox.resize();
        }
    }
    catch (e)
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
        if (typeof returned.errormsg != 'undefined') {
            // Oops an error
            jQuery("#reg-error").html(returned.errormsg).show('fast', function() {
                jQuery.fancybox.resize();
            });
        } else {
            // Content is being passed back so display
            jQuery('#fancybox-content div').html(data);
            jQuery.fancybox.resize();
        }
    }
    catch (e)
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
        if (typeof returned.errormsg != 'undefined') {
            // Oops an error
            jQuery("#login-error").html(returned.errormsg).show('fast', function() {
                jQuery.fancybox.resize();
            });
        } else {
            // Content is being passed back so display
            jQuery('#fancybox-content div').html(data);
            jQuery.fancybox.resize();
        }
    }
    catch (e)
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

    var post_data = jQuery('#reg-form').serializeArray();
    post_data.push({name: 'action', value: 'register_user'});
    post_data.push({name: 'nonce', value: membership.registernonce});

    jQuery.ajax({
        type: 'POST',
        cache: false,
        url: membership.ajaxurl,
        data: post_data,
        success: m_registersuccess,
        error: m_registererror
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
        type: 'POST',
        cache: false,
        url: membership.ajaxurl,
        data: {action: 'login_user', user_login: jQuery("#login_user_login").val(), password: jQuery("#login_password").val(), nonce: membership.loginnonce, subscription: jQuery('#login_subscription').val()},
        success: m_loginsuccess,
        error: m_loginerror
    });

    return false;
}

jQuery(document).ready(function() {
    jQuery("a.popover").fancybox({
        transitionIn: 'elastic',
        transitionOut: 'elastic',
        speedIn: 200,
        speedOut: 200,
        overlayShow: true,
		overlayOpacity: 0.4,
		overlayColor: '#000',
		showCloseButton: false,
        padding: 0,
        scrolling: 'no',
        autoDimensions: true,
        onComplete: m_register_events
    });
});