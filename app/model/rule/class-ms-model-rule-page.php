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
class MS_Model_Rule_Page extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_PAGE;

	/**
	 * Membership relationship start date.
	 *
	 * @since 1.0.0
	 *
	 * @var string $start_date
	 */
	protected $start_date;

	/**
	 * Set initial protection (front-end only)
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Membership_Relationship $ms_relationship Optional. The membership relationship.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->start_date = $ms_relationship->start_date;
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
			'ms_model_rule_page_protect_pages_rule_value',
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

			// Dripped content.
			if ( MS_Model_Membership::TYPE_DRIPPED === $membership->type ) {
				if ( $this->has_dripped_rules( $page->ID )
					&& ! $this->has_dripped_access( $this->start_date, $page->ID )
				) {
					unset( $pages[ $key ] );
				}
			}
		}

		return apply_filters(
			'ms_model_rule_page_protect_pages',
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
			'ms_model_rule_page_get_current_page_id',
			$page_id,
			$this
		);
	}

	/**
	 * Verify access to the current page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page_id Optional. The page_id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $page_id = null ) {
		$has_access = null;

		if ( empty( $page_id ) ) {
			$page_id = $this->get_current_page_id();
		}
		else {
			$post = get_post( $page_id );
			if ( ! is_a( $post, 'WP_Post' ) || $post->post_type != 'page' )  {
				$page_id = 0;
			}
		}

		if ( ! empty( $page_id ) ) {
			$has_access = false;
			$has_access = parent::has_access( $page_id );

			// Membership special pages has access
			if ( MS_Factory::load( 'MS_Model_Pages' )->is_ms_page( $page_id ) ) {
				$has_access = true;
			}
		}

		return apply_filters(
			'ms_model_rule_page_has_access',
			$has_access,
			$page_id,
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

		return apply_filters(
			'ms_model_rule_page_has_dripped_rules',
			parent::has_dripped_rules( $page_id ),
			$this
		);
	}

	/**
	 * Verify access to dripped content.
	 *
	 * The MS_Helper_Period::current_date may be simulating a date.
	 *
	 * @since 1.0.0
	 * @param string $start_date The start date of the member membership.
	 * @param string $id The content id to verify dripped access.
	 */
	public function has_dripped_access( $start_date, $page_id = null ) {
		$has_access = false;

		if ( empty( $page_id ) ) {
			$page_id = $this->get_current_page_id();
		}

		$has_access = parent::has_dripped_access( $start_date, $page_id );

		return apply_filters(
			'ms_model_rule_page_has_dripped_access',
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
	 *     @see @link http://codex.wordpress.org/Function_Reference/get_pages
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$count = 0;
		$args = self::get_query_args( $args );
		$posts = get_pages( $args );

		$count = count( $posts );

		return apply_filters(
			'ms_model_rule_page_get_content_count',
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
		$args = self::get_query_args( $args );

		$pages = get_pages( $args );

		foreach ( $pages as $content ) {
			$content->id = $content->ID;
			$content->type = MS_Model_RULE::RULE_TYPE_PAGE;
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
			'ms_model_rule_page_get_contents',
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
		$defaults = array(
			'number' => false,
			'offset' => 0,
			'hierarchical' => 1,
			'sort_column' => 'post_title',
			'sort_order' => 'ASC',
			'post_type' => 'page',
		);

		$status = ! empty( $args['rule_status'] ) ? $args['rule_status'] : null;

		switch ( $status ) {
			case MS_Model_Rule::FILTER_HAS_ACCESS;
				$args['include'] = array_keys( $this->rule_value, true );
				break;

			case MS_Model_Rule::FILTER_NO_ACCESS;
				$args['include'] = array_keys( $this->rule_value, false );
				break;

			case MS_Model_Rule::FILTER_PROTECTED;
				$args['include'] = array_keys( $this->rule_value, true );
				break;

			case MS_Model_Rule::FILTER_NOT_PROTECTED;
				$args['exclude'] = array_keys( $this->rule_value, true );
				break;

			default:
				// If not visitor membership, just show protected content
				if ( ! $this->rule_value_invert ) {
					$args['include'] = array_keys( $this->rule_value );
				}
				break;
		}

		$args = wp_parse_args( $args, $defaults );

		return apply_filters(
			'ms_model_rule_page_get_query_args',
			$args,
			$this
		);
	}

	/**
	 * Get page content array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $array The query args. @see self::get_query_args()
	 * @return array {
	 *     @type int $key The content ID.
	 *     @type string $value The content title.
	 * }
	 */
	public function get_content_array( $args = null ) {
		$cont = array();
		$args = self::get_query_args( $args );
		$contents = get_pages( $args );
		$pages = array();

		foreach ( $contents as $content ) {
			$pages[$content->ID] = $content;
		}

		foreach ( $contents as $page_id => $content ) {
			$level = 0;
			$parent = $content;
			while ( $parent->post_parent ) {
				$parent = $pages[$parent->post_parent];
				$level += 1;
			}

			$cont[ $content->ID ] = str_repeat( '&nbsp;&mdash;&nbsp;', $level ) . $content->post_title;
		}

		return apply_filters(
			'ms_model_rule_page_get_content_array',
			$cont,
			$this
		);
	}

}