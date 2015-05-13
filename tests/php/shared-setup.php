<?php

// This file offers a convenient way to setup/reset test-data in the database.
class TData {

	protected static $ids = array();

	const ONE_DAY = 86400; // One day has 86400 seconds

	/**
	 * Resets the database.
	 *
	 * @since  1.0.0
	 */
	public static function reset() {
		global $wpdb;

		// wipe all existing data.
		$wpdb->query( "TRUNCATE TABLE {$wpdb->users};" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->usermeta};" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->posts};" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->postmeta};" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%transient_%';" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'MS_%';" );
		self::$ids = array(
			'user' => array(),
			'post' => array(),
			'membership' => array(),
		);

		// create demo users
		$users = array(
			'admin' => array(
				'role' => 'administrator',
			),
			'editor' => array(
				'role' => 'editor',
			)
		);
		foreach ( $users as $login => $data ) {
			$defaults = array(
				'user_login' => $login,
				'user_pass' => $login . '-password',
				'user_email' => $login . '@local.dev',
				'role' => 'subscriber',
				'user_nicename' => '',
				'user_url' => '',
				'display_name' => 'User ' . $login,
				'nickname' => '',
				'first_name' => '',
				'last_name' => '',
				'description' => '',
				'user_registered' => '',
			);
			$data = shortcode_atts( $defaults, $data );

			$id = wp_insert_user( $data );
			if ( ! empty( $data['meta'] ) ) {
				foreach ( $data['meta'] as $key => $val ) {
					$val = maybe_serialize( $val );
					update_user_meta( $id, $key, $val );
				}
			}

			self::$ids['user'][$login] = $id;
		}

		// create demo posts
		$posts = array(
			'sample-post' => array(
				'post_content' => 'Just a very simple sample post...',
			),
			'sample-page' => array(
				'post_type' => 'page',
				'post_content' => 'Just a very simple sample page...',
			),
		);
		foreach ( $posts as $slug => $data ) {
			$defaults = array(
				'post_type' => 'post',
				'post_author' => self::id( 'user', 'admin' ),
				'post_title' => $slug,
				'post_name' => $slug,
			);
			$data = shortcode_atts( $defaults, $data );

			$id = wp_insert_post( $data );
			if ( ! empty( $data['meta'] ) ) {
				foreach ( $data['meta'] as $key => $val ) {
					$val = maybe_serialize( $val );
					update_post_meta( $id, $key, $val );
				}
			}

			self::$ids['post'][$slug] = $id;
		}

		// create demo memberships
		$memberships = array(
			'simple' => array(
				'name' => 'Simple Membership',
				'type' => MS_Model_Membership::TYPE_STANDARD,
				'payment_type' => MS_Model_Membership::PAYMENT_TYPE_PERMANENT,
				'price' => 29,
				'rule_values' => array(),
			),
			'simple-trial' => array(
				'name' => 'Simple Membership with Trial',
				'type' => MS_Model_Membership::TYPE_STANDARD,
				'payment_type' => MS_Model_Membership::PAYMENT_TYPE_PERMANENT,
				'price' => 29,
				'rule_values' => array(),
				'trial_period_enabled' => true,
				'trial_period' => array( 'period_unit' => 14, 'period_type' => 'days' ),
			),
			'limited' => array(
				'name' => 'Limited Membership',
				'type' => MS_Model_Membership::TYPE_STANDARD,
				'payment_type' => MS_Model_Membership::PAYMENT_TYPE_FINITE,
				'price' => 19,
				'rule_values' => array(),
				'period' => array( 'period_unit' => 28, 'period_type' => 'days' ),
			),
			'limited-trial' => array(
				'name' => 'Limited Membership with Trial',
				'type' => MS_Model_Membership::TYPE_STANDARD,
				'payment_type' => MS_Model_Membership::PAYMENT_TYPE_FINITE,
				'price' => 19,
				'rule_values' => array(),
				'period' => array( 'period_unit' => 28, 'period_type' => 'days' ),
				'trial_period_enabled' => true,
				'trial_period' => array( 'period_unit' => 14, 'period_type' => 'days' ),
			),
			'daterange-trial' => array(
				'name' => 'Date-Range Membership with Trial',
				'type' => MS_Model_Membership::TYPE_STANDARD,
				'payment_type' => MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE,
				'price' => 39,
				'rule_values' => array(),
				'period_date_start' => date( 'Y-m-d', time() + self::ONE_DAY ), // Starts tomorrow
				'period_date_end' => date( 'Y-m-d', time() + 10 * self::ONE_DAY ) , // Ends in 10 days
				'trial_period_enabled' => true,
				// Note: Trial is longer than the access period:
				'trial_period' => array( 'period_unit' => 14, 'period_type' => 'days' ),
			),
			'free-limited' => array(
				'name' => 'Free Limited Membership',
				'type' => MS_Model_Membership::TYPE_STANDARD,
				'payment_type' => MS_Model_Membership::PAYMENT_TYPE_FINITE,
				'is_free' => true,
				'price' => 0,
				'rule_values' => array(),
				'period' => array( 'period_unit' => 28, 'period_type' => 'days' ),
			),
		);
		foreach ( $memberships as $key => $data ) {
			$item = new MS_Model_Membership();
			foreach ( $data as $prop => $val ) {
				if ( ! property_exists( $item, $prop ) ) { continue; }
				$item->$prop = $val;
			}
			$item->save();
			$id = $item->id;

			self::$ids['membership'][$key] = $id;
		}

		// Clear the plugin Factory-Cache
		MS_Factory::_reset();
	}

	/**
	 * Returns a single item ID.
	 *
	 * Example:
	 * $user = id( 'user', 'admin' );
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public static function id( $type, $key ) {
		if ( ! isset( self::$ids[ $type ] ) ) {
			$err = '[UNKNOWN TYPE] ' . $type;
			error_log( $err );
			throw new Exception( $err, 1 );
		}

		if ( ! isset( self::$ids[ $type ][ $key ] ) ) {
			$err = '[UNKNOWN ELEMENT] ' . $type . '.' . $key;
			error_log( $err );
			throw new Exception( $err, 1 );
		}

		return self::$ids[ $type ][ $key ];
	}

	/**
	 * Creates a new subscription for the specified user/membership and returns
	 * the MS_Model_Relationship object.
	 *
	 * @since  1.0.0
	 * @return MS_Model_Relationship
	 */
	public static function subscribe( $user_key, $membership_key, $gateway_id = '' ) {
		if ( ! is_numeric( $user_key ) ) {
			$user_key = self::id( 'user', $user_key );
		}
		if ( ! is_numeric( $membership_key ) ) {
			$membership_key = self::id( 'membership', $membership_key );
		}

		$subscription = MS_Model_Relationship::create_ms_relationship(
			$membership_key,
			$user_key,
			$gateway_id,
			0
		);

		return $subscription;
	}

	/**
	 * Activates a specific Add-on
	 *
	 * @since  1.0.0
	 * @param  string $key Addon-ID
	 */
	public static function enable_addon( $key ) {
		$addons = MS_Factory::load( 'MS_Model_Addon' );
		$addons->enable( $key );
	}

	/**
	 * Deactivates a specific Add-on
	 *
	 * @since  1.0.0
	 * @param  string $key Addon-ID
	 */
	public static function disable_addon( $key ) {
		$addons = MS_Factory::load( 'MS_Model_Addon' );
		$addons->disable( $key );
	}
};