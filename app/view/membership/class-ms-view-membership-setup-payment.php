<?php

class MS_View_Membership_Setup_Payment extends MS_View {

	protected $data;
	
	public function to_html() {		
		$fields = $this->get_fields();

		$desc = MS_Helper_Html::html_input( $fields['is_free'], true );
		
		ob_start();
		?>
		
		<div class="wrap ms-wrap">
			<?php 
				MS_Helper_Html::settings_header( array(
					'title' => __( 'Payment', MS_TEXT_DOMAIN ),
					'desc' => "$desc",
					'bread_crumbs' => $this->data['bread_crumbs'],
				) ); 
			?>
			<div class="clear"></div>
			<hr />
			<div id="ms-payment-settings-wrapper">
				<div class="ms-list-table-half">
					<?php $this->global_payment_settings(); ?>
				</div>
				<?php
					if( $this->data['membership']->can_have_children() ) { 
						foreach( $this->data['children'] as $child ) {
							$this->specific_payment_settings( $child );
						}
					}
					else {
						$this->specific_payment_settings( $this->data['membership'] );
					}
				?>
			</div>
			<div class="clear"></div>
			<?php MS_Helper_Html::settings_footer( array( 'fields' => $this->fields['control_fields'] ) ); ?>
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	private function get_fields() {
		$membership = $this->data['membership'];
	
		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce = wp_create_nonce( $action );
		
		$fields = array(
				'is_free' => array(
						'id' => 'is_free',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
						'value' => $membership->is_free,
						'desc' => __( 'Do you want to accept payments for this membership?', MS_TEXT_DOMAIN ),
						'class' => 'ms-payments-choice ms-ajax-update',
						'field_options' => array(
								1 => __( 'Yes', MS_TEXT_DOMAIN ),
								0 => __( 'No', MS_TEXT_DOMAIN ),
						),
						'data_ms' => array(
								'field' => 'is_free',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'control_fields' => array(
						'membership_id' => array(
								'id' => 'membership_id',
								'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'value' => $membership->id,
						),
						'step' => array(
								'id' => 'step',
								'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'value' => $this->data['step'],
						),
						'action' => array(
								'id' => 'action',
								'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'value' => $this->data['action'],
						),
						'_wpnonce' => array(
								'id' => '_wpnonce',
								'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'value' => wp_create_nonce( $this->data['action'] ),
						),
				),
		);
		
		return apply_filters( 'ms_view_memebrship_setup_payment_get_fields', $fields );
	}
	
	public function global_payment_settings() {

		if( $this->data['is_global_payments_set'] ) {
// 			return;
		}
		
		$view = MS_Factory::create( 'MS_View_Gateway_Global' );
		$view->render();
	}
	
	public function specific_payment_settings( $membership ) {
		$title = sprintf( __( '%s Specific Payment Settings:', MS_TEXT_DOMAIN ), $membership->name );
		$desc = sprintf( __( 'Payment Settings for %s.', MS_TEXT_DOMAIN ), $membership->name );
		$fields = $this->get_specific_payment_fields( $membership );
		?>
		<div class="ms-specific-payment-wrapper ms-setup-half-width">
			<?php MS_Helper_Html::settings_box_header( $title, $desc ); ?>
				<div class="ms-payment-structure-wrapper">
					<?php MS_Helper_Html::html_input( $fields['price'] ); ?>
					<?php MS_Helper_Html::html_input( $fields['payment_type'] ); ?>
				</div>
				<div class="ms-payment-types-wrapper">
					<div class="ms-payment-type-wrapper ms-payment-type-finite ms-period-wrapper">
						<?php MS_Helper_Html::html_input( $fields['period_unit'] );?>
						<?php MS_Helper_Html::html_input( $fields['period_type'] );?>
					</div>
					<div class="ms-payment-type-wrapper ms-payment-type-recurring ms-period-wrapper">
						<?php MS_Helper_Html::html_input( $fields['pay_cycle_period_unit'] );?>
						<?php MS_Helper_Html::html_input( $fields['pay_cycle_period_type'] );?>
					</div>
					<div class="ms-payment-type-wrapper ms-payment-type-date-range">
						<?php MS_Helper_Html::html_input( $fields['period_date_start'] );?>
						<span> to </span>
						<?php MS_Helper_Html::html_input( $fields['period_date_end'] );?>
					</div>											
				</div>
				<div class="ms-trial-wrapper">
					<div class="ms-title"><?php _e( 'Membership Trial:', MS_TEXT_DOMAIN ); ?></div>
					<div id="ms-trial-period-wrapper">
						<div class="ms-period-wrapper">
							<?php MS_Helper_Html::html_input( $fields['trial_period_enabled'] );?>
							<?php MS_Helper_Html::html_input( $fields['trial_period_unit'] );?>
							<?php MS_Helper_Html::html_input( $fields['trial_period_type'] );?>
						</div>
					</div>
				</div>
				<div class="ms-after-end-wrapper">
					<?php MS_Helper_Html::html_input( $fields['on_end_membership_id'] );?>
				</div>
			<?php MS_Helper_Html::settings_box_footer(); ?>
		</div>
		<?php 
	}

	private function get_specific_payment_fields( $membership ) {
		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce = wp_create_nonce( $action );
	
		$fields = array(
				'price' => array(
						'id' => 'price_' . $membership->id,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Payment Structure:', MS_TEXT_DOMAIN ),
						'desc' => '$',
						'value' => $membership->price,
						'class' => 'ms-field-input-price ms-text-small ms-ajax-update',
						'data_ms' => array(
								'field' => 'price',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'payment_type' => array(
						'id' => 'payment_type_' . $membership->id,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $membership->payment_type,
						'field_options' => MS_Model_Membership::get_payment_types(),
						'class' => 'ms-field-input-membership-type ms-payment-type ms-ajax-update',
						'data_ms' => array(
								'field' => 'payment_type',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'period_unit' => array(
						'id' => 'period_unit_' . $membership->id,
						'name' => '[period][period_unit]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Period', MS_TEXT_DOMAIN ),
						'value' => $membership->period_unit,
						'class' => 'ms-field-input-period-unit ms-ajax-update',
						'data_ms' => array(
								'field' => 'period_unit',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'period_type' => array(
						'id' => 'period_type_' . $membership->id,
						'name' => '[period][period_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $membership->period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => 'ms-field-input-period-type ms-ajax-update',
						'data_ms' => array(
								'field' => 'period_type',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'pay_cycle_period_unit' => array(
						'id' => 'pay_cycle_period_unit_' . $membership->id,
						'name' => '[pay_cycle_period][period_unit]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Payment Cycle', MS_TEXT_DOMAIN ),
						'value' => $membership->pay_cycle_period_unit,
						'class' => 'ms-field-input-pay-cycle-period-unit ms-ajax-update',
						'data_ms' => array(
								'field' => 'pay_cycle_period_unit',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'pay_cycle_period_type' => array(
						'id' => 'pay_cycle_period_type_' . $membership->id,
						'name' => '[pay_cycle_period][period_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $membership->pay_cycle_period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => 'ms-field-input-pay-cycle-period-type ms-ajax-update',
						'data_ms' => array(
								'field' => 'pay_cycle_period_type',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'period_date_start' => array(
						'id' => 'period_date_start_' . $membership->id,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Date range', MS_TEXT_DOMAIN ),
						'value' => $membership->period_date_start,
						'class' => 'ms-field-input-period-date-start ms-ajax-update',
						'data_ms' => array(
								'field' => 'period_date_start',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'period_date_end' => array(
						'id' => 'period_date_end_' . $membership->id,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $membership->period_date_end,
						'class' => 'ms-field-input-period-date-end ms-ajax-update',
						'data_ms' => array(
								'field' => 'period_date_end',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'on_end_membership_id' => array(
						'id' => 'on_end_membership_id_' . $membership->id,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'After membership ends, change to', MS_TEXT_DOMAIN ),
						'value' => $membership->on_end_membership_id,
						'field_options' => MS_Model_Membership::get_membership_names(),
						'class' => 'ms-field-input-on-end-membership ms-ajax-update',
						'data_ms' => array(
								'field' => 'on_end_membership_id',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'trial_period_enabled' => array(
						'id' => 'trial_period_enabled_' . $membership->id,
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'title' => __( 'Offer Free Trial lasting', MS_TEXT_DOMAIN ),
						'value' => $membership->trial_period_enabled,
						'class' => 'ms-field-input-trial-period-enabled ms-ajax-update',
						'data_ms' => array(
								'field' => 'trial_period_enabled',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'trial_period_unit' => array(
						'id' => 'trial_period_unit_' . $membership->id,
						'name' => '[trial_period][period_unit]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $membership->trial_period_unit,
						'class' => 'ms-field-input-trial-period-unit ms-ajax-update',
						'data_ms' => array(
								'field' => 'trial_period_unit',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'trial_period_type' => array(
						'id' => 'trial_period_type_' . $membership->id,
						'name' => '[trial_period][period_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $membership->trial_period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => 'ms-field-input-trial-period-type ms-ajax-update',
						'data_ms' => array(
								'field' => 'trial_period_type',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'on_end_membership_id' => array(
						'id' => 'on_end_membership_id',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'After membership ends', MS_TEXT_DOMAIN ),
						'value' => $membership->on_end_membership_id,
						'field_options' => MS_Model_Membership::get_membership_names(),
						'class' => 'ms-field-input-on-end-membership ms-ajax-update',
						'data_ms' => array(
								'field' => 'on_end_membership_id',
								'_wpnonce' => $nonce,
								'action' => $action,
								'membership_id' => $membership->id,
						),
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $membership->id,
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['action'],
				),
		);
	
		return apply_filters( 'ms_view_memebrship_setup_payment_get_global_fields', $fields );
	}
	
}