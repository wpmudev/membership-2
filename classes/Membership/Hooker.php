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
 * Base hooker class provides generic interface to hook on actions and filters.
 *
 * @category Membership
 *
 * @since 3.5
 */
class Membership_Hooker {

	/**
	 * The array of registered actions hooks.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var array
	 */
	private $_actions = array();

	/**
	 * The array of registered filters hooks.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var array
	 */
	private $_filters = array();

	/**
	 * Builds and returns hook key.
	 *
	 * @since 3.5
	 *
	 * @static
	 * @access private
	 * @param array $args The hook arguments.
	 * @return string The hook key.
	 */
	private static function _get_hook_key( array $args ) {
		return md5( implode( '/', $args ) );
	}

	/**
	 * Registers an action hook.
	 *
	 * @since 3.5
	 * @uses add_action() To register action hook.
	 *
	 * @access protected
	 * @param string $tag The name of the action to which the $method is hooked.
	 * @param string $method The name of the method to be called.
	 * @param int $priority optional. Used to specify the order in which the functions associated with a particular action are executed (default: 10). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
	 * @param int $accepted_args optional. The number of arguments the function accept (default 1).
	 * @return Membership_Module
	 */
	protected function _add_action( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		$args = func_get_args();
		$this->_actions[self::_get_hook_key( $args )] = $args;

		add_action( $tag, array( $this, !empty( $method ) ? $method : $tag ), $priority, $accepted_args );
		return $this;
	}

	/**
	 * Removes an action hook.
	 *
	 * @since 3.5
	 * @uses remove_action() To remove action hook.
	 *
	 * @access protected
	 * @param string $tag The name of the action to which the $method is hooked.
	 * @param string $method The name of the method to be called.
	 * @param int $priority optional. Used to specify the order in which the functions associated with a particular action are executed (default: 10). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
	 * @param int $accepted_args optional. The number of arguments the function accept (default 1).
	 * @return Membership_Module
	 */
	protected function _remove_action( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		remove_action( $tag, array( $this, !empty( $method ) ? $method : $tag ), $priority, $accepted_args );
		return $this;
	}

	/**
	 * Registers AJAX action hook.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $tag The name of the AJAX action to which the $method is hooked.
	 * @param string $method Optional. The name of the method to be called. If the name of the method is not provided, tag name will be used as method name.
	 * @param boolean $private Optional. Determines if we should register hook for logged in users.
	 * @param boolean $public Optional. Determines if we should register hook for not logged in users.
	 * @return Membership_Module
	 */
	protected function _add_ajax_action( $tag, $method = '', $private = true, $public = false ) {
		if ( $private ) {
			$this->_add_action( 'wp_ajax_' . $tag, $method );
		}

		if ( $public ) {
			$this->_add_action( 'wp_ajax_nopriv_' . $tag, $method );
		}

		return $this;
	}

	/**
	 * Removes AJAX action hook.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $tag The name of the AJAX action to which the $method is hooked.
	 * @param string $method Optional. The name of the method to be called. If the name of the method is not provided, tag name will be used as method name.
	 * @param boolean $private Optional. Determines if we should register hook for logged in users.
	 * @param boolean $public Optional. Determines if we should register hook for not logged in users.
	 * @return Membership_Module
	 */
	protected function _remove_ajax_action( $tag, $method = '', $private = true, $public = false ) {
		if ( $private ) {
			$this->_remove_action( 'wp_ajax_' . $tag, $method );
		}

		if ( $public ) {
			$this->_remove_action( 'wp_ajax_nopriv_' . $tag, $method );
		}

		return $this;
	}

	/**
	 * Registers a filter hook.
	 *
	 * @since 3.5
	 * @uses add_filter() To register filter hook.
	 *
	 * @access protected
	 * @param string $tag The name of the filter to hook the $method to.
	 * @param type $method The name of the method to be called when the filter is applied.
	 * @param int $priority optional. Used to specify the order in which the functions associated with a particular action are executed (default: 10). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
	 * @param int $accepted_args optional. The number of arguments the function accept (default 1).
	 * @return Membership_Module
	 */
	protected function _add_filter( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		$args = func_get_args();
		$this->_filters[self::_get_hook_key( $args )] = $args;

		add_filter( $tag, array( $this, !empty( $method ) ? $method : $tag ), $priority, $accepted_args );
		return $this;
	}

	/**
	 * Removes a filter hook.
	 *
	 * @since 3.5
	 * @uses remove_filter() To remove filter hook.
	 *
	 * @access protected
	 * @param string $tag The name of the filter to remove the $method to.
	 * @param type $method The name of the method to remove.
	 * @param int $priority optional. The priority of the function (default: 10).
	 * @param int $accepted_args optional. The number of arguments the function accepts (default: 1).
	 * @return Membership_Module
	 */
	protected function _remove_filter( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		remove_filter( $tag, array( $this, !empty( $method ) ? $method : $tag ), $priority, $accepted_args );
		return $this;
	}

	/**
	 * Unbinds all hooks previously registered for actions and/or filters.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param boolean $actions TRUE to unbind all actions hooks.
	 * @param boolean $filters TRUE to unbind all filters hooks.
	 */
	public function unbind( $actions = true, $filters = true ) {
		$types = array();

		if ( $actions ) {
			$types['_actions'] = 'remove_action';
		}

		if ( $filters ) {
			$types['_filters'] = 'remove_filter';
		}

		foreach ( $types as $hooks => $method ) {
			foreach ( $this->$hooks as $hook ) {
				call_user_func_array( $method, $hook );
			}
		}
	}

}