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
 * Membership URL Group Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Url_Group extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_URL_GROUP;

	/**
	 * Strip query strings from url before testing.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean $strip_query_string
	 */
	protected $strip_query_string;

	/**
	 * Is regular expression indicator.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean $is_regex
	 */
	protected $is_regex = true;

	/**
	 * A list of all URLs that are allowed by the current membership.
	 *
	 * @since  1.0.4.4
	 *
	 * @var array
	 */
	protected $_allowed_urls = null;

	/**
	 * Verify access to the current content.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Optional. The post/CPT ID to verify access. Defaults to current URL.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $post_id = null ) {
		$has_access = null;

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			if ( ! $this->has_rule_for_current_url() ) { return null; }

			if ( ! empty( $post_id ) ) {
				$url = get_permalink( $post_id );
			} else {
				$url = MS_Helper_Utility::get_current_url();
			}

			if ( $this->strip_query_string ) {
				$url = current( explode(  '?', $url ) );
			}

			$exclude = apply_filters(
				'ms_model_rule_url_group_excluded_urls',
				array()
			);

			// Check for exclude list.
			if ( $this->check_url_expression_match( $url, $exclude ) ) {
				$has_access = true;
			} else {
				// The URL is protected and has no exception. Deny it by default.
				$has_access = false;

				// Check for URL group.
				if ( $this->check_url_expression_match( $url, $this->get_allowed_urls() ) ) {
					if ( $this->get_membership()->is_base() ) {
						// For guests all defined URL groups are denied.
						$has_access = false;
					} else {
						$has_access = true;
					}
				}
			}
		}

		return apply_filters(
			'ms_model_rule_url_group_has_access',
			$has_access,
			$post_id,
			$this
		);
	}

	/**
	 * Verify if current url has protection rules.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if has access, false otherwise.
	 */
	protected function has_rule_for_current_url() {
		$has_rules = false;

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			$url = MS_Helper_Utility::get_current_url();
			if ( $this->strip_query_string ) {
				$url = current( explode(  '?', $url ) );
			}

			if ( $this->check_url_expression_match( $url, $this->get_urls() ) ) {
				$has_rules = true;
			}
		}

		return apply_filters(
			'ms_model_rule_url_group_has_access',
			$has_rules,
			$this
		);
	}

	/**
	 * Verify if a post/custom post type has protection rules.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if has access, false otherwise.
	 */
	public function has_rule_for_post( $post_id ) {
		$has_rules = false;

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			$url = get_permalink( $post_id );
			if ( $this->strip_query_string ) {
				$url = current( explode(  '?', $url ) );
			}

			if ( $this->check_url_expression_match( $url, $this->get_urls() ) ) {
				$has_rules = true;
			}
		}

		return apply_filters(
			'ms_model_rule_url_group_has_rule_for_post',
			$has_rules,
			$this
		);
	}

	/**
	 * Check url expression match.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The url to match.
	 * @param string[] $check_list The url list to verify match.
	 * @return boolean True if matches.
	 */
	public function check_url_expression_match( $url, $check_list ) {
		$match = false;

		if ( is_array( $check_list ) && ! empty( $check_list ) ) {
			$check_list = array_map( 'strtolower', $check_list );
			$check_list = array_map( 'trim', $check_list );

			if ( $this->is_regex ) {
				// Use regex to find match.
				foreach ( $check_list as $check_url ) {
					if ( mb_stripos( $check_url, '\/' ) !== false ) {
						$match_string = regescape( $check_url );
					} else {
						$match_string = $check_url;
					}
					$match_string = "#{$match_string}#i";

					if ( preg_match( $match_string, $url ) ) {
						$match = true;
						break;
					}
				}
			} else {
				// Straight match.
				$check_list = array_merge(
					$check_list,
					array_map( 'untrailingslashit', $check_list )
				);

				if ( in_array( strtolower( $url ), $check_list ) ) {
					$match = true;
				}
			}
		}

		return apply_filters(
			'ms_model_rule_url_group_check_url_expression_match',
			$match,
			$url,
			$check_list,
			$this
		);
	}

   /**
	* Count protection rules quantity.
	*
	* @since 1.0.0
	*
	* @param bool $has_access_only Optional. Count rules for has_access status only.
	* @return int $count The rule count result.
	*/
	public function count_rules( $has_access_only = true ) {
		$count = count( $this->rule_value );

		return apply_filters(
			'ms_model_rule_url_group_count_rules',
			$count,
			$this
		);
	}

	/**
	 * Get the total content count.
	 * Used in Dashboard to display how many special pages are protected.
	 *
	 * @since 1.0.4.4
	 *
	 * @param $args Ignored
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$count = count( $this->get_urls() );

		return apply_filters(
			'ms_model_rule_url_group_get_content_count',
			$count,
			$args
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 * @param $args The filter args
	 *
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$contents = array();

		$url_list = $this->get_urls();

		foreach ( $this->rule_value as $id => $value ) {
			if ( ! isset( $url_list[$id] ) ) {
				continue;
			}

			$content = new StdClass();
			$content->id = $id;
			$content->type = MS_Model_Rule::RULE_TYPE_URL_GROUP;
			$content->name = $url_list[$id];
			$content->url = $url_list[$id];
			$content->access = $this->get_rule_value( $content->id );
			$contents[] = $content;
		}

		return apply_filters(
			'ms_model_rule_url_group_get_contents',
			$contents
		);
	}

	/**
	 * Returns a list of all protected URLs.
	 *
	 * @since  1.0.4.4
	 *
	 * @return array
	 */
	public function get_urls() {
		static $Urls = null;

		if ( null === $Urls ) {
			$base_rules = MS_Model_Membership::get_base()->rules;
			if ( isset( $base_rules[ MS_Model_Rule::RULE_TYPE_URL_GROUP ] ) ) {
				$Urls = $base_rules[ MS_Model_Rule::RULE_TYPE_URL_GROUP ]->rule_value;
			}

			$Urls = WDev()->get_array( $Urls );
			$Urls = apply_filters(
				'ms_model_rule_url_group_get_urls',
				$Urls
			);
		}

		return $Urls;
	}

	/**
	 * Generates a list of URLs that are allowed by the current membership.
	 * This is always a sub-set of the get_urls() list.
	 *
	 * @since  1.0.4.4
	 *
	 * @return array
	 */
	public function get_allowed_urls() {
		if ( null === $this->_allowed_urls ) {
			$urls = $this->get_urls();
			$this->_allowed_urls = array();

			foreach ( $urls as $id => $url ) {
				if ( $this->get_rule_value( $id ) ) {
					$this->_allowed_urls[$id] = $url;
				}
			}

			$this->_allowed_urls = apply_filters(
				'ms_model_rule_url_group_get_allowed_urls',
				$this->_allowed_urls
			);
		}

		return $this->_allowed_urls;
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'rule_value':
					if ( ! is_array( $value ) ) {
						$items = explode( PHP_EOL, $value );
						$value = array();
						foreach ( $items as $line => $url ) {
							$value['url' . $line] = $url;
						}
					}

					$this->rule_value = array_filter(
						array_map( 'trim', $value )
					);
					break;

				case 'strip_query_string':
				case 'is_regex':
					$this->$property = $this->validate_bool( $value );
					break;

				default:
					parent::__set( $property, $value );
					break;
			}
		}

		do_action(
			'ms_model_rule_url_group__set_after',
			$property,
			$value,
			$this
		);
	}
}