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
		
		add_shortcode( MS_Helper_Shortcode::SCODE_MS_INVOICE, array( $this, 'membership_invoice' ) );
	}

	/**
	 * Membership register callback function.  
	 *
	 * @since 4.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_register_user( $atts ) {
		$data = apply_filters( 'ms_controller_shortcode_membership_register_user_atts',
				shortcode_atts(
						array(
								'first_name' => substr( trim( filter_input( INPUT_POST, 'first_name' ) ), 0, 50 ),
								'last_name' => substr( trim( filter_input( INPUT_POST, 'last_name' ) ), 0, 50 ),
								'username' => substr( trim( filter_input( INPUT_POST, 'username' ) ), 0, 50 ),
								'email' => substr( trim( filter_input( INPUT_POST, 'email' ) ), 0, 50 ),
								'membership_id' => filter_input( INPUT_POST, 'membership_id' ),
								'errors' => '',
						),
						$atts
				)
		);
		$data['action'] = 'register_user';
		$data['step'] = 'register_submit';
		
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

		$data = apply_filters( 
				'ms_controller_shortcode_membership_signup_atts', 
				shortcode_atts( 
					array(
						'title' => '',
						MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP . '_text' =>  __( 'Signup', MS_TEXT_DOMAIN ),
						MS_Helper_Membership::MEMBERSHIP_ACTION_MOVE . '_text' => __( 'Signup', MS_TEXT_DOMAIN ),
						MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL . '_text' => __( 'Cancel', MS_TEXT_DOMAIN ),
						MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW . '_text' => __( 'Renew', MS_TEXT_DOMAIN ),
					), 
				$atts
			) 
		);

		$data['member'] = MS_Model_Member::get_current_member();
		/** Get member's memberships, including pending relationships. */
		$data['ms_relationships'] = MS_Model_Membership_Relationship::get_membership_relationships( array( 'user_id' => $data['member']->id, 'status' => 'valid' ) );
		$not_in = array();		
		/** Prepare select arguments to get the memberships user is not part of. */
		foreach( $data['ms_relationships'] as $ms_relationship ) {
			$not_in[] = $ms_relationship->membership_id;
		}
		$not_in = array_merge( $not_in, array( MS_Model_Membership::get_visitor_membership()->id, MS_Model_Membership::get_default_membership()->id ) );
		$args = array( 'post__not_in' => array_unique ( $not_in ) );
		
		/** Only active memberships */
		$args['meta_query']['active'] = array(
			'key'     => 'active',
			'value'   => true,
		); 
		
		/** Only public memberships when add-on is enabled. */
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_PRIVATE_MEMBERSHIPS ) ) {
			$args['meta_query']['public'] = array(
				'key'     => 'public',
				'value'   => true,
			); 
		}
//		MS_Helper_Debug::log($args);
		
		/** Retrieve memberships user is not part of, using selected args */
		$data['memberships'] = MS_Model_Membership::get_memberships( $args );
		
		/** When Multiple memberships is not enabled, a member should move to another membership. */
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
			/** Membership Relationship status which can move to another one */
			foreach( $data['member']->membership_relationships as $ms_relationship ) {
				if( in_array( $ms_relationship->status, array(
						MS_Model_Membership_Relationship::STATUS_TRIAL,
						MS_Model_Membership_Relationship::STATUS_ACTIVE,
						MS_Model_Membership_Relationship::STATUS_EXPIRED,
				 	) ) ) {
					
					$data['move_from_id'] = $ms_relationship->membership_id;
					break;
				}
			}
		}
		
		$view = apply_filters( 'ms_view_shortcode_membership_signup', new MS_View_Shortcode_Membership_Signup() );
		$view->data = $data;
// 		MS_Helper_Debug::log($data);
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
		$data = apply_filters( 'ms_controller_shortcode_membership_account_atts',
				shortcode_atts(
						array(
								'user_id' => 0,
						),
						$atts
				)
		);
		$data['member'] = MS_Model_Member::get_current_member();
		if( is_array( $data['member']->membership_relationships ) ) {
			foreach( $data['member']->membership_relationships as $ms_relationship ) {
				$data['membership'][] = $ms_relationship->get_membership();
			}
		}
		$data['invoices'] = MS_Model_Invoice::get_invoices( array( 
				'author' => $data['member']->id,
				'posts_per_page' => 50,
				'meta_query' => array( array(
								'key' => 'amount',
								'value' => '0',
								'compare' => '!='
		) ) ) );
		$data['news'] = MS_Model_Event::get_events( array(
				'author' => $data['member']->id,
				'posts_per_page' => 50,
		) );
		$view = apply_filters( 'ms_view_shortcode_account', new MS_View_Shortcode_Account() );
		$view->data = $data;
		return $view->to_html();
	}
	
	public function membership_invoice( $atts ) {
		$data = apply_filters( 'ms_controller_shortcode_invoice_atts',
				shortcode_atts(
						array(
								'post_id' => 0,
								'display_pay_button' => true,
						),
						$atts
				)
		);

		if( ! empty( $data['post_id'] ) ) {
			$invoice = MS_Model_Invoice::load( $data['post_id'] );
			$data['invoice'] = $invoice;
			$data['member'] = MS_Model_Member::load( $invoice->user_id );
			$ms_relationship = MS_Model_Membership_Relationship::load( $invoice->ms_relationship_id );
			$data['ms_relationship'] = $ms_relationship;
			$data['membership'] = $ms_relationship->get_membership();
			$data['gateway'] = $ms_relationship->get_gateway();

			$view = apply_filters( 'ms_view_shortcode_invoice', new MS_View_Shortcode_Invoice() );
			$view->data = $data;

			return $view->to_html();
		}
	}
}