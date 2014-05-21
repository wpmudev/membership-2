<?php

class MS_View_Membership_Edit extends MS_View {

	const MEMBERSHIP_SAVE_NONCE = 'membership_save_nonce';
	const DRIPPED_NONCE = 'dripped_nonce';
	
	const MEMBERSHIP_SECTION = 'membership_section';
	const DRIPPED_SECTION = 'item';
	
	protected $fields = array();
	
	protected $model;
	
	protected $section;
	
	protected $title;
	
	protected $post_by_post_option;
	
	public function to_html() {
		$tabs = array(
				'general' => array(
						'title' =>	__( 'General', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=general&membership_id=' . $this->model->id,
				),
				'page' => array(
						'title' => __( 'Pages', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=page&membership_id=' . $this->model->id,
				),
				'category' => array(
						'title' => __( 'Categories', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=category&membership_id=' . $this->model->id,
				),
				'post' => array(
						'title' => __( 'Post by post', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=post&membership_id=' . $this->model->id,
				),
				'comment' => array(
						'title' => __( 'Comments', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=comment&membership_id=' . $this->model->id,
				),
				'media' => array(
						'title' => __( 'Media', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=media&membership_id=' . $this->model->id,
				),
				'menu' => array(
						'title' => __( 'Menus', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=menu&membership_id=' . $this->model->id,
				),
				'shortcode' => array(
						'title' => __( 'Shortcodes', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=shortcode&membership_id=' . $this->model->id,
				),
				'urlgroup' => array(
						'title' => __( 'URL Groups', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=urlgroup&membership_id=' . $this->model->id,
				),
				'dripped' => array(
						'title' => __( 'Dripped Content', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=dripped&membership_id=' . $this->model->id,
				),
			);
		/**
		 * Just general tab in the first access.
		 */
		if( ! $this->model->id ){
			$tabs = array( 'general' => $tabs['general'] );
		}
		/**
		 * Enable / Disable post by post tab
		 */
		if( $this->post_by_post_option ) {
			unset( $tabs['category'] );
		}
		else {
			unset( $tabs['post'] );
		}
		ob_start();
		
		$this->title = __( 'Create New Membership', MS_TEXT_DOMAIN );
		if( $this->model->name ) {
			$this->title = $this->model->name;
			if( false === stripos( $this->title, 'membership' ) ) {
				$this->title .= ' Membership';
			}
		}
		/** Render tabbed interface. */
		?>
		<div class='ms-wrap'>
		<h1 class='ms-settings-title'><?php echo $this->title; ?></h1>		

		<?php
		$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
	
		/** Call the appropriate form to render. */		
		call_user_func( array( $this, 'render_' . str_replace('-', '_', $active_tab ) ) );

		?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function render_general() {
		$this->prepare_general();
		ob_start();
		?>
		<div class='ms-settings'>
			<h2><?php _e( 'General Membership Settings', MS_TEXT_DOMAIN ); ?></h2>
			<form class="ms-form" action="" method="post">
				<?php wp_nonce_field( self::MEMBERSHIP_SAVE_NONCE, self::MEMBERSHIP_SAVE_NONCE ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['name'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['description'] );?>
							</td>
						</tr>
						<?php if( ! $this->model->visitor_membership ): ?>
							<tr>
								<td>
									<?php MS_Helper_Html::html_input( $this->fields['price'] );?>
								</td>
							</tr>
							<tr>
								<td>
									<?php MS_Helper_Html::html_input( $this->fields['membership_type'] );?>
								</td>
							</tr>
							<tr>
								<td>
									<div id="ms-membership-type-finite-wrapper" class="ms-period-wrapper ms-membership-type">
										<?php MS_Helper_Html::html_input( $this->fields['period_unit'] );?>
										<?php MS_Helper_Html::html_input( $this->fields['period_type'] );?>
									</div>
									<div id="ms-membership-type-recurring-wrapper" class="ms-period-wrapper ms-membership-type">
										<?php MS_Helper_Html::html_input( $this->fields['pay_cycle_period_unit'] );?>
										<?php MS_Helper_Html::html_input( $this->fields['pay_cycle_period_type'] );?>
									</div>
									<div id="ms-membership-type-date-range-wrapper" class="ms-membership-type">
										<?php MS_Helper_Html::html_input( $this->fields['period_date_start'] );?>
										<span> to </span>
										<?php MS_Helper_Html::html_input( $this->fields['period_date_end'] );?>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div id="ms-membership-on-end-membership-wrapper">
										<?php MS_Helper_Html::html_input( $this->fields['on_end_membership_id'] );?>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<?php MS_Helper_Html::html_input( $this->fields['trial_period_enabled'] );?>
									<div id="ms-trial-period-wrapper">
										<?php MS_Helper_Html::html_input( $this->fields['trial_price'] );?>
										<div class="ms-period-wrapper">
											<?php MS_Helper_Html::html_input( $this->fields['trial_period_unit'] );?>
											<?php MS_Helper_Html::html_input( $this->fields['trial_period_type'] );?>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<?php MS_Helper_Html::html_submit();?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
			<div class="clear"></div>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function prepare_general() {
	
		$this->fields = array(
				'name' => array(
						'id' => 'name',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Name', MS_TEXT_DOMAIN ),
						'value' => $this->model->name,
						'class' => '',
				),
				'description' => array(
						'id' => 'description',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
						'title' => __( 'Description', MS_TEXT_DOMAIN ),
						'value' => $this->model->description,
						'class' => '',
				),
				'price' => array(
						'id' => 'price',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Price', MS_TEXT_DOMAIN ),
						'value' => $this->model->price,
						'class' => '',
				),
				'membership_type' => array(
						'id' => 'membership_type',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Membership type', MS_TEXT_DOMAIN ),
						'value' => $this->model->membership_type,
						'field_options' => MS_Model_Membership::get_membership_types(),
						'class' => '',
				),
				'period_unit' => array(
						'id' => 'period_unit',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[period][period_unit]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Period', MS_TEXT_DOMAIN ),
						'value' => $this->model->period['period_unit'],
						'class' => '',
				),
				'period_type' => array(
						'id' => 'period_type',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[period][period_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->period['period_type'],
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'pay_cycle_period_unit' => array(
						'id' => 'pay_cycle_period_unit',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[pay_cycle_period][period_unit]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Payment Cicle', MS_TEXT_DOMAIN ),
						'value' => $this->model->pay_cycle_period['period_unit'],
						'class' => '',
				),
				'pay_cycle_period_type' => array(
						'id' => 'pay_cycle_period_type',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[pay_cycle_period][period_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->pay_cycle_period['period_type'],
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'period_date_start' => array(
						'id' => 'period_date_start',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Date range', MS_TEXT_DOMAIN ),
						'value' => $this->model->period_date_start,
						'class' => '',
				),
				'period_date_end' => array(
						'id' => 'period_date_end',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $this->model->period_date_end,
						'class' => '',
				),
				'on_end_membership_id' => array(
						'id' => 'on_end_membership_id',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'After membership ends, change to', MS_TEXT_DOMAIN ),
						'value' => $this->model->on_end_membership_id,
						'field_options' => MS_Model_Membership::get_membership_names(),
						'class' => '',
				),
				'trial_period_enabled' => array(
						'id' => 'trial_period_enabled',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'title' => __( 'Trial period', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_period_enabled,
						'class' => '',
				),
				'trial_price' => array(
						'id' => 'trial_price',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Trial price', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_price,
						'class' => '',
				),
				'trial_period_unit' => array(
						'id' => 'trial_period_unit',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[trial_period][period_unit]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Trial period', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_period['period_unit'],
						'class' => '',
				),
				'trial_period_type' => array(
						'id' => 'trial_period_type',
// 						'section' => self::MEMBERSHIP_SECTION,
						'name' =>  self::MEMBERSHIP_SECTION. '[trial_period][period_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->trial_period['period_type'],
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->model->id,
				),
	
		);
	}
	
	public function render_page() {
		$model = $this->model->rules['page'];
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php echo __( 'Page access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h2>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
		
	public function render_category() {
		$model = $this->model->rules['category'];
		$rule_list_table = new MS_Helper_List_Table_Rule_Category( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php echo __( 'Category access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h2>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_post() {
		$model = array( 
			'post' => $this->model->rules['post'], 
			'category'	=> $this->model->rules['category'],
		);
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php echo __( 'Post by post access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h2>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}

	public function render_comment() {
		$model = $this->model->rules['comment'];
		$rule_list_table = new MS_Helper_List_Table_Rule_Comment( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php echo __( 'Comments access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h2>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
		
	public function render_menu() {
		$model = $this->model->rules['menu'];
		$rule_list_table = new MS_Helper_List_Table_Rule_Menu( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php echo __( 'Menu access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h2>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_media() {
		$model = $this->model->rules['media'];
		$rule_list_table = new MS_Helper_List_Table_Rule_Media( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php echo __( 'Media access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h2>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	public function render_shortcode() {
		$model = $this->model->rules['shortcode'];
		$rule_list_table = new MS_Helper_List_Table_Rule_Shortcode( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
		<div class='ms-settings'>
			<h2><?php echo __( 'Shortcode access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h2>
			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->display(); ?>
			</form>
		</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_urlgroup() {
		$model = $this->model->rules['url_group'];
		$rule_list_table = new MS_Helper_List_Table_Rule_Url_Group( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php echo __( 'URL Groups access for ', MS_TEXT_DOMAIN ) . $this->title; ?></h2>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_dripped() {
		$model = array(
			'post' => $this->model->rules['post'],
			'category'	=> $this->model->rules['category'],
			'page'	=> $this->model->rules['page'],
		);
		$this->prepare_dripped( $model );
		
		$rule_list_table = new MS_Helper_List_Table_Rule_Dripped( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php echo __( 'Dripped content for ', MS_TEXT_DOMAIN ) . $this->title; ?></h2>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php wp_nonce_field( self::DRIPPED_NONCE, self::DRIPPED_NONCE ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['membership_copy'] );?>
					<?php MS_Helper_Html::html_submit( $this->fields['copy_dripped'] );?>
				</form>
				<form id="add_form">
				<table class="form-table">
					<tbody>
						<tr>
							<td id="ms-content-type-wrapper">
								<?php MS_Helper_Html::html_input( $this->fields['type'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<div id="ms-rule-type-post-wrapper" class="ms-rule-type-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['posts'] );?>
								</div>
								<div id="ms-rule-type-page-wrapper" class="ms-rule-type-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['pages'] );?>
								</div>
							</td>
						</tr>							
						<tr>
							<td>
								<div class="ms-period-wrapper">
									<?php MS_Helper_Html::html_input( $this->fields['period_unit'] );?>
									<?php MS_Helper_Html::html_input( $this->fields['period_type'] );?>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['btn_add'] );?>
							</td>
						</tr>
					</tbody>
				</table>
				</form>
				<form action="" method="post">
					<?php MS_Helper_Html::html_input( $this->fields['membership_id'] );?>
					<?php //MS_Helper_Html::html_input( $this->fields['action'] );?>	
					<?php $rule_list_table->display(); ?>
					<?php MS_Helper_Html::html_submit( $this->fields['dripped_submit'] );?>
				</form>
				<script id="dripped_template" type="text/x-jquery-tmpl">
					<tr class="${css_class}">
						<td class="title column-title">
							<input type="hidden" id="${full_id}" name="item[${counter}][id]" value="${id}">
							${title}
						</td>
						<td class="dripped column-dripped">
							${period_unit} ${period_type}
							<input type="hidden" name="item[${counter}][period_unit]" value="${period_unit}">
							<input type="hidden" name="item[${counter}][period_type]" value="${period_type}">
						</td>
						<td class="type column-type">
							${type}
							<input type="hidden" name="item[${counter}][type]" value="${type}">
						</td>
						<td class="delete column-delete">
							<button class="ms-delete" type="button">Delete</button>
						</td>
					</tr>
				</script>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function prepare_dripped( $model ) {
		$membership_copy = MS_Model_Membership::get_membership_names();
		unset( $membership_copy[ $this->model->id ] );
		
		$this->fields = array(
				'type' => array(
						'id' => 'type',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
						'title' => __( 'Select content type', MS_TEXT_DOMAIN ),
						'field_options' => array( 
							'post' => __( 'Post', MS_TEXT_DOMAIN ), 
							'page' => __( 'Page', MS_TEXT_DOMAIN ), 
						),
						'value' => 'post',
						'class' => '',
				),
				'posts' => array(
						'id' => 'posts',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Select Post', MS_TEXT_DOMAIN ),
						'value' => 0,
						'field_options' =>$model['post']->get_content_array(),
						'class' => 'ms-radio-rule-type chosen-select',
				),
				'pages' => array(
						'id' => 'pages',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Select Page', MS_TEXT_DOMAIN ),
						'value' => 0,
						'field_options' => $model['page']->get_content_array(),
						'class' => 'ms-radio-rule-type chosen-select',
				),
				'period_unit' => array(
						'id' => 'period_unit',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Days/Months/Years until the content becomes available', MS_TEXT_DOMAIN ),
						'value' => 1,
						'class' => '',
				),
				'period_type' => array(
						'id' => 'period_type',
						'section' => self::DRIPPED_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => null,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->model->id,
				),
				'btn_add' => array(
						'id' => 'btn_add',
						'section' => self::DRIPPED_SECTION,
						'value' => __('Add', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'class' => 'button-primary',
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'dripped',
				),
				'dripped_submit' => array(
						'id' => 'dripped_submit',
						'value' => __('Save Changes', MS_TEXT_DOMAIN ),
				),
				'membership_copy' => array(
						'id' => 'membership_copy',
						'title' => __('Copy dripped content schedule from another membership', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => null,
						'field_options' => $membership_copy,
						'class' => '',
				),
				'copy_dripped' => array(
						'id' => 'copy_dripped',
						'value' => __('Copy', MS_TEXT_DOMAIN ),
				),

		);
	}
}