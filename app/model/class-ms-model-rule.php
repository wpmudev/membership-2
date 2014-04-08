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


class MS_Model_Rule extends MS_Model {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type;
	
	protected $rule_value;
	
	protected $delayed_period;
	
	protected $delayed_period_type;
	
	public function __construct() {

	}
	
	public function on_protection() {
		throw new Exception ("Method to be implemented in child class");
	}
	
	public function validate_protection() {
		throw new Exception ("Method to be implemented in child class");
	}

}