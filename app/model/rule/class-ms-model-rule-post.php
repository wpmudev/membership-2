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
class MS_Model_Rule_Post extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_POST;

	/**
	 * Membership relationship start date.
	 *
	 * @since 1.0.0
	 *
	 * @var string $start_date
	 */
	private $start_date;

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Membership_Relationship $ms_relationship Optional. The membership relationship.
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

		do_action( 'ms_model_rule_post_protect_posts', $wp_query, $this );
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
			'ms_model_rule_post_include_dripped_before',
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
			'ms_model_rule_post_include_dripped',
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
			'ms_model_rule_post_get_current_post_id',
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
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			if ( isset( $this->rule_value[ $id ] ) ) {
				$value = $this->rule_value[ $id ];
			} else {
				$value = self::RULE_VALUE_HAS_ACCESS;
			}
		} else {
			$membership = $this->get_membership();
			$rule_category = $membership->get_rule( self::RULE_TYPE_CATEGORY );

			if ( isset( $this->rule_value[ $id ] ) ) {
				$value = $this->rule_value[ $id ];
			} else {
				$value = $rule_category->has_access( $id );
			}
		}

		return apply_filters(
			'ms_model_rule_post_get_rule_value',
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
	 * @param int $post_id Optional. The page_id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $post_id = null ) {
		global $wp_query;
		$has_access = null;

		if ( empty( $post_id ) ) {
			$post_id = $this->get_current_post_id();
		}

		$post_type = get_post_type( $post_id );
		if ( in_array( $post_type, array( 'post', '' ) ) ) {
			$has_access = false;

			/*
			 * Only verify permission if ruled by post by post.
			 * @todo verify addon handling
			 */
			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
				$has_access = parent::has_access( $post_id );
			}
			else {
				$has_access = $this->get_rule_value( $post_id );
			}
		}

		// Feed page request
		if ( ! empty( $wp_query->query_vars['feed'] ) ) {
			$has_access = true;
		}

		return apply_filters(
			'ms_model_rule_post_has_access',
			$has_access,
			$post_id,
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
			'ms_model_rule_post_has_dripped_rules',
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
			'ms_model_rule_post_has_dripped_access',
			$has_access,
			$this
		);
	}

	/**
	 * Merge rule values.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Rule $src_rule The source rule model to merge rules to.
	 */
	public function merge_rule_values( $src_rule ) {
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			parent::merge_rule_values( $src_rule );
		}

		do_action( 'ms_model_rule_post_merge_rule_values', $src_rule, $this );
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
		$count = 0;
		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );

		$count = $query->found_posts;

		return apply_filters(
			'ms_model_rule_post_get_content_count',
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
			$content->type = MS_Model_RULE::RULE_TYPE_POST;
			$content->access = false;
			$content->name = $content->post_name;

			$content->categories = array();
			$cats = array();
			$categories = wp_get_post_categories( $content->id );
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $cat_id ) {
					$cat = get_category( $cat_id );
					$cats[] = $cat->name;
				}
				$content->categories = $cats;
			}
			else {
				$content->categories = array();
			}

			$content->access = $this->get_rule_value( $content->id );

			$content->delayed_period = $this->has_dripped_rules( $content->id );
			$content->avail_date = $this->get_dripped_avail_date(
				$content->id,
				MS_Helper_Period::current_date( null, true )
			);

			$contents[ $content->id ] = $content;
		}

		if ( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}

		return apply_filters(
			'ms_model_rule_post_get_contents',
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
		$defaults = array(
			'posts_per_page' => -1,
			'offset'      => 0,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_type'   => 'post',
			'post_status' => 'publish',
		);

		// If not visitor membership, just show protected content
		if ( ! $this->is_base_rule ) {
			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
				if ( ! empty( $this->rule_value ) ) {
					$args['post__in'] = array_keys( $this->rule_value );
				}
				else {
					$args['post__in'] = array( 0 );
				}
			} else {
				// Category rules
				$membership = $this->get_membership();
				$rule_category = $membership->get_rule( self::RULE_TYPE_CATEGORY );

				if ( ! empty( $rule_category->rule_value ) ) {
					$args['category__in'] = array_keys( $rule_category->rule_value );
					$args['tax_query'] = array( 'relation' => 'OR' );
				} else {
					$args['post__in'] = array( 0 );
				}
			}
		}
		$args = wp_parse_args( $args, $defaults );

		return apply_filters( 'ms_model_rule_post_get_query_args', $args );
	}

	/**
	 * Get post content array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $array The query args. @see self::get_query_args()
	 * @return array {
	 *     @type int $key The content ID.
	 *     @type string $value The content title.
	 * }
	 */
	public function get_content_array() {
		$cont = array();
		$contents = $this->get_contents();
		foreach ( $contents as $content ) {
			$cont[ $content->id ] = $content->post_title;
		}

		return apply_filters(
			'ms_model_rule_post_get_content_array',
			$cont,
			$this
		);
	}

}