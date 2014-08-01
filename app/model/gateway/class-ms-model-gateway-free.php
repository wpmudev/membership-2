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

class MS_Model_Gateway_Free extends MS_Model_Gateway {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected static $instance;
	
	protected $id = self::GATEWAY_FREE;
	
	protected $name = 'Free Gateway';
	
	protected $description = 'Free Memberships';
	
	protected $manual_payment = true;
	
	protected $active = true;
	
	public function purchase_button( $ms_relationship = false ) {
		$membership = $ms_relationship->get_membership();
		if( 0 != $membership->price ) {
			return;
		}
		parent::purchase_button( $ms_relationship );
	}
}
