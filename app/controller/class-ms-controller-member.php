<?php
/**
 * This file defines the MS_Controller_Member class.
 *
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Controller for managing Members and Membership relationships.
 *
 * Manages the Member and the member's Memberships.
 *
 * @since 1.0.0
 *
 * @package Membership
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
	const AJAX_ACTION_TOGGLE_MEMBER = 'toggle_member';
	const AJAX_ACTION_GET_USERS = 'get_users';

	/**
	 * Prepare the Member manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$hook = 'protect-content_page_protected-content-members';

		$this->add_action( 'load-' . $hook, 'members_admin_page_process' );

		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_MEMBER, 'ajax_action_toggle_member' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_GET_USERS, 'ajax_action_get_users' );

		$this->add_action( 'ms_controller_membership_setup_completed', 'add_current_user' );

		$this->add_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related action hooks:
	 * - wp_ajax_toggle_member
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_toggle_member() {
		$msg = 0;
		if ( $this->verify_nonce()
			&& ! empty( $_POST['member_id'] )
			&& $this->is_admin_user()
		) {
			$msg = $this->member_list_do_action(
				'toggle_activation',
				array( $_POST['member_id'] )
			);
		}

		wp_die( $msg );
	}

	/**
	 * Handle Ajax request to list all non-member-users.
	 *
	 * Response is wrapped in a JSONP callback.
	 * Data is an array of objects with properties 'id' and 'text'.
	 *
	 * @since  1.0.0
	 */
	public function ajax_action_get_users() {
		WDev()->load_request_fields( 'callback', 'filter' );

		$callback_name = sanitize_html_class( $_REQUEST['callback'] );
		$filter = $_REQUEST['filter'];

		$args = array(
			'number' => false,
			'orderby' => 'user_name',
			'search' => '*' . $filter . '*',
			'search_columns' => array( 'user_login' ),
		);

		$data = MS_Model_Member::get_usernames(
			$args,
			MS_Model_Member::SEARCH_NOT_MEMBERS,
			false
		);

		printf(
			'%s(%s)',
			$callback_name,
			json_encode( $data )
		);
		exit;
	}

	/**
	 * Show admin notices.
	 *
	 * @since 1.0.0
	 *
	 */
	public function print_admin_message() {
		add_action(
			'admin_notices',
			array( 'MS_Helper_Member', 'print_admin_message' )
		);
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
		$this->print_admin_message();

		$msg = 0;
		$redirect = false;

		if ( $this->is_admin_user() ) {
			$fields_new = array( 'new_member', 'action' );
			$fields_edit = array( 'member_id', 'action' );

			if ( $this->verify_nonce( 'add_member' )
				&& self::validate_required( $fields_new )
			) {
				$ids = explode( ',', $_POST['new_member'] );
				foreach ( $ids as $id ) {
					$member = MS_Factory::load(
						'MS_Model_Member',
						$id
					);

					$member->is_member = true;
					$member->save();
				}
				$msg = MS_Helper_Member::MSG_MEMBER_USER_ADDED;

				$redirect = add_query_arg( array( 'msg' => $msg ) );
			}

			// Execute list table single action.
			else if ( $this->verify_nonce( null, 'GET' )
				&& self::validate_required( $fields_edit, 'GET' )
			) {
				$msg = $this->member_list_do_action(
					$_GET['action'],
					array( $_GET['member_id'] )
				);

				$redirect = remove_query_arg(
					array( 'member_id', 'action', '_wpnonce' )
				);

				$redirect = add_query_arg( array( 'msg' => $msg ), $redirect );
			}

			// Execute list table bulk actions.
			elseif ( $this->verify_nonce( 'bulk-members' )
				&& self::validate_required( $fields_edit, 'POST' )
			) {
				$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
				if ( $action == 'toggle_activation' ) {
					$msg = $this->member_list_do_action( $action, $_POST['member_id'] );

					$redirect = add_query_arg( array( 'msg' => $msg ) );
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

				$redirect = add_query_arg( array( 'msg' => $msg ) );
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
	 * Menu Members, show all members available.
	 *
	 * @since 1.0.0
	 */
	public function admin_member_list() {
		// Action view edit page request
		$fields = array( 'member_id', 'action' );
		if ( self::validate_required( $fields, 'REQUEST' ) ) {
			$this->prepare_action_view( $_REQUEST['action'], $_REQUEST['member_id'] );
		} else {
			$data = array();
			$data['usernames'] = MS_Model_Member::get_usernames(
				null,
				MS_Model_Member::SEARCH_NOT_MEMBERS
			);
			$data['action'] = 'add_member';

			$view = MS_Factory::create( 'MS_View_Member_List' );
			$view->data = apply_filters( 'ms_view_member_list_data', $data );
			$view->render();
		}
	}

	/**
	 * Prepare and show action view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The action to execute.
	 * @param int $member_id User ID of the member.
	 */
	public function prepare_action_view( $action, $member_id ) {
		$view = null;
		$data = array();

		// Bulk actions
		if ( is_array( $member_id ) ) {
			$memberships = MS_Model_Membership::get_membership_names();
			$data['member_id'] = $member_id;

			switch ( $action ) {
				case 'add':
					$memberships[0] = __( 'Select Membership to add', MS_TEXT_DOMAIN );
					break;

				case 'cancel':
					$memberships[0] = __( 'Select Membership to cancel', MS_TEXT_DOMAIN );
					break;

				case 'drop':
					$memberships[0] = __( 'Select Membership to drop', MS_TEXT_DOMAIN );
					break;

				case 'move':
					$memberships_move = $memberships;
					$memberships_move[0] = __( 'Select Membership to move from', MS_TEXT_DOMAIN );

					$memberships = MS_Model_Membership::get_membership_names();
					$memberships[0] = __( 'Select Membership to move to', MS_TEXT_DOMAIN );
					break;
			}
		}

		// Single action
		else {
			// Member Model
			$member = apply_filters(
				'membership_member_model',
				MS_Factory::load( 'MS_Model_Member', $member_id )
			);
			$data['member_id'] = array( $member_id );

			switch ( $action ) {
				case 'add':
					$memberships = MS_Model_Membership::get_signup_membership_list(
						null,
						array_keys( $member->ms_relationships ),
						true,
						true
					);
					$memberships = array_unshift_assoc(
						$memberships,
						0,
						__( '- Select Membership to add -', MS_TEXT_DOMAIN )
					);
					break;

				case 'cancel':
					$args = array(
						'post__in' => array_keys( $member->ms_relationships ),
					);
					$memberships = MS_Model_Membership::get_membership_names( $args );
					$memberships = array_unshift_assoc(
						$memberships,
						0,
						__( '- Select Membership to cancel -', MS_TEXT_DOMAIN )
					);
					break;

				case 'drop':
					$args = array(
						'post__in' => array_keys( $member->ms_relationships ),
					);
					$memberships = MS_Model_Membership::get_membership_names( $args, true );
					$memberships = array_unshift_assoc(
						$memberships,
						0,
						__( '- Select Membership to drop -', MS_TEXT_DOMAIN )
					);
					break;

				case 'move':
					$args = array(
						'post__in' => array_keys( $member->ms_relationships ),
					);
					$memberships_move = MS_Model_Membership::get_membership_names( $args );
					$memberships_move = array_unshift_assoc(
						$memberships_move,
						0,
						__( '- Select Membership to move from -', MS_TEXT_DOMAIN )
					);

					$memberships = MS_Model_Membership::get_membership_names( null, true );
					$memberships = array_diff_key( $memberships, $member->ms_relationships );
					$memberships = array_unshift_assoc(
						$memberships,
						0,
						__( '- Select Membership to move to -', MS_TEXT_DOMAIN )
					);
					break;

				case 'edit_date':
					$view = MS_Factory::create( 'MS_View_Member_Date' );
					$data['member_id'] = $member_id;
					$data['ms_relationships'] = MS_Model_Membership_Relationship::get_membership_relationships(
						array( 'user_id' => $member->id )
					);
					break;
			}
		}

		if ( in_array( $action, array( 'add', 'move', 'drop', 'cancel' ) ) ) {
			$view = MS_Factory::create( 'MS_View_Member_Membership' );
			$data['memberships'] = $memberships;
			if ( 'move' == $action ){
				$data['memberships_move'] = $memberships_move;
			}
		}

		$data['action'] = $action;
		$view->data = apply_filters(
			'ms_view_member_data',
			$data,
			$this
		);
		$view->render();
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

		foreach ( $members as $member_id ){
			// Member Model
			$member = MS_Factory::load( 'MS_Model_Member', $member_id );
			switch ( $action ) {
				case 'add':
					$member->add_membership( $membership_id );
					$msg = MS_Helper_Member::MSG_MEMBER_ADDED;
					break;

				case 'cancel':
					$member->cancel_membership( $membership_id );
					$msg = MS_Helper_Member::MSG_MEMBER_UPDATED;
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
							$ms_relationship = $member->ms_relationships[ $id ];
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
		WDev()->add_ui( 'jquery-ui' );
	}

	/**
	 * Load Member manager specific scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$data = array();

		if ( 'edit_date' == @$_GET['action'] ) {
			// Start and expire date edit
			wp_enqueue_script( 'jquery-ui-datepicker' );
			$data['ms_init'] = 'view_member_date';
		} else {
			// Members list
			$data['ms_init'][] = 'view_member_list';
			$data['lang'] = array(
				'select_user' => __( 'Select an User', MS_TEXT_DOMAIN ),
			);
		}

		wp_localize_script( 'ms-admin', 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

}