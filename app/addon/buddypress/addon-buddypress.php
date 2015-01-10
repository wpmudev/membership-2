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


class MS_Addon_BuddyPress extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.1.0
	 */
	const ID = 'buddypress';

	const RULE_ID = 'buddypress';
	const RULE_ID_BLOG = 'buddypress_blog';
	const RULE_ID_FRIENDSHIP = 'buddypress_friendship';
	const RULE_ID_GROUP = 'buddypress_group';
	const RULE_ID_GROUP_CREATION = 'buddypress_group_creation';
	const RULE_ID_PRIVATE_MSG = 'buddypress_private_msg';

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'id' => self::ID,
			'name' => __( 'BuddyPress Integration', MS_TEXT_DOMAIN ),
			'description' => __( 'Enable BuddyPress rules integration.', MS_TEXT_DOMAIN ),
			'active' => MS_Model_Addon::is_enabled( self::ID ),
		);

		return $list;
	}

	/**
	 * Activates the Add-on logic, only executed when add-on is active.
	 *
	 * @since  1.1.0
	 */
	public function activate() {
		$this->add_filter( 'ms_model_rule_get_rule_types', 'buddypress_rule_types' );
		$this->add_filter( 'ms_model_rule_get_rule_type_classes', 'buddypress_rule_type_classes' );
		$this->add_filter( 'ms_model_rule_get_rule_type_titles', 'buddypress_rule_type_titles' );
		$this->add_filter( 'ms_controller_membership_tabs', 'buddypress_rule_tabs' );
		$this->add_filter( 'ms_view_membership_protected_content_render_tab_callback', 'buddypress_manage_render_callback', 10, 3 );
		$this->add_filter( 'ms_view_membership_accessible_content_render_tab_callback', 'buddypress_manage_render_callback', 10, 3 );
	}

	/**
	 * Add buddypress rule types.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_model_rule_get_rule_types
	 *
	 * @param array $rules The current rule types.
	 * @return array The filtered rule types.
	 */
	public function buddypress_rule_types( $rules ) {
		$rules[] = self::RULE_ID;

		/** @todo integrate it better in 4.1
		$rules[] = self::RULE_ID_GROUP;

		array_unshift( $rules, self::RULE_ID_BLOG );
		*/
		return $rules;
	}

	/**
	 * Add buddypress rule classes.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_model_rule_get_rule_type_classes
	 *
	 * @param array $rules The current rule classes.
	 * @return array The filtered rule classes.
	 */
	public function buddypress_rule_type_classes( $rules ) {
		$rules[ self::RULE_ID  ] = 'MS_Model_Rule_Buddypress';

		/** @todo integrate it better in 4.1
		$rules[ self::RULE_ID_BLOG  ] = 'MS_Model_Rule_Buddypress_Blog';
		$rules[ self::RULE_ID_GROUP  ] = 'MS_Model_Rule_Buddypress_Group';
		*/

		return $rules;
	}

	/**
	 * Add buddypress rule type titles.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_model_rule_get_rule_type_titles
	 *
	 * @param array $rules The current rule type titles.
	 * @return array The filtered rule type titles.
	 */
	public function buddypress_rule_type_titles( $rules ) {
		$rules[ self::RULE_ID  ] = __( 'BuddyPress' , MS_TEXT_DOMAIN );

		/** @todo integrate it better in 4.1
		$rules[ self::RULE_ID_BLOG  ] = __( 'BuddyPress blog' , MS_TEXT_DOMAIN );
		$rules[ self::RULE_ID_GROUP  ] = __( 'BuddyPress group' , MS_TEXT_DOMAIN );
		*/

		return $rules;
	}

	/**
	 * Add buddypress rule tabs in membership level edit.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function buddypress_rule_tabs( $tabs ) {
		$rule = self::RULE_ID;
		$tabs[ $rule  ]['title']  = __( 'BuddyPress', MS_TEXT_DOMAIN );

		/** @todo integrate it better in 4.1
		$rule = self::RULE_ID_BLOG;
		$tabs[ $rule  ] = array(
			'title' => __( 'BuddyPress blog', MS_TEXT_DOMAIN ),
		);
		$rule = self::RULE_ID_GROUP;
		$tabs[ $rule  ] = array(
			'title' => __( 'BuddyPress groups', MS_TEXT_DOMAIN ),
		);
		*/

		return $tabs;
	}

	/**
	 * Add buddypress views callback.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_view_membership_protected_content_render_tab_callback
	 * @filter ms_view_membership_accessible_content_render_tab_callback
	 *
	 * @param array $callback The current function callback.
	 * @param string $tab The current membership rule tab.
	 * @param MS_View_Membership_Protected_Content $obj The protected-content view object.
	 * @return array The filtered callback.
	 */
	public function buddypress_manage_render_callback( $callback, $tab, $obj ) {
		if ( in_array( $tab, $this->buddypress_rule_types( array() ) ) ) {
			$view = null;

			switch ( $tab ) {
				default:
				case self::RULE_ID_BLOG:
					$view = new MS_Addon_Buddypress_View_Blog();
					break;

				case self::RULE_ID_GROUP:
					$view = new MS_Addon_Buddypress_View_Group();
					break;

				case self::RULE_ID:
					$view = new MS_Addon_Buddypress_View_General();
					break;
			}

			$data = $obj->data;
			$view->data = apply_filters( 'ms_addon_buddypress_view_settings_edit_data', $data );
			$callback = array( $view, 'render_rule_tab' );

		}
		return $callback;
	}
}