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

	const RULE_TYPE_BUDDYPRESS = 'buddypress';
	const RULE_TYPE_BUDDYPRESS_BLOG = 'buddypress_blog';
	const RULE_TYPE_BUDDYPRESS_FRIENDSHIP = 'buddypress_friendship';
	const RULE_TYPE_BUDDYPRESS_GROUP = 'buddypress_group';
	const RULE_TYPE_BUDDYPRESS_GROUP_CREATION = 'buddypress_group_creation';
	const RULE_TYPE_BUDDYPRESS_PRIVATE_MSG = 'buddypress_private_msg';

	/**
	 * Add filters for buddypress integration.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_filter( 'ms_model_addon_get_addon_types', 'buddypress_addon' );
		$this->add_filter( 'ms_model_addon_get_addon_list', 'buddypress_addon_list' );

		if ( MS_Model_Addon::is_enabled( self::ADDON_BUDDYPRESS ) ) {
			$this->add_filter( 'ms_model_rule_get_rule_types', 'buddypress_rule_types' );
			$this->add_filter( 'ms_model_rule_get_rule_type_classes', 'buddypress_rule_type_classes' );
			$this->add_filter( 'ms_model_rule_get_rule_type_titles', 'buddypress_rule_type_titles' );
			$this->add_filter( 'ms_controller_membership_tabs', 'buddypress_rule_tabs' );
			$this->add_filter( 'ms_view_membership_setup_protected_content_render_tab_callback', 'buddypress_manage_render_callback', 10, 3 );
			$this->add_filter( 'ms_view_membership_accessible_content_render_tab_callback', 'buddypress_manage_render_callback', 10, 3 );
		}
	}

	/**
	 * Add buddypress add-on type.
	 *
	 * @since 4.0.0
	 *
	 * @filter ms_model_addon_get_addon_types
	 *
	 * @param array $addons The current add-ons.
	 * @return string
	 */
	public function buddypress_addon( $addons ) {
		$addons[] = self::ADDON_BUDDYPRESS;
		return $addons;
	}

	/**
	 * Add buddypress add-on info.
	 *
	 * @since 4.0.0
	 *
	 * @filter ms_model_addon_get_addon_list
	 *
	 * @param array $list The current list of add-ons.
	 * @return array The filtered add-on list.
	 */
	public function buddypress_addon_list( $list ) {
		$list[ self::ADDON_BUDDYPRESS ] = (object) array(
			'id' => self::ADDON_BUDDYPRESS,
			'name' => __( 'BuddyPress Integration', MS_TEXT_DOMAIN ),
			'description' => __( 'Enable BuddyPress rules integration.', MS_TEXT_DOMAIN ),
			'active' => MS_Model_Addon::is_enabled( self::ADDON_BUDDYPRESS ),
		);

		return $list;
	}

	/**
	 * Add buddypress rule types.
	 *
	 * @since 4.0.0
	 *
	 * @filter ms_model_rule_get_rule_types
	 *
	 * @param array $rules The current rule types.
	 * @return array The filtered rule types.
	 */
	public function buddypress_rule_types( $rules ) {
		$rules[] = self::RULE_TYPE_BUDDYPRESS;

		/** @todo integrate it better in 4.1
		$rules[] = self::RULE_TYPE_BUDDYPRESS_GROUP;

		array_unshift( $rules, self::RULE_TYPE_BUDDYPRESS_BLOG );
		*/
		return $rules;
	}

	/**
	 * Add buddypress rule classes.
	 *
	 * @since 4.0.0
	 *
	 * @filter ms_model_rule_get_rule_type_classes
	 *
	 * @param array $rules The current rule classes.
	 * @return array The filtered rule classes.
	 */
	public function buddypress_rule_type_classes( $rules ) {
		$rules[ self::RULE_TYPE_BUDDYPRESS  ] = 'MS_Model_Rule_Buddypress';

		/** @todo integrate it better in 4.1
		$rules[ self::RULE_TYPE_BUDDYPRESS_BLOG  ] = 'MS_Model_Rule_Buddypress_Blog';
		$rules[ self::RULE_TYPE_BUDDYPRESS_GROUP  ] = 'MS_Model_Rule_Buddypress_Group';
		*/

		return $rules;
	}

	/**
	 * Add buddypress rule type titles.
	 *
	 * @since 4.0.0
	 *
	 * @filter ms_model_rule_get_rule_type_titles
	 *
	 * @param array $rules The current rule type titles.
	 * @return array The filtered rule type titles.
	 */
	public function buddypress_rule_type_titles( $rules ) {
		$rules[ self::RULE_TYPE_BUDDYPRESS  ] = __( 'BuddyPress' , MS_TEXT_DOMAIN );

		/** @todo integrate it better in 4.1
		$rules[ self::RULE_TYPE_BUDDYPRESS_BLOG  ] = __( 'BuddyPress blog' , MS_TEXT_DOMAIN );
		$rules[ self::RULE_TYPE_BUDDYPRESS_GROUP  ] = __( 'BuddyPress group' , MS_TEXT_DOMAIN );
		*/

		return $rules;
	}

	/**
	 * Add buddypress rule tabs in membership level edit.
	 *
	 * @since 4.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function buddypress_rule_tabs( $tabs ) {
		$rule = self::RULE_TYPE_BUDDYPRESS;
		$tabs[ $rule  ]['title']  = __( 'BuddyPress', MS_TEXT_DOMAIN );

		/** @todo integrate it better in 4.1
		$rule = self::RULE_TYPE_BUDDYPRESS_BLOG;
		$tabs[ $rule  ] = array(
			'title' => __( 'BuddyPress blog', MS_TEXT_DOMAIN ),
		);
		$rule = self::RULE_TYPE_BUDDYPRESS_GROUP;
		$tabs[ $rule  ] = array(
			'title' => __( 'BuddyPress groups', MS_TEXT_DOMAIN ),
		);
		*/

		return $tabs;
	}

	/**
	 * Add buddypress views callback.
	 *
	 * @since 4.0.0
	 *
	 * @filter ms_view_membership_setup_protected_content_render_tab_callback
	 * @filter ms_view_membership_accessible_content_render_tab_callback
	 *
	 * @param array $callback The current function callback.
	 * @param string $tab The current membership rule tab.
	 * @param MS_View_Membership_Setup_Protected_Content $obj The protected-content view object.
	 * @return array The filtered callback.
	 */
	public function buddypress_manage_render_callback( $callback, $tab, $obj ) {
		if ( in_array( $tab, $this->buddypress_rule_types( array() ) ) ) {
			$view = null;

			switch ( $tab ) {
				default:
				case self::RULE_TYPE_BUDDYPRESS_BLOG:
					$view = new MS_View_Buddypress_Blog();
					break;

				case self::RULE_TYPE_BUDDYPRESS_GROUP:
					$view = new MS_View_Buddypress_Group();
					break;

				case self::RULE_TYPE_BUDDYPRESS:
					$view = new MS_View_Buddypress_General();
					break;
			}

			$data = $obj->data;
			$view->data = apply_filters( 'ms_view_buddypress_settings_edit_data', $data );
			$callback = array( $view, 'render_rule_tab' );

		}
		return $callback;
	}
}