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

	protected $membership_relationships = array();
	
	protected $is_admin = false;
	
	/** Staus to activate or deactivate a user independently of the membership status. */
	protected $active = true;
	
	protected $username;
	
	protected $email;
	
	protected $name;
	
	protected $first_name;
	
	protected $last_name;
	
	protected $password;
	
	protected $password2;
		
	protected $gateway_profiles;
	
	public $ignore_fields = array( 'membership_relationships', 'id', 'name', 'username', 'email', 'name', 'first_name', 'last_name', 'password', 'password2', 'actions', 'filters', 'ignore_fields' );
		
	public static function get_current_member() {
		return MS_Factory::load( 'MS_Model_Member', get_current_user_id() );
	}
	
	public function save()
	{
		if( empty( $this->id ) ) {
			$this->create_new_user();
		}
		
		$user_details = get_user_meta( $this->id );
		$fields = get_object_vars( $this );
		foreach( $fields as $field => $val ) {
			if( in_array( $field, $this->ignore_fields ) ) {
				continue;
			}
			if( isset( $this->$field ) && ( ! isset( $user_details[ "ms_$field" ][0] ) || $user_details[ "ms_$field" ][0] != $this->$field ) ) {
				update_user_meta( $this->id, "ms_$field", $this->$field);
			}
		}
		if( isset( $this->username ) ) {
			$wp_user = new stdClass();
			$wp_user->ID = $this->id;
			$wp_user->nickname = $this->username;
			$wp_user->user_nicename = $this->username;
			$wp_user->first_name = $this->first_name;
			$wp_user->last_name = $this->last_name;
			$wp_user->display_name = $this->username;
			if( ! empty( $this->password ) && $this->password == $this->password2 ) {
				$wp_user->user_pass = $this->password;
			}
			wp_update_user( get_object_vars( $wp_user ) );
		}				
				
		return $this;
	}
	
	/**
	 * Create new WP user.
	 * @throws Exception
	 */
	private function create_new_user() {
		$validation_errors = new WP_Error();

		$required = array(
				'username' => __( 'Username', MS_TEXT_DOMAIN ),
				'email' => __( 'Email address', MS_TEXT_DOMAIN ),
				'password'   => __( 'Password', MS_TEXT_DOMAIN ),
				'password2'  => __( 'Password confirmation', MS_TEXT_DOMAIN ),
		);
		
		foreach( $required as $field => $message ) {
			if( empty( $this->$field ) ) {
				$validation_errors->add( $field, __( 'Please ensure that the ', MS_TEXT_DOMAIN ) . "<strong>$message</strong>" . __( ' information is completed.', MS_TEXT_DOMAIN ) );
			}
		}
		
		if( $this->password != $this->password2 ) {
			$validation_errors->add( 'passmatch', __( 'Please ensure the passwords match.', MS_TEXT_DOMAIN ) );
		}
			
		if( ! validate_username( $this->username ) ) {
			$validation_errors->add( 'usernamenotvalid', __( 'The username is not valid, sorry.', MS_TEXT_DOMAIN ) );
		}
			
		if( username_exists( $this->username ) ) {
			$validation_errors->add( 'usernameexists', __( 'That username is already taken, sorry.', MS_TEXT_DOMAIN ) );
		}
			
		if( ! is_email( $this->email ) ) {
			$validation_errors->add( 'emailnotvalid', __( 'The email address is not valid, sorry.', MS_TEXT_DOMAIN ) );
		}
			
		if( email_exists( $this->email ) ) {
			$validation_errors->add( 'emailexists', __( 'That email address is already taken, sorry.', MS_TEXT_DOMAIN ) );
		}

		$validation_errors = apply_filters( 'ms_model_membership_create_new_user_validation_errors', $validation_errors );
		
		$result = apply_filters( 'wpmu_validate_user_signup', array(
				'user_name' => $this->username,
				'orig_username' => $this->username,
				'user_email' => $this->email,
				'errors' => $validation_errors
		) );
		
		$validation_errors = $result['errors'];
		$errors = $validation_errors->get_error_messages();
		
		if( ! empty( $errors ) ) {
			throw new Exception( implode( '<br/>', $errors ) );
		}
		else {
			$user_id = wp_create_user( $this->username, $this->password, $this->email );
			if ( is_wp_error( $user_id ) ) {
				$validation_errors->add( 'userid', $user_id->get_error_message() );
				throw new Exception( implode( '<br/>', $validation_errors->get_error_messages() ) );
			}
			$this->id = $user_id;
		}
	}
	 
	/**
	 * Sign on user.
	 */
	public function signon_user() {
		if ( ! headers_sent() ) {
			$user = @wp_signon( array(
					'user_login'    => $this->username,
					'user_password' => $this->password,
					'remember'      => true,
				) 
			);
			
			if ( is_wp_error( $user ) && method_exists( $user, 'get_error_message' ) ) {
				return $user;
			} 
			else {
				/** Set the current user up */
				wp_set_current_user( $this->id );
			}
		}
		else {
			/** Set the current user up */
			wp_set_current_user( $this->id );
		}
	}
	
	public static function get_members_count( $args = null ) {
		$defaults = array(
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
			$members[] = MS_Factory::load( 'MS_Model_Member', $user_id );
		}
		
		return $members;
		
	}
	
	public static function get_members_usernames( $args = null ) {
		$defaults = array(
				'fields' => array( 'ID', 'user_login' ),
		);
		$args = wp_parse_args( $args, $defaults );
		
		// Query the user IDs for this page
		$wp_user_search = new WP_User_Query( $args );
		
		$users = $wp_user_search->get_results();

		$members = array();
		foreach( $users as $user ) {
			$members[ $user->ID ] = $user->user_login;
		}
		
		return $members;
	}
	
	/**
	 * Add a new membership.
	 * 
	 * If multiple membership is disabled, may move existing membership.
	 * 
	 * Only add a membership if a user is not already a member.
	 * 
	 * @since 4.0.0
	 * 
	 * @param int $membership_id The membership id to add to.
	 * @param optional string $gateway The gateway used to add the membership.
	 * @param optional int $move_from_id The membership id to move from if any.  
	 */
	public function add_membership( $membership_id, $gateway_id = 'admin', $move_from_id = 0 )
	{
		
		if( ! MS_Model_Membership::is_valid_membership( $membership_id ) ) {
			return;
		}
		$ms_relationship = null;
		
		if( ! array_key_exists( $membership_id,  $this->ms_relationships ) ) {
			$ms_relationship = MS_Model_Membership_Relationship::create_ms_relationship( $membership_id, $this->id, $gateway_id, $move_from_id );

			do_action( 'ms_model_membership_add_membership', $ms_relationship, $this );
				
			if( 'admin' != $gateway_id ) {
				$ms_relationship->get_current_invoice();
			}
			if( MS_Model_Membership_Relationship::STATUS_PENDING != $ms_relationship->status ) {
				$this->ms_relationships[ $membership_id ] = $ms_relationship;
			}
		}
		else {
			$ms_relationship = $this->ms_relationships[ $membership_id ];
		}
		
		return $ms_relationship;
	}
	
	/**
	 * Drop a membership.
	 * 
	 * Only update the status to deactivated.
	 * 
	 * @param int $membership_id The membership id to drop.
	 */
	public function drop_membership( $membership_id ) {
		
		if( array_key_exists( $membership_id,  $this->ms_relationships ) ) {
			do_action( 'ms_model_membership_drop_membership', $this->ms_relationships[ $membership_id ], $this );
			
			$this->ms_relationships[ $membership_id ]->deactivate_membership( false );
			unset( $this->ms_relationships[ $membership_id ] );
		}
	}

	/**
	 * Cancel a membership.
	 * 
	 * The membership remains valid until expiration date.
	 *
	 * @param int $membership_id The membership id to drop.
	 */
	public function cancel_membership( $membership_id ) {
		
		if( array_key_exists( $membership_id,  $this->ms_relationships ) ) {
			do_action( 'ms_model_membership_cancel_membership', $this->ms_relationships[ $membership_id ], $this );
		
			$this->ms_relationships[ $membership_id ]->cancel_membership( false );
		}
	}
	
	/**
	 * Move a membership.
	 * 
	 * @param int $move_from_id The membership id to move from.
	 * @param int $move_to_id The membership id to move to.
	 */
	public function move_membership( $move_from_id, $move_to_id ) {
		if( array_key_exists( $move_from_id,  $this->ms_relationships ) ) {
			$move_from = $this->ms_relationships[ $move_from_id ];
			$ms_relationship = MS_Model_Membership_Relationship::create_ms_relationship( $move_to_id, $this->id, $move_from->gateway_id, $move_from_id );
			
			$this->cancel_membership( $move_from_id );
			$this->ms_relationships[ $move_to_id ] = $ms_relationship;
				
			MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_MOVE, $this->ms_relationships[ $move_to_id ] );
		}
	}
	
	/**
	 * Check membership relationship status.
	 * 
	 * Canceled status is allowed until it expires.
	 * 
	 * @param int $membership_id
	 * @return bool
	 */
	public function is_member( $membership_id = 0 ) {
		$is_member = false;
		/** Allowed membership status to have access */
		$allowed_status = apply_filters( 'membership_model_member_allowed_status', array( 
				MS_Model_Membership_Relationship::STATUS_ACTIVE,  
				MS_Model_Membership_Relationship::STATUS_TRIAL,
				MS_Model_Membership_Relationship::STATUS_CANCELED, 
			)
		);
		$simulate = MS_Factory::load( 'MS_Model_Simulate' );
		
		if ( $this->is_admin && ! $simulate->is_simulating() ) {
			$is_member = true;
		}
		
		if( ! empty( $membership_id ) ) {
			if( array_key_exists( $membership_id,  $this->ms_relationships ) && 
					in_array( $this->ms_relationships[ $membership_id ]->get_status(), $allowed_status ) ) {
				$is_member = true;
			}
		}
		elseif ( ! empty ( $this->ms_relationships ) ) {
			foreach( $this->ms_relationships as $membership_relationship ) {
				if( in_array( $membership_relationship->get_status(), $allowed_status ) ) {
					$is_member = true;
				}
			}
		}
		
		return apply_filters( 'membership_model_member_is_member', $is_member, $this->id, $membership_id );
	}

	public function delete_all_membership_usermeta() {
		$this->ms_relationships = array();
		$this->gateway_profiles = array();
	}
	
	public static function is_logged_user() {
		return is_user_logged_in();
	}
	
	public static function is_admin_user( $user_id = false, $capability = null ) {
		$is_admin = false;

		if( is_super_admin( $user_id ) ) {
			$is_admin = true;
		}

		if( ! empty( $capability ) ) {
			$wp_user = null;
			if( empty( $user_id ) ) {
				$wp_user = wp_get_current_user();
			}
			else {
				$wp_user = new WP_User( $user_id );
			}
			$is_admin = $wp_user->has_cap( $capability );
		}
		
		return apply_filters( 'ms_model_member_is_admin_user', $is_admin, $user_id );
	}
	
	public static function get_admin_user_emails() {
		$admins = array();
		
		$args = array(
				'role' => 'administrator',
				'fields' => array( 'ID', 'user_email' ),
		);
		
		$wp_user_search = new WP_User_Query( $args );
		$users = $wp_user_search->get_results();
		if( ! empty ($users ) ) {
			foreach( $users as $user ) {
				$admins[ $user->user_email ]  = $user->user_email;
			}
		}
		return $admins;
	}
	
	public static function get_username( $user_id ) {
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		return apply_filters( 'ms_model_member_get_username', $member->username, $user_id );
	}
	
	public function is_valid() {
		return ( $this->id > 0 );
	}
	
	public function get_gateway_profile( $gateway, $field = null ) {
		if( empty( $field ) ) {
			if( ! isset( $this->gateway_profiles[ $gateway ] ) ) {
				$this->gateway_profiles[ $gateway ] = array();
			}
			return $this->gateway_profiles[ $gateway ];
				
		}
		else {
			if( ! isset( $this->gateway_profiles[ $gateway ][ $field ] ) ) {
				$this->gateway_profiles[ $gateway ][ $field ] = '';
			}
			return $this->gateway_profiles[ $gateway ][ $field ];
		}
	}

	public function set_gateway_profile( $gateway, $field, $value ) {
		$this->gateway_profiles[ $gateway ][ $field ] = $value;
	}

	public function validate_member_info() {
		$validation_errors = new WP_Error();
		if( ! is_email( $this->email ) ) {
			$validation_errors->add( 'emailnotvalid', __( 'The email address is not valid, sorry.', MS_TEXT_DOMAIN ) );
		}
		if( $this->password != $this->password2 ) {
			MS_Helper_Debug::log("no macth");
			$validation_errors->add( 'passmatch', __( 'Please ensure the passwords match.', MS_TEXT_DOMAIN ) );
		}
		$errors = $validation_errors->get_error_messages();
		MS_Helper_Debug::log($validation_errors);
		if( ! empty( $errors ) ) {
			throw new Exception( implode( '<br/>', $errors ) );
		}
		else {
			return true;
		}
	}
	
	/**
	 * Set specific property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'email':
					if( is_email( $value ) ) {
						$this->$property = $value;
					}
					break;
				case 'username':
					$this->$property = sanitize_user( $value );
				case 'name':
				case 'first_name':
				case 'last_name':
					$this->$property = sanitize_text_field( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}