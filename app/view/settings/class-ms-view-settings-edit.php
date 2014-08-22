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
class MS_View_Settings_Edit extends MS_View {

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
		$tabs = $this->data['tabs'];
		
		/** Render tabbed interface. */
		?>
		<div class='ms-wrap wrap'>
		<h2 class='ms-settings-title'><i class="fa fa-cog"></i> <?php  _e( 'Membership Settings', MS_TEXT_DOMAIN ) ; ?></h2>		

		<?php
		$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
		
		/** Call the appropriate form to render. */
		$render_callback =  apply_filters( 'ms_view_settings_edit_render_callback', array( $this, 'render_' . str_replace('-', '_', $active_tab ) ), $active_tab, $this->data );
		call_user_func( $render_callback );
		
		?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	public function render_general() {
		$this->prepare_general();
		?>
		<div class='ms-settings'>
			<h3><?php  _e( 'General Settings', MS_TEXT_DOMAIN ) ; ?></h3>	
			<div class="metabox-holder">
				<form action="" method="post">
					<?php wp_nonce_field( $this->fields['action']['value'] );?>
					<?php MS_Helper_Html::html_input( $this->fields['action'] ); ?>
					<?php 
						MS_Helper_Html::settings_box( 
							array( $this->fields['plugin_enabled'] ),
							__( 'Enable plugin', MS_TEXT_DOMAIN ) 
						); 
					?>
					<?php 
						MS_Helper_Html::settings_box( 
							array( $this->fields['default_membership_enabled'] ),
							__( 'Enable default membership level', MS_TEXT_DOMAIN ) 
						); 
					?>
					<?php 
						MS_Helper_Html::settings_box( 
							array( $this->fields['hide_admin_bar'] ),
							__( 'Hide admin bar', MS_TEXT_DOMAIN ) 
						); 
					?>
				</form>
			</div>
		</div>
		<?php
	}
	
	public function prepare_general() {
		$this->fields = array(
				'plugin_enabled' => array(
						'id' => 'plugin_enabled',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'title' => __( 'This setting enable/disable the membership plugin protection.', MS_TEXT_DOMAIN ),
						'value' => $this->model->plugin_enabled,
						'field_options' => array(
								'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
								'setting' => 'plugin_enabled',
						),
				),
				'default_membership_enabled' => array(
						'id' => 'default_membership_enabled',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'title' => __( 'This setting enable/disable default membership to logged in users without any membership.', MS_TEXT_DOMAIN ),
						'value' => $this->model->default_membership_enabled,
						'field_options' => array(
								'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
								'setting' => 'default_membership_enabled',
						),
				),
				'hide_admin_bar' => array(
						'id' => 'hide_admin_bar',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'title' => __( 'Hide admin bar for non administrator users.', MS_TEXT_DOMAIN ),
						'value' => $this->model->hide_admin_bar,
						'field_options' => array(
								'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
								'setting' => 'hide_admin_bar',
						),
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'save_general',
				),
		);
	}
	
	public function render_pages() {
		$this->prepare_pages();
		$action = array(
			'id' => 'action',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => 'create_special_page',
		);
		?>
			<div class='ms-settings'>
			   	<h3><?php  _e( 'Page Settings', MS_TEXT_DOMAIN ) ; ?></h3>
				<form action="" method="post">
					<?php
						wp_nonce_field( $action['value'] );
						MS_Helper_Html::html_input( $action );
					?>
					<?php foreach( $this->fields as $field ): ?>
						<div class="ms-settings-box-wrapper">
							<div class="ms-settings-box">
							<h3><?php echo $field['box_title'];?></h3>
							<div class="inside">
								<?php 
									MS_Helper_Html::html_input( $field );
									MS_Helper_Html::html_submit( array( 
										'id' => "create_page_{$field['id']}", 
										'value' => __('Create new page', MS_TEXT_DOMAIN ), 
										'class' => 'button button-primary ms-create-page',
									) );
								?>
								<div id="ms-settings-page-links-wrapper">
									<?php
										MS_Helper_Html::html_link( array(
											'url' => get_permalink( $field['value'] ),
											'value' => __( 'View', MS_TEXT_DOMAIN ),
										) );
									?>
									<span> | </span>	
									<?php edit_post_link( __( 'Edit', MS_TEXT_DOMAIN ), '', '', $field['value'] ); ?>
								</div>
							</div>
						</div>
					<?php endforeach;?>
					<?php MS_Helper_Html::html_submit( array( 'id' => 'submit_pages' ) );?>
		   		</form>
			</div>
		<?php
	}
	
