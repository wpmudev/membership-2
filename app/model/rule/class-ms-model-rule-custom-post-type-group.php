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
class MS_Model_Rule_Custom_Post_Type_Group extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP;

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
		 * Only protect if cpt group.
		 * Protect in list rather than on a single post.
		 * Workaroudn to invalidate the query.
		 */
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			if ( $apply && $wp_query->is_singular ) { $apply = false; }
			if ( $apply && empty( $wp_query->query_vars['pagename'] ) ) { $apply = false; }
			if ( $apply && in_array( @$post_type, self::get_custom_post_types() ) ) { $apply = false; }
			if ( $apply && ! parent::has_access( $post_type ) ) { $apply = false; }

			if ( $apply )  {
				$wp_query->query_vars['post__in'] = array( 0 => 0 );
			}
		}

		do_action( 'ms_model_rule_custom_post_type_group_protect_posts', $wp_query, $this );
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

		// Only verify permission if NOT ruled by cpt post by post.
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$has_access = false;
			if ( ! empty( $post_id ) ) {
				$post = get_post( $post_id );
			}
			else {
				$post = get_queried_object();
			}

			$post_type = ! empty( $post->post_type ) ? $post->post_type : '';
			if ( empty( $post_type ) && ! empty( $post->query_var ) ) {
				$post_type = $post->query_var;
			}

			if ( in_array( $post_type, self::get_ms_post_types() ) ) {
				$has_access = true;
			}
			elseif ( in_array( $post_type, self::get_custom_post_types() ) && parent::has_access( $post_type ) )  {
				$has_access = true;
			}
		}

		return apply_filters( 'ms_model_rule_custom_post_type_group_has_access', $has_access, $post_id, $this );
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 *
	 * @param string $args Optional. Not used.
	 *
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$cpts = self::get_custom_post_types();
		$contents = array();

		foreach ( $cpts as $key => $content ) {
			if ( ! empty( $args['protected_content'] ) && ! $this->has_rule( $key ) ) {
				continue;
			}

			$contents[ $key ] = new StdClass();
			$contents[ $key ]->id = $key;
			$contents[ $key ]->name = $content;
			$contents[ $key ]->type = $this->rule_type;

			$contents[ $key ]->access = parent::has_access( $key );
		}

		if ( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}

		return apply_filters(
			'ms_model_rule_custom_post_type_group_get_contents',
			$contents,
			$args,
			$this
		);
	}

	/**
	 * Get content array.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Optional. Not used.
	 * @return array {
	 *     @type string $rule_value The rule value.
	 *     @type string $description The rule description.
	 * }
	 */
	public function get_content_array() {
		$cont = array();
		$contents = $this->get_contents();

		foreach ( $contents as $content ) {
			$cont[ $content->id ] = $content->name;
		}

		return apply_filters( 'ms_model_rule_comment_get_content_array', $cont, $this );
	}

	/**
	 * Get post types that should not be protected.
	 *
	 * Default WP post types, membership post types
	 *
	 * @since 1.0.0
	 *
	 * @return array The excluded post types.
	 */
	public static function get_excluded_content() {
		$exclude = array_merge(
			array(
				'post',
				'page',
				'attachment',
				'revision',
				'nav_menu_item',
			),
			self::get_ms_post_types()
		);

		return apply_filters( 'ms_model_rule_custom_post_type_group_get_excluded_content', $exclude );
	}

	/**
	 * Get post types that are part of this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return array The plugin core post types.
	 */
	public static function get_ms_post_types() {
		$cpts = array(
			MS_Model_Membership::$POST_TYPE,
			MS_Model_Invoice::$POST_TYPE,
			MS_Model_Communication::$POST_TYPE,
			MS_Model_Coupon::$POST_TYPE,
			MS_Model_Membership_Relationship::$POST_TYPE,
			MS_Model_Event::$POST_TYPE,
		);

		return apply_filters( 'ms_model_rule_custom_post_type_group_get_ms_post_types', $cpts );
	}

	/**
	 * Get custom post types.
	 *
	 * Excludes membership plugin and default wp post types.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_custom_post_types() {
		$cpts = get_post_types();
		$excluded = self::get_excluded_content();

		return apply_filters(
			'ms_model_rule_custom_post_type_group_get_custom_post_types',
			array_diff( $cpts, $excluded )
		);
	}
}