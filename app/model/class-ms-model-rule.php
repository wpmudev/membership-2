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
		
	/**
	 * Create rule with default rule_value (has access). 
	 */
	public function __construct() {
		$contents = $this->get_content();
		if( ! empty( $contents ) ) {
			foreach( $contents as $content ) {
				$this->rule_value[ $content->id ] = $content->id;
			}
		}
	}
	/**
	 * Set initial protection.
	 */
	public function protect_content() {
		return false;
	}
	/**
	 * Verify access to the current asset.
	 * @return boolean
	 */
	public function has_access() {
		return false;
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
	
	/**
	 * Add single rule value.
	 * @param int $content_id The content id to add.
	 */
	public function add_rule_value( $content_id ) {
		$this->rule_value[ $content_id ] = $content_id;
	}
	
	/**
	 * Remove single rule value.
	 * @param int $content_id The content id to remove.
	 */
	public function remove_rule_value( $content_id ) {
		unset( $this->rule_value[ $content_id ] );
	}
	
	/**
	 * Rule types.
	 *
	 * This array is ordered in the hierarchy way.
	 * First one has more priority than the last one.
	 * This hierarchy is used to determine access to protected content.
	 */
	public static function get_rule_types() {
		return apply_filters( 'ms_model_rule_get_rule_types', array(
				self::RULE_TYPE_POST,
				self::RULE_TYPE_CATEGORY,
				self::RULE_TYPE_CUSTOM_POST_TYPE,
				self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
				self::RULE_TYPE_PAGE,
				self::RULE_TYPE_MORE_TAG,
				self::RULE_TYPE_MENU,
				self::RULE_TYPE_SHORTCODE,
				self::RULE_TYPE_COMMENT,
				self::RULE_TYPE_MEDIA,
				self::RULE_TYPE_URL_GROUP,
			)
		);
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
				self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP => __( 'Custom Post Type Group', MS_TEXT_DOMAIN ),
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
	 * Get content of this rule domain.
	 * @todo Specify a return content interface
	 * @throws Exception
	 */
	public function get_content( $args = null ) {
		throw new Exception ("Method to be implemented in child class");
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