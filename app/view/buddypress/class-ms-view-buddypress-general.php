<?php

class MS_View_Buddypress_General extends MS_View {

	protected $title;

	public function render_rule_tab() {
		$fields = $this->get_control_fields();

		$base_membership = MS_Model_Membership::get_protected_content();
		$membership = $this->data['membership'];
		$is_base = $membership->id === $base_membership->id;

		$rule = $membership->get_rule( MS_Integration_Buddypress::RULE_TYPE_BUDDYPRESS );
		$rule_list_table = new MS_Helper_List_Table_Rule_Buddypress( $rule, $membership );
		$rule_list_table->is_base_list( $is_base );
		$rule_list_table->prepare_items();

		$edit_link = array(
			'id' => 'buddypress_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected BuddyPress Content', MS_TEXT_DOMAIN ),
			'url' => sprintf(
				'admin.php?page=%s&tab=%s',
				MS_Controller_Plugin::MENU_SLUG . '-setup',
				MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS
			),
		);

		$title = __( 'BuddyPress', MS_TEXT_DOMAIN );
		if ( $is_base ) {
			$desc = __( 'Protect the following BuddyPress content. ', MS_TEXT_DOMAIN );
		} else {
			$desc = sprintf(
				__( 'Give access to protected BuddyPress content to %s members.', MS_TEXT_DOMAIN ),
				$this->data['membership']->name
			);
		}

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) );
			MS_Helper_Html::html_separator();
			?>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->display(); ?>
			</form>
			<?php if ( ! $is_base ) : ?>
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $edit_link ); ?>
				</div>
			<?php endif;?>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);

		$html = ob_get_clean();
		echo apply_filters(
			'ms_view_buddypress_general_render_tab_shortcode',
			$html
		);
	}

	public function get_control_fields() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];

		$fields = array(
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
		return apply_filters(
			'ms_view_buddypress_general_get_control_fields',
			$fields
		);
	}
}