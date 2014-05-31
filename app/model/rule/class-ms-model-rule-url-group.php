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


class MS_Model_Rule_Url_Group extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_URL_GROUP;
	
	protected $urls = array();
	
	protected $access;
	
	protected $url_group;
	
	protected $strip_query_string;
	
	protected $is_regex;
	
	public function protect_content() {
		
	}
	
	public function get_content() {
		$contents = array();
		foreach( $this->urls as $id => $url ) {
			$contents[ $id ]->id = $id;
			$contents[ $id ]->url = $url['url'];
			if( in_array( $id, $this->rule_value ) ) {
				$contents[ $id ]->access = true;
			}
			else {
				$contents[ $id ]->access = false;
			}
		} 
		return $contents;
	}
	
	/**
	 * Validate specific property before set.
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
				case 'rule_value':
					$this->$property =  array_filter( array_map( 'trim', explode( PHP_EOL, $value ) ) )	;
					break;
				default:
					parent::__set( $property, $value );
					break;
			}
		}
	}
}