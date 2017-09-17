<?php
/**
 * Class that handles Report functions.
 *
 * @since  1.1.2
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Report extends MS_Controller {

	const ACTION_EXPORT 		= 'export';


	/**
	 * Prepare the Report manager.
	 *
	 * @since  1.1.2
	 */
	public function __construct() {
		parent::__construct();

	}


	/**
	 * Main entry point: Processes the reports action.
	 *
	 * This function is called by the settings-controller whenever the
	 * Report tab provides a correct nonce. We will first find out which
	 * action to execute and then handle all the details...
	 *
	 * @since  1.1.2
	 */
	public function process() {

	}


	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.1.2
	 */
	public function admin_init() {
		$tab_key = 'report'; // Should be unique plugin-wide value of `&tab=`.

		$this->run_action(
			'ms_controller_settings_enqueue_scripts_' . $tab_key,
			'enqueue_scripts'
		);
	}



	/**
	 * Enqueue admin scripts in the settings screen.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' 	=> array( 'view_settings_report' ),
			'lang' 		=> array(
				'progress_title' 			=> __( 'Generating data...', 'membership2' ),
				'close_progress' 			=> __( 'Okay', 'membership2' ),
				'report_done' 				=> __( 'All done!', 'membership2' ),
				'task_start' 				=> __( 'Preparing...', 'membership2' ),
				'task_done' 				=> __( 'Cleaning up...', 'membership2' ),
				'task_report_member' 		=> __( 'Generating Member Data', 'membership2' ),
				'task_report_membership' 	=> __( 'Generating Membership Data', 'membership2' )
			),
		);

		lib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}
}
?>