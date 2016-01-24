<?php
/**
 * Membership URL Group Rule class.
 *
 * Persisted by Membership class.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Rule_Url_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since  1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Url::RULE_ID;

	/**
	 * A list of all URLs that are allowed by the current membership.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	protected $_allowed_urls = null;

	/**
	 * Returns the active flag for a specific rule.
	 * State depends on Add-on
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS );
	}

	/**
	 * Verify access to the current content.
	 *
	 * @since  1.0.0
	 *
	 * @param int $id The post/CPT ID to verify access. Defaults to current URL.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id, $admin_has_access = true ) {
		$has_access = null;

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			if ( ! empty( $id ) ) {
				$url = get_permalink( $id );
			} else {
				$url = MS_Helper_Utility::get_current_url();
			}

			if ( ! $this->has_rule_for_url( $url ) ) { return null; }

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
				$accessible = $this->get_accessible_urls();
				if ( $this->check_url_expression_match( $url, $accessible ) ) {
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
			$id,
			$this
		);
	}

	/**
	 * Verify if current url has protection rules.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean True if has access, false otherwise.
	 */
	protected function has_rule_for_url( $url ) {
		$has_rules = false;

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @param string $url The url to match.
	 * @param string[] $check_list The url list to verify match.
	 * @return boolean True if matches.
	 */
	public function check_url_expression_match( $url, $check_list ) {
		$match = false;

		$check_list = lib3()->array->get( $check_list );
		if ( count( $check_list ) ) {
			$check_list = array_map( 'strtolower', $check_list );
			$check_list = array_map( 'trim', $check_list );

			$url = strtolower( $url );
			foreach ( $check_list as $check ) {
				if ( $match ) { break; }

				if ( false !== strpos( $url, $check ) ) {
					$match = true;
				}
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 * @param string $id The content id to set access to.
	 * @param int $access The access status to set.
	 */
	public function set_access( $hash, $access ) {
		if ( $this->is_base_rule ) {
			/*
			 * Base rule cannot modify URL access via this function!
			 * Values of the base-rule are modified via a special Ajax handler
			 * that directly calls `add_url()`
			 *
			 * @see MS_Rule_Url::process_form()
			 */
			return;
		}

		if ( empty( $access ) ) {
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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

	/**
	 * Returns a list with all accessible URLs.
	 *
	 * @since  1.0.0
	 * @param string $hash The URL-hash.
	 */
	public function get_accessible_urls() {
		$accessible_Urls = $this->get_protected_urls();
		foreach ( $accessible_Urls as $key => $access ) {
			if ( empty( $this->rule_value[$key] ) ) {
				unset( $accessible_Urls[$key] );
			}
		}

		return $accessible_Urls;
	}
}