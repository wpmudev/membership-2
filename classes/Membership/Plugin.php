<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * The core plugin class.
 *
 * @category Membership
 *
 * @since 3.5
 */
class Membership_Plugin {

	const NAME    = 'membership';
	const VERSION = '3.5.0.9';

	/**
	 * Singletone instance of the plugin.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var Membership_Plugin
	 */
	private static $_instance = null;

	/**
	 * The array of registered modules.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var array
	 */
	private $_modules = array();

	/**
	 * The factory instance.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var Membership_Factory
	 */
	private $_factory = null;

	/**
	 * Private constructor.
	 *
	 * @since 3.5
	 *
	 * @access private
	 */
	private function __construct() {}

	/**
	 * Private clone method.
	 *
	 * @since 3.5
	 *
	 * @access private
	 */
	private function __clone() {}

	/**
	 * Returns singletone instance of the plugin.
	 *
	 * @since 3.5
	 *
	 * @static
	 * @access public
	 * @return Membership_Plugin
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new Membership_Plugin();
		}

		return self::$_instance;
	}

	/**
	 * Sets membership classes factory.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param Membership_Factory $factory The factory instance.
	 */
	public function set_factory( Membership_Factory $factory ) {
		$this->_factory = $factory;
	}

	/**
	 * Returns membership facotry.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @return Membership_Factory The factory instance.
	 */
	public function get_factory() {
		return $this->_factory;
	}

	/**
	 * Returns membership factory for current instance of the plguin.
	 *
	 * @since 3.5
	 *
	 * @static
	 * @access public
	 * @return Membership_Factory The factory instance.
	 */
	public static function factory() {
		return self::instance()->get_factory();
	}

	/**
	 * Returns a module if it was registered before. Otherwise NULL.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $name The name of the module to return.
	 * @return Membership_Module|null Returns a module if it was registered or NULL.
	 */
	public function get_module( $name ) {
		return isset( $this->_modules[$name] ) ? $this->_modules[$name] : null;
	}

	/**
	 * Determines whether the module has been registered or not.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $name The name of a module to check.
	 * @return boolean TRUE if the module has been registered. Otherwise FALSE.
	 */
	public function has_module( $name ) {
		return isset( $this->_modules[$name] );
	}

	/**
	 * Register new module in the plugin.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $module The name of the module to use in the plugin.
	 */
	public function set_module( $class ) {
		$this->_modules[$class] = new $class( $this );
	}

	/**
	 * Removes module from the plugin.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $module The name of the module to use in the plugin.
	 * @return boolean TRUE if a module has been unset, otherwise FALSE.
	 */
	public function unset_module( $class ) {
		if ( !isset( $this->_modules[$class] ) ) {
			return false;
		}

		$module = $this->_modules[$class];
		$module->unbind();
		$module = null;

		unset( $this->_modules[$class], $module );

		return true;
	}

	/**
	 * Determines whether global tables are used or not.
	 *
	 * @since 3.5
	 *
	 * @static
	 * @access public
	 * @return boolean TRUE if global tables are used, otherwise FALSE.
	 */
	public static function is_global_tables() {
		return defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && filter_var( MEMBERSHIP_GLOBAL_TABLES, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Determines whether the protection is enabled or not.
	 *
	 * @since 3.5
	 *
	 * @static
	 * @access public
	 * @return boolean TRUE if protection enabled, otherwise FALSE
	 */
	public static function is_enabled() {
		$option = 'membership_active';
		$default = 'no';

		$value = self::is_global_tables() && function_exists( 'get_blog_option' )
			? get_blog_option( MEMBERSHIP_GLOBAL_MAINSITE, $option, $default )
			: get_option( $option, $default );

		return apply_filters( 'membership_enabled', $value != 'no' );
	}

	/**
	 * Returns current member.
	 *
	 * @sicne 3.5
	 *
	 * @access public
	 * @global array $M_options The array of the plugin options.
	 * @staticvar Membership_Model_Member $member
	 * @return Membership_Model_Member Current member.
	 */
	public static function current_member() {
		global $M_options;
		static $member = null;

		if ( is_null( $member ) ) {
			$factory = self::factory();
			$member = $factory->get_member( get_current_user_id() );

			if ( $member->has_cap( Membership_Model_Member::CAP_MEMBERSHIP_ADMIN ) || $member->has_cap('manage_options') || is_super_admin($member->ID) ) {
				// member has admin capabilities
				membership_debug_log( __( 'Current member has admin capabilities.', 'membership' ) );

				// check whether we need to switch membership level or not
				if ( !empty( $_COOKIE['membershipuselevel'] ) && ( $membershipuselevel = absint( $_COOKIE['membershipuselevel'] ) ) ) {
					$member->assign_level( $membershipuselevel, true );
					membership_debug_log( sprintf( __( 'Switching membership level to %d for current member.', 'membership' ), $membershipuselevel ) );
				}
			} else {
				if ( $member->ID > 0 ) {
					if ( $member->has_levels() ) {
						// load the levels for this member
						$member->load_levels( true );
						membership_debug_log( __( 'Standard levels are loaded for current member.', 'membership' ) );
					} elseif ( !empty( $M_options['freeusersubscription'] ) ) {
						// load default subscription for registered users
						$subscription = $factory->get_subscription( $M_options['freeusersubscription'] );
						$levels = $subscription->get_levels();
						if ( !empty( $levels ) ) {
							$member->assign_level( $levels[0]->level_id );
							membership_debug_log( __( 'Default subscription for registered users is used to assign a level for current member.', 'membership' ) );
						}
					}
				}

				// if no levels were assigned, then assign stanger level
				if ( !$member->has_levels() && isset( $M_options['strangerlevel'] ) && $M_options['strangerlevel'] != 0 ) {
					$member->assign_level( $M_options['strangerlevel'] );
				}
			}
		}

		return $member;
	}

}