<?php

/**
 * Controller for managing Members and Membership relationships.
 *
 * Manages the Member and the member's Memberships.
 *
 * @since      1.0.0
 *
 * @package    Membership2
 * @subpackage Controller
 */
class MS_Controller_Member extends MS_Controller {

	/**
	 * AJAX action constant: Edit subscriptions of a single member.
	 *
	 * @var string
	 * @since  1.0.0
	 *
	 */
	const AJAX_ACTION_CHANGE_MEMBERSHIPS = 'member_subscriptions';

	/**
	 * AJAX action constant: Validate a user field before creating the user.
	 *
	 * @var string
	 * @since  1.0.1.0
	 *
	 */
	const AJAX_ACTION_VALIDATE_FIELD = 'member_validate_field';

	/**
	 * AJAX action constant: Search users via Ajax.
	 *
	 * @var string
	 * @since  1.0.1.0
	 *
	 */
	const AJAX_ACTION_SEARCH = 'member_search';

	/**
	 * Used on the Add Member screen to indicate that a new WP User should be
	 * created and added to M2.
	 *
	 * @var   string
	 * @since 1.0.1.0
	 *
	 */
	const ACTION_ADD_MEMBER = 'member_add';

	/**
	 * Used on the Add Member screen to indicate that the submitted form details
	 * should update an existing user.
	 *
	 * @var   string
	 * @since 1.0.1.0
	 *
	 */
	const ACTION_UPDATE_MEMBER = 'member_update';

	/**
	 * Used on the Add Member screen to trigger a new subscription action for an
	 * existing user (user subscribes to one or multiple memberships)
	 *
	 * @var   string
	 * @since 1.0.1.0
	 *
	 */
	const ACTION_MODIFY_SUBSCRIPTIONS = 'member_subscription';

