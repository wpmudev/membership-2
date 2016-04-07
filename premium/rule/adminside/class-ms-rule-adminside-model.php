<?php
/**
 * Membership Admin Side Protection Rule class.
 *
 * Persisted by Membership class.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Rule_Adminside_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since  1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Adminside::RULE_ID;

	/**
	 * An array of all menu items that are not allowed for the current user.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	static protected $denied_items = array();

	/**
	 * Returns the active flag for a specific rule.
	 * State depends on Add-on
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_ADMINSIDE );
	}

	/**
	 * Verify access to the current content.
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
	 * Set initial protection.
	 *
	 * @since  1.0.0
	 */
	public function protect_admin_content() {
		parent::protect_admin_content();

		/*
		 * Find out which menu items are allowed.
		 */
		$this->add_filter( 'custom_menu_order', 'prepare_protection', 1 );

		/*
		 * Remove menu items that are not allowed.
		 */
		$this->add_filter( 'custom_menu_order', 'protect_menus', 10 );
	}

	/**
	 * Set the protection flag for each menu item.
	 *
	 * This function is called before function protect_menuitems() below.
	 * Here we evaluate each menu item to see if the user has access to the
	 * menu item and collect all accessible menu items in a static/shared array
	 * so we have correct information when evaluating multiple memberships.
	 *
	 * Relevant Action Hooks:
	 * - network_admin_menu
	 * - user_admin_menu
	 * - admin_menu
	 *
	 * @since  1.0.0
	 * @global array $menu
	 */
	public function prepare_protection( $ignore ) {
		foreach ( $this->rule_value as $url => $denied ) {
			if ( $this->is_base_rule ) {
				if ( ! isset( self::$denied_items[$url] ) ) {
					// Base rule: Defines the denied items.
					self::$denied_items[$url] = $denied;
				}
			} else {
				// Normal membership: Defines the allowed items.
				self::$denied_items[$url] = ! $denied;
			}
		}

		return $ignore;
	}

	/**
	 * Remove menu items that are protected.
	 *
	 * Menu-Item protection is split into two steps to ensure correct
	 * menu-visibility when users are members of multiple memberships.
	 * http://premium.wpmudev.org/forums/topic/multiple-membership-types-defaults-to-less-access-protected-content
	 *
	 * Relevant Action Hooks:
	 * - network_admin_menu
	 * - user_admin_menu
	 * - admin_menu
	 *
	 * @since  1.0.0
	 * @global array $menu
	 */
	public function protect_menus( $ignore ) {
		global $menu, $submenu, $_wp_submenu_nopriv, $_wp_menu_nopriv;
		static $Done = false;

		// Only remove menu items once.
		if ( $Done ) { return; }
		$Done = true;

		$denied = apply_filters(
			'ms_rule_adminside_model_denied_items',
			self::$denied_items,
			$this
		);

		// Protect the main menu.
		foreach ( $menu as $main_key => $main_item ) {
			$main_url = $main_item[2];

			if ( isset( $denied[ $main_url ] ) && $denied[ $main_url ] ) {
				// Remove protected items from the global array.
				unset( $menu[ $main_key ] );
				unset( $submenu[ $main_key ] );
				$_wp_menu_nopriv[$main_url] = true;
				continue;
			}

			if ( ! isset( $submenu[$main_url] ) ) { continue; }

			// Protect sub menus.
			foreach ( $submenu[$main_url] as $child_key => $child_item ) {
				$child_url = $main_url . ':' . $child_item[2];
				if ( isset( $denied[ $child_url ] ) && $denied[ $child_url ] ) {
					// Remove protected items from the global array.
					unset( $submenu[$main_url][ $child_key ] );
					$_wp_submenu_nopriv[$main_url][$child_item[2]] = true;
				}
			}
		}

		// Remove submenu items that have same URL as the top-menu item
		foreach ( $submenu as $main_key => $children ) {
			foreach ( $children as $child_key => $child_item ) {
				if ( isset( $denied[ $child_item[2] ] ) && $denied[ $child_item[2] ] ) {
					// Remove protected items from the global array.
					unset( $submenu[ $main_key ][ $child_key ] );
					$_wp_submenu_nopriv[ $main_key ][ $child_key ] = true;
					continue;
				}
			}
		}

		return $ignore;
	}

	/**
	 * Get content to protect. An array of objects is returned.
	 *
	 * @since  1.0.0
	 * @param $args The query post args
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		static $Items = null;
		$contents = array();
		$full_menu = MS_Plugin::instance()->controller->get_admin_menu();

		// Admin-Pages in this list cannot be protected.
		$blacklist = array(
			'index.php',  // Dashboard
			'edit-tags.php?taxonomy=link_category', // Links (deprecated)
			MS_Controller_Plugin::MENU_SLUG, // Main Membership2 menu item.
			// Membership2 sub-menu items
			MS_Controller_Plugin::MENU_SLUG . '-setup',
			MS_Controller_Plugin::MENU_SLUG . '-members',
			MS_Controller_Plugin::MENU_SLUG . '-billing',
			MS_Controller_Plugin::MENU_SLUG . '-protection',
			MS_Controller_Plugin::MENU_SLUG . '-coupons',
			MS_Controller_Plugin::MENU_SLUG . '-addon',
			MS_Controller_Plugin::MENU_SLUG . '-settings',
			MS_Controller_Plugin::MENU_SLUG . '-help',
		);
		$blacklist = apply_filters(
			'ms_rule_adminside_blacklist',
			$blacklist
		);

		if ( null === $Items ) {
			$Items = array();
			foreach ( $full_menu['main'] as $pos => $item ) {
				// Skip separators
				if ( empty( $item[0] ) ) { continue; }
				$parts = explode( '<', $item[0] );
				$parent_name = trim( array_shift( $parts ) );
				$skip_parent = false;

				if ( in_array( $item[2], $blacklist ) ) {
					$skip_parent = true;
				} elseif ( ! empty( $args['s'] ) ) {
					// Search the submenu name...
					if ( false === stripos( $parent_name, $args['s'] ) ) {
						$skip_parent = true;
					}
				}

				if ( ! $skip_parent ) {
					$Items[$item[2]] = (object) array(
						'name' => $parent_name,
						'parent_id' => 0,
					);
				}

				if ( isset( $full_menu['sub'][$item[2]] ) ) {
					$children = $full_menu['sub'][$item[2]];

					foreach ( $children as $pos => $child ) {
                                                
                                                // Same page name fix for BP title
                                                if( $child[2] == 'bp-about' ) {
                                                    $child[0] = 'Welcome to BuddyPress (About)';
                                                }elseif( $child[2] == 'bp-credits' ){
                                                    $child[0] = 'Welcome to BuddyPress (Credits)';
                                                }
                                                
						$parts = explode( '<', $child[0] );
						$child_name = trim( array_shift( $parts ) );

						// Search the submenu name...
						if ( $skip_parent && ! empty( $args['s'] ) ) {
							if ( false === stripos( $child_name, $args['s'] ) ) {
								continue;
							}
						}

						if ( in_array( $child[2], $blacklist ) ) { continue; }
                                
						$Items[$item[2] . ':' . $child[2]] = (object) array(
							'name' => $parent_name . ' &rarr; ' . $child_name,
							'parent_id' => $item[2],
						);
					}
				}
			}
		}

		$filter = self::get_exclude_include( $args );

		foreach ( $Items as $key => $item ) {
			if ( is_array( $filter->include ) ) {
				if ( ! in_array( $key, $filter->include ) ) { continue; }
			} elseif ( is_array( $filter->exclude ) ) {
				if ( in_array( $key, $filter->exclude ) ) { continue; }
			}

			$contents[ $key ] = $item;
			$contents[ $key ]->id = $key;
			$contents[ $key ]->title = $contents[ $key ]->name;
			$contents[ $key ]->post_title = $contents[ $key ]->name;
			$contents[ $key ]->type = MS_Rule_Adminside::RULE_ID;
			$contents[ $key ]->access = $this->get_rule_value( $key );
		}

		if ( ! empty( $args['posts_per_page'] ) ) {
			$total = $args['posts_per_page'];
			$offset = ! empty( $args['offset'] ) ? $args['offset'] : 0;
			$contents = array_slice( $contents, $offset, $total );
		}

		return apply_filters(
			'ms_rule_adminside_model_get_contents',
			$contents,
			$args,
			$this
		);
	}

	/**
	 * Get total content count.
	 *
	 * @since  1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The content count.
	 */
	public function get_content_count( $args = null ) {
		$args['posts_per_page'] = 0;
		$args['offset'] = false;
		$count = count( $this->get_contents( $args ) );

		return apply_filters(
			'ms_rule_adminside_model_get_contents',
			$count,
			$args,
			$this
		);
	}
}