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
class MS_View_Membership_Accessible_Content extends MS_View_Membership_Protected_Content {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		// Modify the section header texts.
		$this->remove_filter( 'ms_view_membership_protected_content_header' );
		$this->add_filter(
			'ms_view_membership_protected_content_header',
			'list_header',
			10, 3
		);

		// Display the "Edit Protected Content" below each list.
		$this->remove_action( 'ms_view_membership_protected_content_footer' );
		$this->add_action(
			'ms_view_membership_protected_content_footer',
			'list_footer'
		);

		$tabs = $this->data['tabs'];

		if ( isset( $this->data['bread_crumbs'] ) ) {
			$bread_crumbs = $this->data['bread_crumbs'];
		} else {
			$bread_crumbs = null;
		}

		$desc = sprintf(
			__( 'Setup which Protected Content is available to <span class="ms-bold">%s</span> members.', MS_TEXT_DOMAIN ),
			esc_html( $this->data['membership']->name )
		);

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<div class="ms-accessible-content ms-edit-access">
				<?php
				MS_Helper_Html::settings_header(
					array(
						'title' => __( 'Accessible content', MS_TEXT_DOMAIN ),
						'title_icon_class' => 'wpmui-fa wpmui-fa-cog',
						'desc' => $desc,
						'bread_crumbs' => $bread_crumbs,
					)
				);

				$active_tab = $this->data['active_tab'];
				MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );

				// Call the appropriate form to render.
				$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
				$render_callback = apply_filters(
					'ms_view_membership_accessible_content_render_tab_callback',
					array( $this, $callback_name ),
					$active_tab, $this
				);

				$html = call_user_func( $render_callback );
				$html = apply_filters(
					'ms_view_membership_accessible_' . $callback_name,
					$html
				);
				echo '' . $html;
				?>
			</div>
		</div>
		<?php

		$html = ob_get_clean();

		return apply_filters(
			'ms_view_membership_accessible_content_to_html',
			$html,
			$this
		);
	}

	/**
	 * Modifies the title/description of the list header
	 *
	 * @since  1.1.0
	 * @param  array $header_data The original header details.
	 * @param  string $rule ID of the list that is displayed.
	 * @param  array $args Additional arguments, specific to $rule.
	 * @return array {
	 *     The new title/description
	 *
	 *     title
	 *     desc
	 * }
	 */
	public function list_header( $header_data, $rule, $args ) {
		switch ( $rule ) {
			case MS_Model_Rule::RULE_TYPE_CATEGORY:
				$args['parts'] = WDev()->get_array( $args['parts'] );

				$header_data['title'] = sprintf(
					__( '%s Access', MS_TEXT_DOMAIN ),
					implode( ', ', $args['parts'] )
				);
				$header_data['desc'] = sprintf(
					__( 'Give access to protected %2$s to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name,
					implode( ' & ', $args['parts'] )
				);
				break;

			case MS_Model_Rule::RULE_TYPE_PAGE:
				$header_data['title'] = __( 'Pages', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to following Pages to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_ADMINSIDE:
				$header_data['title'] = __( 'Admin Side Protection', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to following Admin Side pages to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_MEMBERCAPS:
				if (  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
					$header_data['title'] = __( 'Member Capabilities', MS_TEXT_DOMAIN );
					$header_data['desc'] = sprintf(
						__( 'All %1$s members are granted the following Capabilities.', MS_TEXT_DOMAIN ),
						$args['membership']->name
					);
				} else {
					$header_data['title'] = __( 'User Roles', MS_TEXT_DOMAIN );
					$header_data['desc'] = sprintf(
						__( 'All %1$s members are assigned to the following User Role.', MS_TEXT_DOMAIN ),
						$args['membership']->name
					);
				}
				break;

			case MS_Model_Rule::RULE_TYPE_SPECIAL:
				$header_data['title'] = __( 'Special Pages', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to following Special Pages to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_POST:
				$header_data['title'] = __( 'Posts', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to following Posts to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE:
				$header_data['title'] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to following Custom Post Types to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_COMMENT:
				$header_data['title'] = __( 'Comments, More Tag & Menus', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to protected Comments, More Tag & Menus to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_SHORTCODE:
				$header_data['title'] = __( 'Shortcodes', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to protected Shortcodes to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_URL_GROUP:
				$header_data['title'] = __( 'URL Protection', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to protected URLs to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;
		}

		return $header_data;
	}

	/**
	 * Adds content after the item list
	 *
	 * An "Edit Protected Content" link is added below the list.
	 *
	 * @since  1.1.0
	 * @param  string $rule ID of the list that is displayed.
	 */
	public function list_footer( $rule ) {
		$edit_link = $this->restriction_link( $rule );

		?>
		<div class="ms-protection-edit-link">
			<?php MS_Helper_Html::html_element( $edit_link ); ?>
		</div>
		<?php
	}

	/**
	 * Return the field-definition for an "Edit Protected Content" link
	 *
	 * @since  1.0.4
	 * @param  string $rule The protected content to edit (ID).
	 * @return array Field-definition for HTML helper class.
	 */
	protected function restriction_link( $rule = '' ) {
		$titles = array(
			MS_Model_Rule::RULE_TYPE_PAGE => __( 'Manage Protected Pages', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_SPECIAL => __( 'Manage Protected Special Pages', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_CATEGORY => __( 'Manage Protected Categories', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP => __( 'Manage Protected Custom Post Types', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_POST => __( 'Manage Protected Posts', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE => __( 'Manage Protected Custom Post Types', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_COMMENT => __( 'Edit Comments Restrictions', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_MORE_TAG => __( 'Edit More Tag Restrictions', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_MENU => __( 'Edit Menu Restrictions', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_SHORTCODE => __( 'Manage Protected Shortcodes', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_URL_GROUP => __( 'Edit URL Group Restrictions', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_ADMINSIDE => __( 'Edit Admin Side Restrictions', MS_TEXT_DOMAIN ),
			MS_Model_Rule::RULE_TYPE_MEMBERCAPS => __( 'Edit Capability Restrictions', MS_TEXT_DOMAIN ),
			'' => __( 'Manage Protected Content', MS_TEXT_DOMAIN ),
		);

		$url = sprintf(
			'admin.php?page=%s&tab=%s&from=%s',
			MS_Controller_Plugin::MENU_SLUG . '-setup',
			$rule,
			base64_encode( MS_Helper_Utility::get_current_url() )
		);

		return array(
			'id' => 'rule_edit_' . $rule,
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => $titles[ $rule ],
			'url' => $url,
		);
	}

	/* ====================================================================== *
	 *                               INEXISTENT TAB
	 * ====================================================================== */

	/**
	 * Render content for inexistent tabs.
	 *
	 * @since 1.0.0
	 */
	public function render_tab_() {
		$menu_link = $this->restriction_link();

		ob_start();
		?>
		<div class="ms-settings">
			<div class="ms-not-protected-msg-wrapper">
				<div class="ms-not-protected-msg">
					<?php _e( 'You do not have any protection rules set.', MS_TEXT_DOMAIN ); ?>
				</div>
				<?php MS_Helper_Html::html_element( $menu_link ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

}