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
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Rule extends MS_Controller {
	
	const AJAX_ACTION_TOGGLE_RULE = 'toggle_rule';
	
	const AJAX_ACTION_TOGGLE_RULE_DEFAULT = 'toggle_rule_default';

	const AJAX_ACTION_UPDATE_RULE = 'update_rule';
	
	const AJAX_ACTION_UPDATE_DRIPPED = 'update_dripped';
	
	/**
	 * Prepare the Rule manager.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_RULE, 'ajax_action_toggle_rule' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_RULE_DEFAULT, 'ajax_action_toggle_rule_default' );
		
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_RULE, 'ajax_action_update_rule' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_DRIPPED, 'ajax_action_update_dripped' );
		
		
		$this->add_action( 'ms_controller_membership_admin_page_process_' . MS_Controller_Membership::STEP_SETUP_PROTECTED_CONTENT, 'edit_rule_manager' );
	}
	
	/**
	 * Handle Ajax toggle action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_toggle_rule
	 *
	 * @since 4.0.0
	 */
	public function ajax_action_toggle_rule() {
		$msg = 0;
		
		$required = array( 'membership_id', 'rule', 'item' );
		if( $this->verify_nonce() && $this->validate_required( $required ) && $this->is_admin_user() ) {
			$msg = $this->rule_list_do_action( 'toggle_access',  $_POST['rule'], array( $_POST['item'] ) );
		}
	
		echo $msg;
		exit;
	}
	
	/**
	 * Handle Ajax toggle action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_toggle_rule_default
	 *
	 * @since 4.0.0
	 */
	public function ajax_action_toggle_rule_default() {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if( $this->verify_nonce() && ! empty( $_POST['membership_id'] ) && ! empty( $_POST['rule'] ) ) {
			$this->active_tab = $_POST['rule'];
			$msg = $this->rule_list_do_action( self::AJAX_ACTION_TOGGLE_RULE_DEFAULT, $_POST['rule'], array( $_POST['rule'] ) );
		}
	
		echo $msg;
		exit;
	}
	
	public function ajax_action_update_rule() {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		
		$required = array( 'membership_id', 'rule_type' );
		$isset = array( 'rule_ids', 'rule_value' );
		if( $this->verify_nonce() && $this->validate_required( $required ) && $this->validate_required( $isset, 'POST', false ) ) {
			$msg = $this->save_rule_values( $_POST['rule_type'], $_POST['rule_ids'], $_POST['rule_value'] );
		}
	
		echo $msg;
		exit;
	}
	
	private function save_rule_values( $rule_type, $rule_ids, $rule_values ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		$membership = $this->get_membership();

		if( $membership->is_valid() ) {
			$rule = $membership->get_rule( $rule_type );
			$rule->reset_rule_values();
			if( ! is_array( $rule_ids ) ) {
				$rule_ids = array( $rule_ids );
			}
			foreach( $rule_ids as $id ) {
				if( ! empty( $id ) ) {
					if( is_array( $rule_values ) ) {
						$rule_value = $rule_values[ $id ];
					}
					else{
						$rule_value = $rule_values;
					}
					$rule->set_access( $id, $rule_value );
				}
			}
			$membership->set_rule( $rule_type, $rule );
			$membership->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}
		return $msg;
	}
	
	public function ajax_action_update_dripped() {
// 		MS_Helper_Debug::log( $_POST );
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;

		$fields = array( 'membership_id', 'rule_type', 'dripped_type', 'id', 'field', 'value' );
		if( $this->verify_nonce() && $this->validate_required( $fields ) && $this->is_admin_user() ) {
			$membership = $this->get_membership();
			if( $membership->is_valid() ) {
				$rule_type = $_POST['rule_type'];
				$dripped_type = $_POST['dripped_type'];
				$id = $_POST['id'];
				$field = $_POST['field'];
				$value = $_POST['value'];
				$rule = $membership->get_rule( $rule_type );
				
				$rule->set_dripped_value( $dripped_type, $id, $field, $value );
// 				MS_Helper_Debug::log( $rule->dripped );
				$membership->set_rule( $rule_type, $rule );
				$membership->save();
				$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
			}
		}
		
		echo $msg;
		exit;
	}
	
	/**
	 * Handles Membership Rule form submissions.
	 *
	 * **Hooks Actions: **
	 *
	 * * ms_controller_membership_edit_manager

	 * @since 4.0.0
	 */
	public function edit_rule_manager( $rule_type ) {

		/**
		 * Copy membership dripped schedule
		 */
		if( ! empty( $_POST['copy_dripped'] ) && ! empty( $_POST['membership_copy'] ) && $this->verify_nonce() ) {
			$msg = $this->copy_dripped_schedule( $_POST['membership_copy'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
		}
		/**
		 * Save membership dripped schedule
		 */
		elseif( ! empty( $_POST['dripped_submit'] ) && $this->verify_nonce( 'bulk-rules', 'POST' ) ) {
			$items = ! empty( $_POST['item'] ) ?  $_POST['item'] : null;
			$msg = $this->save_dripped_schedule( $items );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
		}
		/**
		 * Rule single action
		 */
		if( $this->verify_nonce( null, 'GET' ) ) {
			$msg = $this->rule_list_do_action( $_GET['action'], $rule_type, array( $_GET['item'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'action', 'item', '_wpnonce' ) ) ) );
		}
		/**
		 * Rule bulk actions
		 */
		elseif( $this->verify_nonce( 'bulk-rules', 'POST' ) && ! empty( $_POST['action'] ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->rule_list_do_action( $action, $rule_type, $_POST['item'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
		}
		/**
		 * Save url group add/edit
		 */
		elseif ( ! empty( $_POST['url_group_submit'] ) && $this->verify_nonce() ) {
			$msg = $this->save_url_group( $_POST );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
		}
		
	}
	
	/**
	 * Execute action in Rule model.
	 *
	 * @since 4.0.0
	 *
	 * @param string $action The action to execute.
	 * @param int[] $items The item ids which action will be taken.
	 * @return int Resulting message id.
	 */
	private function rule_list_do_action( $action, $rule_type, $items ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
	
		$membership = $this->get_membership(); 
		if( empty( $membership ) || ! MS_Model_Rule::is_valid_rule_type( $rule_type ) ) {
			return $msg;
		}
		
		
		$rule = $membership->get_rule( $rule_type );
		if( ! empty( $rule ) ) {
			foreach( $items as $item ) {
				switch( $action ) {
					case 'give_access':
						$rule->give_access( $item );
						break;
					case 'no_access':
						$rule->remove_access( $item );
						break;
					case 'toggle_access':
						$rule->toggle_access( $item );
						break;
					case self::AJAX_ACTION_TOGGLE_RULE_DEFAULT:
						$rule->rule_value_default = ! $rule->rule_value_default; 
						break;
				}
			}
//			MS_Helper_Debug::log($rule);
			$membership->set_rule( $rule_type, $rule );
			$membership->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}
		
		return $msg;
	}
	
	/**
	 * Save Url Groups tab.
	 *
	 * @since 4.0.0
	 *
	 * @param array $fields The POST fields
	 */
	private function save_url_group( $fields ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		$membership = $this->get_membership();
		if( empty( $membership ) ) {
			return $msg;
		}
		
		if( is_array( $fields ) ) {
			$rule_type = MS_Model_Rule::RULE_TYPE_URL_GROUP;
			$rule = $membership->get_rule( $rule_type );
	
			foreach( $fields as $field => $value ) {
				$rule->$field = $value;
			}
			$membership->set_rule( $rule_type, $rule );
			$membership->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}
		return $msg;
	
	}
	
	/**
	 * Save new 'dripped content' schedule(s).
	 *
	 * @deprecated
	 * @since 4.0.0
	 *
	 * @param mixed[] $items The item ids which action will be taken.
	 */
	private function save_dripped_schedule( $items ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		$membership = $this->get_membership();
		if( empty( $membership ) ) {
			return $msg;
		}
		
		$dripped = array(
				'post' => array(),
				'page' => array(),
		);
	
		if( is_array( $items ) ) {
			foreach( $items as $item ) {
				$dripped[ $item['type'] ][ $item['id'] ] = array(
						'period_unit' => $item['period_unit'],
						'period_type' => $item['period_type'],
				);
			}
	
		}
	
		foreach( $dripped as $rule_type => $drip ) {
			$rule = $membership->rules[ $rule_type ];
			$rule->dripped = $drip;
			$membership->set_rule( $rule_type, $rule );
		}
	
		$membership->save();
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		return $msg;
	}
	
	/**
	 * Coppy 'dripped content' schedule from one Membership to another.
	 *
	 * @deprecated
	 * @since 4.0.0
	 *
	 * @param int $copy_from_id The Membership ID to copy from.
	 */
	private function copy_dripped_schedule( $copy_from_id ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_DRIPPED_NOT_COPIED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		$membership = $this->get_membership();
		if( empty( $membership ) ) {
			return $msg;
		}
		
		$src_membership = MS_Factory::load( 'MS_Model_Membership', $copy_from_id );
		if( $src_membership->id > 0 ) {
	
			$rule_types = array( 'post', 'page' );
			foreach( $rule_types as $rule_type) {
				$membership->set_rule( $rule_type, $src_membership->rules[ $rule_type ] );
			}
			$membership->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_DRIPPED_COPIED;
		}
		return $msg;
	}
	
	/**
	 * Get membership from request.
	 * 
	 * @since 4.0.0
	 * 
	 * @return MS_Model_Membership or null if not found.
	 */
	private function get_membership() {
		$membership_id = 0;
		if( ! empty( $_GET['membership_id'] ) ) {
			$membership_id = $_GET['membership_id'];
		}
		elseif( ! empty( $_POST['membership_id'] ) ) {
			$membership_id = $_POST['membership_id'];
		}
		
		$membership = null;
		if( ! empty( $membership_id ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		}
		
		return apply_filters( 'ms_controller_rule_get_membership', $membership );
	}
}