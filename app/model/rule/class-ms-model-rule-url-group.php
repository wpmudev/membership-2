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
	
	protected $access;
	
	protected $strip_query_string;
	
	protected $is_regex = true;
	
	/**
	 * Verify access to the current url.
     *
	 * @since 4.0
	 *
	 * @access public
	 * @return boolean
	 */
	 public function has_access( $id = null ) {

	 	$has_access = false;
	 	
	 	if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
	 		
			$url = MS_Helper_Utility::get_current_page_url();
			
			if( $this->strip_query_string ) {
				$url = current( explode(  '?', $url ) );
			}
			
			$exclude = apply_filters( 'ms_model_rule_url_group_excluded_urls', array() );
			
			/**
			 * Check for exclude list.
			 */
			if( $this->check_url_expression_match( $url, $exclude ) ) {
				$has_access = true;
			}
			
			/**
			 * Check for url group.
			 */
			if( $this->check_url_expression_match( $url, $this->rule_value ) ) {
				$has_access = $this->access;
				if( $this->rule_value_invert ) {
					$has_access = ! $has_access;
				}
			}
	 	}
	 	return apply_filters( 'ms_model_rule_url_group_has_access', $has_access );
	}
	
	public function has_rule_for_current_url() {
		$has_rules = false;
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			$url = MS_Helper_Utility::get_current_page_url();
			if( $this->strip_query_string ) {
				$url = current( explode(  '?', $url ) );
			}
			
			if( $this->check_url_expression_match( $url, $this->rule_value ) ) {
				$has_rules = true;
			}
		}
		return apply_filters( 'ms_model_rule_url_group_has_access', $has_rules );
	}
	
	/**
	 * Check url expression macth.
	 * 
	 * @since 4.0
	 *
	 * @access public
	 * @param string $url The url to match.
	 * @param string[] $check_list The url list to verify match.
	 * @return boolean
	 */
	public function check_url_expression_match( $url, $check_list ) {
		if( is_array( $check_list ) && ! empty( $check_list ) ) {
			
			/**
			 * Use regex to find match.
			 */
			if( $this->is_regex ) {
				$check_list = array_map( 'strtolower', array_filter( array_map( 'trim', $check_list ) ) );
				foreach ( $check_list as $list_value ) {
					$match_string = mb_stripos( $list_value, '\/' ) !== false ? stripcslashes( $list_value ) : $list_value;
					if ( preg_match( "#^{$match_string}$#i", $url ) ) {
						return true;
					}
				}
			}
			/**
			 * Straight match.
			 */
			else {
				$check_list = array_map( 'strtolower', array_filter( array_map( 'trim', $check_list ) ) );
				$check_list = array_merge( $check_list, array_map( 'untrailingslashit', $check_list ) );
				if ( in_array( strtolower( $url ), $check_list ) ) {
					return true;
				} 
			}
		}
		return false;
	}
	
   /**
	* Count protection rules quantity.
	*
	* @since 1.0.0
	* @param bool $has_access_only Optional. Count rules for has_access status only.
	* @return int $count The rule count result.
	*/
	public function count_rules( $has_access_only = true ) {
	
		$count = 0;
		if( $this->access ) {
			$count = count( $this->rule_value );
		}
		return apply_filters( 'ms_model_rule_url_group_count_rules', $count );
	}
	
	/**
	 * Get content eligible for protection.
	 * 
	 * 
	 * @since 1.0
	 *
	 * @access public
	 * @return object[] The content array.
	 */
	public function get_contents( $args = null ) {
		$contents = array();
		foreach( $this->rule_value as $value ) {
			$content = new StdClass();
			$content->name = $value;
			$content->access = $this->access;
			$contents[] = $content;
		}
		return apply_filters( 'ms_model_rule_url_group_get_contents', $contents );
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
					if( ! is_array( $value ) ) {
						$value = explode( PHP_EOL, $value );
					}
					$this->$property = array_filter( array_map( 'trim', $value ) )	;
					break;
				case 'strip_query_string':
				case 'is_regex':
					$this->$property = $this->validate_bool( $value );
					break;
				default:
					parent::__set( $property, $value );
					break;
			}
		}
	}
}