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
class MS_Model_Rule_Custom_Post_Type extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_CUSTOM_POST_TYPE;

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
			$protected_list = MS_Model_Rule_Custom_Post_Type_Group::get_excluded_content();

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

		do_action( 'ms_model_rule_custom_post_type_protect_posts', $wp_query, $this );
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
			$value = isset( $this->rule_value[ $id ] ) ? $this->rule_value[ $id ] : $this->rule_value_default;
		}
		else {
			$membership = $this->get_membership();
			$cpt_group = $membership->get_rule( self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
			$value = isset( $this->rule_value[ $id ] ) ? $this->rule_value[ $id ] : $cpt_group->has_access( $id );
		}

		return apply_filters( 'ms_model_rule_cpt_get_rule_value', $value, $id, $this );
	}

	/**
	 * Verify access to the current content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_id The content id to verify access.
	 * @return boolean True if has access, false otherwise.
	 */
	public function has_access( $post_id = null ) {
		$has_access = false;

		// Only verify permission if ruled by cpt post by post.
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			if ( empty( $post_id ) ) {
				$post_id = $this->get_current_post_id();
			}

			if ( ! empty( $post_id ) ) {
				$post_type = get_post_type( $post_id );

				if ( in_array( $post_type, MS_Model_Rule_Custom_Post_Type_Group::get_ms_post_types() ) ) {
					$has_access = true;
				}
				elseif ( in_array( $post_type, MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types() ) ) {
					$has_access = parent::has_access( $post_id  );
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

		return apply_filters( 'ms_model_rule_custom_post_type_get_current_post_id', $post_id, $this );
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
		$args = $this->get_query_args( $args );
		$query = new WP_Query( $args );
		$count = $query->found_posts;

		return apply_filters( 'ms_model_rule_custom_post_type_gget_content_count', $count, $args, $this );
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$cpts = MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types();

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

		if ( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}

		return apply_filters( 'ms_model_rule_custom_post_type_get_contents', $contents, $args, $this );
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

		$cpts = MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types();

		$defaults = array(
			'posts_per_page' => -1,
			'offset'      => 0,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_type'   => $cpts,
			'post_status' => 'publish',
		);

		$args = wp_parse_args( $args, $defaults );
		$args = parent::get_query_args( $args );

		return apply_filters( 'ms_model_rule_cpt_get_query_args', $args, $this );
	}

	/**
	 * Get cpt content array.
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
			if ( ! is_array( $cont[ $content->post_type ] ) ) {
				$cont[ $content->post_type ] = array();
			}

			$cont[ $content->post_type ][ $content->id ] = $content->post_title;
		}

		return apply_filters( 'ms_model_rule_cpt_get_content_array', $cont, $this );
	}
}