<?php

class MS_View_Membership_Payment extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_html() {
		$fields = $this->get_fields();
		$wrapper_class = $this->data['is_global_payments_set'] ? '' : 'wide';

		ob_start();
		?>

		<div class="wrap ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Payment', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'wpmui-fa wpmui-fa-money',
					'desc' => __( 'Set up your payment gateways and Membership Price' ),
				)
			);
			?>
			<div class="ms-settings ms-wrapper-center ms-membership-payment cf <?php echo esc_attr( $wrapper_class ); ?>">
				<?php
				$this->global_payment_settings();
				$this->specific_payment_settings();

				MS_Helper_Html::settings_footer(
					$this->fields['control_fields'],
					$this->data['show_next_button']
				);
				?>
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

		return apply_filters(
			'ms_view_membership_payment_get_fields',
			$fields
		);
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

		$view = MS_Factory::create( 'MS_View_Settings_Page_Payment' );

		echo '<div class="ms-half space">';
		$view->render();
		MS_Helper_Html::html_separator( 'vertical' );
		echo '</div>';
	}

	/**
	 * Render the payment box for a single Membership subscription.
	 *
	 * @since  1.0.0
	 */
	public function specific_payment_settings() {
		$membership = $this->data['membership'];

		$title = sprintf(
			__( 'Payment settings for %s', MS_TEXT_DOMAIN ),
			$membership->get_name_tag()
		);

		$fields = $this->get_specific_payment_fields();
		$type_class = $this->data['is_global_payments_set'] ? '' : 'ms-half right';
		?>
		<div class="ms-specific-payment-wrapper <?php echo esc_attr( $type_class ); ?>">
			<div class="ms-header">
				<div class="ms-settings-tab-title">
					<h3><?php echo '' . $title; ?></h3>
				</div>
				<?php MS_Helper_Html::html_separator(); ?>
			</div>

			<div class="inside">
				<?php if ( ! $membership->can_change_payment() ) : ?>
					<div class="error below-h2">
						<p>
							<?php _e( 'This membership already has some paying members.', MS_TEXT_DOMAIN ); ?>
						</p>
						<p>
							<?php _e( 'Any changes will affect new invoices but not existing ones.', MS_TEXT_DOMAIN ); ?>
						</p>
					</div>
				<?php endif; ?>
				<div class="cf">
					<div class="ms-payment-structure-wrapper ms-half space">
						<?php
						MS_Helper_Html::html_element( $fields['payment_type'] );
						MS_Helper_Html::html_element( $fields['price'] );
						if ( isset( $fields['payment_type_val' ] ) ) {
							MS_Helper_Html::html_element( $fields['payment_type_val'] );
						}
						?>
					</div>
					<div class="ms-payment-types-wrapper ms-half">
						<div class="ms-payment-type-wrapper ms-payment-type-finite ms-period-wrapper">
							<?php
							MS_Helper_Html::html_element( $fields['period_unit'] );
							MS_Helper_Html::html_element( $fields['period_type'] );
							?>
						</div>
						<div class="ms-payment-type-wrapper ms-payment-type-recurring ms-period-wrapper">
							<?php
							MS_Helper_Html::html_element( $fields['pay_cycle_period_unit'] );
							MS_Helper_Html::html_element( $fields['pay_cycle_period_type'] );
							MS_Helper_Html::html_element( $fields['pay_cycle_repetitions'] );
							?>
						</div>
						<div class="ms-payment-type-wrapper ms-payment-type-date-range">
							<?php
							MS_Helper_Html::html_element( $fields['period_date_start'] );
							MS_Helper_Html::html_element( $fields['period_date_end'] );
							?>
						</div>
						<div class="ms-after-end-wrapper">
							<?php MS_Helper_Html::html_element( $fields['on_end_membership_id'] );?>
						</div>
					</div>
				</div>

				<?php /* Only show the trial option for PAID memberships */ ?>
				<?php if ( ! $membership->is_free ) : ?>
				<div class="cf">
					<?php
					$show_trial_note = MS_Plugin::instance()->settings->is_first_paid_membership;
					if ( ! empty( $_GET['edit'] ) ) { $show_trial_note = false; }
					if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) :
						?>
						<div class="ms-trial-wrapper">
							<?php
							MS_Helper_Html::html_separator();
							MS_Helper_Html::html_element( $fields['trial_period_enabled'] );
							$style = $membership->trial_period_enabled ? '' : 'style="display:none"';
							?>
							<div class="ms-trial-period-details" <?php echo '' . $style; ?>>
								<?php
								MS_Helper_Html::html_element( $fields['trial_period_unit'] );
								MS_Helper_Html::html_element( $fields['trial_period_type'] );
								?>
							</div>
						</div>
						<?php
					else : if ( $show_trial_note ) :
						?>
						<div class="ms-trial-wrapper">
							<?php MS_Helper_Html::html_separator(); ?>
							<h4>
								<?php _e( 'Well done, you just created your first paid membership!', MS_TEXT_DOMAIN ); ?>
							</h4>
							<p>
								<?php _e( 'To give visitors an extra incentive to register for this Membership you can offer a free trial period for a limited time. Do you want to enable this feature now?', MS_TEXT_DOMAIN ); ?>
							</p>
							<p>
								<?php MS_Helper_Html::html_element( $fields['enable_trial_addon'] ); ?><br />
								<em><?php _e( 'This message is only displayed once. Ignore it if you do not want to use trial memberships.', MS_TEXT_DOMAIN ); ?></em><br />
								<em><?php _e( 'You can change this feature anytime by visiting the Add-ons section.', MS_TEXT_DOMAIN ); ?></em>
							</p>
						</div>
						<?php
					endif; endif;
					?>
				</div>

				<?php if ( $this->data['is_global_payments_set'] && count( $fields['gateways'] ) ) : ?>
				<div class="cf ms-payment-gateways">
					<?php MS_Helper_Html::html_separator(); ?>
					<p><strong><?php _e( 'Allowed payment gateways', MS_TEXT_DOMAIN ); ?></strong></p>
					<?php foreach ( $fields['gateways'] as $field ) {
						MS_Helper_Html::html_element( $field );
					} ?>
				</div>
				<?php endif; ?>

				<?php endif; ?>

				<?php
				/**
				 * This action allows other add-ons or plugins to display custom
				 * options in the payment dialog.
				 *
				 * @since  1.0.0
				 */
				do_action(
					'ms_view_membership_payment_form',
					$this,
					$membership
				);
				?>
			</div>
			<?php MS_Helper_Html::save_text(); ?>
		</div>
		<?php
	}

	/**
	 * Returns field definitions to render the payment box for the specified
	 * membership.
	 *
	 * @since  1.0.0
	 *
	 * @return array An array containing all field definitions.
	 */
	private function get_specific_payment_fields() {
		global $wp_locale;

		$membership = $this->data['membership'];
		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce = wp_create_nonce( $action );

		$fields = array();
		$fields['price'] = array(
			'id' => 'price',
			'title' => __( 'Payment Amount', MS_TEXT_DOMAIN ),
			'type' => MS_Helper_Html::INPUT_TYPE_NUMBER,
			'before' => MS_Plugin::instance()->settings->currency_symbol,
			'value' => $membership->price, // Without taxes
			'class' => 'ms-text-smallish',
			'config' => array(
				'step' => 'any',
				'min' => 0,
			),
			'placeholder' => '0' . $wp_locale->number_format['decimal_point'] . '00',
			'ajax_data' => array( 1 ),
		);

		$fields['payment_type'] = array(
			'id' => 'payment_type',
			'title' => __( 'This Membership requires', MS_TEXT_DOMAIN ),
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => $membership->payment_type,
			'field_options' => MS_Model_Membership::get_payment_types(),
			'ajax_data' => array( 1 ),
		);

		$fields['period_unit'] = array(
			'id' => 'period_unit',
			'title' => __( 'Grant access for', MS_TEXT_DOMAIN ),
			'name' => '[period][period_unit]',
			'type' => MS_Helper_Html::INPUT_TYPE_NUMBER,
			'value' => $membership->period_unit,
			'class' => 'ms-text-small',
			'config' => array(
				'step' => 1,
				'min' => 1,
			),
			'placeholder' => '1',
			'ajax_data' => array( 1 ),
		);

		$fields['period_type'] = array(
			'id' => 'period_type',
			'name' => '[period][period_type]',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => $membership->period_type,
			'field_options' => MS_Helper_Period::get_period_types( 'plural' ),
			'ajax_data' => array( 1 ),
		);

		$fields['pay_cycle_period_unit'] = array(
			'id' => 'pay_cycle_period_unit',
			'title' => __( 'Payment Frequency', MS_TEXT_DOMAIN ),
			'name' => '[pay_cycle_period][period_unit]',
			'type' => MS_Helper_Html::INPUT_TYPE_NUMBER,
			'value' => $membership->pay_cycle_period_unit,
			'class' => 'ms-text-small',
			'config' => array(
				'step' => 1,
				'min' => 1,
			),
			'placeholder' => '1',
			'ajax_data' => array( 1 ),
		);

		$fields['pay_cycle_period_type'] = array(
			'id' => 'pay_cycle_period_type',
			'name' => '[pay_cycle_period][period_type]',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => $membership->pay_cycle_period_type,
			'field_options' => MS_Helper_Period::get_period_types( 'plural' ),
			'ajax_data' => array( 1 ),
		);

		$fields['pay_cycle_repetitions'] = array(
			'id' => 'pay_cycle_repetitions',
			'title' => __( 'Total Payments', MS_TEXT_DOMAIN ),
			'name' => '[pay_cycle_repetitions]',
			'type' => MS_Helper_Html::INPUT_TYPE_NUMBER,
			'after' => __( 'payments (0 = unlimited)', MS_TEXT_DOMAIN ),
			'value' => $membership->pay_cycle_repetitions,
			'class' => 'ms-text-small',
			'config' => array(
				'step' => '1',
				'min' => 0,
			),
			'placeholder' => '0',
			'ajax_data' => array( 1 ),
		);

		$fields['period_date_start'] = array(
			'id' => 'period_date_start',
			'title' => __( 'Grant access from', MS_TEXT_DOMAIN ),
			'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
			'value' => $membership->period_date_start,
			'placeholder' => __( 'Start Date...', MS_TEXT_DOMAIN ),
			'ajax_data' => array( 1 ),
		);

		$fields['period_date_end'] = array(
			'id' => 'period_date_end',
			'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
			'value' => $membership->period_date_end,
			'before' => _x( 'to', 'date range', MS_TEXT_DOMAIN ),
			'placeholder' => __( 'End Date...', MS_TEXT_DOMAIN ),
			'ajax_data' => array( 1 ),
		);

		$fields['on_end_membership_id'] = array(
			'id' => 'on_end_membership_id',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'title' => __( 'After this membership ends', MS_TEXT_DOMAIN ),
			'value' => $membership->on_end_membership_id,
			'field_options' => $membership->get_after_ms_ends_options(),
			'ajax_data' => array( 1 ),
		);

		$fields['enable_trial_addon'] = array(
			'id' => 'enable_trial_addon',
			'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
			'value' => __( 'Yes, enable Trial Memberships!', MS_TEXT_DOMAIN ),
			'button_value' => 1,
			'ajax_data' => array(
				'action' => MS_Controller_Addon::AJAX_ACTION_TOGGLE_ADDON,
				'_wpnonce' => wp_create_nonce( MS_Controller_Addon::AJAX_ACTION_TOGGLE_ADDON ),
				'addon' => MS_Model_Addon::ADDON_TRIAL,
				'field' => 'active',
			),
		);

		$fields['trial_period_enabled'] = array(
			'id' => 'trial_period_enabled',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title' => '<strong>' . __( 'Trial Period', MS_TEXT_DOMAIN ) . '</strong>',
			'after' => __( 'Offer Free Trial', MS_TEXT_DOMAIN ),
			'value' => $membership->trial_period_enabled,
			'ajax_data' => array( 1 ),
		);

		$fields['trial_period_unit'] = array(
			'id' => 'trial_period_unit',
			'name' => '[trial_period][period_unit]',
			'before' => __( 'The Trial is free and lasts for', MS_TEXT_DOMAIN ),
			'type' => MS_Helper_Html::INPUT_TYPE_NUMBER,
			'value' => $membership->trial_period_unit,
			'class' => 'ms-text-small',
			'config' => array(
				'step' => 1,
				'min' => 1,
			),
			'placeholder' => '1',
			'ajax_data' => array( 1 ),
		);

		$fields['trial_period_type'] = array(
			'id' => 'trial_period_type',
			'name' => '[trial_period][period_type]',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => $membership->trial_period_type,
			'field_options' => MS_Helper_Period::get_period_types( 'plural' ),
			'ajax_data' => array( 1 ),
		);

		$fields['membership_id'] = array(
			'id' => 'membership_id',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $membership->id,
		);

		$fields['action'] = array(
			'id' => 'action',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $this->data['action'],
		);

		// Get a list of all payment gateways.
		$gateways = MS_Model_Gateway::get_gateways();
		$fields['gateways'] = array();
		foreach ( $gateways as $gateway ) {
			if ( 'free' == $gateway->id ) { continue; }
			if ( ! $gateway->active ) { continue; }

			$payment_types = $gateway->supported_payment_types();
			$wrapper_class = 'ms-payment-type-' . implode( ' ms-payment-type-', array_keys( $payment_types ) );

			$fields['gateways'][$gateway->id] = array(
				'id' => 'disabled-gateway-' . $gateway->id,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => $gateway->name,
				'before' => __( 'Available', MS_TEXT_DOMAIN ),
				'after' => __( 'Not available', MS_TEXT_DOMAIN ),
				'value' => ! $membership->can_use_gateway( $gateway->id ),
				'class' => 'reverse',
				'wrapper_class' => 'ms-payment-type-wrapper ' . $wrapper_class,
				'ajax_data' => array(
					'field' => 'disabled_gateways[' . $gateway->id . ']',
					'_wpnonce' => $nonce,
					'action' => $action,
					'membership_id' => $membership->id,
				),
			);
		}

		// Modify some fields for free memberships.
		if ( $membership->is_free ) {
			$fields['price'] = '';
			$fields['payment_type'] = array(
				'id' => 'payment_type',
				'title' => __( 'Access Structure:', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $membership->payment_type,
				'field_options' => MS_Model_Membership::get_payment_types( 'free' ),
				'ajax_data' => array( 1 ),
			);
		}

		// Modify some fields if payment method cannot be changed anymore.
		/*if ( ! $membership->can_change_payment() ) {
			$payment_types = MS_Model_Membership::get_payment_types();
			$fields['payment_type'] = array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'before' => $payment_types[ $membership->payment_type ],
			);
			$fields['payment_type_val'] = array(
				'id' => 'payment_type',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership->payment_type,
			);
		}*/

		// Process the fields and add missing default attributes.
		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['ajax_data'] ) ) {
				if ( ! empty( $field['ajax_data']['action'] ) ) {
					continue;
				}

				if ( ! isset( $fields[ $key ]['ajax_data']['field'] ) ) {
					$fields[ $key ]['ajax_data']['field'] = $fields[ $key ]['id'];
				}
				$fields[ $key ]['ajax_data']['_wpnonce'] = $nonce;
				$fields[ $key ]['ajax_data']['action'] = $action;
				$fields[ $key ]['ajax_data']['membership_id'] = $membership->id;
			}
		}

		return apply_filters(
			'ms_view_membership_payment_get_global_fields',
			$fields
		);
	}

}