<?php
/**
 * Controller for managing Members and Membership relationships.
 *
 * Manages the Member and the member's Memberships.
 *
 * @since 1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Member extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_CHANGE_MEMBERSHIPS = 'member_subscriptions';

	/**
	 * Prepare the Member manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_action(
			'ms_controller_membership_setup_completed',
			'add_current_user'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_CHANGE_MEMBERSHIPS,
			'ajax_action_change_memberships'
		);
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since 2.0.0
	 */
	public function admin_init() {
		$hooks = array(
			MS_Controller_Plugin::admin_page_hook( 'members' ),
			MS_Controller_Plugin::admin_page_hook( 'add-members' ),
		);

		foreach ( $hooks as $hook ) {
			$this->run_action( 'load-' . $hook, 'members_admin_page_process' );
			$this->run_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
			$this->run_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
		}
	}

	/**
	 * Add the current user to the Members-List.
	 *
	 * This does NOT assign any membership to the user, but ensures that the
	 * admin user appears in the Members-List
	 *
	 * @since 1.0.4.5
	 */
	public function add_current_user() {
		$member = MS_Factory::load(
			'MS_Model_Member',
			get_current_user_id()
		);

		$member->is_member = true;
		$member->save();
	}

	/**
	 * Manages membership actions.
	 *
	 * Verifies GET and POST requests to manage members
	 *
	 * @todo It got complex, maybe consider using ajax editing or create a new edit page with all member
	 *     membership fields (active, memberships, start, end, gateway)
	 *
	 * @since 1.0.0
	 */
	public function members_admin_page_process() {
		$msg = 0;
		$redirect = false;

		if ( $this->is_admin_user() ) {
			$fields_new = array( 'new_member', 'action' );
			$fields_edit = array( 'member_id', 'action' );

			// Execute list table single action.
			if ( $this->verify_nonce( null, 'GET' )
				&& self::validate_required( $fields_edit, 'GET' )
			) {
				$msg = $this->member_list_do_action(
					$_GET['action'],
					array( $_GET['member_id'] )
				);

				$redirect = esc_url_raw(
					add_query_arg(
						array( 'msg' => $msg ),
						remove_query_arg(
							array( 'member_id', 'action', '_wpnonce' )
						)
					)
				);
			}

			// Execute list table bulk actions.
			elseif ( $this->verify_nonce( 'bulk' ) ) {
				lib2()->array->equip_post( 'action', 'action2', 'member_id' );
				$action = $_POST['action'];
				if ( empty( $action ) || $action == '-1' ) {
					$action = $_POST['action2'];
				}
				$members = $_POST['member_id'];

				/*
				 * The Bulk-Edit action is built like 'cmd-id'
				 * e.g. 'add-123' will add membership 123 to the selected items.
				 */
				if ( empty( $action ) ) {
					$cmd = array();
				} elseif ( empty( $members ) ) {
					$cmd = array();
				} elseif ( '-1' == $action ) {
					$cmd = array();
				} else {
					$cmd = explode( '-', $action );
				}

				if ( 2 == count( $cmd ) ) {
					$action = $cmd[0];
					$action_id = $cmd[1];

					// Get a list of specified memberships...
					if ( is_numeric( $action_id ) ) {
						// ... either a single membership.
						$memberships = array(
							MS_Factory::load( 'MS_Model_Membership', $action_id ),
						);
					} elseif ( 'all' == $action_id ) {
						// ... or all memberships.
						$memberships = MS_Model_Membership::get_memberships();
					}

					// Loop defined memberships and add/remove members.
					foreach ( $memberships as $membership ) {
						$msg = $this->member_list_do_action(
							$action,
							$members,
							$membership->id
						);
					}

					$redirect = esc_url_raw(
						add_query_arg( array( 'msg' => $msg ) )
					);
				}
			}

			// Execute edit view page action submit.
			elseif ( isset( $_POST['submit'] )
				&& $this->verify_nonce()
				&& self::validate_required( $fields_edit, 'POST' )
			) {
				if ( is_array( $_POST['member_id'] ) ) {
					$member_ids = $_POST['member_id'];
				} else {
					$member_ids = explode( ',', $_POST['member_id'] );
				}

				$msg = $this->member_list_do_action(
					$_POST['action'],
					$member_ids,
					$_POST['membership_id']
				);

				$redirect = esc_url_raw(
					add_query_arg( array( 'msg' => $msg ) )
				);
			}
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Show member list.
	 *
	 * Menu "All Members", show all members available.
	 * Called by MS_Controller_Plugin::route_submenu_request()
	 *
	 * @since 1.0.0
	 */
	public function admin_member_list() {
		$data = array();

		$view = MS_Factory::create( 'MS_View_Member_List' );
		$view->data = apply_filters( 'ms_view_member_list_data', $data );
		$view->render();
	}

	/**
	 * Show member editor.
	 *
	 * Menu "Add Member", add or edit a single member.
	 * Called by MS_Controller_Plugin::route_submenu_request()
	 *
	 * @since 1.0.1.0
	 */
	public function admin_member_editor() {
		$data = array();

		if ( ! empty( $_REQUEST['user_id'] ) ) {
			$data['user_id'] = intval( $_REQUEST['user_id'] );
			$data['action'] = 'edit';
		} else {
			$data['user_id'] = 0;
			$data['action'] = 'add';
		}

		$view = MS_Factory::create( 'MS_View_Member_Editor' );
		$view->data = apply_filters( 'ms_view_member_editor_data', $data );
		$view->render();
	}

	/**
	 * Handle Ajax change-memberships action.
	 *
	 * This action handler is only called by admin users via the Members admin
	 * page, so all memberships added here have gateway_id 'admin'.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_change_memberships
	 *
	 * @since 1.1.0
	 */
	public function ajax_action_change_memberships() {
		$msg = 0;
		$this->_resp_reset();

		$required = array( 'member' );
		if ( $this->_resp_ok() && ! $this->is_admin_user() ) {
			$this->_resp_err( 'permission denied' );
		} elseif ( $this->_resp_ok() && ! $this->verify_nonce() ) {
			$this->_resp_err( 'subscribe: nonce' );
		} elseif ( $this->_resp_ok() && ! self::validate_required( $required ) ) {
			$this->_resp_err( 'subscribe: required' );
		}

		if ( $this->_resp_ok() ) {
			$values = array();
			if ( isset( $_POST['values'] ) && is_array( $_POST['values'] ) ) {
				$values = $_POST['values'];
			}

			$msg = $this->assign_memberships(
				$_POST['member'],
				$values
			);
		}
		$msg .= $this->_resp_code();

		echo $msg;
		exit;
	}

	/**
	 * Assigns (or removes) memberships to a Member.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $user_id
	 * @param  array $memberships Memberships that will be assigned to the
	 *                rule-item. Memberships that are not mentioned are removed.
	 * @return string [description]
	 */
	private function assign_memberships( $user_id, $memberships ) {
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );

		$memberships = apply_filters(
			'ms_controller_member_assign_memberships',
			$memberships,
			$member,
			$this
		);

		// Drop memberships that are not specified
		foreach ( $member->get_membership_ids() as $old_id ) {
			if ( in_array( $old_id, $memberships ) ) { continue; }
			$member->drop_membership( $old_id );
		}

		// Add new memberships
		foreach ( $memberships as $membership_id ) {
			$subscription = $member->add_membership( $membership_id );
		}

		if ( $member->has_membership() ) {
			$member->is_member = true;
		} else {
			$member->is_member = false;
		}
		$member->save();

		do_action(
			'ms_controller_member_assign_memberships_done',
			$member,
			$memberships,
			$this
		);

		return MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
	}

	/**
	 * Handles Member list actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The action to execute.
	 * @param object[] $members Array of members.
	 * @param int $membership_id The Membership to apply action to.
	 */
	public function member_list_do_action( $action, $members, $membership_id = null ) {
		$msg = MS_Helper_Member::MSG_MEMBER_NOT_UPDATED;
		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		foreach ( $members as $member_id ) {
			// Member Model
			$member = MS_Factory::load( 'MS_Model_Member', $member_id );
			switch ( $action ) {
				case 'add':
					$member->add_membership( $membership_id );
					$msg = MS_Helper_Member::MSG_MEMBER_ADDED;
					break;

				case 'drop':
					$member->drop_membership( $membership_id );
					$msg = MS_Helper_Member::MSG_MEMBER_DELETED;
					break;

				case 'move':
					if ( ! empty( $_POST['membership_move_from_id'] ) ) {
						$member->move_membership(
							$_POST['membership_move_from_id'],
							$_POST['membership_id']
						);
						$msg = MS_Helper_Member::MSG_MEMBER_UPDATED;
					}
					break;

				case 'toggle_activation':
					$member->active = ! $member->active;
					$msg = MS_Helper_Member::MSG_MEMBER_UPDATED;
					break;

				case 'edit_date':
					if ( is_array( $membership_id ) ) {
						foreach ( $membership_id as $id ) {
							$ms_relationship = $member->subscriptions[ $id ];
							if ( ! empty( $_POST[ 'start_date_' . $id ] ) ){
								$ms_relationship->start_date = $_POST[ 'start_date_' . $id ];
								$ms_relationship->set_trial_expire_date();
							}

							if ( ! empty( $_POST[ 'expire_date_' . $id ] ) ){
								$ms_relationship->expire_date = $_POST[ 'expire_date_' . $id ];
							}
							$ms_relationship->save();
						}
						$msg = MS_Helper_Member::MSG_MEMBER_UPDATED;
					}
					break;
			}
			$member->save();
		}

		return apply_filters(
			'ms_controller_member_member_list_do_action',
			$msg,
			$action,
			$members,
			$membership_id,
			$this
		);
	}

	/**
	 * Load Member manager specific styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		lib2()->ui->add( 'jquery-ui' );
	}

	/**
	 * Load Member manager specific scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array(),
		);
		lib2()->array->equip_get( 'action' );

		if ( 'edit_date' == $_GET['action'] ) {
			// Start and expire date edit
			wp_enqueue_script( 'jquery-ui-datepicker' );
			$data['ms_init'][] = 'view_member_date';
		} else {
			// Members list
			$data['ms_init'][] = 'view_member_list';
			$data['lang'] = array(
				'select_user' => __( 'Select an User', MS_TEXT_DOMAIN ),
			);
		}

		lib2()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

}