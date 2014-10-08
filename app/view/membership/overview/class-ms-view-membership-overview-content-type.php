<?php

class MS_View_Membership_Overview_Content_Type extends MS_View_Membership_Overview {

	protected $data;

	public function available_content_panel_data() {
		if ( $this->data['child_membership']->is_valid() ) : ?>
			<div class="ms-wrap wrap">
				<div class="ms-tabs-titlerow">
					<span class="ms-tabs"><?php _e( 'Content Type:', MS_TEXT_DOMAIN );?></span>
					<span><?php _e( 'Accessible Content:', MS_TEXT_DOMAIN );?></span>
				</div>
				<?php MS_Helper_Html::html_admin_vertical_tabs( $this->data['tabs'] ); ?>
			</div>

			<div class="ms-settings">
				<?php
				$membership = $this->data['child_membership'];
				$visitor_membership = MS_Model_Membership::get_visitor_membership();
				$rule_types = MS_Model_Rule::get_rule_types();
				foreach ( $rule_types as $rule_type ) {
					if ( $visitor_membership->get_rule( $rule_type )->has_rules() ) {
						$this->content_box_tags( $membership->get_rule( $rule_type ) );
					}
				}
				?>
			</div>
		<?php endif;

		echo '<div class="clear"></div>';

		MS_Helper_Html::html_element(
			array(
				'id' => 'setup_tiers',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Edit Content Types', MS_TEXT_DOMAIN ),
				'url' => add_query_arg(
					array(
						'step' => MS_Controller_Membership::STEP_SETUP_CONTENT_TYPES,
						'membership_id' => $this->data['membership']->id,
						'edit' => 1,
					)
				),
				'class' => 'ms-link-button button',
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
						'membership_id' => $this->data['membership']->id,
						'edit' => 1,
					)
				),
				'class' => 'ms-link-button button',
			)
		);
	}
}