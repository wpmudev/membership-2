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
 * Membership Custom Post Type Groups Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Rule_CptItem_Model extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_CUSTOM_POST_TYPE;

	/**
	 * Set-up the Rule
	 *
	 * @since  1.1.0
	 */
	static public function prepare_class() {
		// Register the tab-output handler for the admin side
		MS_Factory::load( 'MS_Rule_CptItem_View' )->register();
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. Not used.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->add_action( 'pre_get_posts', 'protect_posts', 98 );
	}

	/**
	 * Adds filter for posts query to remove all protected custom post types.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */

	public function protect_posts( $wp_query ) {
		$post_type = $wp_query->get( 'post_type' );
		$apply = true;

		/**
		 * Only protect if not cpt group.
		 * Restrict query to show only has_access cpt posts.
		 */
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$protected_list = MS_Rule_CptGroup_Model::get_excluded_content();

			if ( $apply && $wp_query->is_singular ) { $apply = false; }
			if ( $apply && empty( $wp_query->query_vars['pagename'] ) ) { $apply = false; }
			if ( $apply && ! in_array( @$post_type, $protected_list ) ) { $apply = false; }

			if ( $apply ) {
				foreach ( $this->rule_value as $id => $value ) {
					if ( ! $this->has_access( $id ) ) {
						$wp_query->query_vars['post__not_in'][] = $id;
					}
				}
			}
		}

		do_action(
			'ms_model_rule_custom_post_type_protect_posts',
			$wp_query,
			$this
		);
	}

	/**
	 * Get rule value for a specific content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to get rule value for.
	 * @return boolean The rule value for the requested content. Default $rule_value_default.
	 */
	public function get_rule_value( $id ) {
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			if ( isset( $this->rule_value[ $id ] ) ) {
				$value = $this->rule_value[ $id ];
			} else {
				$value = self::RULE_VALUE_HAS_ACCESS;
			}
		} else {
			$membership = $this->get_membership();
			$cpt_group = $membership->get_rule( self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );

			if ( isset( $this->rule_value[ $id ] ) ) {
				$value = $this->rule_value[ $id ];
			} else {
				$value = $cpt_group->has_access( $id );
			}
		}

		return apply_filters(
			'ms_rule_cptgroup_model_get_rule_value',
			$value,
			$id,
			$this
		);
	}

	/**
	 * Verify access to the current content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $post_id = null ) {
		$has_access = null;

		// Only verify permission if ruled by cpt post by post.
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			if ( empty( $post_id ) ) {
				$post_id = $this->get_current_post_id();
			}

			if ( ! empty( $post_id ) ) {
				$post_type = get_post_type( $post_id );
				$mspt = MS_Rule_CptGroup_Model::get_ms_post_types();
				$cpt = MS_Rule_CptGroup_Model::get_custom_post_types();

				if ( in_array( $post_type, $mspt ) ) {
					// Always allow access to Protected Content pages.
					$has_access = true;
				} elseif ( in_array( $post_type, $cpt ) ) {
					// Custom post type
					$has_access = parent::has_access( $post_id  );
				} else {
					// WordPress core pages are ignored by this rule.
					$has_access = null;
				}
			}
		}

		return apply_filters(
			'ms_model_rule_custom_post_type_has_access',
			$has_access,
			$post_id,
			$this
		);
	}

	/**
	 * Get the current post id.
	 *
	 * @since 1.0.0
	 *
	 * @return int The post id, or null if it is not a post.
	 */
	private function get_current_post_id() {
		$post_id = null;
		$post = get_queried_object();

		if ( is_a( $post, 'WP_Post' ) ) {
			$post_id = $post->ID;
		}

		return apply_filters(
			'ms_model_rule_custom_post_type_get_current_post_id',
			$post_id,
			$this
		);
	}

	/**
	 * Get the total content count.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Function_Reference/get_pages
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		unset( $args['posts_per_page'] );
		$args = $this->get_query_args( $args );
		$items = get_posts( $args );
		$count = count( $items );

		return apply_filters(
			'ms_model_rule_custom_post_type_gget_content_count',
			$count,
			$args,
			$this
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Function_Reference/get_pages
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$cpts = MS_Rule_CptGroup_Model::get_custom_post_types();

		if ( empty( $cpts ) ) {
			return array();
		}

		$args = $this->get_query_args( $args );
		$contents = get_posts( $args );

		foreach ( $contents as $content ) {
			$content->id = $content->ID;
			$content->type = $this->rule_type;
			$content->access = $this->get_rule_value( $content->id  );
		}

		return apply_filters(
			'ms_model_rule_custom_post_type_get_contents',
			$contents,
			$args,
			$this
		);
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Return default search arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Function_Reference/get_pages
	 * @return array $args The parsed args.
	 */
	public function get_query_args( $args = null ) {
		$cpts = MS_Rule_CptGroup_Model::get_custom_post_types();
		if ( ! isset( $args['post_type'] ) ) { $args['post_type'] = $cpts; }

		return parent::prepare_query_args( $args, 'get_posts' );
	}
}