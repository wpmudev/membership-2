<?php
/**
 * Membership Custom Post Type Groups Rule class.
 *
 * Persisted by Membership class.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Rule_CptItem_Model extends MS_Rule {

	/**
	 * A list of all posts that are allowed by any MS_Rule_CptItem_Model.
	 * (this logic is needed to merge rules if multiple memberships is enabled)
	 *
	 * @since  1.0.0
	 * @var   array
	 */
	static protected $allowed_ids = array();

	/**
	 * A list of all posts that are not available by any MS_Rule_CptItem_Model.
	 * (this logic is needed to merge rules if multiple memberships is enabled)
	 *
	 * @since  1.0.0
	 * @var   array
	 */
	static protected $denied_ids = array();
	

	/**
	 * Rule type.
	 *
	 * @since  1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_CptItem::RULE_ID;

	/**
	 * Returns the active flag for a specific rule.
	 * State depends on Add-on
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST );
	}


	/**
	 * Set initial protection.
	 *
	 * @since  1.0.0
	 */
	public function protect_content() {
		/**
		 * Only protect if not cpt group.
		 * Restrict query to show only has_access cpt posts.
		 */
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			parent::protect_content();
			$this->add_action( 'parse_query', 'find_protected_posts', 97 );
			$this->add_action( 'parse_query', 'protect_posts', 98 );
			
		}
	}

	/**
	 * Protect CPT from showing.
	 *
	 * Related Action Hooks:
	 * - parse_query
	 *
	 * @since  1.0.0
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function find_protected_posts( $wp_query ) {
		$post_types = $wp_query->get( 'post_type' );		

		// There was one case where this was needed...
		if ( empty( $post_types )
			&& isset( $wp_query->queried_object )
			&& isset( $wp_query->queried_object->post_type )
		) {
			$post_types = $wp_query->queried_object->post_type;
		}

		if ( ! empty( $post_types ) // Only protect anything if post-type is known
			&& ! $wp_query->is_singular  // Single pages are protected by `has_access()`
		) {
			$excluded = MS_Rule_CptGroup_Model::get_excluded_content();

			/*
			 * We need an array. WordPress will give us an array, when the
			 * WP_Query did query for multiple post-types at once.
			 * We check each post-type individually!
			 */
			if ( ! is_array( $post_types ) ) {
				$post_types = array( $post_types );
			}

			$allowed_posts = $wp_query->query_vars['post__in'];
			if ( ! is_array( $allowed_posts ) ) {
				$allowed_posts = array();
			}

			$denied_posts = $wp_query->query_vars['post__not_in'];
			if ( ! is_array( $denied_posts ) ) {
				$denied_posts = array();
			}

			foreach ( $post_types as $post_type ) {
				/*
				 * If the current post type is protected then set the query-arg
				 * post__not_in to a list of all protected items.
				 */
				if ( ! in_array( $post_type, $excluded ) ) {
					foreach ( $this->rule_value as $id => $value ) {
						if ( $this->has_access( $id ) ) {
							self::$allowed_ids[] = $id;
						} else {
							self::$denied_ids[] = $id;
						}
					}
				}
			}			
		}
	}		

	/**
	 * Adds filter for posts query to remove all protected custom post types.
	 *
	 * @since  1.0.0
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function protect_posts( &$wp_query ) {
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

			if ( ! empty( self::$denied_ids ) ) {
				$wp_query->set( 'post__not_in', self::$denied_ids );
			}
		}

		self::$denied_ids = array();
		self::$allowed_ids = array();

		do_action(
			'ms_rule_custom_post_type_protect_posts',
			$wp_query,
			$this
		);
	}

	/**
	 * Verify access to the current content.
	 *
	 * @since  1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id, $admin_has_access = true ) {
		$has_access = null;

		// Only verify permission if ruled by cpt post by post.
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			if ( empty( $id ) ) {
				$id = $this->get_current_post_id();
			}

			if ( ! empty( $id ) ) {
				$post_type = get_post_type( $id );
				$mspt = MS_Rule_CptGroup_Model::get_ms_post_types();
				$cpt = MS_Rule_CptGroup_Model::get_custom_post_types();

				if ( in_array( $post_type, $mspt ) ) {
					// Always allow access to Membership2 pages.
					$has_access = true;
				} elseif ( in_array( $post_type, $cpt ) ) {
					// Custom post type
					$has_access = parent::has_access( $id, $admin_has_access );
				} else {
					// WordPress core pages are ignored by this rule.
					$has_access = null;
				}
			}
		}

		return apply_filters(
			'ms_rule_custom_post_type_has_access',
			$has_access,
			$id,
			$this
		);
	}

	/**
	 * Get the current post id.
	 *
	 * @since  1.0.0
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
			'ms_rule_custom_post_type_get_current_post_id',
			$post_id,
			$this
		);
	}

	/**
	 * Get the total content count.
	 *
	 * @since  1.0.0
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
			'ms_rule_custom_post_type_gget_content_count',
			$count,
			$args,
			$this
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since  1.0.0
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
		$result = array();

		foreach ( $contents as $content ) {
			$content->id = $content->ID;
			$content->type = MS_Rule_CptItem::RULE_ID;
			$content->access = $this->get_rule_value( $content->id );

			$result[ $content->id ] = $content;
		}

		return apply_filters(
			'ms_rule_custom_post_type_get_contents',
			$result,
			$args,
			$this
		);
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Return default search arguments.
	 *
	 * @since  1.0.0
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