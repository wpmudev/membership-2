<?php

/**
 * Abstract class for all Hustle addon providers.
 *
 * Almost all functionality will be created with in an extended class.
 *
 * @since  1.1.2
 *
 * @uses MS_Hooker
 *
 * @package Membership2
 */
class MS_Addon_Hustle_Provider extends MS_Hooker {

	/**
	 * Class provider name
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 * Override this value in child object.
	 *
	 * @since  1.1.2
	 * @var string
	 */
	protected static $PROVIDER_ID = '';


	/**
	 * Get provider settings
	 *
	 * @return array|bool
	 */
	protected function get_settings() {
		$settings 			= MS_Factory::load( 'MS_Model_Settings' );
		$provider_settings 	= $settings->get_custom_setting( 'hustle', self::$PROVIDER_ID );
		return $provider_settings;
	}

	/**
	 * Get provider detail
	 *
	 * @param string $name - the detail key
	 *
	 * @return string
	 */
	protected function get_provider_detail( $name ) {
		$settings = $this->get_settings();
		if ( $provider_details && is_array( $provider_details ) ) {
			if ( isset( $provider_details[ $name ] ) ) {
				return $provider_details[ $name ];
			}
		}

		return "";
	}

	/**
	 * Subscribe a user to a list
	 *
	 * @since  1.1.2
	 *
	 * @param  MS_Model_Member $member
	 * @param  int|string $list_id
	 */
	public function subscribe_user( $member, $list_id ) { 

	}

	/**
	 * UnSubscribe a user to a list
	 *
	 * @since  1.1.2
	 *
	 * @param  MS_Model_Member $member
	 * @param  int|string $list_id
	 */
	public function unsubscribe_user( $member, $list_id ) {

	}

	/**
	 * Check if a user is subscribed in the list
	 *
	 * @param  string $user_email
	 * @param  string $list_id
	 * @return bool True if the user is subscribed already to the list
	 */
	public function is_user_subscribed( $user_email, $list_id ) {

		return false;
	}
	
}
?>