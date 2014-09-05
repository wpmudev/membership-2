<?php
/**
 * This file defines the MS_Controller_Membership class.
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
 * Controller for managing Memberships and Membership Rules.
 *
 * Focuses on Memberships and specifying access to content.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Membership extends MS_Controller {
	
	const AJAX_ACTION_TOGGLE_MEMBERSHIP = 'toggle_membership';
	
	const STEP_MS_LIST = 'ms_list';
	const STEP_OVERVIEW = 'ms_overview';
	const STEP_NEWS = 'ms_news';
	const STEP_SETUP_PROTECTED_CONTENT = 'setup_protected_content';
	const STEP_CHOOSE_MS_TYPE = 'choose_ms_type';
	const STEP_ACCESSIBLE_CONTENT = 'accessible_content';
	const STEP_SETUP_PAYMENT = 'setup_payment';
	
	const STEP_SETUP_CONTENT_TYPES = 'setup_content_types';
	const STEP_SETUP_MS_TIERS = 'setup_ms_tiers';
	const STEP_SETUP_DRIPPED = 'setup_dripped';
	
	/**
	 * The model to use for loading/saving Membership data.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */
	private $model;
	
	protected $active_tab;
	
	/**
	 * Prepare the Membership manager.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		$protected_content_menu_hook = 'toplevel_page_protected-content';
		$protected_content_setup_hook = 'protected-content_page_protected-content-setup';
		
		$this->add_action( 'load-' . $protected_content_menu_hook, 'membership_admin_page_process' );
		$this->add_action( 'load-' . $protected_content_setup_hook, 'membership_admin_page_process' );
		
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_MEMBERSHIP, 'ajax_action_toggle_membership' );
		
		$this->add_action( 'admin_print_scripts-' . $protected_content_setup_hook, 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-' . $protected_content_setup_hook, 'enqueue_styles' );
		
		$this->add_action( 'admin_print_scripts-' . $protected_content_menu_hook, 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-' . $protected_content_menu_hook, 'enqueue_styles' );
	}
	
	/**
	 * Handle Ajax toggle action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_toggle_membership
	 *
	 * @since 4.0.0
	 */
	public function ajax_action_toggle_membership() {
		$msg = 0;
		if( $this->verify_nonce() && ! empty( $_POST['membership_id'] ) && ! empty( $_POST['field'] ) && $this->is_admin_user() ) {
			$msg = $this->membership_list_do_action( 'toggle_'. $_POST['field'], array( $_POST['membership_id'] ) );
		}
	
		echo $msg;
		exit;
	}
	
	public function load_membership() {
		if( empty( $this->model ) || ! $this->model->is_valid() ) {
			$step = $this->get_step();
			if( self::STEP_SETUP_PROTECTED_CONTENT == $step ) {
				$this->model = MS_Model_Membership::get_visitor_membership();
			}
			else {
				$membership_id = ! empty( $_GET['membership_id'] ) ? $_GET['membership_id'] : 0;
				$this->model = MS_Factory::load( 'MS_Model_Membership', $membership_id );
			}
		}
		
		return apply_filters( 'ms_controller_membership_load_membership', $this->model );
	}
	
	/**
	 * Show admin notices.
	 * 
	 * @since 4.0.0
	 *
	 */
	public function print_admin_message() {
		add_action( 'admin_notices', array( 'MS_Helper_Membership', 'print_admin_message' ) );
	}
	
	/**
	 * Manages membership actions.
	 * 
	 * Verifies GET and POST requests to manage memberships
	 *
	 * @since 4.0.0
	 */
	public function membership_admin_page_process() {
		$this->print_admin_message();
		$msg = 0;
		$next_step = null;
// 		MS_Helper_Debug::log($_POST);
		$step = $this->get_step();
		$goto_url = null;
		
		/** MS_Controller_Rule is executed using this action*/
		do_action( 'ms_controller_membership_admin_page_process_'. $step, $this->get_active_tab() );
		
// 		MS_Helper_Debug::log("step: $step");
		/** Verify intent in request, only accessible to admin users */
		if( $this->is_admin_user() && ( $this->verify_nonce() || $this->verify_nonce( null, 'GET' ) ) ) {
			/** Take next actions based in current step.*/
			switch( $step ) {
				case self::STEP_MS_LIST:
					$this->print_admin_message();
					$msg = 0;
					$msg = $this->membership_list_do_action( $_GET['action'], array( $_GET['membership_id'] ) );
					$next_step = apply_filters( 'ms_controller_membership_membership_admin_page_process_next_step', self::STEP_MS_LIST, $step );
					$goto_url = add_query_arg( array( 'step' => $next_step ), admin_url( 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG ) );
					break;
				case self::STEP_SETUP_PROTECTED_CONTENT:
					$next_step = apply_filters( 'ms_controller_membership_membership_admin_page_process_next_step', self::STEP_CHOOSE_MS_TYPE, $step );
					$goto_url = add_query_arg( array( 'step' => $next_step ) );
					break;
				case self::STEP_CHOOSE_MS_TYPE:
					if( empty( $_POST['private'] ) ) {
						$_POST['private'] = false;
					}
					$msg = $this->save_membership( $_POST );
					
					$next_step = self::STEP_ACCESSIBLE_CONTENT;
					switch( $this->model->type ) {
						case MS_Model_Membership::TYPE_CONTENT_TYPE:
							$next_step = self::STEP_SETUP_CONTENT_TYPES;
							break;
						case MS_Model_Membership::TYPE_TIER:
							$next_step = self::STEP_SETUP_MS_TIERS;
							break;
						case MS_Model_Membership::TYPE_DRIPPED:
							$next_step = self::STEP_SETUP_DRIPPED;
							break;
						default:
							$next_step = self::STEP_ACCESSIBLE_CONTENT;
							break;
					}
					$next_step = apply_filters( 'ms_controller_membership_membership_admin_page_process_next_step', $next_step, $step );
					$goto_url = add_query_arg( array( 'membership_id' => $this->model->id, 'step' => $next_step ) );
					break;
				case self::STEP_ACCESSIBLE_CONTENT:
					$msg = $this->save_membership( $_POST );
					$next_step = self::STEP_ACCESSIBLE_CONTENT;
					switch( $this->model->type ) {
						case MS_Model_Membership::TYPE_CONTENT_TYPE:
							$next_step = self::STEP_SETUP_CONTENT_TYPES;
							break;
						case MS_Model_Membership::TYPE_TIER:
							$next_step = self::STEP_SETUP_MS_TIERS;
							break;
						default:
							$next_step = self::STEP_MS_LIST;
							break;
					}
					$next_step = apply_filters( 'ms_controller_membership_membership_admin_page_process_next_step', $next_step, $step );
					$goto_url = add_query_arg( array( 'membership_id' => $this->model->id, 'step' => $next_step ) );
					break;
				case self::STEP_SETUP_PAYMENT:
					$next_step = apply_filters( 'ms_controller_membership_membership_admin_page_process_next_step', self::STEP_MS_LIST, $step );
					$goto_url = add_query_arg( array( 'membership_id' => $this->model->id, 'step' => $next_step ) );
					break;
				case self::STEP_SETUP_CONTENT_TYPES:
					if( $this->validate_required( array( 'name' ) ) && 'create_content_type' == $_POST['action'] ) {
						$this->create_child_membership(  $_POST['name'] );
						$next_step = apply_filters( 'ms_controller_membership_membership_admin_page_process_next_step', self::STEP_ACCESSIBLE_CONTENT, $step );
						$goto_url = add_query_arg( array( 'membership_id' => $this->model->id, 'step' => $next_step ) );
					}
					else {
						$next_step = apply_filters( 'ms_controller_membership_membership_admin_page_process_next_step', self::STEP_SETUP_PAYMENT, $step );
						$goto_url = add_query_arg( array( 'membership_id' => $this->model->id, 'step' => $next_step ) );
					}
					break;
				case self::STEP_SETUP_MS_TIERS:
					$next_step = apply_filters( 'ms_controller_membership_membership_admin_page_process_next_step', self::STEP_SETUP_PAYMENT, $step );
					$goto_url = add_query_arg( array( 'membership_id' => $this->model->id, 'step' => $next_step ) );
					break;
				case self::STEP_SETUP_DRIPPED:
					$next_step = apply_filters( 'ms_controller_membership_membership_admin_page_process_next_step', self::STEP_SETUP_PAYMENT, $step );
					$goto_url = add_query_arg( array( 'membership_id' => $this->model->id, 'step' => $next_step ) );
					break;
				default:
					break;
			}
			if( ! empty( $goto_url ) ) {
				wp_safe_redirect( $goto_url );
			}
		}
	}
	
	public function membership_admin_page_router() {
		$this->wizard_tracker();
		$step = $this->get_step();
// 		MS_Helper_Debug::log($step);
		
		if( self::is_valid_step( $step ) ) {
			do_action( 'ms_controller_membership_membership_admin_page_router_' . $step );
			
			$method = "page_{$step}";
			if( method_exists( $this, $method ) ) {
				$this->$method();
			}
			else {
				do_action( 'ms_controller_membership_membership_admin_page_router_' . $step );
				MS_Helper_Debug::log( "Method $method not found for step $step" );
			}
		}
		else {
			MS_Helper_Debug::log( "Invalid step: $step" );
		}
	}
	
	public function page_setup_protected_content() {

		$data = array();
		$data['tabs'] = $this->get_protected_content_tabs();
		$data['step'] = $this->get_step();
		$data['action'] = MS_Controller_Rule::AJAX_ACTION_UPDATE_RULE;
		$data['initial_setup'] = MS_Plugin::instance()->settings->initial_setup;
		
		$data['membership'] = MS_Model_Membership::get_visitor_membership();
		$data['menus'] = $data['membership']->get_rule( MS_Model_Rule::RULE_TYPE_MENU )->get_menu_array();
		$first_value = array_keys( $data['menus'] );
		$first_value = reset( $first_value );
		$data['menu_id'] = $this->get_request_field( 'menu_id', $first_value, 'REQUEST' );
				 
		$view = apply_filters( 'ms_view_membership_setup_protected_content', new MS_View_Membership_Setup_Protected_Content() ); ;
		$view->data = apply_filters( 'ms_view_membership_setup_protected_content_data', $data );
		$view->render();
	}
	
	public function page_choose_ms_type() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = 'save_membership';
		$data['membership'] = $this->load_membership();

		$view = apply_filters( 'ms_view_membership_choose_type', new MS_View_Membership_Choose_Type() ); ;
		$view->data = apply_filters( 'ms_view_membership_choose_type_data', $data );
		$view->render();
		
	}
	
	public function page_accessible_content() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = MS_Controller_Rule::AJAX_ACTION_UPDATE_RULE;
		$data['tabs'] = $this->get_accessible_content_tabs();
		$data['membership'] = $this->load_membership();
		$data['initial_setup'] = MS_Plugin::instance()->settings->initial_setup;
		$data['menus'] = $data['membership']->get_rule( MS_Model_Rule::RULE_TYPE_MENU )->get_menu_array();
		$first_value = array_keys( $data['menus'] );
		$first_value = reset( $first_value );
		$data['menu_id'] = $this->get_request_field( 'menu_id', $first_value, 'REQUEST' );
		
		$view = apply_filters( 'ms_view_membership_accessible_content', new MS_View_Membership_Accessible_Content() ); ;
		$view->data = apply_filters( 'ms_view_membership_setup_accessible_content_data', $data );
		$view->render();
	}
	
	public function page_ms_list() {
		MS_Helper_Debug::log("page_ms_list");
		
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = 'save_membership';
		$data['tabs'] = $this->get_accessible_content_tabs();
		$data['membership'] = $this->load_membership();
		$view = apply_filters( 'ms_view_membership_list', new MS_View_Membership_List() ); ;
		$view->data = apply_filters( 'ms_view_membership_list_data', $data );
		$view->render();
	}
	
	public function page_setup_payment() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = 'save_membership';
		$data['membership'] = $this->load_membership();
		$view = apply_filters( 'ms_view_membership_setup_payment', new MS_View_Membership_Setup_Payment() ); ;
		$view->data = apply_filters( 'ms_view_membership_setup_payment_data', $data );
		$view->render();
	}
	
	public function page_ms_overview() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = 'save_membership';
		$membership = $this->load_membership();
		$data['membership'] = $membership;
		
		$args = array();
		$args['meta_query']['membership_id'] = array(
				'key'     => 'membership_id',
				'value'   => array( $membership->id, 0 ),
				'compare' => 'IN',
		);
		$data['events'] = MS_Model_Event::get_events( $args );
		
		$data['members'] = array();
		$ms_relationships = MS_Model_Membership_Relationship::get_membership_relationships( array( 'membership_id' => $membership->id ) );
		foreach( $ms_relationships as $ms_relationship ) {
			$data['members'][] = $ms_relationship->get_member();
		} 
		switch( $membership->type ) {
			case MS_Model_Membership::TYPE_DRIPPED:
				$view = new MS_View_Membership_Overview_Dripped();
				break;
			case MS_Model_Membership::TYPE_TIER:
				$view = new MS_View_Membership_Overview_Tier();
				break;
			case MS_Model_Membership::TYPE_CONTENT_TYPE:
				$view = new MS_View_Membership_Overview_Content_Type();
				break;
			default:
			case MS_Model_Membership::TYPE_SIMPLE:
				$view = new MS_View_Membership_Overview();
				break;
		}
		$view = apply_filters( 'ms_view_membership_ms_overview', $view );
		$view->data = apply_filters( 'ms_view_membership_ms_overview_data', $data );
		$view->render();
	}
	
	public function page_ms_news() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = '';
		$data['membership'] = $this->load_membership();
		$args = apply_filters( 'ms_controller_membership_page_ms_news_event_args', array( 'posts_per_page' => -1 ) );
		$data['events'] = MS_Model_Event::get_events( $args );
		
		$view = apply_filters( 'ms_view_membership_news', new MS_View_Membership_News() ); ;
		$view->data = apply_filters( 'ms_view_membership_news', $data );
		$view->render();
	}

	public function page_setup_content_types() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = 'create_content_type';
		$data['membership'] = $this->load_membership();
		$data['initial_setup'] = MS_Plugin::instance()->settings->initial_setup;
		
		$view = apply_filters( 'ms_view_membership_setup_content_types', new MS_View_Membership_Setup_Content_Type() ); ;
		$view->data = apply_filters( 'ms_view_membership_setup_content_types_data', $data );
		$view->render();
	}
	
	public function page_setup_ms_tiers() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = 'create_tier';
		$data['membership'] = $this->load_membership();

		$view = apply_filters( 'ms_view_membership_ms_tiers', new MS_View_Membership_Setup_Tier() ); ;
		$view->data = apply_filters( 'ms_view_membership_ms_tiers_data', $data );
		$view->render();
	}
	
	public function page_setup_dripped() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = 'save_membership';
		$data['membership'] = $this->load_membership();
		$data['tabs'] = $this->get_setup_dripped_tabs();
		
		$view = apply_filters( 'ms_view_membership_setup_dripped', new MS_View_Membership_Setup_Dripped() ); ;
		$view->data = apply_filters( 'ms_view_membership_setup_dripped_data', $data );
		$view->render();
	}
	
	public static function get_steps() {
		$steps = array(
				self::STEP_MS_LIST,
				self::STEP_OVERVIEW,
				self::STEP_NEWS,
				self::STEP_SETUP_PROTECTED_CONTENT,
				self::STEP_CHOOSE_MS_TYPE,
				self::STEP_SETUP_CONTENT_TYPES,
				self::STEP_SETUP_MS_TIERS,
				self::STEP_SETUP_DRIPPED,
				self::STEP_ACCESSIBLE_CONTENT,
				self::STEP_SETUP_PAYMENT,
		);
		return apply_filters( 'ms_controller_membership_get_steps', $steps );
	}
	
	public static function is_valid_step( $step ) {
		$valid = false;
		
		$steps = self::get_steps();
		if( in_array( $step, $steps ) ) {
			$valid = true;
		}
		
		return apply_filters( 'ms_controller_membership_is_valid_step', $valid, $step );
	}
	
	public function get_step() {
		/** Initial step */
		$step = self::STEP_MS_LIST;
		$settings = MS_Factory::load( 'MS_Model_Settings' );
	
		if( ! empty( $_REQUEST['step'] ) && self::is_valid_step( $_REQUEST['step'] ) ) {
			$step = $_REQUEST['step'];
		}
		elseif( $settings->initial_setup ) {
			if( $settings->wizard_step ) {
				$step = $settings->wizard_step;
			}
			else {
				$step = self::STEP_SETUP_PROTECTED_CONTENT;
			}
		}
		
		if( ! empty( $_GET['page'] )  && MS_Controller_Plugin::MENU_SLUG . '-setup' == $_GET['page'] ) {
			$step = self::STEP_SETUP_PROTECTED_CONTENT;
		}
		return apply_filters( 'ms_controller_membership_get_next_step', $step );
	}
	
	public function wizard_tracker() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		if( $settings->initial_setup && ! empty( $_POST['step'] ) ) {
			$settings->wizard_step = $_POST['step'];
			if( self::STEP_SETUP_PROTECTED_CONTENT == $settings->wizard_step ) {
				$settings->initial_setup = false;
			}
			$settings->save();
		}
	}
	
	/**
	 * Get available tabs for editing the membership.
	 *
	 * @return array The tabs configuration.
	 */
	public function get_protected_content_tabs() {
		$membership_id = $this->load_membership()->id;
	
		$tabs = array(
				'page' => array(
						'title' => __( 'Pages', MS_TEXT_DOMAIN ),
				),
				'category' => array(
						'title' => __( 'Categories, Custom Post Types', MS_TEXT_DOMAIN ),
				),
				'post' => array(
						'title' => __( 'Posts', MS_TEXT_DOMAIN ),
				),
				'cpt' => array(
						'title' => __( 'Custom Post Types', MS_TEXT_DOMAIN ),
				),
				'comment' => array(
						'title' => __( 'Comments, More Tag, Menus', MS_TEXT_DOMAIN ),
				),
				'shortcode' => array(
						'title' => __( 'Shortcodes', MS_TEXT_DOMAIN ),
				),
				'url_group' => array(
						'title' => __( 'URL Groups', MS_TEXT_DOMAIN ),
				),
		);
		$title = array();
		/**
		 * Enable / Disable post by post tab.
		 */
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			unset( $tabs['post'] );
			$title['category'] = __( 'Categories', MS_TEXT_DOMAIN );
		}
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$title['cpt_group'] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
		}
		$tabs['category']['title'] = implode( ', ', $title );
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) && 
			MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST	) ) {
			unset( $tabs['category'] );
		}
		/**
		 * Enable / Disable custom post by post tab.
		 */
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			unset( $tabs['cpt'] );
		}
		
		/**
		 * Disable urlgroup tab.
		 */
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			unset( $tabs['urlgroup'] );
		}
		/**
		 * Disable shortcode tab.
		 */
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SHORTCODE ) ) {
			unset( $tabs['shortcode'] );
		}
		
		$tabs = apply_filters( 'ms_controller_membership_tabs', $tabs, $membership_id );
		$url = admin_url( 'admin.php'); 
		$page = ! empty( $_GET['page'] ) ? $_GET['page'] : 'protected-content-memberships';
		foreach( $tabs as $tab => $info ) {
			$tabs[ $tab ]['url'] = add_query_arg( array(
					'page' => $page,
					'tab' => $tab,
				),
				$url 
			);
		}
	
		return apply_filters( 'ms_controller_membership_get_tabs', $tabs, $membership_id );
	}
	
	public function get_accessible_content_tabs() {
		$membership_id = $this->load_membership()->id;
	
		$tabs = $this->get_protected_content_tabs();
	
		$protected_content = MS_Model_Membership::get_visitor_membership();
	
		$step = $this->get_step();
		$page = ! empty( $_GET['page'] ) ? $_GET['page'] : 'protected-content-memberships';
		foreach( $tabs as $tab => $info ) {
			$rule = $protected_content->get_rule( $tab );
			switch( $tab ) {
				case 'category':
					if( ! $rule->has_rules() && ! $protected_content->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP )->has_rules() ) {
						unset( $tabs[ $tab ] );
					}
					break;
				case 'comment':
					if(!  $rule->has_rules() && ! $protected_content->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG )->has_rules() &&
						! $protected_content->get_rule( MS_Model_Rule::RULE_TYPE_MENU )->has_rules() ) {
						unset( $tabs[ $tab ] );
					}
					break;
				default:
					if( ! $rule->has_rules() ) {
						unset( $tabs[ $tab ] );
					}
					break;
			}
		}
		foreach( $tabs as $tab => $info ) {
			$tabs[ $tab ]['url'] = add_query_arg( array(
					'page' => $page,
					'step' => $step,
					'tab' => $tab,
					'membership_id' => $membership_id,
			) );
		}
		return apply_filters( 'ms_controller_membership_get_tabs', $tabs, $membership_id );
	}
	
	/**
	 * Get available tabs for dripped membership.
	 * 
	 * @since 1.0
	 *
	 * @return array The tabs configuration.
	 */
	public function get_setup_dripped_tabs() {
		$membership_id = $this->load_membership()->id;
	
		$tabs = array(
				'post' => array(
						'title' => __( 'Posts', MS_TEXT_DOMAIN ),
				),
				'page' => array(
						'title' => __( 'Pages', MS_TEXT_DOMAIN ),
				),
		);
		
		$step = $this->get_step();
		$page = ! empty( $_GET['page'] ) ? $_GET['page'] : 'protected-content';
		foreach( $tabs as $tab => $info ) {
			$tabs[ $tab ]['url'] = add_query_arg( array( 
					'page' => $page,
					'step' => $step, 
					'tab' => $tab, 
					'membership_id' => $membership_id,
			) );
		}
	
		return apply_filters( 'ms_controller_membership_get_tabs', $tabs, $membership_id );
	}
	
	/**
	 * Get the current active settings page/tab.
	 *
	 * @since 4.0.0
	 */
	public function get_active_tab() {
		$step = $this->get_step();
	
		if( self::STEP_SETUP_PROTECTED_CONTENT ) {
			$tabs = $this->get_protected_content_tabs();
		}
		elseif( self::STEP_ACCESSIBLE_CONTENT ) {
			$tabs = $this->get_accessible_content_tabs();
		}
		elseif( self::STEP_SETUP_DRIPPED ) {
			$tabs = $this->get_setup_dripped_tabs();
		}
	
		reset( $tabs );
		$first_key = key( $tabs );
	
		/** Setup navigation tabs. */
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : $first_key;
		if ( ! array_key_exists( $active_tab, $tabs ) ) {
			switch( $active_tab ) {
				case 'cpt_group':
					$active_tab = 'category';
					break;
				case 'menu':
				case 'more_tag':
					$active_tab = 'comment';
					break;
				default:
					$active_tab = $first_key;
					break;
			}
			
			wp_safe_redirect( add_query_arg( array( 'tab' => $active_tab ) ) );
		}
		return $this->active_tab = apply_filters( 'ms_controller_membership_get_active_tab', $active_tab );
	}
	
	/**
	 * Execute action in Membership model.
	 * 
	 * @since 4.0.0
	 *
	 * @param string $action The action to execute.
	 * @param int[] $membership_ids The membership ids which action will be taken.
	 * @return number Resulting message id.
	 */	
	private function membership_list_do_action( $action, $membership_ids ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		$msg = 0;
		foreach( $membership_ids as $membership_id ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
			switch( $action ) {
				case 'toggle_active':
				case 'toggle_activation':
					$membership->active = ! $membership->active;
					$membership->save();
					$msg = MS_Helper_Membership::MEMBERSHIP_MSG_ACTIVATION_TOGGLED;
					break;
				case 'toggle_public':
					$membership->private = ! $membership->private;
					$membership->save();
					$msg = MS_Helper_Membership::MEMBERSHIP_MSG_STATUS_TOGGLED;
					break;
				case 'delete':
					try{
						$membership->delete();
						$msg = MS_Helper_Membership::MEMBERSHIP_MSG_DELETED;
					}
					catch( Exception $e ) {
						$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_DELETED;
					}
					break;
			}
		}
		
		return $msg;
	}
	/**
	 * Show admin membership list.
	 * 
	 * Show all memberships available.
	 *
	 * @since 4.0.0
	 */
	public function admin_membership_list() {
	
		/** Menu: Memberships */
		$view = apply_filters( 'membership_membership_list_view', new MS_View_Membership_List() );
	
		$view->render();
	}
	
	/**
	 * Save membership general tab fields
	 *
	 * @since 4.0.0 
	 *
	 * @param mixed[] $fields
	 */
	private function save_membership( $fields ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		$membership = $this->load_membership();
		
		$msg = 0;
		if( is_array( $fields ) ) {
			foreach( $fields as $field => $value ) {
				try {
					$membership->$field = $value;
				} 
				catch (Exception $e) {
					$msg = MS_Helper_Membership::MEMBERSHIP_MSG_PARTIALLY_UPDATED;					  
				}
			}
			$membership->save();
			if( empty( $msg ) ) {
				$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
			}
		}
		return $msg;
	}

	private function create_child_membership( $name ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_ADDED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		$parent = $this->load_membership();
		if( $parent->is_valid() && $parent->can_have_children() ) {
			$parent->create_child( $name );
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_ADDED;
		}
		
		return $msg;
	}
	
	/**
	 * Load Membership manager specific styles.
	 *
	 * @since 4.0.0
	 */			
	public function enqueue_styles() {
		
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		switch( $this->get_active_tab() ) {
			case 'category':
				wp_enqueue_style( 'jquery-chosen' );
				break;
			default:
				wp_enqueue_style( 'jquery-ui' );
				break;
		}
		wp_enqueue_style( 'ms_view_membership', $plugin_url. 'app/assets/css/ms-view-membership.css', null, $version );
	}
	
	/**
	 * Load Membership manager specific scripts.
	 *
	 * @since 4.0.0
	 */				
	public function enqueue_scripts() {

		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		wp_enqueue_script( 'ms-radio-slider' );
		
		switch( $this->get_step() ) {
			case self::STEP_CHOOSE_MS_TYPE:
				wp_enqueue_script( 'jquery-validate' );
				wp_enqueue_script( 
					'ms-view-membership-choose-type', 
					$plugin_url. 'app/assets/js/ms-view-membership-choose-type.js', 
					array( 'jquery', 'jquery-validate' ), 
					$version 
				);
				break;
			case self::STEP_OVERVIEW:
				wp_enqueue_script(
					'ms-view-membership-overview',
					$plugin_url. 'app/assets/js/ms-view-membership-overview.js',
					null,
					$version
				);
				break;
			case self::STEP_SETUP_PROTECTED_CONTENT:
			case self::STEP_ACCESSIBLE_CONTENT:
				switch( $this->get_active_tab() ) {
					case 'category':
					case 'comment':
						wp_enqueue_style( 'jquery-chosen' );
						wp_enqueue_script( 
							'ms-view-membership-setup-protected-content', 
							$plugin_url. 'app/assets/js/ms-view-membership-setup-protected-content.js', 
							array( 'jquery', 'jquery-chosen' ), 
							$version 
						);
						break;
					case 'url_group':
						wp_register_script( 
							'ms-view-membership-render-url-group', 
							$plugin_url. 'app/assets/js/ms-view-membership-render-url-group.js', 
							array( 'jquery' ), 
							$version 
						);
						wp_localize_script( 'ms-view-membership-render-url-group', 'ms', array(
								'valid_rule_msg' => __( 'Valid', MS_TEXT_DOMAIN ),
								'invalid_rule_msg' => __( 'Invalid', MS_TEXT_DOMAIN ),
								'empty_msg'	=> __( 'Add Page URLs to the group in case you want to test it against', MS_TEXT_DOMAIN ),
								'nothing_msg' => __( 'Enter an URL above to test against rules in the group', MS_TEXT_DOMAIN ),
						) );
						wp_enqueue_script( 'ms-view-membership-render-url-group' );
						wp_dequeue_script( 'ms-radio-slider' );
						break;
					default:
						wp_enqueue_script( 'jquery-ui-datepicker' );
						wp_enqueue_script( 'jquery-validate' );
						break;
				}
				break;
		}
	}
	
}