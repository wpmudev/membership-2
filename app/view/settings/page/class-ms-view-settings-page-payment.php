<?php

class MS_View_Settings_Page_Payment extends MS_View_Settings_Edit {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * HTML contains the list of available payment gateways.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$fields = $this->get_global_payment_fields();

		ob_start();
		?>
		<div id="ms-payment-settings-wrapper">
		<div class="ms-global-payment-wrapper">
			<div class="ms-list-table-wrapper">
				<?php
				MS_Helper_Html::settings_tab_header(
					array(
						'title' => __( 'Global Payment Settings', MS_TEXT_DOMAIN ),
						'desc' => __( 'These are shared across all memberships.', MS_TEXT_DOMAIN ),
					)
				);
				?>
				<div class="ms-half space">
					<?php MS_Helper_Html::html_element( $fields['currency'] ); ?>
				</div>
				<div class="ms-half">
					<?php MS_Helper_Html::html_element( $fields['invoice_sender_name'] ); ?>
				</div>

				<div class="ms-group-head">
					<div class="ms-bold"><?php _e( 'Payment Gateways', MS_TEXT_DOMAIN ); ?></div>
					<div class="ms-description"><?php _e( 'You need to set-up at least one Payment Gateway to be able to process payments.', MS_TEXT_DOMAIN ); ?></div>
				</div>

				<div class="gateways">
					<?php $this->gateway_settings(); ?>
				</div>
			</div>

			<?php MS_Helper_Html::settings_footer(); ?>
		</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Prepares a list with field definitions that are required to render the
	 * payment list/global options (i.e. currency and sender name)
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	protected function get_global_payment_fields() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'currency' => array(
				'id' => 'currency',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Select payment currency', MS_TEXT_DOMAIN ),
				'value' => $settings->currency,
				'field_options' => $settings->get_currencies(),
				'class' => '',
				'class' => 'ms-select',
				'data_ms' => array(
					'field' => 'currency',
				),
			),

			'invoice_sender_name' => array(
				'id' => 'invoice_sender_name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Invoice sender name', MS_TEXT_DOMAIN ),
				'value' => $settings->invoice_sender_name,
				'data_ms' => array(
					'field' => 'invoice_sender_name',
				),
			),
		);

		foreach ( $fields as $key => $field ) {
			if ( is_array( $field['data_ms'] ) ) {
				$fields[ $key ]['data_ms']['_wpnonce'] = $nonce;
				$fields[ $key ]['data_ms']['action'] = $action;
			}
		}

		return apply_filters( 'ms_gateway_view_get_global_payment_fields', $fields );
	}

	/**
	 * Displays the edit form for all payment gateways.
	 *
	 * @since  1.0.0
	 */
	protected function gateway_settings() {
		$gateways = MS_Model_Gateway::get_gateways();
		$groups = array();

		foreach ( $gateways as $gateway ) {
			$group = $gateway->group;
			if ( empty( $group ) ) { continue; }
			$groups[$group] = lib3()->array->get( $groups[$group] );
			$groups[$group][$gateway->id] = $gateway;
		}

		foreach ( $groups as $label => $group ) : ?>
		<div class="ms-gateway-group">
			<h4><?php echo $label; ?></h4>

			<?php
			foreach ( $group as $gateway ) {
				$this->gateway_item_settings( $gateway );
			}
			?>

		</div>
		<?php endforeach;
	}

	protected function gateway_item_settings( $gateway ) {
		$is_online = lib3()->net->is_online( MS_Helper_Utility::home_url( '/' ) );
		$row_class = 'gateway-' . $gateway->id;
		$active_class = 'ms-gateway-not-configured';

		if ( $gateway->is_configured() ) {
			$row_class .= ' is-configured';
			$active_class = 'ms-gateway-configured';
		} else {
			$row_class .= ' not-configured';
		}

		if ( $gateway->is_live_mode() ) {
			$row_class .= ' is-live';
		} else {
			$row_class .= ' is-sandbox';
		}

		if ( ! $is_online ) {
			$row_class .= ' is-offline';
		} else {
			$row_class .= ' is-online';
		}

		$actions = array(
			sprintf(
				'<a href="%1$s">%2$s</a>',
				MS_Controller_Plugin::get_admin_url(
					'billing',
					array( 'gateway_id' => $gateway->id )
				),
				__( 'View Transactions', MS_TEXT_DOMAIN )
			),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				MS_Controller_Plugin::get_admin_url(
					'billing',
					array( 'show' => 'logs', 'gateway_id' => $gateway->id )
				),
				__( 'View Logs', MS_TEXT_DOMAIN )
			),
		);

		$actions = apply_filters(
			'gateway_settings_actions',
			$actions,
			$gateway
		);

		$action_tag = array();
		foreach ( $actions as $action => $link ) {
			$action_tag[] = "<span class='$action'>$link</span>";
		}

		$toggle = array(
			'id' => 'ms-toggle-' . $gateway->id,
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'value' => $gateway->active,
			'data_ms' => array(
				'action' => MS_Controller_Gateway::AJAX_ACTION_TOGGLE_GATEWAY,
				'gateway_id' => $gateway->id,
			),
		);

		$settings_class = 'MS_Gateway_' . ucwords( esc_attr( $gateway->id ) ) . '_View_Settings';
		$settings = MS_Factory::create( $settings_class );
		$settings->data = array( 'model' => $gateway );

		// -- Output the form --

		?>
		<div class="ms-gateway-item <?php echo esc_attr( $row_class ); ?>">
			<span class="gateway-title">
				<label class="gateway-toggle gateway-name" for="settings-<?php echo esc_attr( $gateway->id ); ?>">
					<i class="row-status-close wpmui-fa wpmui-fa-caret-right"></i>
					<i class="row-status-open wpmui-fa wpmui-fa-caret-down"></i>
					<?php echo esc_html( $gateway->name ); ?>
				</label>
				<span class="gateway-description">
					<?php echo esc_html( $gateway->description ); ?>
				</span>
				<span class="wpmui-fa offline-flag" title="<?php echo __( 'Website seems to be not publicly available. This payment method might not work.', MS_TEXT_DOMAIN ); ?>">
				</span>
			</span>

			<span class="mode">
				<span class="mode-sandbox"><?php _e( 'Sandbox', MS_TEXT_DOMAIN ); ?></span>
				<span class="mode-live"><?php _e( 'Live', MS_TEXT_DOMAIN ); ?></span>
			</span>

			<div class="ms-gateway-status <?php echo esc_attr( $active_class ); ?> ms-active-wrapper-<?php echo esc_attr( $gateway->id ); ?>">
				<?php MS_Helper_Html::html_element( $toggle ); ?>
				<div class="ms-gateway-setup-wrapper">
					<label for="settings-<?php echo esc_attr( $gateway->id ); ?>" class="button">
						<i class="wpmui-fa wpmui-fa-cog"></i> <?php _e( 'Configure', MS_TEXT_DOMAIN ); ?>
					</label>
				</div>

			</div>

			<div class="row-actions"><?php echo implode( ' | ', $action_tag ); ?></div>

			<input type="checkbox" class="show-settings" id="settings-<?php echo esc_attr( $gateway->id ); ?>"/>
			<div class="ms-gateway-settings">
				<?php echo $settings->to_html(); ?>
			</div>

		</div>
		<?php
	}

}