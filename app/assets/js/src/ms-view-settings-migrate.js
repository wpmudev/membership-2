/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */

window.ms_init.view_settings_migrate = function init() {

	var migrationTimer;

	function check_migration() {
		jQuery.post(
			window.ajaxurl, 
			{ 'action' : 'ms_check_migration', 'security' : jQuery('input[name=check_migration_nonce]').val() },
			function(response){
				if (response.status) {
					jQuery(".ms_migrate_progress").html( '<div class="bar" style="width:' + response.data.percent + '%"></div>' );
					jQuery(".ms_migrate_message").html( response.data.message );
			  		if ( response.data.percent === 100 ) {
						window.clearInterval( migrationTimer );
						jQuery(".ms_migrate_message").html( ms_data.lang.task_done );
						migrationTimer = window.setInterval( function(){
							jQuery(".ms_migrate_message").html( ms_data.lang.migrate_done );
							window.clearInterval( migrationTimer );
						}, 1000);
				  	}
				} else {
					window.clearInterval( migrationTimer );
					jQuery(".ms_migrate_message").html( ms_data.task_error );
				}
			}
		).fail(function() {
			window.clearInterval( migrationTimer );
			jQuery(".ms_migrate_message").html( ms_data.task_error );
		});
	}

	function start_migration() {
		jQuery.post( window.ajaxurl,{ action : 'ms_do_migration', 'security' : jQuery('input[name=migration_nonce]').val() });
		// Refresh the progress bar every 10 second.
		migrationTimer = window.setInterval( check_migration, 6000 );
	}

	jQuery(document).ready(function(){
		jQuery('.ms-migration-start').on('click', function(){
			jQuery(this).attr('disabled',true);
			start_migration();
		});
	});
};