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
 * Controller to manage billing and transactions.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Billing extends MS_Controller {

	/**
	 * The custom post type used with Billing.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $post_type
	 */
	private $post_type;

	/**
	 * Capability required to manage Billing.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $capability
	 */		
	private $capability = 'manage_options';
	
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
	 * Manages billing actions.
	 *
	 * Verifies GET and POST requests to manage billing.
	 *
	 * @since 4.0.0	
	 */
	public function admin_billing_manager() {
		$msg = 0;
		/**
		 * Save membership general tab
		 */
		$nonce = MS_View_Billing_Edit::BILLING_NONCE;
		if ( ! empty( $_POST['submit'] ) && ! empty( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
			$section = MS_View_Billing_Edit::BILLING_SECTION;
			if( ! empty( $_POST[ $section ] ) ) {
				$msg = $this->save_transaction( $_POST[ $section ] );
			}
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'transaction_id') ) ) ) ;
		}
		/**
		 * Execute table single action.
		 */
		elseif( ! empty( $_GET['action'] ) && ! empty( $_GET['transaction_id'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'] ) ) {
			$msg = $this->billing_do_action( $_GET['action'], array( $_GET['transaction_id'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ), remove_query_arg( array( 'member_id', 'action', '_wpnonce' ) ) ) );
			die();
		}
		/**
		 * Execute bulk actions.
		 */
		elseif( ! empty( $_POST['transaction_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-billings' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->billing_do_action( $action, $_POST['transaction_id'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
		}
	}
	
	/**
	 * Sets up the 'Billing' navigation and list page.
	 *
	 * @since 4.0.0	
	 */
	public function admin_billing() {
		/**
		 * Action view page request
		 */
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['transaction_id'] ) ) {
			if( 'edit' == $_GET['action'] ) {
				$this->views['edit'] = apply_filters( 'membership_billing_view', new MS_View_Billing_Edit() );
				$data['transaction'] = MS_Model_Transaction::load( $_GET['transaction_id'] );
				$data['action'] = $_GET['action'];
				$this->views['edit']->data = $data;
				$this->views['edit']->render();
			}
		}
		else {
			$this->views['billing'] = apply_filters( 'membership_billing_view', new MS_View_Billing_List() );
			$this->views['billing']->render();
		}
	}

	/**
	 * Perform actions for each transaction.
	 *
	 * @todo Still incomplete.
	 *
	 * @since 4.0.0	
	 * @param string $action The action to perform on selected transactions
	 * @param object[] $transactions The list of transactions to process.
	 */	
	public function billing_do_action( $action, $transactions ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		foreach( $transactions as $transaction_id ) {
			
		}
	}

	/**
	 * Save transactions using the transactions model.
	 *
	 * @since 4.0.0	
	 * @param mixed $fields Transaction fields
	 */	
	public function save_transaction( $fields ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		if( is_array( $fields ) ) {
			$this->model = apply_filters( 'ms_model_transaction', MS_Model_Transaction::load( $fields['transaction_id'] ) );
			if( ! empty( $fields['execute'] ) ) {
				$this->model->process_transaction( $fields['status'] );
			}
			foreach( $fields as $field => $value ) {
				if( property_exists( $this->model, $field ) ) {
					$this->model->$field = $value;
				}
			}
			$this->model->save();
		}
		
	}

	/**
	 * Load Billing specific styles.
	 *
	 * @since 4.0.0
	 */	
	public function enqueue_styles() {
		if( ! empty($_GET['action']  ) && 'edit' == $_GET['action'] ) {
			wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
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
			wp_enqueue_script( 'ms-view-billing-edit', MS_Plugin::instance()->url. 'app/assets/js/ms-view-billing-edit.js', null, MS_Plugin::instance()->version );
			wp_enqueue_script( 'jquery-validate', MS_Plugin::instance()->url. 'app/assets/js/jquery.validate.js', array( 'jquery' ), MS_Plugin::instance()->version );
		}
	}
	
}