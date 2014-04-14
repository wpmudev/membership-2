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

class MS_View_Membership extends MS_View {

	const SAVE_NONCE = 'membership_save_nonce';
	
	protected $section = 'ms_membership';
	
	protected $fields;
	
	protected $membership;
	
	public function __construct( $membership ) {
				
		$this->set_membership( $membership );
	}
	public function membership_edit( $tabs ) {
				
		ob_start();
		?>
		<div class='ms-wrap'>
		<h2 class='ms-settings-title'>Membership Details</h2>		
		
		<?php 
		
		$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
		
		$this->render_general();

		?>
		</div>
		<?php
		
		$html = ob_get_clean();
		
		echo $html;
	}
		
	public function render_general() {

		$nonce = wp_create_nonce( self::SAVE_NONCE );
		ob_start();
		?>
		<div class='ms-settings'>
			<form id="setting_form" action="<?php echo add_query_arg( array( 'membership_id' => $this->membership->id ) ); ?>" method="post">
				<?php wp_nonce_field( self::SAVE_NONCE, self::SAVE_NONCE ); ?>
				<table class="form-table">
					<tbody>
						<?php foreach ($this->fields as $field): ?>
							<tr valign="top">
								<td>
									<?php MS_Helper_Html::html_input( $field );?>
								</td>
							</tr>
						<?php endforeach; ?>
						<tr>
							<td>
								<?php MS_Helper_Html::html_submit();?>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<div class="clear"></div>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function admin_membership_list() {
		$membership_list = new MS_Membership_List_Table();
		$membership_list->prepare_items();
		?>
		<div class="wrap">
			<h2><?php  _e( 'Memberships', MS_TEXT_DOMAIN ) ; ?>
				<a class="add-new-h2" href="/wp-admin/admin.php?page=membership-edit"><?php _e( 'Add New', MS_TEXT_DOMAIN ); ?></a>
			</h2>
			<?php $membership_list->display(); ?>
		</div>
		<?php 
	}
	
	public function set_membership( $membership ) {
		$this->membership = $membership;

		$this->fields = array(
				array(
						'id' => 'name',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Name', MS_TEXT_DOMAIN ),
						'value' => $this->membership->name,
						'class' => '',
				),
				array(
						'id' => 'description',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
						'title' => __( 'Description', MS_TEXT_DOMAIN ),
						'value' => $this->membership->description,
						'class' => '',
				),
				array(
						'id' => 'price',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Price', MS_TEXT_DOMAIN ),
						'value' => $this->membership->price,
						'class' => '',
				),
				array(
						'id' => 'membership_type',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Membership type', MS_TEXT_DOMAIN ),
						'value' => $this->membership->membership_type,
						'field_options' => array (
								'permanent' => __( 'Single payment for permanent access', MS_TEXT_DOMAIN ),
								'finite' => __( 'Single payment for finite access', MS_TEXT_DOMAIN ),
								'dt_range' => __( 'Single payment for date range access', MS_TEXT_DOMAIN ),
								'recurring' => __( 'Recurring payment', MS_TEXT_DOMAIN ),
						),
						'class' => '',
				),
				array(
						'id' => 'pay_cicle_period_unit',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Payment Cicle', MS_TEXT_DOMAIN ),
						'value' => $this->membership->pay_cicle_period_unit,
						'class' => '',
				),
				array(
						'id' => 'pay_cicle_period_type',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->membership->pay_cicle_period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				array(
						'id' => 'trial_period_enabled',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'title' => __( 'Trial period', MS_TEXT_DOMAIN ),
						'value' => $this->membership->trial_period_enabled,
						'class' => '',
				),
				array(
						'id' => 'trial_price',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Trial price', MS_TEXT_DOMAIN ),
						'value' => $this->membership->trial_price,
						'class' => '',
				),
				array(
						'id' => 'trial_period_unit',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Trial period', MS_TEXT_DOMAIN ),
						'value' => $this->membership->trial_period_unit,
						'class' => '',
				),
				array(
						'id' => 'trial_period_type',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->membership->trial_period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				array(
						'id' => 'membership_id',
						'section' => $this->section,
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->membership->id,
				),
		
		);
	}
}