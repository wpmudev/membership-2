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
		$fields = array(
			array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'migration_nonce',
				'value' => wp_create_nonce( 'ms_do_migration' ),
			),
			array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'check_migration_nonce',
				'value' => wp_create_nonce( 'ms_check_migration' ),
			),
			array(
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'class' => 'button-primary ms-migration-start',
				'value' => __( 'Start migration', 'membership2' ),
			)
		);
		ob_start();
		
		?>
		<div class="ms-wrap wrap">
			<div class="ms-welcome-box" method="POST">
				<h2 class="ms-welcome-title">
					<?php _e( 'Database Upgrade!', 'membership2' ); ?>
				</h2>

				<div class="ms-welcome-text">
					<?php _e( 'We have made some change to improve the performance of the <strong>Membership2</strong> plugin.<br/> The process can take long depending on the size of your data ', 'membership2' ); ?>
				</div>
				<div class="ms-welcome-image-box">
					<i class="wpmui-fa wpmui-fa-exchange" style="font-size: 20em;" aria-hidden="true"></i>
				</div>
				<div class="ms-welcome-text">
					<div class="ms_migrate_progress"></div>
					<div class="ms_migrate_message"></div>
					<?php
					foreach ( $fields as $field ) {
						MS_Helper_Html::html_element( $field );
					}
					?>
				</div>
				
			</div>
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