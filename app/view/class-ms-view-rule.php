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
	
	protected $rule_types;
	
	public function __construct( $membership ) {
	
		$this->set_membership( $membership );
		
	}
	
	public function membership_rule_edit( $tabs ) {
	
		ob_start();
		?>
			<div class='ms-wrap'>
				<h2 class='ms-settings-title'>Manage Membership</h2>		
			
		<?php 
			
			MS_Helper_Html::html_admin_vertical_tabs( $tabs );
			
			$this->render_rule();
	
		?>
			</div>
		<?php
			
		$html = ob_get_clean();
		
		echo $html;
	}
	public function render_rule() {
	
		$nonce = wp_create_nonce( self::SAVE_NONCE );
		$rule_list = new MS_Helper_Rule_List_Table( $this->membership );
		$rule_list->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<td>
								<div>
									<span class='ms-field-label'><?php echo __( 'Content to protect', MS_TEXT_DOMAIN ); ?></span>
									<?php MS_Helper_Html::html_input( $this->fields['rule_type'] );?>
									<?php 
										foreach ($this->rule_types as $rule_type ) {
 											MS_Helper_Html::html_input( $rule_type );
										}
									?>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['delay_access_enabled'] );?>
								<div id="ms-delayed-period-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['delayed_period'] );?>
									<?php MS_Helper_Html::html_input( $this->fields['delayed_period_type'] );?>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<div id="ms-inherit-rules-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['inherit_rules'] );?>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['btn_add_rule'] );?>
							</td>
						</tr>
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
		$rule_types = MS_Model_Rule::get_rule_type_titles();
		$section = 'ms_rule';
		
		$this->fields = array( 
			'rule_type' => array( 
				'id' => 'rule_type', 
				'section' => $section, 
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => '', 
				'field_options' => $rule_types,
				'class' => '',
			),
			'delay_access_enabled' => array(
				'id' => 'delay_access_enabled',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Delayed access (dripped content)', MS_TEXT_DOMAIN ),
				'value' => '',
				'class' => '',
			),
			'delayed_period' => array(
				'id' => 'delayed_period',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Delayed period', MS_TEXT_DOMAIN ),
				'value' => '',
				'class' => '',
			),
			'delayed_period_type' => array(
				'id' => 'delayed_period_type',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => '',
				'field_options' => MS_Helper_Period::get_periods(),
				'class' => '',
			),
			'inherit_rules' => array(
					'id' => 'inherit_rules',
					'section' => $section,
					'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
					'title' => __( 'Inherit parents access by default (recommended)', MS_TEXT_DOMAIN ),
					'value' => '1',
					'class' => '',
			),
			'btn_add_rule' => array(
				'id' => 'btn_add_rule',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Add Rule', MS_TEXT_DOMAIN ),
				'class' => '',
			),
				
		);
		foreach ( MS_Model_Rule::get_rule_types as $rule_type ) {
			$this->rule_types["rule_value_$rule_type"] = array (
						'id' => "rule_value_$rule_type",
						'section' => $section,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => '',
						'field_options' => MS_Model_Rule::rule_factory( $rule_type )->get_content(),
						'class' => 'ms-select-rule-type',
						'multiple' => 'multiple',
			);
		}
	}
	
}