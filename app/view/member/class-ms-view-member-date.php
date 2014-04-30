<?php

class MS_View_Member_Date extends MS_View {
	
	const MEMBERSHIP_SECTION = 'membership_section';
	const MEMBERSHIP_NONCE = 'membership_nonce';
	
	protected $member_id;
		
	protected $membership_ids;
	
	protected $membership_relationships;
		
	protected $fields;
	
	protected $action;
	
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
					<?php 
						foreach ( $this->fields['membership_id'] as $field ){
							MS_Helper_Html::html_input( $field );	
						} 
					?>
					<?php MS_Helper_Html::html_input( $this->fields['action'] ); ?>
					<table class="form-table">
						<tbody>
							<?php foreach( $this->fields['memberships'] as $membership_id => $field ): ?>
								<tr>
									<td>
										<?php MS_Helper_Html::html_input( $field ); ?>
										<span><?php _e( 'Start date', MS_TEXT_DOMAIN ); ?></span>
										<?php MS_Helper_Html::html_input( $this->fields['dates'][$membership_id]['start_date'] ); ?>
										<?php if($this->fields['dates'][$membership_id]['expire_date']['value']): ?>
											<span><?php _e( 'Expire date', MS_TEXT_DOMAIN ); ?></span>
											<?php MS_Helper_Html::html_input( $this->fields['dates'][$membership_id]['expire_date'] ); ?>
										<?php endif;?>
									</td>
								</tr>
								<?php endforeach; ?>
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
				'value' => __( 'Change Date', MS_TEXT_DOMAIN ),
				'type' => 'submit',
			),
			'action' => array(
				'id' => 'action',
				'section' => self::MEMBERSHIP_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->action,
			),
		);
		
		foreach( $this->membership_relationships as $membership_relationship ) {
			$membership_id = $membership_relationship->membership_id;
			$this->fields['membership_id'][] = array(
					'id' => "membership_id_$membership_id",
					'name' => self::MEMBERSHIP_SECTION . "[membership_id][]",
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $membership_id,
			);
			$this->fields['memberships'][$membership_id] = array(
				'id' => "membership_id_$membership_id",
				'section' => self::MEMBERSHIP_SECTION,
				'title' => __( 'Membership', MS_TEXT_DOMAIN ) . ': '. $membership_relationship->get_membership()->name,
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => '',
			);
			$this->fields['dates'][$membership_id]['start_date'] = array(
				'id' => "start_date_$membership_id",
				'section' => self::MEMBERSHIP_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
// 				'title' => __( 'Start date', MS_TEXT_DOMAIN ),
				'value' => $membership_relationship->start_date,
				'class' => 'ms-date',
			);
			$this->fields['dates'][$membership_id]['expire_date'] = array(
				'id' => "expire_date_$membership_id",
				'section' => self::MEMBERSHIP_SECTION,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
// 				'title' => __( 'Expire date', MS_TEXT_DOMAIN ),
				'value' => $membership_relationship->expire_date,
				'class' => 'ms-date',
			);
		}		
	}
}