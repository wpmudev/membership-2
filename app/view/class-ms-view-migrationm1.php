<?php
/**
 * Special View that is displayed to complete the migration from M1.
 *
 * @since  1.0.0
 */
class MS_View_MigrationM1 extends MS_View {

	/**
	 * Returns the HTML code of the view.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return string
	 */
	public function to_html() {
		$model = MS_Factory::create( 'MS_Model_Import_Membership' );

		if ( MS_Plugin::is_network_wide() && defined( 'BLOG_ID_CURRENT_SITE' ) ) {
			switch_to_blog( BLOG_ID_CURRENT_SITE );
			$model->prepare();
			restore_current_blog();
		} else {
			$model->prepare();
		}

		$view = MS_Factory::create( 'MS_View_Settings_Import' );
		$view->data = array( 'model' => $model, 'compact' => true );
		$msg = __(
			'Tipp: You can also import your data later by visiting the Admin page <b>Membership2 > Settings > Import Tool</b>.',
			MS_TEXT_DOMAIN
		);

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<h2>
				<?php _e( 'Import Your Membership Data To Membership2', MS_TEXT_DOMAIN ); ?>
			</h2>
			<?php
			if ( MS_Plugin::is_network_wide() ) {
				$msg .= '<br><br>' . __(
					'You have enabled Network Wide Protection. We will import Membership data from your main blog.',
					MS_TEXT_DOMAIN
				);
			}

			lib2()->ui->admin_message( $msg, 'info' );
			?>
			<div class="ms-settings-import">
				<?php echo $view->to_html(); ?>
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
			'ms_init' => array( 'view_settings_import' ),
			'close_link' => MS_Controller_Plugin::get_admin_url(),
			'lang' => array(
				'progress_title' => __( 'Importing data...', MS_TEXT_DOMAIN ),
				'close_progress' => __( 'Okay', MS_TEXT_DOMAIN ),
				'import_done' => __( 'All done!', MS_TEXT_DOMAIN ),
				'task_start' => __( 'Preparing...', MS_TEXT_DOMAIN ),
				'task_done' => __( 'Cleaning up...', MS_TEXT_DOMAIN ),
				'task_import_member' => __( 'Importing Member', MS_TEXT_DOMAIN ),
				'task_import_membership' => __( 'Importing Membership', MS_TEXT_DOMAIN ),
				'task_import_settings' => __( 'Importing Settings', MS_TEXT_DOMAIN ),
			),
		);

		lib2()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

}