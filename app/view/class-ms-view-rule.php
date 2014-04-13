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
	
	public function __construct() {
		
	}
	public static function protection_rules_metabox( $post ) {
		$membership = MS_Model_Membership::load( $post->ID );
		$rule_types = MS_Model_Rule::get_rule_types();
		$section = 'ms_rule';
		
		$fields = array( 
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
				
		);
		ob_start();
		?>
		<div class="wrap">
			<div class="postbox metabox-holder">
				<h3><label for="title">Membership protection rules</label></h3>
				<div class="inside">
					<form id="setting_form" action="" method="post">
						<?php wp_nonce_field( self::SAVE_NONCE, $section .'['.self::SAVE_NONCE.']' ); ?>
						<table class="form-table">
							<tbody>
								<?php foreach ($fields as $field): ?>
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
					<div style="clear:both;"></div>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
}