<?php

class MS_View_Member_Membership extends MS_View {

	protected $data;

	protected $fields;

	public function to_html() {
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
		<div class='ms-wrap'>
			<div class='ms-settings'>
				<h2 class="ms-settings-title">
					<i class="ms-fa ms-fa-pencil-square"></i>
					<?php echo $this->data['title'] . __( ' Membership', MS_TEXT_DOMAIN ); ?>
				</h2>
				<form action="<?php echo remove_query_arg( array( 'action', 'member_id' ) ); ?>" method="post">
					<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
					<?php MS_Helper_Html::html_element( $this->fields['member_id'] ); ?>
					<?php MS_Helper_Html::html_element( $this->fields['action'] ); ?>
					<?php
						MS_Helper_Html::settings_box_header(
							sprintf( __( 'Membership to %s', MS_TEXT_DOMAIN ), $this->data['title'] ),
							'',
							array( 'label_element' => 'h3' )
						);
					?>
					<table class="form-table">
						<tbody>
							<tr>
								<td>
									<?php
										if( ! empty( $this->data['memberships_move'] ) ) {
											MS_Helper_Html::html_element( $this->fields['membership_move'] );
										}
										MS_Helper_Html::html_element( $this->fields['membership_list'] );
									?>
								</td>
							</tr>
							<tr>
								<td>
									<?php MS_Helper_Html::html_separator(); ?>
									<?php MS_Helper_Html::html_link( $this->fields['cancel'] ); ?>
									<?php MS_Helper_Html::html_submit( $this->fields['submit'] ); ?>
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
		$html = ob_get_clean();
		echo $html;
	}

	function prepare_fields() {
		$action_label = array(
				'add' => __( 'Add', MS_TEXT_DOMAIN ),
				'cancel' => __( 'Cancel', MS_TEXT_DOMAIN ),
				'drop' => __( 'Drop', MS_TEXT_DOMAIN ),
				'move' => __( 'Move', MS_TEXT_DOMAIN ),
			);
		$this->data['title'] = $action_label[ $this->data['action'] ];
		$this->fields = array(
			'membership_list' => array(
				'id' => 'membership_id',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => 0,
				'field_options' => $this->data['memberships'],
				'class' => '',
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
				'id' => 'cancel',
				'title' => __('Cancel', MS_TEXT_DOMAIN ),
				'value' => __('Cancel', MS_TEXT_DOMAIN ),
				'url' => remove_query_arg( array( 'action', 'member_id' ) ),
				'class' => 'button',
			),
			'submit' => array(
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
		if( ! empty( $this->data['memberships_move'] ) && is_array( $this->data['memberships_move'] ) ) {
			$this->fields['membership_move'] = array(
					'id' => 'membership_move_from_id',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' =>  count( $this->data['memberships_move'] ) == 2 ? end( $this->data['memberships_move'] ) : 0,
					'field_options' => $this->data['memberships_move'],
					'class' => '',
			);
		}

	}
}