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
	 * The custom post type used with Add-ons.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $post_type
	 */
	private $post_type;

	/**
	 * Capability required to manage Add-ons.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $capability
	 */	
	private $capability = 'manage_options';

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
		/** Load the add-on manager model. */
		$this->add_action( 'load-membership_page_membership-addons', 'membership_addon_manager' );
		$this->model = apply_filters( 'membership_addon_model', MS_Model_Addon::load() );

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
		$msg = 0;
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['addon'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'] ) ) {
			$msg = $this->save_addon( $_GET['action'], array( $_GET['addon'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'addon', 'action', '_wpnonce' ) ) ) ) ;
		}
		elseif( ! empty( $_POST['addon'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-addons' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->save_addon( $action, $_POST['addon'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) ) ;
		}
	}


	/**
	 * Load and render the Add-on manager view.
	 *
	 * @since 4.0.0
	 */	
	public function admin_addon() {
		$this->views['addon'] = apply_filters( 'membership_addon_view', new MS_View_Addon() );
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
			if( 'enable' == $action ) {
				$this->model->$addon = true;
			}
			elseif ( 'disable' == $action ) {
				$this->model->$addon = false;
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
		wp_register_script( 'ms_view_member_ui', MS_Plugin::instance()->url. 'app/assets/js/ms-view-member-ui.js', null, MS_Plugin::instance()->version );
		wp_enqueue_script( 'ms_view_member_ui' );		
	}
		
}