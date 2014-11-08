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
 * Membership bbPress Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Bbpress extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Integration_BbPress::RULE_TYPE_BBPRESS;

	/**
	 * Verify access to the current content.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The content post ID to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $post_id = null ) {
		global $wp_query;
		$has_access = null;

		if ( empty( $post_id ) ) {
			$post_id  = $this->get_current_post_id();
		}

		if ( ! empty( $post_id ) ) {
			$post_type = get_post_type( $post_id );

			if ( in_array( $post_type, MS_Integration_Bbpress::get_bb_custom_post_types() ) ) {
				$has_access = false;

				// Only verify permission if addon is enabled.
				if ( MS_Model_Addon::is_enabled( MS_Integration_Bbpress::ADDON_BBPRESS ) ) {
					switch ( $post_type ) {
						case MS_Integration_Bbpress::CPT_BB_FORUM:
							$has_access = parent::has_access( $post_id );
							break;

						case MS_Integration_Bbpress::CPT_BB_TOPIC:
							if ( function_exists( 'bbp_get_topic_forum_id' ) ) {
								$forum_id = bbp_get_topic_forum_id( $post_id );
								$has_access = parent::has_access( $forum_id );
							}
							break;

						case MS_Integration_Bbpress::CPT_BB_REPLY:
							if ( function_exists( 'bbp_get_reply_forum_id' ) ) {
								$forum_id = bbp_get_reply_forum_id( $post_id );
								$has_access = parent::has_access( $forum_id );
							}
							break;
					}
				}
				else {
					$has_access = true;
				}
			}
		}
		else {
			/*
			 * If post type is forum and no post_id, it is the forum list page, give access.
			 * @todo Find another way to verify if the current page is the forum list page.
			 */
			if ( MS_Integration_Bbpress::CPT_BB_FORUM === $wp_query->get( 'post_type' ) ) {
				$has_access = true;
			}
		}

		return apply_filters(
			'ms_model_rule_bbpress_has_access',
			$has_access,
			$post_id,
			$this
		);
	}

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
	}

	/**
	 * Adds filter for posts query to remove all protected bbpress custom post types.
	 *
	 * Related Action Hooks:
	 * - pre_get_posts
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function protect_posts( $wp_query ) {
		do_action(
			'ms_model_rule_bbpress_protect_posts_before',
			$wp_query,
			$this
		);

		$post_type = $wp_query->get( 'post_type' );

		/*
		 * Only protect if add-on is enabled.
		 * Restrict query to show only has_access cpt posts.
		 */
		if ( MS_Model_Addon::is_enabled( MS_Integration_Bbpress::ADDON_BBPRESS ) ) {
			if ( ! $wp_query->is_singular
				&& empty( $wp_query->query_vars['pagename'] )
				&& ! empty( $post_type )
				&& MS_Integration_Bbpress::CPT_BB_FORUM == $post_type
			) {
				foreach ( $this->rule_value as $id => $value ) {
					if ( ! $this->has_access( $id ) ) {
						$wp_query->query_vars['post__not_in'][] = $id;
					}
				}
			}
		}

		do_action(
			'ms_model_rule_bbpress_protect_posts_after',
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

		if ( is_a( $post, 'WP_Post' ) )  {
			$post_id = $post->ID;
		}

		return apply_filters(
			'ms_model_rule_bbpress_get_current_post_id',
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
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$defaults = array(
			'posts_per_page' => -1,
			'post_type'   => MS_Integration_Bbpress::CPT_BB_FORUM,
			'post_status' => 'publish',
		);
		$args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $args );
		$count = $query->found_posts;

		return apply_filters(
			'ms_model_rule_bbpress_get_content_count',
			$count
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 *
	 * @param string $args The default query post args.
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$args = self::get_query_args( $args );

		$query = new WP_Query( $args );
		$posts = $query->get_posts();

		$contents = array();
		foreach ( $posts as $content ) {
			$content->id = $content->ID;
			$content->name = $content->post_title;
			$content->type = $this->rule_type;
			$content->access = $this->get_rule_value( $content->id  );

			$contents[ $content->id ] = $content;
		}

		if ( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}

		return apply_filters( 'ms_model_rule_bbpress_get_contents', $contents );
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Return default search arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public function get_query_args( $args = null ) {
		$defaults = array(
			'posts_per_page' => -1,
			'offset'      => 0,
			'orderby'     => 'ID',
			'order'       => 'DESC',
			'post_type'   => MS_Integration_Bbpress::CPT_BB_FORUM,
			'post_status' => 'publish',
		);

		$args = wp_parse_args( $args, $defaults );
		$args = parent::get_query_args( $args );

		return apply_filters( 'ms_model_rule_bbpress_get_query_args', $args );
	}
}