<?php
/**
 * Class that handles Import/Export functions.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Import extends MS_Controller {

	// Action definitions.
	const ACTION_EXPORT = 'export';
	const ACTION_PREVIEW = 'preview';

	// Ajax action: Import data.
	const AJAX_ACTION_IMPORT = 'ms_import';

	// Ajax action: Save an automatic transaction matching (Billings page).
	const AJAX_ACTION_MATCH = 'ms_save_matching';

	// Ajax action: Retry to process a single transaction (Billings page).
	const AJAX_ACTION_RETRY = 'transaction_retry';

	/**
	 * Prepare the Import manager.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_ajax_action(
			self::AJAX_ACTION_IMPORT,
			'ajax_action_import'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_MATCH,
			'ajax_action_match'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_RETRY,
			'ajax_action_retry'
		);
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		$tab_key = 'import'; // should be unique plugin-wide value of `&tab=`.

		$this->run_action(
			'ms_controller_settings_enqueue_scripts_' . $tab_key,
			'enqueue_scripts'
		);
	}

	/**
	 * Handles the matching of transaction details with a membership.
	 *
	 * Expected output:
	 *   OK:Message to display
	 *   ERR
	 *
	 * @since  1.0.1.2
	 */
	public function ajax_action_match() {
		$res = 'ERR';

		if ( ! $this->is_admin_user() ) {
			return;
		}

		$fields_match = array( 'match_with', 'source', 'source_id' );

		// Save details of a single invoice.
		if ( $this->verify_nonce()
			&& self::validate_required( $fields_match )
		) {
			$source = $_POST['source'];
			$source_id = $_POST['source_id'];
			$match_id = $_POST['match_with'];

			if ( MS_Model_Import::match_with_source( $match_id, $source_id, $source ) ) {
				$res = 'OK:' . __( 'Matching details saved. Future transactions are automatically processed from now on!', MS_TEXT_DOMAIN );
			}
		}

		echo $res;
		exit;
	}

	/**
	 * Retries to process a single error-state transaction.
	 *
	 * Expected output:
	 *   OK
	 *   ERR
	 *
	 * @since  1.0.1.2
	 */
	public function ajax_action_retry() {
		$res = 'ERR';

		if ( ! $this->is_admin_user() ) {
			return;
		}

		$fields_retry = array( 'id' );

		// Save details of a single invoice.
		if ( $this->verify_nonce()
			&& self::validate_required( $fields_retry )
		) {
			$log_id = intval( $_POST['id'] );

			if ( MS_Model_Import::retry_to_process( $log_id ) ) {
				$res = 'OK';
			}

			$log = MS_Factory::load( 'MS_Model_Transactionlog', $log_id );
			$res .= ':' . $log->description;
		}

		echo $res;
		exit;
	}

	/**
	 * Handles an import batch that is sent via ajax.
	 *
	 * One batch includes multiple import commands that are to be processed in
	 * the specified order.
	 *
	 * Expected output:
	 *   OK:<number of successful commands>
	 *   ERR
	 *
	 * @since  1.0.0
	 */
	public function ajax_action_import() {
		$res = 'ERR';
		$success = 0;

		if ( ! isset( $_POST['items'] ) || ! isset( $_POST['source'] ) ) {
			echo $res;
			exit;
		}

		$batch = $_POST['items'];
		$source = $_POST['source'];

		$res = 'OK';
		foreach ( $batch as $item ) {
			if ( $this->process_item( $item, $source ) ) {
				$success += 1;
			}
		}

		echo $res . ':' . $success;
		exit;
	}

	/**
	 * Processes a single import command.
	 *
	 * @since  1.0.0
	 * @param  array $item The import command.
	 */
	protected function process_item( $item, $source ) {
		$res = false;

		lib2()->array->equip( $item, 'task', 'data' );
		$task = $item['task'];
		$data = $item['data'];
		$model = MS_Factory::create( 'MS_Model_Import' );
		$model->source_key = $source;

		// Set MS_STOP_EMAILS modifier to suppress any outgoing emails.
		MS_Plugin::set_modifier( 'MS_STOP_EMAILS', true );

		// Possible tasks are defined in ms-view-settings-import.js
		switch ( $task ) {
			case 'start':
				lib2()->array->equip( $item, 'clear' );
				$clear = lib2()->is_true( $item['clear'] );
				$model->start( $clear );
				$res = true;
				break;

			case 'import-membership':
				// Function expects an object, not an array!
				$data = (object) $data;
				$model->import_membership( $data );
				$res = true;
				break;

			case 'import-member':
				// Function expects an object, not an array!
				$data = (object) $data;
				$model->import_member( $data );
				$res = true;
				break;

			case 'import-settings':
				lib2()->array->equip( $item, 'setting', 'value' );
				$setting = $item['setting'];
				$value = $item['value'];
				$model->import_setting( $setting, $value );
				$res = true;
				break;

			case 'done':
				$model->done();
				$res = true;
				break;
		}

		/**
		 * After the import action was complated notify other objects and
		 * add-ons.
		 *
		 * @since  1.0.0
		 */
		do_action( 'ms_import_action_' . $task, $item );

		return $res;
	}

	/**
	 * Main entry point: Processes the import/export action.
	 *
	 * This function is called by the settings-controller whenever the
	 * Import/Export tab provides a correct nonce. We will first find out which
	 * action to execute and then handle all the details...
	 *
	 * @since  1.0.0
	 */
	public function process() {
		lib2()->array->equip_post( 'action', 'import_source' );
		$action = $_POST['action'];

		if ( isset( $_POST['submit'] ) ) {
			$action = $_POST['submit'];
		}

		switch ( $action ) {
			case self::ACTION_EXPORT:
				$handler = MS_Factory::create( 'MS_Model_Import_Export' );
				$handler->process();
				break;

			case self::ACTION_PREVIEW:
				$view = MS_Factory::create( 'MS_View_Settings_Import' );
				$model_name = 'MS_Model_Import_' . $_POST['import_source'];
				$model = null;

				try {
					$model = MS_Factory::create( $model_name );
				} catch( Exception $ex ) {
					self::_message(
						'error',
						__( 'Coming soon: This import source is not supported yet...', MS_TEXT_DOMAIN )
					);
				}

				if ( is_a( $model, 'MS_Model_Import' ) ) {
					if ( $model->prepare() ) {
						$data = array(
							'model' => $model,
						);

						$view->data = apply_filters(
							'ms_view_import_data',
							$data
						);

						self::_message(
							'preview',
							apply_filters(
								'ms_view_import_preview',
								$view->to_html()
							)
						);
					}
				}
				break;
		}
	}

	/**
	 * Enqueue admin scripts in the settings screen.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array( 'view_settings_import' ),
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
