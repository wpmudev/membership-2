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
	
	protected $manual_payment = true;
	
	protected $payment_info;
	
	public function purchase_button( $ms_relationship = false ) {
		
		$fields = array(
				'gateway' => array(
						'id' => 'gateway',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->id,
				),
				'ms_relationship_id' => array(
						'id' => 'ms_relationship_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $ms_relationship->id,
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'process_purchase',
				),
		);
		if( strpos( $this->pay_button_url, 'http' ) === 0 ) {
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
			<form method="post">
				<?php wp_nonce_field( "{$this->id}_{$ms_relationship->id}" ); ?>
				<?php MS_Helper_Html::html_input( $fields['ms_relationship_id'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['submit'] ); ?>
			</form>
		<?php 
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