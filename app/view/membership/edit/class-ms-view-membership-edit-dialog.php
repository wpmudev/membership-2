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
			'children' => $membership->get_children(),
			'action' => 'edit',
		);

		$data = apply_filters( 'ms_view_membership_edit_data', $data );

		// Dialog Title
		$this->title = __( 'Edit membership', MS_TEXT_DOMAIN );

		// Dialog Size
		$this->height = 500;

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
			&& isset( $_POST['ms_ids'] )
		) {
			$ids = explode( ',', $_POST['ms_ids'] );
			foreach ( $ids as $id ) {
				$membership = MS_Factory::load( 'MS_Model_Membership', $id );

				if ( isset( $_POST['ms_name']['n' . $id] ) ) {
					$membership->name = $_POST['ms_name']['n' . $id];
				}
				if ( isset( $_POST['ms_description']['d' . $id] ) ) {
					$membership->description = $_POST['ms_description']['d' . $id];
				}
				if ( isset( $_POST['ms_active']['a' . $id] ) ) {
					$membership->active = WDev()->is_true( $_POST['ms_active']['a' . $id] );
				}
				if ( isset( $_POST['ms_private']['p' . $id] ) ) {
					$membership->private = ! WDev()->is_true( $_POST['ms_private']['p' . $id] );
				}

				$membership->save();
			}
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
		$children = WDev()->get_array( $data['children'] );

		$items = array_merge( array( $membership ), $children );

		// Prepare the form fields.
		$inp_name = array(
			'section' => 'ms_name',
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			'title' => __( 'Name', MS_TEXT_DOMAIN ),
			'class' => 'col-7',
		);
		$inp_description = array(
			'section' => 'ms_description',
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
			'title' => __( 'Description', MS_TEXT_DOMAIN ),
			'class' => 'col-7',
		);
		$inp_active = array(
			'section' => 'ms_active',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title' => __( 'This membership is active', MS_TEXT_DOMAIN ),
			'before' => __( 'No', MS_TEXT_DOMAIN ),
			'after' => __( 'Yes', MS_TEXT_DOMAIN ),
		);
		$inp_private = array(
			'section' => 'ms_private',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title' => __( 'Show this membership in the Membership list', MS_TEXT_DOMAIN ),
			'before' => __( 'No', MS_TEXT_DOMAIN ),
			'after' => __( 'Yes', MS_TEXT_DOMAIN ),
		);

		$inp_dialog = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'dialog',
			'value' => 'View_Membership_Edit_Dialog',
		);

		$inp_idlist = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'ms_ids',
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
			<div class="vnav-editor">
				<?php
				$ids = array();
				foreach ( $items as $item ) {
					$inp_name['id'] = 'n' . $item->id;
					$inp_name['value'] = $item->name;

					$inp_description['id'] = 'd' . $item->id;
					$inp_description['value'] = $item->description;

					$inp_active['id'] = 'a' . $item->id;
					$inp_active['value'] = $item->active;

					$inp_private['id'] = 'p' . $item->id;
					$inp_private['value'] = ! $item->private;

					$ids[] = $item->id;
					?>
					<h3><?php echo esc_html( $item->name ); ?></h3>
					<div class="ms-form">
						<?php
						MS_Helper_Html::html_element( $inp_name );
						MS_Helper_Html::html_element( $inp_active );

						if ( ! $item->can_have_children() ) {
							MS_Helper_Html::html_element( $inp_private );
							MS_Helper_Html::html_separator();
							MS_Helper_Html::html_element( $inp_description );
						}
						?>
					</div>
				<?php
				}
				$inp_idlist['value'] = implode( ',', $ids );
				MS_Helper_Html::html_element( $inp_idlist );
				MS_Helper_Html::html_element( $inp_dialog );
				MS_Helper_Html::html_element( $inp_nonce );
				MS_Helper_Html::html_element( $inp_action );
				?>
			</div>
			<div class="buttons">
				<?php
				MS_Helper_Html::html_element( $inp_cancel );
				MS_Helper_Html::html_element( $inp_save );
				?>
			</div>
			<script>
			jQuery('.vnav-editor').wpmui_vnav();
			</script>
			</form>
		</div>
		<?php
		$html = ob_get_clean();
		return apply_filters( 'ms_view_membership_edit_to_html', $html );
	}

};