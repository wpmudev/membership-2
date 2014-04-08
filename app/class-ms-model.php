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
 * Abstract class for all Models.
 *
 * All models will extend or inherit from the MS_Model class.
 * Methods of this class will prepare objects for the database and
 * manipulate data to be used in a MS_Controller.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_Model extends MS_Hooker {
	
	/**
	 * Dynamic data for MS_Model.
	 *
	 * Allows dynamic properties to be called on model object.
	 * e.g. my_model->new_property
	 *
	 * @since 4.0.0
	 * @access private
	 * @var array data
	 */
	private $data = array();
	
	
	public function __construct() {
	}
	

	/**
	 * Creates/sets MS_Model properties.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param object $index The property.
	 * @param object $value The property value.
	 */
	public function __set($index, $value)
 	{
        $this->data[$index] = $value;
 	}

	/**
	 * Returns MS_Model properties.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param object $index The property.
	 * @return object The property value.
	 */	
	public function __get($index)
	{
		return $this->vars[$index];
	}
	
}