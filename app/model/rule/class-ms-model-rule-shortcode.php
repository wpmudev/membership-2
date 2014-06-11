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
	
	protected $membership_id;
	/**
	 * Set initial protection.
	 * 
	 * Add [ms-protect-content] shortcode to protect membership content inside post.
	 */
	public function protect_content( $membership_relationship ) {
		global $shortcode_tags;
		
		$this->membership_id = $membership_relationship->membership_id;
		
		$exclude = MS_Helper_Shortcode::get_membership_shortcodes();
		
		foreach( $shortcode_tags as $shortcode => $callback_funciton ) {
			if( in_array( $shortcode, $exclude ) ) {
				continue;
			}
			if( ! in_array( $shortcode, $this->rule_value ) ) {
				$shortcode_tags[ $shortcode ] = array( &$this, 'do_protected_shortcode' );
			}
		}
		add_shortcode( self::PROTECT_CONTENT_SHORTCODE, array( $this, 'protect_content_shorcode') );
	}
	/**
	 * Do protected shortcode [do_protected_shortcode].
	 * 
	 * This shortcode is executed to replace a protected shortcode.
	 *  
	 */
	public function do_protected_shortcode() {
		return stripslashes( MS_Plugin::instance()->settings->protection_message );
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
		$atts = apply_filters(
				'ms_model_shortcode_protect_content_shorcode_atts',
				shortcode_atts(
						array(
								'id' => '',
						),
						$atts
				)
		);
		$membership_ids = explode( ',', $atts['id'] );
		
		if( ! empty( $membership_ids ) && ! in_array( $this->membership_id, $membership_ids ) ) {
			$membership_names = MS_Model_Membership::get_membership_names( array( 'post__in' => $membership_ids ) );
			$content = __( 'Content protected to members of: ', MS_TEXT_DOMAIN );
			$content .= implode( ', ', $membership_names ); 
		}
		
		return $content;
	}
	
	public function get_content( $args = null ) {
		global $shortcode_tags;
		
		$exclude = MS_Helper_Shortcode::get_membership_shortcodes();
		
		$contents = array();
		foreach( $shortcode_tags as $key => $function ) {
			if( in_array( $key, $exclude ) ) {
				continue;
			}
			$id = esc_html( trim( $key ) );
			$contents[ $id ]->id = $id;
			$contents[ $id ]->name = "[$key]";
			
			if( in_array( $id, $this->rule_value ) ) {
				$contents[ $id ]->access = true;
			}
			else {
				$contents[ $id ]->access = false;
			}
		}
		
		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		
		return $contents;
	}
}