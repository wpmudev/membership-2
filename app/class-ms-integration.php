<?php
/**
 * This file defines the MS_Helper object.
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
 * Abstract class for all Helpers.
 *
 * All Helpers will extend or inherit from the MS_Helper class.
 * Methods of this class will be used to identify the purpose and
 * and actions of a helper.
 *
 * Almost all functionality will be created with in an extended class.
 *
 * @since 4.0.0
 *
 * @package Membership
 */
class MS_Integration extends MS_Hooker {
	
	const AUTOMESSAGE = 'automessage';
	
	const BUDDYPRESS = 'buddypress';
	
	protected $id =  'integrations';
	
	protected $name = 'Integration manager';
	
	/**
	 * Parent constuctor of all integrations.
	 *
	 * @since 4.0.0
	 */	
	public function __construct() {
		
		/**
		 * Actions to execute when constructing the parent helper.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Helper object.
		 */
		do_action( 'ms_parent_integration_construct', $this );		
	}
	
	public static function load_integrations() {
		return apply_filters( 'ms_integration_get_integrations', array(
				self::AUTOMESSAGE => new MS_Integration_Automessage(),
				self::BUDDYPRESS => new MS_Integration_Buddypress(),
		) );
	}
		
	/**
	 * Set specific property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'addons':
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
	
}