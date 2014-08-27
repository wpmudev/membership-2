<?php

class MS_View_Membership_Choose_Type extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		?>
			<div class='ms-wrap'>
				<?php 
					MS_Helper_Html::settings_header( array(
						'title' => __( 'Choose a membership type suitable to your project', MS_TEXT_DOMAIN ),			
					) ); 
				?>
				<div class='ms-settings'>
					<form action="" method="post">
						<div class="ms-type-wrapper">
							<?php MS_Helper_Html::html_input( $this->fields['type'] ); ?>
						</div>
						<div class="clear"><hr /></div>
						<div class="ms-name-wrapper">
							<?php MS_Helper_Html::html_input( $this->fields['name'] ); ?>
						</div>
						<div class="ms-private-wrapper">
							<?php MS_Helper_Html::html_input( $this->fields['private'] ); ?>
						</div>
						<?php
							foreach( $this->fields['control_fields'] as $field ) {
								MS_Helper_Html::html_input( $field );		
							} 
						?>
					</form>
				</div>
			</div>
		<?php 	
	}
	
	public function prepare_fields() {
		$membership = $this->data['membership'];
		
		$this->fields = array(
			'type' => array(
					'id' => 'type',
					'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
					'title' => __( 'What kind of membership do you want to set up?', MS_TEXT_DOMAIN ),
					'desc' => __( 'What kind of membership do you want to set up?', MS_TEXT_DOMAIN ),
					'value' => ! empty( $membership->type ) ? $membership->type : MS_Model_Membership::TYPE_SIMPLE,
					'field_options' => $this->data['type'],
			),
			'name' => array(
					'id' => 'name',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' => __( 'Choose a name for you membership', MS_TEXT_DOMAIN ),
					'value' => $membership->name,
					'class' => 'ms-field-input-name',
			),
			'private' => array(
					'id' => 'private',
					'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
					'title' => __( 'Make this membership private (No registration, no payment)', MS_TEXT_DOMAIN ),
					'desc' => __( 'Choosing this option assumes that you will manually set up users who can access your content.<br/><b>No registration page will be created and there will be no payment options.</b>', MS_TEXT_DOMAIN ),
					'value' => $membership->private,
					'class' => 'ms-field-input-trial-period-enabled',
			),
			'control_fields' => array( 
					'membership_id' => array(
							'id' => 'membership_id',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => $membership->id,
					),
					'step' => array(
							'id' => 'step',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => $this->data['step'],
					),
					'action' => array(
							'id' => 'action',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => $this->data['action'],
					),
					'_wpnonce' => array(
							'id' => '_wpnonce',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => wp_create_nonce( $this->data['action'] ),
					),
					'save' => array(
							'id' => 'save',
							'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
							'value' => __( 'Save and continue', MS_TEXT_DOMAIN ),
					),
			),
		);

	}
}
