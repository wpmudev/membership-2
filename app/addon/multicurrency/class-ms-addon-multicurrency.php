<?php
/**
 * Add-On controller for: Multi Currency
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Addon_Multicurrency extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.2.6
	 */
	const ID = 'multicurrency';

	const SLUG = 'multicurrency';

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
		
                if ( self::is_active() ) {
                        // Action and Filter Hooks
                        
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
                        
                        $this->add_action(
                                'wp_ajax_' . MS_Addon_MultiCurrency_View::AJAX_ACTION_SAVE_CURRENCIES,
                                'ms_ajax_save_currencies'
                        );
                        
                        $this->add_action(
                                'wp_ajax_' . MS_Addon_MultiCurrency_View::AJAX_ACTION_GET_RATE_CHANGER,
                                'ms_ajax_get_rate_changer'
                        );
                        
                        $this->add_action(
                                'wp_ajax_' . MS_Addon_MultiCurrency_View::AJAX_ACTION_SAVE_RATE_CHANGER,
                                'ms_ajax_save_rate_changer'
                        );
                        
                        $this->add_filter(
                                'ms_format_price',
                                'change_currency',
                                10, 2
                        );
                
                
                }
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Multi Currency', 'membership2' ),
			'description' => __( 'You can show the membership prices in different currencies.', 'membership2' ),
			'icon' => 'dashicons dashicons-chart-area',
		);

		return $list;
	}
        
        
        /**
	 * Add multi currency settings tab in settings page.
	 *
	 * @since  1.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param  array $tabs The current tabs.
	 * @param  int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID  ] = array(
			'title' => __( 'Multi Currency', 'membership2' ),
			'url' => MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
		);

		return $tabs;
	}
        
        
        /**
	 * Add multi currency views callback.
	 *
	 * @since  1.0.0
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
			$view = MS_Factory::load( 'MS_Addon_Multicurrency_View' );
			$view->data = $data;
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}
        
        /**
	 * Enqueue admin scripts in the settings screen.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array( 'view_settings_multicurrency' ),
		);

		lib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}
        
        public function ms_ajax_save_currencies() {
            
            $currencies = $_POST['values'];
            $settings = MS_Plugin::instance()->settings;
            $settings->set_custom_setting( 'multicurrency', 'currencies', $currencies );
            $settings->save();
            die();
        }
        
        public function ms_ajax_get_rate_changer() {
            $currencies = array();
            
            $settings = MS_Plugin::instance()->settings;
            $saved_currencies = $settings->get_custom_setting( 'multicurrency', 'currencies' );
            $conversions = $settings->get_custom_setting( 'multicurrency', 'conversion' );
            
            foreach( $saved_currencies as $key => $val ) {
                foreach( $conversions as $conversion ) {
                    if( $conversion['currency'] == $val ) {
                        $currencies[$val] = $conversion['rate'];
                    }
                }
            }
            
            $keys = array_keys( $currencies );
            foreach( $saved_currencies as $key => $val ) {
                if( ! in_array( $val, $keys ) ) {
                    $currencies[$val] = '';
                }
            }
            
            echo json_encode( $currencies, JSON_FORCE_OBJECT );
            die();
        }
        
        public function ms_ajax_save_rate_changer() {
            $conversion = $_POST['conversion'];
            $settings = MS_Plugin::instance()->settings;
            $settings->set_custom_setting( 'multicurrency', 'conversion', $conversion );
            $settings->save();
            
            
            die();
        }
        
        public function change_currency( $formatted, $price ) {
            $currency = 'CAD';
            
            $settings = MS_Plugin::instance()->settings;
            $conversions = $settings->get_custom_setting( 'multicurrency', 'conversion' );
            
            foreach( $conversions as $conversion ) {
                if( $conversion['currency'] == $currency ) {
                    $rate = $conversion['rate'];
                    break;
                }
            }
            
            $price = $price * $rate;
            
            $this->remove_filter(
                    'ms_format_price',
                    'change_currency',
                    10, 2
            );
            
            $price = MS_Helper_Billing::format_price( $price );
            
            $this->add_filter(
                    'ms_format_price',
                    'change_currency',
                    10, 2
            );
            
            return $price;
        }

}