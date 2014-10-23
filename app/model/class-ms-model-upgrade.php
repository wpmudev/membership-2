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
 * Upgrade DB model.
 *
 * Manages DB upgrading.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Upgrade extends MS_Model {
	
	/**
	 * Initialize upgrading check.
	 * 
	 * @since 1.0.0
	 */
	public static function init() {
		self::upgrade();
		
		MS_Factory::load( 'Ms_Model_Upgrade' );
		
		do_action( 'ms_model_upgrade_init' );
	}
	
	/**
	 * Upgrade database.
	 * 
	 * @since 1.0.0
	 */
	public static function upgrade() {
		
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		
		/** Compare current src version to DB version */
		if ( version_compare( MS_Plugin::instance()->version, $settings->version, '!=' ) ) {
			
			//Upgrade logic from specific version
			switch( $settings->version ) {
				case '1.0.0.0':
					$args = array();
					$args['post_parent__not_in'] = array( 0 );
					$memberships = MS_Model_Membership::get_memberships( $args );
					foreach( $memberships as $membership ) {
						$parent = MS_Factory::load( 'MS_Model_Membership', $membership->parent_id );
						if( ! $parent->is_valid() ) {
							$membership->delete();
						}
					}					
					break;
				default:
					flush_rewrite_rules();
					do_action( 'ms_model_upgrade_upgrade', $settings );
					break;
			}
			
			$settings = MS_Factory::load( 'MS_Model_Settings' );
			$settings->version = MS_Plugin::instance()->version;
			$settings->save();
		}
	}
	
	/**
	 * Remove all plugin related content from database.
	 *
	 * @since 1.0.0
	 */
	private static function cleanup() {
		
		global $wpdb;
		$sql = array();
		
		$sql[] = "DELETE FROM $wpdb->options WHERE option_name LIKE 'ms_%';";
		$sql[] = "DELETE FROM $wpdb->posts WHERE post_type LIKE 'ms_%';";
		$sql[] = "DELETE FROM $wpdb->postmeta  WHERE NOT EXISTS (SELECT 1 FROM wp_posts tmp WHERE tmp.ID = post_id);";
		$sql[] = "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%';";
		
		foreach( $sql as $s ) {
			$wpdb->query( $s );
		}
	}
	
	/**
	 * Remove all plugin related content from database.
	 * 
	 * @since 1.0.0
	 */
	private static function cleanup_db() {
		
		$users = MS_Model_Member::get_members( );
		foreach( $users as $user ) {
			$user->delete_all_membership_usermeta();
			$user->save();
		}
		$memberships = MS_Model_Membership::get_memberships( array( 'posts_per_page' => -1, 'include_visitor' => 1 ) );
		foreach( $memberships as $membership ) {
			$membership->delete( true );
		}
		$comms = MS_Model_Communication::load_communications();
		if( ! empty( $comms ) ) {
			foreach( $comms as $comm ) {
				$comm->delete();
			}
		}
		$ms_relationships = MS_Model_Membership_Relationship::get_membership_relationships( array( 'status' => 'all' ) );
		foreach( $ms_relationships as $ms_relationship ) {
			$ms_relationship->delete();
		}
		$invoices = MS_Model_Invoice::get_invoices( array( 'posts_per_page' => -1 ) );
		foreach( $invoices as $invoice ) {
			$invoice->delete();
		}
		$coupons = MS_Model_Coupon::get_coupons( array( 'posts_per_page' => -1 ) );
		foreach( $coupons as $coupon ) {
			$coupon->delete();
		}
		$events = MS_Model_Event::get_events( array( 'posts_per_page' => -1 ) );
		foreach( $events as $event ) {
			$event->delete();
		}
		self::cleanup_settings();
		
	}
	
	/**
	 * Remove all plugin related settings from database.
	 * 
	 * @since 1.0.0
	 */
	private static function cleanup_settings() {
		$gateways = MS_Model_Gateway::get_gateways();
		foreach( $gateways as $gateway ) {
			$gateway->delete();
		}
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$settings->delete();
		$settings = MS_Factory::create( 'MS_Model_Settings' );
		$settings->instance = $settings;
		$settings->save();
		
		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );
		$ms_pages->pages = array();
		$ms_pages->save();
		
		$addon = MS_Factory::load( 'MS_Model_Addon' );
		$addon->addons = array();
		$addon->save();
		
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );
		$simulate->reset_simulation();
		
	}
}