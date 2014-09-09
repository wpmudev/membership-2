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
	
	public static $instance;
	
	protected $id = self::GATEWAY_MANUAL;
	
	protected $name = 'Manual Gateway';
	
	protected $description = '(Bank orders, cash, etc)';
	
	protected $pro_rate = true;
	
	protected $manual_payment = true;
	
	protected $payment_info;
	
	public function after_load() {
		parent::after_load();
		if( $this->active ) {
			$this->add_action( 'ms_controller_gateway_purchase_info_content', 'purchase_info_content' );
		}
	}
	
	public function purchase_info_content() {
		if( empty( $this->payment_info ) ) {
			$link = admin_url( 'admin.php?page=membership-settings&tab=payment&gateway_id=manual_gateway&action=edit' );
			ob_start();
			?>
				<?php _e( 'It is only an example of manual payment gateway instructions', MS_TEXT_DOMAIN ); ?>
				<br />
				<?php echo sprintf( '%s <a href="%s">%s</a>', __( 'Edit it', MS_TEXT_DOMAIN ), $link, __( 'here.', MS_TEXT_DOMAIN ) ); ?>
				<br /><br />
				<?php _e( 'Name: Example name.', MS_TEXT_DOMAIN ); ?>
				<br />
				<?php _e( 'Bank: Example bank.', MS_TEXT_DOMAIN ); ?>
				<br />
				<?php _e( 'Bank account: Example bank acount 1234.', MS_TEXT_DOMAIN ); ?>
				<br />
			<?php 
			$this->payment_info = ob_get_clean();
		}
		if( ! empty( $_POST['ms_relationship_id'] ) ) {
			$ms_relationship = MS_Factory::load( 'MS_Model_Membership_Relationship', $_POST['ms_relationship_id'] );
			$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
			$this->payment_info .= sprintf( '<br />%s: %s%s', __( 'Total value', MS_TEXT_DOMAIN ), $invoice->currency, $invoice->total );
		}
		
		return wpautop( $this->payment_info ); 
	}

	/**
	 * Verify required fields.
	 *
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function is_configured() {
		$is_configured = true;
		$required = array( 'payment_info' );
		foreach( $required as $field ) {
			if( empty( $this->$field ) ) {
				$is_configured = false;
				break;
			}
		}
	
		return apply_filters( 'ms_model_gateway_manual_is_configured', $is_configured );
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