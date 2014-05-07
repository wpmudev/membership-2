jQuery(document).ready(function($){
	$('#wp-admin-bar-membership-simulate').find('a').click(function(e){
		e.preventDefault();
		
		var $this = $(this);
		
		$('#wp-admin-bar-membership-simulate').removeClass('hover').find('> div').filter(':first-child').html( ms.switching_text );
		
		$.get($this.attr('href')).done(function(data){
			window.location.href = window.location.href;
		});
	});
	$( '.ms-date' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });

});