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
 * @since 1.0.0
 * 
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Coupon extends MS_Controller {

	/**
	 * Prepare the Coupon manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$hook = 'protect-content_page_protected-content-coupons';
		$this->add_action( 'load-' . $hook, 'admin_coupon_manager' );

		$this->add_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
	}

	/**
	 * Manages coupon actions.
	 *
	 * Verifies GET and POST requests to manage billing.
	 *
	 * @since 1.0.0
	 */
	public function admin_coupon_manager() {

		/**
		 * Save coupon add/edit
		 */
		$isset = array( 'submit', 'membership_id' );
		if ( $this->validate_required( $isset, 'POST', false ) && $this->verify_nonce() && $this->is_admin_user() ) {
			$msg = $this->save_coupon( $_POST );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ), remove_query_arg( array( 'coupon_id') ) ) ) ;
			exit;
		}
		/**
		 * Execute table single action.
		 */
		elseif( $this->validate_required( array( 'coupon_id', 'action' ), 'GET' ) && $this->verify_nonce( $_GET['action'], 'GET' ) && $this->is_admin_user() ) {
			$msg = $this->coupon_do_action( $_GET['action'], array( $_GET['coupon_id'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ), remove_query_arg( array( 'coupon_id', 'action', '_wpnonce' ) ) ) );
			exit;
		}
		/**
		 * Execute bulk actions.
		 */
		elseif( $this->validate_required( array( 'coupon_id' ) ) && $this->verify_nonce( 'bulk-coupons' ) && $this->is_admin_user() ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->coupon_do_action( $action, $_POST['coupon_id'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
			exit;
		}
	}

	/**
	 * Perform actions for each coupon.
	 *
	 *
	 * @since 1.0.0
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
						$coupon = MS_Factory::load( 'MS_Model_Coupon', $coupon_id );
						$coupon->delete();
						break;
				}
			}
		}
	}

	/**
	 * Render the Coupon admin manager.
	 *
	 * @since 1.0.0
	 */
	public function admin_coupon() {
		/**
		 * Edit action view page request
		 */
		$isset = array( 'action', 'coupon_id' );
		if( $this->validate_required( $isset, 'GET', false ) && 'edit' == $_GET['action'] ) {
			$coupon_id = ! empty( $_GET['coupon_id'] ) ? $_GET['coupon_id'] : 0;
			$data['coupon'] = MS_Factory::load( 'MS_Model_Coupon', $coupon_id );
			$data['memberships'] = MS_Model_Membership::get_membership_names();
			$data['memberships'][0] = __( 'Any', MS_TEXT_DOMAIN );
			$data['action'] = $_GET['action'];

			$view = MS_Factory::create( 'MS_View_Coupon_Edit' );
			$view->data = apply_filters( 'ms_view_coupon_edit_data', $data );
			$view->render();
		}
		/**
		 * Coupon admin list page
		 */
		else {
			$view = MS_Factory::create( 'MS_View_Coupon_List' );
			$view->render();
		}
	}

	/**
	 * Save coupon using the coupon model.
	 *
	 * @since 1.0.0
	 * 
	 * @param mixed $fields Coupon fields
	 * @return boolean True in success saving.
	 */
	private function save_coupon( $fields ) {
		
		$coupon = null;
		$msg = false;
		
		if( $this->is_admin_user() ) {
			if( is_array( $fields ) ) {
				$coupon_id = ( $fields['coupon_id'] ) ? $fields['coupon_id'] : 0;
				$coupon = MS_Factory::load( 'MS_Model_Coupon', $coupon_id );
	
				foreach( $fields as $field => $value ) {
					$coupon->$field = $value;
				}
				$coupon->save();
				$msg = true;
			}
		}
				
		return apply_filters( 'ms_model_coupon_save_coupon', $msg, $fields, $coupon, $this );
	}

	/**
	 * Load Coupon specific styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		if ( 'edit' == @$_GET['action'] ) {
			wp_enqueue_style( 'jquery-ui' );
		}
		
		do_action( 'ms_controller_coupon_enqueue_styles', $this );
	}

	/**
	 * Load Coupon specific scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( 'edit' == @$_GET['action'] ) {
			wp_enqueue_script( 'jquery-ui' );
			wp_enqueue_script( 'jquery-validate' );
			wp_enqueue_script( 'ms-view-coupon-edit' );
		}
		
		do_action( 'ms_controller_coupon_enqueue_scripts', $this );
	}
}