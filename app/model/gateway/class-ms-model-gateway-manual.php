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
	
	protected $id = 'manual_gateway';
	
	protected $name = 'Manual Gateway';
	
	protected $description = 'Manual Gateway description';
	
	protected $is_single = true;
	
	public function payment_form( $membership, $member ) {
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
				'membership_signup' => array(
						'id' => 'membership_signup',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Signup', MS_TEXT_DOMAIN ),
				),
		);
		?>
			<form action="" method="post">
				<?php wp_nonce_field( "{$this->id}_{$membership->id}" ); ?>
				<?php MS_Helper_Html::html_input( $fields['gateway'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['membership_id'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['membership_signup'] ); ?>
			</form>
		<?php 
		}
}