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
	
	const MEMBERSHIP_STATUS_ACTIVE = 'active';
	
	const MEMBERSHIP_STATUS_TRIAL = 'trial';

	const MEMBERSHIP_STATUS_EXPIRED = 'expired';
	
	const MEMBERSHIP_STATUS_DEACTIVATED = 'deactivated';
	
	private $memberships = array();
	
	private $transactions;
	
	private $is_admin = false;
	
	private $active = false;
	
	private $username;
	
	private $email;
	
	private $phone_number;
		
	private $address;
	
	private $address_number;
	
	private $address_comp;
	
	private $district;
	
	private $city;
	
	private $state_cd;
	
	private $country;
	
	private $zip_code;
	
	protected static $ignore_fields = array( 'id', 'username', 'email', 'type_cd_options', 'state_cd_options', 'referer_options', 'actions', 'filters' );
	
	public function __construct( $user_id ) {
		$this->id = $user_id;
	}
	
	public static function load( $user_id = 0 )
	{
		$member = new MS_Model_Member( $user_id );
		
		if( ! empty( $member_id ) )
		{
			$wp_user = new WP_User( $member_id );

			$member_details = get_user_meta( $member_id );
			$member->id = $member_id;
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
				if( isset( $member_details[ $field ][0] ) )
				{
					$member->$field = $member_details[ $field ][0];
				}
			}
		}
		return $member;
	}
	public function save()
	{
		if( is_multisite() ) {
			switch_to_blog( 1 ); //TODO get main blog_id
		}
	
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
				if(isset( $this->$field ) && ( ! isset( $user_details[ $field ][0]) || $user_details[ $field ][0] != $this->$field ) )
				{
					update_user_meta( $this->id, $field, $this->$field);
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
		
		if( is_multisite() ) {
			restore_current_blog();
		}

		return $this;
	}
	
	public function add_membership( $membership_id, $gateway = 'admin' )
	{
		$membership = MS_Model_Membership::load( $membership_id );
		
		if( ! array_key_exists( $membership_id,  $this->memberships ) ) {
			$this->memberships[ $membership_id ] = array(
				'start_date' => MS_Helper_Period::current_date(),
				'update_date' => MS_Helper_Period::current_date(),
				'trial_expiry_date'	=> $membership->get_trial_expiry_date(),
				'expiry_date' => $membership->get_expiry_date(),
				'gateway' => $gateway,
				'membership_status' => ( $membership->trial_period_unit ) ? self::MEMBERSHIP_STATUS_TRIAL : self::MEMBERSHIP_STATUS_ACTIVE,
			);
			$this->active = true;
		}
	}

	public function deactivate_membership( $membership_id ) {
		if( ! array_key_exists( $membership_id,  $this->memberships ) )
		{
			$this->memberships[ $membership_id ]['membership_status'] = self::MEMBERSHIP_STATUS_DEACTIVATED;
		}
	}
	
	public function drop_membership( $membership_id ) {
		if( ! array_key_exists( $membership_id,  $this->memberships ) )
		{
			unset( $this->memberships[ $membership_id ] );
		}
	}
	
	public function is_member( $membership_id = 0 ) {
		$is_member = false;
		
		if ( $this->is_admin ) {
			$is_member = true;
		}
		
		if( ! empty( $membership_id ) ) {
			if( array_key_exists( $membership_id,  $this->memberships ) ) {
				$is_member = true;
			}
		}
		elseif ( ! empty ($this->memberships ) ) {
			$is_member = true;
		}
		
		return apply_filters( 'ms_is_member', $is_member, $this->id );
	}
	
	public function deactivate() {
		$this->active = false;
	}

	public static function is_logged_user()
	{
		global $current_user;
		return $current_user->ID;
	}
	
	public static function is_admin_user( $wp_user = null )
	{
		$is_admin = false;

		if( empty( $wp_user ) )
		{
			$wp_user = wp_get_current_user();
		}
		if ( ! empty( $wp_user ) && ( $wp_user->has_cap( 'ms_membershipadmin' ) || $wp_user->has_cap( 'manage_options' ) || is_super_admin( $wp_user->id ) ) ) {
			$is_admin = true;
		}
		return $is_admin;
	}
	
}