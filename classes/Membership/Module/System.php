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
 * The module responsible for system tasks.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Module_System extends Membership_Module {

	const NAME = __CLASS__;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param Membership_Plugin $plugin The instance of the plugin class.
	 */
	public function __construct( Membership_Plugin $plugin ) {
		parent::__construct( $plugin );

		$this->_add_action( 'widgets_init', 'register_widgets' );
		$this->_add_action( 'plugins_loaded', 'load_textdomain' );
	}

	/**
	 * Register widgets.
	 *
	 * @since 3.5
	 * @action widgets_init
	 *
	 * @access public
	 */
	public function register_widgets() {
		register_widget( Membership_Widget_Login::NAME );
	}

	/**
	 * Loads text domain for the plugin.
	 *
	 * @since 3.5
	 * @action plugins_loaded
	 *
	 * @access public
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'membership', false, dirname( plugin_basename( MEMBERSHIP_BASEFILE ) ) . '/languages/' );
	}

}