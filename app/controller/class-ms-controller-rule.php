<?php
/**
 * This file defines the MS_Controller_Billing class.
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
 * Controller to manage billing and invoices.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Rule extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_CHANGE_MEMBERSHIPS = 'ms_rule_change_memberships';
	const AJAX_ACTION_UPDATE_MATCHING = 'ms_rule_update_matching';
	const AJAX_ACTION_UPDATE_DRIPPED = 'ms_rule_update_dripped';

	/**
	 * Prepare the Rule manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_CHANGE_MEMBERSHIPS, 'ajax_action_change_memberships' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_MATCHING, 'ajax_action_update_matching' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_DRIPPED, 'ajax_action_update_dripped' );

		$this->add_action(
			'ms_controller_membership_admin_page_process_' . MS_Controller_Membership::STEP_PROTECTED_CONTENT,
			'edit_rule_manager'
		);
	}

	/**
	 * Handle Ajax change-memberships action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_change_memberships
	 *
	 * @since 1.1.0
	 */
	public function ajax_action_change_memberships() {
		$msg = 0;
		$this->_resp_reset();

		$required = array( 'rule', 'item' );
		if ( $this->_resp_ok() && ! $this->is_admin_user() ) { $this->_resp_err( 'permission denied' ); }
		if ( $this->_resp_ok() && ! $this->verify_nonce() ) { $this->_resp_err( 'toggle-rule: nonce' ); }
		if ( $this->_resp_ok() && ! self::validate_required( $required ) ) { $this->_resp_err( 'toggle-rule: required' ); }

		if ( $this->_resp_ok() ) {
			$values = array();
			if ( isset( $_POST['values'] ) && is_array( $_POST['values'] ) ) {
				$values = $_POST['values'];
			}

			$msg = $this->assign_memberships(
				$_POST['rule'],
				$_POST['item'],
				$values
			);
		}
		$msg .= $this->_resp_code();

		echo $msg;
		exit;
	}

	/**
	 * Handle Ajax update rule-matchong action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_update_matching
	 *
	 * @since 1.0.4.2
	 */
	public function ajax_action_update_matching() {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		$this->_resp_reset();

		$required = array( 'rule_type' );
		$isset = array( 'item', 'value' );

		if ( $this->_resp_ok() && ! $this->verify_nonce() ) { $this->_resp_err( 'update-matching-01' ); }
		if ( $this->_resp_ok() && ! self::validate_required( $required ) ) { $this->_resp_err( 'update-matching-02' ); }
		if ( $this->_resp_ok() && ! self::validate_required( $isset, 'POST', false ) ) { $this->_resp_err( 'update-matching-03' ); }

		if ( $this->_resp_ok() ) {
			$rule_type = $_POST['rule_type'];
			$msg = $this->save_rule_values(
				$rule_type,
				$_POST['item'],
				$_POST['value'],
				false
			);
		}
		$msg .= $this->_resp_code();

		echo $msg;
		exit;
	}

	/**
	 * Save rules for a rule type.
	 *
	 * First reset all rules, then save the incoming rules.
	 * The menu rule type is only reset for the parent menu_id group (clears all children submenus).
	 *
	 * @since 1.0.0
	 *
	 * @param string $rule_type The rule type to update.
	 * @param string[] $rule_ids The content identifiers.
	 * @param int|int[] $rule_values The rule values.
	 * @param bool $reset If set to false then the exiting rule values will be kept.
	 */
	private function save_rule_values( $rule_type, $rule_ids, $rule_values, $reset = true ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$membership = $this->get_membership();

		if ( $membership->is_valid() ) {
			$rule = $membership->get_rule( $rule_type );

			if ( $reset ) {
				if ( MS_Rule_MenuItem::RULE_ID === $rule->rule_type
					&& ! empty( $_POST['menu_id'] )
				) {
					$rule->reset_menu_rule_values( $_POST['menu_id'] );
				} else {
					$rule->reset_rule_values();
				}
			}

			if ( ! is_array( $rule_ids ) ) {
				$rule_ids = array( $rule_ids );
			}

			foreach ( $rule_ids as $id ) {
				if ( ! empty( $id ) ) {
					if ( is_array( $rule_values ) ) {
						if ( isset( $rule_values[ $id ] ) ) {
							$rule_value = $rule_values[ $id ];
						} else {
							continue;
						}
					} else {
						$rule_value = $rule_values;
					}
					$rule->set_access( $id, $rule_value );
				}
			}
			$membership->set_rule( $rule_type, $rule );
			$membership->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		return apply_filters(
			'ms_controller_rule_save_rule_values',
			$msg,
			$rule_type,
			$rule_ids,
			$rule_values,
			$this
		);
	}

	/**
	 * Handle Ajax update dripped rules action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_update_dripped
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_dripped() {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		$this->_resp_reset();

		$fields = array(
			'membership_ids',
			'rule_type',
			'item_id',
		);

		if ( ! $this->verify_nonce( 'inline' ) ) {
			$this->_resp_err( 'Invalid Nonce' );
		} elseif ( ! $this->is_admin_user() ) {
			$this->_resp_err( 'Access Denied' );
		} elseif ( ! self::validate_required( $fields, 'POST', false ) ) {
			$this->_resp_err( 'Missing Data' );
		}

		if ( $this->_resp_ok() ) {
			$ids = $_POST['membership_ids'];
			$rule_type = $_POST['rule_type'];
			$item_id = $_POST['item_id'];

			if ( ! is_array( $ids ) ) {
				$ids = explode( ',', $ids );
			}

			// Loop all specified memberships and set the rule values.
			foreach ( $ids as $id ) {
				if ( empty( $_POST['ms_' . $id] ) ) { continue; }
				$data = WDev()->array->get( $_POST['ms_' . $id] );
				WDev()->array->equip( $data, 'dripped_type', 'date', 'delay_unit', 'delay_type' );

				$membership = MS_Factory::load( 'MS_Model_Membership', $id );
				$rule = $membership->get_rule( $rule_type );

				$rule->set_dripped_value(
					$item_id,
					$data['dripped_type'],
					$data['date'],
					$data['delay_unit'],
					$data['delay_type']
				);

				$membership->set_rule( $rule_type, $rule );
				$membership->save();
			}

			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		if ( MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED == $msg ) {
			// If everything went well then get a refershed version of the list-table.
			$GLOBALS['hook_suffix'] = 'protect-content_page_protected-content-setup';
			$table = apply_filters( 'ms_rule_listtable-' . $rule_type, null );

			if ( $table ) {
				$items = $table->get_model()->get_contents();
				echo '' . $table->display_rows( array( $items[ $item_id ] ) );
			}
		} else {
			$msg .= $this->_resp_code();
			echo '' . $msg;
		}

		exit;
	}

	/**
	 * Handles Membership Rule form submissions.
	 *
	 * Related Action Hooks:
	 * - ms_controller_membership_edit_manager

	 * @since 1.0.0
	 */
	public function edit_rule_manager( $rule_type ) {
		$redirect = false;

		if ( isset( $_POST['rule'] ) ) {
			$rule_type = $_POST['rule'];
		}

		do_action( 'ms_controller_rule_edir_rule_manager', $rule_type, $this );

		/**
		 * Rule single action
		 */
		if ( $this->verify_nonce( null, 'GET' ) ) {
			$msg = $this->rule_list_do_action(
				$_GET['action'],
				$rule_type,
				array( $_GET['item'] )
			);
			$redirect = add_query_arg(
				array( 'msg' => $msg),
				remove_query_arg( array( 'action', 'item', '_wpnonce' ) )
			);
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Execute action in Rule model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The action to execute.
	 * @param int[] $items The item ids which action will be taken.
	 * @return int Resulting message id.
	 */
	private function rule_list_do_action( $action, $rule_type, $items ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$membership = $this->get_membership();
		if ( empty( $membership ) ) {
			return $msg;
		}

		$rule = $membership->get_rule( $rule_type );
		if ( ! empty( $rule ) ) {
			foreach ( $items as $item ) {
				switch ( $action ) {
					case 'give_access':
						$rule->give_access( $item );
						break;

					case 'no_access':
						$rule->remove_access( $item );
						break;
				}
			}
			$membership->set_rule( $rule_type, $rule );
			$membership->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		return apply_filters(
			'ms_controller_rule_rule_list_do_action',
			$msg,
			$action,
			$rule_type,
			$items,
			$this
		);
	}

	/**
	 * Assigns (or removes) memberships from a rule-item.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $rule_type [description]
	 * @param  string $item [description]
	 * @param  array $memberships Memberships that will be assigned to the
	 *                rule-item. Memberships that are not mentioned are removed.
	 * @return string [description]
	 */
	private function assign_memberships( $rule_type, $item, $memberships ) {
		$base = MS_Model_Membership::get_base();
		$rule = $base->get_rule( $rule_type );

		$memberships = apply_filters(
			'ms_controller_rule_assign_memberships',
			$memberships,
			$rule,
			$item,
			$this
		);

		$rule->set_memberships( $item, $memberships );

		do_action(
			'ms_controller_rule_assign_memberships_done',
			$rule,
			$item,
			$memberships,
			$this
		);

		return MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
	}

	/**
	 * Save Url Groups tab.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields The POST fields
	 */
	private function save_url_group( $fields ) {

		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$membership = $this->get_membership();
		if ( empty( $membership ) ) {
			return $msg;
		}

		if ( is_array( $fields ) ) {
			$rule_type = MS_Rule_Url::RULE_ID;
			$rule = $membership->get_rule( $rule_type );

			foreach ( $fields as $field => $value ) {
				$rule->$field = $value;
			}
			$membership->set_rule( $rule_type, $rule );
			$membership->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		return apply_filters( 'ms_controller_rule_save_url_group', $msg );
	}

	/**
	 * Get membership from request.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Membership or null if not found.
	 */
	private function get_membership() {
		$membership_id = 0;

		if ( ! empty( $_GET['membership_id'] ) ) {
			$membership_id = $_GET['membership_id'];
		} elseif ( ! empty( $_POST['membership_id'] ) ) {
			$membership_id = $_POST['membership_id'];
		}

		$membership = null;
		if ( ! empty( $membership_id ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		} else {
			$membership = MS_Model_Membership::get_base();
		}

		return apply_filters( 'ms_controller_rule_get_membership', $membership, $this );
	}
}