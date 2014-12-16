<?php

/**
 * Render Dripped Content page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage View
 */
class MS_View_Membership_Setup_Dripped extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		$tabs = $this->data['tabs'];

		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce = wp_create_nonce( $action );
		$membership = $this->data['membership'];

		$field_type = array(
			'id' => 'dripped_type',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
			'value' => $membership->dripped_type,
			'field_options' => MS_Model_Rule::get_dripped_types(),
			'class' => 'ms-dripped-type ms-ajax-update',
			'data_ms' => array(
				'membership_id' => $membership->id,
				'field' => 'dripped_type',
				'action' => $action,
				'_wpnonce' => $nonce,
			),
		);

		ob_start();
		// Render tabbed interface.
		?>
		<div class='ms-wrap wrap'>
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Dripped Content', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'ms-fa ms-fa-cog',
					'desc' => sprintf(
						__( 'Setup which Protected Content will become available to %s members.', MS_TEXT_DOMAIN ),
						$this->data['membership']->name
					),
					'bread_crumbs' => $this->data['bread_crumbs'],
				)
			);

			echo '<div class="clear">';
			MS_Helper_Html::html_element( $field_type );
			echo '</div>';

			$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );

			// Call the appropriate form to render.
			$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
			$render_callback = apply_filters(
				'ms_view_membership_setup_dripped_render_tab_callback',
				array( $this, $callback_name ),
				$active_tab, $this
			);

			$html = call_user_func( $render_callback );
			$html = apply_filters( 'ms_view_membership_setup_dripped_' . $callback_name, $html );
			echo $html;
			?>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_membership_setup_dripped_content_to_html', $html, $this );
	}

	public function render_tab_page() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_PAGE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $rule, $membership );
		$rule_list_table->prepare_items();

		ob_start();
		?>
		<div class="ms-settings">
			<h3><?php _e( 'Pages', MS_TEXT_DOMAIN ); ?></h3>
			<div class="settings-description ms-description">
				<?php printf(
					__( 'Give access to protected Pages to %s members. ', MS_TEXT_DOMAIN ),
					$membership->name
				); ?>
			</div>
			<?php MS_Helper_Html::html_separator(); ?>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->display(); ?>
			</form>
		</div>
		<?php MS_Helper_Html::settings_footer(
			null,
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	public function render_tab_post() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $rule, $membership );
		$rule_list_table->prepare_items();

		ob_start();
		?>
		<div class="ms-settings">
			<h3><?php _e( 'Posts', MS_TEXT_DOMAIN ); ?></h3>
			<div class="settings-description ms-description">
				<?php printf(
					__( 'Give access to protected Posts to %s members. ', MS_TEXT_DOMAIN ),
					$membership->name
				); ?>
			</div>
			<?php MS_Helper_Html::html_separator(); ?>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->display(); ?>
			</form>
		</div>
		<?php MS_Helper_Html::settings_footer(
			null,
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}
}