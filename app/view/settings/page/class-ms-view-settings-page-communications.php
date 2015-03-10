<?php

class MS_View_Settings_Page_Communications extends MS_View_Settings_Edit {

	public function to_html() {
		$comm = $this->data['comm'];

		$this->add_action( 'admin_footer', 'wp_footer' );

		lib2()->array->equip(
			$comm,
			'type',
			'enabled',
			'period',
			'subject',
			'description',
			'cc_enabled',
			'cc_email'
		);

		$action = MS_Controller_Communication::AJAX_ACTION_UPDATE_COMM;
		$nonce = wp_create_nonce( $action );
		$comm_titles = MS_Model_Communication::get_communication_type_titles();

		$fields = array(
			'comm_type' => array(
				'id' => 'comm_type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $comm->type,
				'field_options' => $comm_titles,
			),

			'switch_comm_type' => array(
				'id' => 'switch_comm_type',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Load Email', MS_TEXT_DOMAIN ),
			),

			'type' => array(
				'id' => 'type',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $comm->type,
			),

			'enabled' => array(
				'id' => 'enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $comm->enabled,
				'data_ms' => array(
					'type' => $comm->type,
					'field' => 'enabled',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			// Note: title/desc is overwritten by MS_Model_Communication (below)
			'period_unit' => array(
				'id' => 'period_unit',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Period after/before', MS_TEXT_DOMAIN ),
				'value' => $comm->period['period_unit'],
			),

			'period_type' => array(
				'id' => 'period_type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $comm->period['period_type'],
				'field_options' => MS_Helper_Period::get_period_types( 'plural' ),
			),

			'subject' => array(
				'id' => 'subject',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Message Subject', MS_TEXT_DOMAIN ),
				'value' => $comm->subject,
				'class' => 'ms-comm-subject widefat',
			),

			'email_body' => array(
				'id' => 'email_body',
				'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
				'value' => $comm->description,
				'field_options' => array(
					'media_buttons' => false,
					'editor_class' => 'wpmui-ajax-update',
				),
			),

			'cc_enabled' => array(
				'id' => 'cc_enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Send copy to Administrator', MS_TEXT_DOMAIN ),
				'value' => $comm->cc_enabled,
			),

			'cc_email' => array(
				'id' => 'cc_email',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $comm->cc_email,
				'field_options' => MS_Model_Member::get_admin_user_emails(),
			),

			'save_email' => array(
				'id' => 'save_email',
				'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			),

			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'save_comm',
			),

			'nonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( 'save_comm' ),
			),

			'load_action' => array(
				'id' => 'load_action',
				'name' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'load_action',
			),

			'load_nonce' => array(
				'id' => '_wpnonce1',
				'name' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( 'load_action' ),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_messages_automated_fields', $fields );

		ob_start();

		MS_Helper_Html::settings_tab_header(
			array( 'title' => __( 'Automated Messages', MS_TEXT_DOMAIN ) )
		);
		?>

		<form id="ms-comm-type-form" action="" method="post">
			<?php MS_Helper_Html::html_element( $fields['load_action'] ); ?>
			<?php MS_Helper_Html::html_element( $fields['load_nonce'] ); ?>
			<?php MS_Helper_Html::html_element( $fields['comm_type'] ); ?>
			<?php MS_Helper_Html::html_element( $fields['switch_comm_type'] ); ?>
		</form>

		<?php MS_Helper_Html::html_separator(); ?>

		<form action="" method="post" class="ms-editor-form">
			<?php
			MS_Helper_Html::html_element( $fields['action'] );
			MS_Helper_Html::html_element( $fields['nonce'] );
			MS_Helper_Html::html_element( $fields['type'] );

			if ( is_a( $comm, 'MS_Model_Communication' ) ) {
				printf(
					'<h3>%1$s %2$s: %3$s</h3><div class="ms-description" style="margin-bottom:20px;">%4$s</div>',
					esc_html( $comm_titles[ $comm->type ] ),
					__( 'Message', MS_TEXT_DOMAIN ),
					MS_Helper_Html::html_element( $fields['enabled'], true ),
					$comm->get_description()
				);

				if ( $comm->period_enabled ) {
					echo '<div class="ms-period-wrapper clear">';
					$fields['period_unit'] = $comm->set_period_name( $fields['period_unit'] );
					MS_Helper_Html::html_element( $fields['period_unit'] );
					MS_Helper_Html::html_element( $fields['period_type'] );
					echo '</div>';
				}
			}

			MS_Helper_Html::html_element( $fields['subject'] );
			MS_Helper_Html::html_element( $fields['email_body'] );

			MS_Helper_Html::html_element( $fields['cc_enabled'] );
			echo ' &nbsp; ';
			MS_Helper_Html::html_element( $fields['cc_email'] );
			MS_Helper_Html::html_separator();
			MS_Helper_Html::html_element( $fields['save_email'] );
			?>
		</form>
		<?php

		return ob_get_clean();
	}

	/**
	 * Add short JS values in page footer.
	 *
	 * @since  1.1.0
	 */
	public function wp_footer() {
		$comm = $this->data['comm'];

		if ( ! isset( $comm->comm_vars ) ) {
			$comm->comm_vars = array();
		}

		/**
		 * Print JS details for the custom TinyMCE "Insert Variable" button
		 *
		 * @see class-ms-controller-settings.php (function add_mce_buttons)
		 * @see ms-view-settings-automated-msg.js
		 */
		$var_button = array(
			'title' => __( 'Insert Membership Variables', MS_TEXT_DOMAIN ),
			'items' => $comm->comm_vars,
		);

		printf(
			'<script>window.ms_data.var_button = %1$s;window.ms_data.lang_confirm = %2$s</script>',
			json_encode( $var_button ),
			json_encode(
				__( 'You have made changes that are not saved yet. Do you want to discard those changes?', MS_TEXT_DOMAIN )
			)
		);
	}

}