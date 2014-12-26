<?php

class MS_View_Membership_Choose_Type extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		?>
			<div class='ms-wrap'>
				<?php
					MS_Helper_Html::settings_header(
						array(
							'title' => __( 'Create your membership', MS_TEXT_DOMAIN ),
							'desc' => __( 'First up choose a name and a type for your membership site.', MS_TEXT_DOMAIN ),
						)
					);
				?>
				<form action="" method="post" id="ms-choose-type-form">
					<div class="ms-settings ms-settings-type">
						<div class="ms-group">
							<div class="ms-name-wrapper">
								<?php MS_Helper_Html::html_element( $fields['name'] ); ?>
							</div>
							<div class="ms-private-wrapper">
								<?php MS_Helper_Html::html_element( $fields['private'] ); ?>
							</div>
						</div>

						<?php MS_Helper_Html::html_separator(); ?>
						<div class="ms-type-wrapper">
							<h3><?php _e( 'Choose a membership type:', MS_TEXT_DOMAIN ); ?></h3>
							<?php MS_Helper_Html::html_element( $fields['type'] ); ?>
						</div>

						<div class="ms-control-fields-wrapper">
							<?php
								foreach ( $fields['control_fields'] as $field ) {
									MS_Helper_Html::html_element( $field );
								}
							?>
						</div>
					</div>
				</form>
			</div>
		<?php
	}

	public function prepare_fields() {
		$membership = $this->data['membership'];

		$fields = array(
			'type' => array(
				'id' => 'type',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'value' => ( $membership->type ) ? $membership->type : MS_Model_Membership::TYPE_SIMPLE,
				'class' => 'ms-choose-type',
				'field_options' => array(
					MS_Model_Membership::TYPE_SIMPLE => array(
						'text' => __( 'I simply want to protect some of my content.', MS_TEXT_DOMAIN ),
						'desc' => __( 'This is the most basic membership that creates a single membership level. Members will have access to all protected content.<br /><br /><em>eg. Visitors don\'t see protected content, members access all protected content.</em>', MS_TEXT_DOMAIN ),
					),
					MS_Model_Membership::TYPE_CONTENT_TYPE => array(
						'text' => __( 'I want to have different content available to different members.', MS_TEXT_DOMAIN ),
						'desc' => __( 'This option is for when you have different types of content that you want to make available to different type of members.<br /><br /><em>eg. Music members get access to Guitar Courses, Cooking members get access to Recipes.</em>', MS_TEXT_DOMAIN ),
					),
					MS_Model_Membership::TYPE_TIER => array(
						'text' => __( 'I want to set up a Tier Level-based membership.', MS_TEXT_DOMAIN ),
						'desc' => sprintf(
							'<span class="locked-blur">%1$s</span>',
							__( 'This options allows you to set up different tier level membership.<br /><br /><em>eg. Silver &rarr; Gold &rarr; Platinum. The higher the level, the more content members will have access to.</em>', MS_TEXT_DOMAIN )
						)
						. sprintf(
							'<span class="locked-info" style="display:none">%1$s</span>',
							__( 'This Membership Type is only available to Public Memberships', MS_TEXT_DOMAIN )
						),
					),
					MS_Model_Membership::TYPE_DRIPPED => array(
						'text' => __( 'I want to set up a Dripped Content membership.', MS_TEXT_DOMAIN ),
						'desc' => sprintf(
							'<span class="locked-blur">%1$s</span>',
							__( 'This option will allow you to set up a membership where content will be revelead to users over a period of time.<br /><br /><em>eg. A weekly training / excercize program.</em>', MS_TEXT_DOMAIN )
						)
						. sprintf(
							'<span class="locked-info" style="display:none">%1$s</span>',
							__( 'This Membership Type is only available to Public Memberships', MS_TEXT_DOMAIN )
						),
					),
				),
			),

			'name' => array(
				'id' => 'name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Choose a name for your new membership:', MS_TEXT_DOMAIN ),
				'value' => $membership->name,
				'class' => 'ms-text-large',
				'placeholder' => __( 'Choose a good name that will identify this membership...', MS_TEXT_DOMAIN ),
				'label_type' => 'h3',
			),

			'private' => array(
				'id' => 'private',
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Make this membership private (No registration, no payment)', MS_TEXT_DOMAIN ),
				'desc' => __( 'Choosing this option assumes that you will manually set-up users who can access your content.<br />A registration page will not be created and there will be no payment options.', MS_TEXT_DOMAIN ),
				'value' => $membership->private,
			),

			'control_fields' => array(
					'private_flag' => array(
						// See MS_Controller_Membership->membership_admin_page_process()
						'id' => 'set_private_flag',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 1,
					),
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
					'cancel' => array(
						'id' => 'cancel',
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
						'data_ms' => array(
							'action' => MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
							'field' => 'initial_setup',
							'value' => '0',
						)
					),
					'save' => array(
						'id' => 'save',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Save and continue', MS_TEXT_DOMAIN ) . ' &raquo;',
					),
			),
		);

		// Wizard can only be cancelled when at least one membership exists in DB.
		$count = MS_Model_Membership::get_membership_count();
		if ( ! $count ) {
			unset( $fields['control_fields']['cancel'] );
		}

		return $fields;
	}
}
