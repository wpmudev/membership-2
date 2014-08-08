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
	
	const AJAX_ACTION_TOGGLE_RULE_DEFAULT = 'toggle_rule_default';
	
	/**
	 * Instance of MS_Model_Plugin.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */
	private $model;
	
	/**
	 * Prepare the Rule manager.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_RULE_DEFAULT, 'ajax_action_toggle_rule_default' );
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
	public function ajax_action_toggle_rule_default() {
		$msg = 0;
		if( $this->verify_nonce() && ! empty( $_POST['membership_id'] ) && ! empty( $_POST['rule'] ) ) {
			$this->model = apply_filters( 'membership_membership_model', MS_Factory::get_factory()->load_membership( $_POST['membership_id'] ) );
			$this->active_tab = $_POST['rule'];
			$msg = $this->rule_list_do_action( self::AJAX_ACTION_TOGGLE_RULE_DEFAULT, $_POST['rule'], array( $_POST['rule'] ) );
		}
	
		echo $msg;
		exit;
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
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}
	
		$rule = $this->model->get_rule( $rule_type );
		if( ! empty( $rule ) ) {
			foreach( $items as $item ) {
				switch( $action ) {
					case 'give_access':
						$rule->give_access( $item );
						break;
					case 'no_access':
						$rule->remove_access( $item );
					case 'toggle_activation':
						$rule->toggle_access( $item );
						break;
					case self::AJAX_ACTION_TOGGLE_RULE_DEFAULT:
						$rule->rule_value_default = ! $rule->rule_value_default; 
						break;
				}
			}
			$this->model->set_rule( $rule_type, $rule );
			$this->model->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}
		
		return $msg;
	}
}