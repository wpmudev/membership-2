<?php
/**
 * Add-on: Exclude membership system pages from search results.
 *
 * @since  1.0.1.0
 */
class MS_Addon_ExcludeFromSearch extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.1.0
	 */
	const ID = 'addon_excludefromsearch';

	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since  1.0.1.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initialises the Add-on. Always executed.
	 *
	 * @since  1.0.1.0
	 */
	public function init() {
		if ( self::is_active() ) {
			$this->add_filter( 'pre_get_posts', 'exclude_pages_from_search' );
		}
	}

	/**
	 * Registers the Add-On.
	 *
	 * @since  1.0.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' 			=> __( 'Exclude system pages from Search', 'membership2' ),
			'description' 	=> __( 'Excludes the membership system pages from search results.', 'membership2' ),
			'icon' 			=> 'wpmui-fa wpmui-fa-search',
		);

		return $list;
	}

	/**
	 * Excludes pages from site search by filtering the WP query.
	 *
	 * Related Action Hooks:
	 * - pre_get_posts
	 *
	 * @param WP_Query $wp_query The WP_Query object to filter.
	 */
	public function exclude_pages_from_search( $wp_query ) {
		if ( !$this->is_frontend_search ( $wp_query ) ) {
			return;
		}
		$denied_ids = array();
		$page_types = MS_Model_Pages::get_page_types();
		$i 			= 0;
		foreach ( $page_types as $key => $val ) {
			$denied_ids[$i] = MS_Model_Pages::get_setting( $key );
            $i++;
		}

		$denied_ids = array_unique( $denied_ids, SORT_NUMERIC );

		// Tell the WP query which pages are actually off limit for the user.
		$wp_query->query_vars['post__not_in'] = array_merge(
			$wp_query->query_vars['post__not_in'],
			$denied_ids
		);

		do_action(
			'ms_rule_page_model_exclude_from_search',
			$wp_query,
			$this
		);
	}

	/**
	 * Examines the passed WP query object to check if it is for frontend search.
	 *
	 * @param $wp_query \WP_Query The query object to examine.
	 * @return bool
	 */
	private function is_frontend_search( $wp_query ) {
		return !$wp_query->is_admin && $wp_query->is_search();
	}

}