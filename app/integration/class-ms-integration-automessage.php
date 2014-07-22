<?php
/**
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


class MS_Integration_Automessage extends MS_Integration {
	
	protected static $CLASS_NAME = __CLASS__;
	
	
	public function __construct() {
		parent::__construct();
		$this->add_filter( 'automessage_custom_user_hooks', 'automessage_custom_user_hooks' );
	}
	
	/**
	 * wpmu.dev Automessage plugin integration.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param array $hooks The existing hooks.
	 * @return array The modified array of hooks.
	 */
	public function automessage_custom_user_hooks( $hooks ) {
		$comm_types = MS_Model_Communication::get_communication_type_titles();
	
		foreach( $comm_types as $type => $desc ) {
			$action = "ms_communications_process_$type";
			$hooks[ $action ] = array( 'action_nicename' => $desc );
		}
	
		return $hooks;
	}
	
}