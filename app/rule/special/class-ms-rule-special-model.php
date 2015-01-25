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
 * Membership Special Pages Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.4
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Rule_Special_Model extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.4
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_SPECIAL;

	/**
	 * Available special pages
	 *
	 * @since 1.0.4
	 *
	 * @var array
	 */
	protected $_content = null;

	/**
	 * Analysis information on which page type was detected.
	 *
	 * @since 1.0.4
	 *
	 * @var string
	 */
	public $_matched_type = '';

	/**
	 * Cache the result of is_special_page
	 *
	 * @since 1.0.4.4
	 *
	 * @var bool
	 */
	public $_is_special = null;

	/**
	 * Cache the result of has_rule_for_current_page
	 *
	 * @since 1.0.4.4
	 *
	 * @var bool
	 */
	public $_has_rule = null;

	/**
	 * Set-up the Rule
	 *
	 * @since  1.1.0
	 */
	static public function prepare_class() {
		// Register the tab-output handler for the admin side
		MS_Factory::load( 'MS_Rule_Special_View' )->register();
	}

	/**
	 * Checks if the current page is a special page that can be handled by this
	 * rule
	 *
	 * @since  1.0.4
	 *
	 * @return bool
	 */
	static public function is_special_page() {
		if ( null === $this->_is_special ) {
			$this->_is_special = is_home()
				|| is_front_page()
				|| is_404()
				|| is_search()
				|| is_archive()
				|| is_author()
				|| is_date()
				|| is_time();
		}

		return $this->_is_special;
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.4
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. The membership relationship.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );
	}

	/**
	 * Verify access to the current page.
	 *
	 * @since 1.0.4
	 *
	 * @param int $page_id Optional. The page_id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $page_id = null ) {
		$has_access = null;

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SPECIAL_PAGES ) ) {
			if ( $this->has_rule_for_current_page() ) {
				$has_access = false;

				if ( $this->check_current_page( $this->rule_value ) ) {
					if ( ! $this->get_membership()->is_base() ) {
						$has_access = true;
					}
				}
			}
		}

		return apply_filters(
			'ms_rule_special_model_has_access',
			$has_access,
			$page_id,
			$this
		);
	}

	/**
	 * Checks if the current page is a special page and if the special page is
	 * protected by this rule.
	 *
	 * @since  1.0.4
	 *
	 * @return bool
	 */
	public function has_rule_for_current_page() {
		if ( null === $this->_has_rule ) {
			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SPECIAL_PAGES ) ) {
				$this->_has_rule = false;
			} else {
				$base = $this->get_membership()->get_base();
				$base_rule = $base->get_rule( $this->rule_type );
				$this->_has_rule = $this->check_current_page( $base_rule->rule_value );
			}
		}

		return $this->_has_rule;
	}

	/**
	 * Checks if the current page can be accessed by the specified rules
	 *
	 * @since  1.0.4
	 *
	 * @param  array $rules List of allowed pages.
	 * @return bool
	 */
	protected function check_current_page( $rules ) {
		$result = false;

		foreach ( $rules as $key => $active ) {
			if ( ! $active ) { continue; }

			/*
			 * The item order is critical, in case a page has multiple flags
			 * like "Front" and "Home" and "Archive".
			 * In this example "Archive" might be denied but "Front" allowed,
			 * so we have to define a hierarchy which flag is actually used.
			 */
			switch ( $key ) {
				case 'front': $result = is_front_page(); break;
				case 'home': $result = is_home(); break;
				case 'notfound': $result = is_404(); break;
				case 'search': $result = is_search(); break;
				case 'attachment': $result = is_attachment(); break;
				case 'single': $result = is_singular(); break;
				case 'archive': $result = is_archive(); break;
				case 'author': $result = is_author(); break;
				case 'date': $result = is_date(); break;
				case 'year': $result = is_year(); break;
				case 'month': $result = is_month(); break;
				case 'day': $result = is_day(); break;
				case 'time': $result = is_time(); break;
			}

			if ( $result ) {
				$this->matched_type = $key;
				break;
			}
		}

		return apply_filters(
			'ms_rule_special_model_check_current_page',
			$result,
			$rules
		);
	}

	/**
	 * Returns a list of special pages that can be configured by this rule.
	 *
	 * @since  1.0.4
	 *
	 * @return array List of special pages.
	 */
	protected function get_special_pages() {
		if ( ! is_array( $this->_content ) ) {
			$this->_content = array();
			$front_type = get_option( 'show_on_front' );

			$front_url = get_home_url();
			if ( 'page' === $front_type ) {
				$home_url = get_permalink( get_option( 'page_for_posts' ) );
			} else {
				$home_url = $front_url;
			}

			$arch_year = get_year_link( '' );
			$arch_month = get_month_link( '', '' );
			$arch_day = get_day_link( '', '', '' );
			$arch_hour = add_query_arg( 'hour', '15', $arch_day );

			// Archive pages
			$this->_content['archive'] = (object) array(
				'label' => __( 'Any Archive page', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->_content['author'] = (object) array(
				'label' => __( 'Author Archives', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->_content['date'] = (object) array(
				'label' => __( 'Any Date or Time Archive', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->_content['year'] = (object) array(
				'label' => __( 'Archive: Year', MS_TEXT_DOMAIN ),
				'url' => $arch_year,
			);
			$this->_content['month'] = (object) array(
				'label' => __( 'Archive: Month', MS_TEXT_DOMAIN ),
				'url' => $arch_month,
			);
			$this->_content['day'] = (object) array(
				'label' => __( 'Archive: Day', MS_TEXT_DOMAIN ),
				'url' => $arch_day,
			);
			$this->_content['time'] = (object) array(
				'label' => __( 'Archive: Time', MS_TEXT_DOMAIN ),
				'url' => $arch_hour,
			);

			// Singular pages
			$this->_content['front'] = (object) array(
				'label' => __( 'Front Page', MS_TEXT_DOMAIN ),
				'url' => $front_url,
			);
			$this->_content['home'] = (object) array(
				'label' => __( 'Blog Index', MS_TEXT_DOMAIN ),
				'url' => $home_url,
			);
			$this->_content['notfound'] = (object) array(
				'label' => __( '404 Not Found', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->_content['search'] = (object) array(
				'label' => __( 'Search Results', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->_content['single'] = (object) array(
				'label' => __( 'Any single page or post', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->_content['attachment'] = (object) array(
				'label' => __( 'Any attachment page', MS_TEXT_DOMAIN ),
				'url' => '',
			);
		}

		return $this->_content;
	}

	/**
	 * Get the total content count.
	 * Used in Dashboard to display how many special pages are protected.
	 *
	 * @since 1.0.4
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$args['posts_per_page'] = 0;
		$args['offset'] = false;
		$count = count( $this->get_contents( $args ) );

		return apply_filters(
			'ms_rule_special_model_get_content_count',
			$count,
			$args
		);
	}

	/**
	 * Get content to protect.
	 * Used in Dashboard to display a list of special pages.
	 *
	 * @since 1.0.4
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$special_pages = $this->get_special_pages();
		$contents = array();

		$filter = $this->get_exclude_include( $args );
		if ( is_array( $filter->include ) ) {
			$special_pages = array_intersect_key( $special_pages, array_flip( $filter->include ) );
		} elseif ( is_array( $filter->exclude ) ) {
			$special_pages = array_diff_key( $special_pages, array_flip( $filter->exclude ) );
		}

		foreach ( $special_pages as $id => $data ) {
			$content = (object) array();

			// Search the special page name...
			if ( ! empty( $args['s'] ) ) {
				if ( stripos( $data->label, $args['s'] ) === false ) {
					continue;
				}
			}

			$content->id = $id;
			$content->type = MS_Model_RULE::RULE_TYPE_SPECIAL;
			$content->name = $data->label;
			$content->post_title = $data->label;
			$content->url = $data->url;

			$content->access = $this->get_rule_value( $content->id );

			$content->delayed_period = $this->has_dripped_rules( $content->id );
			$content->avail_date = $this->get_dripped_avail_date(
				$content->id, MS_Helper_Period::current_date( null, true )
			);

			$contents[ $content->id ] = $content;
		}

		// If not visitor membership, just show protected content
		if ( ! $this->is_base_rule ) {
			$contents = array_intersect_key( $contents, $this->rule_value );
		}

		if ( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}

		if ( ! empty( $args['posts_per_page'] ) ) {
			$total = $args['posts_per_page'];
			$offset = ! empty( $args['offset'] ) ? $args['offset'] : 0;
			$contents = array_slice( $contents, $offset, $total );
		}

		return apply_filters(
			'ms_rule_special_model_get_contents',
			$contents,
			$this
		);
	}

}