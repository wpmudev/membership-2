<?php

/**
 * Render Accessible Content page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage View
 */
class MS_View_Membership_Accessible_Content extends MS_View_Membership_Setup_Protected_Content {

	/**
	 * Data set by controller.
	 *
	 * @since 1.0.0
	 * @var mixed $data
	 */
	protected $data;

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {

		$tabs = $this->data['tabs'];
		if ( 1 == @$_GET['edit'] ) {
			$this->data[ 'hide_next_button' ] = true;
		}

		ob_start();
		/* Render tabbed interface. */
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Accessible content', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'fa fa-cog',
					'desc' => sprintf( __( 'Setup which Protected Content is available to <span class="ms-bold">%s</span> members.', MS_TEXT_DOMAIN ), $this->data['membership']->name ),
					'bread_crumbs' => $this->data['bread_crumbs'],
				)
			);

			$active_tab = $this->data['active_tab'];
			MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );

			/* Call the appropriate form to render. */
			$render_callback = 'render_tab_' . str_replace( '-', '_', $active_tab );
			$render_callback = apply_filters(
				'ms_view_membership_accessible_content_render_tab_callback',
				array( $this, $render_callback ),
				$active_tab, $this
			);

			call_user_func( $render_callback );
			?>
			</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_membership_accessible_content_to_html', $html, $this );
	}

	/**
	 * Render content for inexistent tabs.
	 *
	 * @since 1.0.0
	 */
	public function render_tab_() {

		$menu_link = array(
				'id' => 'menu_link',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Manage Protected Content', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s', MS_Controller_Plugin::MENU_SLUG . '-setup' ),
		);

		ob_start();
		?>
			<div class='ms-settings'>
				<div class="ms-not-protected-msg-wrapper">
					<div class="ms-not-protected-msg">
						<?php _e( 'You do not have any protection rules set.', MS_TEXT_DOMAIN );?>
					</div>
					<?php MS_Helper_Html::html_element( $menu_link );?>
				</div>
			</div>
		<?php
		$html = ob_get_clean();

		echo apply_filters( 'ms_view_membership_accessible_render_tab_', $html );
	}

	/**
	 * Render category tab.
	 *
	 * @since 1.0.0
	 */
	public function render_tab_category() {
		$fields = $this->get_tab_category_fields();
		$membership = $this->data['membership'];

		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$category_rule_list_table = new MS_Helper_List_Table_Rule_Category( $rule, $membership );
		$category_rule_list_table->prepare_items();

		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
		$cpt_rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type_Group( $rule, $membership );
		$cpt_rule_list_table->prepare_items();

		$title = array();
		$desc = '';
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$title['category'] = __( 'Categories', MS_TEXT_DOMAIN );
		}
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$title['cpt_group'] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
		}
		$desc = sprintf(
			__( 'Give access to protected %s to %s members.', MS_TEXT_DOMAIN ),
			implode( ' & ', $title ),
			$membership->name
		);
		$title = sprintf( __( '%s Access', MS_TEXT_DOMAIN ), implode( ', ', $title ) );
		ob_start();
		?>
			<div class='ms-settings'>
				<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
				<div class="ms-separator"></div>

				<?php if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) : ?>
					<div class="ms-list-table-wrapper ms-half space">
						<div class="ms-field-input-label">
							<?php _e( 'Protected Categories:', MS_TEXT_DOMAIN ); ?>
						</div>
						<?php $category_rule_list_table->display(); ?>
						<?php if ( empty( $this->data['protected_content'] ) ) : ?>
							<div class="ms-protection-edit-link">
								<?php MS_Helper_Html::html_element( $fields['category_rule_edit'] );?>
							</div>
						<?php endif;?>
					</div>
				<?php endif; ?>
				<?php if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) : ?>
					<div class="ms-list-table-wrapper ms-half">
						<div class="ms-field-input-label">
							<?php _e( 'Protected Custom Post Types:', MS_TEXT_DOMAIN );?>
						</div>
						<?php $cpt_rule_list_table->display(); ?>
						<?php if ( empty( $this->data['protected_content'] ) ) : ?>
							<div class="ms-protection-edit-link">
								<?php MS_Helper_Html::html_element( $fields['cpt_group_rule_edit'] );?>
							</div>
						<?php endif;?>
					</div>
				<?php endif; ?>
			</div>
			<?php MS_Helper_Html::settings_footer(); ?>
		<?php
		$html = ob_get_clean();

		echo apply_filters( 'ms_view_membership_accessible_render_tab_category', $html );
	}

}