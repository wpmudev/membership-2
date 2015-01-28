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

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {
		// Always remove bbpress from MS_Rule_CptGroup_Model.
		$this->add_filter(
			'ms_rule_cptgroup_model_get_excluded_content',
			'exclude_bbpress_cpts'
		);

		if ( self::is_active() ) {
			$this->add_filter( 'ms_controller_membership_tabs', 'rule_tabs' );
			MS_Factory::load( 'MS_Addon_Bbpress_Rule' );
		}
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
	public function rule_tabs( $tabs ) {
		$rule = MS_Addon_Bbpress_Rule::RULE_ID;
		$tabs[ $rule  ] = true;

		return $tabs;
	}

	/**
	 * Exclude BBPress custom post type from MS_Rule_CptGroup_Model.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_rule_cptgroup_model_get_excluded_content
	 *
	 * @param array $excluded The current excluded ctps.
	 * @return array The filtered excluded ctps.
	 */
	public function exclude_bbpress_cpts( $excluded ) {
		$excluded = array_merge(
			$excluded,
			MS_Addon_Bbpress_Rule_Model::get_bb_cpt()
		);

		return $excluded;
	}

}