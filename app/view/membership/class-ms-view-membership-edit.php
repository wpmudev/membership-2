<?php

class MS_View_Membership_Edit extends MS_View {

	const MEMBERSHIP_SAVE_NONCE = 'membership_save_nonce';
	const RULE_SAVE_NONCE = 'rule_save_nonce';
	
	const MEMBERSHIP_SECTION = 'membership_section';
	const RULE_SECTION = 'rule_section';
	
	private $rule_types = array();
	protected $fields = array();
	protected $model;
	protected $section;
	

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
		
		ob_start();

		/** Render tabbed interface. */
		?>
		<div class='ms-wrap'>
		<h2 class='ms-settings-title'>Membership Settings</h2>		

		<?php
		$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
	
		/** Call the appropriate form to prepare. */
// 		call_user_func( array( $this, 'prepare_' . str_replace('-', '_', $active_tab ) ) );		
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
			<form id="setting_form" action="" method="post">
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
									<?php MS_Helper_Html::html_input( $this->fields['pay_cicle_period_unit'] );?>
									<?php MS_Helper_Html::html_input( $this->fields['pay_cicle_period_type'] );?>
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
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Period', MS_TEXT_DOMAIN ),
						'value' => $this->model->period_unit,
						'class' => '',
				),
				'period_type' => array(
						'id' => 'period_type',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'pay_cicle_period_unit' => array(
						'id' => 'pay_cicle_period_unit',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Payment Cicle', MS_TEXT_DOMAIN ),
						'value' => $this->model->pay_cicle_period_unit,
						'class' => '',
				),
				'pay_cicle_period_type' => array(
						'id' => 'pay_cicle_period_type',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->pay_cicle_period_type,
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
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Trial period', MS_TEXT_DOMAIN ),
						'value' => $this->model->trial_period_unit,
						'class' => '',
				),
				'trial_period_type' => array(
						'id' => 'trial_period_type',
						'section' => self::MEMBERSHIP_SECTION,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->trial_period_type,
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
				<h2><?php _e( 'Page access', MS_TEXT_DOMAIN ); ?></h2>
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
				<h2><?php _e( 'Category access', MS_TEXT_DOMAIN ); ?></h2>
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
				<h2><?php _e( 'Post by post access', MS_TEXT_DOMAIN ); ?></h2>
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
				<h2><?php _e( 'Comments access', MS_TEXT_DOMAIN ); ?></h2>
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
				<h2><?php _e( 'Menu access', MS_TEXT_DOMAIN ); ?></h2>
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
				<h2><?php _e( 'Media access', MS_TEXT_DOMAIN ); ?></h2>
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
			<h2><?php _e( 'Shortcode access', MS_TEXT_DOMAIN ); ?></h2>
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
				<h2><?php _e( 'URL Groups access', MS_TEXT_DOMAIN ); ?></h2>
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
		$model = $this->model->rules['dripped'];
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $model );
		$rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php _e( 'Dripped content', MS_TEXT_DOMAIN ); ?></h2>
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
}