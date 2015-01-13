<?php

class MS_View_Membership_Overview_Tier extends MS_View_Membership_Overview {

	protected $data;

	public function available_content_panel_data() {
		if ( $this->data['child_membership']->is_valid() ) : ?>
			<div class="ms-settings ms-group">
				<?php
					$membership = $this->data['child_membership'];
					$protected_content = MS_Model_Membership::get_protected_content();
					$rule_types = MS_Model_Rule::get_rule_types();

					echo '<div class="ms-group">';
					foreach ( $rule_types as $rule_type ) {
						$has_rules = false;

						switch ( $rule_type ) {
							case MS_Model_Rule::RULE_TYPE_REPLACE_MENUS:
							case MS_Model_Rule::RULE_TYPE_REPLACE_MENULOCATIONS:
								$rule = $membership->get_rule( $rule_type );
								$has_rules = true;
								break;

							default:
								$rule = $protected_content->get_rule( $rule_type );
								$has_rules = $rule->has_rules();
								break;
						}

						if ( $has_rules ) {
							$this->content_box_tags( $membership->get_rule( $rule_type ) );
						}
					}
					echo '</div>';
				?>
			</div>
		<?php endif;

		MS_Helper_Html::html_element(
			array(
				'id' => 'setup_tiers',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Edit Tiers', MS_TEXT_DOMAIN ),
				'url' => add_query_arg(
					array(
						'step' => MS_Controller_Membership::STEP_SETUP_MS_TIERS,
						'membership_id' => $this->data['membership']->id,
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
						'membership_id' => $this->data['membership']->id,
						'edit' => 1,
					)
				),
				'class' => 'wpmui-field-button button',
			)
		);
	}
}