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
        var _current_form = jQuery(this);
        jQuery(_current_form).find('.message').addClass('hidden');
        jQuery.post(_aim_return_url, jQuery(this).serialize(), function(data) {
            if (data.status == "success") {
                jQuery("#reg-form").load(_permalink_url+" #reg-form", {action: 'validatepage2', custom: data.custom});
                window.location += '#';
            } else {
                jQuery(_current_form).find('.message').html(data.message);
                if (data.more) {
                    jQuery(_current_form).find('.message').append(data.more);
                }
                jQuery(_current_form).find('.message').removeClass('hidden');
            }
        }, "json");
        
        return false;
    });
});
  