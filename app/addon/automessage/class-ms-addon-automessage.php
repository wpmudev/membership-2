<?php
class MS_Addon_Automessage extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'addon_automessage';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.0
	 */
	public function init() {
		$this->add_filter( 'automessage_custom_user_hooks', 'automessage_custom_user_hooks' );
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		/*
		// Don't register: Not completed yet...

		$list[ self::ID ] = (object) array(
			'name' => __( 'Automessage', 'membership2' ),
			'description' => __( 'Automessage integration.', 'membership2' ),
		);
		*/
		return $list;
	}

	/**
	 * wpmu.dev Automessage plugin integration.
	 *
	 * @since  1.0.0
	 *
	 * @access public
	 * @param array $hooks The existing hooks.
	 * @return array The modified array of hooks.
	 */
	public function automessage_custom_user_hooks( $hooks ) {
		$comm_types = MS_Model_Communication::get_communication_type_titles();

		foreach ( $comm_types as $type => $desc ) {
			$action 			= "ms_communications_process_$type";
			$hooks[ $action ] 	= array( 'action_nicename' => $desc );
		}

		return $hooks;
	}

}