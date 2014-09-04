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
	
	const RULE_TYPE_CATEGORY = 'category';
	
	const RULE_TYPE_COMMENT = 'comment';
	
	const RULE_TYPE_MEDIA = 'media';
	
	const RULE_TYPE_MENU = 'menu';
	
	const RULE_TYPE_PAGE = 'page';
	
	const RULE_TYPE_POST = 'post';
	
	const RULE_TYPE_MORE_TAG = 'more_tag';
	
	const RULE_TYPE_CUSTOM_POST_TYPE = 'cpt';
	
	const RULE_TYPE_CUSTOM_POST_TYPE_GROUP = 'cpt_group';
	
	const RULE_TYPE_SHORTCODE = 'shortcode';
	
	const RULE_TYPE_URL_GROUP = 'url_group';
	
	const FILTER_HAS_ACCESS = 'has_access';
	
	const FILTER_NO_ACCESS = 'no_access';
	
	const FILTER_DRIPPED = 'dripped';
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type;
	
	protected $rule_value = array();
	
	protected $inherit_rules;
	
	protected $dripped = array();
	
	protected $rule_value_default = true;
	
	protected $rule_value_invert = false;

	/**
	 * Rule types.
	 *
	 * @todo change array to be rule -> title.
	 *
	 * This array is ordered in the hierarchy way.
	 * First one has more priority than the last one.
	 * This hierarchy is used to determine access to protected content.
	 */
	public static function get_rule_types() {
		$rule_types =  array(
				0 => self::RULE_TYPE_POST,
				1 => self::RULE_TYPE_CATEGORY,
				2 => self::RULE_TYPE_CUSTOM_POST_TYPE,
				3 => self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
				4 => self::RULE_TYPE_PAGE,
				5 => self::RULE_TYPE_MORE_TAG,
				6 => self::RULE_TYPE_MENU,
				7 => self::RULE_TYPE_SHORTCODE,
				8 => self::RULE_TYPE_COMMENT,
				9 => self::RULE_TYPE_MEDIA,
				10 => self::RULE_TYPE_URL_GROUP,
		);
	
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			unset( $rule_types[1] );
		}
		else {
			unset( $rule_types[0] );
		}
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			unset( $rule_types[3] );
		}
		else {
			unset( $rule_types[2] );
		}
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) ) {
			unset( $rule_types[9] );
		}
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			unset( $rule_types[10] );
		}
	
		return  apply_filters( 'ms_model_rule_get_rule_types', $rule_types );
	}
	
	/**
	 * Rule types and respective classes.
	 *
	 * This array is ordered in the hierarchy way.
	 * First one has more priority than the last one.
	 * This hierarchy is used to determine access to protected content.
	 */
	public static function get_rule_type_classes() {
		return apply_filters( 'ms_model_rule_get_rule_type_classes', array(
				self::RULE_TYPE_POST => 'MS_Model_Rule_Post',
				self::RULE_TYPE_CATEGORY => 'MS_Model_Rule_Category',
				self::RULE_TYPE_CUSTOM_POST_TYPE => 'MS_Model_Rule_Custom_Post_Type',
				self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP => 'MS_Model_Rule_Custom_Post_Type_Group',
				self::RULE_TYPE_PAGE => 'MS_Model_Rule_Page',
				self::RULE_TYPE_MORE_TAG => 'MS_Model_Rule_More',
				self::RULE_TYPE_MENU => 'MS_Model_Rule_Menu',
				self::RULE_TYPE_SHORTCODE => 'MS_Model_Rule_Shortcode',
				self::RULE_TYPE_COMMENT => 'MS_Model_Rule_Comment',
				self::RULE_TYPE_MEDIA => 'MS_Model_Rule_Media',
				self::RULE_TYPE_URL_GROUP => 'MS_Model_Rule_Url_Group',
		)
		);
	}
	
	public static function get_rule_type_titles() {
		return apply_filters( 'ms_model_rule_get_rule_type_titles', array(
				self::RULE_TYPE_CATEGORY => __( 'Category' , MS_TEXT_DOMAIN ),
				self::RULE_TYPE_COMMENT => __( 'Comment', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_MEDIA => __( 'Media', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_MENU => __( 'Menu', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_PAGE => __( 'Page', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_MORE_TAG => __( 'More Tag', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_POST => __( 'Post', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_SHORTCODE => __( 'Shortcode', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_URL_GROUP => __( 'Url Group', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_CUSTOM_POST_TYPE => __( 'Custom Post Type', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP => __( 'CPT Group', MS_TEXT_DOMAIN ),
		)
		);
	}
	
	public static function rule_factory( $rule_type ) {
		if( self::is_valid_rule_type( $rule_type ) ) {
			$rule_types = self::get_rule_type_classes();
			return apply_filters( 'ms_model_rule_rule_factory', new $rule_types[ $rule_type ]() );
		}
		else {
			throw new Exception( "Rule factory - rule type not found: $rule_type"  );
		}
	}
	
	public static function rule_set_factory( $rules = null ) {
		$rule_types = self::get_rule_type_classes();
	
		foreach( $rule_types as $type => $class ) {
			if( empty( $rules[ $type ]) ) {
				$rules[ $type ] = new $rule_types[ $type ]();
			}
		}
	
		return apply_filters( 'ms_model_rule_rule_set_factory', $rules );
	}
	
	public static function is_valid_rule_type( $rule_type ) {
		return apply_filters( 'ms_model_rule_is_valid_rule_type', array_key_exists( $rule_type, self::get_rule_type_classes() ) );
	}
	
	/**
	 * Set initial protection.
	 * To be implemented by children classes.
	 * 
	 * @since 1.0
	 */
	public function protect_content( $ms_relationship = false ) {
		return false;
	}
	
	/**
	 * Verify if this model has rules set.
	 * 
	 * @since 4.0.0
	 * 
	 * @return boolean True if it has rules, false otherwise.
	 */
	public function has_rules() {
		$has_rules = ! empty( $this->rule_value ) ;
		return apply_filters( 'ms_model_rule_has_rules', $has_rules, $this->rule_value );	
	}
	
	public function count_rules() {
		$count = count( $this->rule_value ) ;
		return apply_filters( 'ms_model_rule_count_rules', $count, $this->rule_value );
	}
	
	/**
	 * Verify if this model has rule for a content.
	 * 
	 * @since 4.0.0
	 * 
	 * @param $id The content id to verify rules.
	 * @return boolean True if it has rules, false otherwise.
	 */
	public function has_rule( $id ) {
		$has_rule = isset( $this->rule_value[ $id ] ) ;
		return apply_filters( 'ms_model_rule_has_rule', $has_rule, $id, $this->rule_value );	
	}
	
	/**
	 * Verify access to the current asset.
	 * 
	 * @since 4.0.0
	 * 
	 * @param $id The item id to verify access.
	 * @return boolean True if has access, false otherwise.
	 */
	public function has_access( $id = null ) {
		$has_access = false;
		
		if( ! empty( $id ) ) {
			if( isset( $this->rule_value[ $id ] ) ) {
				$has_access = $this->rule_value[ $id ];
			}
			else {
				$has_access = $this->rule_value_default;
			}
		}
		
		if( $this->rule_value_invert ) {
			$has_access = ! $has_access;
		}
		
		return apply_filters( 'ms_model_rule_has_access', $has_access, $id );
	}
	
	/**
	 * Verify if has dripped rules.
	 * @return boolean
	 */
	public function has_dripped_rules() {
		return ! empty( $this->dripped );
	}
		
	/**
	 * Verify access to dripped content.
	 * @param $id The content id to verify dripped acccess. 
	 * @param $start_date The start date of the member membership.
	 */
	public function has_dripped_access( $start_date, $id ) {
		if( array_key_exists( $id, $this->dripped ) ) {
			$dripped = MS_Helper_Period::add_interval( $this->dripped[ $id ]['period_unit'],  $this->dripped[ $id ]['period_type'], $start_date );
			$now = MS_Helper_Period::current_date();
			if( strtotime( $now ) >= strtotime( $dripped ) ) {
				return true;
			}
			return false;
		}
		
	}
	
	public function count_item_access( $args = null ) {
		if( $this->rule_value_invert ) {
			$args['default'] = 1;
		}
		
		$total = $this->get_content_count( $args );
		$count_accessible = 0;
		$count_restricted = 0;
		if( ! is_array( $this->rule_value ) ) {
			$this->rule_value = array();
		}
		foreach( $this->rule_value as $id => $value ) {
			if( $value ) {
				$count_accessible++;
			}
			else {
				$count_restricted++;
			}
		}
		
		if( $this->rule_value_default ) {
			$count_accessible = $total - $count_restricted;
		}
		else {
			$count_restricted = $total - $count_accessible;
		}
		$count = array( 
				'total' => $total, 
				'accessible' => $count_accessible,
				'restricted' => $count_restricted,
		);
		
		return apply_filters( 'ms_model_rule_count_item_access', $count );
	}
	
	/**
	 * Get content of this rule domain.
	 * @todo Specify a return content interface
	 * @throws Exception
	 */
	public function get_contents( $args = null ) {
		throw new Exception ("Method to be implemented in child class");
	}
	
	public function get_content( $id ) {
		$content = null;
		$contents = $this->get_contents();
		if( ! empty( $contents[ $id ]->name ) ) {
			$content = $contents[ $id ]->name;
		}
		elseif( ! empty( $contents[ $id ]->post_name ) ) {
			$content = $contents[ $id ]->post_name;
				
		}
		elseif( ! empty( $contents[ $id ]->title ) ) {
			$content = $contents[ $id ]->title;
		}
		elseif( ! empty( $contents[ $id ] ) ) {
			$content = $contents[ $id ];
		}
		else {
			$content = $id;
		}
		
		return apply_filters( 'ms_model_rule_get_content', $content, $id );
	}
	public function get_content_count( $args = null ) {
		return 0;
	}
	
	public function reset_rule_values() {
		$this->rule_value = array();
	}
	
	public function merge_rule_values( $src_rule ) {
		
		$rule_value = $this->rule_value;
		if( ! is_array( $this->rule_value ) ) {
			$rule_value = array();
		}
		$src_rule_value = $src_rule->rule_value;
		if( ! is_array( $src_rule->rule_value ) ) {
			$src_rule_value = array();
		}
		
		/** first intersect to preserve only protected rules overrides and after that, merge preserving keys */
		$this->rule_value = array_intersect_key( $rule_value,  $src_rule_value) + $src_rule_value;
	}
	
	public function set_access( $id, $has_access ) {
		$has_access = $this->validate_bool( $has_access );
		
		$this->rule_value[ $id ] = $has_access;
		if( $this->rule_value_invert ) {
			if( $this->rule_value[ $id ] ) {
				unset( $this->rule_value[ $id ] );
			}
		}
	}
	public function give_access( $id ) {
		$this->set_access( $id, true );
	}
	
	public function remove_access( $id ) {
		$this->set_access( $id, false );
	}
	
	public function toggle_access( $id ) {
		if( isset( $this->rule_value[ $id ] ) ) {
			$has_access = ! $this->rule_value[ $id ];
		}
		else {
			$has_access = ! $this->rule_value_default;
		}
		$this->set_access( $id, $has_access );
	}
	
	public function get_query_args( $args = null ) {
	
		$defaults = array(
				'posts_per_page' => -1,
				'offset'      => 0,
				'orderby'     => 'ID',
				'order'       => 'DESC',
				'post_status' => 'publish',
		);
		$args = wp_parse_args( $args, $defaults );
	
		/** If not visitor membership, just show protected content */
		if( ! $this->rule_value_invert ) {
			$args['post__in'] = array_keys( $this->rule_value );
		}
			
		/** Cannot use post__in and post_not_in at the same time.*/
		if( ! empty( $args['post__in'] ) && ! empty( $args['post__not_in'] ) ) {
			$include = $args['post__in'];
			$exclude = $args['post__not_in'];
			foreach( $exclude as $id ) {
				$key = array_search( $id, $include );
				unset( $include[ $key ] );
			}
			unset( $args['post__not_in'] );
		}
	
		return apply_filters( "ms_model_rule_{$this->id}_get_query_args", $args );
	}
	
	public function filter_content( $status, $contents ) {
		foreach( $contents as $key => $content ) {
			if( !empty( $content->ignore ) ) {
				continue;
			}
			switch( $status ) {
				case self::FILTER_HAS_ACCESS:
					if( ! $content->access ) {
						unset( $contents[ $key ] );
					}
					break;
				case self::FILTER_NO_ACCESS:
					if( $content->access ) {
						unset( $contents[ $key ] );
					}
					break;
				case self::FILTER_DRIPPED:
					if( empty( $content->delayed_period ) ) {
						unset( $contents[ $key ] );
					}
					break;
			}
		}
		return $contents;
	}
	
	/**
	 * Returns property associated with the render.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;
		switch( $property ) {
			case 'rule_value':
				if( ! is_array( $this->rule_value ) ) {
					$this->rule_value = array();
				}
				$value = $this->rule_value;
				break;
			default:
				if( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}
	
		return apply_filters( 'ms_model_rule__get', $value, $property );
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
				case 'rule_type':
					if( in_array( $value, self::get_rule_types() ) ) {
						$this->$property = $value;
					}
					break;
				case 'dripped':
					if( is_array( $value ) ) {
						foreach( $value as $key => $period ) {
							$value[ $key ] = $this->validate_period( $period );
						}
						$this->$property = $value;
					}
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}