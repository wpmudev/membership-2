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
class MS_Model_Rule_Special extends MS_Model_Rule {

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
	 * @var array
	 */
	protected $content = null;

	/**
	 * Analysis information on which page type was detected.
	 * @var string
	 */
	public $matched_type = '';

	/**
	 * Checks if the current page is a special page that can be handled by this
	 * rule
	 *
	 * @since  1.0.4
	 * @return bool
	 */
	static public function is_special_page() {
		static $Result = null;

		if ( null === $Result ) {
			$Result = is_home()
				|| is_front_page()
				|| is_404()
				|| is_search()
				|| is_archive()
				|| is_author()
				|| is_date()
				|| is_time();
		}

		return $Result;
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.4
	 *
	 * @param MS_Model_Membership_Relationship $ms_relationship Optional. The membership relationship.
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
			if ( ! $this->has_rule_for_current_page() ) { return null; }

			$has_access = $this->check_current_page( $this->rule_value );
		}

		return apply_filters(
			'ms_model_rule_special_has_access',
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
	 * @return bool
	 */
	public function has_rule_for_current_page() {
		static $Result = null;

		if ( null === $Result ) {
			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SPECIAL_PAGES ) ) {
				$Result = false;
			} else {
				$base = $this->get_membership()->get_protected_content();
				$base_rule = $base->get_rule( $this->rule_type );
				$Result = $this->check_current_page( $base_rule->rule_value );
			}
		}

		return $Result;
	}

	/**
	 * Checks if the current page can be accessed by the specified rules
	 *
	 * @since  1.0.4
	 * @param  array $rules List of allowed pages.
	 * @return bool
	 */
	protected function check_current_page( $rules ) {
		$result = false;

		foreach ( $rules as $key => $active ) {
			if ( ! $active ) { continue; }

			switch ( $key ) {
				case 'archive': $result = is_archive(); break;
				case 'author': $result = is_author(); break;
				case 'date': $result = is_date(); break;
				case 'year': $result = is_year(); break;
				case 'month': $result = is_month(); break;
				case 'day': $result = is_day(); break;
				case 'time': $result = is_time(); break;
				case 'front': $result = is_front_page(); break;
				case 'home': $result = is_home(); break;
				case 'notfound': $result = is_404(); break;
				case 'search': $result = is_search(); break;
				case 'single': $result = is_singular(); break;
			}

			if ( $result ) {
				$this->matched_type = $key;
				break;
			}
		}

		return apply_filters(
			'ms_model_rule_special_check_current_page',
			$result,
			$rules
		);
	}

	/**
	 * Returns a list of special pages that can be configured by this rule.
	 *
	 * @since  1.0.4
	 * @param  bool $flat If set to true then all pages are in the same
	 *      hierarchy (no sub-arrays).
	 * @return array List of special pages.
	 */
	protected function get_special_pages( $flat = false ) {
		if ( ! is_array( $this->content ) ) {
			$this->content = array();
			$front_type = get_option( 'show_on_front' );

			$front_url = get_home_url();
			if ( 'page' === $front_type ) {
				$home_url = get_permalink( get_option( 'page_for_posts' ) );
			} else {
				$home_url = $front_url;
			}

			$base = $this->get_membership()->get_protected_content();
			$show_all = $this->membership_id === $base->id;

			$arch_year = get_year_link( '' );
			$arch_month = get_month_link( '', '' );
			$arch_day = get_day_link( '', '', '' );
			$arch_hour = add_query_arg( 'hour', '15', $arch_day );

			$this->content['archive'] = array();
			$this->content['single'] = array();

			$this->content['archive']['archive'] = (object) array(
				'label' => __( 'Any Archive page', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->content['archive']['author'] = (object) array(
				'label' => __( 'Author Archives', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->content['archive']['date'] = (object) array(
				'label' => __( 'Any Date or Time Archive', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->content['archive']['year'] = (object) array(
				'label' => __( 'Archive: Year', MS_TEXT_DOMAIN ),
				'url' => $arch_year,
			);
			$this->content['archive']['month'] = (object) array(
				'label' => __( 'Archive: Month', MS_TEXT_DOMAIN ),
				'url' => $arch_month,
			);
			$this->content['archive']['day'] = (object) array(
				'label' => __( 'Archive: Day', MS_TEXT_DOMAIN ),
				'url' => $arch_day,
			);
			$this->content['archive']['time'] = (object) array(
				'label' => __( 'Archive: Time', MS_TEXT_DOMAIN ),
				'url' => $arch_hour,
			);

			$this->content['single']['front'] = (object) array(
				'label' => __( 'Front Page', MS_TEXT_DOMAIN ),
				'url' => $front_url,
			);
			$this->content['single']['home'] = (object) array(
				'label' => __( 'Blog Index', MS_TEXT_DOMAIN ),
				'url' => $home_url,
			);
			$this->content['single']['notfound'] = (object) array(
				'label' => __( '404 Not Found', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->content['single']['search'] = (object) array(
				'label' => __( 'Search Results', MS_TEXT_DOMAIN ),
				'url' => '',
			);
			$this->content['single']['single'] = (object) array(
				'label' => __( 'Any single page or post', MS_TEXT_DOMAIN ),
				'url' => '',
			);

			if ( ! $show_all ) {
				$base_rule = $base->get_rule( $this->rule_type );
				$base_values = $base_rule->rule_value;

				foreach ( $this->content['archive'] as $key => $val ) {
					if ( ! isset( $base_values[$key] ) ) { unset( $this->content['archive'][$key] ); }
				}
				foreach ( $this->content['single'] as $key => $val ) {
					if ( ! isset( $base_values[$key] ) ) { unset( $this->content['single'][$key] ); }
				}
			}
		}

		if ( $flat ) {
			$special_pages = $this->content['single'] + $this->content['archive'];
		} else {
			$special_pages = array();
			foreach ( $this->content['single'] as $key => $item ) {
				$special_pages[$key] = $item->label;
			}

			$archive_key = __( 'Archives', MS_TEXT_DOMAIN );
			$special_pages[$archive_key] = array();
			foreach ( $this->content['archive'] as $key => $item ) {
				$special_pages[$archive_key][$key] = $item->label;
			}
		}

		return $special_pages;
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
		$special_pages = $this->get_special_pages( true );
		$count = count( $special_pages );

		$status = ! empty( $args['rule_status'] ) ? $args['rule_status'] : null;
		switch ( $status ) {
			case MS_Model_Rule::FILTER_HAS_ACCESS:
			case MS_Model_Rule::FILTER_NO_ACCESS:
			case MS_Model_Rule::FILTER_PROTECTED:
			case MS_Model_Rule::FILTER_NOT_PROTECTED:
			default:
				break;
		}

		return apply_filters(
			'ms_model_rule_special_get_content_count',
			$count,
			$args
		);
	}

	/**
	 * Get content to protect.
	 * Used in Dashboard to display a list of special pages.
	 *
	 * @since 1.0.4
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$special_pages = $this->get_special_pages( true );
		$contents = array();

		foreach ( $special_pages as $id => $data ) {
			$content = (object) array();

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

		return apply_filters(
			'ms_model_rule_special_get_contents',
			$contents,
			$this
		);
	}

	/**
	 * Get page content array.
	 *
	 * @since 1.0.4
	 *
	 * @param array $array The query args. @see self::get_query_args()
	 * @return array {
	 *     @type int $key The content ID.
	 *     @type string $value The content title.
	 * }
	 */
	public function get_content_array( $args = null ) {
		$content = $this->get_special_pages();

		return apply_filters(
			'ms_model_rule_special_get_content_array',
			$content,
			$this
		);
	}

}