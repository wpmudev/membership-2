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
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Admin_Bar extends MS_Controller {
		
	/**
	 * Views to use for rendering admin bar features.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $views
	 */	
	private $views;	
	
	/**
	 * Admin bar nodes.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $nodes
	 */	
	private $nodes;
		
	/**
	 * Original Admin bar nodes.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $nodes
	 */	
	private $original_nodes;		
	
	/**
	 * Admin URL function to use.
	 *
	 * network_admin_url() or admin_url()
	 *
	 * @todo Think about use with global tables.
	 * @since 4.0.0
	 * @access private
	 * @var $nodes
	 */	
	private $admin_url_function;		
	
	
		
	/**
	 * Prepare the Admin Bar simulator.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		if( ! $this->is_admin_user() && MS_Plugin::instance()->settings->hide_admin_bar ) {
			add_filter( 'show_admin_bar', '__return_false' );
			$this->add_action( 'wp_before_admin_bar_render', 'customize_toolbar_front', 999 );
			$this->add_action( 'admin_head-profile.php', 'customize_admin_sidebar', 999 );
		}
		$this->add_action( 'wp_before_admin_bar_render', 'customize_toolbar', 999 );
		$this->add_action( 'add_admin_bar_menus', 'add_admin_bar_menus' );
		$this->add_action( 'admin_enqueue_scripts', 'enqueue_scripts');
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts');
		
		//TODO if global tables
		$this->admin_url_function = 0 ? 'network_admin_url' : 'admin_url';
	}

	/**
	 * Sets what menu to add to the admin bar.
	 *
	 * **Hooks Actions: **  
	 *  
	 * * add_admin_bar_menus
	 *
	 * @since 4.0.0
	 * @access public
	 */
	public function add_admin_bar_menus() {
		 
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );

		if( isset( $_GET['membership_id'] ) && $this->verify_nonce( 'ms_simulate-' . $_GET['membership_id'], 'GET' ) ) {
			$simulate->membership_id = $_GET['membership_id'];
			$simulate->save();
			wp_safe_redirect( wp_get_referer() );
		}

		if( ! empty( $_POST['simulate_submit'] ) ) {
			if( isset( $_POST['simulate_period_unit'] ) && in_array( $_POST['simulate_period_type'], MS_Helper_Period::get_periods() ) ) {
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
	 * @since 4.0.0
	 * @access private
	 * @param string[] String ID's of node's to exclude.
	 */
	private function remove_admin_bar_nodes( $exclude = array() ) {
		global $wp_admin_bar;
		$nodes = $wp_admin_bar->get_nodes();
		if( is_array( $nodes ) ) {
			foreach( $nodes as $node) {
				if ( is_array( $exclude) && ! in_array ( $node->id, $exclude ) ) {
					$wp_admin_bar->remove_node( $node->id );
				}
			}
		}
	}	
	
	/**
	 * Add simulation nodes.
	 *
	 * @since 4.0.0
	 * @access private
	 */
	private function add_simulator_nodes() {
		global $wp_admin_bar;
		$admin_url_func = $this->admin_url_function;
		
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );
				
		$memberships = MS_Model_Membership::get_memberships();
		if ( $simulate->is_simulating() ) {	
			$reset_simulation = (object) array(
					'id' => 0,
					'name' => __( 'Membership Admin', MS_TEXT_DOMAIN ),
			);
			$memberships[] = $reset_simulation;
			
			$membership = MS_Factory::load( 'MS_Model_Membership', $simulate->membership_id );
			
			$title = null;
			$html = null;
			if( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type ) {
				$view = apply_filters( 'membership_view_admin_bar', new MS_View_Admin_Bar() );
				$view->simulate_date = $simulate->date;
				$title = __( 'View on: ', MS_TEXT_DOMAIN );
				$html = $view->to_html();
			}
			elseif( MS_Model_Membership::PAYMENT_TYPE_FINITE == $membership->payment_type || $membership->has_dripped_content() ) {
				$view = apply_filters( 'membership_view_admin_bar', new MS_View_Admin_Bar() );
				$view->simulate_period_unit = $simulate->period['period_unit'];
				$view->simulate_period_type = $simulate->period['period_type'];
				$title = __( 'View in: ', MS_TEXT_DOMAIN );
				$html = $view->to_html();
			}
			if( $html ) {
				$wp_admin_bar->add_menu( 
						array(
							'id'     => 'membership-simulate-period',
							// 'parent' => 'top-secondary',
							'title'  => $title,
							'href'   => '',
							'meta'   => array(
									'html'  => $html,
									'class' => apply_filters( 'membership_controller_admin_bar_simulate_period_class', 'membership-simulate-period' ),
									'title' => __( 'Simulate period', MS_TEXT_DOMAIN ),
							),
						) 
				);
			}
		}
	}	


	/**
	 * Add 'View site as' node.
	 *
	 * Switches simulation views.
	 *
	 * @since 4.0.0
	 * @access private
	 */
	private function add_view_site_as_node() {
		global $wp_admin_bar;
		$admin_url_func = $this->admin_url_function;
		
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );
		$memberships = MS_Model_Membership::get_memberships();

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
		MS_Helper_Html::html_input( $action_field );
		MS_Helper_Html::html_input( $membership_field );
		MS_Helper_Html::html_input( $nonce_field );
		$html .= ob_get_clean();
		
		$html .= '</form>';
		
		$wp_admin_bar->add_node( array(
				'id'     => 'membership-simulate',
				'title'  => $title,
				'meta'   => array(
						'html'	 => $html,
						'class' => apply_filters( 'membership_controller_admin_bar_view_site_as_class', 'membership-view-site-as' ),
						'title' => __( 'Select a membership to view your site as', MS_TEXT_DOMAIN ),
				),
		) );

	}

	
	/**
	 * Add 'Test Memberships' node.
	 *
	 * @since 4.0.0
	 * @access private
	 */
	private function add_test_membership_node() {
		global $wp_admin_bar;

		$admin_url_func = $this->admin_url_function;

		$memberships = MS_Model_Membership::get_memberships();
		$id = ! empty( $memberships ) ? $memberships[0]->id : false;

		if ( $id ) {

			$link_url = wp_nonce_url( 
					$admin_url_func( 
							"?action=ms_simulate&membership_id={$id}", 
							( is_ssl() ? 'https' : 'http' ) 
					),
			 		"ms_simulate-{$id}"
			);

			$args = array(
				'id'     => 'ms-test-memberships',
				'title'  => __( 'Test Memberships', 'text_domain' ),
				'href'	 => $link_url,
				'meta'   => array(
					'class'    => 'ms-test-memberships',
					// 'onclick'  => 'doThisJS()',
					'title'    => 'Membership Simulation Menu',
					'tabindex' => '1',
				),
			);

			$wp_admin_bar->add_node( $args );
		}
	}	

	/**
	 * Add 'Test Memberships' node.
	 *
	 * @since 4.0.0
	 * @access private
	 */
	private function add_exit_test_node() {
		global $wp_admin_bar;

		$admin_url_func = $this->admin_url_function;
		
		$memberships = MS_Model_Membership::get_memberships();
		$id = 0;

			$link_url = wp_nonce_url( 
					$admin_url_func( 
							"?action=ms_simulate&membership_id={$id}", 
							( is_ssl() ? 'https' : 'http' ) 
					),
			 		"ms_simulate-{$id}"
			);

			$args = array(
				'id'     => 'ms-exit-memberships',
				'title'  => __( 'Exit Test Mode', 'text_domain' ),
				'href'	 => $link_url,
				'meta'   => array(
					'class'    => 'ms-exit-memberships',
					// 'onclick'  => 'doThisJS()',
					'title'    => 'Membership Simulation Menu',
					'tabindex' => '1',
				),
			);

			$wp_admin_bar->add_node( $args );

	}	


	/**
	 * Add 'Enable Memberships' node.
	 *
	 * @since 4.0.0
	 * @access private
	 */
	private function add_enable_membership_node() {
		global $wp_admin_bar;

		$linkurl = 'admin.php?page=protected-content-settings&tab=general&setting=plugin_enabled&action=toggle_activation';
		$linkurl = wp_nonce_url( $linkurl, 'toggle_activation' );
		
		$wp_admin_bar->add_node( array(
				'id'     => 'membership',
				'parent' => 'top-secondary',
				'title'  => __( 'Membership', MS_TEXT_DOMAIN ) . ' : <span class="ms-admin-bar-disabled">' . __( 'Disabled', MS_TEXT_DOMAIN ) . "</span>",
				'href'   => $linkurl,
				'meta'   => array(
						'title' => __( 'Click to Enable the Membership protection', MS_TEXT_DOMAIN ),
				),
		) );
	
		$wp_admin_bar->add_node( array(
				'parent' => 'membership',
				'id'     => 'membershipenable',
				'title'  => __( 'Enable Membership', MS_TEXT_DOMAIN ),
				'href'   => $linkurl,
		) );
	}	


	/**
	 * Customize the Admin Toolbar.
	 *
	 * **Hooks Actions: **  
	 *  
	 * * wp_before_admin_bar_render
	 *
	 * @since 4.0.0
	 * @access private
	 */
	public function customize_toolbar() {
		// $this->original_nodes = $this->get_original_node();
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );
		$method = '';
		if( MS_Model_Member::is_admin_user() ) {
			$method = MS_Plugin::is_enabled()
				? 'add_view_site_as_menu'
				: 'add_activate_plugin_menu';
		}
		// $this->add_test_membership_node();
		switch( $method ){
			case 'add_activate_plugin_menu':
				$this->add_enable_membership_node();			
				break;
			case 'add_view_site_as_menu':
				if ( $simulate->is_simulating() ){
					$this->remove_admin_bar_nodes();
					$this->add_view_site_as_node();				
					$this->add_simulator_nodes();
					$this->add_exit_test_node();
				} else {
					$this->add_test_membership_node();
				}
				break;
		}

	}
	
	/**
	 * Customize the Admin Toolbar for front end users.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_before_admin_bar_render
	 *
	 * @since 4.0.0
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
	 * @since 4.0.0
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
	 * Adds "View Site As" menu to admin bar.
	 *
	 * **Hooks Actions: **  
	 *  
	 * * add_admin_bar_menus  
	 *
	 * @since 4.0
	 * @access public
	 * @param object $wp_admin_bar WP_Admin_Bar object.
	 */
	public function add_view_site_as_menu( WP_Admin_Bar $wp_admin_bar ) {

		//TODO if global tables
		$admin_url_func = 0
		? 'network_admin_url'
				: 'admin_url';
		
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );
				
		$memberships = MS_Model_Membership::get_memberships();
		if ( $simulate->is_simulating() ) {	
			$reset_simulation = (object) array(
					'id' => 0,
					'name' => __( 'Membership Admin', MS_TEXT_DOMAIN ),
			);
			$memberships[] = $reset_simulation;
			
			$membership = MS_Factory::load( 'MS_Model_Membership', $simulate->membership_id );
			
			$title = null;
			if( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type ) {
				$view = apply_filters( 'membership_view_admin_bar', new MS_View_Admin_Bar() );
				$view->simulate_date = $simulate->date;
				$title = $view->to_html();
			}
			elseif( MS_Model_Membership::PAYMENT_TYPE_FINITE == $membership->payment_type || $membership->has_dripped_content() ) {
				$view = apply_filters( 'membership_view_admin_bar', new MS_View_Admin_Bar() );
				$view->simulate_period_unit = $simulate->period['period_unit'];
				$view->simulate_period_type = $simulate->period['period_type'];
				
				$title = $view->to_html();
			}
			if( $title ) {
				$wp_admin_bar->add_menu( 
						array(
							'id'     => 'membership-simulate-period',
							'parent' => 'top-secondary',
							'title'  => $title,
							'href'   => '',
							'meta'   => array(
									'class' => apply_filters( 'membership_controller_admin_bar_simulate_period_class', 'membership-view-site-as' ),
									'title' => __( 'Simulate period', MS_TEXT_DOMAIN ),
							),
						) 
				);
			}
		}
		
		$title = __( 'View site as: ', MS_TEXT_DOMAIN );
		
		if( $simulate->is_simulating() ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $simulate->membership_id );
			$title .= $membership->name;
		}
		else {
			$title .= __( 'Membership Admin', MS_TEXT_DOMAIN );
		}
		$wp_admin_bar->add_menu( array(
				'id'     => 'membership-simulate',
				'parent' => 'top-secondary',
				'title'  => $title,
				'href'   => '',
				'meta'   => array(
						'class' => apply_filters( 'membership_controller_admin_bar_view_site_as_class', 'membership-view-site-as' ),
						'title' => __( 'Select a membership to view your site as', MS_TEXT_DOMAIN ),
				),
		) );
		
		if ( !empty( $memberships ) ) {
			foreach ( $memberships as $membership ) {
				$link_url = wp_nonce_url( 
						$admin_url_func( 
								"?action=ms_simulate&membership_id={$membership->id}", 
								( is_ssl() ? 'https' : 'http' ) 
						),
				 		"ms_simulate-{$membership->id}"
				);
				$wp_admin_bar->add_menu( 
						array(
							'parent' => 'membership-simulate',
							'id' => 'membership-' . $membership->id,
							'title' => $membership->name,
							'href' => $link_url
						) 
				);
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
	 * @since 4.0.0
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