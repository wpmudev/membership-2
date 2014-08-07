<?php
/**
 * This file defines the MS_Controller_Billing class.
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
 * Controller to manage billing and invoices.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Billing extends MS_Controller {
	
	/**
	 * The model to use for loading/saving billing data.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */	
	private $model;

	/**
	 * View to use for rendering billing settings and lists.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $views
	 */	
	private $views;

	/**
	 * Prepare the Billing manager.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		$this->add_action( 'load-membership_page_membership-billing', 'admin_billing_manager' );
		
		$this->add_action( 'admin_print_scripts-membership_page_membership-billing', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-membership_page_membership-billing', 'enqueue_styles' );
	}
	
	/**
	 * Show admin notices.
	 *
	 * @since 4.0.0
	 *
	 */
	public function print_admin_message() {
		add_action( 'admin_notices', array( 'MS_Helper_Billing', 'print_admin_message' ) );
	}
	
	/**
	 * Manages billing actions.
	 *
	 * Verifies GET and POST requests to manage billing.
	 *
	 * @since 4.0.0	
	 */
	public function admin_billing_manager() {
		$this->print_admin_message();
		$msg = 0;

		/**
		 * Save billing add/edit
		 */
		if ( ! empty( $_POST['submit'] ) && ! empty( $_POST['_wpnonce'] )  && ! empty(  $_POST['action'] ) && check_admin_referer( $_POST['action'] ) ) {
			$section = MS_View_Billing_Edit::BILLING_SECTION;
			if( ! empty( $_POST[ $section ] ) ) {
				$msg = $this->save_invoice( $_POST[ $section ] );
			}
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ), remove_query_arg( array( 'invoice_id') ) ) ) ;
		}
		/**
		 * Execute table single action.
		 */
		elseif( ! empty( $_GET['action'] ) && ! empty( $_GET['invoice_id'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'] ) ) {
			$msg = $this->billing_do_action( $_GET['action'], array( $_GET['invoice_id'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ), remove_query_arg( array( 'member_id', 'action', '_wpnonce' ) ) ) );
			die();
		}
		/**
		 * Execute bulk actions.
		 */
		elseif( ! empty( $_POST['invoice_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-billings' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->billing_do_action( $action, $_POST['invoice_id'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
		}
	}
	
	/**
	 * Sets up the 'Billing' navigation and list page.
	 *
	 * @since 4.0.0	
	 */
	public function admin_billing() {
		$this->print_admin_message();
		/**
		 * Action view page request
		 */
		if( ! empty( $_GET['action'] ) && 'edit' == $_GET['action'] && isset( $_GET['invoice_id'] ) ) {
			$invoice_id = ! empty( $_GET['invoice_id'] ) ? $_GET['invoice_id'] : 0;
			$data['invoice'] =  apply_filters( 'ms_model_invoice', MS_Factory::get_factory()->load_invoice( $_GET['invoice_id'] ) );
			$data['action'] = $_GET['action'];
			$data['users'] = MS_Model_Member::get_members_usernames();
			$data['gateways'] = MS_Model_Gateway::get_gateway_names();
			$data['memberships'] = MS_Model_Membership::get_membership_names();
			$this->views['edit'] = apply_filters( 'ms_view_billing_edit', new MS_View_Billing_Edit() );
			$this->views['edit']->data = $data;
			$this->views['edit']->render();
		}
		else {
			$this->views['billing'] = apply_filters( 'ms_view_billing_list', new MS_View_Billing_List() );
			$this->views['billing']->render();
		}
	}

	/**
	 * Perform actions for each invoice.
	 *
	 * @todo Still incomplete.
	 *
	 * @since 4.0.0	
	 * @param string $action The action to perform on selected invoices
	 * @param int[] $invoice_ids The list of invoices ids to process.
	 */	
	public function billing_do_action( $action, $invoice_ids ) {
		$msg = MS_Helper_Billing::BILLING_MSG_NOT_UPDATED;
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}

		if( is_array( $invoice_ids ) ) {
			foreach( $invoice_ids as $invoice_id ) {
				switch( $action ) {
					case 'delete':
						$invoice = MS_Factory::get_factory()->load_invoice( $invoice_id );
						$invoice->delete();
						$msg = MS_Helper_Billing::BILLING_MSG_DELETED;
						break;
				}
			}
		}
		return $msg;
	}

	/**
	 * Save invoices using the invoices model.
	 *
	 * @since 4.0.0	
	 * @param mixed $fields Transaction fields
	 */	
	public function save_invoice( $fields ) {
		
		$msg = MS_Helper_Billing::BILLING_MSG_NOT_UPDATED;
		
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}
		
		if( is_array( $fields ) && ! empty( $fields['user_id'] ) && ! empty( $fields['membership_id'] ) && ! empty( $fields['gateway_id'] ) ) {

			$member = MS_Factory::get_factory()->load_member( $fields['user_id'] );
			$membership_id = $fields['membership_id'];
			$gateway_id = $fields['gateway_id'];
			
			$ms_relationship = MS_Model_Membership_Relationship::get_membership_relationship( $member->id, $membership_id );
			if( empty( $ms_relationship ) ){
				$ms_relationship = MS_Model_Membership_Relationship::create_ms_relationship( $membership_id, $member->id, $gateway_id );
			}
			else {
				$ms_relationship->gateway_id = $gateway_id;
				$ms_relationship->save();
			}
			
			$invoice = apply_filters( 'ms_model_invoice', MS_Factory::get_factory()->load_invoice( $fields['invoice_id'] ) );
			if( ! $invoice->is_valid() ) {
				$invoice = MS_Model_Invoice::create_invoice( $ms_relationship );
				$msg = MS_Helper_Billing::BILLING_MSG_ADDED;
			}
			else {
				$msg = MS_Helper_Billing::BILLING_MSG_UPDATED;
			}

			foreach( $fields as $field => $value ) {
				$invoice->$field = $value;
			}

			$invoice->save();
		
			if( ! empty( $fields['execute'] ) ) {
				$gateway = $ms_relationship->get_gateway();
				$gateway->process_transaction( $invoice );
			}
		}
		
		return $msg;	
	}

	/**
	 * Load Billing specific styles.
	 *
	 * @since 4.0.0
	 */	
	public function enqueue_styles() {
		if( ! empty($_GET['action']  ) && 'edit' == $_GET['action'] ) {
			wp_enqueue_style( 'jquery-ui' );
		}
	}

	/**
	 * Load Billing specific scripts.
	 *
	 * @since 4.0.0
	 */	
	public function enqueue_scripts() {
		if( ! empty($_GET['action']  ) && 'edit' == $_GET['action'] ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-validate' );
			wp_enqueue_script( 'ms-view-billing-edit', MS_Plugin::instance()->url. 'app/assets/js/ms-view-billing-edit.js', null, MS_Plugin::instance()->version );
		}
	}
	
}