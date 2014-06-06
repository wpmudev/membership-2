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
			
			$this->init_member();
			
			$this->setup_communications();
			
			$this->setup_download_protection();
				
			$this->add_action( 'template_redirect', 'protect_current_page', 1 );
			$this->add_action( 'parse_request', 'setup_protection', 2 );
		}
	}
	
	/**
	 * Upgrade database if needs to.
	 */
	public function upgrade() {
			
		MS_Model_Upgrade::upgrade();
	}
	
	public function init_member() {
		$this->member = MS_Model_Member::get_current_member();
		$this->check_member_status();
		
		$simulate = MS_Model_Simulate::load();

		/** Admin user simulating membership */
		if( $this->member->is_admin_user() ) {
			if( $simulate->is_simulating() ) {
				$this->member->add_membership( $simulate->membership_id );
				if( $simulate->is_simulating_period() ) {
					$membership_relationships = $this->member->membership_relationships;
					$membership_relationships[ $simulate->membership_id ]->set_elapsed_period( $simulate->period['period_unit'], $simulate->period['period_type'] );
					$this->member->membership_relationships = $membership_relationships;
				}
				elseif( $simulate->is_simulating_date() ) {
					$simulate->simulate_date();
				}
			}
		}
		else {
			/** Visitor: assign a Visitor Membership */
			if( ! $this->member->is_logged_user() ){
				$this->member->add_membership( MS_Model_Membership::get_visitor_membership()->id );
			}
			/** Logged user with no memberships: assign default Membership */
			if( $this->member->is_logged_user() && ! $this->member->is_member() ) {
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
		foreach( $this->member->membership_relationships as $membership_relationship ) {
			/**
			 * Verify status of the membership.
			 * Only active or trial status memberships.
			 */
			if( ! $this->member->is_member( $membership_relationship->membership_id ) ) {
				continue;
			}
			$membership = $membership_relationship->get_membership();

			$rules = $this->get_rules_hierarchy( $membership );
			/**
			 * Set initial protection.
			 * Hide content.
			 */
			foreach( $rules as $rule ) {
				$rule->protect_content( $membership_relationship );
			}
			/** 
			 * Verify membership rules hierachyly.
			 * Verify content accessed directly.
			 * If 'has access' is found, it does have access.
			 */
			foreach( $rules as $rule ) {
				$has_access = ( $has_access || $rule->has_access() );
				
				if( $has_access ) {
					break;
				}
			}
			/**
			 * Verify membership dripped rules hierachyly.
			 */
			$dripped = apply_filters( 'membership_model_plugin_dripped_rules', array( 
						MS_Model_Rule::RULE_TYPE_PAGE, 
						MS_Model_Rule::RULE_TYPE_POST
					) 
			);
			/**
			 * Dripped has the final decision.
			 */
			foreach( $dripped as $rule_type ) {
				if( $rules[ $rule_type ]->has_dripped_rules() ) {
					$has_access = $rules[ $rule_type ]->has_dripped_access( $membership_relationship->start_date ); 
				}
			}
		}
		
		/** If front page or home then return. Honours all other rules. */
		if( is_home() || is_front_page() ) {
			$has_access = true;
		}
		
		if( ! $has_access ) {
			$url = get_permalink( $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS ) );
			
			$page_url = @$_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
			if ( $_SERVER['SERVER_PORT'] != '80' ) {
				$page_url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
			}
			else {
				$page_url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			}
			
			$url .= add_query_arg( array( 'redirect_to' =>  $page_url ), $url );

			wp_safe_redirect( $url );
		}

	}
	/**
	 * Get protection rules sorted.
	 * First one has priority over the last one.
	 * These rules are used to determine access.
	 * @since 4.0.0
	 */
	private function get_rules_hierarchy( $membership ) {
		$rule_types = MS_Model_Rule::get_rule_types();
		foreach( $rule_types as $rule_type ) {
			$rules[ $rule_type ] = $membership->get_rule( $rule_type );
		}
		return apply_filters( 'ms_model_plugin_get_rules_hierarchy', $rules );
	}
	/**
	 * Setup initial protection.
	 *
	 * Hide menu and pages in the front end.
	 * Protect feeds.
	 * 
	 * @since 4.0.0
	 * @action parse_request
	 *
	 * @access public
	 * @param WP $wp Instance of WP class.
	 */
	public function setup_protection( WP $wp ){
		
	}
	
	/**
	 * Setup download / media protection.
	 * 
	 * @since 4.0.0
	 * @action parse_request
	 *
	 * @access public
	 */
	public function setup_download_protection() {
		/** Admin user has access to everything */
		if( $this->member->is_admin_user() && ! MS_Model_Simulate::load()->is_simulating() ) {
			return;
		}
		
		/**
		 * Hook media protection rules for all memberships joined.
		 */
		foreach( $this->member->membership_relationships as $membership_relationship ) {
			/**
			 * Verify status of the membership.
			 * Only active or trial status memberships.
			 */
			if( ! $this->member->is_member( $membership_relationship->membership_id ) ) {
				continue;
			}
			$membership = $membership_relationship->get_membership();
		
			$rules = $this->get_rules_hierarchy( $membership );
			$download_rules = $membership->rules[ MS_Model_Rule::RULE_TYPE_MEDIA ];
			
			$download_rules->add_action( 'pre_get_posts', 'handle_download_protection', 3 );
		}
		
	}
	
	public function communications_time_period( $periods ) {
		if ( !is_array( $periods ) ) {
			$periods = array();
		}
	
		$periods['10mins'] = array( 'interval' => 10 * MINUTE_IN_SECONDS, 'display' => __( 'Every 10 Mins', MS_TEXT_DOMAIN ) );
		$periods['5mins']  = array( 'interval' =>  5 * MINUTE_IN_SECONDS, 'display' => __( 'Every 5 Mins', MS_TEXT_DOMAIN ) );
	
		return $periods;
	}
	
	public function setup_communications() {
		
		$this->add_filter( 'cron_schedules', 'communications_time_period' );
		
		MS_Model_Communication::load_communications();
		
		// Action to be called by the cron job
		$checkperiod = MS_Plugin::instance()->cron_interval == 10 ? '10mins' : '5mins';
		if ( !wp_next_scheduled( 'ms_communications_process' ) ) {
			wp_schedule_event( time(), $checkperiod, 'ms_communications_process' );
		}
		
	}
	
}