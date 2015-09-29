<?php
/**
 * Membership Menu Rule class.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Rule_MenuItem_Model extends MS_Rule {

	/**
	 * An array that holds all menu-IDs that are available for the current user.
	 * This is static, so it has correct values even when multiple memberships
	 * are evaluated.
	 *
	 * @var array
	 */
	static protected $allowed_items = array();

	/**
	 * Rule type.
	 *
	 * @since  1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_MenuItem::RULE_ID;

	/**
	 * Returns the active flag for a specific rule.
	 * State depends on Add-on
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		return 'item' == $settings->menu_protection;
	}

	/**
	 * Initialize the rule
	 *
	 * @since  1.0.0
	 */
	public function prepare_obj() {
		$this->add_filter( 'ms_rule_menuitem_listtable_url', 'view_url' );
	}

	/**
	 * Verify access to the current content.
	 *
	 * This rule will return NULL (not relevant), because the menus are
	 * protected via a wordpress hook instead of protecting the current page.
	 *
	 * @since  1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id, $admin_has_access = true ) {
		return null;
	}

	/**
	 * Set initial protection.
	 *
	 * @since  1.0.0
	 */
	public function protect_content() {
		parent::protect_content();

		$this->add_filter( 'wp_setup_nav_menu_item', 'prepare_menuitem', 10, 3 );
		$this->add_filter( 'wp_get_nav_menu_items', 'protect_menuitems', 10, 3 );
	}

	/**
	 * Checks if the specified menu-ID is allowed by this rule.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $item The menu item object.
	 * @return bool
	 */
	protected function can_access_menu( $item, $admin_has_access = true ) {
		$result = false;

		if ( parent::has_access( $item->ID, $admin_has_access ) ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Set the protection flag for each menu item.
	 *
	 * This function is called before function protect_menuitems() below.
	 * Here we evaluate each menu item by itself to see if the user has access
	 * to the menu item and collect all accessible menu items in a static/shared
	 * array so we have correct information when evaluating multiple memberships.
	 *
	 * Relevant Action Hooks:
	 * - wp_setup_nav_menu_item
	 *
	 * @since  1.0.0
	 *
	 * @param array $item A single menu item.
	 * @param mixed $args The menu select args.
	 */
	public function prepare_menuitem( $item ) {
		if ( ! empty( $item ) ) {
			if ( $this->can_access_menu( $item ) ) {
				self::$allowed_items[$item->ID] = $item->ID;
			}
		}

		return apply_filters(
			'ms_rule_menuitem_model_prepare_menuitems',
			$item,
			$this
		);
	}

	/**
	 * Remove menu items that are protected.
	 *
	 * Menu-Item protection is split into two steps to ensure correct
	 * menu-visibility when users are members of multiple memberships.
	 * http://premium.wpmudev.org/forums/topic/multiple-membership-types-defaults-to-less-access-protected-content
	 *
	 * Relevant Action Hooks:
	 * - wp_get_nav_menu_items
	 *
	 * @since  1.0.0
	 *
	 * @param array $items The menu items.
	 * @param object $menu The menu object.
	 * @param mixed $args The menu select args.
	 */
	public function protect_menuitems( $items, $menu, $args ) {
		if ( ! empty( $items ) ) {
			foreach ( $items as $key => $item ) {
				if ( ! isset( self::$allowed_items[ $item->ID ] ) ) {
					unset( $items[ $key ] );
				}
			}
		}

		return apply_filters(
			'ms_rule_menuitem_model_protect_menuitems',
			$items,
			$menu,
			$args,
			$this
		);
	}

	/**
	 * Reset the rule value data. This does not remove all items but only the
	 * items that belong to the specified menu.
	 *
	 * @since  1.0.0
	 * @param $menu_id The menu_id to reset children menu item rules.
	 * @return array The reset rule value.
	 */
	public function reset_menu_rule_values( $menu_id ) {
		$items = wp_get_nav_menu_items( $menu_id );

		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				unset( $this->rule_value[ $item->ID ] );
			}
		}

		$this->rule_value = apply_filters(
			'ms_rule_menuitem_model_reset_menu_rule_values',
			$this->rule_value,
			$this
		);
	}

	/**
	 * Menu table always displays all menu items on one page.
	 *
	 * @since  1.0.0
	 * @param  array $option [description]
	 * @return int Number of items to display on one page
	 */
	protected function get_items_per_page( $option ) {
		return 0;
	}

	/**
	 * Customize the URL used for the view-list
	 *
	 * @since  1.0.0
	 * @param  string $url The URL
	 * @return string The URL
	 */
	public function view_url( $url ) {
		$menu_id = MS_Controller::get_request_field( 'menu_id', 0, 'REQUEST' );
		$url = esc_url_raw( add_query_arg( 'menu_id', $menu_id, $url ) );
		return $url;
	}

	/**
	 * Get content to protect.
	 *
	 * @since  1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$contents = array();

		if ( ! empty( $args['menu_id'] ) ) {
			$menu_id = $args['menu_id'];
			$items = wp_get_nav_menu_items( $menu_id );

			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					$item_id = $item->ID;
					$contents[ $item_id ] = $item;
					$contents[ $item_id ]->id = $item_id;
					$contents[ $item_id ]->title = esc_html( $item->title );
					$contents[ $item_id ]->name = esc_html( $item->title );
					$contents[ $item_id ]->parent_id = $menu_id;
					$contents[ $item_id ]->type = MS_Rule_MenuItem::RULE_ID;
					$contents[ $item_id ]->access = $this->get_rule_value( $contents[ $item_id ]->id );
				}
			}
		}

		$filter = self::get_exclude_include( $args );
		if ( is_array( $filter->include ) ) {
			$contents = array_intersect_key( $contents, array_flip( $filter->include ) );
		} elseif ( is_array( $filter->exclude ) ) {
			$contents = array_diff_key( $contents, array_flip( $filter->exclude ) );
		}

		return apply_filters(
			'ms_rule_menuitem_model_get_contents',
			$contents,
			$args,
			$this
		);
	}

	/**
	 * Get the total content count.
	 *
	 * @since  1.0.0
	 *
	 * @param $args The query post args
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$items = $this->get_contents( $args );
		$count = count( $items );

		return apply_filters(
			'ms_rule_menuitem_model_get_content_count',
			$count,
			$args
		);
	}

	/**
	 * Get a list of all menus (only the menu details, without menu-items).
	 *
	 * @since  1.0.0
	 *
	 * @return array {
	 *      @type string $menu_id The menu id.
	 *      @type string $name The menu name.
	 * }
	 */
	public function get_menu_array() {
		$contents = array();
		$navs = wp_get_nav_menus( array( 'orderby' => 'name' ) );

		$count_args = array();
		if ( ! empty( $_REQUEST['membership_id'] ) ) {
			$count_args['membership_id'] = $_REQUEST['membership_id'];
		}

		if ( ! empty( $navs ) ) {
			foreach ( $navs as $nav ) {
				$count_args['menu_id'] = $nav->term_id;
				$total = $this->get_content_count( $count_args );

				$menu_url = esc_url_raw(
					add_query_arg(
						array( 'menu_id' => $nav->term_id )
					)
				);

				$contents[ $nav->term_id ] = array(
					'label' => $nav->name,
					'url' => $menu_url,
					'count' => $total,
				);
			}
		}

		if ( empty( $contents ) ) {
			$contents[] = array(
				'label' => __( '(No Menus Available)', 'membership2' )
			);
		}

		return apply_filters(
			'ms_rule_menuitem_model_get_menu_array',
			$contents,
			$this
		);
	}

}