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
	
	const RULE_TYPE_DOWNLOAD = 'download';
	
	const RULE_TYPE_MEDIA = 'media';
	
	const RULE_TYPE_MENU = 'menu';
	
	const RULE_TYPE_PAGE = 'page';
	
	const RULE_TYPE_POST = 'post';
	
	const RULE_TYPE_SHORTCODE = 'shortcode';
	
	const RULE_TYPE_URL_GROUP = 'url_group';
	
	const FILTER_HAS_ACCESS = 'has_access';
	
	const FILTER_NO_ACCESS = 'no_access';
	
	const FILTER_DRIPPED = 'dripped';
	
	public static $RULE_TYPE_CLASSES = array (
			self::RULE_TYPE_CATEGORY => 'MS_Model_Rule_Category',
			self::RULE_TYPE_COMMENT => 'MS_Model_Rule_Comment',
			self::RULE_TYPE_DOWNLOAD => 'MS_Model_Rule_Download',
			self::RULE_TYPE_MEDIA => 'MS_Model_Rule_Media',
			self::RULE_TYPE_MENU => 'MS_Model_Rule_Menu',
			self::RULE_TYPE_PAGE => 'MS_Model_Rule_Page',
			self::RULE_TYPE_POST => 'MS_Model_Rule_Post',
			self::RULE_TYPE_SHORTCODE => 'MS_Model_Rule_Shortcode',
			self::RULE_TYPE_URL_GROUP => 'MS_Model_Rule_Url_Group',
	);
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
	
	public function can_view_current_page() {
		return true;
		throw new Exception ("Method to be implemented in child class");
	}

	public static function get_rule_type_titles() {
		return array(
				self::RULE_TYPE_CATEGORY => __( 'Category' , MS_TEXT_DOMAIN ),
				self::RULE_TYPE_COMMENT => __( 'Comment', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_DOWNLOAD => __( 'Donwload', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_MEDIA => __( 'Media', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_MENU => __( 'Menu', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_PAGE => __( 'Page', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_POST => __( 'Post', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_SHORTCODE => __( 'Shortcode', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_URL_GROUP => __( 'Url Group', MS_TEXT_DOMAIN ),
		);
	}
	
	public static function rule_factory( $rule_type ) {
		if( ! in_array( $rule_type, self::$RULE_TYPE_CLASSES ) ) {
			return new self::$RULE_TYPE_CLASSES[ $rule_type ]();
		}
		else {
			throw new Exception( "Rule factory - rule type not found: $rule_type"  );
		}
	}
	
	public function get_validation_rules() {
		return apply_filters( 'membeship_model_rule_validation_rules', array(
				'dripped' => array( 'function' => array( &$this, 'validate_period' ) ),
		) );
	}
}