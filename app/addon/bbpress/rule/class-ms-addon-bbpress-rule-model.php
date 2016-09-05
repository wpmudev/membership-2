<?php
/**
 * Membership bbPress Rule class.
 *
 * Persisted by Membership class.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Addon_Bbpress_Rule_Model extends MS_Rule {

	/**
	 * Custom Post Type names that are used by bbPress
	 *
	 * @since  1.0.0
	 */
	const CPT_BB_FORUM = 'forum';
	const CPT_BB_TOPIC = 'topic';
	const CPT_BB_REPLY = 'reply';

	/**
	 * Rule type.
	 *
	 * @since  1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Addon_BbPress_Rule::RULE_ID;

	/**
	 * Verify access to the current content.
	 *
	 * @since  1.0.0
	 *
	 * @param int $id The content post ID to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id, $admin_has_access = true ) {
		global $wp_query;
		$has_access = null;

		if ( empty( $id ) ) {
			$id  = $this->get_current_post_id();
		}

		if ( ! empty( $id ) ) {
			$post_type = get_post_type( $id );

			if ( in_array( $post_type, self::get_bb_cpt() ) ) {
				$has_access = false;

				// Only verify permission if addon is enabled.
				if ( MS_Addon_Bbpress::is_active() ) {
					switch ( $post_type ) {
						case self::CPT_BB_FORUM:
							$has_access = parent::has_access( $id, $admin_has_access );
							break;

						case self::CPT_BB_TOPIC:
							if ( function_exists( 'bbp_get_topic_forum_id' ) ) {
								$forum_id = bbp_get_topic_forum_id( $id );
								$has_access = parent::has_access( $forum_id, $admin_has_access );
							}
							break;

						case self::CPT_BB_REPLY:
							if ( function_exists( 'bbp_get_reply_forum_id' ) ) {
								$forum_id = bbp_get_reply_forum_id( $id );
								$has_access = parent::has_access( $forum_id, $admin_has_access );
							}
							break;
					}
				} else {
					$has_access = true;
				}
			}
		} else {
			/*
			 * If post type is forum and no post_id, it is the forum list page, give access.
			 * @todo Find another way to verify if the current page is the forum list page.
			 */
			if ( self::CPT_BB_FORUM === $wp_query->get( 'post_type' ) ) {
				$has_access = true;
			}
		}

		return apply_filters(
			'ms_addon_bbpress_model_rule_has_access',
			$has_access,
			$id,
			$this
		);
	}

	/**
	 * Set initial protection.
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. Not used.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->add_action( 'pre_get_posts', 'protect_posts', 98 );
	}

	/**
	 * Adds filter for posts query to remove all protected bbpress custom post types.
	 *
	 * Related Action Hooks:
	 * - pre_get_posts
	 *
	 * @since  1.0.0
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function protect_posts( $wp_query ) {
		do_action(
			'ms_addon_bbpress_model_rule_protect_posts_before',
			$wp_query,
			$this
		);

		$post_type = $wp_query->get( 'post_type' );

		/*
		 * Only protect if add-on is enabled.
		 * Restrict query to show only has_access cpt posts.
		 */
		if ( MS_Addon_Bbpress::is_active() ) {
			if ( ! $wp_query->is_singular
				&& empty( $wp_query->query_vars['pagename'] )
				&& ! empty( $post_type )
				&& self::CPT_BB_FORUM == $post_type
			) {
				foreach ( $this->rule_value as $id => $value ) {
					if ( ! $this->has_access( $id ) ) {
						$wp_query->query_vars['post__not_in'][] = $id;
					}
				}
			}
		}

		do_action(
			'ms_addon_bbpress_model_rule_protect_posts_after',
			$wp_query,
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

		if ( is_a( $post, 'WP_Post' ) )  {
			$post_id = $post->ID;
		}

		return apply_filters(
			'ms_addon_bbpress_model_rule_get_current_post_id',
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
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$defaults = array(
			'posts_per_page' => -1,
			'post_type' => self::CPT_BB_FORUM,
			'post_status' => 'publish',
		);
		$args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $args );
		$count = $query->found_posts;

		return apply_filters(
			'ms_addon_bbpress_model_rule_get_content_count',
			$count
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since  1.0.0
	 *
	 * @param string $args The default query post args.
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$args = self::get_query_args( $args );

		$query = new WP_Query( $args );
		$posts = $query->posts;

		$contents = array();
		foreach ( $posts as $content ) {
			$content->id = $content->ID;
			$content->name = $content->post_title;
			$content->type = $this->rule_type;
			$content->access = $this->get_rule_value( $content->id );

			$contents[ $content->id ] = $content;
		}

		if ( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}

		return apply_filters(
			'ms_addon_bbpress_model_rule_get_contents',
			$contents
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
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public function get_query_args( $args = null ) {
		$defaults = array(
			'posts_per_page' => -1,
			'offset' => 0,
			'orderby' => 'ID',
			'order' => 'DESC',
			'post_type' => self::CPT_BB_FORUM,
			'post_status' => 'publish',
		);

		$args = wp_parse_args( $args, $defaults );
		$args = parent::prepare_query_args( $args );

		return apply_filters(
			'ms_addon_bbpress_model_rule_get_query_args',
			$args
		);
	}

	/**
	 * Get BBPress custom post types.
	 *
	 * @since  1.0.0
	 *
	 * @return array The bbpress custom post types.
	 */
	public static function get_bb_cpt() {
		return apply_filters(
			'ms_addon_bbpress_rule_model_get_bb_cpt',
			array(
				self::CPT_BB_FORUM,
				self::CPT_BB_TOPIC,
				self::CPT_BB_REPLY,
			)
		);
	}
}