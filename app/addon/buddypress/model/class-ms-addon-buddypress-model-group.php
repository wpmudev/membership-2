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


class MS_Addon_Buddypress_Model_Rulegroup extends MS_Model_Rule {

	protected $rule_type = MS_Addon_Buddypress::RULE_ID_GROUP;

	/**
	 * Verify access to the current page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access() {
		$has_access = null;
		$group_id = false;

		if ( function_exists( 'bp_is_current_component' )
			&& bp_is_current_component( 'groups' )
		) {
			$group_id = bp_get_current_group_id();
			$has_access = in_array( $group_id, $this->rule_value );
		}

		return apply_filters(
			'ms_model_rule_buddypress_group_has_access',
			$has_access,
			$group_id
		);
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 *
	 * @param optional $membership_relationship The membership relationship info.
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->add_filter( 'groups_get_groups', 'protect_groups' );
		$this->add_filter( 'bp_activity_get', 'protect_activities' );
	}

	/**
	 * Protect BP groups from showing.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $groups The available groups.
	 * @return mixed The filtered groups.
	 */
	public function protect_groups( $groups ) {
		foreach ( $groups['groups'] as $key => $group ) {
			if ( ! in_array( $group->id, $this->rule_value ) ) {
				unset( $groups['groups'][ $key ] );
				$groups['total']--;
			}
		}

		sort( $groups['groups'] );

		return apply_filters(
			'ms_model_rule_buddypress_group_protect_groups',
			$groups
		);
	}

	/**
	 * Protect activities.
	 *
	 * Filter activities of protected groups.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $activities The BP activities to filter.
	 * @return mixed The filtered BP activities.
	 */
	public function protect_activities( $activities ) {
		if ( ! empty( $activities['activities'] ) ) {
			foreach ( $activities['activities'] as $key => $activity ) {
				if ( 'groups' === $activity->component
					&& ! in_array( $activity->item_id, $this->rule_value )
				) {
					unset( $activities['activities'][ $key ] );
					$activities['total']--;
				}
			}
			// reset index keys
			$activities['activities'] = array_values( $activities['activities'] );
		}

		return apply_filters(
			'ms_model_rule_buddypress_blog_protect_activity',
			$activities
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Not used, but kept due to method override.
	 * @return array The content eligible to protect by this rule domain.
	 */
	public function get_contents( $args = null ) {
		$contents = array();

		if ( function_exists( 'groups_get_groups' ) ) {
			$groups = groups_get_groups( array( 'per_page' => 50 ) );

			if ( ! empty( $groups['groups'] ) ) {
				foreach ( $groups['groups'] as $group ) {
					$content = new StdClass();
					$content->id = $group->id;
					$content->name = $group->name;

					if ( in_array( $content->id, $this->rule_value ) ) {
						$content->access = true;
					} else {
						$content->access = false;
					}
					$contents[] = $content;
				}
			}
		}

		return apply_filters(
			'ms_model_rule_buddypress_blog_get_content',
			$contents
		);
	}
}