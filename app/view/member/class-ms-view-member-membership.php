<?php

class MS_View_Member_Membership extends MS_View {
	
	const MEMBERSHIP_SECTION = 'membership_section';
	const MEMBERSHIP_NONCE = 'membership_nonce';
	
	protected $member_id;
	
	protected $action;
	
	protected $memberships;
	
	protected $fields;
	
	public function to_html() {
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<h2>Add Membership</h2>
				<form action="<?php echo remove_query_arg( array( 'action', 'member_id' ) ); ?>" method="post">
					<?php wp_nonce_field( self::MEMBERSHIP_NONCE, self::MEMBERSHIP_NONCE ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['member_id'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['action'] ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<td>
									<?php MS_Helper_Html::html_input( $this->fields['membership_list'] ); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php MS_Helper_Html::html_link( $this->fields['cancel'] ); ?>
									<?php MS_Helper_Html::html_submit( $this->fields['submit'] ); ?>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	function prepare_fields() {
		$this->fields = array(
			'membership_list' => array(
				'id' => 'membership_id',
				'section' => self::MEMBERSHIP_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Membership', MS_TEXT_DOMAIN ),
				'value' => 0,
				'field_options' => $this->memberships,
				'class' => '',
			),
			'member_id' => array(
				'id' => 'member_id',
				'section' => self::MEMBERSHIP_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->member_id,
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
				'value' => ( $this->action == 'add' ) ? __('Add', MS_TEXT_DOMAIN ) : __('Drop', MS_TEXT_DOMAIN ),
				'type' => 'submit',
			),
			'action' => array(
				'id' => 'action',
				'section' => self::MEMBERSHIP_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->action,
			),
		);		
	}
}