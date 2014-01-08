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
 * The base membership factory which is used to instantiate classes instances.
 *
 * @category Membership
 * @package Factory
 *
 * @since 3.5
 */
class Membership_Factory {

	const TYPE_MEMBER = 'member';

	/**
	 * Objects cache.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var array
	 */
	protected $_object_cache = array();

	/**
	 * Classes cache.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var array
	 */
	protected $_classes_cache = array();

	/**
	 * Extracts object from objects cache.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @param string $type The object family.
	 * @param string $key The object key.
	 * @param mixed $object The object if it exists in the cache, otherwise NULL.
	 * @return boolean TRUE if object has been extracted, otherwise FALSE.
	 */
	protected function _extract_from_cache( $type, $key, &$object = null ) {
		if ( !isset( $this->_object_cache[$type] ) ) {
			$this->_object_cache[$type] = array();
		}

		if ( array_key_exists( $key, $this->_object_cache[$type] ) ) {
			$object = $this->_object_cache[$type][$key];
			return true;
		}

		return false;
	}

	/**
	 * Puts object into cache.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @param string $type The object family.
	 * @param string $key The object key.
	 * @param mixed $object The object to put into the cache.
	 */
	protected function _put_into_cache( $type, $key, $object ) {
		if ( !isset( $this->_object_cache[$type] ) ) {
			$this->_object_cache[$type] = array();
		}

		$this->_object_cache[$type][$key] = $object;
	}

	/**
	 * Returns a member object.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param int|string|stdClass|WP_User $id User's ID, a WP_User object, or a user object from the DB.
	 * @param string $name Optional. User's username
	 * @param int $blog_id Optional Blog ID, defaults to current blog.
	 * @return Membership_Model_Member The member object.
	 */
	public function get_member( $id = 0, $name = '', $blog_id = '' ) {
		$object = null;
		if ( $this->_extract_from_cache( self::TYPE_MEMBER, $id, $object ) ) {
			return $object;
		}

		if ( !isset( $this->_classes_cache[self::TYPE_MEMBER] ) ) {
			$this->_classes_cache[self::TYPE_MEMBER] = apply_filters( 'membership_factory_class', 'Membership_Model_Member', self::TYPE_MEMBER );
		}

		$class = $this->_classes_cache[self::TYPE_MEMBER];
		if ( !class_exists( $class ) ) {
			$class = 'Membership_Model_Member';
		}

		$object = new $class( $id, $name, $blog_id );
		$this->_put_into_cache( self::TYPE_MEMBER, $id, $object );

		return $object;
	}

}