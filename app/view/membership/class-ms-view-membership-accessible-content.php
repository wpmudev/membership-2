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
			$this->data[ 'show_next_button' ] = false;
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
					'desc' => sprintf(
						__( 'Setup which Protected Content is available to <span class="ms-bold">%s</span> members.', MS_TEXT_DOMAIN ),
						esc_html( $this->data['membership']->name )
					),
					'bread_crumbs' => $this->data['bread_crumbs'],
				)
			);

			$active_tab = $this->data['active_tab'];
			MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );

			/* Call the appropriate form to render. */
			$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
			$render_callback = apply_filters(
				'ms_view_membership_accessible_content_render_tab_callback',
				array( $this, $callback_name ),
				$active_tab, $this
			);

			$html = call_user_func( $render_callback );
			$html = apply_filters( 'ms_view_membership_accessible_' . $callback_name, $html );
			printf( $html );
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
		$menu_link = array(
			'id' => 'menu_link',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected Content', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s', MS_Controller_Plugin::MENU_SLUG . '-setup' ),
		);

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
			'category_rule_edit' => array(
				'id' => 'category_rule_edit',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Manage Protected Categories', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_CATEGORY ),
			),

			'cpt_group_rule_edit' => array(
				'id' => 'cpt_group_rule_edit',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Manage Protected Custom Post Types', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP ),
			),
		);

		$fields = apply_filters( 'ms_view_membership_setup_protected_content_get_category_fields', $fields );

		$rule_cat = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$category_rule_list_table = new MS_Helper_List_Table_Rule_Category( $rule_cat, $membership );
		$category_rule_list_table->prepare_items();

		$rule_cpt = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
		$cpt_rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type_Group( $rule_cpt, $membership );
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
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => $title, 'desc' => $desc )
			); ?>
			<div class="ms-separator"></div>

			<?php if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) : ?>
				<div class="ms-group">
					<div class="inside">
						<div class="ms-field-input-label">
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
						<div class="ms-field-input-label">
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
		<?php MS_Helper_Html::settings_footer();
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

		$edit_link = array(
			'id' => 'page_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected Pages', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_PAGE ),
		);

		$title = __( 'Pages ', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to following Pages to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

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
	 *                               POSTS
	 * ====================================================================== */

	public function render_tab_post() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = array(
			'id' => 'page_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected Posts', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_POST ),
		);

		$title = __( 'Posts ', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to following Posts to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

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

		$edit_link = array(
			'id' => 'page_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected Custom Post Types', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE ),
		);

		$title = __( 'Custom Post Types ', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to following Custom Post Types to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

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

		$protected_content = MS_Model_Membership::get_visitor_membership();

		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$rule_comment = $membership->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT );

		$fields = array(
			'comment' => array(
				'id' => 'comment',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Members have:', MS_TEXT_DOMAIN ),
				'value' => $rule_comment->get_rule_value( MS_Model_Rule_Comment::CONTENT_ID ),
				'field_options' => $rule_comment->get_content_array(),
				'class' => 'chosen-select',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_COMMENT,
					'rule_ids' => MS_Model_Rule_Comment::CONTENT_ID,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'comment_rule_edit' => array(
				'id' => 'comment_rule_edit',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Edit Comments Restrictions', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_COMMENT ),
			),

			'more_tag' => array(
				'id' => 'more_tag',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Members can read full post (beyond the More Tag):', MS_TEXT_DOMAIN ),
				'value' => $rule_more_tag->get_rule_value( MS_Model_Rule_More::CONTENT_ID ) ? 1 : 0,
				'field_options' => $rule_more_tag->get_options_array(),
				'class' => 'ms-more-tag ms-ajax-update',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_MORE_TAG,
					'rule_ids' => MS_Model_Rule_More::CONTENT_ID,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'more_tag_rule_edit' => array(
				'id' => 'more_tag_rule_edit',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Edit More Tag Restrictions', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_MORE_TAG ),
			),

			'menu_id' => array(
				'id' => 'menu_id',
				'title' => __( 'Menus:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Select menu to load:', MS_TEXT_DOMAIN ),
				'value' => $this->data['menu_id'],
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $this->data['menus'],
				'class' => 'chosen-select',
			),

			'menu_rule_edit' => array(
				'id' => 'menu_rule_edit',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Edit Menu Restrictions', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_MENU ),
			),

			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);


		if ( MS_Model_Rule_Comment::RULE_VALUE_WRITE == $protected_content->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT )->get_rule_value( MS_Model_Rule_Comment::CONTENT_ID ) ) {
			$fields['comment'] = array(
				'id' => 'comment',
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
				'value' => __( 'Members can Read & Post comments', MS_TEXT_DOMAIN ),
				'class' => 'ms-field-description',
				'wrapper' => 'div',
			);
		}

		if ( ! $protected_content->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG )->get_rule_value( MS_Model_Rule_More::CONTENT_ID ) ) {
			$fields['more_tag'] = array(
				'id' => 'more_tag',
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
				'value' => __( 'Members can read full post (beyond the More Tag)', MS_TEXT_DOMAIN ),
				'class' => 'ms-field-description',
				'wrapper' => 'div',
			);
		}

		$fields = apply_filters( 'ms_view_membership_setup_protected_content_get_tab_comment_fields', $fields );

		$rule = $membership->get_rule( 'menu' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Menu( $rule, $membership, $this->data['menu_id'] );
		$rule_list_table->prepare_items();

		$title = __( 'Comments, More Tag & Menus', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to protected Comments, More Tag & Menus to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

			<div class="ms-half space">
				<div class="inside">
					<?php MS_Helper_Html::html_element( $fields['comment'] ); ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_element( $fields['comment_rule_edit'] ); ?>
					</div>
					<?php MS_Helper_Html::html_separator( 'vertical' ); ?>
				</div>
			</div>

			<div class="ms-half">
				<div class="inside">
					<?php MS_Helper_Html::html_element( $fields['more_tag'] ); ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_element( $fields['more_tag_rule_edit'] ); ?>
					</div>
				</div>
			</div>

			<div class="ms-group">
				<form id="ms-menu-form" method="post">
					<?php MS_Helper_Html::html_element( $fields['menu_id'] ); ?>
				</form>
				<?php $rule_list_table->display(); ?>
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $fields['menu_rule_edit'] ); ?>
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

		$edit_link = array(
			'id' => 'shortcode_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected Shortcodes', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_SHORTCODE ),
		);

		$title = __( 'Shortcodes', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to protected Shortcodes to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

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
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_URL_GROUP );

		$fields = array(
			'access' => array(
				'id' => 'access',
				'title' => __( 'Members Access', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $rule->access,
				'class' => '',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'field' => 'access',
					'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
					'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
				),
			),

			'rule_value' => array(
				'id' => 'rule_value',
				'title' => __( 'Page URLs', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
				'value' => implode( PHP_EOL, $rule->rule_value ),
				'class' => 'ms-textarea-medium ms-ajax-update',
				'read_only' => true,
				'data_ms' => array(
						'membership_id' => $membership->id,
						'rule_type' => $rule->rule_type,
						'field' => 'rule_value',
						'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
						'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
				),
			),

			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $nonce,
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

			'membership_id' => array(
				'id' => 'membership_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership->id,
			),
		);

		$fields = apply_filters( 'ms_view_membership_setup_protected_content_get_tab_urlgroup_fields', $fields );

		$edit_link = array(
			'id' => 'menu_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Edit URL Group Restrictions', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_URL_GROUP ),
		);

		$title = __( 'URL Groups', MS_TEXT_DOMAIN );
		$desc = sprintf(
			__( 'Give access to protected URL Groups to %s members.', MS_TEXT_DOMAIN ),
			$this->data['membership']->name
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

			<form action="" method="post" class="ms-form">
				<?php MS_Helper_Html::settings_box( $fields ); ?>
			</form>
			<div class="clear"></div>
			<div class="ms-protection-edit-link">
				<?php MS_Helper_Html::html_element( $edit_link ); ?>
			</div>
			<?php
			MS_Helper_Html::settings_footer(
				array( $fields['step'] ),
				$this->data['show_next_button']
			);

			MS_Helper_Html::settings_box(
				array(
					array(
						'id' => 'url_test',
						'desc' => __( 'Enter an URL to test against rules in the group', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'class' => 'widefat',
					),
				),
				__( 'Test URL group', MS_TEXT_DOMAIN )
			);
			?>
			<div id="url-test-results-wrapper"></div>
		</div>
		<?php
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

}