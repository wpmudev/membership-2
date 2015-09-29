<?php

/**
 * Render Invitaiton add/edit view.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage View
 */
class MS_Addon_Invitation_View_Edit extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();
		$form_url = esc_url_raw(
			remove_query_arg( array( 'action', 'invitation_id' ) )
		);

		if ( $this->data['invitation']->is_valid() ) {
			$title = __( 'Edit Invitation Code', 'membership2' );
		} else {
			$title = __( 'Add Invitation Code', 'membership2' );
		}

		ob_start();
		?>
		<div class="ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => $title,
					'title_icon_class' => 'wpmui-fa wpmui-fa-pencil-square',
				)
			);
			?>
			<form action="<?php echo esc_url( $form_url ); ?>" method="post" class="ms-form">
				<?php MS_Helper_Html::settings_box( $fields, '', '', 'static', 'ms-small-form' ); ?>
			</form>
			<div class="clear"></div>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_addon_invitation_view_edit_to_html',
			$html,
			$this
		);
	}

	/**
	 * Prepare html fields.
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	function prepare_fields() {
		$invitation = $this->data['invitation'];
		if ( ! $invitation->code ) {
			$invitation->code = substr( md5( time() ), 0, 20 );
		}

		$fields = array(
			'code' => array(
				'id' => 'code',
				'title' => __( 'Invitation code', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $invitation->code,
				'class' => 'widefat',
			),
			'start_date' => array(
				'id' => 'start_date',
				'title' => __( 'Start date', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'value' => ( $invitation->start_date ) ? $invitation->start_date : MS_Helper_Period::current_date(),
				'class' => 'ms-date',
			),
			'expire_date' => array(
				'id' => 'expire_date',
				'title' => __( 'Expire date', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'value' => $invitation->expire_date,
				'class' => 'ms-date',
			),
			'membership_id' => array(
				'id' => 'membership_id',
				'title' => __( 'Invitation can be applied to these Memberships', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'field_options' => $this->data['memberships'],
				'value' => $invitation->membership_id,
			),
			'max_uses' => array(
				'id' => 'max_uses',
				'title' => __( 'Max uses', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_NUMBER,
				'value' => $invitation->max_uses,
				'config' => array(
					'step' => '1',
					'min' => 0,
				),
			),
			'invitation_id' => array(
				'id' => 'invitation_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $invitation->id,
			),
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $this->data['action'] ),
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['action'],
			),
			'separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'cancel' => array(
				'id' => 'cancel',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'title' => __( 'Cancel', 'membership2' ),
				'value' => __( 'Cancel', 'membership2' ),
				'url' => remove_query_arg( array( 'action', 'invitation_id' ) ),
				'class' => 'wpmui-field-button button',
			),
			'submit' => array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save Changes', 'membership2' ),
			),
		);

		return apply_filters(
			'ms_addon_invitation_view_edit_prepare_fields',
			$fields,
			$this
		);
	}

}