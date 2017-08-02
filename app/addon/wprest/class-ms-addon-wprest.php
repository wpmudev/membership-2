<?php
/**
 * Add-On controller for: Add WordPress Res API
 *
 * @since 1.0.4
 *
 * @package Membership2
 * @subpackage Addon
 */
class MS_Addon_WPRest extends MS_Addon {

    /**
     * Rest API Namespace
     *
     * @since  1.0.4
     */
	const API_NAMESPACE = 'membership2/v1';
	

    /**
	 * The Add-on ID
	 *
	 * @since  1.0.4
	 */
	const ID = 'addon_wprest';

	/**
	 * Plugin Settings
	 *
	 * @since  1.0.4
	 */
	protected $plugin_settings = null;


    /**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.4
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

    /**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.4
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}


    /**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.4
	 */
	public function init() {
		if ( self::is_active() ) {
			$this->plugin_settings = MS_Factory::load( 'MS_Model_Settings' );
            $this->add_action( 'rest_api_init', 'register_routes' );
        }
	}
	
	/**
	 * Register API routes
	 *
	 * @since  1.0.4
	 */
    function register_routes() {
		//Action is set in each of the API classes in the /app/api directory
        do_action( 'ms_addon_wprest_register_route', $this->get_namespace(), $this->plugin_settings->wprest['api_passkey'] );
    }

	/**
	 * API namespace
	 *
	 * @since  1.0.4
	 *
	 * @return String
	 */
    protected function get_namespace() {
		return $this->plugin_settings->wprest['api_namespace'];
	}


	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.4
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
	
		$list[ self::ID ] = (object) array(
			'name' 			=> __( 'Rest API', 'membership2' ),
			'description' 	=> __( 'Enable WordPress REST API', 'membership2' ),
			'footer' 		=> sprintf( '<i class="dashicons dashicons dashicons-admin-settings"></i> %s', __( 'Options available', 'membership2' ) ),
			'icon' 			=> 'wpmui-fa wpmui-fa-angle-double-up',
			'class' 		=> 'ms-options',
			'details' 		=> array(
				array(
					'id' 		=> 'api_namespace',
					'before' 	=> get_rest_url(),
					'type' 		=> MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' 	=> __( 'API namespace:', 'membership2' ),
					'value' 	=> $settings->wprest['api_namespace'],
					'data_ms' 	=> array(
						'field' 	=> 'api_namespace',
						'action' 	=> MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
						'_wpnonce' 	=> true, // Nonce will be generated from 'action'
					),
				),
				array(
					'id' 		=> 'api_passkey',
					'type' 		=> MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' 	=> __( 'API passkey:', 'membership2' ),
					'value' 	=> $settings->wprest['api_passkey'],
					'data_ms' 	=> array(
						'field' 	=> 'api_passkey',
						'action' 	=> MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
						'_wpnonce' 	=> true, // Nonce will be generated from 'action'
					),
				),
			)
		);

		return $list;
	}
}
?>