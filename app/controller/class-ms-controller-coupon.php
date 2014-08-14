<?php
/**
 * This file defines the MS_Controller_Coupon class.
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
 * Controller to manage Membership coupons.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Coupon extends MS_Controller {

	/**
	 * The model to use for loading/saving coupon data.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */	
	private $model;

	/**
	 * View to use for rendering coupon settings.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $views
	 */	
	private $views;

	/**
	 * Prepare the Coupon manager.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		$this->add_action( 'load-membership_page_membership-coupons', 'admin_coupon_manager' );
		
		$this->add_action( 'admin_print_scripts-membership_page_membership-coupons', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-membership_page_membership-coupons', 'enqueue_styles' );
	}
	
	/**
	 * Manages coupon actions.
	 *
	 * Verifies GET and POST requests to manage billing.
	 *
	 * @since 4.0.0
	 */
	public function admin_coupon_manager() {
		/**
		 * Save coupon add/edit
		 */
		if ( ! empty( $_POST['submit'] ) && ! empty( $_POST['_wpnonce'] )  && ! empty(  $_POST['action'] ) && check_admin_referer( $_POST['action'] ) ) {
			$section = MS_View_Coupon_Edit::COUPON_SECTION;
			if( ! empty( $_POST[ $section ] ) ) {
				$msg = $this->save_coupon( $_POST[ $section ] );
			}
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'action', 'coupon_id') ) ) ) ;
		}
		/**
		 * Execute table single action.
		 */
		elseif( ! empty( $_GET['action'] ) && ! empty( $_GET['coupon_id'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) ) {
			$msg = $this->coupon_do_action( $_GET['action'], array( $_GET['coupon_id'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ), remove_query_arg( array( 'coupon_id', 'action', '_wpnonce' ) ) ) );
		}
		/**
		 * Execute bulk actions.
		 */
		elseif( ! empty( $_POST['coupon_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-coupons' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->coupon_do_action( $action, $_POST['coupon_id'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
		}
	}
	
	/**
	 * Perform actions for each coupon.
	 *
	 *
	 * @since 4.0.0
	 * @param string $action The action to perform on selected coupons
	 * @param int[] $coupons The list of coupons ids to process.
	 */
	public function coupon_do_action( $action, $coupon_ids ) {
		if( ! $this->is_admin_user() ) {
			return;
		}
		
		if( is_array( $coupon_ids ) ) {
			foreach( $coupon_ids as $coupon_id ) {
				switch( $action ) {
					case 'delete':
						$coupon = MS_Factory::get_factory()->load_coupon( $coupon_id );
						$coupon->delete();
						break;
				}			
			}
		}
	}
	
	/**
	 * Render the Coupon admin manager.
	 *
	 * @since 4.0.0
	 */	
	public function admin_coupon() {
		/**
		 * Edit action view page request
		 */
		if( ! empty( $_GET['action'] ) && 'edit' == $_GET['action'] && isset( $_GET['coupon_id'] ) ) {
			$coupon_id = ! empty( $_GET['coupon_id'] ) ? $_GET['coupon_id'] : 0;
			$this->model = apply_filters( 'ms_model_coupon', MS_Factory::get_factory()->load_coupon( $coupon_id ), $coupon_id );
			$data['coupon'] = $this->model;
			$data['memberships'] = MS_Model_Membership::get_membership_names();
			$data['memberships'][0] = __( 'Any', MS_TEXT_DOMAIN );
			$data['action'] = $_GET['action'];
			
			$this->views['edit'] = apply_filters( 'ms_view_coupon_edit', new MS_View_Coupon_Edit() );
			$this->views['edit']->data = $data;
			$this->views['edit']->render();
		}
		/**
		 * Coupon admin list page 
		 */
		else {
			$this->views['coupon'] = apply_filters( 'ms_view_coupon_list', new MS_View_Coupon_List() );
			$this->views['coupon']->render();
		}
	}

	/**
	 * Save coupon using the coupon model.
	 *
	 * @since 4.0.0
	 * @param mixed $fields Coupon fields
	 */
	private function save_coupon( $fields ) {
		if( ! $this->is_admin_user() ) {
			return;
		}
		
		if( is_array( $fields ) ) {
			$coupon_id = ( $fields['coupon_id'] ) ? $fields['coupon_id'] : 0;
			$this->model = apply_filters( 'ms_model_coupon', MS_Factory::get_factory()->load_coupon( $coupon_id ), $coupon_id );
				
			foreach( $fields as $field => $value ) {
				$this->model->$field = $value;
			}				
			$this->model->save();
		}
	}
	
	/**
	 * Load Coupon specific styles.
	 *
	 * @since 4.0.0
	 */
	public function enqueue_styles() {
		if( ! empty($_GET['action']  ) && 'edit' == $_GET['action'] ) {
			wp_enqueue_style( 'jquery-ui' );
		}
	}
	
	/**
	 * Load Coupon specific scripts.
	 *
	 * @since 4.0.0
	 */
	public function enqueue_scripts() {
		if( ! empty($_GET['action']  ) && 'edit' == $_GET['action'] ) {
			wp_enqueue_script( 'jquery-ui' );
			wp_enqueue_script( 'jquery-validate' );
			wp_enqueue_script( 'ms-view-coupon-edit', MS_Plugin::instance()->url. 'app/assets/js/ms-view-coupon-edit.js', null, MS_Plugin::instance()->version );
		}
	}
}