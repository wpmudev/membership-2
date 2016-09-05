<?php

/**
 * Tab: Edit Membership Details
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage View
 */
class MS_View_Membership_Tab_Details extends MS_View {

	/**
	 * Returns the contens of the dialog
	 *
	 * @since  1.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		$field = $this->get_fields();
		$membership = $this->data['membership'];

		ob_start();
		?>
		<div>
			<form class="ms-form wpmui-ajax-update ms-edit-membership" data-wpmui-ajax="<?php echo esc_attr( 'save' ); ?>">
				<div class="ms-form wpmui-form wpmui-grid-8">
					<div class="col-5">
						<?php
						MS_Helper_Html::html_element( $field['name'] );
						if ( ! $membership->is_system() ) {
							MS_Helper_Html::html_element( $field['description'] );
						}
						?>
					</div>
					<div class="col-3">
						<?php
						MS_Helper_Html::html_element( $field['active'] );
						if ( ! $membership->is_system() ) {
							MS_Helper_Html::html_element( $field['public'] );
							MS_Helper_Html::html_element( $field['paid'] );
						}
						?>
					</div>
				</div>
				<div class="ms-form wpmui-form wpmui-grid-8">
					<div class="col-8">
					<?php
					if ( ! $membership->is_system() ) {
						MS_Helper_Html::html_element( $field['priority'] );
					}
					?>
					</div>
				</div>
			</form>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_membership_edit_to_html', $html, $field, $membership );
	}

	/**
	 * Prepares fields for the edit form.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	protected function get_fields() {
		$membership = $this->data['membership'];
		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce = wp_create_nonce( $action );

		$fields = array();

		// Prepare the form fields.
		$fields['name'] = array(
			'id' => 'name',
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			'title' => apply_filters(
				'ms_translation_flag',
				__( 'Name:', 'membership2' ),
				'membership-name'
			),
			'value' => $membership->name,
			'ajax_data' => array( 1 ),
		);

		$fields['description'] = array(
			'id' => 'description',
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
			'title' => apply_filters(
				'ms_translation_flag',
				__( 'Description:', 'membership2' ),
				'membership-name'
			),
			'value' => $membership->description,
			'ajax_data' => array( 1 ),
		);

		$fields['active'] = array(
			'id' => 'active',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title' => __( 'This membership is active', 'membership2' ),
			'before' => __( 'No', 'membership2' ),
			'after' => __( 'Yes', 'membership2' ),
			'class' => 'ms-active',
			'value' => $membership->active,
			'ajax_data' => array( 1 ),
		);

		$fields['public'] = array(
			'id' => 'public',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title' => __( 'This membership is public', 'membership2' ),
			'desc' => __( 'Users can see it listed on your site and can register for it', 'membership2' ),
			'before' => __( 'No', 'membership2' ),
			'after' => __( 'Yes', 'membership2' ),
			'class' => 'ms-public',
			'value' => $membership->public,
			'ajax_data' => array( 1 ),
		);

		$fields['paid'] = array(
			'id' => 'is_paid',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title' => __( 'This is a paid membership', 'membership2' ),
			'before' => __( 'No', 'membership2' ),
			'after' => __( 'Yes', 'membership2' ),
			'class' => 'ms-paid',
			'value' => $membership->is_paid,
			'ajax_data' => array( 1 ),
		);
                
		$priority_list = array();
		$args = array( 'include_guest' => 0 );
		$count = MS_Model_Membership::get_membership_count( $args );
		for ( $i = 1; $i <= $count; $i += 1 ) {
			$priority_list[$i] = $i;
		}
		$priority_list[$membership->priority] = $membership->priority;

		$fields['priority'] = array(
			'id' => 'priority',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'title' => __( 'Membership order', 'membership2' ),
			'desc' => __( 'This defines the display order on the Membership Page.', 'membership2' ),
			'class' => 'ms-priority',
			'before' => __( 'Order', 'membership2' ),
			'value' => $membership->priority,
			'field_options' => $priority_list,
			'ajax_data' => array( 1 ),
		);

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
			$fields['priority']['desc'] .= '<br>' .
				__( 'It also controlls which Protection Message is used in case a member has multiple memberships (the lowest value wins)', 'membership2' );
		}
                
                $fields = apply_filters(
                            'ms_view_membership_details_tab',
                            $fields,
                            $membership,
                            $this->data
                        );

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
                
		return $fields;
	}

};