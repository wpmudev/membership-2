/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */
/*global wpmUi:false */

window.ms_init.view_settings_migrate = function init() {

	var migrationTimer,
		migrationBar;

	/**
	 * Send migration requests
	 * 
	 * @param integer pass 
	 */
	function do_migration(pass) {
		jQuery.post( window.ajaxurl,{ action : 'ms_do_migration', 'pass' : pass, 'security' : jQuery('input[name=migration_nonce]').val() });
	}

	/**
	 * Check migration status
	 */
	function check_migration() {
		jQuery.post(
			window.ajaxurl, 
			{ 'action' : 'ms_check_migration', 'security' : jQuery('input[name=check_migration_nonce]').val() },
			function( response ){
				if ( response.success ) {
					migrationBar.value( response.data.percent );
					jQuery(".ms_migrate_message").html( response.data.message );
					if ( typeof response.data.pass !== 'undefined' ) {
						do_migration(response.data.pass);
					}
			  		if ( response.data.percent === 100 ) {
						window.clearInterval( migrationTimer );
						jQuery(".ms_migrate_message").html( ms_data.lang.task_done );
						window.setTimeout(function(){
							jQuery(".ms_migrate_message").html( ms_data.lang.migrate_done ); 
							window.setTimeout(function(){ ms_functions.reload(); }, 3000);
						}, 6000);
						
				  	}
				} else {
					migrationBar.value( 0 );
					jQuery(".ms_migrate_message").html( response.data.message );
					window.clearInterval( migrationTimer );
					window.setTimeout(function(){ ms_functions.reload(); }, 6000);
				}
			}
		).fail(function() {
			window.clearInterval( migrationTimer );
			jQuery(".ms_migrate_message").html( ms_data.lang.task_error );
			window.setTimeout(function(){ ms_functions.reload(); }, 6000);
		});
	}

	

	/**
	 * Migration button
	 */
	jQuery(document).ready(function(){
		jQuery('.ms-migration-start').on('click', function(){
			jQuery(this).attr('disabled',true);
			migrationBar = wpmUi.progressbar();
			jQuery(".ms_migrate_progress").append( migrationBar.$() );
			do_migration(0);
			migrationTimer = window.setInterval( check_migration, 6000 );
		});

		jQuery('.ms-migration-ignore').on('click', function(){
			jQuery(this).attr('disabled',true);
			jQuery.post(
				window.ajaxurl, 
				{ 'action' : 'ms_ignore_migration', 'security' : jQuery('input[name=ignore_migration_nonce]').val() },
				function( response ){
					ms_functions.reload();
				}
			).fail(function() {
				ms_functions.reload();
			});
		});
	});
};