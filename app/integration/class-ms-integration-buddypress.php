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


class MS_Integration_BuddyPress extends MS_Integration {
	
	protected static $CLASS_NAME = __CLASS__;
	
	const ADDON_BUDDYPRESS = 'buddypress';
	
	const RULE_TYPE_BUDDYPRESS_BLOG = 'buddypress_blog';
	const RULE_TYPE_BUDDYPRESS_FRIENDSHIP = 'buddypress_friendship';
	const RULE_TYPE_BUDDYPRESS_GROUP = 'buddypress_group';
	const RULE_TYPE_BUDDYPRESS_GROUP_CREATION = 'buddypress_group_creation';
	const RULE_TYPE_BUDDYPRESS_PRIVATE_MSG = 'buddypress_private_msg';
	
	public function __construct() {
		parent::__construct();
		
// 		$this->add_filter( 'ms_model_addon_get_addon_types', 'buddypress_addon' );
		$this->add_filter( 'ms_model_addon_get_addon_list', 'buddypress_addon_list' );
		
		if( MS_Model_Addon::is_enabled( self::ADDON_BUDDYPRESS ) ) {
			$this->add_filter( 'ms_model_rule_get_rule_types', 'buddypress_rule_types' );
			$this->add_filter( 'ms_model_rule_get_rule_type_classes', 'buddypress_rule_type_classes' );
			$this->add_filter( 'ms_model_rule_get_rule_type_titles', 'buddypress_rule_type_titles' );
			$this->add_filter( 'ms_controller_membership_get_tabs', 'buddypress_rule_tabs', 10, 2 );
			$this->add_filter( 'ms_view_membership_edit_render_callback', 'buddypress_manage_render_callback', 10, 3 );
		}
	}

	public function buddypress_addon( $addons ) {
		$addons[] = self::ADDON_BUDDYPRESS;
		return $addons;
	}
	
	public function buddypress_addon_list( $list ) {
		$list[ self::ADDON_BUDDYPRESS ] = (object) array(
				'id' => self::ADDON_BUDDYPRESS,
				'name' => __( 'Buddypress Integration', MS_TEXT_DOMAIN ),
				'description' => __( 'Enable buddypress rules integration.', MS_TEXT_DOMAIN ),
				'active' => MS_Model_Addon::is_enabled( self::ADDON_BUDDYPRESS ),
		);
	
		return $list;
	}
	public function buddypress_rule_types( $rules ) {
		
		$rules[] = self::RULE_TYPE_BUDDYPRESS_BLOG;
		$rules[] = self::RULE_TYPE_BUDDYPRESS_FRIENDSHIP;
		$rules[] = self::RULE_TYPE_BUDDYPRESS_GROUP;
		$rules[] = self::RULE_TYPE_BUDDYPRESS_GROUP_CREATION;
		$rules[] = self::RULE_TYPE_BUDDYPRESS_PRIVATE_MSG;
		
		return $rules;
	}
	
	public function buddypress_rule_type_classes( $rules ) {
	
		$rules[ self::RULE_TYPE_BUDDYPRESS_BLOG  ] = 'MS_Model_Rule_Buddypress_Blog';
		$rules[ self::RULE_TYPE_BUDDYPRESS_FRIENDSHIP  ] = 'MS_Model_Rule_Buddypress_Friendship';
		$rules[ self::RULE_TYPE_BUDDYPRESS_GROUP  ] = 'MS_Model_Rule_Buddypress_Group';
		$rules[ self::RULE_TYPE_BUDDYPRESS_GROUP_CREATION  ] = 'MS_Model_Rule_Buddypress_Group_Creation';
		$rules[ self::RULE_TYPE_BUDDYPRESS_PRIVATE_MSG  ] = 'MS_Model_Rule_Buddypress_Private_Msg';
	
		return $rules;
	}
	
	public function buddypress_rule_type_titles( $rules ) {
	
		$rules[ self::RULE_TYPE_BUDDYPRESS_BLOG  ] = __( 'Buddypress blog' , MS_TEXT_DOMAIN );
		$rules[ self::RULE_TYPE_BUDDYPRESS_FRIENDSHIP  ] = __( 'Buddypress friend' , MS_TEXT_DOMAIN );
		$rules[ self::RULE_TYPE_BUDDYPRESS_GROUP  ] = __( 'Buddypress group' , MS_TEXT_DOMAIN );
		$rules[ self::RULE_TYPE_BUDDYPRESS_GROUP_CREATION  ] = __( 'Buddypress group creation' , MS_TEXT_DOMAIN );
		$rules[ self::RULE_TYPE_BUDDYPRESS_PRIVATE_MSG  ] = __( 'Buddypress private msg' , MS_TEXT_DOMAIN );
	
		return $rules;
	}
	
	public function buddypress_rule_tabs( $tabs, $membership_id ) {

		$rule = self::RULE_TYPE_BUDDYPRESS_BLOG;
		$tabs[ $rule  ] = array(
				'title' => __( 'Buddypress blog', MS_TEXT_DOMAIN ),
				'url' => "admin.php?page=membership-edit&tab={$rule}&membership_id={$membership_id}",
		);
		$rule = self::RULE_TYPE_BUDDYPRESS_FRIENDSHIP;
		$tabs[ $rule  ] = array(
				'title' => __( 'Buddypress friends', MS_TEXT_DOMAIN ),
				'url' => "admin.php?page=membership-edit&tab={$rule}&membership_id={$membership_id}",
		);
		$rule = self::RULE_TYPE_BUDDYPRESS_GROUP;
		$tabs[ $rule  ] = array(
				'title' => __( 'Buddypress groups', MS_TEXT_DOMAIN ),
				'url' => "admin.php?page=membership-edit&tab={$rule}&membership_id={$membership_id}",
		);
		$rule = self::RULE_TYPE_BUDDYPRESS_GROUP_CREATION;
		$tabs[ $rule  ] = array(
				'title' => __( 'Buddypress group creation', MS_TEXT_DOMAIN ),
				'url' => "admin.php?page=membership-edit&tab={$rule}&membership_id={$membership_id}",
		);
		$rule = self::RULE_TYPE_BUDDYPRESS_PRIVATE_MSG;
		$tabs[ $rule  ] = array(
				'title' => __( 'Buddypress private msg', MS_TEXT_DOMAIN ),
				'url' => "admin.php?page=membership-edit&tab={$rule}&membership_id={$membership_id}",
		);
		
		return $tabs;
	}
	
	public function buddypress_manage_render_callback( $callback, $tab, $data ) {
		if( in_array( $tab, $this->buddypress_rule_types( array() ) ) ) {
			switch( $tab ) {
				default:
				case self::RULE_TYPE_BUDDYPRESS_BLOG:
					$view = new MS_View_Buddypress_Blog();
					$view->data = $data;
					$callback = array( $view, 'render_rule_tab' ); 
					break;
			}
		}
		return $callback;
	}
}