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
 * Membership Category Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Category extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_CATEGORY;

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Membership_Relationship $ms_relationship Optional. Not used.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->add_action( 'pre_get_posts', 'protect_posts', 98 );
		$this->add_filter( 'get_terms', 'protect_categories', 99, 2 );
	}

	/**
	 * Adds category__in filter for posts query to remove all posts which not
	 * belong to allowed categories.
	 *
	 * Related Filters:
	 * - pre_get_posts
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function protect_posts( $wp_query ) {
		// Only verify permission if ruled by categories.
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$post_type = $wp_query->get( 'post_type' );

			if ( empty( $post_type ) && isset( $wp_query->queried_object->post_type ) ) {
				$post_type = $wp_query->queried_object->post_type;
			}

			/*
			 * '' .. when post type is unknown assume 'post'
			 * 'post' .. obviously protect certain posts
			 * 'page' .. when front page is set to static page
			 */
			if ( in_array( $post_type, array( 'post', 'page', '' ) ) ) {
				// This list is already filtered (see the get_terms filter!)
				$contents = get_categories( 'get=all' );
				$categories = array();

				foreach ( $contents as $content ) {
					$categories[] = absint( $content->term_id );
				}

				$wp_query->query_vars['category__in'] = $categories;
			}
		}

		do_action(
			'ms_model_rule_category_protect_posts',
			$wp_query,
			$this
		);
	}

	/**
	 * Filters categories and removes all not accessible categories.
	 *
	 * @since 1.0.0
	 *
	 * @param array $terms The terms array.
	 * @param array $taxonomies The taxonomies array.
	 * @return array Filtered terms array.
	 */
	public function protect_categories( $terms, $taxonomies ) {
		$new_terms = array();

		// Bail - not fetching category taxonomy.
		if ( ! in_array( 'category', $taxonomies ) ) {
			return $terms;
		}

		if ( ! is_array( $terms ) ) {
			$terms = (array) $terms;
		}

		foreach ( $terms as $key => $term ) {
			if ( ! empty( $term->taxonomy ) && 'category' === $term->taxonomy ) {
				if ( parent::has_access( $term->term_id ) ) {
					$new_terms[ $key ] = $term;
				}
			} else {
				// Taxonomy is no category: Add it so custom taxonomies don't break.
				$new_terms[ $key ] = $term;
			}
		}

		return apply_filters(
			'ms_model_rule_category_protect_categories',
			$new_terms
		);
	}

	/**
	 * Verify access to the current category or post belonging to a catogory.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Optional. The current post_id.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $post_id = null ) {
		$has_access = null;

		// Only verify permissions if ruled by categories.
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$taxonomies = get_object_taxonomies( get_post_type() );

			// Verify post access accordingly to category rules.
			if ( ! empty( $post_id )
				|| ( is_single() && in_array( 'category', $taxonomies ) )
			) {
				if ( empty( $post_id ) ) {
					$post_id = get_the_ID();
				}

				$categories = wp_get_post_categories( $post_id );
				foreach ( $categories as $category_id ) {
					$has_access = parent::has_access( $category_id );

					if ( $has_access ) {
						break;
					}
				}
			}
			// Category page.
			elseif ( is_category() ) {
				$has_access = parent::has_access( get_queried_object_id() );
			}
		}

		return apply_filters(
			'ms_model_rule_category_has_access',
			$has_access,
			$post_id,
			$this
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 *
	 * @param string $args The default query args.
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$args = self::get_query_args( $args );

		$categories = get_categories( $args );
		$cont = array();

		foreach ( $categories as $key => $category ) {
			$category->id = $category->term_id;

			$category->type = MS_Model_RULE::RULE_TYPE_CATEGORY;
			$category->access = $this->get_rule_value( $category->id );

			if ( array_key_exists( $category->id, $this->dripped ) ) {
				$category->delayed_period =
					$this->dripped[ $category->id ]['period_unit'] . ' ' .
					$this->dripped[ $category->id ]['period_type'];
				$category->dripped = $this->dripped[ $category->id ];
			} else {
				$category->delayed_period = '';
			}

			$cont[ $key ] = $category;
		}


		return $cont;
	}

	/**
	 * Get category content array.
	 * Used to show content in html select.
	 *
	 * @since 1.0.0
	 * @return array of id => category name
	 */
	public function get_content_array( $args = null ) {
		$args = self::get_query_args( $args );
		$categories = get_categories( $args );
		$cont = array();

		foreach ( $categories as $key => $category ) {
			$cont[ $category->term_id ] = $category->name;
		}

		return apply_filters(
			'ms_model_rule_category_get_content_array',
			$cont
		);
	}

	/**
	 * Get the total content count.
	 *
	 * @since 1.1.0
	 *
	 * @param $args The query post args
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$count = 0;
		unset( $args['number'] );
		$args = self::get_query_args( $args );
		$categories = get_categories( $args );

		$count = count( $categories );

		return apply_filters(
			'ms_model_rule_category_get_content_count',
			$count,
			$args
		);
	}

	/**
	 * Get the default query args.
	 *
	 * @since 1.1.0
	 *
	 * @param string $args The query post args.
	 * @return array The parsed args.
	 */
	public function get_query_args( $args = null ) {
		$defaults = array(
			'number' => false,
			'offset' => 0,
			'get' => 'all', // interpreted by get_terms()
		);

		$status = ! empty( $args['rule_status'] ) ? $args['rule_status'] : null;
		$include = array();
		$exclude = array();

		switch ( $status ) {
			case MS_Model_Rule::FILTER_HAS_ACCESS;
			case MS_Model_Rule::FILTER_PROTECTED;
				$include = array_keys( $this->rule_value, true );
				$include = array_keys( $this->rule_value, true );
				break;

			case MS_Model_Rule::FILTER_NO_ACCESS;
				$include = array_keys( $this->rule_value, false );
				break;

			case MS_Model_Rule::FILTER_NOT_PROTECTED;
				$exclude = array_keys( $this->rule_value, true );
				break;

			default:
				// If not visitor membership, just show protected content
				if ( ! $this->is_base_rule ) {
					$include = array_keys( $this->rule_value );
				}
				break;
		}

		if ( isset( $args['s'] ) ) {
			$args['search'] = $args['s'];
		}

		if ( ! empty( $args['number'] ) ) {
			/*
			 * 'hierarchical' and 'child_of' must be empty in order for
			 * offset/number to work correctly.
			 */
			$args['hierarchical'] = false;
			$args['child_of'] = false;
		}

		if ( ! empty( $include ) ) {
			$args['include'] = $include;
		} elseif ( ! empty( $exclude ) ) {
			$args['exclude'] = $exclude;
		}

		$args = wp_parse_args( $args, $defaults );

		return apply_filters(
			'ms_model_rule_category_get_query_args',
			$args,
			$this
		);
	}

}