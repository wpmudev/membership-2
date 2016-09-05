<?php
/**
 * Membership Member Capabilities Rule class.
 *
 * Persisted by Membership class.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Rule_MemberCaps_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since  1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_MemberCaps::RULE_ID;

	/**
	 * List of capabilities that are effectively used for the current user
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	static protected $real_caps = array();

	/**
	 * Caches the get_content_array output
	 *
	 * @var array
	 */
	protected $_content_array = null;

	/**
	 * Returns the active flag for a specific rule.
	 * State depends on Add-on
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		$def = MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS );
		$adv = MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV );
		return $def && $adv;
	}


	/**
	 * Initializes the object as early as possible
	 *
	 * @since  1.0.0
	 */
	public function prepare_obj() {
		$this->_content_array = null;
	}

	/**
	 * Set initial protection.
	 *
	 * @since  1.0.0
	 */
	public function protect_content() {
		parent::protect_content();

		$this->add_filter( 'user_has_cap', 'prepare_caps', 1, 4 );
		$this->add_filter( 'user_has_cap', 'modify_caps', 10, 4 );
	}

	/**
	 * Set initial protection.
	 *
	 * @since  1.0.0
	 */
	public function protect_admin_content() {
		parent::protect_admin_content();

		$this->add_filter( 'user_has_cap', 'prepare_caps', 1, 4 );
		$this->add_filter( 'user_has_cap', 'modify_caps', 10, 4 );
	}

	/**
	 * Verify access to the current content.
	 *
	 * Always returns null since this rule modifies the capabilities of the
	 * current user and does not directly block access to any page.
	 *
	 * @since  1.0.0
	 *
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id, $admin_has_access = true ) {
		return null;
	}

	/**
	 * Prepares the list of effective capabilities to use
	 *
	 * Relevant Action Hooks:
	 * - user_has_cap
	 *
	 * @since  1.0.0
	 *
	 * @param array   $allcaps An array of all the role's capabilities.
	 * @param array   $caps    Actual capabilities for meta capability.
	 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
	 */
	public function prepare_caps( $allcaps, $caps, $args, $user ) {
		global $wp_roles;

		if ( isset( self::$real_caps[$user->ID] ) ) {
			// Only run the init code once for each user-ID.
			return $allcaps;
		} else {
			// First get a list of the users default capabilities.
			self::$real_caps[$user->ID] = $allcaps;
		}

		$caps = $this->rule_value;

		if ( null === self::$real_caps ) {
			// First get a list of the users default capabilities.
			self::$real_caps = $allcaps;

			// Use the permissions of the first rule without checking.
			foreach ( $caps as $key => $value ) {
				self::$real_caps[$key] = $value;
			}
		} else {
			// Only add additional capabilities from now on...
			foreach ( $caps as $key => $value ) {
				if ( $value ) { self::$real_caps[$key] = 1; }
			}
		}

		return $allcaps;
	}

	/**
	 * Modify the users capabilities.
	 *
	 * Relevant Action Hooks:
	 * - user_has_cap
	 *
	 * @since  1.0.0
	 *
	 * @param array   $allcaps An array of all the role's capabilities.
	 * @param array   $caps    Actual capabilities for meta capability.
	 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
	 */
	public function modify_caps( $allcaps, $caps, $args, $user ) {
		if ( ! isset( self::$real_caps[$user->ID] ) ) {
			self::$real_caps[$user->ID] = $allcaps;
		}

		return apply_filters(
			'ms_rule_membercaps_model_modify_caps',
			self::$real_caps[$user->ID],
			$caps,
			$args,
			$user,
			$this
		);
	}

	/**
	 * Get a simple array of capabilties (e.g. for display in select lists)
	 *
	 * @since  1.0.0
	 * @global array $menu
	 *
	 * @return array {
	 *      @type string $id The id.
	 *      @type string $name The name.
	 * }
	 */
	public function get_capabilities( $args = null ) {
		if ( null === $this->_content_array ) {
			$this->_content_array = array();
			$member = MS_Model_Member::get_current_member();
			$capslist = $member->wp_user->allcaps;

			$ignored_caps = array(
				'level_10' => 1,
				'level_9' => 1,
				'level_8' => 1,
				'level_7' => 1,
				'level_6' => 1,
				'level_5' => 1,
				'level_4' => 1,
				'level_3' => 1,
				'level_2' => 1,
				'level_1' => 1,
				'level_0' => 1,
				'administrator' => 1,
			);

			$capslist = array_diff_assoc( $capslist, $ignored_caps );
			$capslist = array_keys( $capslist );

			/**
			 * Exclude certain capabilities for security reasons.
			 *
			 * @since  1.0.0
			 * @var array
			 */
			$exclude = apply_filters(
				'ms_rule_membercaps_model_exclude',
				array(
					MS_Plugin::instance()->controller->capability,
					'edit_plugins',
					'delete_plugins',
					'edit_files',
					'edit_users',
					'delete_users',
					'remove_users',
					'promote_users',
					'list_users',
				)
			);

			$capslist = array_diff( $capslist, $exclude );
			$this->_content_array = array_combine( $capslist, $capslist );

			// Make sure the rule_value only contains valid items.
			$rule_value = array_intersect_key(
				$this->rule_value,
				$this->_content_array
			);
			$this->rule_value = lib3()->array->get( $rule_value );

			// If not visitor membership, just show Membership2
			if ( ! $this->get_membership()->is_base() ) {
				$this->_content_array = array_intersect_key(
					$this->_content_array,
					$this->rule_value
				);
			}

			$this->_content_array = apply_filters(
				'ms_rule_membercaps_model_get_content_array',
				$this->_content_array,
				$this
			);
		}

		$contents = $this->_content_array;

		// Search the shortcode-tag...
		if ( ! empty( $args['s'] ) ) {
			foreach ( $contents as $key => $name ) {
				if ( false === stripos( $name, $args['s'] ) ) {
					unset( $contents[$key] );
				}
			}
		}

		$filter = self::get_exclude_include( $args );
		if ( is_array( $filter->include ) ) {
			$contents = array_intersect( $contents, $filter->include );
		} elseif ( is_array( $filter->exclude ) ) {
			$contents = array_diff( $contents, $filter->exclude );
		}

		// Pagination
		if ( ! empty( $args['posts_per_page'] ) && $args['posts_per_page'] > 0 ) {
			$total = $args['posts_per_page'];
			$offset = ! empty( $args['offset'] ) ? $args['offset'] : 0;
			$contents = array_slice( $contents, $offset, $total );
		}

		return $contents;
	}

	/**
	 * Get content to protect. An array of objects is returned.
	 *
	 * @since  1.0.0
	 * @param $args The query post args
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$contents = array();
		$caps = $this->get_capabilities( $args );

		foreach ( $caps as $key => $item ) {
			$content = (object) array();

			$content->id = $item;
			$content->title = $item;
			$content->name = $item;
			$content->post_title = $item;
			$content->type = MS_Rule_MemberCaps::RULE_ID;
			$content->access = $this->get_rule_value( $key );

			$contents[ $key ] = $content;
		}

		return apply_filters(
			'ms_rule_membercaps_model_get_contents',
			$contents,
			$args,
			$this
		);
	}

	/**
	 * Get the total content count.
	 * Used in Dashboard to display how many special pages are protected.
	 *
	 * @since  1.0.0
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
			'ms_rule_membercaps_model_get_content_count',
			$count,
			$args
		);
	}

}