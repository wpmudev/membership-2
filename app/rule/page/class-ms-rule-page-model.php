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
 * Membership Page Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Rule_Page_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Page::RULE_ID;

	/**
	 * Initialize the rule.
	 *
	 * @since 1.1.0
	 * @param int $membership_id
	 */
	public function __construct( $membership_id ) {
		parent::__construct( $membership_id );

		$this->add_filter(
			'ms_rule_exclude_items-' . $this->rule_type,
			'exclude_items',
			10, 2
		);
	}

	/**
	 * Set initial protection (front-end only)
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. The membership relationship.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->add_filter( 'get_pages', 'protect_pages', 99 );
	}

	/**
	 * Filters protected pages.
	 *
	 * @since 1.0.0
	 *
	 * Related action hook:
	 * - get_pages
	 *
	 * @param array $pages The array of pages to filter.
	 * @return array Filtered array which doesn't include prohibited pages.
	 */
	public function protect_pages( $pages ) {
		$rule_value = apply_filters(
			'ms_rule_page_model_protect_pages_rule_value',
			$this->rule_value
		);
		$membership = $this->get_membership();

		if ( ! is_array( $pages ) ) {
			$pages = (array) $pages;
		}

		foreach ( $pages as $key => $page ) {
			if ( ! self::has_access( $page->ID ) ) {
				unset( $pages[ $key ] );
			}
		}

		return apply_filters(
			'ms_rule_page_model_protect_pages',
			$pages,
			$this
		);
	}

	/**
	 * Get the current page id.
	 *
	 * @since 1.0.0
	 *
	 * @return int The page id, or null if it is not a page.
	 */
	private function get_current_page_id() {
		$page_id = null;
		$post = get_queried_object();

		if ( is_a( $post, 'WP_Post' ) && $post->post_type === 'page' )  {
			$page_id = $post->ID;
		}

		return apply_filters(
			'ms_rule_page_model_get_current_page_id',
			$page_id,
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
			$id = $this->get_current_page_id();
		} else {
			$post = get_post( $id );
			if ( ! is_a( $post, 'WP_Post' ) || $post->post_type != 'page' )  {
				$id = 0;
			}
		}

		if ( ! empty( $id ) ) {
			$has_access = false;
			// Membership special pages has access
			if ( MS_Model_Pages::is_membership_page( $id ) ) {
				$has_access = true;
			} else {
				$has_access = parent::has_access( $id );
			}
		}

		return apply_filters(
			'ms_rule_page_model_has_access',
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
	public function has_dripped_rules( $page_id = null ) {
		if ( empty( $page_id ) ) {
			$page_id = $this->get_current_page_id();
		}

		return parent::has_dripped_rules( $page_id );
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
		unset( $args['number'] );
		$args = $this->get_query_args( $args );
		$posts = get_pages( $args );

		$count = count( $posts );

		return apply_filters(
			'ms_rule_page_model_get_content_count',
			$count,
			$args
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Function_Reference/get_pages
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$args = $this->get_query_args( $args );

		if ( isset( $args['s'] ) ) {
			$matches = get_posts( $args );
		}
		$pages = get_pages( $args );

		foreach ( $pages as $content ) {
			$name = $content->post_title;

			$parent = get_post( $content->post_parent );
			while ( ! empty( $parent ) ) {
				$name = '&mdash; ' . $name;
				$parent = get_post( $parent->post_parent );
			}

			$content->id = $content->ID;
			$content->type = MS_Rule_Page::RULE_ID;
			$content->name = $name;
			$content->access = $this->get_rule_value( $content->id );

			$contents[ $content->id ] = $content;
		}

		return apply_filters(
			'ms_rule_page_model_get_contents',
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
	 *     @see @link http://codex.wordpress.org/Function_Reference/get_pages
	 * @return array The parsed args.
	 */
	public function get_query_args( $args = null ) {
		return parent::prepare_query_args( $args, 'get_pages' );
	}

	/**
	 * Exclude the special Protected-Content pages from the results as they
	 * cannot be protected.
	 *
	 * @since  1.1.0
	 * @param  array $excluded
	 * @param  array $args
	 * @return array
	 */
	public function exclude_items( $excluded, $args ) {
		static $Page_Ids = null;

		if ( null === $Page_Ids ) {
			$Page_Ids = array();
			$types = MS_Model_Pages::get_page_types();
			foreach ( $types as $type => $title ) {
				$Page_Ids[] = MS_Model_Pages::get_setting( $type );
			}
		}

		return array_merge( $excluded, $Page_Ids );
	}

}