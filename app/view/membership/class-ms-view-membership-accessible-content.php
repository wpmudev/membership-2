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
		$header_data['class'] = 'ms-edit-access';

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
				$header_data['title'] = __( 'Member Capabilities', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'All %1$s members are granted the following Capabilities.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;

			case MS_Model_Rule::RULE_TYPE_MEMBERROLES:
				$header_data['title'] = __( 'User Roles', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'All %1$s members are assigned to the following User Role.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
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
		if ( empty( $edit_link ) ) { return; }

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
			MS_Model_Rule::RULE_TYPE_MEMBERROLES => false, // No Protected Content settings!
			'' => false, // No Protected Content settings!
		);

		$link = '';

		if ( ! empty( $titles[ $rule ] ) ) {
			$url = sprintf(
				'admin.php?page=%s&tab=%s&from=%s',
				MS_Controller_Plugin::MENU_SLUG . '-setup',
				$rule,
				base64_encode( MS_Helper_Utility::get_current_url() )
			);

			$link = array(
				'id' => 'rule_edit_' . $rule,
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => $titles[ $rule ],
				'url' => $url,
			);
		}

		return $link;
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

	/* ====================================================================== *
	 *                               MEMBER CAPS
	 * ====================================================================== */

	public function render_tab_membercaps() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
			$rule_id = MS_Model_Rule::RULE_TYPE_MEMBERCAPS;
			$rule = $membership->get_rule( $rule_id );
			$rule_list_table = new MS_Helper_List_Table_Rule_Membercaps( $rule, $membership );
			$rule_list_table->prepare_items();
		} else {
			$rule_id = MS_Model_Rule::RULE_TYPE_MEMBERROLES;
			$rule = $membership->get_rule( $rule_id );

			$input_desc = '';
			if (  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
				$input_desc = __( 'Tipp: If a member belongs to more than one membership then the User Role capabilities of both roles are merged.', MS_TEXT_DOMAIN );
			}
			$options = array( '' => __( '(Don\'t change the members role)', MS_TEXT_DOMAIN ) );
			$options += $rule->get_content_array();

			$role_selection = array(
				'id' => 'ms-user-role',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'desc' => $input_desc,
				'value' => $rule->user_role,
				'field_options' => $options,
				'ajax_data' => array(
					'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'field' => 'user_role',
				),
			);
		}

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			$rule_id,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
				$rule_list_table->views();
				$rule_list_table->search_box( __( 'Capabilities', MS_TEXT_DOMAIN ) );
				?>
				<form action="" method="post">
					<?php
					$rule_list_table->display();

					do_action(
						'ms_view_membership_protected_content_footer',
						MS_Model_Rule::RULE_TYPE_MEMBERCAPS,
						$this
					);
					?>
				</form>
				<?php
			} else {
				MS_Helper_Html::html_element( $role_selection );
			}
			?>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               COMMENT, MORE, MENU
	 * ====================================================================== */

	/**
	 * Render tab content for:
	 * Comments, More tag, Menus
	 *
	 * @since  1.0.0
	 */
	public function render_tab_comment() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$menu_protection = $this->data['settings']->menu_protection;
		$protected_content = MS_Model_Membership::get_protected_content();

		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$rule_comment = $membership->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT );

		switch ( $menu_protection ) {
			case 'item':
				$rule_menu = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MENU );
				$rule_list_table = new MS_Helper_List_Table_Rule_Menu(
					$rule_menu,
					$membership,
					$this->data['menu_id']
				);
				break;

			case 'menu':
				$rule_menu = $membership->get_rule( MS_Model_Rule::RULE_TYPE_REPLACE_MENUS );
				$rule_list_table = new MS_Helper_List_Table_Rule_Replace_Menu(
					$rule_menu,
					$membership
				);
				break;

			case 'location':
				$rule_menu = $membership->get_rule( MS_Model_Rule::RULE_TYPE_REPLACE_MENULOCATIONS );
				$rule_list_table = new MS_Helper_List_Table_Rule_Replace_Menulocation(
					$rule_menu,
					$membership
				);
				break;
		}

		$val_comment = $rule_comment->get_rule_value( MS_Model_Rule_Comment::CONTENT_ID );
		$val_more_tag = absint( $rule_more_tag->get_rule_value( MS_Model_Rule_More::CONTENT_ID ) );

		$fields = array(
			'comment' => array(
				'id' => 'comment',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Members have:', MS_TEXT_DOMAIN ),
				'value' => $val_comment,
				'field_options' => $rule_comment->get_content_array(),
				'class' => 'chosen-select',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_COMMENT,
					'values' => MS_Model_Rule_Comment::CONTENT_ID,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'more_tag' => array(
				'id' => 'more_tag',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Members can read full post (beyond the More Tag):', MS_TEXT_DOMAIN ),
				'value' => $val_more_tag,
				'field_options' => $rule_more_tag->get_options_array(),
				'class' => 'ms-more-tag',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_MORE_TAG,
					'values' => MS_Model_Rule_More::CONTENT_ID,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		if ( 'item' === $menu_protection ) {
			$fields['menu_id'] = array(
				'id' => 'menu_id',
				'title' => __( 'Protect menu items', MS_TEXT_DOMAIN ),
				'desc' => __( 'Select menu to protect:', MS_TEXT_DOMAIN ),
				'value' => $this->data['menu_id'],
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $this->data['menus'],
				'class' => 'chosen-select',
			);
			$fields['rule_menu'] = array(
				'id' => 'rule_menu',
				'name' => 'rule',
				'value' => 'menu',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			);
		}

		if ( MS_Model_Rule_Comment::RULE_VALUE_WRITE === $val_comment ) {
			$fields['comment'] = array(
				'id' => 'comment',
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
				'value' => __( 'Members can Read and Post comments', MS_TEXT_DOMAIN ),
				'class' => 'wpmui-field-description',
				'wrapper' => 'div',
			);
		}

		if ( $val_more_tag ) {
			$fields['more_tag'] = array(
				'id' => 'more_tag',
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
				'value' => __( 'Members can read full post (beyond the More Tag)', MS_TEXT_DOMAIN ),
				'class' => 'wpmui-field-description',
				'wrapper' => 'div',
			);
		}

		$fields = apply_filters(
			'ms_view_membership_protected_content_get_tab_comment_fields',
			$fields
		);

		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_COMMENT,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();
			?>

			<div class="ms-group">
				<div class="ms-half">
					<div class="inside">
						<?php
						MS_Helper_Html::html_element( $fields['comment'] );
						MS_Helper_Html::save_text();

						do_action(
							'ms_view_membership_protected_content_footer',
							MS_Model_Rule::RULE_TYPE_COMMENT,
							$this
						);

						MS_Helper_Html::html_separator( 'vertical' );
						?>
					</div>
				</div>

				<div class="ms-half">
					<div class="inside">
						<?php
						MS_Helper_Html::html_element( $fields['more_tag'] );
						MS_Helper_Html::save_text();

						do_action(
							'ms_view_membership_protected_content_footer',
							MS_Model_Rule::RULE_TYPE_MORE_TAG,
							$this
						);
						?>
					</div>
				</div>
			</div>

			<?php MS_Helper_Html::html_separator(); ?>

			<div class="ms-group ms-group-menu ms-protect-<?php echo esc_attr( $menu_protection ); ?>">
				<div class="ms-inside">

				<?php if ( 'item' === $menu_protection ) {
					$menu_url = add_query_arg( array( 'menu_id' => $this->data['menu_id'] ) );
					?>
					<form id="ms-menu-form" method="post">
						<?php MS_Helper_Html::html_element( $fields['menu_id'] ); ?>
					</form>
					<form id="ms-menu-form" method="post" action="<?php echo '' . $menu_url; ?>">
						<?php
						MS_Helper_Html::html_element( $fields['rule_menu'] );
						$rule_list_table->views();
						$rule_list_table->display();
						?>
					</form>
					<?php

					do_action(
						'ms_view_membership_protected_content_footer',
						MS_Model_Rule::RULE_TYPE_MENU,
						$this
					);
				} else {
					$rule_list_table->views();
					$rule_list_table->display();
					if ( 'menu' === $menu_protection ) {
						?>
						<p>
							<?php _e( 'Hint: Only one replacement rule is applied to each menu.', MS_TEXT_DOMAIN ); ?>
						</p>
						<?php
					}
				}
				?>

				</div>
			</div>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               URL GROUP
	 * ====================================================================== */

	public function render_tab_url_group() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_URL_GROUP );
		$rule_list_table = new MS_Helper_List_Table_Rule_Url_Group( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_URL_GROUP,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			$rule_list_table->views();
			?>
			<form action="" method="post">
				<?php
				$rule_list_table->search_box( __( 'Search URLs', MS_TEXT_DOMAIN ), 'search' );
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_URL_GROUP,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

}