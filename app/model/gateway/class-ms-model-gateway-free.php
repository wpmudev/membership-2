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

/**
 * Free Gateway.
 *
 * Process free memberships.
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Gateway_Free extends MS_Model_Gateway {
	
	/**
	 * Gateway singleton instance.
	 *
	 * @since 1.0.0
	 * @var string $instance
	 */
	public static $instance;
	
	/**
	 * Gateway ID.
	 *
	 * @since 1.0.0
	 * @var int $id
	 */
	protected $id = self::GATEWAY_FREE;
	
	/**
	 * Gateway name. 
	 * 
	 * @since 1.0.0
	 * @var string $name
	 */
	protected $name = 'Free Gateway';//i18n please, you'll have to set via __construct()
	
	/**
	 * Gateway description.
	 *
	 * @since 1.0.0
	 * @var string $description
	 */
	protected $description = '';
	
	/**
	 * Gateway active status.
	 *
	 * @since 1.0.0
	 * @var string $active
	 */
	protected $active = true;
	
	/**
	 * Manual payment indicator.
	 * 
	 * If the gateway does not allow automatic reccuring billing.
	 * 
	 * @since 1.0.0
	 * @var bool $manual_payment
	 */
	protected $manual_payment = true;
	
}
