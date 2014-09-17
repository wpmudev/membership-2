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


class MS_Integration_Bbpress extends MS_Integration {
	
	protected static $CLASS_NAME = __CLASS__;
	
	const ADDON_BBPRESS = 'bbpress';
	
	const RULE_TYPE_BBPRESS = 'bbpress';
	
	const CPT_BB_FORUM = 'forum';
	const CPT_BB_TOPIC = 'topic';
	const CPT_BB_REPLY = 'reply';
	
	/**
	 * Add filters for bbpress integration.
	 * 
	 * @since 4.0.0
	 */
	public function __construct() {
		parent::__construct();
		
		$this->add_filter( 'ms_model_addon_get_addon_types', 'bbpress_addon' );
		$this->add_filter( 'ms_model_addon_get_addon_list', 'bbpress_addon_list' );
		
		/** Always remove bbpress from MS_Model_Rule_Custom_Post_Type_Group. */
		$this->add_filter( 'ms_model_rule_custom_post_type_group_get_excluded_content', 'exclude_bbpress_cpts' );
		if( MS_Model_Addon::is_enabled( self::ADDON_BBPRESS ) ) {
			$this->add_filter( 'ms_model_rule_get_rule_types', 'bbpress_rule_types' );
			$this->add_filter( 'ms_model_rule_get_rule_type_classes', 'bbpress_rule_type_classes' );
			$this->add_filter( 'ms_model_rule_get_rule_type_titles', 'bbpress_rule_type_titles' );
			$this->add_filter( 'ms_controller_membership_tabs', 'bbpress_rule_tabs' );
			$this->add_filter( 'ms_view_membership_setup_protected_content_render_tab_callback', 'bbpress_manage_render_callback', 10, 3 );
			$this->add_filter( 'ms_view_membership_accessible_content_render_tab_callback', 'bbpress_manage_render_callback', 10, 3 );
		}
	}

	/**
	 * Add bbpress add-on type.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_model_addon_get_addon_types
	 * 
	 * @param array $addons The current add-ons.
	 * @return string
	 */
	public function bbpress_addon( $addons ) {
		$addons[] = self::ADDON_BBPRESS;
		return $addons;
	}
	
	/**
	 * Add bbpress add-on info.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_model_addon_get_addon_list
	 * 
	 * @param array $list The current list of add-ons.
	 * @return array The filtered add-on list.
	 */
	public function bbpress_addon_list( $list ) {
		$list[ self::ADDON_BBPRESS ] = (object) array(
				'id' => self::ADDON_BBPRESS,
				'name' => __( 'bbPress Integration', MS_TEXT_DOMAIN ),
				'description' => __( 'Enable bbPress rules integration.', MS_TEXT_DOMAIN ),
				'active' => MS_Model_Addon::is_enabled( self::ADDON_BBPRESS ),
		);
	
		return $list;
	}
	
	/**
	 * Add bbpress rule types.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_model_rule_get_rule_types
	 * 
	 * @param array $rules The current rule types.
	 * @return array The filtered rule types.
	 */
	public function bbpress_rule_types( $rules ) {
		
		$rules[] = self::RULE_TYPE_BBPRESS;
		
		return $rules;
	}
	
	/**
	 * Add bbpress rule classes.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_model_rule_get_rule_type_classes
	 * 
	 * @param array $rules The current rule classes.
	 * @return array The filtered rule classes.
	 */
	public function bbpress_rule_type_classes( $rules ) {
	
		$rules[ self::RULE_TYPE_BBPRESS  ] = 'MS_Model_Rule_Bbpress';
	
		return $rules;
	}
	
	/**
	 * Add bbpress rule type titles.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_model_rule_get_rule_type_titles
	 * 
	 * @param array $rules The current rule type titles.
	 * @return array The filtered rule type titles.
	 */
	public function bbpress_rule_type_titles( $rules ) {
	
		$rules[ self::RULE_TYPE_BBPRESS  ] = __( 'BBPress' , MS_TEXT_DOMAIN );
	
		return $rules;
	}
	
	/**
	 * Add bbpress rule tabs in membership level edit.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_controller_membership_get_tabs
	 * 
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit 
	 * @return array The filtered tabs.
	 */
	public function bbpress_rule_tabs( $tabs ) {
		$rule = self::RULE_TYPE_BBPRESS;
		$tabs[ $rule  ]['title'] = __( 'bbPress', MS_TEXT_DOMAIN );
				
		return $tabs;
	}
	
	/**
	 * Add bbpress views callback.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_view_membership_edit_render_callback
	 * 
	 * @param array $callback The current function callback.
	 * @param string $tab The current membership rule tab.
	 * @param array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function bbpress_manage_render_callback( $callback, $tab, $data ) {
		if( self::RULE_TYPE_BBPRESS == $tab ) {
			$view = new MS_View_Bbpress_General();
			$view->data = $data;
			$callback = array( $view, 'render_rule_tab' );
				
		}
		return $callback;
	}
	
	/**
	 * Exclude BBPress custom post type from MS_Model_Rule_Custom_Post_Type_Group.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_model_rule_custom_post_type_group_get_excluded_content
	 * 
	 * @param array $excluded The current excluded ctps.
	 * @return array The filtered excluded ctps.
	 */
	public function exclude_bbpress_cpts( $excluded ) {
		
		$excluded = array_merge( $excluded, self::get_bb_custom_post_types() );
		
		return apply_filters( 'ms_integration_bbpress_exclude_bbpress_cpts', $excluded );
	}
	
	/**
	 * Get BBPress custom post types.
	 *
	 * @since 4.0.0
	 *
	 * @return array The bbpress custom post types.
	 */
	public static function get_bb_custom_post_types() {
		return apply_filters( 'ms_integration_bbpress_get_bb_custom_post_types', array(
				MS_Integration_Bbpress::CPT_BB_FORUM,
				MS_Integration_Bbpress::CPT_BB_TOPIC,
				MS_Integration_Bbpress::CPT_BB_REPLY,
		) );
	}
	
}