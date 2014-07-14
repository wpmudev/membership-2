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


class MS_Model_Plugin extends MS_Model {
	
	private $member;
	
	public function __construct() {
		
		$this->upgrade();
		if( MS_Plugin::instance()->settings->plugin_enabled ) {
			
			$this->add_filter( 'cron_schedules', 'cron_time_period' );
				
			$this->init_member();
			
			/** Init gateways to enable hooking actions/filters */ 
			MS_Model_Gateway::get_gateways();
			
			/** Init communications to enable hooking actions/filters */
			MS_Model_Communication::load_communications();
				
			$this->setup_cron_services();
			
			$this->add_action( 'parse_request', 'setup_protection', 2 );
			$this->add_action( 'template_redirect', 'protect_current_page', 1 );
			
			/** cron service action */
			$this->add_action( 'ms_check_membership_status', 'check_membership_status' );
			
// 			$this->check_membership_status();
		}
	}
	
	/**
	 * Upgrade database if needs to.
	 */
	public function upgrade() {
			
		MS_Model_Upgrade::upgrade();
	}
	
	/**
	 * Initialise current member.
	 *
	 * Get current member and membership relationships.
	 * If user is not logged in (visitor), assign a visitor membership.
	 * If user is logged in but has not any memberships, assign a default membership.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function init_member() {

		$this->member = MS_Model_Member::get_current_member();
		$this->check_member_status();
		
		$simulate = MS_Model_Simulate::load();

		/** Admin user simulating membership */
		if( MS_Model_Member::is_admin_user() ) {
			if( $simulate->is_simulating() ) {
				$this->member->add_membership( $simulate->membership_id );
				$simulate->start_simulation();
			}
		}
		else {
			/** Visitor: assign a Visitor Membership */
			if( ! MS_Model_Member::is_logged_user() ){
				$this->member->add_membership( MS_Model_Membership::get_visitor_membership()->id );
			}
			/** Logged user with no memberships: assign default Membership */
			elseif( MS_Model_Member::is_logged_user() && ! $this->member->is_member() ) {
				$this->member->add_membership( MS_Model_Membership::get_default_membership()->id );
			}
		}
	}
	
	/**
	 * Checks whether curren member is active or not. 
	 * 
	 * If member is deactivated, then he has to be logged out immediately.
	 *
	 * @since 4.0.0
	 * @todo Give user a feedback about the lockout
	 *
	 * @access public
	 */
	public function check_member_status() {
		if ( ! $this->member->is_logged_user() ) {
			return;
		}
		
		if( $this->member->is_admin_user() ) {
			return;
		}
		
		if ( ! $this->member->active ) {
			wp_logout();
			wp_redirect( home_url( $_SERVER['REQUEST_URI'] ) );
			exit;
		}
	}

	/**
	 * Checks member permissions and protects current page.
	 *
	 * @since 4.0.0
	 * @action template_redirect 1
	 *
	 * @access public
	 */
	public function protect_current_page() {

		/** Admin user has access to everything */
		if( $this->member->is_admin_user() && ! MS_Model_Simulate::load()->is_simulating() ) {
			return true;
		}

		$settings = MS_Plugin::instance()->settings;
		$addon = MS_Plugin::instance()->addon;
		$has_access = false;
		/**
		 * Search permissions through all memberships joined.
		 */
		foreach( $this->member->membership_relationships as $ms_relationship ) {
			/**
			 * Verify status of the membership.
			 * Only active, trial or canceled (until it expires) status memberships.
			 */
			if( ! $this->member->is_member( $ms_relationship->membership_id ) ) {
				continue;
			}
			$membership = $ms_relationship->get_membership();

			$has_access = $membership->has_access_to_current_page( $ms_relationship );
			
			/** Found a membership that gives access. Stop searching. */
			if( $has_access ) {
				break;
			}
		}
		
		/** If front page or home then return. Honours all other rules. */
		if( is_home() || is_front_page() ) {
			$has_access = true;
		}
				
		if( ! $has_access ) {
			$no_access_page_url = get_permalink( $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS ) );
			
			$current_page_url = MS_Helper_Utility::get_current_page_url();
			
			/** Don't redirect the protection page. */
			if( ! $settings->is_special_page( MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS ) ) {
				$no_access_page_url = add_query_arg( array( 'redirect_to' =>  $current_page_url ), $no_access_page_url );
				wp_safe_redirect( $no_access_page_url );
			}
		}

	}
	
	/**
	 * Setup initial protection.
	 *
	 * Hide menu and pages, protect media donwload and feeds.
	 * Protect feeds.
	 * 
	 * @since 4.0.0
	 * @action parse_request
	 *
	 * @access public
	 * @param WP $wp Instance of WP class.
	 */
	public function setup_protection( WP $wp ){
		/** Admin user has access to everything */
		if( $this->member->is_admin_user() && ! MS_Model_Simulate::load()->is_simulating() ) {
			return true;
		}
		
		$settings = MS_Plugin::instance()->settings;
		$addon = MS_Plugin::instance()->addon;
		$has_access = false;
		/**
		 * Search permissions through all memberships joined.
		 */
		foreach( $this->member->membership_relationships as $ms_relationship ) {
			/**
			 * Verify status of the membership.
			 * Only active, trial or canceled (until it expires) status memberships.
			 */
			if( ! $this->member->is_member( $ms_relationship->membership_id ) ) {
				continue;
			}
			
			$membership = $ms_relationship->get_membership();
			$membership->protect_content( $ms_relationship );
		}
	}
	
	/**
	 * Config cron time period.
	 *
	 * @since 4.0.0
	 *
	 * @access public
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
	
		return $periods;
	}

	/**
	 * Setup cron plugin services. 
	 *
	 * Setup cron to call actions. 
	 *
	 * @todo checkperiod review.
	 * 
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function setup_cron_services() {
		
		if( ! ( $this->member->is_admin_user() && MS_Model_Simulate::load()->is_simulating() ) ) {
			
			/**
			 * Check for membership status.
			 */
			$checkperiod = '6hours';
			if ( ! wp_next_scheduled( 'ms_check_membership_status' ) ) {
				/** Action to be called by the cron job */
				wp_schedule_event( time(), $checkperiod, 'ms_check_membership_status' );
			}
			/**
			 * Setup automatic communications.
			 */
			$checkperiod = '60mins';
			if ( ! wp_next_scheduled( 'ms_communications_process' ) ) {
				/** Action to be called by the cron job */
				wp_schedule_event( time(), $checkperiod, 'ms_communications_process' );
			}
		}
	}
	
	/**
	 * Check membership status.
	 *
	 * Execute actions when time/period condition are met.
	 * E.g. change membership status, add communication to queue, create invoices.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function check_membership_status() {
		
		if( ( $this->member->is_admin_user() && MS_Model_Simulate::load()->is_simulating() ) ) {
			return;
		}
		
		$args = apply_filters( 'ms_model_plugin_check_membership_status_get_membership_relationship_args', array( 'status' => 'valid' ) );
		$ms_relationships = MS_Model_Membership_Relationship::get_membership_relationships( $args );

		foreach( $ms_relationships as $ms_relationship ) {
			$ms_relationship->check_membership_status();
		}
	}
}