<?php

class MS_View_Membership_Edit extends MS_View {

	const MEMBERSHIP_SAVE_NONCE = 'membership_save_nonce';
	const RULE_SAVE_NONCE = 'rule_save_nonce';
	
	const MEMBERSHIP_SECTION = 'membership_section';
	const RULE_SECTION = 'rule_section';
	
	private $rule_types = array();
	protected $fields = array();
	protected $model;
	protected $section;
	

	public function to_html() {
		$tabs = array(
				'general' => array(
						'title' =>	__( 'General', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=general&membership_id=' . $this->model->id,
				),
				'rules' => array(
						'title' =>	__( 'Protection Rules', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=rules&membership_id=' . $this->model->id,
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
		$this->prepare_general();
		ob_start();
		?>
		<div class='ms-settings'>
			<form id="setting_form" action="" method="post">
				<?php wp_nonce_field( self::MEMBERSHIP_SAVE_NONCE, self::MEMBERSHIP_SAVE_NONCE ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['name'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['description'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['price'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['membership_type'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<div id="ms-membership-type-finite" class="ms-period-wrapper ms-membership-type">
									<?php MS_Helper_Html::html_input( $this->fields['period_unit'] );?>
									<?php MS_Helper_Html::html_input( $this->fields['period_type'] );?>
								</div>
								<div id="ms-membership-type-recurring" class="ms-period-wrapper ms-membership-type">
									<?php MS_Helper_Html::html_input( $this->fields['pay_cicle_period_unit'] );?>
									<?php MS_Helper_Html::html_input( $this->fields['pay_cicle_period_type'] );?>
								</div>
								<div id="ms-membership-type-date-range" class="ms-membership-type">
									<?php MS_Helper_Html::html_input( $this->fields['period_date_start'] );?>
									<span> to </span>
									<?php MS_Helper_Html::html_input( $this->fields['period_date_end'] );?>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['trial_period_enabled'] );?>
								<div id="ms-trial-period-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['trial_price'] );?>
									<div class="ms-period-wrapper">
										<?php MS_Helper_Html::html_input( $this->fields['trial_period_unit'] );?>
										<?php MS_Helper_Html::html_input( $this->fields['trial_period_type'] );?>
									</div>
								</div>
							</td>
						</tr>
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

	public function render_rules() {	
		$rule_list = new MS_Helper_List_Table_Rule( $this->model );
		$rule_list->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<table>
					<tbody>
						<tr valign="top">
							<td>
								<div>
									<span class='ms-field-label'><?php echo __( 'Content to protect', MS_TEXT_DOMAIN ); ?></span>
									<?php MS_Helper_Html::html_input( $this->fields['rule_type'] );?>
									<div id="ms-wrapper-rule-type-category" class="ms-rule-type-wrapper">
									<?php 
										$rule_list_table = new MS_Helper_List_Table_Rule_Category();
										$rule_list_table->prepare_items();
										$rule_list_table->display();
									?>
									</div>
									<div id="ms-wrapper-rule-type-page" class="ms-rule-type-wrapper">
									<?php 
										$rule_list_table = new MS_Helper_List_Table_Rule_Page();
										$rule_list_table->prepare_items();
										$rule_list_table->display();
									?>
									</div>
									<div id="ms-wrapper-rule-type-post" class="ms-rule-type-wrapper">
									<?php 
										$rule_list_table = new MS_Helper_List_Table_Rule_Post();
										$rule_list_table->prepare_items();
										$rule_list_table->display();
									?>
									</div>
									<?php 
// 										foreach ( $this->rule_types as $rule_type ) {
//  											MS_Helper_Html::html_input( $rule_type );
// 										}
									?>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['delay_access_enabled'] );?>
								<div id="ms-delayed-period-wrapper" class="ms-period-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['delayed_period_unit'] );?>
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
				<form id="setting_form" action="<?php echo add_query_arg( array( 'membership_id' => $this->model->id ) ); ?>" method="post">
					<?php wp_nonce_field( self::RULE_SAVE_NONCE, self::RULE_SAVE_NONCE ); ?>
					<?php $rule_list->display(); ?>
					<?php MS_Helper_Html::html_submit();?>
				</form>
				<div class="clear"></div>
			</div>
			<script id="rule_template" type="text/x-jquery-tmpl">
				<tr class="alternate">
					<td class="content_col column-content_col">
						${content}
						<input type="hidden" name="ms_rule[${counter}][rule_value]" value="${rule_value}">
					</td>
					<td class="rule_type column-rule_type">
						${rule_type}
						<input type="hidden" name="ms_rule[${counter}][rule_type]" value="${rule_type}">
					</td>
					<td class="delayed_period_unit_col column-delayed_period_unit_col">
						${delayed_period_unit} ${delayed_period_type}
						<input type="hidden" name="ms_rule[${counter}][delayed_period_unit]" value="${delayed_period_unit}">
						<input type="hidden" name="ms_rule[${counter}][delayed_period_type]" value="${delayed_period_type}">
					</td>
					<td class="inherit_col column-inherit_col">
						${inherit_rules}
						<input type="hidden" name="ms_rule[${counter}][inherit_rules]" value="${inherit_rules}">
					</td>
					<td class="actions_col column-actions_col">
						delete | edit
					</td>
				</tr> 
			</script>
			<?php
			$html = ob_get_clean();
			echo $html;
	}
				
	public function prepare_general() {

		$this->fields = array(
				'name' => array(
						'id' => 'name',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Name', MS_TEXT_DOMAIN ),
						'value' => $this->model->name,
						'class' => '',
				),
				'description' => array(
						'id' => 'description',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
						'title' => __( 'Description', MS_TEXT_DOMAIN ),
						'value' => $this->model->description,
						'class' => '',
				),
				'price' => array(
						'id' => 'price',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Price', MS_TEXT_DOMAIN ),
						'value' => $this->model->price,
						'class' => '',
				),
				'membership_type' => array(
						'id' => 'membership_type',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Membership type', MS_TEXT_DOMAIN ),
						'value' => $this->model->membership_type,
						'field_options' => array (
								'permanent' => __( 'Single payment for permanent access', MS_TEXT_DOMAIN ),
								'finite' => __( 'Single payment for finite access', MS_TEXT_DOMAIN ),
								'date-range' => __( 'Single payment for date range access', MS_TEXT_DOMAIN ),
								'recurring' => __( 'Recurring payment', MS_TEXT_DOMAIN ),
						),
						'class' => '',
				),
				'period_unit' => array(
						'id' => 'period_unit',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Period', MS_TEXT_DOMAIN ),
						'value' => $this->model->period_unit,
						'class' => '',
				),
				'period_type' => array(
						'id' => 'period_type',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'pay_cicle_period_unit' => array(
						'id' => 'pay_cicle_period_unit',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Payment Cicle', MS_TEXT_DOMAIN ),
						'value' => $this->model->pay_cicle_period_unit,
						'class' => '',
				),
				'pay_cicle_period_type' => array(
						'id' => 'pay_cicle_period_type',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->pay_cicle_period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'period_date_start' => array(
						'id' => 'period_unit',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Date range', MS_TEXT_DOMAIN ),
						'value' => $this->model->period_date_start,
						'class' => '',
				),
				'period_date_end' => array(
						'id' => 'period_date_end',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $this->model->period_unit,
						'class' => '',
				),
				'trial_period_enabled' => array(
						'id' => 'trial_period_enabled',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'title' => __( 'Trial period', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_period_enabled,
						'class' => '',
				),
				'trial_price' => array(
						'id' => 'trial_price',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Trial price', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_price,
						'class' => '',
				),
				'trial_period_unit' => array(
						'id' => 'trial_period_unit',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Trial period', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_period_unit,
						'class' => '',
				),
				'trial_period_type' => array(
						'id' => 'trial_period_type',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->trial_period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->model->id,
				),
		
		);		
	}

				
	public function prepare_rules() {
		$rule_types = MS_Model_Rule::get_rule_type_titles();
		$section = 'ms_rule';
		
		$this->fields = array( 
			'rule_type' => array( 
				'id' => 'rule_type', 
				'section' => self::RULE_SECTION, 
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => '', 
				'field_options' => $rule_types,
				'class' => '',
			),
			'delay_access_enabled' => array(
				'id' => 'delay_access_enabled',
				'section' => self::RULE_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Delayed access (dripped content)', MS_TEXT_DOMAIN ),
				'value' => '',
				'class' => '',
			),
			'delayed_period_unit' => array(
				'id' => 'delayed_period_unit',
				'section' => self::RULE_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Delayed period', MS_TEXT_DOMAIN ),
				'value' => '',
				'class' => '',
			),
			'delayed_period_type' => array(
				'id' => 'delayed_period_type',
				'section' => self::RULE_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => '',
				'field_options' => MS_Helper_Period::get_periods(),
				'class' => '',
			),
			'inherit_rules' => array(
					'id' => 'inherit_rules',
					'section' => self::RULE_SECTION,
					'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
					'title' => __( 'Inherit parents access by default (recommended)', MS_TEXT_DOMAIN ),
					'value' => '1',
					'class' => '',
			),
			'btn_add_rule' => array(
				'id' => 'btn_add_rule',
				'section' => self::RULE_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Add Rule', MS_TEXT_DOMAIN ),
				'class' => '',
			),
				
		);
		foreach ( MS_Model_Rule::$RULE_TYPE_CLASSES as $rule_type => $class ) {
			$this->rule_types["rule_value_$rule_type"] = array (
						'id' => "rule_value_$rule_type",
						'section' => self::RULE_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => '',
						'field_options' => MS_Model_Rule::rule_factory( $rule_type )->get_content(),
						'class' => 'ms-select-rule-type',
						'multiple' => 'multiple',
			);
		}		
	}
	
}