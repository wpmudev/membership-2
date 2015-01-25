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
class MS_Rule_Url_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Url::RULE_ID;

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

			$exclude = apply_filters(
				'ms_rule_url_model_excluded_urls',
				array()
			);

			// Check for exclude list.
			if ( $this->check_url_expression_match( $url, $exclude ) ) {
				$has_access = true;
			} else {
				// The URL is protected and has no exception. Deny it by default.
				$has_access = false;

				// Check for URL group.
				if ( $this->check_url_expression_match( $url, $this->rule_value ) ) {
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
			'ms_rule_url_model_has_access',
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
			if ( $this->check_url_expression_match( $url, $this->get_protected_urls() ) ) {
				$has_rules = true;
			}
		}

		return apply_filters(
			'ms_rule_url_model_has_access',
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
			if ( $this->check_url_expression_match( $url, $this->get_protected_urls() ) ) {
				$has_rules = true;
			}
		}

		return apply_filters(
			'ms_rule_url_model_has_rule_for_post',
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

			// Straight match.
			$check_list = array_merge(
				$check_list,
				array_map( 'untrailingslashit', $check_list )
			);

			if ( in_array( strtolower( $url ), $check_list ) ) {
				$match = true;
			}
		}

		return apply_filters(
			'ms_rule_url_model_check_url_expression_match',
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
			'ms_rule_url_model_count_rules',
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
		$count = count( $this->get_protected_urls() );

		return apply_filters(
			'ms_rule_url_model_get_content_count',
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
		$protected_urls = $this->get_protected_urls();
		$membership_urls = $this->rule_value;

		$contents = array();
		foreach ( $membership_urls as $hash => $value ) {
			if ( ! isset( $protected_urls[$hash] ) ) {
				continue;
			}

			$content = new StdClass();
			$content->id = $hash;
			$content->type = MS_Rule_Url::RULE_ID;
			$content->name = $protected_urls[$hash];
			$content->url = $protected_urls[$hash];
			$content->access = $this->get_rule_value( $content->id );
			$contents[] = $content;
		}

		return apply_filters(
			'ms_rule_url_model_get_contents',
			$contents
		);
	}

	/**
	 * Set access status to content.
	 *
	 * @since 1.1.0
	 * @param string $id The content id to set access to.
	 * @param int $access The access status to set.
	 */
	public function set_access( $hash, $access ) {
		if ( $this->is_base_rule ) {
			// Base rule cannot modify URL access via this function!
			return;
		}

		if ( is_bool( $access ) ) {
			if ( $access ) {
				$access = MS_Model_Rule::RULE_VALUE_HAS_ACCESS;
			} else {
				$access = MS_Model_Rule::RULE_VALUE_NO_ACCESS;
			}
		}

		if ( $access == MS_Model_Rule::RULE_VALUE_NO_ACCESS ) {
			$this->delete_url( $hash );
		} else {
			$base_rule = MS_Model_Membership::get_base()->get_rule( $this->rule_type );
			$url = $base_rule->get_url_from_hash( $hash );
			$this->add_url( $url );
		}

		do_action( 'ms_rule_url_set_access', $hash, $access, $this );
	}

	/**
	 * Serializes this rule in a single array.
	 * We don't use the PHP `serialize()` function to serialize the whole object
	 * because a lot of unrequired and duplicate data will be serialized
	 *
	 * @since  1.1.0
	 * @return array The serialized values of the Rule.
	 */
	public function serialize() {
		$result = $this->rule_value;
		return $result;
	}

	/**
	 * Populates the rule_value array with the specified value list.
	 * This function is used when de-serializing a membership to re-create the
	 * rules associated with the membership.
	 *
	 * @since  1.1.0
	 * @param  array $values A list of allowed IDs.
	 */
	public function populate( $values ) {
		foreach ( $values as $hash => $url ) {
			$this->add_url( $url );
		}
	}

	/**
	 * Adds a new URL to the rule
	 *
	 * @since 1.1.0
	 * @param string $url
	 */
	public function add_url( $url ) {
		$url = trim( $url );

		// Index is a hash to prevent duplicate URLs in the list.
		$hash = md5( $url );
		$this->rule_value[ $hash ] = $url;
	}

	/**
	 * Removes an URL from the rule.
	 *
	 * An URL can be deleted either by specifying an Hash (prefered) or the
	 * plain-text URL. If a Hash value is specified the URL is always ignored.
	 *
	 * @since 1.1.0
	 * @param string $hash The URL-hash.
	 * @param string $url Optional. The plain-text URL.
	 */
	public function delete_url( $hash, $url = null ) {
		if ( ! empty( $hash ) ) {
			unset( $this->rule_value[ $hash ] );
		} elseif ( ! empty( $url ) ) {
			$url = trim( $url );
			$hash = md5( $url );
			unset( $this->rule_value[ $hash ] );
		}
	}

	/**
	 * Returns the URL from a specific hash value.
	 *
	 * @since 1.1.0
	 * @param string $hash The URL-hash.
	 */
	public function get_url_from_hash( $hash ) {
		$url = '';

		$urls = $this->get_protected_urls();
		if ( isset( $urls[ $hash ] ) ) {
			$url = $urls[ $hash ];
		}

		return $url;
	}

	/**
	 * Returns a list with all protected URLs.
	 *
	 * @since 1.1.0
	 * @param string $hash The URL-hash.
	 */
	public function get_protected_urls() {
		static $Protected_Urls = null;

		if ( null === $Protected_Urls ) {
			$base_rule = MS_Model_Membership::get_base()->get_rule( $this->rule_type );
			$Protected_Urls = $base_rule->rule_value;
		}

		return $Protected_Urls;
	}
}