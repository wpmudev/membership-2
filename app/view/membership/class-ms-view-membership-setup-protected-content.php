<?php

class MS_View_Membership_Setup_Protected_Content extends MS_View {

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
						'desc' => array( 
							__( 'Hello and welcome to Protected Content by WPMU DEV.', MS_TEXT_DOMAIN ),
							__( 'Lets begin by setting up the content you want to protect. Please select at least 1 page or category to protect.', MS_TEXT_DOMAIN ), 
						),
					) ); 
				?>
				<?php
					$active_tab = $this->data['active_tab']; 
					MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );

					/** Call the appropriate form to render. */
					$render_callback =  apply_filters( 'ms_view_membership_setup_protected_content_render_tab_callback', array( $this, 'render_tab_' . str_replace('-', '_', $active_tab ) ), $active_tab, $this->data );
					call_user_func( $render_callback );
				?>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function render_tab_category() {
		$fields = $this->get_tab_category_fields();
		$title = array();
		$desc = array();
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$title['category'] = __( 'Categories', MS_TEXT_DOMAIN );
			$desc['category'] = __( 'The easiest way to restrict content is by setting up a category that you can then use to mark content you want restricted.', MS_TEXT_DOMAIN );
		}
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$title['cpt_group'] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
			$desc['cpt_group'] = __( 'You can choose Custom Post Type(s) to be restricted (eg. Products or Events).', MS_TEXT_DOMAIN );
		}
		
		ob_start();
		?>
			<div class='ms-settings'>
				<?php MS_Helper_Html::settings_tab_header( array( 'title' => implode( ' &', $title ), 'desc' => $desc ) ); ?>
				<hr />
				<?php if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ): ?>
					<div class="ms-rule-wrapper">
						<?php MS_Helper_Html::html_input( $fields['category'] );?>
					</div>
				<?php endif; ?>
				<?php if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ): ?>
					<div class="ms-rule-wrapper">
						<?php MS_Helper_Html::html_input( $fields['cpt_group'] );?>
					</div>
				<?php endif; ?>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $fields['step'] ) ),
						true,
						! $this->data['initial_setup']
				); 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function get_tab_category_fields() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];
		
		$fields = array(
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
							'value' => 1,
							'_wpnonce' => $nonce,
							'action' => $action,
						),
				),
				'category_rule_edit' => array(
						'id' => 'category_rule_edit',
						'type' => MS_Helper_Html::TYPE_HTML_LINK,
						'value' => __( 'Manage Protected Categories', MS_TEXT_DOMAIN ),
						'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_CATEGORY ),
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
								'value' => 1,
								'_wpnonce' => $nonce,
								'action' => $action,
						),
				),
				'cpt_group_rule_edit' => array(
						'id' => 'cpt_group_rule_edit',
						'type' => MS_Helper_Html::TYPE_HTML_LINK,
						'value' => __( 'Manage Protected Custom Post Types', MS_TEXT_DOMAIN ),
						'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP ),
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
		
		return apply_filters( 'ms_view_membership_setup_protected_content_get_category_fields', $fields );
	}
	
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

		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Pages ', MS_TEXT_DOMAIN ); ?></h3>
				<div class="settings-description">
					<?php _e( 'Protect the following Pages to members only. ', MS_TEXT_DOMAIN ); ?>
				</div>
				<hr />
				
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->search_box( __( 'Search Pages', MS_TEXT_DOMAIN ), 'search' ); ?>
					<?php $rule_list_table->display(); ?>
					<?php if( empty( $this->data['protected_content'] ) ): ?>
						<div class="ms-protection-edit-link">
							<?php MS_Helper_Html::html_input( $edit_link );?>
						</div>
					<?php endif;?>
				</form>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $fields['step'] ) ),
						true,
						! $this->data['initial_setup']
				); 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
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
		return apply_filters( 'ms_view_membership_setup_protected_content_get_control_fields', $fields );
	}
	
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

		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Posts ', MS_TEXT_DOMAIN ); ?></h3>
				<div class="settings-description">
					<?php _e( 'Protect the following Posts to members only. ', MS_TEXT_DOMAIN ); ?>
				</div>
				<hr />
				
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->search_box( __( 'Search Posts', MS_TEXT_DOMAIN ), 'search' ); ?>
					<?php $rule_list_table->display(); ?>
					<?php if( empty( $this->data['protected_content'] ) ): ?>
						<div class="ms-protection-edit-link">
							<?php MS_Helper_Html::html_input( $edit_link );?>
						</div>
					<?php endif;?>
				</form>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $fields['step'] ) ),
						true,
						! $this->data['initial_setup']
				); 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
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
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Custom Post Types', MS_TEXT_DOMAIN ); ?></h3>
				<div class="settings-description">
					<?php _e( 'Protect the following Custom Post Type to members only. ', MS_TEXT_DOMAIN ); ?>
				</div>
				<hr />
				
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->search_box( __( 'Search Posts', MS_TEXT_DOMAIN ), 'search' ); ?>
					<?php $rule_list_table->display(); ?>
				</form>
				<?php if( empty( $this->data['protected_content'] ) ): ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_input( $edit_link );?>
					</div>
				<?php endif;?>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $fields['step'] ) ),
						true,
						! $this->data['initial_setup']
				); 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function render_tab_comment() {
		$fields = $this->get_tab_comment_fields();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( 'menu' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Menu( $rule, $membership, $this->data['menu_id'] );
		$rule_list_table->prepare_items();
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Comments, More Tag & Menus', MS_TEXT_DOMAIN ); ?></h3>
				<div class="settings-description">
					<div><?php echo sprintf( __( 'Give access to protected Comments, More Tag & Menus to %s members.', MS_TEXT_DOMAIN ), $this->data['membership']->name ); ?></div>
				</div>
				<hr />
				<div class="ms-rule-wrapper">
					<?php MS_Helper_Html::html_input( $fields['comment'] );?>
					<?php if( empty( $this->data['protected_content'] ) ): ?>
						<div class="ms-protection-edit-link">
							<?php MS_Helper_Html::html_input( $fields['comment_rule_edit'] );?>
						</div>
					<?php endif;?>
				</div>
				<div class="ms-rule-wrapper">
					<?php MS_Helper_Html::html_input( $fields['more_tag'] );?>
					<?php if( empty( $this->data['protected_content'] ) ): ?>
						<div class="ms-protection-edit-link">
							<?php MS_Helper_Html::html_input( $fields['more_tag_rule_edit'] );?>
						</div>
					<?php endif;?>
				</div>
				<div class="ms-list-table-wrapper">
					<form id="ms-menu-form" method="post">
						<?php MS_Helper_Html::html_input( $fields['menu_id'] );?>
					</form>
					<?php $rule_list_table->display(); ?>
					<?php if( empty( $this->data['protected_content'] ) ): ?>
						<div class="ms-protection-edit-link">
							<?php MS_Helper_Html::html_input( $fields['menu_rule_edit'] );?>
						</div>
					<?php endif;?>
				</div>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $fields['step'] ) ),
						true,
						! $this->data['initial_setup']
				); 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function get_tab_comment_fields() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];
		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$rule_comment = $membership->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT );
		$desc = ( $this->data['protected_content'] ) 
			? __( 'Visitors', MS_TEXT_DOMAIN ) 
			: __( 'Members', MS_TEXT_DOMAIN );
		$fields = array(
			'comment' => array(
					'id' => 'comment',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
					'desc' => sprintf( __( '%s have:', MS_TEXT_DOMAIN ), $desc ),
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
					'desc' => sprintf( __( '%s can read full post (beyond the More Tag):', MS_TEXT_DOMAIN ), $desc ),
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

		if( ! $this->data['protected_content'] ) {
			$protected_content = MS_Model_Membership::get_visitor_membership();
			if( MS_Model_Rule_Comment::RULE_VALUE_WRITE == $protected_content->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT )->get_rule_value( MS_Model_Rule_Comment::CONTENT_ID ) ) {
				$fields['comment'] = array(
							'id' => 'comment',
							'type' => MS_Helper_Html::TYPE_HTML_TEXT,
							'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
							'value' => __( 'Members can Read & Post comments', MS_TEXT_DOMAIN ),
							'class' => 'ms-field-description',
							'wrapper' => 'div',
				);
			}
			if( $protected_content->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG )->get_rule_value( MS_Model_Rule_More::CONTENT_ID ) ) {
				$fields['more_tag'] = array(
							'id' => 'more_tag',
							'type' => MS_Helper_Html::TYPE_HTML_TEXT,
							'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
							'value' => __( 'Members can read full post (beyond the More Tag)', MS_TEXT_DOMAIN ),
							'class' => 'ms-field-description',
							'wrapper' => 'div',
				);
			}
		}
		return apply_filters( 'ms_view_membership_setup_protected_content_get_tab_comment_fields', $fields );	
	}
	
	public function render_tab_shortcode() {
		$fields = $this->get_control_fields();
		
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_SHORTCODE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Shortcode( $rule, $membership );
		$rule_list_table->prepare_items();
		
		$title = __( 'Shortcodes', MS_TEXT_DOMAIN );
		$desc = __( 'Protect the following Custom Post Type to members only. ', MS_TEXT_DOMAIN );
		
		$edit_link = array(
				'id' => 'shortcode_rule_edit',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Manage Protected Shortcodes', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_SHORTCODE ),
		);
		ob_start();
		?>
			<div class='ms-settings'>
				<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
				<hr />
				
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
				<?php if( empty( $this->data['protected_content'] ) ): ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_input( $edit_link );?>
					</div>
				<?php endif;?>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $fields['step'] ) ),
						true,
						! $this->data['initial_setup']
				); 
			?>
		<?php
		
		$html = ob_get_clean();
		echo apply_filters( 'ms_view_membership_protected_content_render_tab_shortcode', $html );
	}
	
	public function render_tab_url_group() {
		$fields = $this->prepare_url_group_fields();
		$edit_link = array(
				'id' => 'menu_rule_edit',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Edit URL Group Restrictions', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_URL_GROUP ),
		);
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'URL Groups access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<form action="" method="post" class="ms-form">
					<?php
						MS_Helper_Html::settings_box(
							$fields, 
							__( 'Edit URL access rules', MS_TEXT_DOMAIN ) 
						);
					?>
				</form>
				<div class="clear"></div>
				<?php if( empty( $this->data['protected_content'] ) ): ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_input( $edit_link );?>
					</div>
				<?php endif;?>
				<?php 
					MS_Helper_Html::settings_footer( 
							array( 'fields' => array( $fields['step'] ) ),
							true,
							! $this->data['initial_setup']
					); 
				?>
				<?php
					MS_Helper_Html::settings_box(
						array( array( 
							'id' => 'url_test',
							'desc' => __( 'Enter an URL to test against rules in the group', MS_TEXT_DOMAIN ),
							'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
							'class' => 'widefat',
						) ), 
						__( 'Test URL group', MS_TEXT_DOMAIN )
					);
				?>
				<div id="url-test-results-wrapper"></div>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	function prepare_url_group_fields() {

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_URL_GROUP );

		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];
		
		$fields = array(
				'access' => array(
						'id' => 'access',
						'title' => __( 'Members Access', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'value' => $rule->access,
						'class' => 'ms-ajax-update',
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
						'data_ms' => array(
								'membership_id' => $membership->id,
								'rule_type' => $rule->rule_type,
								'field' => 'rule_value',
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
		return apply_filters( 'ms_view_membership_setup_protected_content_get_tab_urlgroup_fields', $fields );
	}
	
}