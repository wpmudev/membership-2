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
 * Membership URL Group Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Url_Group extends MS_Model_Rule {
	
	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_URL_GROUP;
	
	/**
	 * Has access to url group toggle.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean $access
	 */
	protected $access;
	
	/**
	 * Strip query strings from url before testing.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean $strip_query_string
	 */
	protected $strip_query_string;
	
	/**
	 * Is regular expression indicator.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean $is_regex
	 */
	protected $is_regex = true;
	
	/**
	 * Verify access to the current content.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Optional. The post/CPT ID to verify access. Defaults to current URL. 
	 * @return boolean True if has access, false otherwise.
	 */
	 public function has_access( $post_id = null ) {

	 	$has_access = false;
	 	
	 	if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
	 		
	 		if( ! empty( $post_id ) ) {
	 			$url = get_permalink( $post_id );
	 		}
	 		else {
				$url = MS_Helper_Utility::get_current_page_url();
	 		}
	 		
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
	 	
	 	return apply_filters( 'ms_model_rule_url_group_has_access', $has_access, $post_id, $this );
	}
	
	/**
	 * Verify if current url has protection rules.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if has access, false otherwise.
	 */
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
		
		return apply_filters( 'ms_model_rule_url_group_has_access', $has_rules, $this );
	}
	
	/**
	 * Verify if a post/custom post type has protection rules.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if has access, false otherwise.
	 */
	public function has_rule_for_post( $post_id ) {
		$has_rules = false;
		
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			$url = get_permalink( $post_id );
			if( $this->strip_query_string ) {
				$url = current( explode(  '?', $url ) );
			}
				
			if( $this->check_url_expression_match( $url, $this->rule_value ) ) {
				$has_rules = true;
			}
		}
		
		return apply_filters( 'ms_model_rule_url_group_has_rule_for_post', $has_rules, $this );
	}
	
	/**
	 * Check url expression macth.
	 * 
	 * @since 1.0.0
	 *
	 * @param string $url The url to match.
	 * @param string[] $check_list The url list to verify match.
	 * @return boolean True if matches.
	 */
	public function check_url_expression_match( $url, $check_list ) {
		
		$match = false;
		
		if( is_array( $check_list ) && ! empty( $check_list ) ) {
			
			/**
			 * Use regex to find match.
			 */
			if( $this->is_regex ) {
				$check_list = array_map( 'strtolower', array_filter( array_map( 'trim', $check_list ) ) );
				foreach ( $check_list as $list_value ) {
					$match_string = mb_stripos( $list_value, '\/' ) !== false ? stripcslashes( $list_value ) : $list_value;
					if ( preg_match( "#^{$match_string}$#i", $url ) ) {
						$match = true;
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
					$match = true;
				} 
			}
		}
		
		return apply_filters( 'ms_model_rule_url_group_check_url_expression_match', $match, $url, $check_list, $this );
	}
	
   /**
	* Count protection rules quantity.
	*
	* @since 1.0.0
	* 
	* @param bool $has_access_only Optional. Count rules for has_access status only.
	* @return int $count The rule count result.
	*/
	public function count_rules( $has_access_only = true ) {
	
		$count = 0;
		if( $this->access ) {
			$count = count( $this->rule_value );
		}
		
		return apply_filters( 'ms_model_rule_url_group_count_rules', $count, $this );
	}
	
	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 * @param $args The filter args
	 * 				
	 * @return array The contents array.
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
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		
		if( property_exists( $this, $property ) ) {
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
		
		do_action( 'ms_model_rule_url_group__set_after', $property, $value, $this );
	}
}