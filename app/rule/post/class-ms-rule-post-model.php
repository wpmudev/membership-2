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
 * Membership Post Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Rule_Post_Model extends MS_Rule {

	/**
	 * A list of all posts that are allowed by any MS_Rule_Post.
	 * (this logic is needed to merge rules if multiple memberships is enabled)
	 *
	 * @since 1.1.0.7
	 * @var   array
	 */
	static protected $allowed_ids = array();

	/**
	 * A list of all posts that are not available by any MS_Rule_Post.
	 * (this logic is needed to merge rules if multiple memberships is enabled)
	 *
	 * @since 1.1.0.7
	 * @var   array
	 */
	static protected $denied_ids = array();

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Post::RULE_ID;

	/**
	 * Returns the active flag for a specific rule.
	 * State depends on Add-on
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST );
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. The membership relationship.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->add_action( 'pre_get_posts', 'find_protected_posts', 99 );
		$this->add_action( 'pre_get_posts', 'protect_posts', 100 );
	}

	/**
	 * Protect post from showing.
	 *
	 * Related Action Hooks:
	 * - pre_get_posts
	 *
	 * @since 1.1.0.7
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function find_protected_posts( $wp_query ) {
		// List rather than on a single post
		if ( ! $wp_query->is_singular
			&& empty( $wp_query->query_vars['pagename'] )
			&& ( ! isset( $wp_query->query_vars['post_type'] )
				|| in_array( $wp_query->query_vars['post_type'], array( 'post', '' ) )
			)
		) {
			foreach ( $this->rule_value as $id => $value ) {
				if ( $this->has_access( $id ) ) {
					self::$allowed_ids[] = $id;
				} else {
					self::$denied_ids[] = $id;
				}
			}
		}
	}

	/**
	 * Protect post from showing.
	 *
	 * Related Action Hooks:
	 * - pre_get_posts
	 *
	 * @since 1.1.0.7
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function protect_posts( $wp_query ) {
		if ( empty( self::$denied_ids ) && empty( self::$allowed_ids ) ) {
			return $wp_query;
		}

		if ( ! empty( self::$denied_ids ) ) {
			// Remove duplicate entries from the ID arrays.
			self::$denied_ids = array_unique( self::$denied_ids, SORT_NUMERIC );
			self::$allowed_ids = array_unique( self::$allowed_ids, SORT_NUMERIC );

			// Remove any post that is allowed from the denied_ids list.
			self::$denied_ids = array_diff(
				self::$denied_ids,
				self::$allowed_ids
			);

			// Tell the WP query which posts are actually off limit for the user.
			$wp_query->query_vars['post__not_in'] = array_merge(
				$wp_query->query_vars['post__not_in'],
				self::$denied_ids
			);
		}

		self::$denied_ids = array();
		self::$allowed_ids = array();

		do_action(
			'ms_rule_post_model_protect_posts',
			$wp_query,
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

		if ( is_a( $post, 'WP_Post' ) && 'post' == $post->post_type )  {
			$post_id = $post->ID;
		}

		return apply_filters(
			'ms_rule_post_model_get_current_post_id',
			$post_id,
			$this
		);
	}

	/**
	 * Verify access to the current page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The page_id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id ) {
		$has_access = null;

		if ( empty( $id ) ) {
			$id = $this->get_current_post_id();
		} else {
			$post = get_post( $id );
			if ( ! is_a( $post, 'WP_Post' )
				|| ( ! empty( $post->post_type ) && 'post' != $post->post_type )
			)  {
				$id = 0;
			}
		}

		if ( ! empty( $id ) ) {
			$has_access = parent::has_access( $id );
		}

		return apply_filters(
			'ms_rule_post_model_has_access',
			$has_access,
			$id,
			$this
		);
	}

	/**
	 * Verify if has dripped rules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify.
	 * @return boolean True if has dripped rules.
	 */
	public function has_dripped_rules( $post_id = null ) {
		if ( empty( $post_id ) ) {
			$post_id  = $this->get_current_post_id();
		}

		return parent::has_dripped_rules( $post_id );
	}

	/**
	 * Get the total content count.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );

		$count = $query->found_posts;

		return apply_filters(
			'ms_rule_post_model_get_content_count',
			$count,
			$args
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$args = self::get_query_args( $args );

		$query = new WP_Query( $args );
		$posts = $query->get_posts();

		$contents = array();
		foreach ( $posts as $content ) {
			$content->id = $content->ID;
			$content->type = MS_Rule_Post::RULE_ID;
			$content->name = $content->post_name;
			$content->access = $this->get_rule_value( $content->id );

			$contents[ $content->id ] = $content;
		}

		return apply_filters(
			'ms_rule_post_model_get_contents',
			$contents,
			$this
		);
	}

	/**
	 * Get the default query args.
	 *
	 * @since 1.0.0
	 *
	 * @param string $args The query post args.
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The parsed args.
	 */
	public function get_query_args( $args = null ) {
		return parent::prepare_query_args( $args, 'wp_query' );
	}

}