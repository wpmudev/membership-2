<?php
/**
 * Controller for managing Members and Membership relationships.
 *
 * Manages the Member and the member's Memberships.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Member extends MS_Controller {

	/**
	 * AJAX action constant: Edit subscriptions of a single member.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_CHANGE_MEMBERSHIPS = 'member_subscriptions';

	/**
	 * AJAX action constant: Validate a user field before creating the user.
	 *
	 * @since  1.0.1.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_VALIDATE_FIELD = 'member_validate_field';

	/**
	 * AJAX action constant: Search users via Ajax.
	 *
	 * @since  1.0.1.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_SEARCH = 'member_search';

	/**
	 * Used on the Add Member screen to indicate that a new WP User should be
	 * created and added to M2.
	 *
	 * @since 1.0.1.0
	 *
	 * @var   string
	 */
	const ACTION_ADD_MEMBER = 'member_add';

	/**
	 * Used on the Add Member screen to indicate that the submitted form details
	 * should update an existing user.
	 *
	 * @since 1.0.1.0
	 *
	 * @var   string
	 */
	const ACTION_UPDATE_MEMBER = 'member_update';

	/**
	 * Used on the Add Member screen to trigger a new subscription action for an
	 * existing user (user subscribes to one or multiple memberships)
	 *
	 * @since 1.0.1.0
	 *
	 * @var   string
	 */
	const ACTION_MODIFY_SUBSCRIPTIONS = 'member_subscription';

	/**
	 * Used on the Add Member screen to indicate that an existing WP User should
	 * be added to M2.
	 *
	 * @since 1.0.1.0
	 *
	 * @var   string
	 */
	const ACTION_SELECT_MEMBER = 'member_select';

	/**
	 * Prepare the Member manager.
	 *
	 * @since  1.0.0
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

		$this->add_ajax_action(
			self::AJAX_ACTION_VALIDATE_FIELD,
			'ajax_action_validate_field'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_SEARCH,
			'ajax_action_search'
		);
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		$hooks = array(
			'list' => MS_Controller_Plugin::admin_page_hook( 'members' ),
			'editor' => MS_Controller_Plugin::admin_page_hook( 'add-member' ),
		);

		foreach ( $hooks as $key => $hook ) {
			$this->run_action( 'load-' . $hook, 'members_admin_page_process_' . $key );
			$this->run_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts_' . $key );
			$this->run_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
		}
	}

	/**
	 * Add the current user to the Members-List.
	 *
	 * This does NOT assign any membership to the user, but ensures that the
	 * admin user appears in the Members-List
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 */
	public function members_admin_page_process_list() {
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
	 * Manages membership actions for the ADD/EDIT screen.
	 *
	 * @since  1.0.1.0
	 */
	public function members_admin_page_process_editor() {
		$msg = 0;
		$redirect = false;

		if ( $this->is_admin_user() ) {
			$fields_add = array( 'username', 'email' );
			$fields_select = array( 'user_id' );
			$fields_update = array( 'user_id', 'email' );
			$fields_modify = array( 'user_id', 'memberships' );
			$fields_subscribe = array( 'user_id', 'subscribe' );

			// Process Action: Create new user.
			if ( isset( $_POST['btn_create'] )
				&& $this->verify_nonce()
				&& self::validate_required( $fields_add, 'POST' )
			) {
				$data = array(
					'user_login' => $_POST['username'],
					'user_email' => $_POST['email'],
					'first_name' => $_POST['first_name'],
					'last_name' => $_POST['last_name'],
					'user_pass' => $_POST['password'],
				);
				$user_id = wp_insert_user( $data );

				if ( ! is_wp_error( $user_id ) ) {
					$redirect = esc_url_raw(
						add_query_arg( array( 'user_id' => $user_id ) )
					);
				}
			}

			// Process Action: Select existing user.
			elseif ( isset( $_POST['btn_select'] )
				&& $this->verify_nonce()
				&& self::validate_required( $fields_select, 'POST' )
			) {
				$user_id = intval( $_POST['user_id'] );

				$redirect = esc_url_raw(
					add_query_arg( array( 'user_id' => $user_id ) )
				);
			}

			// Process Action: Update existing user.
			elseif ( isset( $_POST['btn_save'] )
				&& $this->verify_nonce()
				&& self::validate_required( $fields_update, 'POST' )
			) {
				$data = array(
					'ID' => intval( $_POST['user_id'] ),
					'user_email' => $_POST['email'],
					'first_name' => $_POST['first_name'],
					'last_name' => $_POST['last_name'],
					'display_name' => $_POST['displayname'],
				);
				wp_update_user( $data );
			}

			// Process Action: Subscribe to a new membership.
			elseif ( isset( $_POST['btn_modify'] )
				&& $this->verify_nonce()
			) {
				$user_id = intval( $_POST['user_id'] );
				$user = MS_Factory::load( 'MS_Model_Member', $user_id );

				// Modify existing subscriptions.
				if ( self::validate_required( $fields_modify, 'POST' ) ) {
					$memberships = lib2()->array->get( $_POST['memberships'] );

					foreach ( $memberships as $membership_id ) {
						if ( empty( $_POST['mem_' . $membership_id] ) ) { continue; }

						$subscription = $user->get_subscription( $membership_id );
						$data = $_POST['mem_' . $membership_id];

						$subscription->start_date = $data['start'];
						$subscription->expire_date = $data['expire'];
						$subscription->status = $data['status'];
						$subscription->save();
					}
				}

				// Add new subscriptions.
				if ( self::validate_required( $fields_subscribe, 'POST' ) ) {
					$subscribe_to = $_POST['subscribe'];

					if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
						// Memberships is an array.
						foreach ( $subscribe_to as $membership_id ) {
							$user->add_membership( $membership_id, 'admin' );
						}
					} else {
						// Memberships is a single ID.
						foreach ( $user->subscriptions as $subscription ) {
							$subscription->deactivate_membership( false );
						}
						$user->add_membership( $subscribe_to, 'admin' );
					}
				}

				$user->save();
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
	 * @since  1.0.0
	 */
	public function admin_page() {
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
	public function admin_page_editor() {
		$data = array();

		if ( ! empty( $_REQUEST['user_id'] ) && intval( $_REQUEST['user_id'] ) ) {
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
	 * @since  1.0.0
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
	 * Handle Ajax validate field action.
	 *
	 * This function should validate the field value before the user is created
	 * to make sure that the value is unique/valid.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_validate_field
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_action_validate_field() {
		$msg = 0;
		$this->_resp_reset();

		$required = array( 'field', 'value' );
		if ( $this->_resp_ok() && ! $this->is_admin_user() ) {
			$this->_resp_err( 'permission denied' );
		} elseif ( $this->_resp_ok() && ! self::validate_required( $required ) ) {
			$this->_resp_err( 'validate: required' );
		}

		if ( $this->_resp_ok() ) {
			$field = $_POST['field'];
			$value = $_POST['value'];

			if ( 'email' == $field ) {
				if ( ! is_email( $value ) ) {
					$msg = __( 'Invalid Email address', MS_TEXT_DOMAIN );
				} elseif ( email_exists( $value ) ) {
					$msg = __( 'Email already taken', MS_TEXT_DOMAIN );
				} else {
					$msg = 1;
				}
			} elseif ( 'username' == $field ) {
				if ( username_exists( $value ) ) {
					$msg = __( 'Username already taken', MS_TEXT_DOMAIN );
				} else {
					$msg = 1;
				}
			}
		}
		$msg .= $this->_resp_code();

		echo $msg;
		exit;
	}

	/**
	 * Handle Ajax search users action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_search
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_action_search() {
		$res = (object) array(
			'items' => array(),
			'more' => false,
		);
		$this->_resp_reset();
		$items_per_page = 20;

		$required = array( 'q', 'p' );
		if ( $this->_resp_ok() && ! $this->is_admin_user() ) {
			$this->_resp_err( 'permission denied' );
		} elseif ( $this->_resp_ok() && ! self::validate_required( $required, 'any' ) ) {
			$this->_resp_err( 'search: required' );
		}

		if ( $this->_resp_ok() ) {
			$term = $_REQUEST['q'];
			$page = max( intval( $_REQUEST['p'] ) - 1, 0 );
			$offset = $page * $items_per_page;

			$args = array(
				'search' => '*' . $term . '*',
				'offset' => $offset,
				'number' => $items_per_page + 1,
				'fields' => array(
					'ID',
					'user_login',
					'display_name',
				),
				'orderby' => 'display_name',
			);
			$users = get_users( $args );

			if ( count( $users ) > $items_per_page ) {
				$res->more = true;
				array_pop( $users );
			}

			foreach ( $users as $user ) {
				$res->items[] = array(
					'id' => $user->ID,
					'text' => sprintf(
						'%s (%s)',
						$user->display_name,
						$user->user_login
					),
				);
			}
		}

		echo json_encode( $res );
		exit;
	}

	/**
	 * Assigns (or removes) memberships to a Member.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
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

				case 'edit_date':
					if ( is_array( $membership_id ) ) {
						foreach ( $membership_id as $id ) {
							$subscription = $member->get_subscriptions( $id );
							if ( ! empty( $_POST[ 'start_date_' . $id ] ) ){
								$subscription->start_date = $_POST[ 'start_date_' . $id ];
								$subscription->set_trial_expire_date();
							}

							if ( ! empty( $_POST[ 'expire_date_' . $id ] ) ){
								$subscription->expire_date = $_POST[ 'expire_date_' . $id ];
							}
							$subscription->save();
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
	 * @since  1.0.0
	 */
	public function enqueue_styles() {
		lib2()->ui->add( 'jquery-ui' );
	}

	/**
	 * Load Member specific scripts for the LIST view.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts_list() {
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

	/**
	 * Load Member specific scripts for ADD/EDIT screen.
	 *
	 * @since  1.0.1.0
	 */
	public function enqueue_scripts_editor() {
		$data = array(
			'ms_init' => array(),
		);

		$data['ms_init'][] = 'view_member_editor';

		lib2()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

}