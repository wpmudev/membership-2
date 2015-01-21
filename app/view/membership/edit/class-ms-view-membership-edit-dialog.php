<?php

/**
 * Dialog: Edit Membership
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.1.0
 * @package Membership
 * @subpackage View
 */
class MS_View_Membership_Edit_Dialog extends MS_Dialog {

	const ACTION_SAVE = 'save_membership';

	/**
	 * Generate/Prepare the dialog attributes.
	 *
	 * @since 1.1.0
	 */
	public function prepare() {
		$membership_id = $_POST['membership_id'];
		$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );

		$data = array(
			'model' => $membership,
			'action' => 'edit',
		);

		$data = apply_filters( 'ms_view_membership_edit_data', $data );

		// Dialog Title
		$this->title = sprintf(
			__( 'Edit %s', MS_TEXT_DOMAIN ),
			esc_html( $membership->name )
		);

		// Dialog Size
		if ( $membership->is_guest() ) {
			$this->height = 200;
		} else {
			$this->height = 390;
		}

		// Contents
		$this->content = $this->get_contents( $data );

		// Make the dialog modal
		$this->modal = true;
	}

	/**
	 * Save the gateway details.
	 *
	 * @since  1.1.0
	 * @return string
	 */
	public function submit() {
		$data = $_POST;
		$res = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;

		unset( $data['action'] );
		unset( $data['dialog'] );

		// Update the memberships
		if ( isset( $_POST['dialog_action'] )
			&& $this->verify_nonce( $_POST['dialog_action'] )
			&& isset( $_POST['ms_id'] )
		) {
			$id = $_POST['ms_id'];
			$membership = MS_Factory::load( 'MS_Model_Membership', $id );

			if ( isset( $_POST['ms_name'] ) ) {
				$membership->name = $_POST['ms_name'];
			}
			if ( isset( $_POST['ms_description'] ) ) {
				$membership->description = $_POST['ms_description'];
			}
			if ( isset( $_POST['ms_active'] ) ) {
				$membership->active = WDev()->is_true( $_POST['ms_active'] );
			}
			if ( isset( $_POST['ms_public'] ) ) {
				$membership->private = ! WDev()->is_true( $_POST['ms_public'] );
			}
			if ( isset( $_POST['ms_paid'] ) ) {
				$membership->is_free = ! WDev()->is_true( $_POST['ms_paid'] );
			}

			$membership->save();
			$res = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		return $res;
	}

	/**
	 * Returns the contens of the dialog
	 *
	 * @since 1.1.0
	 *
	 * @return object
	 */
	public function get_contents( $data ) {
		$membership = $data['model'];

		// Prepare the form fields.
		$inp_name = array(
			'name' => 'ms_name',
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			'title' => __( 'Name:', MS_TEXT_DOMAIN ),
			'value' => $membership->name,
		);

		$inp_description = array(
			'name' => 'ms_description',
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
			'title' => __( 'Description:', MS_TEXT_DOMAIN ),
			'id' => 'description',
			'value' => $membership->description,
		);

		$inp_active = array(
			'name' => 'ms_active',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title' => __( 'This membership is active', MS_TEXT_DOMAIN ),
			'before' => __( 'No', MS_TEXT_DOMAIN ),
			'after' => __( 'Yes', MS_TEXT_DOMAIN ),
			'class' => 'ms-active',
			'value' => $membership->active,
		);

		$inp_public = array(
			'name' => 'ms_public',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title' => __( 'This membership is public', MS_TEXT_DOMAIN ),
			'desc' => __( 'Users can see it listed on your site and can register for it', MS_TEXT_DOMAIN ),
			'before' => __( 'No', MS_TEXT_DOMAIN ),
			'after' => __( 'Yes', MS_TEXT_DOMAIN ),
			'class' => 'ms-public',
			'value' => ! $membership->private,
		);

		$inp_paid = array(
			'name' => 'ms_paid',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title' => __( 'This is a paid membership', MS_TEXT_DOMAIN ),
			'before' => __( 'No', MS_TEXT_DOMAIN ),
			'after' => __( 'Yes', MS_TEXT_DOMAIN ),
			'class' => 'ms-paid',
			'value' => ! $membership->is_free,
		);

		$inp_dialog = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'dialog',
			'value' => 'View_Membership_Edit_Dialog',
		);

		$inp_id = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'ms_id',
			'value' => $membership->id,
		);

		$inp_nonce = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => '_wpnonce',
			'value' => wp_create_nonce( self::ACTION_SAVE ),
		);

		$inp_action = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'dialog_action',
			'value' => self::ACTION_SAVE,
		);

		$inp_save = array(
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Save', MS_TEXT_DOMAIN ),
			'class' => 'ms-submit-form',
			'data' => array(
				'form' => 'ms-edit-membership',
			)
		);

		$inp_cancel = array(
			'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
			'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
			'class' => 'close',
		);

		ob_start();
		?>
		<div>
			<form class="ms-form wpmui-ajax-update ms-edit-membership" data-ajax="<?php echo esc_attr( 'save' ); ?>">
				<div class="ms-form wpmui-form wpmui-grid-8">
					<div class="col-5">
						<?php
						MS_Helper_Html::html_element( $inp_name );
						if ( ! $membership->is_guest() ) {
							MS_Helper_Html::html_element( $inp_description );
						}
						?>
					</div>
					<div class="col-3">
						<?php
						MS_Helper_Html::html_element( $inp_active );
						if ( ! $membership->is_guest() ) {
							MS_Helper_Html::html_element( $inp_public );
							MS_Helper_Html::html_element( $inp_paid );
						}
						?>
					</div>
				</div>
				<?php
				MS_Helper_Html::html_element( $inp_id );
				MS_Helper_Html::html_element( $inp_dialog );
				MS_Helper_Html::html_element( $inp_nonce );
				MS_Helper_Html::html_element( $inp_action );
				?>
			</form>
			<div class="buttons">
				<?php
				MS_Helper_Html::html_element( $inp_cancel );
				MS_Helper_Html::html_element( $inp_save );
				?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		return apply_filters( 'ms_view_membership_edit_to_html', $html );
	}

};