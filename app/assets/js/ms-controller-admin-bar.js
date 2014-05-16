jQuery(document).ready(function($){
	$('#wp-admin-bar-membership-simulate').find('a').click(function(e){
		$('#wp-admin-bar-membership-simulate').removeClass('hover').find('> div').filter(':first-child').html( ms.switching_text );
	});
	$( '.ms-date' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });

});