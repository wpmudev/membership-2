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

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {
	}

	/**
	 * Activates the Add-on logic, only executed when add-on is active.
	 *
	 * @since  1.1.0
	 */
	public function activate() {
		$this->add_filter( 'ms_rule_get_rule_types', 'buddypress_rule_types' );
		$this->add_filter( 'ms_rule_get_rule_type_classes', 'buddypress_rule_type_classes' );
		$this->add_filter( 'ms_rule_get_rule_type_titles', 'buddypress_rule_type_titles' );
		$this->add_filter( 'ms_controller_membership_tabs', 'buddypress_rule_tabs' );
		$this->add_filter( 'ms_view_protectedcontent_define-' . self::RULE_ID, 'buddypress_manage_render_callback', 10, 3 );
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'BuddyPress Integration', MS_TEXT_DOMAIN ),
			'description' => __( 'Enable BuddyPress rules integration.', MS_TEXT_DOMAIN ),
		);

		return $list;
	}

	/**
	 * Add buddypress rule types.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_rule_get_rule_types
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
	 * @filter ms_rule_get_rule_type_classes
	 *
	 * @param array $rules The current rule classes.
	 * @return array The filtered rule classes.
	 */
	public function buddypress_rule_type_classes( $rules ) {
		$rules[ self::RULE_ID  ] = 'MS_Addon_Buddypress_Model_Rule';

		/** @todo integrate it better in 4.1
		$rules[ self::RULE_ID_BLOG  ] = 'MS_Addon_Buddypress_Rule_Blog';
		$rules[ self::RULE_ID_GROUP  ] = 'MS_Addon_Buddypress_Rule_Group';
		*/

		return $rules;
	}

	/**
	 * Add buddypress rule type titles.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_rule_get_rule_type_titles
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
	 * @filter ms_view_protectedcontent_define-buddypress
	 *
	 * @param array $callback The current function callback.
	 * @param array $data The data collection.
	 * @param MS_View_Membership_ProtectedContent $obj The protected-content view object.
	 * @return array The filtered callback.
	 */
	public function buddypress_manage_render_callback( $callback, $data, $obj ) {
		$view = MS_Factory::load( 'MS_Addon_Buddypress_View_General' );

		$view->data = apply_filters(
			'ms_addon_buddypress_view_settings_edit_data',
			$data
		);
		$callback = array( $view, 'render_rule_tab' );

		return $callback;
	}
}