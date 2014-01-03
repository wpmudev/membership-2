(function($) {
	var locked = false;

	$(document).ready(function() {
		$("head").append('<link href="' + membership_authorize.stylesheet_url + '" rel="stylesheet" type="text/css">');
	});

	$(document).on('change', '#auth-cim-profiles input', function() {
		$('#auth-new-cc-body').toggle($(this).parents('li').attr('id') == 'auth-new-cc');
		if ($.fancybox) {
			$.fancybox.resize();
		}
	});

	$(document).on('submit', "form.membership_payment_form.authorizenet", function(e) {
		if (locked) {
			return false;
		}

		locked = true;
		$('html').css('cursor', 'wait');

		$.ajax({
			url: membership_authorize.return_url,
			type: 'POST',
			dataType: 'json',
			data: $(this).serialize(),
			success: function(data) {
				locked = false;
				$('html').css('cursor', 'default');
				if (typeof data != 'object') {
					alert(membership_authorize.payment_error_msg);
					return;
				}

				switch (data.status) {
					case 'error':
						var error_div = $('#authorize_errors');
						if ($.isArray(data.errors)) {
							error_div.html('');
							$.each(data.errors, function(i, e) {
								error_div.append('<p class="error">' + e + '</p>');
							});
							$('p.error').show();
						} else {
							alert(membership_authorize.payment_error_msg);
						}
						break;
					case 'success':
						if (typeof data.redirect != 'undefined' && data.redirect != 'no') {
							// Redirect to welcome page
							window.location.href = data.redirect;
						} else {
							// Show the message instead
							$('#fancybox-content div').html(data.message);
							$.fancybox.resize();
						}
						break;
				}
			},
			error: function() {
				locked = false;
				$('html').css('cursor', 'default');
				alert(membership_authorize.payment_error_msg);
			}
		});

		return false;
	});
})(jQuery);