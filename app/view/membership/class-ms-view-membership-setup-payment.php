<?php

class MS_View_Membership_Setup_Payment extends MS_View {

	protected $data;

	public function to_html() {
		$fields = $this->get_fields();

		$desc = MS_Helper_Html::html_element( $fields['is_free'], true );
		$wrapper_class = $this->data['is_global_payments_set'] ? '' : 'wide';

		if ( 1 == @$_GET['edit'] ) {
			$this->data[ 'show_next_button' ] = false;
		}

		ob_start();
		?>

		<div class="wrap ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Payment', MS_TEXT_DOMAIN ),
					'desc' => "$desc",
					'bread_crumbs' => $this->data['bread_crumbs'],
				)
			);
			?>
			<br class="clear" />
			<div class="ms-wrapper-center <?php echo esc_attr( $wrapper_class ); ?>">
				<div class="ms-separator"></div>

				<div id="ms-payment-settings-wrapper">
					<?php
					$this->global_payment_settings();

					if ( $this->data['membership']->can_have_children() ) {
						foreach ( $this->data['children'] as $child ) {
							$this->specific_payment_settings( $child );
						}
					}
					else {
						$this->specific_payment_settings( $this->data['membership'] );
					}
					?>
				</div>
				<br class="clear" />
				<?php MS_Helper_Html::settings_footer(
					$this->fields['control_fields'],
					$this->data['show_next_button']
				); ?>
			</div>
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
				'value' => ( ! $membership->is_free ? 0 : 1),
				'desc' => __( 'Do you want to accept payments for this membership?', MS_TEXT_DOMAIN ),
				'class' => 'ms-payments-choice ms-ajax-update',
				'field_options' => array(
					0 => __( 'Yes', MS_TEXT_DOMAIN ),
					1 => __( 'No', MS_TEXT_DOMAIN ),
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

		return apply_filters( 'ms_view_membership_setup_payment_get_fields', $fields );
	}

	/**
	 * Render the Payment settings the first time the user creates a membership.
	 * After the user set up a payment gateway these options are not displayed
	 * anymore
	 *
	 * @since  1.0.0
	 */
	public function global_payment_settings() {
		if ( $this->data['is_global_payments_set'] ) {
			return;
		}

		$view = MS_Factory::create( 'MS_View_Settings_Payment' );

		echo '<div class="ms-half space">';
		$view->render();
		echo '</div>';
	}

	/**
	 * Render the payment box for a single Membership subscription.
	 * For Simple/Dripped there will be one Payment box.
	 * For Content-Based/Tiered there will be one box per content/tier.
	 *
	 * @since  1.0.0
	 *
	 * @param  MS_Model_Membership $membership The membership/subscription
	 */
	public function specific_payment_settings( MS_Model_Membership $membership ) {
		// If multiple boxes are displayed only the first is expanded.
		static $First = true;

		$title = sprintf(
			__( '<span class="ms-item-name">%s</span> Specific Payment Settings:', MS_TEXT_DOMAIN ),
			$membership->name
		);
		$desc = sprintf(
			__( 'Payment Settings for <span class="ms-bold">%s</span>.', MS_TEXT_DOMAIN ),
			$membership->name
		);
		$fields = $this->get_specific_payment_fields( $membership );
		$type_class = $this->data['is_global_payments_set'] ? '' : 'ms-half right';
		$state = ($First ? 'open' : 'closed');

		?>
		<div class="ms-specific-payment-wrapper <?php echo esc_attr( $type_class ); ?>">
			<?php MS_Helper_Html::settings_box_header( $title, $desc, $state ); ?>
			<div class="ms-payment-structure-wrapper">
				<?php MS_Helper_Html::html_element( $fields['price'] ); ?>
				<?php MS_Helper_Html::html_element( $fields['payment_type'] ); ?>
			</div>
			<div class="ms-payment-types-wrapper">
				<div class="ms-payment-type-wrapper ms-payment-type-finite ms-period-wrapper">
					<?php MS_Helper_Html::html_element( $fields['period_unit'] );?>
					<?php MS_Helper_Html::html_element( $fields['period_type'] );?>
				</div>
				<div class="ms-payment-type-wrapper ms-payment-type-recurring ms-period-wrapper">
					<?php MS_Helper_Html::html_element( $fields['pay_cycle_period_unit'] );?>
					<?php MS_Helper_Html::html_element( $fields['pay_cycle_period_type'] );?>
				</div>
				<div class="ms-payment-type-wrapper ms-payment-type-date-range">
					<?php MS_Helper_Html::html_element( $fields['period_date_start'] );?>
					<span> to </span>
					<?php MS_Helper_Html::html_element( $fields['period_date_end'] );?>
				</div>
			</div>
			<div class="ms-after-end-wrapper">
				<?php MS_Helper_Html::html_element( $fields['on_end_membership_id'] );?>
			</div>
			<?php if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) : ?>
				<div class="ms-trial-wrapper">
					<div class="wpmui-input-label"><?php _e( 'Membership Trial:', MS_TEXT_DOMAIN ); ?></div>
					<div id="ms-trial-period-wrapper">
						<div class="ms-period-wrapper">
							<?php MS_Helper_Html::html_element( $fields['trial_period_enabled'] );?>
							<?php MS_Helper_Html::html_element( $fields['trial_period_unit'] );?>
							<?php MS_Helper_Html::html_element( $fields['trial_period_type'] );?>
						</div>
					</div>
				</div>
			<?php endif; ?>
			<?php
			MS_Helper_Html::save_text();
			MS_Helper_Html::settings_box_footer();
			?>
		</div>
		<?php
		$First = false;
	}

	/**
	 * Returns field definitions to render the payment box for the specified
	 * membership.
	 *
	 * @since  1.0.0
	 *
	 * @param  MS_Model_Membership $membership
	 * @return array An array containing all field definitions.
	 */
	private function get_specific_payment_fields( MS_Model_Membership $membership ) {
		global $wp_locale;

		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'price' => array(
				'id' => 'price_' . $membership->id,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Payment Structure:', MS_TEXT_DOMAIN ),
				'before' => MS_Plugin::instance()->settings->currency_symbol,
				'value' => $membership->price,
				'class' => 'ms-text-small ms-ajax-update',
				'placeholder' => '0' . $wp_locale->number_format['decimal_point'] . '00',
				'data_ms' => array( 'field' => 'price' ),
			),
			'payment_type' => array(
				'id' => 'payment_type_' . $membership->id,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $membership->payment_type,
				'field_options' => MS_Model_Membership::get_payment_types(),
				'read_only' => ( $membership->get_members_count() > 0 ) ? 'disabled' : '',
				'class' => 'ms-payment-type ms-ajax-update',
				'data_ms' => array( 'field' => 'payment_type' ),
			),
			'period_unit' => array(
				'id' => 'period_unit_' . $membership->id,
				'name' => '[period][period_unit]',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Period', MS_TEXT_DOMAIN ),
				'value' => $membership->period_unit,
				'class' => 'ms-text-small ms-ajax-update',
				'placeholder' => '0',
				'data_ms' => array( 'field' => 'period_unit' ),
			),
			'period_type' => array(
				'id' => 'period_type_' . $membership->id,
				'name' => '[period][period_type]',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $membership->period_type,
				'field_options' => MS_Helper_Period::get_periods(),
				'class' => 'ms-ajax-update',
				'data_ms' => array( 'field' => 'period_type' ),
			),
			'pay_cycle_period_unit' => array(
				'id' => 'pay_cycle_period_unit_' . $membership->id,
				'name' => '[pay_cycle_period][period_unit]',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Payment Cycle', MS_TEXT_DOMAIN ),
				'value' => $membership->pay_cycle_period_unit,
				'class' => 'ms-text-small ms-ajax-update',
				'placeholder' => '0',
				'data_ms' => array( 'field' => 'pay_cycle_period_unit' ),
			),
			'pay_cycle_period_type' => array(
				'id' => 'pay_cycle_period_type_' . $membership->id,
				'name' => '[pay_cycle_period][period_type]',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $membership->pay_cycle_period_type,
				'field_options' => MS_Helper_Period::get_periods(),
				'class' => 'ms-ajax-update',
				'data_ms' => array( 'field' => 'pay_cycle_period_type' ),
			),
			'period_date_start' => array(
				'id' => 'period_date_start_' . $membership->id,
				'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'title' => __( 'Date range', MS_TEXT_DOMAIN ),
				'value' => $membership->period_date_start,
				'class' => 'ms-ajax-update',
				'placeholder' => __( 'Start Date...', MS_TEXT_DOMAIN ),
				'data_ms' => array( 'field' => 'period_date_start' ),
			),
			'period_date_end' => array(
				'id' => 'period_date_end_' . $membership->id,
				'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'value' => $membership->period_date_end,
				'class' => 'ms-ajax-update',
				'placeholder' => __( 'End Date...', MS_TEXT_DOMAIN ),
				'data_ms' => array( 'field' => 'period_date_end' ),
			),
			'on_end_membership_id' => array(
				'id' => 'on_end_membership_id_' . $membership->id,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'After this membership ends:', MS_TEXT_DOMAIN ),
				'value' => $membership->on_end_membership_id,
				'field_options' => $membership->get_after_ms_ends_options(),
				'class' => 'ms-ajax-update',
				'data_ms' => array( 'field' => 'on_end_membership_id' ),
			),
			'trial_period_enabled' => array(
				'id' => 'trial_period_enabled_' . $membership->id,
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Offer Free Trial lasting', MS_TEXT_DOMAIN ),
				'value' => $membership->trial_period_enabled,
				'class' => 'ms-ajax-update',
				'data_ms' => array( 'field' => 'trial_period_enabled' ),
			),
			'trial_period_unit' => array(
				'id' => 'trial_period_unit_' . $membership->id,
				'name' => '[trial_period][period_unit]',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $membership->trial_period_unit,
				'class' => 'ms-text-small ms-ajax-update',
				'placeholder' => '0',
				'data_ms' => array( 'field' => 'trial_period_unit' ),
			),
			'trial_period_type' => array(
				'id' => 'trial_period_type_' . $membership->id,
				'name' => '[trial_period][period_type]',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $membership->trial_period_type,
				'field_options' => MS_Helper_Period::get_periods(),
				'class' => 'ms-ajax-update',
				'data_ms' => array( 'field' => 'trial_period_type' ),
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

		foreach ( $fields as $key => $field ) {
			if ( is_array( @$field['data_ms'] ) ) {
				$fields[ $key ]['data_ms']['_wpnonce'] = $nonce;
				$fields[ $key ]['data_ms']['action'] = $action;
				$fields[ $key ]['data_ms']['membership_id'] = $membership->id;
			}
		}

		return apply_filters( 'ms_view_membership_setup_payment_get_global_fields', $fields );
	}

}