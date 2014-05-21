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

class MS_Controller_Shortcode extends MS_Controller {
	
	public function __construct() {
		add_shortcode( 'ms-membership-form', array( $this, 'membership_form' ) );
		add_shortcode( 'ms-membership-title', array( $this, 'membership_title' ) );
		add_shortcode( 'ms-membership-details', array( $this, 'membership_details' ) );
		add_shortcode( 'ms-membership-price', array( $this, 'membership_price' ) );
		add_shortcode( 'ms-membership-button', array( $this, 'membership_button' ) );
		
		add_shortcode( 'membership-login', array( $this, 'membership_login' ) );
		
	}

	public function membership_form( $atts ) {
		$data = shortcode_atts( 
			array(
				'title' => '',
				'signup_text' =>  __( 'Signup', MS_TEXT_DOMAIN ),
				'action' => 'membership_signup',
			), 
			$atts 
		);
		$args = array( 'post__not_in' => array( MS_Model_Membership::get_visitor_membership()->id ) );
		$data['memberships'] = MS_Model_Membership::get_memberships( $args );
		$view = apply_filters( 'ms_view_shortcode_membership_form', new MS_View_Shortcode_Membership_Form() );
		$view->data = $data;
		$view->to_html();
	}
	
	public function membership_title() {
		
	}
	
	public function membership_details() {
		
	}
	
	public function membership_price() {
		
	}
	
	public function membership_button() {
		
	}
	
	public function membership_login() {
		
	}
}