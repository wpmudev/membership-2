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

class MS_Model_Gateway_Manual extends MS_Model_Gateway {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $id = self::GATEWAY_MANUAL;
	
	protected $name = 'Manual Gateway';
	
	protected $description = 'Manual Gateway description';
	
	protected $is_single = true;
	
	protected $payment_info;
	
	public function purchase_button( $membership, $member, $move_from_id = 0, $coupon_id = 0 ) {
		$fields = array(
				'gateway' => array(
						'id' => 'gateway',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->id,
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $membership->id,
				),
				'move_from_id' => array(
						'id' => 'move_from_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $move_from_id,
				),
				'coupon_id' => array(
						'id' => 'coupon_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $coupon_id,
				),
		);
		if( strpos( $this->payment_url, 'http' ) === 0 ) {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
					'value' =>  $this->pay_button_url,
			);
		}
		else {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' =>  $this->pay_button_url ? $this->pay_button_url : __( 'Signup', MS_TEXT_DOMAIN ),
			);
		}
		
		?>
			<form action="<?php echo $this->get_return_url();?>" method="post">
				<?php wp_nonce_field( "{$this->id}_{$membership->id}" ); ?>
				<?php MS_Helper_Html::html_input( $fields['gateway'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['membership_id'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['move_from_id'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['coupon_id'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['submit'] ); ?>
			</form>
		<?php 
	}
	
	public function handle_return() {
		/** Change the query to show memberships special page and replace the content with payment instructions */
		global $wp_query;
		$settings = MS_Plugin::instance()->settings;
		$wp_query->query_vars['page_id'] = $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_MEMBERSHIPS );
		$wp_query->query_vars['post_type'] = 'page';

		if( ! empty( $_POST['membership_id'] ) && ! empty( $_POST['gateway'] ) &&
			! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['gateway'] .'_' . $_POST['membership_id'] ) ) {
		
			$membership_id = $_POST['membership_id'];
			$move_from_id = ! empty ( $_POST['move_from_id'] ) ? $_POST['move_from_id'] : 0;
			$member = MS_Model_Member::get_current_member();
			$ms_relationship = $member->add_membership( $membership_id, $this->id, $move_from_id );
			
			if( MS_Model_Membership_Relationship::STATUS_PENDING != $ms_relationship->status ) {
				$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME ) );
				wp_safe_redirect( $url );
				exit;
			}
			else{
				$this->add_action( 'the_content', 'content' );
			}
		}
		else {
			$this->add_action( 'the_content', 'content_error' );
		}
	}
	
	public function content() {
		ob_start();
		 if( empty( $this->payment_info ) ) {
			$link = admin_url( 'admin.php?page=membership-settings&tab=payment&gateway_id=manual_gateway&action=edit' );
		 	$this->payment_info = __( "You need to edit you manual payment gateway instructions <a href='$link'>here</a>");
		 }
		echo wpautop( $this->payment_info ); 
		$html = ob_get_clean();
		return $html;
	}
	
	public function content_error() {
		return __( 'Sorry, your signup request has failed. Try again.', MS_TEXT_DOMAIN );
	}
		
	/**
	 * Validate specific property before set.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'payment_info':
					$this->$property = wp_kses_post( $value );
					break;
				default:
					parent::__set( $property, $value );
					break;
			}
		}
	}
	
}