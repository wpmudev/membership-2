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


class MS_Addon_Bbpress extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.1.0
	 */
	const ID = 'bbpress';

	const RULE_ID = 'bbpress';

	const CPT_BB_FORUM = 'forum';
	const CPT_BB_TOPIC = 'topic';
	const CPT_BB_REPLY = 'reply';

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {
		// Always remove bbpress from MS_Model_Rule_Custom_Post_Type_Group.
		$this->add_filter( 'ms_model_rule_custom_post_type_group_get_excluded_content', 'exclude_bbpress_cpts' );
	}

	/**
	 * Activates the Add-on logic, only executed when add-on is active.
	 *
	 * @since  1.1.0
	 */
	public function activate() {
		$this->add_filter( 'ms_model_rule_get_rule_types', 'bbpress_rule_types' );
		$this->add_filter( 'ms_model_rule_get_rule_type_classes', 'bbpress_rule_type_classes' );
		$this->add_filter( 'ms_model_rule_get_rule_type_titles', 'bbpress_rule_type_titles' );
		$this->add_filter( 'ms_controller_membership_tabs', 'bbpress_rule_tabs' );
		$this->add_filter( 'ms_view_membership_protected_content_render_tab_callback', 'bbpress_manage_render_callback', 10, 3 );
		$this->add_filter( 'ms_view_membership_accessible_content_render_tab_callback', 'bbpress_manage_render_callback', 10, 3 );
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
			'name' => __( 'bbPress Integration', MS_TEXT_DOMAIN ),
			'description' => __( 'Enable bbPress rules integration.', MS_TEXT_DOMAIN ),
		);

		return $list;
	}

	/**
	 * Add bbpress rule types.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_model_rule_get_rule_types
	 *
	 * @param array $rules The current rule types.
	 * @return array The filtered rule types.
	 */
	public function bbpress_rule_types( $rules ) {
		$rules[] = self::RULE_ID;

		return $rules;
	}

	/**
	 * Add bbpress rule classes.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_model_rule_get_rule_type_classes
	 *
	 * @param array $rules The current rule classes.
	 * @return array The filtered rule classes.
	 */
	public function bbpress_rule_type_classes( $rules ) {
		$rules[ self::RULE_ID  ] = 'MS_Addon_Bbpress_Model_Rule';

		return $rules;
	}

	/**
	 * Add bbpress rule type titles.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_model_rule_get_rule_type_titles
	 *
	 * @param array $rules The current rule type titles.
	 * @return array The filtered rule type titles.
	 */
	public function bbpress_rule_type_titles( $rules ) {
		$rules[ self::RULE_ID  ] = __( 'bbPress' , MS_TEXT_DOMAIN );

		return $rules;
	}

	/**
	 * Add bbpress rule tabs in membership level edit.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function bbpress_rule_tabs( $tabs ) {
		$rule = self::RULE_ID;
		$tabs[ $rule  ]['title'] = __( 'bbPress', MS_TEXT_DOMAIN );

		return $tabs;
	}

	/**
	 * Add bbpress views callback.
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
	public function bbpress_manage_render_callback( $callback, $tab, $obj ) {
		if ( self::RULE_ID == $tab ) {
			$view = MS_Factory::load( 'MS_Addon_Bbpress_View_General' );

			$data = $obj->data;
			$view->data = apply_filters(
				'ms_addon_bbpress_view_general_edit_data',
				$data
			);
			$callback = array( $view, 'render_rule_tab' );
		}

		return $callback;
	}

	/**
	 * Exclude BBPress custom post type from MS_Model_Rule_Custom_Post_Type_Group.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_model_rule_custom_post_type_group_get_excluded_content
	 *
	 * @param array $excluded The current excluded ctps.
	 * @return array The filtered excluded ctps.
	 */
	public function exclude_bbpress_cpts( $excluded ) {
		$excluded = array_merge( $excluded, self::get_bb_custom_post_types() );

		return apply_filters(
			'ms_addon_bbpress_exclude_bbpress_cpts',
			$excluded
		);
	}

	/**
	 * Get BBPress custom post types.
	 *
	 * @since 1.0.0
	 *
	 * @return array The bbpress custom post types.
	 */
	public static function get_bb_custom_post_types() {
		return apply_filters(
			'ms_addon_bbpress_get_bb_custom_post_types',
			array(
				MS_Addon_Bbpress::CPT_BB_FORUM,
				MS_Addon_Bbpress::CPT_BB_TOPIC,
				MS_Addon_Bbpress::CPT_BB_REPLY,
			)
		);
	}

}