/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */
/*global wpmUi:false */

window.ms_init.view_settings_migrate = function init() {

	var migrationTimer;

	function check_migration() {
		jQuery.ajax({
			url: window.ajaxurl,
			data : { action : 'ms_check_migration'},
			success:function(response){
				if ( typeof response.data !== 'undefined' ) {
					jQuery("#progress").html( '<div class="bar" style="width:' + response.data.percent + '%"></div>' );
					jQuery("#message").html( response.data.message );
			  		if ( response.data.percent == 100 ) {
						window.clearInterval( migrationTimer );
						jQuery("#message").html( ms_data.lang.task_done );
						migrationTimer = window.setInterval( function(){
							jQuery("#message").html( ms_data.lang.migrate_done );
							window.clearInterval(migrationTimer);
						}, 1000);
				  	}
				} else {
					jQuery("#message").html(ms_data.task_error);
				}
			}
		});
	}

	function start_migration() {
		jQuery.ajax({url: window.ajaxurl, data : { action : 'ms_do_migration'} });
		// Refresh the progress bar every 1 second.
		migrationTimer = window.setInterval(check_migration, 1000);
	}
}