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
							<h3><?php _e( 'What kind of membership do you want to set up?', MS_TEXT_DOMAIN ); ?></h3>
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
					'value' => ! empty( $membership->type ) ? $membership->type : MS_Model_Membership::TYPE_SIMPLE,
					'class' => 'ms-chose-type',
					'field_options' => array(
							MS_Model_Membership::TYPE_SIMPLE => array(
									'text' => __( 'I simply want to protect some of my content', MS_TEXT_DOMAIN ),
									'desc' => __( "This is the most basic membership that creates as single membership level. Members will have access to all protected content. <div class='ms-eg'>eg. Visitors don't see protected content, members access all protected content.</div>", MS_TEXT_DOMAIN ),
							),
							MS_Model_Membership::TYPE_CONTENT_TYPE => array(
									'text' => __( 'I want to have different content available to different members', MS_TEXT_DOMAIN ),
									'desc' => __( "This option is for when you have different types of content that you want to make available to different type of members. <div class='ms-eg'>eg. Music members get access to Guitar Courses, Cooking members get access to Recipes. </div>", MS_TEXT_DOMAIN ),
							),
							MS_Model_Membership::TYPE_TIER => array(
									'text' => __( 'I want to set up a Tier Level-based membership', MS_TEXT_DOMAIN ),
									'desc' => __( "This options allows you to set up different tier level membership. <div class='ms-eg'>eg. Silver >> Gold >> Platinum. The higher the level, the more content members will have access to.</div>", MS_TEXT_DOMAIN ),
							),
							MS_Model_Membership::TYPE_DRIPPED => array(
									'text' => __( 'I want to set up a Dripped Content membership', MS_TEXT_DOMAIN ),
									'desc' => __( "This option will allow you to set up a membership where content will be revelead to users over a period of time. <div class='ms-eg'>eg. A weekly training / excercize program. </div>", MS_TEXT_DOMAIN ),
							),
					),
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
