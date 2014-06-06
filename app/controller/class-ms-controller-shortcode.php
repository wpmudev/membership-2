<?php
/**
 * This file defines the MS_Controller_Shortcode class.
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
 * Controller for managing Membership shortcodes.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Shortcode extends MS_Controller {
	
	/**
	 * Prepare the shortcode hooks.  
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		add_shortcode( MS_Helper_Shortcode::SCODE_REGISTER_USER, array( $this, 'membership_register_user' ) );
		add_shortcode( MS_Helper_Shortcode::SCODE_SIGNUP, array( $this, 'membership_signup' ) );
		add_shortcode( MS_Helper_Shortcode::SCODE_UPGRADE, array( $this, 'membership_upgrade' ) );
		add_shortcode( MS_Helper_Shortcode::SCODE_RENEW, array( $this, 'membership_renew' ) );
		
		add_shortcode( MS_Helper_Shortcode::SCODE_MS_TITLE, array( $this, 'membership_title' ) );
		add_shortcode( MS_Helper_Shortcode::SCODE_MS_DETAILS, array( $this, 'membership_details' ) );
		add_shortcode( MS_Helper_Shortcode::SCODE_MS_PRICE, array( $this, 'membership_price' ) );
		add_shortcode( MS_Helper_Shortcode::SCODE_MS_BUTTON, array( $this, 'membership_button' ) );
		
		add_shortcode( MS_Helper_Shortcode::SCODE_LOGIN, array( $this, 'membership_login' ) );
		add_shortcode( MS_Helper_Shortcode::SCODE_MS_ACCOUNT, array( $this, 'membership_account' ) );	

	}

	/**
	 * Membership register callback function.  
	 *
	 * @since 4.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_register_user( $atts ) {
		MS_Helper_Debug::log( "Register user shortcode..." );
		$data = apply_filters(
				'ms_controller_shortcode_membership_register_user_atts',
				shortcode_atts(
						array(
								'first_name' => '',
								'last_name' => '',
								'username' => '',
								'email' => '',
								'membership_id' => 0,
								'errors' => '',
						),
						$atts
				)
		);
		$view = apply_filters( 'ms_view_shortcode_membership_register_user', new MS_View_Shortcode_Membership_Register_User() );
		$view->data = $data;
		return $view->to_html();
	}

	/**
	 * Membership signup callback function.  
	 *
	 * @since 4.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */	
	public function membership_signup( $atts ) {
		MS_Helper_Debug::log( __( 'About to run the signup shortcode...', MS_TEXT_DOMAIN ) );
		$data = apply_filters( 
				'ms_controller_shortcode_membership_signup_atts', 
				shortcode_atts( 
					array(
						'title' => '',
						MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_SIGNUP . '_text' =>  __( 'Signup', MS_TEXT_DOMAIN ),
						MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_MOVE . '_text' => __( 'Signup', MS_TEXT_DOMAIN ),
						MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_CANCEL . '_text' => __( 'Cancel', MS_TEXT_DOMAIN ),
						MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_RENEW . '_text' => __( 'Renew', MS_TEXT_DOMAIN ),
					), 
				$atts
			) 
		);

		// Get a list of all the memberships that the current user is part of
		$args = null;
		$data['member'] = MS_Model_Member::get_current_member();
		$not_in = $data['member']->membership_ids;
		$not_in = array_merge( $not_in, array( MS_Model_Membership::get_visitor_membership()->id, MS_Model_Membership::get_default_membership()->id ) );
		$args = array( 'post__not_in' => array_unique ( $not_in ) );

		// Now get all the memberships excluding the ones the member is already a part of
		$data['memberships'] = MS_Model_Membership::get_memberships( $args );

		// Create the signup form view
		$view = apply_filters( 'ms_view_shortcode_membership_signup', new MS_View_Shortcode_Membership_Signup() );
		$view->data = $data;
		return $view->to_html();
	}
	

	/**
	 * Membership title shortcode callback function.  
	 *
	 * @since 4.0.0
	 */	
	public function membership_title() {
		
	}

	/**
	 * Membership details shortcode callback function.  
	 *
	 * @since 4.0.0
	 */		
	public function membership_details() {
		
	}

	/**
	 * Membership price shortcode callback function.  
	 *
	 * @since 4.0.0
	 */		
	public function membership_price() {
		
	}

	/**
	 * Membership signup button shortcode callback function.  
	 *
	 * @since 4.0.0
	 */		
	public function membership_button() {
		
	}

	/**
	 * Membership login shortcode callback function.  
	 *
	 * @since 4.0.0
	 * @param mixed[] $atts Shortcode attributes.	
	 */		
	public function membership_login( $atts ) {
		$data = apply_filters( 'ms_controller_shortcode_membership_login_atts',
					shortcode_atts(
						array(
							'holder'        => 'div',
							'holderclass'   => 'ms-login-form',
							'item'          => '',
							'itemclass'     => '',
							'postfix'       => '',
							'prefix'        => '',
							'wrapwith'      => '',
							'wrapwithclass' => '',
							'redirect'      => filter_input( INPUT_GET, 'redirect_to', FILTER_VALIDATE_URL ),
							'lostpass'      => '',
							'header'		=> true,
							'register'		=> true,
							'title'			=> '',
						),
						$atts
					)
		);
		$view = apply_filters( 'ms_view_shortcode_membership_login', new MS_View_Shortcode_Membership_Login() );
		$view->data = $data;
		return $view->to_html();
	}

	/**
	 * Membership account page shortcode callback function.  
	 *
	 * @since 4.0.0
	 */		
	public function membership_account( $atts ) {
		MS_Helper_Debug::log( "Inside the Account shortcode..." );
		$data = apply_filters( 'ms_controller_shortcode_membership_account_atts',
				shortcode_atts(
						array(
								'user_id' => 0,
						),
						$atts
				)
		);
		$data['member'] = MS_Model_Member::get_current_member();
		$membership_ids = $data['member']->membership_ids;
		if( is_array( $membership_ids ) ) {
			foreach( $membership_ids as $membership_id ) {
				$data['membership'][] = MS_Model_Membership::load( $membership_id );
			}
		}
		$data['transaction'] = MS_Model_Transaction::get_transactions( array( 
				'author' => $data['member']->id,
				'posts_per_page' => 50,
				'meta_query' => array(
						array(
								'key' => 'amount',
								'value' => '0',
								'compare' => '!='
						)
			) ) );
		$view = apply_filters( 'ms_view_shortcode_account', new MS_View_Shortcode_Account() );
		$view->data = $data;
		return $view->to_html();
	}
}