<?php
/**
 * This file defines the MS_Controller_Addon class.
 * 
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

/**
 * Controller for Membership add-ons.
 *
 * Manages the activating and deactivating of Memnbership addons.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Addon extends MS_Controller {

	/**
	 * The model to use for loading/saving add-on data.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */	
	private $model;

	/**
	 * View to use for rendering add-on settings.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $views
	 */	
	private $views;

	/**
	 * Prepare the Add-on manager.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		/**
		 * Actions to execute when the Addon controller construction starts.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Controller_Addon object.
		 */
		do_action( 'membership_addon_controller_construct_pre_processing', $this );
		
		/** Load the add-on manager model. */
		$this->add_action( 'load-membership_page_membership-addons', 'membership_addon_manager' );
		
		/**
		 * Reference the Addon model.
		 *
		 * **Note:**  
		 * Already filtered in main plugin instance using 'membership_model_addon'.
		 *
		 * @uses Filter: 'membership_model_addon'
		 * @since 4.0.0
		 */		
		$this->model = MS_Factory::get_factory()->load_addon();

		/** Enqueue scripts and styles. */
		$this->add_action( 'admin_print_scripts-membership_page_membership-addons', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-membership_page_membership-addons', 'enqueue_styles' );		
	}

	/**
	 * Handles Add-on manager actions.
	 *
	 * Handles activation/deactivation toggles and bulk update actions, then saves the model.
	 *
	 * @since 4.0.0
	 */
	public function membership_addon_manager() {
		
		// Get the server request method to use.
		$request_method = 'POST' === $_SERVER['REQUEST_METHOD'] ? 'POST' : 'GET';
		// Get the relevant $_REQUEST variable.
		$request_fields = 'POST' == $request_method ? $_POST : $_GET; 

		/**
		 * Hook into the Addon reguest handler before processing.
		 *
		 * **Note:**  
		 * This action uses the "raw" request objects which could lead to SQL injections / XSS.
		 * By hooking this action you need to take **responsibility** for filtering user input.
		 *
		 * @since 4.0.0  
		 * @param mixed[] $request_fields The relevant $_REQUEST variable.
		 * @param string $request_method Reuest method to handle - 'POST' or 'GET'.
		 * @param object $this The MS_Controller_Addon object.
		 */
		do_action( 'membership_addon_manager_request_handler', $request_fields, $request_method, $this );		
		
		$msg = 0;
		switch ( $request_method ) {
			case 'GET':

					/**
					 * Hook into the Addon GET handler.
					 *
					 * **Note:**  
					 * This action uses the "raw" GET input which could lead to SQL injections / XSS.
					 * By hooking this action you need to take **responsibility** for filtering user input.
					 *
					 * @since 4.0.0  
					 * @param mixed[] $request_fields The relevant $_GET variable.
					 * @param object $this The MS_Controller_Addon object.
					 */
					do_action( 'membership_addon_manager_get_handler', $request_fields, $this );
					
					if( ! empty( $request_fields['action'] ) && ! empty( $request_fields['addon'] ) && ! empty( $request_fields['_wpnonce'] ) && wp_verify_nonce( $request_fields['_wpnonce'], $request_fields['action'] ) ) {
						$msg = $this->save_addon( $request_fields['action'], array( $request_fields['addon'] ) );
						wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'addon', 'action', '_wpnonce' ) ) ) ) ;
					}
				break;
			
			case 'POST':
			
					/**
					 * Hook into the Addon POST handler.
					 *
					 * **Note:**  
					 * This action uses the "raw" POST input which could lead to SQL injections / XSS.
					 * By hooking this action you need to take **responsibility** for filtering user input.
					 *
					 * @since 4.0.0  
					 * @param mixed[] $request_fields The relevant $_POST variable.
					 * @param object $this The MS_Controller_Addon object.
					 */
					do_action( 'membership_addon_manager_post_handler', $request_fields, $this );			
					
					if( ! empty( $request_fields['addon'] ) && ! empty( $request_fields['_wpnonce'] ) && wp_verify_nonce( $request_fields['_wpnonce'], 'bulk-addons' ) ) {
						$action = $request_fields['action'] != -1 ? $request_fields['action'] : $request_fields['action2'];
						$msg = $this->save_addon( $action, $request_fields['addon'] );
						wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) ) ;
					}
				break;			
			default:
					die( __( 'Something very odd happened.', MS_TEXT_DOMAIN ) );
				break;
		}
	}


	/**
	 * Load and render the Add-on manager view.
	 *
	 * @since 4.0.0
	 */	
	public function admin_addon() {

		/**
		 * Create / Filter the Addon admin view.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Controller_Addon object.
		 */
		$this->views['addon'] = apply_filters( 'membership_addon_view', new MS_View_Addon(), $this );
		$this->views['addon']->model = $this->model;
		$this->views['addon']->render();
	}

	/**
	 * Call the model to save the addon settings.
	 *
	 * Saves activation/deactivation settings.
	 *
	 * @since 4.0.0
	 * @param string $action The action to perform on the add-on
	 * @param object[] $addons The add-on or add-ons to update. 
	 */	
	public function save_addon( $action, $addons ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		foreach( $addons as $addon ) {
			switch( $action ) {
				case 'enable':
					$this->model->enable( $addon );
					break;
				case 'disable':
					$this->model->disable( $addon );
					break;
				case 'toggle_activation':
					$this->model->toggle_activation( $addon );
					break;
			}
		}
		$this->model->save();
	}	

	/**
	 * Load Add-on specific styles.
	 *
	 * @since 4.0.0
	 */
	public function enqueue_styles() {
	}
	
	/**
	 * Load Add-on specific scripts.
	 *
	 * @since 4.0.0
	 */	
	public function enqueue_scripts() {
		wp_enqueue_script( 'ms-radio-slider' );		
	}
		
}