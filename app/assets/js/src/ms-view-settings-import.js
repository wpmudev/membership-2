/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */
/*global wpmUi:false */

window.ms_init.view_settings_import = function init() {

	var form_import = jQuery( '.ms-settings-import' ),
		btn_download = form_import.find( '#btn-download' ),
		btn_import = form_import.find( '#btn-import' ),
		chk_clear = form_import.find( '#clear_all' ),
		sel_batchsize = form_import.find( '#batchsize' ),
		the_popup = null,
		the_progress = null,
		queue = [],
		queue_count = 0;

	/**
	 * Checks if the browser supports downloading js-created files.
	 */
	function support_download() {
		var a = document.createElement( 'a' );
		if ( undefined === a.download ) { return false; }
		if ( undefined === window.Blob ) { return false; }
		if ( undefined === window.JSON ) { return false; }
		if ( undefined === window.JSON.stringify ) { return false; }

		return true;
	}

	/**
	 * Tries to provide the specified data as a file-download.
	 */
	function download( content, filename, contentType ) {
		var a, blob;
		if ( ! support_download() ) { return false; }

		if ( ! contentType ) { contentType = 'application/octet-stream'; }
		a = document.createElement( 'a' );
		blob = new window.Blob([content], {'type':contentType});

		a.href = window.URL.createObjectURL(blob);
		a.download = filename;
		a.click();
	}

	/**
	 * Provides the import data object as file-download.
	 */
	function download_import_data() {
		var content;

		if ( undefined === window._ms_import_obj ) { return; }

		content = window.JSON.stringify( window._ms_import_obj );
		download( content, 'protected-content.json' );
	}

	/**
	 * Displays the Import-Progress popup
	 */
	function show_popup() {
		var content = jQuery( '<div></div>' );

		the_progress = wpmUi.progressbar();

		content.append( the_progress.$() );
		the_popup = wpmUi.popup()
			.title( ms_data.lang.progress_title, false )
			.modal( true, false )
			.content( content, true )
			.size( 600, 140 )
			.show();
	}

	/**
	 * Hides the Import-Progress popup
	 */
	function allow_hide_popup() {
		var el = jQuery( '<div style="text-align:center"></div>' ),
			btn = jQuery( '<a href="#" class="close"></a>' );

		btn.text( ms_data.lang.close_progress );
		if ( ms_data.close_link ) {
			btn.attr( 'href', ms_data.close_link );
		}
		btn.addClass( 'button-primary' );
		btn.appendTo( el );

		the_popup.content( el, true )
			.modal( true, true )
			.title( ms_data.lang.import_done );
	}

	/**
	 * Returns the next batch for import.
	 */
	function get_next_batch( max_items ) {
		var batch = {},
			count = 0,
			item;

		batch.items = [];
		batch.item_count = 0;
		batch.label = '';
		batch.source = window._ms_import_obj.source_key;

		for ( count = 0; count < max_items; count += 1 ) {
			item = queue.shift();

			if ( undefined === item ) {
				// Whole queue is processed.
				break;
			}

			batch.label = item.label;
			delete item.label;

			batch.items.push( item );
			batch.item_count += 1;
		}

		return batch;
	}

	/**
	 * Send the next item from the import queue to the ajax handler.
	 */
	function process_queue() {
		var icon ='<i class="wpmui-loading-icon"></i> ',
			batchsize = sel_batchsize.val(),
			batch = get_next_batch( batchsize );

		if ( ! batch.item_count ) {
			// All items were sent - hide the progress bar and show close button.
			allow_hide_popup();
			return;
		}

		// Update the progress bar.
		the_progress
			.value( queue_count - queue.length )
			.label( icon + '<span>' + batch.label + '</span>' );

		// Prepare the ajax payload.
		batch.action = btn_import.val();
		delete batch.label;

		// Send the ajax request and call this function again when done.
		jQuery.post(
			window.ajaxurl,
			batch,
			process_queue
		);
	}

	/**
	 * Starts the import process: A popup is opened to display the progress and
	 * then all import items are individually sent to the plugin via Ajax.
	 */
	function start_import() {
		var k, data, count,
			lang = ms_data.lang;

		queue = [];

		// This will prepare the import process
		queue.push({
			'task': 'start',
			'clear': chk_clear.is(':checked'),
			'label': lang.task_start
		});

		// _ms_import_obj is a JSON object, so we skip the .hasOwnProperty() check.
		count = 0;
		for ( k in window._ms_import_obj.memberships ) {
			data = window._ms_import_obj.memberships[k];
			count += 1;
			queue.push({
				'task': 'import-membership',
				'data': data,
				'label': lang.task_import_membership +  ': ' + count + '...'
			});
		}

		count = 0;
		for ( k in window._ms_import_obj.members ) {
			data = window._ms_import_obj.members[k];
			count += 1;
			queue.push({
				'task': 'import-member',
				'data': data,
				'label': lang.task_import_member +  ': ' + count + '...'
			});
		}

		for ( k in window._ms_import_obj.settings ) {
			data = window._ms_import_obj.settings[k];
			queue.push({
				'task': 'import-settings',
				'setting': k,
				'value': data,
				'label': lang.task_import_settings + '...'
			});
		}

		// Finally clean up after the import
		queue.push({
			'task': 'done',
			'label': lang.task_done
		});

		// Display the import progress bar
		show_popup();
		queue_count = queue.length;
		the_progress.max( queue_count );

		// Start to process the import queue
		process_queue();
	}

	if ( support_download() ) {
		btn_download.click( download_import_data );
	} else {
		btn_download.hide();
	}

	btn_import.click( start_import );

};