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

/**
 * Communicataion model class.
 * 
 */
class MS_Model_Communication_Registration extends MS_Model_Communication {
	
	public static $POST_TYPE = 'ms_communication';
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $type = self::COMM_TYPE_REGISTRATION;
	
	public function __construct() {
	
		$this->comm_vars = array(
				'TODO' => 'config '. $this->type,
				'%blogname%' => 'Blog/site name',
				'%blogurl%' => 'Blog/site url',
				'%username%' => 'Username',
				'%usernicename%' => 'User nice name',
				'%userdisplayname%' => 'User display name',
				'%userfirstname%' => 'User first name',
				'%userlastname%' => 'User last name',
				'%networkname%' => 'Network name',
				'%networkurl%' => 'Network url',
				'%membershipname%' => 'Membership name',
				'%total%' => 'Invoice Total',
				'%taxname%' => 'Tax name',
				'%taxamount%' => 'Tax amount',
		);

		$this->add_action( 'ms_communications_process_'. $this->type, 'communication_process_registration', 10, 3 );

	}
	public function get_description() {
		return __( 'Sent when a member completes the registration for a  membership. For the first one, the terms of the membership are to be presented and the invoice will also be included in this email', MS_TEXT_DOMAIN );
	}
	
	public static function create_default_communication() {
		$model = new self();
	
		$model->subject = __( 'Registration complete', MS_TEXT_DOMAIN );
		$model->message = self::get_default_message();
		$model->enabled = true;
		$model->save();
	
		return $model;
	}
	
	public static function get_default_message() {
		ob_start();
		?>
			<h2> Welcome, %username%,</h2>
			<p>
				You are now member of %membershipname%.
			</p>
			<p>
				Invoice details: <br/><br/>
				Tax name: %taxname% <br/><br/>
				Tax amount: %taxamount% <br/><br/>
				Total: %total% <br/><br/>
			</p>
		<?php 
		$html = ob_get_clean();
		return apply_filters( 'ms_model_communication_registration_get_default_message', $html );
	}
	
	// Cant override parent method with different parameters... see below comment.
	public function communication_process() {
	}
		
	// Need to choose whether this is just a standard we adopt or make it different
	public function communication_process_registration( $user_id, $membership_id, $transaction_id ) {
		$this->send_message( $user_id, $membership_id, $transaction_id );
	}
}