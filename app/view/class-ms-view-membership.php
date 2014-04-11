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
	
	public function __construct() {
		
	}
	public static function membership_general_metabox( $post ) {
		$membership = MS_Model_Membership::load( $post->ID );
		$section = 'ms_membership';
		$fields = array( 
			array( 
				'id' => 'name', 
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Name', MS_TEXT_DOMAIN ),
				'value' => $membership->name, 
				'class' => '',
			),
			array( 
				'id' => 'description', 
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
				'title' => __( 'Description', MS_TEXT_DOMAIN ), 
				'value' => $membership->description, 
				'class' => '',
			),
			array( 
				'id' => 'price', 
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Price', MS_TEXT_DOMAIN ), 
				'value' => $membership->price, 
				'class' => '',
			),
			array( 
				'id' => 'membership_type', 
				'section' => $section, 
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Membership type', MS_TEXT_DOMAIN ), 
				'value' => $membership->membership_type, 
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
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Payment Cicle', MS_TEXT_DOMAIN ), 
				'value' => $membership->pay_cicle_period_unit, 
				'class' => '',
			),
			array(
				'id' => 'pay_cicle_period_type',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $membership->pay_cicle_period_type,
				'field_options' => MS_Helper_Period::get_periods(),
				'class' => '',
			),
			array(
				'id' => 'trial_period_enabled',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Trial period', MS_TEXT_DOMAIN ),
				'value' => $membership->trial_period_enabled,
				'class' => '',
			),
			array(
				'id' => 'trial_price',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Trial price', MS_TEXT_DOMAIN ),
				'value' => $membership->trial_price,
				'class' => '',
			),
			array(
				'id' => 'trial_period_unit',
				'section' => $section,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $membership->trial_period_unit,
				'field_options' => MS_Helper_Period::get_periods(),
				'class' => '',
			),
				
		);
		ob_start();
		?>
		<div class="wrap">
			<div class="postbox metabox-holder">
				<h3><label for="title">These are the general settings for the membership</label></h3>
				<div class="inside">
					<form id="setting_form" action="" method="post">
						<input type="hidden" value="<?php echo $nonce; ?>" name="jun_nonce_field">
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
		<div class='ms-wrap'>
			<div class='ms-tab-container'>
		       <ul id="sortable-units" class="ms-tabs" style="">
	               <li class="ms-tab active">
	                   <a class="ms-tab-link" href="#">General</a>
	                    <div> 
	                    	<form action="">
	                    	<table class="form-table">
	                    	<tr>
	                    	<td> 
							<?php MS_Helper_Html::html_input( array( 'id' => 'name', 'section' => 'ms_membership', 'title' => __( 'Membership Name', MS_TEXT_DOMAIN ), 'value' => $membership->name ) );?>
							</td>
							</tr>>
							</table>
							</form>
						</div>
	               </li>
	               <li class="ms-tab">
	                   <a class="ms-tab-link" href="#">Link Title</a>                                                         
	               </li>
		       </ul>
		       <div style="clear:both;"></div>
		   </div>		
		</div>

		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
}