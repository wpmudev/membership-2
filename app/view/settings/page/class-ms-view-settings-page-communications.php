<?php

class MS_View_Settings_Page_Communications extends MS_View_Settings_Edit {

	/**
	 * Return the HTML form.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_html() {
		$comm = $this->data['comm'];
		$fields = $this->get_fields();

		$this->add_action( 'admin_footer', 'wp_footer' );
		$title = __( 'Automated Email Responses', 'membership2' );

		if ( isset( $this->data['membership'] ) ) {
			$membership = $this->data['membership'];
		} else {
			$membership = false;
		}

		if ( $membership instanceof MS_Model_Membership ) {
			$settings_url = MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => MS_Controller_Settings::TAB_EMAILS )
			);
			$desc = sprintf(
				__( 'Here you can override %sdefault messages%s for this membership.', 'membership2' ),
				'<a href="' . $settings_url . '">',
				'</a>'
			);
		} else {
			$desc = '';
		}

		ob_start();

		MS_Helper_Html::settings_tab_header(
			array( 'title' => $title, 'desc' => $desc )
		);
		?>

		<form id="ms-comm-type-form" action="" method="post">
			<?php
			MS_Helper_Html::html_element( $fields['load_action'] );
			MS_Helper_Html::html_element( $fields['load_nonce'] );
			MS_Helper_Html::html_element( $fields['comm_type'] );
			MS_Helper_Html::html_element( $fields['switch_comm_type'] );
			?>
		</form>

		<?php
		MS_Helper_Html::html_separator();
		if ( ! empty( $fields['override'] ) ) {
			MS_Helper_Html::html_element( $fields['override'] );
		}
		?>

		<form action="" method="post" class="ms-editor-form">
			<?php
			if ( ! empty( $fields['membership_id'] ) ) {
				MS_Helper_Html::html_separator();
				MS_Helper_Html::html_element( $fields['membership_id'] );
			}
			MS_Helper_Html::html_element( $fields['action'] );
			MS_Helper_Html::html_element( $fields['nonce'] );
			MS_Helper_Html::html_element( $fields['type'] );

			if ( is_a( $comm, 'MS_Model_Communication' ) ) {
				printf(
					'<h3>%1$s %2$s: %3$s</h3><div class="ms-description" style="margin-bottom:20px;">%4$s</div>',
					$comm->get_title(),
					__( 'Message', 'membership2' ),
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
	 * @since  1.0.0
	 */
	public function wp_footer() {
		$comm = $this->data['comm'];
		$vars = $comm->comm_vars;
		$vars = lib3()->array->get( $vars );

		/**
		 * Print JS details for the custom TinyMCE "Insert Variable" button
		 *
		 * @see class-ms-controller-settings.php (function add_mce_buttons)
		 * @see ms-view-settings-automated-msg.js
		 */
		$var_button = array(
			'title' => __( 'Insert Membership Variables', 'membership2' ),
			'items' => $vars,
		);

		printf(
			'<script>window.ms_data.var_button = %1$s;window.ms_data.lang_confirm = %2$s</script>',
			json_encode( $var_button ),
			json_encode(
				__( 'You have made changes that are not saved yet. Do you want to discard those changes?', 'membership2' )
			)
		);
	}

	/**
	 * Prepare the fields that are displayed in the form.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	protected function get_fields() {
		$comm = $this->data['comm'];
		$membership = false;
		$membership_id = 0;

		if ( isset( $this->data['membership'] ) ) {
			$membership = $this->data['membership'];

			if ( $membership instanceof MS_Model_Membership ) {
				$membership_id = $membership->id;
			} else {
				$membership = false;
			}
		}

                if ( version_compare( PHP_VERSION, '5.3' ) >= 0 ) {
                    lib3()->array->equip(
                            $comm,
                            'type',
                            'enabled',
                            'period',
                            'subject',
                            'description',
                            'cc_enabled',
                            'cc_email'
                    );
                }

		$action = MS_Controller_Communication::AJAX_ACTION_UPDATE_COMM;
		$nonce = wp_create_nonce( $action );
		$comm_titles = MS_Model_Communication::get_communication_type_titles(
			$membership
		);

		$key_active = __( 'Send Email', 'membership2' );
		$key_inactive = __( 'No Email', 'membership2' );
		$key_skip = __( 'Use default template', 'membership2' );
		$titles = array(
			$key_active => array(),
			$key_inactive => array(),
			$key_skip => array(),
		);
		foreach ( $comm_titles as $type => $title ) {
			$tmp_comm = MS_Model_Communication::get_communication(
				$type,
				$membership,
				true
			);

			if ( $membership && ! $tmp_comm->override ) {
				$titles[$key_skip][$type] = $title;
			} elseif ( $tmp_comm->enabled ) {
				$titles[$key_active][$type] = $title;
			} else {
				$titles[$key_inactive][$type] = $title;
			}
		}

		$fields = array(
			'comm_type' => array(
				'id' => 'comm_type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $comm->type,
				'field_options' => $titles,
			),

			'switch_comm_type' => array(
				'id' => 'switch_comm_type',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Load Template', 'membership2' ),
			),

			'override' => array(
				'id' => 'override',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $comm->override,
				'before' => __( 'Use default template', 'membership2' ),
				'after' => __( 'Define custom template', 'membership2' ),
				'wrapper_class' => 'ms-block ms-tcenter',
				'class' => 'override-slider',
				'ajax_data' => array(
					'type' => $comm->type,
					'field' => 'override',
					'action' => $action,
					'_wpnonce' => $nonce,
					'membership_id' => $membership_id,
				),
			),

			'membership_id' => array(
				'id' => 'membership_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership_id,
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
				'class' => 'state-slider',
				'before' => '&nbsp;<i class="wpmui-fa wpmui-fa-ban"></i>',
				'after' => '<i class="wpmui-fa wpmui-fa-envelope"></i>&nbsp;',
				'ajax_data' => array(
					'type' => $comm->type,
					'field' => 'enabled',
					'action' => $action,
					'_wpnonce' => $nonce,
					'membership_id' => $membership_id,
				),
			),

			// Note: title/desc is overwritten by MS_Model_Communication (below)
			'period_unit' => array(
				'id' => 'period_unit',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Period after/before', 'membership2' ),
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
				'title' => apply_filters(
					'ms_translation_flag',
					__( 'Message Subject', 'membership2' ),
					'communication-subject'
				),
				'value' => $comm->subject,
				'class' => 'ms-comm-subject widefat',
			),

			'email_body' => array(
				'id' => 'email_body',
				'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
				'title' => apply_filters(
					'ms_translation_flag',
					'',
					'communication-body'
				),
				'value' => $comm->description,
				'field_options' => array(
					'media_buttons' => false,
					'editor_class' => 'wpmui-ajax-update',
				),
			),

			'cc_enabled' => array(
				'id' => 'cc_enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Send copy to Administrator', 'membership2' ),
				'value' => $comm->cc_enabled,
				'class' => 'ms-inline-block',
			),

			'cc_email' => array(
				'id' => 'cc_email',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $comm->cc_email,
				'field_options' => MS_Model_Member::get_admin_user_emails(),
			),

			'save_email' => array(
				'id' => 'save_email',
				'value' => __( 'Save Changes', 'membership2' ),
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

		if ( ! ( $membership instanceof MS_Model_Membership ) ) {
			unset( $fields['override'] );
			unset( $fields['membership_id'] );
		}

		return apply_filters(
			'ms_view_settings_prepare_email_fields',
			$fields
		);
	}
}