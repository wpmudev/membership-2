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

class MS_Model_Member extends MS_Model {

	protected $membership_ids = array();
	
	protected $membership_relationships = array();
	
	protected $transactions = array();
	
	protected $is_admin = false;
	
	/** Staus to activate or deactivate a user independently of the membership status. */
	protected $active = true;
	
	protected $username;
	
	protected $email;
	
	protected $phone_number;
		
	protected $address;
	
	protected $address_number;
	
	protected $address_comp;
	
	protected $district;
	
	protected $city;
	
	protected $state_cd;
	
	protected $country;
	
	protected $zip_code;
	
	protected static $ignore_fields = array( 'id', 'name', 'username', 'email', 'actions', 'filters' );
	
	public function __construct( $user_id ) {
		$this->id = $user_id;
	}
	
	public static function get_current_member() {
		return self::load( get_current_user_id() );
	}
	
	public static function load( $user_id = 0 )
	{
		$member = new MS_Model_Member( $user_id );
		
		if( ! empty( $user_id ) )
		{
			$wp_user = new WP_User( $user_id );

			$member_details = get_user_meta( $user_id );
			$member->id = $user_id;
			$member->username = $wp_user->user_login;
			$member->email = $wp_user->user_email;
			$member->name = $wp_user->user_nicename;

			$member->is_admin = self::is_admin_user( $wp_user );

			$fields = get_object_vars( $member );
			foreach( $fields as $field => $val )
			{
				if( in_array( $field, self::$ignore_fields ) )
				{
					continue;
				}
				if( isset( $member_details[ "ms_$field" ][0] ) )
				{
					$member->$field = maybe_unserialize( $member_details[ "ms_$field" ][0] );
				}
			}
		}

		return $member;
	}
	
	public function save()
	{
		if( ! empty( $this->id ) )
		{
			$user_details = get_user_meta( $this->id );
			$fields = get_object_vars( $this );
			foreach( $fields as $field => $val )
			{
				if( in_array( $field, self::$ignore_fields ) )
				{
					continue;
				}
				if(isset( $this->$field ) && ( ! isset( $user_details[ "ms_$field" ][0]) || $user_details[ "ms_$field" ][0] != $this->$field ) )
				{
					update_user_meta( $this->id, "ms_$field", $this->$field);
				}
			}
			if(isset( $this->name ) )
			{
				$wp_user = new stdClass();
				$wp_user->ID = $this->id;
				$wp_user->nickname = $this->name;
				$wp_user->user_nicename = $this->name;
				$wp_user->display_name = $this->name;
				wp_update_user( get_object_vars( $wp_user ) );
			}				
		}
		else 
		{
			throw new Exception( "user id is empty" );
		}
		
		return $this;
	}
	
	public static function get_members_count( $args = null ) {
		$defaults = array(
				'number' => 10,
				'offset' => 0,
				'fields' => 'ID'
		);
		$args = wp_parse_args( $args, $defaults );
		$wp_user_search = new WP_User_Query( $args );
		
		return $wp_user_search->get_total();		
	}
	public static function get_members( $args = null ) {
		$defaults = array(
				'number' => 10,
				'offset' => 0,
				'fields' => 'ID'
		);
		$args = wp_parse_args( $args, $defaults );
		
		// Query the user IDs for this page
		$wp_user_search = new WP_User_Query( $args );
		
		$users = $wp_user_search->get_results();

		$members = array();
		foreach( $users as $user_id ) {
			$members[] = self::load( $user_id );
		}
		
		return $members;
		
	}
	/**
	 * Add a new membership.
	 * 
	 * Only add a membership ff a user is not already a member.
	 * @param int $membership_id The membership id to add to.
	 * @param string $gateway The gateway used to add the membership.
	 */
	public function add_membership( $membership_id, $gateway = 'admin' )
	{
		if( ! array_key_exists( $membership_id,  $this->membership_relationships ) ) {
			$membership_relationship = new MS_Model_Membership_Relationship( $membership_id, $gateway );
			$this->membership_relationships[ $membership_id ] = $membership_relationship;
			$this->membership_ids[ $membership_id ] = $membership_id;
		}
	}

	/**
	 * Deactivate membership.
	 * 
	 * Only update the status to deactivated.
	 * 
	 * @param int $membership_id The membership id to deactivate.
	 */
	public function deactivate_membership( $membership_id ) {
		if( ! array_key_exists( $membership_id,  $this->membership_relationships ) ) {
			$this->membership_relationships[ $membership_id ]->status = MS_Model_Membership_Relationship::MEMBERSHIP_STATUS_DEACTIVATED;
		}
	}
	
	/**
	 * Drop a membership.
	 * 
	 * @param int $membership_id The membership id to drop.
	 */
	public function drop_membership( $membership_id ) {
		if( array_key_exists( $membership_id,  $this->membership_relationships ) ) {
			unset( $this->membership_relationships[ $membership_id ] );
			unset( $this->membership_ids[ $membership_id ] );
		}
	}

	/**
	 * Move a membership.
	 * 
	 * Retain start date of the membership.
	 * @param int $move_from_id The membership id to move from.
	 * @param int $move_to_id The membership id to move to.
	 */
	public function move_membership( $move_from_id, $move_to_id ) {
		if( array_key_exists( $move_from_id,  $this->membership_relationships ) ) {
			$move_from = $this->membership_relationships[ $move_from_id ];
			$move_from->move( $move_from_id, $move_to_id );
			
			$this->drop_membership( $move_from_id );
			$this->membership_relationships[ $move_to_id ] = $move_from;
			$this->membership_ids[ $move_to_id ] = $move_to_id;
		}
	}
	
	public function is_member( $membership_id = 0 ) {
		$is_member = false;
		$member_status = array( MS_Model_Membership_Relationship::MEMBERSHIP_STATUS_ACTIVE,  MS_Model_Membership_Relationship::MEMBERSHIP_STATUS_TRIAL );
		
		if ( $this->is_admin ) {
			$is_member = true;
		}
		
		if( ! empty( $membership_id ) ) {
			if( array_key_exists( $membership_id,  $this->membership_relationships ) && 
					in_array( $this->membership_relationships[ $membership_id ]->get_status(), $member_status ) ) {
				$is_member = true;
			}
		}
		elseif ( ! empty ( $this->membership_relationships ) ) {
			foreach( $this->membership_relationships as $membership_relationship ) {
				if( in_array( $this->membership_relationships[ $membership_id ]->get_status(), $member_status ) ) {
					$is_member = true;
				}
			}
		}
		
		return apply_filters( 'membership_model_member_is_member', $is_member, $this->id );
	}
		
	public function is_logged_user() {
		return is_user_logged_in();
	}
	
	public static function is_admin_user( $wp_user = null )
	{
		$is_admin = false;

		if( empty( $wp_user ) )
		{
			$wp_user = wp_get_current_user();
		}
		if ( ! empty( $wp_user ) && ( $wp_user->has_cap( 'ms_membershipadmin' ) || $wp_user->has_cap( 'manage_options' ) || is_super_admin( $wp_user->ID ) ) ) {
			$is_admin = true;
		}
		return $is_admin;
	}
	
}