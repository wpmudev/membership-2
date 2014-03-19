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
 * Base class for all modules. Implements routine methods required by all modules.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Module extends Membership_Hooker {

	/**
	 * The instance of wpdb class.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var wpdb
	 */
	protected $_wpdb = null;

	/**
	 * The plugin instance.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var Membership_Plugin
	 */
	protected $_plugin = null;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 * @global wpdb $wpdb Current database connection.
	 *
	 * @access public
	 * @param Membership_Plugin $plugin The instance of the plugin.
	 */
	public function __construct( Membership_Plugin $plugin ) {
		global $wpdb;

		$this->_wpdb = $wpdb;
		$this->_plugin = $plugin;
	}

	/**
	 * Registers a hook for shortcode tag.
	 *
	 * @since 3.5
	 * @uses add_shortcode() To register shortcode hook.
	 *
	 * @access protected
	 * @param string $tag Shortcode tag to be searched in post content.
	 * @param string $method Hook to run when shortcode is found.
	 * @return Membership_Module
	 */
	protected function _add_shortcode( $tag, $method ) {
		add_shortcode( $tag, array( $this, $method ) );
		return $this;
	}

}