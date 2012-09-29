function cc_card_pick(card_image, card_num){
    if (card_image == null) {
        card_image = '#cardimage';
    }
    
    if (card_num == null) {
        card_num = '#card_num';
    }
  
    numLength = jQuery(card_num).val().length;
    number = jQuery(card_num).val();
    if (numLength > 10) {
        if((number.charAt(0) == '4') && ((numLength == 13)||(numLength==16))) { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage visa_card'); }
        else if((number.charAt(0) == '5' && ((number.charAt(1) >= '1') && (number.charAt(1) <= '5'))) && (numLength==16)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage mastercard'); }
        else if(number.substring(0,4) == "6011" && (numLength==16)) 	{ jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage amex'); }
        else if((number.charAt(0) == '3' && ((number.charAt(1) == '4') || (number.charAt(1) == '7'))) && (numLength==15)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage discover_card'); }
        else { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage nocard'); }
    }
}

jQuery(document).ready( function() {
    jQuery(".noautocomplete").attr("autocomplete", "off");
    jQuery("form.membership_payment_form.authorizenet").submit(function () {
		var error_div = jQuery('#authorize_errors');
        jQuery.ajax({
			url: _authorize_return_url,
			type: 'POST',
			dataType: 'json',
			data: jQuery(this).serialize(),
			beforeSend: function() {
				error_div.fadeOut(400).html('');
			},
			success: function(data) {
				if(typeof data != 'object')
					alert(_authorize_payment_error_msg);
				switch(data.status) {
					case 'error':
						var error_div = jQuery('#authorize_errors');
						if(jQuery.isArray(data.errors)) {
							jQuery.each(data.errors, function(i,e) {
								error_div.append('<p>' + e + '</p>');
							});
							error_div.fadeIn(400);
						} else {
							alert(_authorize_payment_error_msg);
						}
						break;
					case 'success':
						if(typeof data.redirect != 'undefined') {
							console.log(data.redirect);
							window.location.href = data.redirect;
						}
						break;
				}
			},
			error: function(data) {
				alert(_authorize_payment_error_msg);
			}
			
		});
        
        return false;
    });
	
});