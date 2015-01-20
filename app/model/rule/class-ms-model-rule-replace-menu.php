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

/**
 * Membership Replace-Menu Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.4.2
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Replace_Menu extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.4.2
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_REPLACE_MENUS;

	/**
	 * An array of all available menu items.
	 * @var array
	 */
	protected $menus = array();

	/**
	 * Mapping of menu_ids that should be replaced.
	 * @var array
	 */
	protected $replacements;

	/**
	 * Verify access to the current content.
	 *
	 * This rule will return NULL (not relevant), because the menus are
	 * protected via a wordpress hook instead of protecting the current page.
	 *
	 * @since 1.0.4.2
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id = null ) {
		return apply_filters(
			'ms_model_rule_replace_menu_has_access',
			null,
			$id,
			$this
		);
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.4.2
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. The membership relationship.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		/*
		 * Replace the "menu" attribute of the wp_nav_menu() call
		 */
		$this->add_filter( 'wp_nav_menu_args', 'replace_menus' );
	}

	/**
	 * Replace specific menus for certain members.
	 *
	 * Relevant Action Hooks:
	 * - wp_nav_menu_args
	 *
	 * @since 1.0.4.2
	 *
	 * @param mixed $args Attributes of the call to wp_nav_menu().
	 * @return mixed The updated attributes.
	 */
	public function replace_menus( $args ) {
		$id = $args['menu'];

		if ( ! is_numeric( $id ) ) {
			// Get the nav menu based on the theme_location
			$locations = get_nav_menu_locations();
			if ( $args['theme_location'] && isset( $locations[ $args['theme_location'] ] ) ) {
				$id = $locations[ $args['theme_location'] ];
			}
		}

		if ( is_numeric( $id ) ) {
			$replacements = $this->get_replacements();

			if ( isset( $replacements[ $id ] ) ) {
				$args['menu'] = $replacements[ $id ];
				$args['theme_location'] = '';
			}
		}

		return apply_filters(
			'ms_model_rule_replace_menu_replace_menus',
			$args,
			$this
		);
	}

	/**
	 * Get menu array.
	 *
	 * @since 1.0.4.2
	 *
	 * @return array {
	 *      @type string $menu_id The menu id.
	 *      @type string $name The menu name.
	 * }
	 */
	public function get_contents_array() {
		if ( empty( $this->menus ) ) {
			$this->menus = array(
				__( 'No menus found.', MS_TEXT_DOMAIN ),
			);

			$navs = wp_get_nav_menus( array( 'orderby' => 'name' ) );

			if ( ! empty( $navs ) ) {
				$this->menus = array();

				foreach ( $navs as $nav ) {
					$this->menus[ $nav->term_id ] = $nav->name;
				}
			}

			$this->menus = apply_filters(
				'ms_model_rule_replace_menu_get_contents_array',
				$this->menus,
				$this
			);
		}

		return $this->menus;
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.4.2
	 * @param $args The query post args
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$contents = array();

		$menus = $this->get_contents_array();

		if ( is_array( $menus ) ) {
			foreach ( $menus as $key => $name ) {
				$val = 0;
				$saved = $this->get_rule_value( $key );
				$post_title = '';
				$access = false;

				if ( is_numeric( $saved ) && isset( $menus[ $saved ] ) ) {
					$val = absint( $saved );
					$access = true;
					$post_title = sprintf(
						'%s &rarr; %s',
						strip_tags( $name ),
						$menus[$saved]
					);
				}

				$contents[ $key ] = (object) array(
					'access' => $access,
					'title' => $name,
					'value' => $val,
					'post_title' => $post_title,
					'id' => $key,
					'type' => $this->rule_type,
				);
			}
		}

		if ( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}

		return apply_filters(
			'ms_model_rule_replace_menu_get_contents',
			$contents,
			$args,
			$this
		);
	}

	/**
	 * Returns an array that contains menu_ids that should be replaced.
	 *
	 * @since  1.0.4.2
	 * @return array {
	 *     $original => $replacement
	 * }
	 */
	protected function get_replacements() {
		if ( ! is_array( $this->replacements ) ) {
			$this->replacements = array();
			$menus = $this->get_contents_array();

			foreach ( $menus as $menu_id => $name ) {
				$replacement = $this->get_rule_value( $menu_id );

				if ( is_numeric( $replacement ) && $replacement > 0 ) {
					$this->replacements[ $menu_id ] = intval( $replacement );
				}
			}
		}

		return $this->replacements;
	}

	/**
	 * Returns an array of matching options that are displayed in a select
	 * list for each item.
	 *
	 * @since  1.0.4.2
	 * @return array
	 */
	public function get_matching_options( $args = null ) {
		$options = array(
			0 => __( '( No replacement )', MS_TEXT_DOMAIN ),
		);

		$options += $this->get_contents_array();

		return apply_filters(
			'ms_model_rule_replace_menu_get_matching_options',
			$options,
			$args,
			$this
		);
	}

	/**
	 * Get post content array.
	 *
	 * @since 1.0.4.2
	 *
	 * @param array $array The query args. @see self::get_query_args()
	 * @return array {
	 *     @type int $key The content ID.
	 *     @type string $value The content title.
	 * }
	 */
	public function get_options_array( $args = array() ) {
		$cont = array();
		$contents = $this->get_contents( $args );

		foreach ( $contents as $content ) {
			$cont[ $content->id ] = $content->name;
		}

		return apply_filters(
			'ms_model_rule_replace_menu_get_content_array',
			$cont,
			$this
		);
	}

}