	/**
	 * Used on the Add Member screen to indicate that an existing WP User should
	 * be added to M2.
	 *
	 * @var   string
	 * @since 1.0.1.0
	 *
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

		$this->add_action(
			'delete_user',
			'remove_membership_from_user'
		);

		$this->add_action(
			'delete_user',
			'cleanup_member_events'
		);

		$this->add_action(
			'ms_bulk_actions_table_nav_members',
			'members_export_button'
		);


		$this->add_action(
			'admin_action_membership_export_csv',
			'membership_export_csv'
		);

		$this->add_filter( 'set-screen-option', array( $this, 'members_admin_page_set_screen_option' ), 10, 3 );

		//User columns
		$this->add_filter( 'manage_users_columns', array( $this, 'manage_users_columns' ), 10, 1 );
		$this->add_filter( 'manage_users_sortable_columns', array( $this, 'manage_users_columns' ), 10, 1 );
		$this->add_filter( 'manage_users_custom_column', array( $this, 'manage_users_custom_column' ), 10, 3 );


		$settings = MS_Factory::load( 'MS_Model_Settings' );

		if ( $settings->force_registration_verification ) {
			$this->add_filter( 'bulk_actions-users', array( $this, 'add_verify_bulk_action' ), 10, 1 );
			$this->add_filter( 'handle_bulk_actions-users', array( $this, 'handle_verify_bulk_action' ), 10, 3 );
			$this->add_action( 'admin_notices', array( $this, 'handle_verify_bulk_message' ) );
		}

		//Profile update hooks
		add_action( 'profile_update', array( $this, 'handle_profile_membership' ), 10, 2 );

		// When subscription status is changed by admin.
		$this->add_action( 'ms_controller_members_admin_status_changed', 'subscription_status_change', 10, 4 );
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		$hooks = array(
			'list'   => MS_Controller_Plugin::admin_page_hook( 'members' ),
			'editor' => MS_Controller_Plugin::admin_page_hook( 'add-member' ),
		);

		$this->add_action( 'load-' . $hooks['list'], 'members_admin_page_screen_option' );

		foreach ( $hooks as $key => $hook ) {
			$this->run_action( 'load-' . $hook, 'members_admin_page_process_' . $key );
			$this->run_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts_' . $key );
			$this->run_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
		}
	}


	/**
	 * Export members as CSV
	 * Exports data of the current page on the members list
	 *
	 * @return csv file
	 */
	public function membership_export_csv() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}


		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'csv_export' ) ) {
			return;
		}

		$handler = MS_Factory::create( 'MS_Model_Report_Members' );
		$handler->process();
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
	 * Remove membership for an user
	 *
	 * @since 1.0.3
	 */
	public function remove_membership_from_user( $user_id ) {
		$member          = MS_Factory::load( 'MS_Model_Member', $user_id );
		$memberships_ids = ( array ) $member->get_membership_ids();

		if ( ! empty( $memberships_ids ) ) {
			foreach ( $memberships_ids as $memberships_id ) {
				$member->drop_membership( $memberships_id );
			}
		}
	}

	/**
	 * Delete all events for the user.
	 *
	 * We need to clear all the events from the
	 * wp database, otherwise it may show up for
	 * the future users with same user id.
	 *
	 * @param int $user_id User ID.
	 *
	 * @since 1.1.6
	 */
	public function cleanup_member_events( $user_id ) {
		// Delete events.
		MS_Model_Event::delete_events( array(
			'author'         => $user_id,
			'posts_per_page' => -1,
		) );
	}

	/**
	 * Add pagination members screen option
	 *
	 * @since 1.0.3
	 */
	function members_admin_page_screen_option() {
		$option = 'per_page';
		$args   = array(
			'label'   => 'Members',
			'default' => 20,
			'option'  => 'members_per_page',
		);

		add_screen_option( $option, $args );
	}

	/**
	 * Set pagination members screen option
	 *
	 * @since 1.0.3
	 */
	public static function members_admin_page_set_screen_option( $status, $option, $value ) {
		return $value;
	}


	/**
	 * Manages membership actions.
	 *
	 * Verifies GET and POST requests to manage members
	 *
	 * @since  1.0.0
	 */
	public function members_admin_page_process_list() {
		$msg      = 0;
		$redirect = false;

		if ( $this->is_admin_user() ) {
			$fields_new  = array( 'new_member', 'action' );
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
			} elseif ( $this->verify_nonce( 'bulk' ) ) { // Execute list table bulk actions.
				mslib3()->array->equip_post( 'action', 'action2', 'member_id' );
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
					$action    = $cmd[0];
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
			} elseif ( isset( $_POST['submit'] )
			           && $this->verify_nonce()
			           && self::validate_required( $fields_edit, 'POST' )
			) { // Execute edit view page action submit.
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
		$msg      = 0;
		$redirect = false;

		if ( $this->is_admin_user() ) {
			$fields_add       = array( 'username', 'email' );
			$fields_select    = array( 'user_id' );
			$fields_update    = array( 'user_id', 'email' );
			$fields_modify    = array( 'user_id', 'memberships' );
			$fields_subscribe = array( 'user_id', 'subscribe' );

			// Process Action: Create new user.
			if ( isset( $_POST['btn_create'] )
			     && $this->verify_nonce()
			     && self::validate_required( $fields_add, 'POST' )
			) {
				$data    = array(
					'user_login' => sanitize_text_field( $_POST['username'] ),
					'user_email' => sanitize_email( $_POST['email'] ),
					'first_name' => sanitize_text_field( $_POST['first_name'] ),
					'last_name'  => sanitize_text_field( $_POST['last_name'] ),
					'user_pass'  => $_POST['password'],
				);
				$user_id = wp_insert_user( $data );

				if ( ! is_wp_error( $user_id ) ) {
					$redirect = esc_url_raw(
						add_query_arg( array( 'user_id' => $user_id ) )
					);
				}
			} elseif ( isset( $_POST['btn_select'] )
			           && $this->verify_nonce()
			           && self::validate_required( $fields_select, 'POST' )
			) { // Process Action: Select existing user.
				$user_id = intval( $_POST['user_id'] );

				$redirect = esc_url_raw(
					add_query_arg( array( 'user_id' => $user_id ) )
				);
			} elseif ( isset( $_POST['btn_save'] )
			           && $this->verify_nonce()
			           && self::validate_required( $fields_update, 'POST' )
			) { // Process Action: Update existing user.
				$data    = array(
					'ID'           => intval( $_POST['user_id'] ),
					'user_email'   => sanitize_email( $_POST['email'] ),
					'first_name'   => sanitize_text_field( $_POST['first_name'] ),
					'last_name'    => sanitize_text_field( $_POST['last_name'] ),
					'display_name' => sanitize_text_field( $_POST['displayname'] ),
				);
				$user_id = wp_update_user( $data );

				if ( ! is_wp_error( $user_id ) ) {
					$redirect = esc_url_raw(
						add_query_arg( array( 'user_id' => $user_id ) )
					);
				}
			} elseif ( isset( $_POST['btn_modify'] )
			           && $this->verify_nonce()
			) { // Process Action: Subscribe to a new membership.
				// REQUEST here: When editing a user the ID is sent in the URL.
				$user_id = intval( $_REQUEST['user_id'] );
				$user    = MS_Factory::load( 'MS_Model_Member', $user_id );
				// We don't need need user_id here as this is an user modification
				$fields_modify = array( 'memberships' );

				// Modify existing subscriptions.
				if ( self::validate_required( $fields_modify, 'POST' ) ) {
					$memberships = mslib3()->array->get( $_POST['memberships'] );

					foreach ( $memberships as $membership_id ) {
						if ( empty( $_POST['mem_' . $membership_id] ) ) {
							continue;
						}

						$subscription = $user->get_subscription( $membership_id );
						$data         = $_POST['mem_' . $membership_id];

						$invoice = $subscription->get_current_invoice( false );
						if ( $invoice ) {
							if ( $data['status'] === MS_Model_Relationship::STATUS_ACTIVE ) {
								$invoice->status = MS_Model_Invoice::STATUS_PAID;
								$invoice->save();
							} else {
								if ( $data['status'] === MS_Model_Relationship::STATUS_CANCELED ) {
									if ( $invoice->status !== MS_Model_Invoice::STATUS_PAID ) {
										$invoice->status = MS_Model_Invoice::STATUS_PENDING;
										$invoice->save();
									}
								}
							}
						}

						if ( $data['status'] !== $subscription->status ) {
							/**
							 * When subscription status is changed.
							 *
							 * This action hook runs when subscription status is changed
							 * manually by admin.
							 *
							 * @param string $data         ['status'] New status.
							 * @param object $subscription MS_Model_Relationship
							 * @param array  $data         Form data of current membership.
							 *
							 * @since 1.1.6
							 *
							 * @param  object $user MS_Model_Member
							 * @since  1.1.7
							 */
							do_action( 'ms_controller_members_admin_status_changed',
								$data['status'],
								$subscription,
								$data,
								$user
							);
						}

						$subscription->start_date  = $data['start'];
						$subscription->expire_date = $data['expire'];
						$subscription->status      = $data['status'];
						$subscription->save();
					}
				}

				// Add new subscriptions.
				if ( self::validate_required( $fields_subscribe, 'POST' ) ) {
					$subscribe_to = $_POST['subscribe'];

					if ( ! empty( $subscribe_to ) ) {
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

							if ( isset ( $_POST['create_invoice'] ) && $_POST['create_invoice'] ) {
								//Get the payment mode for the membership
								$subscription = $user->get_subscription( $subscribe_to );
								if ( $subscription && $subscription->id > 0 ) {
									$subscription->set_recalculate_expire_date( false ); //Dont adjust the subscription expire date
									$invoice               = $subscription->get_current_invoice();
									$invoice->payment_info .= sprintf(
										'<div class="ms-manual-price">%s: <span class="ms-price">%s%s</span></div>',
										__( 'Total value', 'membership2' ),
										$invoice->currency,
										$invoice->total
									);
									$invoice->status       = MS_Model_Invoice::STATUS_BILLED;
									$invoice->save();
								}
							}
						}
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
		$data       = array();
		$view       = MS_Factory::create( 'MS_View_Member_List' );
		$view->data = apply_filters( 'ms_view_member_list_data', $data, $this );
		$view->render();
	}

	/**
	 * Handle the manual status change process.
	 *
	 * When status of a subscription is changed by
	 * admin user, process the related actions.
	 *
	 * @param string $new_status   New status..
	 * @param object $subscription MS_Model_Relationship.
	 * 
	 *
	 * @since 1.1.6
	 *
	 * @param  array $data $_POST params
	 * @param  object $member MS_Model_Member
	 *
	 * @since  1.1.7
	 */
	public function subscription_status_change( $new_status, $subscription, $data, $member ) {
		// Switch status.
		switch ( $new_status ) {
			// Cancel the subscription.
			case MS_Model_Relationship::STATUS_CANCELED:
				$subscription->cancel_membership();
				break;
			case MS_Model_Relationship::STATUS_ACTIVE:
				/**
				 * Make sure the status is changed to active if it is manually
                 * edited from admin.
				 *
				 * @since 1.1.7
				 */
				if ( MS_Model_Relationship::STATUS_PENDING === $subscription->status ) {
					$member->add_membership( $subscription->membership_id );
				}
				break;

			// Handle other statuses here.
		}
	}

	/**
	 * Generate the Export button on the Members list view
	 *
	 * @since 1.1.3
	 *
	 * @return String
	 */
	public function members_export_button() {
		$status = $_REQUEST['status'];
		if ( empty( $status ) ) {
			$status = MS_Model_Relationship::STATUS_ACTIVE;
		}
		$url = 'admin.php?action=membership_export_csv&status=' . $status;
		if ( isset( $_REQUEST['membership_id'] ) ) {
			$url .= '&membership_id=' . $_REQUEST['membership_id'];
		}
		$url        = wp_nonce_url( admin_url( $url ), 'csv_export' );
		$csv_button = array(
			'id'    => 'csv_ms_button',
			'type'  => MS_Helper_Html::TYPE_HTML_LINK,
			'url'   => $url,
			'value' => __( 'Export List as CSV', 'membership2' ),
			'class' => 'button button-primary action-button export_csv_memberships_button',
		);
		MS_Helper_Html::html_element( $csv_button );
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

		if ( ! empty( $_REQUEST['user_id'] ) && ( $user_id = intval( $_REQUEST['user_id'] ) ) ) {
			if ( user_can( $user_id, 'administrator' ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this page.', 'membership2' ), 403 );
			}
			$data['user_id'] = $user_id;
			$data['action']  = 'edit';
		} else {
			$data['user_id'] = 0;
			$data['action']  = 'add';
		}

		$view       = MS_Factory::create( 'MS_View_Member_Editor' );
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
			$this->_resp_err( __( 'This field is required.', 'membership2' ) );
		}

		if ( $this->_resp_ok() ) {
			$field = $_POST['field'];
			$value = $_POST['value'];

			if ( 'email' == $field ) {
				if ( ! is_email( $value ) ) {
					$msg = __( 'Invalid Email address', 'membership2' );
				} elseif ( email_exists( $value ) ) {
					$msg = __( 'Email already taken', 'membership2' );
				} else {
					$msg = 1;
				}
			} elseif ( 'username' == $field ) {
				if ( username_exists( $value ) ) {
					$msg = __( 'Username already taken', 'membership2' );
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
			'more'  => false,
		);
		$this->_resp_reset();
		$items_per_page = 20;

		$required = array( 'q' );
		if ( $this->_resp_ok() && ! $this->is_admin_user() ) {
			$this->_resp_err( 'permission denied' );
		} elseif ( $this->_resp_ok() && ! self::validate_required( $required, 'any' ) ) {
			$this->_resp_err( 'search: required' );
		}
		if ( empty( $_REQUEST['p'] ) ) {
			$_REQUEST['p'] = 0;
		}

		if ( $this->_resp_ok() ) {
			$term   = $_REQUEST['q'];
			$page   = max( intval( $_REQUEST['p'] ) - 1, 0 );
			$offset = $page * $items_per_page;

			$args   = array(
				'search'  => '*' . $term . '*',
				'offset'  => $offset,
				'number'  => $items_per_page + 1,
				'fields'  => array(
					'ID',
					'user_login',
					'display_name',
				),
				'orderby' => 'display_name',
			);
			$users  = get_users( $args );
			$admins = get_users( array( 'role' => 'administrator' ) );
			$users  = array_udiff( $users, $admins, array( $this, 'compare_objects' ) );

			if ( count( $users ) > $items_per_page ) {
				$res->more = true;
				array_pop( $users );
			}

			foreach ( $users as $user ) {
				$res->items[] = array(
					'id'   => $user->ID,
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

	public function compare_objects( $obj_a, $obj_b ) {
		return $obj_a->ID - $obj_b->ID;
	}

	/**
	 * Assigns (or removes) memberships to a Member.
	 *
	 * @param string $user_id
	 * @param array  $memberships Memberships that will be assigned to the
	 *                            rule-item. Memberships that are not mentioned are removed.
	 *
	 * @since  1.0.0
	 *
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
			if ( in_array( $old_id, $memberships ) ) {
				continue;
			}
			$member->drop_membership( $old_id );
		}

		// Add new memberships.
		foreach ( $memberships as $membership_id ) {
			$member->add_membership( $membership_id );
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
	 * @param string   $action        The action to execute.
	 * @param object[] $members       Array of members.
	 * @param int      $membership_id The Membership to apply action to.
	 *
	 * @since  1.0.0
	 *
	 * @return string
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
							if ( ! empty( $_POST['start_date_' . $id] ) ) {
								$subscription->start_date = $_POST['start_date_' . $id];
								$subscription->set_trial_expire_date();
							}

							if ( ! empty( $_POST['expire_date_' . $id] ) ) {
								$subscription->expire_date = $_POST['expire_date_' . $id];
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
		mslib3()->ui->add( 'jquery-ui' );
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
		mslib3()->array->equip_get( 'action' );

		if ( 'edit_date' == $_GET['action'] ) {
			// Start and expire date edit
			wp_enqueue_script( 'jquery-ui-datepicker' );
			$data['ms_init'][] = 'view_member_date';
		} else {
			// Members list
			$data['ms_init'][] = 'view_member_list';
			$data['lang']      = array(
				'select_user' => __( 'Select an User', 'membership2' ),
			);
		}

		mslib3()->ui->data( 'ms_data', $data );
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

		mslib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

	/**
	 * Add Membership column after the Role column
	 *
	 * @param Array $columns - current columns
	 *
	 * @return Array
	 */
	public function manage_users_columns( $columns ) {
		$new_columns = array();
		$columns_4   = array_slice( $columns, 0, 5 );
		$columns_5   = array_slice( $columns, 5 );
		$settings    = MS_Factory::load( 'MS_Model_Settings' );

		$membership_column = array( 'membership' => __( 'Membership', 'membership2' ) );
		if ( $settings->force_registration_verification ) {
			$membership_column = array( 'membership' => __( 'Membership', 'membership2' ), 'verified' => __( 'Verified', 'membership2' ) );
		}

		$new_columns = $columns_4 + $membership_column + $columns_5;

		return apply_filters( 'ms_controller_member_manage_users_columns', $new_columns, $columns );
	}

	/**
	 * Add Membership column to users list
	 *
	 * @param string $output      Custom column output. Default empty.
	 * @param string $column_name Column name.
	 * @param int    $user_id     ID of the currently-listed user.
	 *
	 * @return String
	 */
	public function manage_users_custom_column( $value, $column_name, $user_id ) {
		if ( 'membership' == $column_name ) {
			// Admin user has access to everything.
			if ( MS_Model_Member::is_admin_user( $user_id ) ) {
				$value = '<span style="font-weight:bold;">' . __( 'None (Admin User)', 'membership2' ) . '</span>';
			} else {
				$member       = MS_Factory::load( 'MS_Model_Member', $user_id );
				$subscription = $member->get_subscription( 'priority' );
				if ( $subscription ) {
					$membership = $subscription->get_membership();
					$color      = MS_Helper_Utility::color_index( $membership->type . $membership->id );

					$html = '<span class="ms-color" style="
                        background-color:' . $color . ';
                        width: 20px;
                        float: left;
                        margin-right: 5px;
                        border-radius: 45px;
                        box-shadow: 0 -20px 10px -10px rgba(0, 0, 0, 0.2) inset;
                        ">&nbsp;
					</span>';

					$url = MS_Controller_Plugin::get_admin_url(
						'members',
						array( 'membership_id' => $membership->id )
					);

					$view_url = sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						$url,
						__( 'View Members', 'membership2' ),
						$membership->name
					);

					$html  .= '<span style="font-weight:bold;">' . $view_url . '</span>';
					$value = $html;
				}

				if ( empty( $value ) ) {
					$value = __( 'None', 'membership2' );
				}
			}

		} else {
			if ( 'verified' == $column_name ) {

				$user_activation_status       = get_user_meta( $user_id, '_ms_user_activation_status', true );
				$force_user_activation_status = get_user_meta( $user_id, '_ms_user_force_activation_status', true );
				$user_activation_status       = empty( $user_activation_status ) ? 0 : $user_activation_status;
				if ( $user_activation_status != 1 && MS_Model_Member::is_admin_user( $user_id ) ) {
					$user_activation_status = 1;
					update_user_meta( $user_id, '_ms_user_activation_status', $user_activation_status );
				} else {
					if ( ! $force_user_activation_status ) {
						//Set already active users to active
						$udata                    = get_userdata( $user_id );
						$verification_cutoff_date = '2018-04-11 23:59:59';
						if ( $udata->user_registered < $verification_cutoff_date ) {
							$user_activation_status = 1;
							update_user_meta( $user_id, '_ms_user_activation_status', $user_activation_status );
						}
					}
				}

				if ( $user_activation_status != 1 ) {
					if ( $force_user_activation_status ) {
						$value = __( 'Verification Resent', 'membership2' );
					} else {
						$value = __( 'Not Verified', 'membership2' );
					}

				} else {
					$value = __( 'Verified', 'membership2' );
				}
			}
		}
		return apply_filters( 'ms_controller_member_manage_users_custom_column', $value, $column_name, $user_id );
	}

	/**
	 * Add bulk action to verify users
	 *
	 * @param array $actions - the action
	 *
	 * @since  1.1.3
	 *
	 * @return $actions
	 */
	function add_verify_bulk_action( $actions ) {

		$actions['ms_bulk_approve']    = __( 'Approve', 'membership2' );
		$actions['ms_bulk_disapprove'] = __( 'Disapprove', 'membership2' );
		$actions['ms_bulk_resend']     = __( 'Resend Verification Email', 'membership2' );

		return $actions;
	}

	/**
	 * Hande the verify bulk action
	 *
	 * @param string $redirect_to - the url to redirect to
	 * @param string $doaction    - The action being taken
	 * @param array  $items       - The items to take the action on
	 *
	 * @since 1.1.3
	 *
	 * @return string $redirect_to
	 */
	function handle_verify_bulk_action( $redirect_to, $doaction, $items ) {

		switch ( $doaction ) {
			case 'ms_bulk_approve' :
				foreach ( $items as $user_id ) {
					if ( ! MS_Model_Member::is_admin_user( $user_id ) ) {
						update_user_meta( $user_id, '_ms_user_activation_status', 1 );
					}
				}
				$redirect_to = admin_url( 'users.php' );
				$redirect_to = add_query_arg( '_ms_approved', count( $items ), $redirect_to );
				break;

			case 'ms_bulk_disapprove' :
				foreach ( $items as $user_id ) {
					if ( ! MS_Model_Member::is_admin_user( $user_id ) ) {
						update_user_meta( $user_id, '_ms_user_activation_status', 0 );
					}
				}
				$redirect_to = admin_url( 'users.php' );
				$redirect_to = add_query_arg( '_ms_disapproved', count( $items ), $redirect_to );
				break;

			case 'ms_bulk_resend' :
				foreach ( $items as $user_id ) {
					if ( ! MS_Model_Member::is_admin_user( $user_id ) ) {
						$member = MS_Factory::load( 'MS_Model_Member', $user_id );;
						update_user_meta( $user_id, '_ms_user_activation_status', 0 );
						update_user_meta( $user_id, '_ms_user_force_activation_status', 1 );
						MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_VERIFYACCOUNT, $member );
					}
				}
				$redirect_to = admin_url( 'users.php' );
				$redirect_to = add_query_arg( '_ms_resend', count( $items ), $redirect_to );
				break;
		}

		return $redirect_to;
	}

	/**
	 * Handle bulk message for approval status change
	 *
	 * @since 1.1.3
	 */
	function handle_verify_bulk_message() {
		if ( isset ( $_REQUEST['_ms_approved'] ) ) {
			$user_count = intval( $_REQUEST['_ms_approved'] );
			?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf( __( '%d user accounts approved', 'membership2' ), $user_count ); ?></p>
            </div>
			<?php
		} else {
			if ( isset ( $_REQUEST['_ms_disapproved'] ) ) {
				$user_count = intval( $_REQUEST['_ms_disapproved'] );
				?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo sprintf( __( '%d user accounts disapproved', 'membership2' ), $user_count ); ?></p>
                </div>
				<?php
			} else {
				if ( isset ( $_REQUEST['_ms_resend'] ) ) {
					$user_count = intval( $_REQUEST['_ms_resend'] );
					?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php echo sprintf( __( '%d user accounts resent emails', 'membership2' ), $user_count ); ?></p>
                    </div>
					<?php
				}
			}
		}
	}

	/**
	 * Check updated profile.
	 *
	 * If a normal user with a membership is update to admin, we need to clear the subscription.
	 * Also, sync the updated field values for m2 meta.
	 *
	 * @param int   $user_id       The user id.
	 * @param array $old_user_data The old user data.
	 *
	 * @since 1.1.3
	 *
	 */
	function handle_profile_membership( $user_id, $old_user_data ) {
		// Remove memberships from admin users.
		$this->remove_membership_from_admin( $user_id );

		// Update the meta if required.
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		$member->sync_meta();
	}

	/**
	 * Check updated profile
	 * If a normal user with a membership is update to admin, we need to clear the subscription
	 * Admin edit screen
	 *
	 * @param int $user_id - the user id
	 *
	 * @since 1.1.3
	 *
	 */
	function remove_membership_from_admin( $user_id ) {
		if ( MS_Model_Member::is_admin_user( $user_id ) ) {
			$member = MS_Factory::load( 'MS_Model_Member', $user_id );
			if ( $member ) {
				foreach ( $member->subscriptions as $subscription ) {
					$subscription->delete();
				}
			}
		}
	}
}