<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/


class MS_Integration_Mailchimp extends MS_Integration {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected static $mailchimp_api;
	
	const ADDON_MAILCHIMP = 'mailchimp';
	
	/**
	 * Add filters for mailchimp integration.
	 * 
	 * @since 4.0.0
	 */
	public function __construct() {
		parent::__construct();
		
		$this->add_filter( 'ms_model_addon_get_addon_types', 'mailchimp_addon' );
		$this->add_filter( 'ms_model_addon_get_addon_list', 'mailchimp_addon_list' );
		
		if( MS_Model_Addon::is_enabled( self::ADDON_MAILCHIMP ) ) {
			$this->add_filter( 'ms_controller_settings_get_tabs', 'mailchimp_settings_tabs', 10, 2 );
			$this->add_filter( 'ms_view_settings_edit_render_callback', 'mailchimp_manage_render_callback', 10, 3 );
		}
	}

	/**
	 * Add mailchimp add-on type.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_model_addon_get_addon_types
	 * 
	 * @param array $addons The current add-ons.
	 * @return string
	 */
	public function mailchimp_addon( $addons ) {
		$addons[] = self::ADDON_MAILCHIMP;
		return $addons;
	}
	
	/**
	 * Add mailchimp add-on info.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_model_addon_get_addon_list
	 * 
	 * @param array $list The current list of add-ons.
	 * @return array The filtered add-on list.
	 */
	public function mailchimp_addon_list( $list ) {
		$list[ self::ADDON_MAILCHIMP ] = (object) array(
				'id' => self::ADDON_MAILCHIMP,
				'name' => __( 'Mailchimp Integration', MS_TEXT_DOMAIN ),
				'description' => __( 'Enable mailchimp integration.', MS_TEXT_DOMAIN ),
				'active' => MS_Model_Addon::is_enabled( self::ADDON_MAILCHIMP ),
		);
	
		return $list;
	}
	
	/**
	 * Add mailchimp settings tab in settings page.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_controller_membership_get_tabs
	 * 
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit 
	 * @return array The filtered tabs.
	 */
	public function mailchimp_settings_tabs( $tabs ) {

		$tabs[ self::ADDON_MAILCHIMP  ] = array(
				'title' => __( 'Mailchimp', MS_TEXT_DOMAIN ),
				'url' => 'admin.php?page=membership-settings&tab=' . self::ADDON_MAILCHIMP,
		);
		
		return $tabs;
	}
	
	/**
	 * Add mailchimp views callback.
	 * 
	 * @since 4.0.0
	 * 
	 * @filter ms_view_membership_edit_render_callback
	 * 
	 * @param array $callback The current function callback.
	 * @param string $tab The current membership rule tab.
	 * @param array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function mailchimp_manage_render_callback( $callback, $tab, $data ) {
		if( self::ADDON_MAILCHIMP == $tab ) {
			$view = new MS_View_Mailchimp_General();
			$view->data = $data;
			$callback = array( $view, 'render_tab' );
				
		}
		return $callback;
	}
	
	public static function get_api_status() {
		$status = false;
		
		try {
			self::load_mailchimp_api();
			$status = true;
		} 
		catch( Exception $e ) {
			MS_Helper_Debug::log($e);
		}
		
		return $status;
	}
	
	/**
	 * Load the Mailchimp API
	 *
	 * @return Mailchimp Object
	 */
	public static function load_mailchimp_api() {
		if( empty( self::$mailchimp_api ) ) {
			require_once MS_Plugin::instance()->dir . '/lib/mailchimp-api/Mailchimp.php';
			
			$settings = MS_Model_Settings::load();
			
			$options = apply_filters( 'ms_integration_mailchimp_load_mailchimp_api_options', array(
					'timeout' => false,
					'ssl_verifypeer' => false,
					'ssl_verifyhost' => false,
					'ssl_cainfo' => false,
					'debug' => false,
			) );
		
			$api = new Mailchimp( $settings->get_custom_settings( 'mailchimp', 'api_key' ), $options );
		
			/** Pinging the server */
			$ping = $api->helper->ping();
		
			if( is_wp_error( $ping ) ) {
				MS_Helper_Debug::log($ping);
				throw new Exception( $ping );
			}
			
			self::$mailchimp_api = $api;
		}
			
		return self::$mailchimp_api;
	}
}