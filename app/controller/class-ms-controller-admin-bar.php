<?php
/**
 * This file defines the MS_Controller_Admin_Bar class.
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
 * Controller to add functionality to the admin bar.
 *
 * Used extensively for simulating memberships and content access.
 *
 * Adds ability for Membership users to test the behaviour for their end-users.
 *
 * @since 1.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Admin_Bar extends MS_Controller {
		
	/**
	 * Prepare the Admin Bar simulator.
	 *
	 * @since 1.0
	 */		
	public function __construct() {
		/** Hide WP toolbar in fron end to not admin users */ 
		if( ! $this->is_admin_user() && MS_Plugin::instance()->settings->hide_admin_bar ) {
			add_filter( 'show_admin_bar', '__return_false' );
			$this->add_action( 'wp_before_admin_bar_render', 'customize_toolbar_front', 999 );
			$this->add_action( 'admin_head-profile.php', 'customize_admin_sidebar', 999 );
		}
		/** Customize WP toolbar for admin users */
		if( $this->is_admin_user() ) {
			$this->add_action( 'wp_before_admin_bar_render', 'customize_toolbar', 999 );
			$this->add_action( 'add_admin_bar_menus', 'admin_bar_manager' );
			$this->add_action( 'admin_enqueue_scripts', 'enqueue_scripts');
			$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts');
		}
	}
	
	/**
	 * Customize the Admin Toolbar.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_before_admin_bar_render
	 *
	 * @since 1.0
	 * @access private
	 */
	public function customize_toolbar() {
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );

		/** @todo Prepare for network admin/multisite */
		if( MS_Model_Member::is_admin_user() && MS_Plugin::is_enabled() && ! is_network_admin() ) {
			if( $simulate->is_simulating() ){
				$this->remove_admin_bar_nodes();
				$this->add_view_site_as_node();
				$this->add_simulator_nodes();
				$this->add_exit_test_node();
			}
			else {
				$this->add_test_membership_node();
			}
		}
	}

	/**
	 * Process GET and POST requests
	 *
	 * **Hooks Actions: **  
	 *  
	 * * add_admin_bar_menus
	 *
	 * @since 1.0
	 * @access public
	 */
	public function admin_bar_manager() {

		$simulate = MS_Factory::load( 'MS_Model_Simulate' );

		/** Check for memberhship id simulation GET request */
		if( isset( $_GET['membership_id'] ) && $this->verify_nonce( 'ms_simulate-' . $_GET['membership_id'], 'GET' ) ) {
			$simulate->membership_id = $_GET['membership_id'];
			$simulate->save();
			wp_safe_redirect( wp_get_referer() );
		}

		/** Check for simulation periods/dates in POST request */
		if( ! empty( $_POST['simulate_submit'] ) ) {
			if( isset( $_POST['simulate_period_unit'] )  ) {
				$simulate->period = array( 'period_unit' => $_POST['simulate_period_unit'], 'period_type' => $_POST['simulate_period_type'] );
				$simulate->save();
			}
			elseif( ! empty( $_POST['simulate_date'] ) ) {
				$simulate->date = $_POST['simulate_date'];
				$simulate->save();
			}
			wp_safe_redirect( wp_get_referer() );
		}
	}
	
	/**
	 * Remove all Admin Bar nodes.
	 *
	 * @since 1.0
	 * @access private
	 * @param string[] String ID's of node's to exclude.
	 */
	private function remove_admin_bar_nodes( $exclude = array() ) {
		global $wp_admin_bar;
		
		$nodes = $wp_admin_bar->get_nodes();
		
		$exclude = apply_filters( 'ms_controller_admin_bar_remove_admin_bar_nodes_exclude', $exclude, $nodes );
		do_action( 'ms_controller_admin_bar_remove_admin_bar_nodes', $nodes, $exclude );
		
		if( is_array( $nodes ) ) {
			foreach( $nodes as $node) {
				if( is_array( $exclude) && ! in_array ( $node->id, $exclude ) ) {
					$wp_admin_bar->remove_node( $node->id );
				}
			}
		}
	}	
	
	/**
	 * Add simulation nodes.
	 *
	 * @since 1.0
	 * @access private
	 */
	private function add_simulator_nodes() {
		global $wp_admin_bar;
		
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );
				
		$memberships = MS_Model_Membership::get_memberships( array( 'include_visitor' => 1 ) );
		if ( $simulate->is_simulating() ) {	
			$reset_simulation = (object) array(
					'id' => 0,
					'name' => __( 'Membership Admin', MS_TEXT_DOMAIN ),
			);
			$memberships[] = $reset_simulation;
			
			$membership = MS_Factory::load( 'MS_Model_Membership', $simulate->membership_id );
			$title = null;
			$html = null;
			$data = array();
			
			if( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type || 
					( MS_Model_Membership::TYPE_DRIPPED == $membership->type && MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE == $membership->dripped_type ) ) {
				$view = MS_Factory::create( 'MS_View_Admin_Bar' );
				$data['simulate_date'] = $simulate->date;
				$data['period_unit'] = null;
				$data['period_type'] = null;
				$view->data = apply_filters( 'ms_view_admin_bar_data', $data );
				$title = __( 'View on: ', MS_TEXT_DOMAIN );
				$html = $view->to_html();
			}
			elseif( MS_Model_Membership::PAYMENT_TYPE_FINITE == $membership->payment_type || $membership->has_dripped_content() ) {
				$view = MS_Factory::create( 'MS_View_Admin_Bar' );
				$data['simulate_date'] = null;
				$data['period_unit'] = $simulate->period['period_unit'];
				$data['period_type'] = $simulate->period['period_type'];
				$view->data = apply_filters( 'ms_view_admin_bar_data', $data );
				$title = __( 'View in: ', MS_TEXT_DOMAIN );
				$html = $view->to_html();
			}
			
			if( $html ) {
				$wp_admin_bar->add_menu( apply_filters( 'ms_controller_admin_bar_simulate_node', array(
							'id'     => 'membership-simulate-period',
							'title'  => $title,
							'href'   => '',
							'meta'   => array(
									'html'  => $html,
									'class' => apply_filters( 'ms_controller_admin_bar_simulate_period_class', 'membership-simulate-period' ),
									'title' => __( 'Simulate period', MS_TEXT_DOMAIN ),
							),
				) ) );
			}
		}
	}	

	/**
	 * Add 'View site as' node.
	 *
	 * Switches simulation views.
	 *
	 * @since 1.0
	 * @access private
	 */
	private function add_view_site_as_node() {
		global $wp_admin_bar;
		
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );
		$memberships = MS_Model_Membership::get_memberships( array( 'include_visitor' => 1 ) );
		
		$title = __( 'View site as: ', MS_TEXT_DOMAIN );

		$select_options = array();
		
		$html = '<form id="view-site-as" method="GET">';
		
		if ( !empty( $memberships ) ) {
			foreach ( $memberships as $membership ) {
				// Create nonce fields
				$nonce = wp_create_nonce( "ms_simulate-{$membership->id}" );
				// Create options for <select>
				$selected = selected( $simulate->membership_id, $membership->id, false );
				$select_options[] = "<option value=\"{$membership->id}\" {$selected} nonce=\"{$nonce}\">{$membership->name}</option>";
			}
		}
					
		$html .= '<select id="view-as-selector" class="ms-field-input ms-select ab-select" name="view-as-selector">';
		foreach( $select_options as $option ) {
			$html .= $option;
		}
		$html .= '</select>';
					
		$action_field = array(
			'name'      => 'action',
			'value'		=> 'ms_simulate',
			'type'    	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,					
		);
		$membership_field = array(
			'id'		=> 'ab-membership-id',
			'name'      => 'membership_id',
			'value'		=> $simulate->membership_id,
			'type'    	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,					
		);
		$nonce_field = array(
			'id'		=> '_wpnonce',
			'name'      => '_wpnonce',
			'value'		=> '',
			'type'    	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,					
		);				
									
		ob_start();
		MS_Helper_Html::html_element( $action_field );
		MS_Helper_Html::html_element( $membership_field );
		MS_Helper_Html::html_element( $nonce_field );
		$html .= ob_get_clean();
		
		$html .= '</form>';
		
		$wp_admin_bar->add_node( apply_filters( 'ms_controller_admin_bar_add_view_site_as_node', array(
				'id'     => 'membership-simulate',
				'title'  => $title,
				'meta'   => array(
						'html'	 => $html,
						'class' => apply_filters( 'ms_controller_admin_bar_view_site_as_class', 'membership-view-site-as' ),
						'title' => __( 'Select a membership to view your site as', MS_TEXT_DOMAIN ),
				),
		) ) );

	}

	/**
	 * Add 'Test Memberships' node.
	 *
	 * @since 1.0
	 * @access private
	 */
	private function add_test_membership_node() {
		global $wp_admin_bar;

		$memberships = MS_Model_Membership::get_memberships( array( 'include_visitor' => 1 ) );
		$id = ! empty( $memberships ) ? $memberships[0]->id : false;

		if( $id ) {

			$link_url = wp_nonce_url( 
					admin_url( "?action=ms_simulate&membership_id={$id}", ( is_ssl() ? 'https' : 'http' ) ),
			 		"ms_simulate-{$id}"
			);

			$wp_admin_bar->add_node( apply_filters( 'ms_controller_admin_bar_add_test_membership_node', array(
					'id'     => 'ms-test-memberships',
					'title'  => __( 'Test Memberships', MS_TEXT_DOMAIN ),
					'href'	 => $link_url,
					'meta'   => array(
						'class'    => 'ms-test-memberships',
						'title'    => __( 'Membership Simulation Menu', MS_TEXT_DOMAIN ),
						'tabindex' => '1',
					),
			) ) );
		}
	}	

	/**
	 * Add 'Test Memberships' node.
	 *
	 * @since 1.0
	 * @access private
	 */
	private function add_exit_test_node() {
		global $wp_admin_bar;

		/** reset simulation */
		$id = 0;
		$link_url = wp_nonce_url( 
				admin_url( "?action=ms_simulate&membership_id={$id}", ( is_ssl() ? 'https' : 'http' ) ),
		 		"ms_simulate-{$id}"
		);

		$wp_admin_bar->add_node( apply_filters( 'ms_controller_admin_bar_add_exit_test_node', array(
				'id'     => 'ms-exit-memberships',
				'title'  => __( 'Exit Test Mode', MS_TEXT_DOMAIN ),
				'href'	 => $link_url,
				'meta'   => array(
					'class'    => 'ms-exit-memberships',
					'title'    => __( 'Membership Simulation Menu', MS_TEXT_DOMAIN ),
					'tabindex' => '1',
				),
		) ) );
	}	

	/**
	 * Customize the Admin Toolbar for front end users.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_before_admin_bar_render
	 *
	 * @since 1.0
	 * @access private
	 */
	public function customize_toolbar_front() {
		if( ! $this->is_admin_user() ) {
			$this->remove_admin_bar_nodes();
		}
	}
	
	/**
	 * Customize the Admin sidebar for front end users.
	 *
	 * **Hooks Actions: **
	 *
	 * * admin_head-profile.php
	 *
	 * @since 1.0
	 * @access private
	 */
	public function customize_admin_sidebar() {
		if( ! $this->is_admin_user() ) {
			global $menu;
			foreach( $menu as $key => $menu_item ) {
				unset( $menu[ $key ] );
			}
		}
	}
	
	/**
	 * Enqueues necessary scripts and styles.
	 *
	 * **Hooks Actions: **  
	 * 
	 * * wp_enqueue_scripts  
	 * * admin_enqueue_scripts  
	 *
	 * @since 1.0
	 */
	function enqueue_scripts() {
		wp_register_script( 'ms-controller-admin-bar', MS_Plugin::instance()->url. 'app/assets/js/ms-controller-admin-bar.js', array( 'jquery' ), MS_Plugin::instance()->version );
		wp_localize_script( 'ms-controller-admin-bar', 'ms', array( 'switching_text' => __( 'Switching...', MS_TEXT_DOMAIN ) ) );
		wp_enqueue_script( 'ms-controller-admin-bar' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'ms-admin-bar', MS_Plugin::instance()->url. 'app/assets/css/ms-admin-bar.css', null, MS_Plugin::instance()->version );
		wp_enqueue_style( 'jquery-ui' );
	}
}