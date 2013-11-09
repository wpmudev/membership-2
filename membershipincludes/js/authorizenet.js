function cc_card_pick(card_image, card_num) {
	var $ = jQuery;

	if (card_image == null) {
		card_image = '#cardimage';
	}

	if (card_num == null) {
		card_num = '#card_num';
	}

	var numLength = $(card_num).val().length;
	var number = $(card_num).val();
	if (numLength > 10) {
		if ((number.charAt(0) == '4') && ((numLength == 13) || (numLength == 16))) {
			$(card_image).removeClass();
			$(card_image).addClass('cardimage visa_card');
		}
		else if ((number.charAt(0) == '5' && ((number.charAt(1) >= '1') && (number.charAt(1) <= '5'))) && (numLength == 16)) {
			$(card_image).removeClass();
			$(card_image).addClass('cardimage mastercard');
		}
		else if (number.substring(0, 4) == "6011" && (numLength == 16)) {
			$(card_image).removeClass();
			$(card_image).addClass('cardimage amex');
		}
		else if ((number.charAt(0) == '3' && ((number.charAt(1) == '4') || (number.charAt(1) == '7'))) && (numLength == 15)) {
			$(card_image).removeClass();
			$(card_image).addClass('cardimage discover_card');
		}
		else {
			$(card_image).removeClass();
			$(card_image).addClass('cardimage nocard');
		}
	}
}

(function($) {
	$(document).ready(function() {
		var locked = false;

		$("head").append('<link href="' + membership_authorize.stylesheet_url + '" rel="stylesheet" type="text/css">');

		$('body').on('change', '#auth-cim-profiles input', function() {
			$('#auth-new-cc-body').toggle($(this).parents('li').attr('id') == 'auth-new-cc');
		});

		$('body').on('submit', "form.membership_payment_form.authorizenet", function() {
			if (locked) {
				return;
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
	});
})(jQuery);