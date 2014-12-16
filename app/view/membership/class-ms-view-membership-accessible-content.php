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
class MS_View_Membership_Accessible_Content extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		$tabs = $this->data['tabs'];

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Accessible content', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'ms-fa ms-fa-cog',
					'desc' => sprintf(
						__( 'Setup which Protected Content is available to <span class="ms-bold">%s</span> members.', MS_TEXT_DOMAIN ),
						esc_html( $this->data['membership']->name )
					),
					'bread_crumbs' => $this->data['bread_crumbs'],
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
			$html = apply_filters( 'ms_view_membership_accessible_' . $callback_name, $html );
			echo $html;
			?>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_membership_accessible_content_to_html', $html, $this );
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
	 *                               CATEGORY
	 * ====================================================================== */

	/**
	 * Render category tab.
	 *
	 * @since 1.0.0
	 */
	public function render_tab_category() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'category_rule_edit' => $this->restriction_link( MS_Model_Rule::RULE_TYPE_CATEGORY ),
			'cpt_group_rule_edit' => $this->restriction_link( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP ),
		);

		$fields = apply_filters(
			'ms_view_membership_setup_protected_content_get_category_fields',
			$fields
		);

		$rule_cat = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$category_rule_list_table = new MS_Helper_List_Table_Rule_Category(
			$rule_cat,
			$membership
		);
		$category_rule_list_table->prepare_items();

		$rule_cpt = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
		$cpt_rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type_Group(
			$rule_cpt,
			$membership
		);
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
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => $title, 'desc' => $desc )
			);
			MS_Helper_Html::html_separator();
			?>

			<?php if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) : ?>
				<div class="ms-group">
					<div class="inside">
						<div class="wpmui-field-label">
							<?php _e( 'Protected Categories:', MS_TEXT_DOMAIN ); ?>
						</div>
						<?php $category_rule_list_table->display(); ?>
						<div class="ms-protection-edit-link">
							<?php MS_Helper_Html::html_element( $fields['category_rule_edit'] ); ?>
						</div>
						<?php MS_Helper_Html::html_separator(); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) : ?>
				<div class="ms-group">
					<div class="inside">
						<div class="wpmui-field-label">
							<?php _e( 'Protected Custom Post Types:', MS_TEXT_DOMAIN ); ?>
						</div>
						<?php $cpt_rule_list_table->display(); ?>
						<div class="ms-protection-edit-link">
							<?php MS_Helper_Html::html_element( $fields['cpt_group_rule_edit'] ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php MS_Helper_Html::settings_footer(
			null,
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PAGE
	 * ====================================================================== */

	public function render_tab_page() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_PAGE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = $this->restriction_link( MS_Model_Rule::RULE_TYPE_PAGE );

		$title = __( 'Pages ', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to following Pages to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) );
			MS_Helper_Html::html_separator();
			?>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->search_box( __( 'Search Pages', MS_TEXT_DOMAIN ), 'search' ); ?>
				<?php $rule_list_table->display(); ?>
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $edit_link ); ?>
				</div>
			</form>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               ADMIN SIDE
	 * ====================================================================== */

	public function render_tab_adminside() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_ADMINSIDE );

		$rule_list_table = new MS_Helper_List_Table_Rule_Adminside( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = $this->restriction_link( MS_Model_Rule::RULE_TYPE_ADMINSIDE );

		$title = __( 'Admin Side Protection', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to following Admin Side pages to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

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
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $edit_link ); ?>
				</div>
			</form>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               MEMBER CAPS
	 * ====================================================================== */

	public function render_tab_membercaps() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MEMBERCAPS );

		if (  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
			$title = __( 'Member Capabilities', MS_TEXT_DOMAIN );
			$desc = sprintf(
				__( 'All %s members are granted the following Capabilities.', MS_TEXT_DOMAIN ),
				$this->data['membership']->name
			);
		} else {
			$title = __( 'User Roles', MS_TEXT_DOMAIN );
			$desc = sprintf(
				__( 'All %s members are assigned to the following User Role.', MS_TEXT_DOMAIN ),
				$this->data['membership']->name
			);

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

		$rule_list_table = new MS_Helper_List_Table_Rule_Membercaps( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = $this->restriction_link( MS_Model_Rule::RULE_TYPE_MEMBERCAPS );

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) );
			MS_Helper_Html::html_separator();
			?>

			<?php if (  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) : ?>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_element( $edit_link ); ?>
					</div>
				</form>
			<?php else : ?>
				<?php MS_Helper_Html::html_element( $role_selection ); ?>
			<?php endif; ?>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}
	/* ====================================================================== *
	 *                               SPECIAL PAGES
	 * ====================================================================== */

	public function render_tab_special() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_SPECIAL );

		$rule_list_table = new MS_Helper_List_Table_Rule_Special( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = $this->restriction_link( MS_Model_Rule::RULE_TYPE_SPECIAL );

		$title = __( 'Special Pages ', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to following Special Pages to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

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
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $edit_link ); ?>
				</div>
			</form>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               POSTS
	 * ====================================================================== */

	public function render_tab_post() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = $this->restriction_link( MS_Model_Rule::RULE_TYPE_POST );

		$title = __( 'Posts ', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to following Posts to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) );
			MS_Helper_Html::html_separator();
			?>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->search_box( __( 'Search Posts', MS_TEXT_DOMAIN ), 'search' ); ?>
				<?php $rule_list_table->display(); ?>
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $edit_link ); ?>
				</div>
			</form>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               CUSTOM POST TYPE
	 * ====================================================================== */

	public function render_tab_cpt() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = $this->restriction_link( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE );

		$title = __( 'Custom Post Types ', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to following Custom Post Types to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) );
			MS_Helper_Html::html_separator();
			?>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->search_box( __( 'Search Posts', MS_TEXT_DOMAIN ), 'search' ); ?>
				<?php $rule_list_table->display(); ?>
			</form>
			<div class="ms-protection-edit-link">
				<?php MS_Helper_Html::html_element( $edit_link ); ?>
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

			'comment_rule_edit' => $this->restriction_link( MS_Model_Rule::RULE_TYPE_COMMENT ),

			'more_tag' => array(
				'id' => 'more_tag',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Members can read full post (beyond the More Tag):', MS_TEXT_DOMAIN ),
				'value' => $val_more_tag,
				'field_options' => $rule_more_tag->get_options_array(),
				'class' => 'ms-more-tag ms-ajax-update',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_MORE_TAG,
					'values' => MS_Model_Rule_More::CONTENT_ID,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'more_tag_rule_edit' => $this->restriction_link( MS_Model_Rule::RULE_TYPE_MORE_TAG ),

			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		if ( ! $replace_menus ) {
			$fields['menu_id'] = array(
				'id' => 'menu_id',
				'title' => __( 'Menus:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Select menu to load:', MS_TEXT_DOMAIN ),
				'value' => $this->data['menu_id'],
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $this->data['menus'],
				'class' => 'chosen-select',
			);

			$fields['menu_rule_edit'] = $this->restriction_link( MS_Model_Rule::RULE_TYPE_MENU );
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
			'ms_view_membership_setup_protected_content_get_tab_comment_fields',
			$fields
		);


		$rule_list_table->prepare_items();

		$title = __( 'Comments, More Tag & Menus', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to protected Comments, More Tag & Menus to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => $title, 'desc' => $desc )
			);
			MS_Helper_Html::html_separator();
			?>

			<div class="ms-group">
				<div class="ms-half">
					<div class="inside">
						<?php MS_Helper_Html::html_element( $fields['comment'] ); ?>
						<?php MS_Helper_Html::save_text(); ?>
						<div class="ms-protection-edit-link">
							<?php MS_Helper_Html::html_element( $fields['comment_rule_edit'] ); ?>
						</div>
						<?php MS_Helper_Html::html_separator( 'vertical' ); ?>
					</div>
				</div>

				<div class="ms-half">
					<div class="inside">
						<?php MS_Helper_Html::html_element( $fields['more_tag'] ); ?>
						<?php MS_Helper_Html::save_text(); ?>
						<div class="ms-protection-edit-link">
							<?php MS_Helper_Html::html_element( $fields['more_tag_rule_edit'] ); ?>
						</div>
					</div>
				</div>
			</div>

			<?php MS_Helper_Html::html_separator(); ?>

			<div class="ms-group">
				<div class="ms-inside">

				<?php if ( 'item' === $menu_protection ) : ?>
					<form id="ms-menu-form" method="post">
						<?php MS_Helper_Html::html_element( $fields['menu_id'] ); ?>
					</form>
					<?php $rule_list_table->display(); ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_element( $fields['menu_rule_edit'] ); ?>
					</div>
				<?php else : ?>
					<?php $rule_list_table->display(); ?>
					<?php if ( 'menu' === $menu_protection ) : ?>
						<p>
							<?php _e( 'Hint: Only one replacement rule is applied to each menu.', MS_TEXT_DOMAIN ); ?>
						</p>
					<?php endif; ?>
				<?php endif; ?>

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
	 *                               SHORTCODE
	 * ====================================================================== */

	public function render_tab_shortcode() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_SHORTCODE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Shortcode( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = $this->restriction_link( MS_Model_Rule::RULE_TYPE_SHORTCODE );

		$title = __( 'Shortcodes', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to protected Shortcodes to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

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

			<div class="ms-protection-edit-link">
				<?php MS_Helper_Html::html_element( $edit_link ); ?>
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

		$edit_link = $this->restriction_link( MS_Model_Rule::RULE_TYPE_URL_GROUP );

		$title = __( 'URL Protection', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to protected URLs to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => $title, 'desc' => $desc )
			);
			MS_Helper_Html::html_separator();
			?>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->search_box( __( 'Search URLs', MS_TEXT_DOMAIN ), 'search' ); ?>
				<?php $rule_list_table->display(); ?>
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $edit_link ); ?>
				</div>
			</form>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               SHARED
	 * ====================================================================== */

	public function get_control_fields() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		return apply_filters( 'ms_view_membership_setup_protected_content_get_control_fields', $fields );
	}

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

		return array(
			'id' => 'rule_edit_' . $rule,
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => $titles[ $rule ],
			'url' => sprintf(
				'admin.php?page=%s&tab=%s&from=%s',
				MS_Controller_Plugin::MENU_SLUG . '-setup',
				$rule,
				base64_encode( MS_Helper_Utility::get_current_url() )
			),
		);
	}

}