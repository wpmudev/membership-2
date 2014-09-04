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


class MS_Model_Rule_Shortcode extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_SHORTCODE;

	const PROTECT_CONTENT_SHORTCODE = 'ms-protect-content';
	
	protected $membership_id = 0;
	
	/**
	 * Set initial protection.
	 * 
	 * Add [ms-protect-content] shortcode to protect membership content inside post.
	 * 
	 * @since 4.0.0
	 * @param MS_Model_Membership_Relationship $ms_relationship The user membership details.
	 */
	public function protect_content( $ms_relationship = false ) {

		$this->membership_id = $ms_relationship->membership_id;
		
		add_shortcode( self::PROTECT_CONTENT_SHORTCODE, array( $this, 'protect_content_shorcode') );
		
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SHORTCODE ) ) {
			global $shortcode_tags;
			$exclude = MS_Helper_Shortcode::get_membership_shortcodes();
			
			foreach( $shortcode_tags as $shortcode => $callback_funciton ) {
				if( in_array( $shortcode, $exclude ) ) {
					continue;
				}
				if( ! parent::has_access( $shortcode ) ) {
					$shortcode_tags[ $shortcode ] = array( &$this, 'do_protected_shortcode' );
				}
			}
		}
	}
	
	/**
	 * Do protected shortcode [do_protected_shortcode].
	 * 
	 * This shortcode is executed to replace a protected shortcode.
	 *  
	 */
	public function do_protected_shortcode() {

		$content = null;
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		if( $msg = $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE ) ) {
			$content = $msg;
		}
		else {
			$content = __( 'Shortcode content protected.', MS_TEXT_DOMAIN );
		}
		
		return apply_filters( 'ms_model_shortcode_do_protected_shortcode_content', $content );
	}
	
	/**
	 * Do membership content protection shortcode.
	 * 
	 * Verify if content is protected comparing to membership_id.
	 * 
	 * @todo Setup message displayed in admin settings.
	 * 
	 * @param array $atts
	 * @param string $content The content inside the shorcode.
	 * @param string $code The shortcode code.
	 * @return string
	 */
	public function protect_content_shorcode( $atts, $content = null, $code = '' ) {
		$atts = apply_filters( 'ms_model_shortcode_protect_content_shorcode_atts', shortcode_atts( array(
							'id' => '',
							'access' => 1,
					),
					$atts
		) );
		$membership_ids = explode( ',', $atts['id'] );
		
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$msg = $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE );
		
		/** No access to member of membership_ids */
		if( in_array( $atts['access'], array( 'false', false, 0, '0' ) ) ) {
			if( ! empty( $membership_ids ) && in_array( $this->membership_id, $membership_ids ) ) {
				$content = '<br />';
				if( ! empty( $msg ) ) {
					$content .= $msg;
				}
				else {
					$membership_names = MS_Model_Membership::get_membership_names( array( 'post__in' => $membership_ids ) );
					$content .= __( 'No access to members of: ', MS_TEXT_DOMAIN );
					$content .= implode( ', ', $membership_names );
				}
			}
		}
		/** Give access to member of membership_ids */
		else {
			if( ! empty( $membership_ids ) && ! in_array( $this->membership_id, $membership_ids ) ) {
				$content = '<br />';
				
				$membership_names = MS_Model_Membership::get_membership_names( array( 'post__in' => $membership_ids ) );
				if( ! empty( $msg ) ) {
					$content .= $msg;
					MS_Helper_Debug::log("protecte contn22");
				}
				else {
					$membership_names = MS_Model_Membership::get_membership_names( array( 'post__in' => $membership_ids ) );
					$content .= __( 'Content protected to members of: ', MS_TEXT_DOMAIN );
					$content .= implode( ', ', $membership_names );
				}
			}
		}
		
		return apply_filters( 'ms_model_rule_shortcode_protect_content_shorcode_content', $content, $atts, $content, $code );
	}
	
	/**
	 * Get the total content count.
	 * For list table pagination.
	 * @param string $args The default query post args.
	 * @return number The total content count.
	 */
	public function get_content_count( $args = null ) {
		return count( $this->get_content() );
	}
	
	/**
	 * Get content to protect.
	 *
	 * @since 4.0.0
	 *
	 * @param $args The content filtering arguments.
	 * @return array The content eligible to protect by this rule domain.
	 */
	public function get_content( $args = null ) {
		global $shortcode_tags;
		
		$exclude = MS_Helper_Shortcode::get_membership_shortcodes();
		
		$contents = array();
		foreach( $shortcode_tags as $key => $function ) {
			if( in_array( $key, $exclude ) ) {
				continue;
			}
			$id = esc_html( trim( $key ) );
			$contents[ $id ] = new StdClass();
			$contents[ $id ]->id = $id;
			$contents[ $id ]->name = "[$key]";
			$contents[ $id ]->type = $this->rule_type;
			$contents[ $id ]->access = parent::has_access( $id );
		}
		
		/** If not visitor membership, just show protected content */
		if( ! $this->rule_value_invert ) {
			$contents = array_intersect_key( $contents,  $this->rule_value );
		}
		
		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		
		if( ! empty( $args['posts_per_page'] ) ) {
			$total = $args['posts_per_page'];
			$offset = ! empty( $args['offset'] ) ? $args['offset'] : 0;
			$contents = array_slice( $contents, $offset, $total );
		}
		return $contents;
	}
}