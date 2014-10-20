<?php

class MS_View_Membership_Setup_Protected_Content extends MS_View {

	protected $data;

	public function to_html() {
		$tabs = $this->data['tabs'];

		if ( ! empty( $this->data['initial_setup'] ) ) {
			$description = array(
				__( 'Hello and welcome to Protected Content by WPMU DEV.', MS_TEXT_DOMAIN ),
				__( 'Let\'s begin by setting up the content you want to protect. Please select at least 1 page or category to protect.', MS_TEXT_DOMAIN ),
			);
		}
		else {
			$description = array(
				__( 'Choose what content of your site is protected.', MS_TEXT_DOMAIN ),
				__( 'Unprotected Content is available for everyone while Protected Content can be assigned to a Membership.', MS_TEXT_DOMAIN ),
			);
		}

		/** Render tabbed interface. */
		ob_start();
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Select Content to Protect', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'fa fa-pencil-square',
					'desc' => $description,
				)
			);

			$active_tab = $this->data['active_tab'];
			MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );

			// Call the appropriate form to render.
			$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
			if ( method_exists( $this, $callback_name ) ) {
				$render_callback = array( $this, $callback_name );
			}
			else {
				$render_callback = array( $this, 'render_generic_tab' );
			}
			$render_callback = apply_filters(
				'ms_view_membership_setup_protected_content_render_tab_callback',
				$render_callback,
				$active_tab,
				$this
			);

			$html = call_user_func( $render_callback );
			$html = apply_filters( 'ms_view_membership_protected_content_' . $callback_name, $html );
			echo $html;
			?>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Echo default tab contents for the specified fields.
	 *
	 * The function is optimized to avoid redundancy and therefore can contain
	 * only tag-select components.
	 *
	 * @since  1.0.0
	 */
	protected function render_generic_tab( $title = '', $desc = '', $contents = array() ) {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		if ( ! is_array( $title ) ) { $title = array( $title ); }
		if ( ! is_array( $desc ) ) { $desc = array( $desc ); }

		$field_step = array(
			'id' => 'step',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $this->data['step'],
		);

		$args = func_get_args();
		$arg_count = func_num_args();
		$odd = true;

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => implode( ' & ', $title ), 'desc' => $desc )
			); ?>
			<div class="ms-separator"></div>

			<?php for ( $page = 2; $page < $arg_count; $page += 1 ) :
				$item = $args[ $page ];
				if ( empty( $item ) ) { continue; }

				$rule = $membership->get_rule( $item['type'] );
				$f_title = sprintf(
					__( 'Protect %s:', MS_TEXT_DOMAIN ),
					$item['label_plural']
				);
				$f_title_sel = '<i class="ms-img ms-img-lock"></i> ' .
					sprintf(
						__( 'Protected %s', MS_TEXT_DOMAIN ),
						$item['label_plural']
					);
				$f_txt_empty = sprintf(
					__( 'No %s available', MS_TEXT_DOMAIN ),
					$item['label_plural']
				);
				$f_placeholder = sprintf(
					__( 'Choose a %s', MS_TEXT_DOMAIN ),
					$item['label_single']
				);
				$f_txt_button = sprintf(
					__( 'Protect %s', MS_TEXT_DOMAIN ),
					$item['label_single']
				);

				$field = array(
					'id' => $item['id'],
					'type' => MS_Helper_Html::INPUT_TYPE_TAG_SELECT,
					'title' => $f_title,
					'title_selected' => $f_title_sel,
					'value' => $rule->rule_value,
					'field_options' => $rule->get_content_array(),
					'data_placeholder' => $f_placeholder,
					'button_text' => $f_txt_button,
					'empty_text' => $f_txt_empty,
					'data_ms' => array(
						'membership_id' => $membership->id,
						'rule_type' => $item['type'],
						'value' => 1,
						'values' => array(),
						'_wpnonce' => $nonce,
						'action' => $action,
					),
				);

				$field = apply_filters(
					'ms_view_membership_setup_protected_content_' . $item['type'] . '_field',
					$field
				);

				?>
				<div class="ms-half space">
					<div class="inside">
						<?php
						MS_Helper_Html::html_element( $field );
						if ( $odd ) {
							MS_Helper_Html::html_separator( 'vertical' );
							$odd = false;
						} else {
							$odd = true;
						}
						?>
					</div>
				</div>
			<?php endfor; ?>
		</div>

		<?php
		MS_Helper_Html::settings_footer(
			array( $field_step ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}


	/* ====================================================================== *
	 *                               CATEGORY
	 * ====================================================================== */

	public function render_tab_category() {
		$title = array();
		$desc = array();
		$field1 = false;
		$field2 = false;

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$title[] = __( 'Categories', MS_TEXT_DOMAIN );
			$desc[] = __( 'The easiest way to restrict content is by setting up a category that you can then use to mark content you want restricted.', MS_TEXT_DOMAIN );
			$field1 = array(
				'type' => MS_Model_Rule::RULE_TYPE_CATEGORY,
				'id' => 'category',
				'label_single' => __( 'Category', MS_TEXT_DOMAIN ),
				'label_plural' => __( 'Categories', MS_TEXT_DOMAIN ),
			);
			MS_Model_Rule::RULE_TYPE_CATEGORY;
		}

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$title[] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
			$desc[] = __( 'You can choose Custom Post Type(s) to be restricted (eg. Products or Events).', MS_TEXT_DOMAIN );
			$field2 = array(
				'type' => MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
				'id' => 'cpt',
				'label_single' => __( 'Custom Post Type', MS_TEXT_DOMAIN ),
				'label_plural' => __( 'Custom Post Types', MS_TEXT_DOMAIN ),
			);
		}

		return $this->render_generic_tab( $title, $desc, $field1, $field2 );
	}

	/* ====================================================================== *
	 *                               PAGE
	 * ====================================================================== */

	public function render_tab_page() {
		$title = __( 'Pages', MS_TEXT_DOMAIN );
		$desc = __( 'Protected Pages are available for members only.', MS_TEXT_DOMAIN );

		$field = array(
			'type' => MS_Model_Rule::RULE_TYPE_PAGE,
			'id' => 'page',
			'label_single' => __( 'Page', MS_TEXT_DOMAIN ),
			'label_plural' => __( 'Pages', MS_TEXT_DOMAIN ),
		);

		return $this->render_generic_tab( $title, $desc, $field );
	}

	/* ====================================================================== *
	 *                               POSTS
	 * ====================================================================== */

	public function render_tab_post() {
		$title = __( 'Posts', MS_TEXT_DOMAIN );
		$desc = __( 'Protected Pages are available for members only.', MS_TEXT_DOMAIN );

		$field = array(
			'type' => MS_Model_Rule::RULE_TYPE_POST,
			'id' => 'post',
			'label_single' => __( 'Post', MS_TEXT_DOMAIN ),
			'label_plural' => __( 'Posts', MS_TEXT_DOMAIN ),
		);

		return $this->render_generic_tab( $title, $desc, $field );
	}

	/* ====================================================================== *
	 *                               CPT
	 * ====================================================================== */

	public function render_tab_cpt() {
		$title = __( 'Custom Post Types', MS_TEXT_DOMAIN );
		$desc = __( 'Protected Custom Post Types are available for members only.', MS_TEXT_DOMAIN );

		$field = array(
			'type' => MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE,
			'id' => 'post',
			'label_single' => __( 'CPT', MS_TEXT_DOMAIN ),
			'label_plural' => __( 'CPTs', MS_TEXT_DOMAIN ),
		);

		return $this->render_generic_tab( $title, $desc, $field );
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
		$fields = $this->get_tab_comment_fields();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( 'menu' );

		$title = __( 'Comments, More Tag & Menus', MS_TEXT_DOMAIN );
		$desc = __( 'Protected Comments, More Tag & Menus are available for members only.', MS_TEXT_DOMAIN );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => $title, 'desc' => $desc )
			); ?>
			<div class="ms-separator"></div>

			<div class="ms-group">
				<div class="ms-half">
					<div class="inside">
						<?php MS_Helper_Html::html_element( $fields['comment'] ); ?>
					</div>
				</div>

				<div class="ms-half">
					<div class="inside">
						<?php MS_Helper_Html::html_element( $fields['more_tag'] ); ?>
					</div>
				</div>
			</div>

			<div class="ms-separator"></div>

			<div class="ms-group">
				<div class="inside">
					<form id="ms-menu-form" method="post">
					<?php MS_Helper_Html::html_element( $fields['menu_data'] ); ?>
					</form>
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

	/**
	 * Prepare tab fields for:
	 * Comments, More tag, Menus
	 *
	 * @since  1.0.0
	 */
	public function get_tab_comment_fields() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];

		$rule_comment = $membership->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT );
		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$rule_menu = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MENU );

		$field_menu_id = array(
			'id' => 'menu_id',
			'value' => $this->data['menu_id'],
			'field_options' => $this->data['menus'],
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
		);
		$menu_selector = MS_Helper_Html::html_element( $field_menu_id, true );

		$fields = array(
			'comment' => array(
				'id' => 'comment',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Visitors have:', MS_TEXT_DOMAIN ),
				'value' => $rule_comment->get_rule_value( MS_Model_Rule_Comment::CONTENT_ID ),
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
				'desc' => __( 'Only Members can read full post (beyond the More Tag):', MS_TEXT_DOMAIN ),
				'value' => $rule_more_tag->get_rule_value( MS_Model_Rule_More::CONTENT_ID ) ? 1 : 0,
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

			'menu_data' => array(
				'id' => 'menu_data',
				'type' => MS_Helper_Html::INPUT_TYPE_TAG_SELECT,
				'title' => sprintf(
					__( 'Protect Menu-Items of %s', MS_TEXT_DOMAIN ),
					$menu_selector
				),
				'title_selected' => '<i class="ms-img ms-img-lock"></i> ' . __( 'Protected Menu-Items:', MS_TEXT_DOMAIN ),
				'value' => $rule_menu->rule_value,
				'field_options' => $rule_menu->get_options_array( array( 'menu_id' => $this->data['menu_id'] ) ),
				'data_placeholder' => __( 'Choose a Menu-Item', MS_TEXT_DOMAIN ),
				'button_text' => __( 'Protect Menu-Item', MS_TEXT_DOMAIN ),
				'empty_text' => __( 'No Menu-Items available', MS_TEXT_DOMAIN ),
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_MENU,
					'value' => 1,
					'menu_id' => $this->data['menu_id'],
					'values' => array(),
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
			/*
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action,
			),
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $nonce,
			),
			*/
		);

		return apply_filters( 'ms_view_membership_setup_protected_content_get_tab_comment_fields', $fields );
	}

	/* ====================================================================== *
	 *                               SHORTCODE
	 * ====================================================================== */

	public function render_tab_shortcode() {
		$title = __( 'Shortcodes', MS_TEXT_DOMAIN );
		$desc = __( 'Protected Shortcodes are available for members only.', MS_TEXT_DOMAIN );

		$field = array(
			'type' => MS_Model_Rule::RULE_TYPE_SHORTCODE,
			'id' => 'shortcode',
			'label_single' => __( 'Shortcode', MS_TEXT_DOMAIN ),
			'label_plural' => __( 'Shortcodes', MS_TEXT_DOMAIN ),
		);

		return $this->render_generic_tab( $title, $desc, $field );
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

			'strip_query_string' => array(
				'id' => 'strip_query_string',
				'title' => __( 'Strip query strings from URL', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $rule->strip_query_string,
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'field' => 'strip_query_string',
					'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
					'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
				),
			),

			'is_regex' => array(
				'id' => 'is_regex',
				'title' => __( 'Is regular expression', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $rule->is_regex,
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'field' => 'is_regex',
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
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'field' => 'rule_value',
					'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
					'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
				),
			),
			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
			/*
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
			'membership_id' => array(
				'id' => 'membership_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership->id,
			),
			*/
		);

		$fields = apply_filters( 'ms_view_membership_setup_protected_content_get_tab_urlgroup_fields', $fields );

		$edit_link = array(
			'id'    => 'menu_rule_edit',
			'type'  => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Edit URL Group Restrictions', MS_TEXT_DOMAIN ),
			'url'   => sprintf(
				'admin.php?page=%s&tab=%s',
				MS_Controller_Plugin::MENU_SLUG . '-setup',
				MS_Model_Rule::RULE_TYPE_URL_GROUP
			),
		);

		$title = __( 'URL Groups', MS_TEXT_DOMAIN );
		$desc = __( 'Protected URLs can be accessed by members only. ', MS_TEXT_DOMAIN );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

			<form action="" method="post" class="ms-form ms-group">
				<?php MS_Helper_Html::settings_box( $fields ); ?>
			</form>

			<?php
			MS_Helper_Html::settings_footer(
				array( $fields['step'] ),
				$this->data['show_next_button']
			);

			MS_Helper_Html::settings_box(
				array(
					array(
						'id'    => 'url_test',
						'title'  => __( 'Enter an URL to test against rules in the group', MS_TEXT_DOMAIN ),
						'type'  => MS_Helper_Html::INPUT_TYPE_TEXT,
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
}