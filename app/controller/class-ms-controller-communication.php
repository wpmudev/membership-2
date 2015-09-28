<?php
/**
 * Controller for Automated Communications.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Communication extends MS_Controller {

	/**
	 * Ajax action name.
	 *
	 * @since  1.0.0
	 * @var string The ajax action name.
	 */
	const AJAX_ACTION_UPDATE_COMM = 'update_comm';

	/**
	 * Save communication form.
	 *
	 * @since  1.0.1.0
	 * @var  string
	 */
	const ACTION_SAVE_COMM = 'save_comm';

	/**
	 * Prepare Membership settings manager.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		do_action( 'ms_controller_communication_before', $this );

		$this->add_ajax_action(
			self::AJAX_ACTION_UPDATE_COMM,
			'ajax_action_update_communication'
		);

		$this->add_action(
			'ms_controller_membership_setup_completed',
			'auto_setup_communications'
		);

		$this->add_action(
			'ms_model_event',
			'process_event',
			10, 2
		);

		$this->add_action(
			'ms_cron_process_communications',
			'process_queue'
		);

		do_action( 'ms_controller_communication_after', $this );
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		$tab = '';
		if ( isset( $_GET['tab'] ) ) {
			$tab = $_GET['tab'];
		}

		/*
		 * Both in the Settings page and in the Membership Edit page the
		 * communication tab has the same name ('emails'), so we can use this
		 * info to better set-up our action hook.
		 */
		if ( MS_Controller_Settings::TAB_EMAILS == $tab ) {
			$this->run_action(
				'init',
				'admin_manager'
			);

			// Add custom buttons to the MCE editor (insert variable).
			$this->run_action(
				'admin_head',
				'add_mce_buttons'
			);
		}
	}

	/**
	 * Manages communication actions.
	 *
	 * Verifies GET and POST requests to manage settings.
	 *
	 * @since  1.0.1.0
	 */
	public function admin_manager() {
		$msg = 0;
		$redirect = false;

		if ( $this->is_admin_user() && $this->verify_nonce() ) {
			/**
			 * After verifying permissions this filters can be used by Add-ons
			 * to process their own settings.
			 *
			 * @since  1.0.1.0
			 */
			do_action( 'ms_admin_communication_manager' );

			$fields = array( 'type', 'subject', 'email_body' );

			if ( self::validate_required( array( 'comm_type' ) )
				&& MS_Model_Communication::is_valid_communication_type( $_POST['comm_type'] )
			) {
				// Load comm type from user select.
				$redirect = esc_url_raw(
					remove_query_arg(
						'msg',
						add_query_arg( 'comm_type', $_POST['comm_type'] )
					)
				);
			} elseif ( isset( $_POST['save_email'] )
				&& self::validate_required( $fields )
			) {
				// Save email template form.
				$default_type = MS_Model_Communication::COMM_TYPE_REGISTRATION;
				if ( ! empty( $_REQUEST['membership_id'] ) ) {
					$membership_id = intval( $_REQUEST['membership_id'] );
					$comm_types = array_keys(
						MS_Model_Communication::get_communication_type_titles(
							$membership_id
						)
					);
					$default_type = reset( $comm_types );
				}

				if ( ! empty( $_POST['type'] )
					&& MS_Model_Communication::is_valid_communication_type( $_POST['type'] )
				) {
					$type = $_POST['type'];
				} else {
					$type = $default_type;
				}

				$msg = $this->save_communication( $type, $_POST );
				$redirect = esc_url_raw(
					add_query_arg(
						array(
							'comm_type' => urlencode( $_POST['type'] ),
							'msg' => $msg,
						)
					)
				);
			}
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit();
		}
	}

	/**
	 * Handles an event and process the correct communication if required.
	 *
	 * @since  1.0.1.0
	 * @param  MS_Model_Event $event The event that is processed.
	 * @param  mixed $data The data passed to the event handler.
	 */
	public function process_event( $event, $data ) {
		if ( $data instanceof MS_Model_Relationship ) {
			$subscription = $data;
			$membership = $data->get_membership();
		} elseif ( $data instanceof MS_Model_Membership ) {
			$subscription = false;
			$membership = $data;
		} else {
			$subscription = false;
			$membership = false;
		}

		$enqueue = array();
		$process = array();

		switch ( $event->type ) {
			case MS_Model_Event::TYPE_MS_CANCELED:
				$enqueue[] = MS_Model_Communication::COMM_TYPE_CANCELLED;
				break;

			case MS_Model_Event::TYPE_CREDIT_CARD_EXPIRE:
				$enqueue[] = MS_Model_Communication::COMM_TYPE_CREDIT_CARD_EXPIRE;
				break;

			case MS_Model_Event::TYPE_PAYMENT_FAILED:
				$enqueue[] = MS_Model_Communication::COMM_TYPE_FAILED_PAYMENT;
				break;

			case MS_Model_Event::TYPE_MS_DEACTIVATED:
				$enqueue[] = MS_Model_Communication::COMM_TYPE_FINISHED;
				break;

			case MS_Model_Event::TYPE_UPDATED_INFO:
				$enqueue[] = MS_Model_Communication::COMM_TYPE_INFO_UPDATE;
				break;

			case MS_Model_Event::TYPE_PAID:
				$enqueue[] = MS_Model_Communication::COMM_TYPE_INVOICE;
				break;

			case MS_Model_Event::TYPE_MS_SIGNED_UP:
				$process[] = MS_Model_Communication::COMM_TYPE_REGISTRATION_FREE;
				$process[] = MS_Model_Communication::COMM_TYPE_REGISTRATION;
				break;

			case MS_Model_Event::TYPE_MS_RENEWED:
				$process[] = MS_Model_Communication::COMM_TYPE_RENEWED;
				break;

			case MS_Model_Event::TYPE_MS_REGISTERED:
				$process[] = MS_Model_Communication::COMM_TYPE_SIGNUP;
				break;

			case MS_Model_Event::TYPE_MS_MOVED:
				break;
			case MS_Model_Event::TYPE_MS_EXPIRED:
				break;
			case MS_Model_Event::TYPE_MS_TRIAL_EXPIRED:
				break;
			case MS_Model_Event::TYPE_MS_DROPPED:
				break;
			case MS_Model_Event::TYPE_MS_BEFORE_FINISHES:
				break;
			case MS_Model_Event::TYPE_MS_AFTER_FINISHES:
				break;
			case MS_Model_Event::TYPE_MS_BEFORE_TRIAL_FINISHES:
				break;
			case MS_Model_Event::TYPE_MS_TRIAL_FINISHED:
				break;
			case MS_Model_Event::TYPE_PAYMENT_PENDING:
				break;
			case MS_Model_Event::TYPE_PAYMENT_DENIED:
				break;
			case MS_Model_Event::TYPE_PAYMENT_BEFORE_DUE:
				break;
			case MS_Model_Event::TYPE_PAYMENT_AFTER_DUE:
				break;
		}

		foreach ( $enqueue as $type ) {
			$comm = MS_Model_Communication::get_communication( $type, $membership );
			if ( ! $comm ) { continue; }
			$comm->enqueue_messages( $event, $data );
		}

		foreach ( $process as $type ) {
			$comm = MS_Model_Communication::get_communication( $type, $membership );
			if ( ! $comm ) { continue; }
			$comm->process_communication( $event, $data );
		}
	}

	/**
	 * Send enqueued emails now.
	 *
	 * @since  1.0.1.0
	 * @internal  Cron handler
	 * @see  filter ms_cron_process_communications
	 */
	public function process_queue() {
		$comms = MS_Model_Communication::get_communications( null );

		foreach ( $comms as $comm ) {
			$comm->process_queue();
		}
	}

	/**
	 * Handle Ajax update comm field action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_update_comm
	 *
	 * @since  1.0.0
	 */
	public function ajax_action_update_communication() {
		do_action(
			'ms_controller_communication_ajax_action_update_communication_before',
			$this
		);

		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'type', 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			lib3()->array->strip_slashes( $_POST, 'value' );

			$membership_id = null;
			if ( isset( $_POST['membership_id'] ) ) {
				$membership_id = intval( $_POST['membership_id'] );
			}
			$type = $_POST['type'];
			$field = $_POST['field'];
			$value = $_POST['value'];

			$comm = MS_Model_Communication::get_communication(
				$type,
				$membership_id,
				true
			);

			$comm->$field = $value;
			$comm->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		do_action(
			'ms_controller_communication_ajax_action_update_communication_after',
			$this
		);

		echo apply_filters(
			'ms_controller_commnucation_ajax_action_update_communication_msg',
			$msg,
			$this
		);
		exit;
	}

	/**
	 * Auto setup communications.
	 *
	 * Fires after a membership setup is completed.
	 *
	 * Related Action Hooks:
	 * - ms_controller_membership_setup_completed
	 *
	 * @since  1.0.0
	 * @param MS_Model_Membership $membership
	 */
	public function auto_setup_communications( $membership ) {
		/*
		 * Note: We intentionally set the parameter to 0. This
		 * function should set up default messages when first membership is
		 * created. It should not override default messages with membership-
		 * specific ones.
		 */
		$comms = MS_Model_Communication::get_communications( 0 );

		foreach ( $comms as $comm ) {
			$comm->enabled = true;
			$comm->save();
		}

		do_action(
			'ms_controller_communication_auto_setup_communications_after',
			$membership,
			$this
		);
	}

	/**
	 * Handle saving of Communication settings.
	 *
	 * @since  1.0.0
	 *
	 * @param mixed[] $fields The data to process.
	 */
	public function save_communication( $type, $fields ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$membership_id = null;
		if ( isset( $_POST['membership_id'] ) ) {
			$membership_id = intval( $_POST['membership_id'] );
		}

		$comm = MS_Model_Communication::get_communication(
			$type,
			$membership_id,
			true
		);

		if ( ! empty( $fields ) ) {
			lib3()->array->equip(
				$fields,
				'enabled',
				'subject',
				'email_body',
				'period_unit',
				'period_type',
				'cc_enabled',
				'cc_email'
			);

			$comm->enabled = lib3()->is_true( $fields['enabled'] );
			$comm->subject = $fields['subject'];
			$comm->message = $fields['email_body'];
			$comm->period = array(
				'period_unit' => $fields['period_unit'],
				'period_type' => $fields['period_type'],
			);
			$comm->cc_enabled = ! empty( $fields['cc_enabled'] );
			$comm->cc_email = $fields['cc_email'];

			$comm->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		return apply_filters(
			'ms_controller_communication_save',
			$msg,
			$type,
			$fields,
			$this
		);
	}

	/**
	 * Prepare WordPress to add our custom TinyMCE button to the WYSIWYG editor.
	 *
	 * @since  1.0.1.0
	 *
	 * @see class-ms-view-settings-edit.php (function render_tab_messages_automated)
	 * @see ms-view-settings-automated-msg.js
	 */
	static public function add_mce_buttons() {
		// Check user permissions.
		if ( ! current_user_can( 'edit_posts' )
			&& ! current_user_can( 'edit_pages' )
		) {
			return;
		}

		// Check if WYSIWYG is enabled.
		if ( 'true' != get_user_option( 'rich_editing' ) ) {
			return;
		}

		add_filter(
			'mce_external_plugins',
			array( __CLASS__, 'add_variables_plugin' )
		);
		add_filter(
			'mce_buttons',
			array( __CLASS__, 'register_variables_button' )
		);
	}

	/**
	 * Associate a javascript file with the new TinyMCE button.
	 *
	 * Hooks Filters:
	 * - mce_external_plugins
	 *
	 * @since  1.0.0
	 *
	 * @param  array $plugin_array List of default TinyMCE plugin scripts.
	 * @return array Updated list of TinyMCE plugin scripts.
	 */
	public static function add_variables_plugin( $plugin_array ) {
		$plugin_url = MS_Plugin::instance()->url;

		// This is a dummy reference (ms-admin.js is always loaded)!
		// Actually this line would not be needed, but WordPress will not show
		// our button when this is missing...
		$plugin_array['ms_variable'] = $plugin_url . 'app/assets/js/ms-admin.js';

		return $plugin_array;
	}

	/**
	 * Register new "Insert variables" button in the editor.
	 *
	 * Hooks Filters:
	 * - mce_buttons
	 *
	 * @since  1.0.0
	 *
	 * @param  array $buttons List of default TinyMCE buttons.
	 * @return array Updated list of TinyMCE buttons.
	 */
	public static function register_variables_button( $buttons ) {
		array_push( $buttons, 'ms_variable' );
		return $buttons;
	}

}