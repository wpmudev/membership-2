<?php

class MS_View_Membership_Setup_Protected_Content extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		
		$tabs = $this->data['tabs'];
		ob_start();
		
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap wrap'>
				<?php 
					MS_Helper_Html::settings_header( array(
						'title' => __( 'Select Content to Protect', MS_TEXT_DOMAIN ),
						'title_icon_class' => 'fa fa-pencil-square',
						'desc' => __( 'Hello and welcome to Protected Content by WPMU DEV. Lets begin by settinup up the content you want to protect. Please select at least 1 page or category to protect.', MS_TEXT_DOMAIN ),
					) ); 
				?>
				<?php
					$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
				
					/** Call the appropriate form to render. */
					$render_callback =  apply_filters( 'ms_view_membership_edit_render_callback', array( $this, 'render_' . str_replace('-', '_', $active_tab ) ), $active_tab, $this->data );
					call_user_func( $render_callback );
				?>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	public function render_category() {
		$this->prepare_category();
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Categories & Custom Post Types', MS_TEXT_DOMAIN ); ?></h3>
				<div class="settings-description">
					<div><?php _e( 'The easiest way to restrict content is by setting up a category that you can then use to mark content you want restricted.', MS_TEXT_DOMAIN ); ?></div>
					<div><?php _e( 'You can also choose Custom Post Type(s) to be restricted (eg. Products or Events).', MS_TEXT_DOMAIN ); ?></div>
				</div>
				<hr />
				<div class="ms-rule-wrapper">
					<?php MS_Helper_Html::html_input( $this->fields['category'] );?>
				</div>
				<div class="ms-rule-wrapper">
					<?php MS_Helper_Html::html_input( $this->fields['cpt_group'] );?>
				</div>
			</div>
			<?php
				if( $this->data['initial_setup'] ) {
					MS_Helper_Html::settings_footer( array( 'fields' => array( $this->fields['step'] ) ) ); 
				}
				else {
					MS_Helper_Html::settings_footer( array( 'fields' => array( $this->fields['step'] ) ), false ); 
				} 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	public function prepare_category() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];
		
		$this->fields = array(
				'category' => array(
						'id' => 'category',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Protect Categories:', MS_TEXT_DOMAIN ),
						'value' => $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY )->rule_value,
						'multiple' => 'multiple',
						'field_options' => $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY )->get_content_array(),
						'data_placeholder' => __( 'Choose a category', MS_TEXT_DOMAIN ),
						'class' => 'ms-chosen-rule chosen-select',
						'data_ms' => array(
							'membership_id' => $membership->id,
							'rule_type' => MS_Model_Rule::RULE_TYPE_CATEGORY,
							'_wpnonce' => $nonce,
							'action' => $action,
						),
				),
				'cpt_group' => array(
						'id' => 'cpt_group',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Protect Custom Post Types (CPTs):', MS_TEXT_DOMAIN ),
						'value' => $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP )->rule_value,
						'data_placeholder' => __( 'Choose a CPT', MS_TEXT_DOMAIN ),
						'multiple' => 'multiple',
						'field_options' => $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP )->get_content_array(),
						'class' => 'ms-chosen-rule chosen-select',
						'data_ms' => array(
								'membership_id' => $membership->id,
								'rule_type' => MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
								'_wpnonce' => $nonce,
								'action' => $action,
						),
				),
				'protect_category' => array(
						'id' => 'protect_category',
						'value' => __( 'Protect Category', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'class' => '',
				),
				'protect_cpt_group' => array(
						'id' => 'protect_cpt_group',
						'value' => __( 'Protect CPT', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'class' => '',
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $action,
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['step'],
				),
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $nonce,
				),
				
		);
	}
	
	public function render_post() {
	
	}
	
	public function render_page() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( 'page' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $rule );
		$rule_list_table->prepare_items();
	
		$toggle = array(
				'id' => 'ms-toggle-rule',
				'title' => __( 'Default acccess rule:', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $rule->rule_value_default,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE_DEFAULT,
						'membership_id' => $membership->id,
						'rule' => $rule->rule_type,
				),
		);
	
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Page access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description">
				<div class="settings-description">
					<?php _e( 'Select the pages below that you would like to give access to as part of this membership. ', MS_TEXT_DOMAIN ); ?>
					<?php MS_Helper_Html::html_input( $toggle ); ?>
				</div>
				
				<hr />
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
		
	public function render_comment() {
	
	}
	public function render_shortcode() {
	
	}
	public function render_urlgroup() {
	
	}
	
	
}