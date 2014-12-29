<?php

class MS_View_Member_Membership extends MS_View {

	/**
	 * Creates the view output:
	 * Form to edit memberships of a certain user.
	 *
	 * @since  1.0.0
	 *
	 * @return string HTML code
	 */
	public function to_html() {
		$fields = $this->prepare_fields();
		$action = $this->data['action']; // add, cancel, drop, move

		$edit_url = remove_query_arg( array( 'action', 'member_id' ) );

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap">
			<div class="ms-settings">
				<h2 class="ms-settings-title">
					<i class="wpmui-fa wpmui-fa-pencil-square"></i>
					<?php echo esc_html( $this->data['title'] ); ?>
				</h2>

				<form action="<?php echo $edit_url; ?>" method="post">
					<?php
					wp_nonce_field( $action );
					MS_Helper_Html::html_element( $fields['member_id'] );
					MS_Helper_Html::html_element( $fields['action'] );
					MS_Helper_Html::settings_box_header(
						sprintf(
							__( 'Membership to %s', MS_TEXT_DOMAIN ),
							$this->data['title']
						),
						'',
						array( 'label_element' => 'h3' )
					);
					?>
					<table class="form-table">
						<tbody>
							<tr>
								<td>
									<?php
									if ( ! empty( $this->data['memberships_move'] ) ) {
										MS_Helper_Html::html_element( $fields['membership_move'] );
									}
									MS_Helper_Html::html_element( $fields['membership_list'] );
									?>
								</td>
							</tr>
							<tr>
								<td>
									<?php
									MS_Helper_Html::html_separator();
									MS_Helper_Html::html_element( $fields['cancel'] );
									MS_Helper_Html::html_element( $fields['submit'] );
									?>
								</td>
							</tr>
						</tbody>
					</table>
					<?php MS_Helper_Html::settings_box_footer(); ?>
				</form>
				<div class="clear"></div>
			</div>
		</div>
		<?php
		return apply_filters(
			'ms_view_member_membership_' . $action . '_to_html',
			ob_get_clean(),
			$this
		);
	}

	/**
	 * Return HTML field definitions.
	 *
	 * @since  1.0.0
	 *
	 * @return array Field definitions.
	 */
	private function prepare_fields() {
		$action_title = array(
			'add' => __( 'Add Membership', MS_TEXT_DOMAIN ),
			'cancel' => __( 'Cancel Membership', MS_TEXT_DOMAIN ),
			'drop' => __( 'Drop Membership', MS_TEXT_DOMAIN ),
			'move' => __( 'Move Membership', MS_TEXT_DOMAIN ),
		);

		$this->data['title'] = $action_title[ $this->data['action'] ];

		$fields = array(
			'membership_list' => array(
				'id' => 'membership_id',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => 0,
				'field_options' => $this->data['memberships'],
			),

			'member_id' => array(
				'id' => 'member_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => implode( ',', $this->data['member_id'] ),
			),

			'separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'cancel' => array(
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'id' => 'cancel',
				'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
				'url' => remove_query_arg( array( 'action', 'member_id' ) ),
				'class' => 'button',
			),

			'submit' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'id' => 'submit',
				'value' => __( 'OK', MS_TEXT_DOMAIN ),
				'type' => 'submit',
			),

			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['action'],
			),
		);

		if ( is_array( @$this->data['memberships_move'] ) ) {
			$move_val = 0;
			if ( count( $this->data['memberships_move'] ) == 2 ) {
				$move_val = end( $this->data['memberships_move'] );
			}

			$fields['membership_move'] = array(
				'id' => 'membership_move_from_id',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $move_val,
				'field_options' => $this->data['memberships_move'],
			);
		}

		return $fields;
	}
}