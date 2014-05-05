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

	const TYPE_MEMBER       = 'member';
	const TYPE_SUBSCRIPTION = 'subscription';
	const TYPE_LEVEL        = 'level';

	/**
	 * Classes cache.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var array
	 */
	private $_classes_cache = array();

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
		$found = false;
		$object = wp_cache_get( $key, $type, false, $found );
		return $found;
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
		wp_cache_add( $key, $object, $type );
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

	/**
	 * Returns a subscription object.
	 *
	 * @sicne 3.5
	 *
	 * @access public
	 * @param int $id The subscription's id.
	 * @return Membership_Model_Subscription The subscription object.
	 */
	public function get_subscription( $id = false ) {
		$object = null;
		if ( $this->_extract_from_cache( self::TYPE_SUBSCRIPTION, $id, $object ) ) {
			return $object;
		}

		if ( !isset( $this->_classes_cache[self::TYPE_SUBSCRIPTION] ) ) {
			$this->_classes_cache[self::TYPE_SUBSCRIPTION] = apply_filters( 'membership_factory_class', 'Membership_Model_Subscription', self::TYPE_SUBSCRIPTION );
		}

		$class = $this->_classes_cache[self::TYPE_SUBSCRIPTION];
		if ( !class_exists( $class ) ) {
			$class = 'Membership_Model_Subscription';
		}

		$object = new $class( $id );
		$this->_put_into_cache( self::TYPE_SUBSCRIPTION, $id, $object );

		return $object;
	}

	/**
	 * Returns a level object.
	 *
	 * @sicne 3.5
	 *
	 * @access public
	 * @param int $id The level's id.
	 * @param boolean $fullload Determines whether or not we need to load level rules.
	 * @param array $loadtype Determines what rules we need to load.
	 * @return Membership_Model_Level The level object.
	 */
	public function get_level( $id = false, $fullload = false, $loadtype = array( 'public', 'core' ) ) {
		$object = null;
		$key = $id . ( $fullload ? 'yes' : 'no' ) . implode( '', $loadtype );
		if ( $this->_extract_from_cache( self::TYPE_LEVEL, $key, $object ) ) {
			return $object;
		}

		if ( !isset( $this->_classes_cache[self::TYPE_LEVEL] ) ) {
			$this->_classes_cache[self::TYPE_LEVEL] = apply_filters( 'membership_factory_class', 'Membership_Model_Level', self::TYPE_LEVEL );
		}

		$class = $this->_classes_cache[self::TYPE_LEVEL];
		if ( !class_exists( $class ) ) {
			$class = 'Membership_Model_Level';
		}

		$object = new $class( $id, $fullload, $loadtype );
		$this->_put_into_cache( self::TYPE_LEVEL, $key, $object );

		return $object;
	}
	
	/**
	 * Add hook to new user registration.
	 *
	 * Used to make sure that default subscriptions are applied to new users.
	 *
	 * @sicne 3.5.1.4
	 *
	 * @access public
	 */	
	public function hook_new_user_registration() {
		add_action( 'user_register', array( $this, 'new_user_assignment' ) , 10, 1 );
	}
		
	
	/**
	 * Hook new user registrations.
	 *
	 * Assign new users to default subscription if set.
	 *
	 * @sicne 3.5.1.4
	 *
	 * @access public
	 */	
	function new_user_assignment( $user_id ) {
		global $M_options;

		// Assign default subscription to new registered user. Sets expiry date on creation.
		// Only assign when this option is selected in Membership Options->General

		if ( ! empty( $M_options['freeusersubscription'] ) && 0 != $M_options['freeusersubscription'] && $M_options['assignfirstlevel'] ) {		
			$member = $this->get_member( $user_id );
			$subscription = $this->get_subscription( $M_options['freeusersubscription'] );
			if( ! empty( $subscription ) && 0 != $subscription->id ) {
				$member->create_subscription( $subscription->id );
			}
		}		
	}

}