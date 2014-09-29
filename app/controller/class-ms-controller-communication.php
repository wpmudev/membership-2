<?php
/**
 * This file defines the MS_Controller_Settings class.
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
 * Controller for managing Membership Plugin settings.
 *
 * The primary entry point for managing Membership admin pages.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Communication extends MS_Controller {
	
	const AJAX_ACTION_UPDATE_COMM = 'update_comm';
	
	/**
	 * Prepare Membership settings manager.
	 *
	 * @since 1.0
	 */		
	public function __construct() {
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_COMM, 'ajax_action_update_communication' );
		
	}
	
	/**
	 * Handle Ajax update comm field action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_update_comm
	 *
	 * @since 1.0
	 */
	public function ajax_action_update_communication() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		
		$isset = array( 'type', 'field', 'value' );
		if( $this->verify_nonce() && $this->validate_required( $isset, 'POST', false ) && $this->is_admin_user() ) {
			$comm = MS_Model_Communication::get_communication( $_POST['type'] );
			$field = $_POST['field'];
			$value = $_POST['value'];
			$comm->$field = $value;
			$comm->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}
		
		echo $msg;
		exit;
	}
	
}