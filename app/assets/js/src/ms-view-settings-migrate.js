/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */
/*global wpmUi:false */

window.ms_init.view_settings_migrate = function init() {

	var migrationBar;

	/**
	 * Send migration requests
	 * 
	 * @param integer pass 
	 */
	function ms_do_migration() {
		jQuery.post( window.ajaxurl,{ action : 'ms_do_migration', 'security' : jQuery('input[name=migration_nonce]').val() },
		function(response){
			if ( response.success ) {
				migrationBar.value( response.data.percent );
				jQuery(".ms_migrate_message").html( response.data.message );
				if ( response.data.percent === 100 ) {
					jQuery(".ms_migrate_message").html( ms_data.lang.task_done );
					window.setTimeout(function(){
						jQuery(".ms_migrate_message").html( ms_data.lang.migrate_done ); 
						window.setTimeout(function(){ ms_functions.reload(); }, 3000);
					}, 6000);
				} else {
					ms_do_migration();
				}
			} else {
				migrationBar.value( 0 );
				jQuery(".ms_migrate_message").html( response.data.message );
				window.setTimeout(function(){ ms_functions.reload(); }, 6000);
			}
		}).fail(function() {
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
			migrationBar.value( 0 );
			jQuery(".ms_migrate_message").html( ms_data.lang.progress_title );
			ms_do_migration();
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