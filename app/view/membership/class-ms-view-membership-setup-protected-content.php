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
						'desc' => array( 
							__( 'Hello and welcome to Protected Content by WPMU DEV.', MS_TEXT_DOMAIN ),
							__( 'Lets begin by setting up the content you want to protect. Please select at least 1 page or category to protect.', MS_TEXT_DOMAIN ), 
						),
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
						<?php MS_Helper_Html::html_input( $this->fields['category'] );?>
					</div>
				<?php endif; ?>
				<?php if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ): ?>
					<div class="ms-rule-wrapper">
						<?php MS_Helper_Html::html_input( $this->fields['cpt_group'] );?>
					</div>
				<?php endif; ?>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $this->fields['step'] ) ),
						true,
						$this->data['initial_setup']
				); 
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
							'rule_value' => 0,
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
								'rule_value' => 0,
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
	
	public function render_page() {
		$this->prepare_page();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_PAGE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $rule, $membership );
		$rule_list_table->prepare_items();
	
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
				</form>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $this->fields['step'] ) ),
						true,
						$this->data['initial_setup']
				); 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function prepare_page() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];
	
		$this->fields = array(
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
		$this->prepare_page();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $rule, $membership );
		$rule_list_table->prepare_items();
	
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
				</form>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $this->fields['step'] ) ),
						true,
						$this->data['initial_setup']
				); 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function render_cpt() {
		$this->prepare_page();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type( $rule, $membership );
		$rule_list_table->prepare_items();
	
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
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $this->fields['step'] ) ),
						true,
						$this->data['initial_setup']
				); 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function render_comment() {
		$this->prepare_comment();
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
					<?php MS_Helper_Html::html_input( $this->fields['comment'] );?>
				</div>
				<div class="ms-rule-wrapper">
					<?php MS_Helper_Html::html_input( $this->fields['more_tag'] );?>
				</div>
				<div class="ms-list-table-wrapper">
					<form id="ms-menu-form" method="post">
						<?php MS_Helper_Html::html_input( $this->fields['menu_id'] );?>
					</form>
					<?php $rule_list_table->display(); ?>
				</div>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $this->fields['step'] ) ),
						true,
						$this->data['initial_setup']
				); 
			?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function prepare_comment() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];
		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$rule_comment = $membership->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT );
		$this->fields = array(
			'comment' => array(
					'id' => 'comment',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
					'desc' => __( 'Members have:', MS_TEXT_DOMAIN ),
					'value' => $rule_comment->get_rule_value(),
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
			'more_tag' => array(
					'id' => 'more_tag',
					'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
					'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
					'desc' => __( 'Members can read full post (beyond the More Tag):', MS_TEXT_DOMAIN ),
					'value' => $rule_more_tag->has_access( MS_Model_Rule_More::CONTENT_ID ) ? 1 : 0,
					'field_options' => $rule_more_tag->get_options_array(),
					'class' => '',
					'data_ms' => array(
							'membership_id' => $membership->id,
							'rule_type' => MS_Model_Rule::RULE_TYPE_MORE_TAG,
							'rule_ids' => MS_Model_Rule_More::CONTENT_ID,
							'action' => $action,
							'_wpnonce' => $nonce,
					),
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
	
	public function render_shortcode() {
	
	}
	public function render_urlgroup() {
	
	}
	
	
}