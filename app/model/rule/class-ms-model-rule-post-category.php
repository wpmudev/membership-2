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
 * Wrapper class to facilitate category and post rules integration.
 * Mainly for protection rules validation. 
 *
 */
class MS_Model_Rule_Post_Category extends MS_Model {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $post_rule;
	
	protected $category_rule;
		
	public function __construct( MS_Model_Rule_Post $post_rule, MS_Model_Rule_Category $category_rule ) {
		$this->post_rule = $post_rule;
		$this->category_rule = $category_rule;
	}
	
	/**
	 * Wrapper to verify access.
	 * 
	 * Checks both category rule and post rule.
	 * 
	 * @param $post_id The post Id to verify access to.
	 * @return boolean
	 */
	public function has_access( $post_id ) {
	
		$has_access = $this->post_rule->has_access( $post_id ) || $this->category_rule->has_access( $post_id );
		
		return $has_access;
	}	
}