	public function prepare_pages() {

		$pages['no_access'] = $this->model->get_special_page( MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS );
		$pages['account'] = $this->model->get_special_page( MS_Model_Settings::SPECIAL_PAGE_ACCOUNT );
		$pages['welcome'] = $this->model->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME );
		$pages['signup'] = $this->model->get_special_page( MS_Model_Settings::SPECIAL_PAGE_SIGNUP );
		
		$all_pages = $this->model->get_pages();
		$this->fields = array(
			'no_access' => array(
					'id' => MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS,
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Select protected content page', MS_TEXT_DOMAIN ),
					'value' => $pages['no_access'],
					'field_options' => $all_pages,
					'class' => '',
					'box_title' => __( 'Protected content page', MS_TEXT_DOMAIN ),
			),
			'account' => array(
					'id' => MS_Model_Settings::SPECIAL_PAGE_ACCOUNT,
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Select account page', MS_TEXT_DOMAIN ),
					'value' => $pages['account'],
					'field_options' => $all_pages,
					'class' => '',
					'box_title' => __( 'Account page', MS_TEXT_DOMAIN ),
			),
			'welcome' => array(
					'id' => MS_Model_Settings::SPECIAL_PAGE_WELCOME,
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Select registration completed page', MS_TEXT_DOMAIN ),
					'value' => $pages['welcome'],
					'field_options' => $all_pages,
					'class' => '',
					'box_title' => __( 'Welcome page', MS_TEXT_DOMAIN ),
			),
			'signup' => array(
					'id' => MS_Model_Settings::SPECIAL_PAGE_SIGNUP,
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Select signup page', MS_TEXT_DOMAIN ),
					'value' => $pages['signup'],
					'field_options' => $all_pages,
					'class' => '',
					'box_title' => __( 'Signup page', MS_TEXT_DOMAIN ),
			),
		);
	}
	public function render_payment() {

		$this->prepare_payment();
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Payment Settings', MS_TEXT_DOMAIN ); ?></h3>
				<form action="" method="post">
					<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['action'] ) ;?>
					<?php
						MS_Helper_Html::settings_box(
							array( $this->fields['currency'] ), 
							__( 'Payment currency', MS_TEXT_DOMAIN ), 
							__( 'This is the currency that will be used across all gateways. Note: Some gateways have a limited number of currencies available.', MS_TEXT_DOMAIN ),
							array( 'label_element' => 'h3' ) 
						);
					?>
					<?php
						MS_Helper_Html::settings_box(
							array( $this->fields['invoice_sender_name'] ), 
							__( 'Invoice Configuration', MS_TEXT_DOMAIN ), 
							__( 'This is the name used in the invoice.', MS_TEXT_DOMAIN ),
							array( 'label_element' => 'h3' ) 
						);
					?>
					<p>
						<?php MS_Helper_Html::html_submit( array( 'id' => 'submit_payment' ) );?>
					</p>
				</form>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function prepare_payment() {
		$this->fields = array(
			'currency' => array(
					'id' => 'currency',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Select payment currency', MS_TEXT_DOMAIN ),
					'value' => $this->model->currency,
					'field_options' => $this->model->get_currencies(),
					'class' => '',
			),
			'invoice_sender_name' => array(
					'id' => 'invoice_sender_name',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' => __( 'Invoice sender name', MS_TEXT_DOMAIN ),
					'value' => $this->model->invoice_sender_name,
					'class' => '',
			),
			'tax_name' => array(
					'id' => 'tax_name',
					'name' => 'tax[tax_name]',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' => __( 'Tax name', MS_TEXT_DOMAIN ),
					'value' => $this->model->tax['tax_name'],
					'class' => '',
			),
			'tax_rate' => array(
					'id' => 'tax_rate',
					'name' => 'tax[tax_rate]',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' => __( 'Tax rate (%)', MS_TEXT_DOMAIN ),
					'value' => $this->model->tax['tax_rate'],
					'class' => '',
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => 'save_payments',
			),
		);
	}
	public function render_gateway() {
	
		$list_table = new MS_Helper_List_Table_Gateway();
		$list_table->prepare_items();
	
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Gateway Settings', MS_TEXT_DOMAIN ); ?></h3>
				<form action="" method="post">
					<?php $list_table->display(); ?>
				</form>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	public function render_messages_protection() {
		$this->prepare_messages_protection();
		?>
		<div class='ms-settings'>
	   		<h3><?php  _e( 'Protection Messages', MS_TEXT_DOMAIN ) ; ?></h3>
	   		<form class="ms-form" action="" method="post">
				<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
				<?php MS_Helper_Html::html_input( $this->fields['action'] );?>
				<?php
					MS_Helper_Html::settings_box(
						array( $this->fields['content'] ), 
						__( 'Content protection message', MS_TEXT_DOMAIN ), 
						'',
						array( 'label_element' => 'h3' ) );
				?>
				<?php
					MS_Helper_Html::settings_box(
						array( $this->fields['shortcode'] ), 
						__( 'Shortcode protection message', MS_TEXT_DOMAIN ), 
						'',
						array( 'label_element' => 'h3' ) );
				?>
				<?php
					MS_Helper_Html::settings_box(
						array( $this->fields['more_tag'] ), 
						__( 'More tag protection message', MS_TEXT_DOMAIN ), 
						'',
						array( 'label_element' => 'h3' ) );
				?>
				<?php MS_Helper_Html::html_input( $this->fields['action'] ); ?>
				<?php MS_Helper_Html::html_input( $this->fields['submit'] ); ?>
			</form>
   		</div>
		<?php
	}

	public function prepare_messages_protection() {
		$this->fields = array(
			'content' => array(
					'id' => 'content',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
					'title' => __( 'Message displayed when not having access to a protected content.', MS_TEXT_DOMAIN ),
					'value' => $this->model->get_protection_message( MS_Model_Settings::PROTECTION_MSG_CONTENT ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
					'class' => 'ms-textarea-medium',
			),
			'shortcode' => array(
					'id' => 'shortcode',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
					'title' => __( 'Message displayed when not having access to a protected shortcode content.', MS_TEXT_DOMAIN ),
					'value' => $this->model->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
					'class' => 'ms-textarea-medium',
			),
			'more_tag' => array(
					'id' => 'more_tag',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
					'title' => __( 'Message displayed when not having access to a protected content under more tag.', MS_TEXT_DOMAIN ),
					'value' => $this->model->get_protection_message( MS_Model_Settings::PROTECTION_MSG_MORE_TAG ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
					'class' => 'ms-textarea-medium',
			),
			'submit' => array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Submit', MS_TEXT_DOMAIN ),
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => 'save_messages_protection',
			),
		);
	}
	
	public function render_messages_automated() {
		$this->prepare_messages_automated();
		?>
		<div class='ms-settings'>
			<h3><?php  _e( 'Automated Messages', MS_TEXT_DOMAIN ) ; ?></h3>
			<form id="ms-comm-type-form" action="" method="post">
				<?php MS_Helper_Html::html_input( $this->fields['comm_type'] );?>
				<p><?php echo $this->model->get_description(); ?></p>
			</form>
			<form action="" method="post">
				<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
				<?php MS_Helper_Html::html_input( $this->fields['action'] );?>
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
										</tr>
										<?php foreach( $this->model->comm_vars as $var => $description ): ?>
											<tr>
												<td>
													<?php MS_Helper_html::tooltip( $description ); ?>
													<?php echo $var; ?>
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
								<?php MS_Helper_Html::html_separator();?>
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
						'class' => 'ms-comm-subject',
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
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'save_comm',
				),
		);
	}
	
	public function render_downloads() {
		$this->prepare_downloads();
		?>
		<div class='ms-settings'>
			<h3><?php  _e( 'Media / Download Settings', MS_TEXT_DOMAIN ) ; ?></h3>	
			<div class="metabox-holder">
				<form action="" method="post">
					<?php wp_nonce_field( $this->fields['action']['value'] );?>
					<?php
						MS_Helper_Html::settings_box(
							$this->fields 
						);
					?>
				</form>
			</div>
		</div>
		<?php
	}
	public function prepare_downloads() {
		$upload_dir = wp_upload_dir();
 
		$this->fields = array(
				'protection_type' => array(
						'id' => 'protection_type',
						'name' => 'downloads[protection_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
						'title' => __( 'Protection method', MS_TEXT_DOMAIN ),
						'value' => $this->model->downloads['protection_type'],
						'field_options' => MS_Model_Rule_Media::get_protection_types(),
				),
				'upload_url' => array(
						'id' => 'mailchimp_api_test',
						'type' => MS_Helper_Html::TYPE_HTML_TEXT,
						'title' => __( 'Current upload location', MS_TEXT_DOMAIN ),
						'value' => trailingslashit( $upload_dir['baseurl'] ),
						'wrapper' => 'div',
						'class' => '',
				),
				'masked_url' => array(
						'id' => 'masked_url',
						'name' => 'downloads[masked_url]',
						'desc' => esc_html( trailingslashit( get_option( 'home' ) ) ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Masked download url', MS_TEXT_DOMAIN ),
						'value' => $this->model->downloads['masked_url'],
						'class' => '',
				),
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => wp_create_nonce( 'save_downloads' ),
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'save_downloads',
				),
				'separator2' => array(
						'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
				),
				'submit_downloads' => array(
						'id' => 'submit_downloads',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
				)
		);
	}
	
}