<?php

class MS_View_Membership_Overview_Dripped extends MS_View_Membership_Overview {

	protected $data;

	public function available_content_panel_data() {
		$available = array();
		$soon = array();
		$membership = $this->data['membership'];
		$protected_content = MS_Model_Membership::get_protected_content();
		$rule_types = array( MS_Model_Rule::RULE_TYPE_PAGE, MS_Model_Rule::RULE_TYPE_POST );

		foreach ( $rule_types as $rule_type ) {
			$rule = $membership->get_rule( $rule_type );
			$contents = $rule->get_contents( array( 'protected_content' => 1 ) );

			foreach ( $contents as $content ) {
				if ( $rule->has_dripped_rules( $content->id ) && $rule->has_access( $content->id ) ) {
					if ( $rule->has_dripped_access( MS_Helper_Period::current_date(), $content->id ) ) {
						$available[] = $content;
					} else {
						$soon[] = $content;
					}
				}
			}
		}

		?>
		<div class="clear">
			<div class="ms-half ms-available-soon">
				<div class="ms-bold">
					<i class="dashicons dashicons-clock ms-low"></i>
					<?php _e( 'Soon to be available content:', MS_TEXT_DOMAIN ); ?>
				</div>
				<div class="inside">
					<?php $this->content_box_date( $soon ); ?>

					<div class="ms-protection-edit-wrapper">
						<?php
						MS_Helper_Html::html_element(
							array(
								'id' => 'edit_dripped',
								'type' => MS_Helper_Html::TYPE_HTML_LINK,
								'value' => __( 'Edit Dripped Content', MS_TEXT_DOMAIN ),
								'url' => add_query_arg(
									array(
										'step' => MS_Controller_Membership::STEP_SETUP_DRIPPED,
										'edit' => 1,
									)
								),
								'class' => 'wpmui-field-button button',
							)
						);

						MS_Helper_Html::html_element(
							array(
								'id' => 'setup_payment',
								'type' => MS_Helper_Html::TYPE_HTML_LINK,
								'value' => __( 'Payment Options', MS_TEXT_DOMAIN ),
								'url' => add_query_arg(
									array(
										'step' => MS_Controller_Membership::STEP_SETUP_PAYMENT,
										'edit' => 1,
									)
								),
								'class' => 'wpmui-field-button button',
							)
						);
						?>
					</div>
				</div>
			</div>

			<div class="ms-half ms-available">
				<div class="ms-bold">
					<i class="dashicons dashicons-yes ms-low"></i>
					<?php _e( 'Already available content:', MS_TEXT_DOMAIN ); ?>
				</div>
				<div class="inside">
					<?php $this->content_box_date( $available ); ?>
				</div>
			</div>
		</div>

		<?php
	}
}