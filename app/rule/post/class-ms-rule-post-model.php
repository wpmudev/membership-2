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
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Post::RULE_ID;

	/**
	 * Membership relationship start date.
	 *
	 * @since 1.0.0
	 *
	 * @var string $start_date
	 */
	private $start_date;

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

		$this->start_date = $ms_relationship->start_date;
		$this->add_action( 'pre_get_posts', 'protect_posts', 99 );
		$this->add_filter( 'posts_where', 'include_dripped', 10, 2 );
	}

	/**
	 * Protect post from showing.
	 *
	 * Related Action Hooks:
	 * - pre_get_posts
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function protect_posts( $wp_query ) {
		// List rather than on a single post
		if ( ! $wp_query->is_singular
			&& empty( $wp_query->query_vars['pagename'] )
			&& ( ! isset( $wp_query->query_vars['post_type'] )
				|| in_array( $wp_query->query_vars['post_type'], array( 'post', '' ) )
			)
		) {
			// Only verify permission if ruled by post by post.
			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
				foreach ( $this->rule_value as $id => $value ) {
					if ( ! $this->has_access( $id ) ) {
						$wp_query->query_vars['post__not_in'][] = $id;
					}
				}
			}

			$membership = $this->get_membership();
			if ( MS_Model_Membership::TYPE_DRIPPED == $membership->type ) {
				$dripped_type = $this->get_dripped_type();
				/**
				 * Exclude dripped content.
				 * Can't include posts, just exclude because of category clause conflict to post_in.
				 * Using filter 'posts_where' to include dripped content.
				 * * @todo handle default rule value.
				 */
				if ( ! empty( $this->dripped[ $dripped_type ] )
					&& is_array( $this->dripped[ $dripped_type ] )
				) {
					foreach ( $this->dripped[ $dripped_type ] as $post_id => $period ) {
						if ( ! $this->has_dripped_access( $this->start_date, $post_id ) ) {
							$wp_query->query_vars['post__not_in'][] = $post_id;
							if ( $key = array_search( $post_id, $wp_query->query_vars['post__in'] ) ) {
								unset( $wp_query->query_vars['post__in'][ $key ] );
							}
						}
					}
				}
			}
		}

		do_action( 'ms_rule_post_model_protect_posts', $wp_query, $this );
	}

	/**
	 * Include dripped content.
	 *
	 * Workaround to include dripped posts that not belongs to a accessible category.
	 *
	 * Related Actions Hooks:
	 * - posts_where
	 *
	 * @since 1.0.0
	 *
	 * @param string $where The where clause before filter.
	 * @param WP_Query $wp_query The wp_query object.
	 * @return string The modified where clause.
	 */
	public function include_dripped( $where, $wp_query ) {
		do_action(
			'ms_rule_post_model_include_dripped_before',
			$where,
			$wp_query,
			$this
		);

		global $wpdb;

		if ( ! $wp_query->is_singular
			&& empty( $wp_query->query_vars['pagename'] )
			&& ( ! isset( $wp_query->query_vars['post_type'] )
				|| in_array( $wp_query->query_vars['post_type'], array( 'post', '' ) )
			)
		) {
			$membership = $this->get_membership();
			if ( MS_Model_Membership::TYPE_DRIPPED == $membership->type ) {
				$dripped_type = $this->get_dripped_type();

				$posts = array();
				if ( ! empty( $this->rule_value )
					&& is_array( $this->rule_value )
				) {
					foreach ( $this->rule_value as $post_id => $value ) {
						if ( $this->has_dripped_access( $this->start_date, $post_id ) ) {
							$posts[] = $post_id;
						}
					}
				}
				if ( ! empty( $posts ) ) {
					$post__in = join( ',', $posts );
					$where .= " OR {$wpdb->posts}.ID IN ($post__in)";
				}
			}
		}

		return apply_filters(
			'ms_rule_post_model_include_dripped',
			$where,
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

		if ( is_a( $post, 'WP_Post' ) && $post->post_type == 'post' )  {
			$post_id = $post->ID;
		}

		return apply_filters(
			'ms_rule_post_model_get_current_post_id',
			$post_id,
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
		if ( isset( $this->rule_value[ $id ] ) ) {
			$value = $this->rule_value[ $id ];
		} else {
			$value = MS_Model_Rule::RULE_VALUE_HAS_ACCESS;
		}

		return apply_filters(
			'ms_rule_post_model_get_rule_value',
			$value,
			$id,
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
		global $wp_query;
		$has_access = null;

		if ( empty( $id ) ) {
			$id = $this->get_current_post_id();
		}

		$post_type = get_post_type( $id );
		if ( in_array( $post_type, array( 'post', '' ) ) ) {
			// Only verify permission if ruled by post by post.
			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
				$has_access = parent::has_access( $id );
			}
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

		return apply_filters(
			'ms_rule_post_model_has_dripped_rules',
			parent::has_dripped_rules( $post_id )
		);
	}

	/**
	 * Verify access to dripped content.
	 *
	 * The MS_Helper_Period::current_date may be simulating a date.
	 *
	 * @since 1.0.0
	 * @param string $start_date The start date of the member membership.
	 * @param string $id The content id to verify dripped acccess.
	 */
	public function has_dripped_access( $start_date, $post_id = null ) {
		$has_access = false;

		if ( empty( $post_id ) ) {
			$post_id  = $this->get_current_post_id();
		}

		$has_access = parent::has_dripped_access( $start_date, $post_id );

		return apply_filters(
			'ms_rule_post_model_has_dripped_access',
			$has_access,
			$this
		);
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

			$content->delayed_period = $this->has_dripped_rules( $content->id );
			$content->avail_date = $this->get_dripped_avail_date(
				$content->id,
				MS_Helper_Period::current_date( null, true )
			);

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