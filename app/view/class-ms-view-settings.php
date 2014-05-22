<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/

/**
 * Renders Membership Plugin Settings.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @uses MS_Helper_Html Helper used to create form elements and vertical navigation.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_View_Settings extends MS_View {

	const COMM_NONCE = 'comm_save_nonce';
	const GATEWAY_NONCE = 'gateway_save_nonce';
	
	const COMM_SECTION = 'comm_section';
	
	protected $model;
	
	protected $fields;
	
	protected $data;
	
	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @todo Could use callback functions to call dynamic methods from within the helper, thus
	 * creating the navigation with a single method call and passing method pointers in the $tabs array.
	 *
	 * @since 4.0.0
	 *
	 * @return object
	 */
	public function to_html() {		
		ob_start();

		/** Setup navigation tabs. */
		$tabs = array(
			'general' => array(
					'title' =>	__( 'General', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=general',
			),
			'pages' => array(
					'title' =>	__( 'Pages', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=pages',
			),
			'payment' => array(
					'title' =>	__( 'Payment', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=payment',
			),
			'messages-protection' => array(
					'title' =>	__( 'Protection Messages', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=messages-protection',
			),
			'messages-automated' => array(
					'title' =>	__( 'Automated Messages', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=messages-automated',
			),			
			'downloads' => array(
					'title' =>	__( 'Media / Downloads', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=downloads',
			),
			'repair' => array(
					'title' =>	__( 'Verify and Repair', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=repair',
			),												
		);
		
		/** Render tabbed interface. */
		?>
		<div class='ms-wrap'>
		<h2 class='ms-settings-title'><?php  _e( 'Membership Settings', MS_TEXT_DOMAIN ) ; ?></h2>		

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
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'General Settings', MS_TEXT_DOMAIN ) ; ?>	
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}
	
	public function render_pages() {
		$this->prepare_pages();
		?>
			<div class='ms-settings'>
			   	<h2><?php  _e( 'Page Settings', MS_TEXT_DOMAIN ) ; ?></h2>
				<form action="" method="post">
					<?php foreach( $this->fields as $field ): ?>
						<div class="postbox metabox-holder">
							<h3><label for="title"><?php echo $field['title'];?></label></h3>
							<div class="inside">
								<?php MS_Helper_Html::html_input( $field );?>
							</div>
						</div>
					<?php endforeach;?>
					<?php MS_Helper_Html::html_submit( array( 'id' => 'submit_pages' ) );?>
		   		</form>
			</div>
		<?php
	}
	
	public function prepare_pages() {
		$all_pages = $this->model->get_pages();
		$this->fields = array(
			'memberships' => array(
					'id' => 'memberships',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Select memberships page', MS_TEXT_DOMAIN ),
					'value' => $this->model->pages['memberships'],
					'field_options' => $all_pages,
					'class' => '',
			),
			'no_access' => array(
					'id' => 'no_access',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Select protected content page', MS_TEXT_DOMAIN ),
					'value' => $this->model->pages['no_access'],
					'field_options' => $all_pages,
					'class' => '',
			),
			'register' => array(
					'id' => 'register',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Select registration page', MS_TEXT_DOMAIN ),
					'value' => $this->model->pages['register'],
					'field_options' => $all_pages,
					'class' => '',
			),
			'registration_completed' => array(
					'id' => 'registration_completed',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Select registration completed page', MS_TEXT_DOMAIN ),
					'value' => $this->model->pages['registration_completed'],
					'field_options' => $all_pages,
					'class' => '',
			),
		);
	}
	public function render_payment() {
		$list_table = new MS_Helper_List_Table_Gateway();
		$list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h2><?php echo __( 'Payment Settings', MS_TEXT_DOMAIN ); ?></h2>
				<form action="" method="post">
					<?php $list_table->display(); ?>
				</form>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function render_messages_protection() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'Protection Messages', MS_TEXT_DOMAIN ) ; ?>
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}

	public function render_messages_automated() {
		$this->prepare_messages_automated();
		?>
		<div class='ms-settings'>
			<h2><?php  _e( 'Automated Messages', MS_TEXT_DOMAIN ) ; ?></h2>
			<form action="" method="post">
				<?php MS_Helper_Html::html_input( $this->fields['comm_type'] );?>
				<?php MS_Helper_Html::html_submit( $this->fields['load_comm'] );?>
				<p><?php echo $this->model->get_description(); ?></p>
			</form>
			<form action="" method="post">
				<?php wp_nonce_field( self::COMM_NONCE, self::COMM_NONCE ); ?>
				<?php MS_Helper_Html::html_input( $this->fields['type'] );?>
				<table class="form-table">
					<tbody>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['enabled'] );?>
							</td>
						</tr>
						<?php if( $this->model->period_enabled ) : ?>
							<tr>
								<td>
									<div class="ms-period-wrapper">
										<?php MS_Helper_Html::html_input( $this->fields['period_unit'] );?>
										<?php MS_Helper_Html::html_input( $this->fields['period_type'] );?>
									</div>
								</td>
							</tr>
						<?php endif; ?>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['subject'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<div id="ms-comm-message-wrapper">
								<?php MS_Helper_Html::html_input( $this->fields['message'] );?>
								</div>
								<div id="ms-comm-var-wrapper">
									<table>
										<tr>
											<th>Variable values</th>
											<th>Description</th>
										</tr>
										<?php foreach( $this->model->comm_vars as $var => $description ): ?>
											<tr>
												<td>
													<?php echo $var;?>
												</td>
												<td>
													<?php echo $description;?>
												</td>
											</tr>
										<?php endforeach; ?>
									</table>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['cc_enabled'] );?>
								<?php MS_Helper_Html::html_input( $this->fields['cc_email'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $this->fields['save_email'] );?>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>	
		<?php
	}
	public function prepare_messages_automated() {
		$this->fields = array(
				'comm_type' => array(
						'id' => 'comm_type',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->type,
						'field_options' => MS_Model_Communication::get_communication_type_titles(),
						'class' => '',
				),
				'load_comm' => array(
						'id' => 'load_comm',
						'value' => __( 'Load Email', MS_TEXT_DOMAIN ),
				),
				'type' => array(
						'id' => 'type',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->model->type,
				),
				'enabled' => array(
						'id' => 'enabled',
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'title' => __( 'Enabled', MS_TEXT_DOMAIN ),
						'value' => $this->model->enabled,
						'class' => '',
				),
				'period_unit' => array(
						'id' => 'period_unit',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Period after/before', MS_TEXT_DOMAIN ),
						'value' => $this->model->period['period_unit'],
						'class' => '',
				),
				'period_type' => array(
						'id' => 'period_type',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->period['period_type'],
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'subject' => array(
						'id' => 'subject',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Message Subject', MS_TEXT_DOMAIN ),
						'value' => $this->model->subject,
						'class' => '',
				),
				'message' => array(
						'id' => 'message',
						'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
// 						'title' => __( 'Message', MS_TEXT_DOMAIN ),
						'value' => $this->model->description,
						'field_options' => array( 'media_buttons' => false ),
						'class' => '',
				),
				'cc_enabled' => array(
						'id' => 'cc_enabled',
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'title' => __( 'Send copy to Administrator', MS_TEXT_DOMAIN ),
						'value' => $this->model->cc_enabled,
						'class' => '',
				),
				'cc_email' => array(
						'id' => 'cc_email',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->model->cc_email,
						'field_options' => MS_Model_Member::get_admin_user_emails(),
						'class' => '',
				),
				'save_email' => array(
						'id' => 'save_email',
						'value' => __( 'Save Automated Email', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				),
		);
	}
	public function render_downloads() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'Media / Download Settings', MS_TEXT_DOMAIN ) ; ?>
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}
	
	public function render_repair() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'Verify and Repair', MS_TEXT_DOMAIN ) ; ?>
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}	
	
		
}