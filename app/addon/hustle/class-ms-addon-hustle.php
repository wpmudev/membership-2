<?php
/**
 * Add-On controller for: Hustle
 *
 * @since  1.1.2
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Addon_Hustle extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.1.2
	 */
	const ID = 'hustle';


	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.1.2
	 * @return bool
	 */
	static public function is_active() {
		if ( ! self::is_hustle_active()
			&& MS_Model_Addon::is_enabled( self::ID )
		) {
			$model = MS_Factory::load( 'MS_Model_Addon' );
			$model->disable( self::ID );
		}
		return MS_Model_Addon::is_enabled( self::ID );
	}


	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.2
	 */
	public function init() {
		if ( self::is_active() ) {
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'settings_tabs',
				10, 2
			);

			$this->add_action(
				'ms_controller_settings_enqueue_scripts_' . self::ID,
				'enqueue_scripts'
			);

			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_render_callback',
				10, 3
			);
		}
	}


	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.1.2
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Returns true, when the WP_reCaptcha plugin is activated.
	 *
	 * @since  1.1.2
	 * @return bool
	 */
	static public function is_hustle_active() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( is_plugin_active( 'hustle/opt-in.php' ) || 
			is_plugin_active( 'wordpress-popup/popover.php' ) ) {
			
			return true;
		}
		return false;
	}


	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.2
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$plugin_url 		= MS_Plugin::instance()->url;
		$list[ self::ID ] 	= (object) array(
			'name' 				=> __( 'Hustle Integration', 'membership2' ),
			'description' 		=> __( 'Enable Hustle integration.', 'membership2' ),
			'icon' 				=> $plugin_url . 'app/assets/images/hustle.png',
		);

		if ( ! self::is_hustle_active() ) {
			$list[ self::ID ]->description .= sprintf(
				'<br /><b>%s</b>',
				__( 'Activate Hustle to use this Add-on', 'membership2' )
			);
			$list[ self::ID ]->action = '-';
		}

		return $list;
	}


	/**
	 * Add hustle settings tab in settings page.
	 *
	 * @since  1.1.2
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param  array $tabs The current tabs.
	 * @param  int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID  ] = array(
			'title' => __( 'Hustle', 'membership2' ),
			'url' 	=> MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
		);

		return $tabs;
	}


	/**
	 * Enqueue admin scripts in the settings screen.
	 *
	 * @since  1.1.2
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array( 'view_settings_hustle' ),
		);

		lib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}


	/**
	 * Add hustle views callback.
	 *
	 * @since  1.1.2
	 *
	 * @filter ms_view_settings_edit_render_callback
	 *
	 * @param  array $callback The current function callback.
	 * @param  string $tab The current membership rule tab.
	 * @param  array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view 		= MS_Factory::load( 'MS_Addon_Hustle_View' );
			$view->data = $data;
			$callback 	= array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Get the hustle providers
	 *
	 * @since  1.1.2
	 *
	 * @return array
	 */
	public static function hustle_providers() {
		$hustle_providers = array();
		if ( self::is_active() ) {
			global $hustle;
			$providers = $hustle->get_providers();
			$hustle_providers[] = __( 'Select a provider', 'membership2' );
			foreach ( $providers as $provider ) {
				if ( $provider['id'] === 'mailchimp') {
					continue;
				}
				$hustle_providers[ $provider['id'] ] = $provider['name'];
			}
		}
		return $hustle_providers;
	}
}
?>