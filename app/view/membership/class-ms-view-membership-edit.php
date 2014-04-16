<?php

class MS_View_Membership_Edit extends MS_View {

	const MEMBERSHIP_SAVE_NONCE = 'membership_save_nonce';
	const RULE_SAVE_NONCE = 'rule_save_nonce';
	
	private $rule_types = array();
	private $fields = array();
	private $membership;
	private $section;

	public function _to_html() {		

		$tabs = array(
			'general' => array(
					'title' =>	__( 'General', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-edit&tab=general&membership_id=' . $this->data['id'],
			),
			'rules' => array(
					'title' =>	__( 'Protection Rules', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-edit&tab=rules&membership_id=' . $this->data['id'],
			),
		);

		ob_start();

		/** Render tabbed interface. */
		?>
		<div class='ms-wrap'>
		<h2 class='ms-settings-title'>Membership Settings</h2>		

		<?php
		$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
	
		/** Call the appropriate form to prepare. */
		call_user_func( array( $this, 'prepare_' . str_replace('-', '_', $active_tab ) ) );		
		/** Call the appropriate form to render. */		
		call_user_func( array( $this, 'render_' . str_replace('-', '_', $active_tab ) ) );

		?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	

	
	public function render_general() {

		$nonce = wp_create_nonce( self::MEMBERSHIP_SAVE_NONCE );
		ob_start();
		?>
		<div class='ms-settings'>
			<form id="setting_form" action="<?php echo add_query_arg( array( 'membership_id' => $this->membership->id ) ); ?>" method="post">
				<?php wp_nonce_field( self::MEMBERSHIP_SAVE_NONCE, self::MEMBERSHIP_SAVE_NONCE ); ?>
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
		echo $html;	}

	public function render_rules() {	
		$nonce = wp_create_nonce( self::RULE_SAVE_NONCE );
		$rule_list = new MS_Model_Rule_List_Table( $this->data['model'] );
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
					<?php wp_nonce_field( self::RULE_SAVE_NONCE, self::RULE_SAVE_NONCE ); ?>
					<?php $rule_list->display(); ?>
					<?php MS_Helper_Html::html_submit();?>
				</form>
				<div class="clear"></div>
			</div>
			<?php
			$html = ob_get_clean();
			echo $html;
	}
				
	public function prepare_general() {
		$this->membership = $this->data['model'];
		$this->section = 'ms_membership';

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

				
	public function prepare_rules() {
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
		foreach ( MS_Model_Rule::$RULE_TYPE_CLASSES as $rule_type => $class ) {
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