<?php
/**
 * This file defines the MS_Controller object.
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
 * Abstract class for all Controllers.
 *
 * All controllers will extend or inherit from the MS_Controller class.
 * Methods of this class will control the flow and behaviour of the plugin
 * by using MS_Model and MS_View objects.
 *
 * @since 4.0.0
 *
 * @uses MS_Model
 * @uses MS_View
 *
 * @package Membership
 */
class MS_Controller extends MS_Hooker {
	
	/**
	 * Instance of MS_Model object
	 *
	 * **Note:** Could be a keyed array of MS_Model objects
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _model
	 */
	private $_model;

	/**
	 * Instance of MS_View object
	 * 
	 * **Note:** Could be a keyed array of MS_Model objects
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _view
	 */
	private $_view;

	/**
	 * Parent constuctor of all controllers.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
	}
	
	
}