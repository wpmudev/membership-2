<?php

class MS_View_Membership_Edit extends MS_View {

	const MEMBERSHIP_SECTION = 'membership_section';
	const DRIPPED_SECTION = 'item';
	
	protected $fields = array();
	
	protected $model;
	
	protected $title;
	
	protected $data;
	
	public function to_html() {
		$membership_id = $this->data['membership']->id;
		
		$tabs = $this->data['tabs'];
		ob_start();
		
		$this->title = __( 'Create New Membership Level', MS_TEXT_DOMAIN );
		if( $this->model->name ) {
			$this->title = $this->model->name;
			if( false === stripos( $this->title, 'membership' ) ) {
				$this->title = sprintf( __( '%s Membership Level', MS_TEXT_DOMAIN ), $this->title );
			}
		}
		/** Render tabbed interface. */
		?>
		<div class='ms-wrap wrap'>
			<h2 class='ms-settings-title'>
				<i class="fa fa-pencil-square"></i> 
				<?php echo $this->title; ?>
				<?php if( $this->model->name ): ?>
					<a class="add-new-h2" href="admin.php?page=membership-edit"><?php _e( 'Add New', MS_TEXT_DOMAIN ); ?></a>
				<?php endif; ?>
			</h2>		
	
			<?php
				$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
			
				/** Call the appropriate form to render. */
				$render_callback =  apply_filters( 'ms_view_membership_edit_render_callback', array( $this, 'render_' . str_replace('-', '_', $active_tab ) ), $active_tab, $this->data );
				call_user_func( $render_callback );
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
			<h3><?php _e( 'General Membership Level Settings', MS_TEXT_DOMAIN ); ?></h3>
			<div class="settings-description"><?php _e( 'Specify the settings you would like for this membership. Ideally you would not change these often.', MS_TEXT_DOMAIN ); ?></div>

			<form class="ms-form" action="" method="post">
				<?php wp_nonce_field( $this->data['action'] ); ?>
				<?php MS_Helper_Html::html_input( $this->fields['action'] );?>
				<table class="form-table">
					<tbody>
						<tr>
							<td>
								<?php
									$fields = array( $this->fields['name'], $this->fields['description'] );
									MS_Helper_Html::settingsbox( $fields, __( 'Membership Information', MS_TEXT_DOMAIN ), ' ', array( 'label_element' => 'h3' ) );
								?>
							</td>
						</tr>
						<?php if( ! $this->model->visitor_membership ): ?>
							<tr>
								<td>
								<?php
									$fields = array( $this->fields['price'] );
									MS_Helper_Html::settingsbox( $fields, __( 'Membership Pricing', MS_TEXT_DOMAIN ), ' ', array( 'label_element' => 'h3' ) );
								?>
								</td>
							</tr>
							<tr>
								<td>
									<div class="ms-settings-box-wrapper">
										<div class="ms-settings-box">
											<h3><?php _e( 'Membership Type', MS_TEXT_DOMAIN ); ?></h3>
											<div class="inside">
												<?php MS_Helper_Html::html_input( $this->fields['membership_type'], false, array( 'label_element' => 'h3' ) );?>
	
												<div id="ms-membership-type-wrapper">
													<div id="ms-membership-type-finite-wrapper" class="ms-period-wrapper ms-membership-type">
														<?php MS_Helper_Html::html_input( $this->fields['period_unit'], false, array( 'label_element' => 'h3' ) );?>
														<?php MS_Helper_Html::html_input( $this->fields['period_type'] );?>
													</div>
													<div id="ms-membership-type-recurring-wrapper" class="ms-period-wrapper ms-membership-type">
														<?php MS_Helper_Html::html_input( $this->fields['pay_cycle_period_unit'], false, array( 'label_element' => 'h3' ) );?>
														<?php MS_Helper_Html::html_input( $this->fields['pay_cycle_period_type'] );?>
													</div>
													<div id="ms-membership-type-date-range-wrapper" class="ms-membership-type">
														<?php MS_Helper_Html::html_input( $this->fields['period_date_start'], false, array( 'label_element' => 'h3' ) );?>
														<span> to </span>
														<?php MS_Helper_Html::html_input( $this->fields['period_date_end'] );?>
													</div>											
												</div>
												
												<div id="ms-membership-on-end-membership-wrapper">
													<?php MS_Helper_Html::html_input( $this->fields['on_end_membership_id'], false, array( 'label_element' => 'h3' ) );?>
												</div>
											</div>
										</div>
									</div>
									
								</td>
							</tr>
							<?php if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ): ?>
								<tr>
									<td>
										<div class="ms-settings-box-wrapper">
											<div class="ms-settings-box">
												<h3><?php _e( 'Membership Trial', MS_TEXT_DOMAIN ); ?></h3>
												<div class="inside">
													<?php MS_Helper_Html::html_input( $this->fields['trial_period_enabled'], false, array( 'label_element' => 'span' ) );?>
													<div id="ms-trial-period-wrapper">
														<?php MS_Helper_Html::html_input( $this->fields['trial_price'], false, array( 'label_element' => 'h3' ) );?>
														<div class="ms-period-wrapper">
															<?php MS_Helper_Html::html_input( $this->fields['trial_period_unit'], false, array( 'label_element' => 'h3' ) );?>
															<?php MS_Helper_Html::html_input( $this->fields['trial_period_type'], false, array( 'label_element' => 'h3' ) );?>
														</div>
													</div>
												</div>
											</div>
										</div>
									</td>
								</tr>
							<?php endif; ?>
							<tr>
								<td>
									<?php MS_Helper_Html::html_submit();?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
			<div class="clear"></div>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function prepare_general() {
		$gateways = MS_Model_Gateway::get_gateway_names( true );
		$gateways[''] = __( 'Select a payment gateway', MS_TEXT_DOMAIN );
		$this->fields = array(
				'name' => array(
						'id' => 'name',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Name', MS_TEXT_DOMAIN ),
						'value' => $this->model->name,
						'class' => 'ms-field-input-name',
						'tooltip' => __( 'Name your membership.<br />e.g. <strong>"Basic"</strong>, <strong>"Premium"</strong>', MS_TEXT_DOMAIN ),
				),
				'description' => array(
						'id' => 'description',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
						'title' => __( 'Description', MS_TEXT_DOMAIN ),
						'value' => $this->model->description,
						'class' => 'ms-field-input-description',
						'tooltip' => __( 'This description will appear on Membership listing pages.', MS_TEXT_DOMAIN ),
				),
				'price' => array(
						'id' => 'price',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Cost', MS_TEXT_DOMAIN ),
						'value' => $this->model->price,
						'class' => 'ms-field-input-price',
						'tooltip' => __( 'This will be displayed in the currency value you specified in "Settings > Payment". <br /> Use <strong>.</strong> as the decimal separator.', MS_TEXT_DOMAIN ),
				),
				'membership_type' => array(
						'id' => 'membership_type',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Type of membership', MS_TEXT_DOMAIN ),
						'value' => $this->model->membership_type,
						'field_options' => MS_Model_Membership::get_membership_types(),
						'class' => 'ms-field-input-membership-type',
						'tooltip' => '<strong>Single payment for permanent access:</strong><br />Members pay once and can access assigned content indefinitely or until manually removed.<br /><br />' . 
						             '<strong>Single payment for finite access:</strong><br />Members pay once and can access assigned content until membership expires.<br /><br /> ' . 
						             '<strong>Single payment for date range access:</strong><br />Members pay once to access content within a set date range.<br /><br />' .
						             '<strong>Recurring payment:</strong><br />Members pay an ongoing subscription to access content.' ,
				),
				'period_unit' => array(
						'id' => 'period_unit',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[period][period_unit]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Period', MS_TEXT_DOMAIN ),
						'value' => $this->model->period['period_unit'],
						'class' => 'ms-field-input-period-unit',
				),
				'period_type' => array(
						'id' => 'period_type',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[period][period_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->period['period_type'],
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => 'ms-field-input-period-type',
				),
				'pay_cycle_period_unit' => array(
						'id' => 'pay_cycle_period_unit',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[pay_cycle_period][period_unit]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Payment Cycle', MS_TEXT_DOMAIN ),
						'value' => $this->model->pay_cycle_period['period_unit'],
						'class' => 'ms-field-input-pay-cycle-period-unit',
				),
				'pay_cycle_period_type' => array(
						'id' => 'pay_cycle_period_type',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[pay_cycle_period][period_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->pay_cycle_period['period_type'],
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => 'ms-field-input-pay-cycle-period-type',
				),
				'period_date_start' => array(
						'id' => 'period_date_start',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Date range', MS_TEXT_DOMAIN ),
						'value' => $this->model->period_date_start,
						'class' => 'ms-field-input-period-date-start',
				),
				'period_date_end' => array(
						'id' => 'period_date_end',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $this->model->period_date_end,
						'class' => 'ms-field-input-period-date-end',
				),
				'on_end_membership_id' => array(
						'id' => 'on_end_membership_id',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'After membership ends, change to', MS_TEXT_DOMAIN ),
						'value' => $this->model->on_end_membership_id,
						'field_options' => MS_Model_Membership::get_membership_names(),
						'class' => 'ms-field-input-on-end-membership',
				),
				'trial_period_enabled' => array(
						'id' => 'trial_period_enabled',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'field_options' => array( 'checkbox_position' => 'right' ),
						'title' => __( 'Offer Membership Trial', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_period_enabled,
						'class' => 'ms-field-input-trial-period-enabled',
				),
				'trial_price' => array(
						'id' => 'trial_price',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Trial price', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_price,
						'class' => 'ms-field-input-trial-price',
				),
				'trial_period_unit' => array(
						'id' => 'trial_period_unit',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[trial_period][period_unit]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Trial period', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_period['period_unit'],
						'class' => 'ms-field-input-trial-period-unit',
				),
				'trial_period_type' => array(
						'id' => 'trial_period_type',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[trial_period][period_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->trial_period['period_type'],
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => 'ms-field-input-trial-period-type',
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->model->id,
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['action'],
				),
	
		);
	}
	
	public function render_page() {
		$model = $this->model->get_rule( 'page' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $model );
		$rule_list_table->prepare_items();
		
		$toggle = array(
				'id' => 'ms-toggle-rule',
				'title' => __( 'Default acccess rule:', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $model->rule_value_default,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE_DEFAULT,
						'membership_id' => $this->model->id,
						'rule' => $model->rule_type,
				),
		);

		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Page access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description">
				<div class="settings-description">
					<?php _e( 'Select the pages below that you would like to give access to as part of this membership. ', MS_TEXT_DOMAIN ); ?>
					<?php MS_Helper_Html::html_input( $toggle ); ?>
				</div>
				
				<hr />
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
		
	public function render_category() {
		$model = $this->model->get_rule( 'category' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Category( $model );
		$rule_list_table->prepare_items();
		
		$toggle = array(
				'id' => 'ms-toggle-rule',
				'title' => __( 'Default acccess rule:', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $model->rule_value_default,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE_DEFAULT,
						'membership_id' => $this->model->id,
						'rule' => $model->rule_type,
				),
		);

		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Category access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description">
					<?php _e( 'Select the post categories below that you would like to give access to as part of this membership.', MS_TEXT_DOMAIN ); ?>
					<?php MS_Helper_Html::html_input( $toggle ); ?>
				</div>
				<hr />			
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_post() {
		$model = array( 
			'post' => $this->model->get_rule( 'post' ), 
			'category'	=> $this->model->rules['category'],
		);
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $model );
		$rule_list_table->prepare_items();
		
		$toggle = array(
				'id' => 'ms-toggle-rule',
				'title' => __( 'Default acccess rule:', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $model['post']->rule_value_default,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE_DEFAULT,
						'membership_id' => $this->model->id,
						'rule' => $model['post']->rule_type,
				),
		);
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Post by post access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description">
					<?php _e( 'Select the posts below that you would like to give access to as part of this membership.', MS_TEXT_DOMAIN ); ?>
					<?php MS_Helper_Html::html_input( $toggle ); ?>
				</div>
				<hr />				
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}

	public function render_more_tag() {
		$model = $this->model->get_rule( 'more_tag' );
		$rule_list_table = new MS_Helper_List_Table_Rule_More( $model );
		$rule_list_table->prepare_items();
	
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'More tag content access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description"><?php _e( 'Select the more tag settings below that you would like to give access to as part of this membership.', MS_TEXT_DOMAIN ); ?></div>
				<hr />							
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
		}
		
	public function render_comment() {
		$model = $this->model->get_rule( 'comment' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Comment( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Comments access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description"><?php _e( 'Select the comment settings below that you would like to give access to as part of this membership. Commenting access is turned off by default.', MS_TEXT_DOMAIN ); ?></div>
				<hr />							
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
		
	public function render_menu() {
		$model = $this->model->get_rule( 'menu' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Menu( $model );
		$rule_list_table->prepare_items();
		
		$toggle = array(
				'id' => 'ms-toggle-rule',
				'title' => __( 'Default acccess rule:', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $model->rule_value_default,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE_DEFAULT,
						'membership_id' => $this->model->id,
						'rule' => $model->rule_type,
				),
		);

		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Menu access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description">
					<?php _e( 'Select the menu items below that you would like to give access to as part of this membership.', MS_TEXT_DOMAIN ); ?>
					<?php MS_Helper_Html::html_input( $toggle ); ?>
				</div>
				<hr />											
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_media() {
		$model = $this->model->get_rule( 'media' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Media( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Media access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description"><?php _e( 'Select the media below that you would like to give access to as part of this membership. Media access is turned off by default.', MS_TEXT_DOMAIN ); ?></div>
				<hr />											
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_shortcode() {
		$model = $this->model->get_rule( 'shortcode' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Shortcode( $model );
		$rule_list_table->prepare_items();
		
		$toggle = array(
				'id' => 'ms-toggle-rule',
				'title' => __( 'Default acccess rule:', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $model->rule_value_default,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE_DEFAULT,
						'membership_id' => $this->model->id,
						'rule' => $model->rule_type,
				),
		);

		ob_start();
		?>
		<div class='ms-settings'>
			<h3><?php echo __( 'Shortcode access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
			<div class="settings-description">
				<?php _e( 'Select the shortcodes below that you would like to give access to as part of this membership.', MS_TEXT_DOMAIN ); ?>
				<?php MS_Helper_Html::html_input( $toggle ); ?>
			</div>
			<hr />														
			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->display(); ?>
			</form>
		</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_cpt() {
		$model = $this->model->get_rule( 'cpt' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type( $model );
		$rule_list_table->prepare_items();
	
		$toggle = array(
				'id' => 'ms-toggle-rule',
				'title' => __( 'Default acccess rule:', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $model->rule_value_default,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE_DEFAULT,
						'membership_id' => $this->model->id,
						'rule' => $model->rule_type,
				),
		);

		ob_start();
		?>
		<div class='ms-settings'>
			<h3><?php echo __( 'Custom Post Type access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
			<div class="settings-description">
				<?php _e( 'Select the custom posts below that you would like to give access to as part of this membership. Note: To give access to an entire post type, turn off the "Custom Post Type - Post by post" addon.', MS_TEXT_DOMAIN ); ?>
				<?php MS_Helper_Html::html_input( $toggle ); ?>
			</div>
			<hr />			
			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->display(); ?>
			</form>
		</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_cpt_group() {
		$model = $this->model->get_rule( 'cpt_group' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type_Group( $model );
		$rule_list_table->prepare_items();
	
		$toggle = array(
				'id' => 'ms-toggle-rule',
				'title' => __( 'Default acccess rule:', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $model->rule_value_default,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE_DEFAULT,
						'membership_id' => $this->model->id,
						'rule' => $model->rule_type,
				),
		);
		
		ob_start();
		?>
		<div class='ms-settings'>
			<h3><?php echo __( 'Custom Post Type Group access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
			<div class="settings-description">
				<?php _e( 'Select the custom post types below that you would like to give access to as part of this membership.', MS_TEXT_DOMAIN ); ?>
				<?php MS_Helper_Html::html_input( $toggle ); ?>
			</div>
			<hr />														
			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->display(); ?>
			</form>
		</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	public function render_urlgroup() {
		$this->prepare_urlgroup();
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'URL Groups access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="metabox-holder">
					<div class="postbox">
					<h3 class="hndle" style="cursor:auto"><?php esc_html_e( 'Edit URL group', MS_TEXT_DOMAIN ) ?></h3>
						<div class="inside">
							<form action="" method="post" class="ms-form">
								<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
								<table class="form-table">
									<tbody>
										<?php foreach( $this->fields as $field ): ?>
											<tr>
												<td>
													<?php MS_Helper_Html::html_input( $field ); ?>
												</td>
											</tr>
											<?php endforeach; ?>
									</tbody>
								</table>
								<?php MS_Helper_Html::html_submit( array( 'id' => 'url_group_submit' ) ); ?>
							</form>
							<div class="clear"></div>
						</div>
					</div>
				</div>
				<div class="metabox-holder">
					<div class="postbox">
						<h3 class="hndle" style="cursor:auto"><?php esc_html_e( 'Test URL group', MS_TEXT_DOMAIN ) ?></h3>
						<div class="inside">
							<?php 
								MS_Helper_Html::html_input( array( 
									'id' => 'url_test',
									'desc' => __( 'Enter an URL to test against rules in the group', MS_TEXT_DOMAIN ),
									'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
									'class' => 'widefat',
								) ); 
							?>
							<div id="url-test-results-wrapper">
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	function prepare_urlgroup() {
		$model = $this->model->get_rule( 'url_group' );

		$this->fields = array(
				'access' => array(
						'id' => 'access',
						'title' => __( 'Access', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'value' => $model->access,
				),
				'rule_value' => array(
						'id' => 'rule_value',
						'title' => __( 'Page URLs', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
						'value' => implode( PHP_EOL, $model->rule_value ),
				),
				'strip_query_string' => array(
						'id' => 'strip_query_string',
						'title' => __( 'Strip query strings from URL', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'value' => $model->strip_query_string,
				),
				'is_regex' => array(
						'id' => 'is_regex',
						'title' => __( 'Is regular expression', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'value' => $model->is_regex,
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'save_url_group',
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->model->id,
				),
		);
	}
	
	public function render_dripped() {
		$model = array(
			'post' => $this->model->rules['post'],
			'page'	=> $this->model->rules['page'],
		);
		$this->prepare_dripped( $model );
		
		$rule_list_table = new MS_Helper_List_Table_Rule_Dripped( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Dripped content for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description"><?php _e( 'Select post/pages below that you would like to drip feed as part of this membership. Please note: Setting content as dripped will override all other access rules (this membership only).', MS_TEXT_DOMAIN ); ?></div>
				<hr />														
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['membership_copy'] );?>
					<?php MS_Helper_Html::html_input( $this->fields['action'] );?>	
					<?php MS_Helper_Html::html_submit( $this->fields['copy_dripped'] );?>
				</form>
				<form id="add_form">
				<table class="form-table">
					<tbody>
						<tr>
							<td id="ms-content-type-wrapper">
								<?php MS_Helper_Html::html_input( $this->fields['type'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<div id="ms-rule-type-post-wrapper" class="ms-rule-type-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['posts'] );?>
								</div>
								<div id="ms-rule-type-page-wrapper" class="ms-rule-type-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['pages'] );?>
								</div>
							</td>
						</tr>							
						<tr>
							<td>
								<div class="ms-period-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['period_unit'] );?>
									<?php MS_Helper_Html::html_input( $this->fields['period_type'] );?>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['btn_add'] );?>
							</td>
						</tr>
					</tbody>
				</table>
				</form>
				<form action="" method="post">
					<?php MS_Helper_Html::html_input( $this->fields['membership_id'] );?>
					<?php $rule_list_table->display(); ?>
					<?php MS_Helper_Html::html_submit( $this->fields['dripped_submit'] );?>
				</form>
				<script id="dripped_template" type="text/x-jquery-tmpl">
					<tr class="${css_class}">
						<td class="title column-title">
							<input type="hidden" id="${full_id}" name="item[${counter}][id]" value="${id}">
							${title}
						</td>
						<td class="dripped column-dripped">
							${period_unit} ${period_type}
							<input type="hidden" name="item[${counter}][period_unit]" value="${period_unit}">
							<input type="hidden" name="item[${counter}][period_type]" value="${period_type}">
						</td>
						<td class="type column-type">
							${type}
							<input type="hidden" name="item[${counter}][type]" value="${type}">
						</td>
						<td class="delete column-delete">
							<button class="ms-delete" type="button">Delete</button>
						</td>
					</tr>
				</script>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function prepare_dripped( $model ) {
		$membership_copy = MS_Model_Membership::get_membership_names();
		unset( $membership_copy[ $this->model->id ] );
		
		$this->fields = array(
				'type' => array(
						'id' => 'type',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
						'title' => __( 'Select content type', MS_TEXT_DOMAIN ),
						'field_options' => array( 
							'post' => __( 'Post', MS_TEXT_DOMAIN ), 
							'page' => __( 'Page', MS_TEXT_DOMAIN ), 
						),
						'value' => 'post',
						'class' => '',
				),
				'posts' => array(
						'id' => 'posts',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Select Post', MS_TEXT_DOMAIN ),
						'value' => 0,
						'field_options' =>$model['post']->get_content_array(),
						'class' => 'ms-radio-rule-type chosen-select',
				),
				'pages' => array(
						'id' => 'pages',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Select Page', MS_TEXT_DOMAIN ),
						'value' => 0,
						'field_options' => $model['page']->get_content_array(),
						'class' => 'ms-radio-rule-type chosen-select',
				),
				'period_unit' => array(
						'id' => 'period_unit',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Days/Months/Years until the content becomes available', MS_TEXT_DOMAIN ),
						'value' => 1,
						'class' => '',
				),
				'period_type' => array(
						'id' => 'period_type',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => null,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->model->id,
				),
				'btn_add' => array(
						'id' => 'btn_add',
						'section' => self::DRIPPED_SECTION,
						'value' => __('Add', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'class' => 'button-primary',
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'save_dripped',
				),
				'dripped_submit' => array(
						'id' => 'dripped_submit',
						'value' => __('Save Changes', MS_TEXT_DOMAIN ),
				),
				'membership_copy' => array(
						'id' => 'membership_copy',
						'title' => __('Copy dripped content schedule from another membership', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => null,
						'field_options' => $membership_copy,
						'class' => '',
				),
				'copy_dripped' => array(
						'id' => 'copy_dripped',
						'value' => __('Copy', MS_TEXT_DOMAIN ),
				),

		);
	}
}