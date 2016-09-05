<?php

class MS_View_Membership_Add extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();
		$cols = count( $fields['type']['field_options'] );
		if ( $cols < 2 ) { $cols = 2; }
		if ( $cols > 3 ) { $cols = 2; }

		ob_start();
		?>
		<div class="ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Create New Membership', 'membership2' ),
					'desc' => __( 'First up, choose a name and a type for your membership site.', 'membership2' ),
				)
			);
			?>
			<div class="ms-settings ms-membership-add ms-cols-<?php echo esc_attr( $cols ); ?>">
				<form method="post" id="ms-choose-type-form">
					<div class="ms-settings-row cf">
						<h3><?php _e( 'Choose a membership type:', 'membership2' ); ?></h3>
						<?php MS_Helper_Html::html_element( $fields['type'] ); ?>
					</div>
					<div class="ms-settings-row cf">
						<?php MS_Helper_Html::html_element( $fields['name'] ); ?>
					</div>
					<div class="ms-settings-row cf">
						<div class="ms-options-wrapper">
							<?php
							foreach ( $fields['config_fields'] as $field ) {
								echo '<span class="opt">';
								MS_Helper_Html::html_element( $field );
								echo '</span>';
							}
							?>
						</div>
					</div>
					<div class="ms-control-fields-wrapper">
						<?php
						foreach ( $fields['control_fields'] as $field ) {
							MS_Helper_Html::html_element( $field );
						}
						?>
					</div>
				</form>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Prepare the fields displayed in the form.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function prepare_fields() {
		$membership = $this->data['membership'];

		$fields = array(
			'type' => array(
				'id' => 'type',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'value' => ( $membership->type ) ? $membership->type : MS_Model_Membership::TYPE_STANDARD,
				'class' => 'ms-choose-type',
				'field_options' => array(
					MS_Model_Membership::TYPE_STANDARD => array(
						'text' => __( 'Standard Membership', 'membership2' ),
						'desc' => __( 'Make your content available to Members and hidden to Guests (logged-out users).', 'membership2' ),
					),
					MS_Model_Membership::TYPE_DRIPPED => array(
						'text' => __( 'Dripped Content Membership.', 'membership2' ),
						'desc' => __( 'Set-up membership content to be released / made available in intervals.', 'membership2' ),
					),
					MS_Model_Membership::TYPE_GUEST => array(
						'text' => __( 'Guest Membership', 'membership2' ),
						'desc' => __( 'Make your content available only to Guests (logged-out users).', 'membership2' ),
					),
					MS_Model_Membership::TYPE_USER => array(
						'text' => __( 'Default Membership', 'membership2' ),
						'desc' => __( 'Content is available to all logged-in users that did not join any other Membership yet.', 'membership2' ),
					),
				),
			),

			'name' => array(
				'id' => 'name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Name Your Membership:', 'membership2' ),
				'value' => $membership->name,
				'class' => 'ms-text-large',
				'placeholder' => __( 'Choose a name that will identify this membership...', 'membership2' ),
				'label_type' => 'h3',
				'wrapper_class' => 'opt',
				'after' => sprintf(
					'<span class="locked-info">%1$s</span>',
					__( 'Not available for this Membership', 'membership2' )
				),
			),

			'config_fields' => array(
				'public' => array(
					'id' => 'public',
					'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
					'title' => __( 'Allow users to register for this membership.', 'membership2' ),
					'desc' => __( 'If selected, registration experience will be added to your site. Do not tick if you want to make this a private membership.', 'membership2' ),
					'after' => sprintf(
						'<span class="locked-info">%1$s</span>',
						__( 'Not available for this Membership', 'membership2' )
					),
					'value' => ! $membership->private,
				),
				'public_flag' => array(
					// See MS_Controller_Membership->membership_admin_page_process()
					'id' => 'set_public_flag',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => 1,
				),
				'paid' => array(
					'id' => 'paid',
					'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
					'title' => __( 'This is a paid membership.', 'membership2' ),
					'desc' => __( 'Choose this if you want to receive payments from members via Payment Gateways.', 'membership2' ),
					'after' => sprintf(
						'<span class="locked-info">%1$s</span>',
						__( 'Not available for this Membership', 'membership2' )
					),
					'value' => ! $membership->is_free(),
				),
				'paid_flag' => array(
					// See MS_Controller_Membership->membership_admin_page_process()
					'id' => 'set_paid_flag',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => 1,
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
					'cancel' => array(
						'id' => 'cancel',
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'value' => __( 'Cancel', 'membership2' ),
						'data_ms' => array(
							'action' => MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
							'field' => 'initial_setup',
							'value' => '0',
						)
					),
					'save' => array(
						'id' => 'save',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Save and continue', 'membership2' ) . ' &raquo;',
					),
			),
		);

		// Only one Guest Membership can be added
		if ( MS_Model_Membership::get_guest()->is_valid() ) {
			unset( $fields['type']['field_options'][MS_Model_Membership::TYPE_GUEST] );
		}

		// Only one User Membership can be added
		if ( MS_Model_Membership::get_user()->is_valid() ) {
			unset( $fields['type']['field_options'][MS_Model_Membership::TYPE_USER] );
		}

		// Wizard can only be cancelled when at least one membership exists in DB.
		$count = MS_Model_Membership::get_membership_count();
		if ( ! $count ) {
			unset( $fields['control_fields']['cancel'] );
		}

		return $fields;
	}
}
