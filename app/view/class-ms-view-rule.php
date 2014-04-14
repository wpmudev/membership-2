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

class MS_View_Rule extends MS_View {
	
	const SAVE_NONCE = 'rule_save_nonce';
	
	protected $section = 'ms_rule';
	
	protected $fields;
	
	protected $membership;
	
	public function __construct( $membership ) {
	
		$this->set_membership( $membership );
		
	}
	
	public function membership_rule_edit( $tabs ) {
	
		ob_start();
		?>
			<div class='ms-wrap'>
				<h2 class='ms-settings-title'>Membership Details</h2>		
			
		<?php 
			
			$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
			
			$this->render_rule();
	
		?>
			</div>
		<?php
			
		$html = ob_get_clean();
		
		echo $html;
		}
	public function render_rule() {
	
		$nonce = wp_create_nonce( self::SAVE_NONCE );
		$rule_list = new MS_Rule_List_Table( $this->membership );
		$rule_list->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<table class="form-table">
					<tbody>
						<?php foreach ($this->fields as $field): ?>
							<tr valign="top">
								<td>
									<?php MS_Helper_Html::html_input( $field );?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<form id="setting_form" action="<?php echo add_query_arg( array( 'membership_id' => $this->membership->id ) ); ?>" method="post">
					<?php wp_nonce_field( self::SAVE_NONCE, self::SAVE_NONCE ); ?>
					<?php $rule_list->display(); ?>
					<?php MS_Helper_Html::html_submit();?>
				</form>
				<div class="clear"></div>
			</div>
			<?php
			$html = ob_get_clean();
			echo $html;
		}
	public function set_membership( $membership ) {

		$this->membership = $membership;
		$rule_types = MS_Model_Rule::get_rule_types();
		$section = 'ms_rule';
		
		$this->fields = array( 
			array( 
				'id' => 'rule_type', 
				'section' => $section, 
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Content to protect', MS_TEXT_DOMAIN ), 
				'value' => '', 
				'field_options' => $rule_types,
				'class' => '',
			),
			array( 
				'id' => 'rule_value', 
				'section' => $section, 
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => '', 
				'field_options' => $rule_types,
				'class' => '',
				'multiple' => 'multiple',
			),
			array(
				'id' => 'delay_access_enabled',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Delayed access (dripped content)', MS_TEXT_DOMAIN ),
				'value' => '',
				'class' => '',
			),
			array(
				'id' => 'delayed_period',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Delayed period', MS_TEXT_DOMAIN ),
				'value' => '',
				'class' => '',
			),
			array(
				'id' => 'delayed_period_type',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => '',
				'field_options' => MS_Helper_Period::get_periods(),
				'class' => '',
			),
			array(
				'id' => 'btn_add_rule',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Add Rule', MS_TEXT_DOMAIN ),
				'class' => '',
			),
				
		);
	}
	
}