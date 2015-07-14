<?php
/**
 * Membership Page Rule class.
 *
 * Persisted by Membership class.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Rule_Page_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since  1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Page::RULE_ID;

	/**
	 * Initialize the rule.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 */
	public function protect_content() {
		parent::protect_content();

		$this->add_filter( 'get_pages', 'protect_pages', 99 );
	}

	/**
	 * Filters protected pages.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @return int The page id, or null if it is not a page.
	 */
	private function get_current_page_id() {
		$page_id = null;
		$post = get_queried_object();

		if ( is_a( $post, 'WP_Post' ) && 'page' == $post->post_type )  {
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
	 * @since  1.0.0
	 *
	 * @param int $id The page_id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id, $admin_has_access = true ) {
		$has_access = null;

		if ( empty( $id ) ) {
			$id = $this->get_current_page_id();
		} else {
			$post = get_post( $id );
			if ( ! is_a( $post, 'WP_Post' ) || 'page' != $post->post_type )  {
				$id = 0;
			}
		}

		if ( ! empty( $id ) ) {
			$has_access = false;
			// Membership special pages has access
			if ( MS_Model_Pages::is_membership_page( $id ) ) {
				$has_access = true;
			} else {
				$has_access = parent::has_access( $id, $admin_has_access );
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Function_Reference/get_pages
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		/**
		 * The 'hierarchial' flag messes up the offset by skipping some children
		 * in some cases (i.e. it will always skip pages until the first result
		 * is a top-level page). We have to get all pages from 0 and paginate
		 * manually...
		 */
		$offset = absint( $args['offset'] );
		$limit = $offset + absint( $args['number'] );
		$args['offset'] = 0;
		$args['number'] = $limit;

		$args = $this->get_query_args( $args );

		$pages = get_pages( $args );
		$contents = array();
		if ( 0 == $limit ) { $limit = count( $pages ); }

		for ( $num = $offset; $num < $limit; $num += 1 ) {
			if ( ! isset( $pages[$num] ) ) { continue; }

			$content = $pages[$num];
			$name = $content->post_title;

			$parent = get_post( $content->post_parent );
			for ( $level = 0; $level < 5 && $parent; $level += 1 ) {
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
	 * @since  1.0.0
	 *
	 * @param string $args The query post args.
	 *     @see @link http://codex.wordpress.org/Function_Reference/get_pages
	 * @return array The parsed args.
	 */
	public function get_query_args( $args = null ) {
		return parent::prepare_query_args( $args, 'get_pages' );
	}

	/**
	 * Exclude the special Membership2 pages from the results as they
	 * cannot be protected.
	 *
	 * @since  1.0.0
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