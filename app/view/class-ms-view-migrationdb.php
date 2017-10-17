<?php
/**
 * Special View that is displayed to complete the migration to custom dtabase tables
 *
 * @since  1.1.3
 */
class MS_View_MigrationDb extends MS_View {


	/**
	 * Returns the HTML code of the view.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		
		ob_start();
		
		?>
		<div class="ms-wrap wrap">
			<h2>
				<?php _e( 'Migrate your Membership data', 'membership2' ); ?>
			</h2>
			
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enquque scripts and styles used by this special view.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' 		=> array( 'view_settings_migrate' ),
			'close_link' 	=> MS_Controller_Plugin::get_admin_url(),
			'lang' 			=> array(
				'progress_title' 			=> __( 'Migrating data...', 'membership2' ),
				'close_progress' 			=> __( 'Okay', 'membership2' ),
				'migrate_done' 				=> __( 'All done!', 'membership2' ),
				'task_start' 				=> __( 'Preparing...', 'membership2' ),
				'task_done' 				=> __( 'Cleaning up...', 'membership2' ),
				'task_error' 				=> __( 'Migration did not complete. Please try again', 'membership2' )
			),
		);

		lib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}
}
?>