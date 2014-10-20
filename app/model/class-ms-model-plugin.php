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

/**
 * Main class for protection.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Plugin extends MS_Model {
	
	/**
	 * Current Member object.
	 *
	 * @since 1.0.0
	 * @var string $member
	 */
	private $member;
	
	/**
	 * Prepare object.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		
		do_action( 'ms_model_plugin_constructor', $this );
		
		/**
		 * Upgrade membership database if needs to.
		 */
		MS_Model_Upgrade::init();
		
		if( MS_Plugin::instance()->settings->plugin_enabled ) {
			
			$this->add_filter( 'cron_schedules', 'cron_time_period' );
				
			$this->init_member();
			
			/* Init gateways to enable hooking actions/filters */ 
			MS_Model_Gateway::get_gateways();
			
			/* Init communications to enable hooking actions/filters */
			MS_Model_Communication::load_communications();
				
			$this->setup_cron_services();
			
			$this->add_action( 'parse_request', 'setup_protection', 2 );
			$this->add_action( 'template_redirect', 'protect_current_page', 1 );
			
			/* cron service action */
			$this->add_action( 'ms_model_plugin_check_membership_status', 'check_membership_status' );
			
			//for testing
// 			$this->check_membership_status();
		}
	}
	
	/**
	 * Initialise current member.
	 *
	 * Get current member and membership relationships.
	 * If user is not logged in (visitor), assign a visitor membership.
	 * If user is logged in but has not any memberships, assign a default membership.
	 * Deactivated users (active == false) get visitor membership assigned.
	 * 
	 * @since 1.0.0
	 */
	public function init_member() {

		do_action( 'ms_model_plugin_init_member_before', $this );
		
		$this->member = MS_Model_Member::get_current_member();
		
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );
		
		/* Admin user simulating membership */
		if( MS_Model_Member::is_admin_user() ) {
			if( $simulate->is_simulating() ) {
				$this->member->add_membership( $simulate->membership_id );
				$simulate->start_simulation();
			}
		}
		else {
			/* Deactivated status invalidates all memberships*/
			if( false == $this->member->is_member || false == $this->member->active ) {
				$this->member->ms_relationships = array();
			}
			/* Visitor: assign a Visitor Membership = Protected Content */
			if( ! $this->member->has_membership() ){
				$this->member->add_membership( MS_Model_Membership::get_visitor_membership()->id );
			}
		}
		
		do_action( 'ms_model_plugin_init_member_after', $this );
	}
	
	/**
	 * Checks member permissions and protects current page.
	 *
	 * ** Hooks Action **
	 * 
	 * * template_redirect
	 * 
	 * @since 1.0.0
	 */
	public function protect_current_page() {

		do_action( 'ms_model_plugin_protect_current_page_before', $this );
		
		/* Admin user has access to everything */
		if( $this->member->is_admin_user() && ! MS_Factory::load( 'MS_Model_Simulate' )->is_simulating() ) {
			return;
		}

		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$ms_pages = MS_Factory::load( 'MS_Model_Pages');
		$has_access = false;
		/*
		 * Search permissions through all memberships joined.
		 */
		foreach( $this->member->ms_relationships as $ms_relationship ) {
			/*
			 * Verify status of the membership.
			 * Only active, trial or canceled (until it expires) status memberships.
			 */
			if( ! $this->member->has_membership( $ms_relationship->membership_id ) ) {
				continue;
			}
			$membership = $ms_relationship->get_membership();

			$has_access = $membership->has_access_to_current_page( $ms_relationship );
			
			/** Found a membership that gives access. Stop searching. */
			if( $has_access ) {
				break;
			}
		}
		
		/* If front page or home or not found page. Honours all other rules. */
		if( is_home() || is_front_page() || is_404() ) {
			$has_access = true;
		}

		if( ! $has_access ) {
			$no_access_page_url = $ms_pages->get_ms_page_url( MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT, false, true );
			$current_page_url = MS_Helper_Utility::get_current_page_url();

			/* Don't (re) redirect the protection page. */
			if( ! $ms_pages->is_ms_page( null, MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT ) ) {
				$no_access_page_url = add_query_arg( array( 'redirect_to' =>  $current_page_url ), $no_access_page_url );
				
				$no_access_page_url = apply_filters( 'ms_model_plugin_protected_content_page', $no_access_page_url );
				wp_safe_redirect( $no_access_page_url );
				
				exit;
			}
		}
		
		do_action( 'ms_model_plugin_protect_current_page_after', $this );
	}
	
	/**
	 * Setup initial protection.
	 *
	 * Hide menu and pages, protect media donwload and feeds.
	 * Protect feeds.
	 * 
	 * ** Hooks Action **
	 * 
	 * * parse_request
	 * 
	 * @since 1.0.0
	 * @param WP $wp Instance of WP class.
	 */
	public function setup_protection( WP $wp ){
		
		do_action( 'ms_model_plugin_setup_protection_before', $wp, $this );
		
		/* Admin user has access to everything */
		if( $this->member->is_admin_user() && ! MS_Factory::load( 'MS_Model_Simulate' )->is_simulating() ) {
			return true;
		}
		
		$settings = MS_Plugin::instance()->settings;
		$has_access = false;
		
		/*
		 * Search permissions through all memberships joined.
		 */
		foreach( $this->member->ms_relationships as $ms_relationship ) {
			/**
			 * Verify status of the membership.
			 * Only active, trial or canceled (until it expires) status memberships.
			 */
			if( ! $this->member->has_membership( $ms_relationship->membership_id ) ) {
				continue;
			}
			
			$membership = $ms_relationship->get_membership();
			$membership->protect_content( $ms_relationship );
		}
		
		do_action( 'ms_model_plugin_setup_protection_after', $wp, $this );
	}
	
	/**
	 * Config cron time period.
	 *
	 * ** Hooks Action **
	 * 
	 * * cron_schedules
	 * 
	 * @since 1.0.0
	 */
	public function cron_time_period( $periods ) {
		if ( !is_array( $periods ) ) {
			$periods = array();
		}
	
		$periods['6hours'] = array( 'interval' => 6 * HOUR_IN_SECONDS, 'display' => __( 'Every 6 Hours', MS_TEXT_DOMAIN ) );
		$periods['60mins'] = array( 'interval' => 60 * MINUTE_IN_SECONDS, 'display' => __( 'Every 60 Mins', MS_TEXT_DOMAIN ) );
		$periods['30mins'] = array( 'interval' => 30 * MINUTE_IN_SECONDS, 'display' => __( 'Every 30 Mins', MS_TEXT_DOMAIN ) );
		$periods['15mins'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => __( 'Every 15 Mins', MS_TEXT_DOMAIN ) );
		$periods['10mins'] = array( 'interval' => 10 * MINUTE_IN_SECONDS, 'display' => __( 'Every 10 Mins', MS_TEXT_DOMAIN ) );
		$periods['5mins']  = array( 'interval' =>  5 * MINUTE_IN_SECONDS, 'display' => __( 'Every 5 Mins', MS_TEXT_DOMAIN ) );
		$periods['1min']  = array( 'interval' => MINUTE_IN_SECONDS, 'display' => __( 'Every Minute', MS_TEXT_DOMAIN ) );
	
		return apply_filters( 'ms_model_plugin_cron_time_period', $periods );
	}

	/**
	 * Setup cron plugin services. 
	 *
	 * Setup cron to call actions. 
	 *
	 * @todo checkperiod review.
	 * 
	 * @since 1.0.0
	 */
	public function setup_cron_services() {
		
		do_action( 'ms_model_plugin_setup_cron_services_before', $this );
		
		if( ! ( $this->member->is_admin_user() && MS_Factory::load( 'MS_Model_Simulate' )->is_simulating() ) ) {
			
			/*
			 * Check for membership status.
			 */
			$checkperiod = '6hours';
			if ( ! wp_next_scheduled( 'ms_model_plugin_check_membership_status' ) ) {
				/** Action to be called by the cron job */
				wp_schedule_event( time(), $checkperiod, 'ms_model_plugin_check_membership_status' );
			}
			
			/*
			 * Setup automatic communications.
			 */
			$checkperiod = '60mins';
			$checkperiod = '1min'; //@todo for testing 
			if ( ! wp_next_scheduled( 'ms_model_plugin_process_communications' ) ) {
				/** Action to be called by the cron job */
				wp_schedule_event( time(), $checkperiod, 'ms_model_plugin_process_communications' );
			}
		}
		
		do_action( 'ms_model_plugin_setup_cron_services_after', $this );
	}
	
	/**
	 * Check membership status.
	 *
	 * Execute actions when time/period condition are met.
	 * E.g. change membership status, add communication to queue, create invoices.
	 *
	 * @since 1.0.0
	 */
	public function check_membership_status() {
		
		do_action( 'ms_model_plugin_check_membership_status_before', $this );
		
		if( ( $this->member->is_admin_user() && MS_Factory::load( 'MS_Model_Simulate' )->is_simulating() ) ) {
			return;
		}
		
		$args = apply_filters( 'ms_model_plugin_check_membership_status_get_membership_relationship_args', array( 'status' => 'valid' ) );
		$ms_relationships = MS_Model_Membership_Relationship::get_membership_relationships( $args );

		foreach( $ms_relationships as $ms_relationship ) {
			$ms_relationship->check_membership_status();
		}
		
		do_action( 'ms_model_plugin_check_membership_status_after', $this );
	}
}