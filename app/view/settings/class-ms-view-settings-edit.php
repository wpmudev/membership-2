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
 * @since 1.0
 *
 * @return object
 */
class MS_View_Settings_Edit extends MS_View {
	
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
		$render_callback =  apply_filters( 'ms_view_settings_edit_render_callback', array( $this, 'render_tab_' . str_replace('-', '_', $active_tab ) ), $active_tab, $this->data );
		call_user_func( $render_callback );
		
		?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	public function render_tab_general() {
		$fields = $this->prepare_general_fields();
		?>
		<div class='ms-settings'>
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => __( 'General Settings', MS_TEXT_DOMAIN ) ) ); ?>
			<form action="" method="post">
				<?php 
					MS_Helper_Html::settings_box( 
						array( $fields['plugin_enabled'] ),
						__( 'Enable plugin', MS_TEXT_DOMAIN ) 
					); 
				?>
				<?php 
					MS_Helper_Html::settings_box( 
						array( $fields['hide_admin_bar'] ),
						__( 'Hide admin bar', MS_TEXT_DOMAIN ) 
					); 
				?>
				<?php 
					MS_Helper_Html::settings_box( 
						array( $fields['initial_setup'] ),
						__( 'Enable wizard', MS_TEXT_DOMAIN ) 
					); 
				?>
			</form>
		</div>
		<?php
	}
	
	public function prepare_general_fields() {
		$settings = $this->data['settings'];
		$fields = array(
				'plugin_enabled' => array(
						'id' => 'plugin_enabled',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'title' => __( 'This setting enable/disable the membership plugin protection.', MS_TEXT_DOMAIN ),
						'value' => $settings->plugin_enabled,
						'field_options' => array(
								'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
								'setting' => 'plugin_enabled',
						),
				),
				'hide_admin_bar' => array(
						'id' => 'hide_admin_bar',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'title' => __( 'Hide admin bar for non administrator users.', MS_TEXT_DOMAIN ),
						'value' => $settings->hide_admin_bar,
						'field_options' => array(
								'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
								'setting' => 'hide_admin_bar',
						),
				),
				'initial_setup' => array(
						'id' => 'initial_setup',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'title' => __( 'Enable wizard.', MS_TEXT_DOMAIN ),
						'value' => $settings->initial_setup,
						'field_options' => array(
								'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
								'setting' => 'initial_setup',
						),
				),
		);
		return apply_filters( 'ms_view_settings_prepare_general_fields', $fields );
	}
	
	public function render_tab_pages() {
		$fields = $this->prepare_pages_fields();
		
		?>
		<div class='ms-settings'>
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => __( 'Page Settings', MS_TEXT_DOMAIN ) ) ); ?>
			<form action="" method="post">
				<?php
					MS_Helper_Html::html_input( $fields['control']['action'] );
					MS_Helper_Html::html_input( $fields['control']['nonce'] );
					MS_Helper_Html::html_input( $fields['page_urls'] );
					MS_Helper_Html::html_input( $fields['page_edit_urls'] );
				?>
				<?php foreach( $fields['pages'] as $field ): ?>
					<?php MS_Helper_Html::settings_box_header( );?>
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
									'id' => "url_page_{$field['id']}",
									'url' => get_permalink( $field['value'] ),
									'value' => __( 'View', MS_TEXT_DOMAIN ),
								) );
							?>
							<span> | </span>
							<?php
								MS_Helper_Html::html_link( array(
									'id' => "edit_url_page_{$field['id']}",
									'url' => get_edit_post_link( $field['value'] ),
									'value' => __( 'Edit', MS_TEXT_DOMAIN ),
								) );
							?>	
						</div>
					<?php MS_Helper_Html::settings_box_footer();?>
				<?php endforeach;?>
	   		</form>
	   		<?php MS_Helper_Html::settings_footer( null, false, true ); ?>
		</div>
		<?php
	}
	
	public function prepare_pages_fields() {

		$settings = $this->data['settings'];
		$pages['no_access'] = $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS );
		$pages['account'] = $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_ACCOUNT );
		$pages['welcome'] = $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME );
		$pages['signup'] = $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_SIGNUP );
		
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING;
		$nonce = wp_create_nonce( $action );
		
		$all_pages = $settings->get_pages();
		$page_urls = array();
		$page_edit_urls = array();
		foreach( $all_pages as $id => $page ) {
			$page_urls[ $id ] = get_permalink( $id );
			$page_edit_urls[ $id ] = get_edit_post_link( $id );
		}
		 
		$fields = array(
			'pages' => array(
				'no_access' => array(
						'id' => MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Select protected content page', MS_TEXT_DOMAIN ),
						'value' => $pages['no_access'],
						'field_options' => $all_pages,
						'class' => 'chosen-select ms-ajax-update',
						'data_ms' => array(
							'field' => 'page_no_access',
							'action' => $action,
							'_wpnonce' => $nonce,
						),
				),
				'account' => array(
						'id' => MS_Model_Settings::SPECIAL_PAGE_ACCOUNT,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Select account page', MS_TEXT_DOMAIN ),
						'value' => $pages['account'],
						'field_options' => $all_pages,
						'class' => 'chosen-select ms-ajax-update',
						'data_ms' => array(
								'field' => 'page_account',
								'action' => $action,
								'_wpnonce' => $nonce,
						),
				),
				'welcome' => array(
						'id' => MS_Model_Settings::SPECIAL_PAGE_WELCOME,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Select registration completed page', MS_TEXT_DOMAIN ),
						'value' => $pages['welcome'],
						'field_options' => $all_pages,
						'class' => 'chosen-select ms-ajax-update',
						'data_ms' => array(
								'field' => 'page_welcome',
								'action' => $action,
								'_wpnonce' => $nonce,
						),
				),
				'signup' => array(
						'id' => MS_Model_Settings::SPECIAL_PAGE_SIGNUP,
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Select signup page', MS_TEXT_DOMAIN ),
						'value' => $pages['signup'],
						'field_options' => $all_pages,
						'class' => 'chosen-select ms-ajax-update',
						'data_ms' => array(
								'field' => 'page_signup',
								'action' => $action,
								'_wpnonce' => $nonce,
						),
				),
			),
			'control' => array(
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'create_special_page',
				),
				'nonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => wp_create_nonce( 'create_special_page' ),
				),
			),
			'page_urls' => array(
					'id' => 'page_urls',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $page_urls,
					'class' => 'ms-hidden',
			),
			'page_edit_urls' => array(
					'id' => 'page_edit_urls',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $page_edit_urls,
					'class' => 'ms-hidden',
			),
			
		);

		return apply_filters( 'ms_view_settings_prepare_pages_fields', $fields );
	}
	public function render_tab_payment() {
		ob_start();
		?>
		<div class='ms-settings'>
			<div id="ms-payment-settings-wrapper">
			<?php 
				$view = MS_Factory::create( 'MS_View_Settings_Payment' );
				$view->render();
			?>
			</div>
			<?php MS_Helper_Html::settings_footer( null, false, true ); ?>
		</div>
		<?php 
		$html = ob_get_clean();
		echo $html;
	}
	
	public function render_tab_messages_protection() {
		$fields = $this->prepare_protection_messages_fields();
		?>
		<div class='ms-settings'>
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => __( 'Protection Messages', MS_TEXT_DOMAIN ) ) ); ?>
	   		<form class="ms-form" action="" method="post">
				<?php
					MS_Helper_Html::settings_box(
						array( $fields['content'] ), 
						__( 'Content protection message', MS_TEXT_DOMAIN ) 
					);
				?>
				<?php
					MS_Helper_Html::settings_box(
						array( $fields['shortcode'] ), 
						__( 'Shortcode protection message', MS_TEXT_DOMAIN ) 
					);
				?>
				<?php
					MS_Helper_Html::settings_box(
						array( $fields['more_tag'] ), 
						__( 'More tag protection message', MS_TEXT_DOMAIN ) 
					);
				?>
				<?php MS_Helper_Html::settings_footer( null, false, true ); ?>
			</form>
   		</div>
		<?php
	}

	public function prepare_protection_messages_fields() {
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_PROTECTION_MSG;
		$nonce = wp_create_nonce( $action );
		$settings = $this->data['settings'];
		
		$fields = array(
			'content' => array(
					'id' => 'content',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
					'title' => __( 'Message displayed when not having access to a protected content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_CONTENT ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
					'class' => 'ms-textarea-medium ms-ajax-update',
					'data_ms' => array(
							'type' => 'content',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'shortcode' => array(
					'id' => 'shortcode',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
					'title' => __( 'Message displayed when not having access to a protected shortcode content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
					'class' => 'ms-textarea-medium ms-ajax-update',
					'data_ms' => array(
							'type' => 'shortcode',
							'action' => $action,
							'_wpnonce' => $nonce,
					),

			),
			'more_tag' => array(
					'id' => 'more_tag',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
					'title' => __( 'Message displayed when not having access to a protected content under more tag.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_MORE_TAG ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
					'class' => 'ms-textarea-medium ms-ajax-update',
					'data_ms' => array(
							'type' => 'more_tag',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
		);
		return apply_filters( 'ms_view_settings_prepare_pages_fields', $fields );
	}
	
	public function render_tab_messages_automated() {
		$fields = $this->prepare_messages_automated_fields();
		$comm = $this->data['comm'];
		?>
		<div class='ms-settings'>
			<h3><?php  _e( 'Automated Messages', MS_TEXT_DOMAIN ) ; ?></h3>
			<form id="ms-comm-type-form" action="" method="post">
				<?php MS_Helper_Html::html_input( $fields['load_action'] );?>
				<?php MS_Helper_Html::html_input( $fields['load_nonce'] );?>
				<?php MS_Helper_Html::html_input( $fields['comm_type'] );?>
				<p><?php echo $comm->get_description(); ?></p>
			</form>
			<form action="" method="post">
				<?php MS_Helper_Html::html_input( $fields['action'] );?>
				<?php MS_Helper_Html::html_input( $fields['nonce'] );?>
				<?php MS_Helper_Html::html_input( $fields['type'] );?>
				<table class="form-table">
					<tbody>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $fields['enabled'] );?>
							</td>
						</tr>
						<?php if( $comm->period_enabled ) : ?>
							<tr>
								<td>
									<div class="ms-period-wrapper">
										<?php MS_Helper_Html::html_input( $fields['period_unit'] );?>
										<?php MS_Helper_Html::html_input( $fields['period_type'] );?>
									</div>
								</td>
							</tr>
						<?php endif; ?>
						<tr>
							<td>
								<?php MS_Helper_Html::html_input( $fields['subject'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<div id="ms-comm-message-wrapper">
								<?php MS_Helper_Html::html_input( $fields['message'] );?>
								</div>
								<div id="ms-comm-var-wrapper">
									<table>
										<tr>
											<th>Variable values</th>
										</tr>
										<?php foreach( $comm->comm_vars as $var => $description ): ?>
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
								<?php MS_Helper_Html::html_input( $fields['cc_enabled'] );?>
								<?php MS_Helper_Html::html_input( $fields['cc_email'] );?>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_separator();?>
								<?php MS_Helper_Html::html_input( $fields['save_email'] );?>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>	
		<?php
	}
	public function prepare_messages_automated_fields() {
		$comm = $this->data['comm'];
		$fields = array(
				'comm_type' => array(
						'id' => 'comm_type',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $comm->type,
						'field_options' => MS_Model_Communication::get_communication_type_titles(),
						'class' => '',
				),
				'type' => array(
						'id' => 'type',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $comm->type,
				),
				'enabled' => array(
						'id' => 'enabled',
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'title' => __( 'Enabled', MS_TEXT_DOMAIN ),
						'value' => $comm->enabled,
						'class' => '',
				),
				'period_unit' => array(
						'id' => 'period_unit',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Period after/before', MS_TEXT_DOMAIN ),
						'value' => $comm->period['period_unit'],
						'class' => '',
				),
				'period_type' => array(
						'id' => 'period_type',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $comm->period['period_type'],
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => '',
				),
				'subject' => array(
						'id' => 'subject',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'Message Subject', MS_TEXT_DOMAIN ),
						'value' => $comm->subject,
						'class' => 'ms-comm-subject',
				),
				'message' => array(
						'id' => 'message',
						'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
// 						'title' => __( 'Message', MS_TEXT_DOMAIN ),
						'value' => $comm->description,
						'field_options' => array( 'media_buttons' => false ),
						'class' => '',
				),
				'cc_enabled' => array(
						'id' => 'cc_enabled',
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'title' => __( 'Send copy to Administrator', MS_TEXT_DOMAIN ),
						'value' => $comm->cc_enabled,
						'class' => '',
				),
				'cc_email' => array(
						'id' => 'cc_email',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $comm->cc_email,
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
				'nonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => wp_create_nonce( 'save_comm' ),
				),
				'load_action' => array(
						'id' => 'load_action',
						'name' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'load_action',
				),
				'load_nonce' => array(
						'id' => '_wpnonce1',
						'name' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => wp_create_nonce( 'load_action' ),
				),
		);
		return apply_filters( 'ms_view_settings_prepare_messages_automated_fields', $fields );
	}
	
	public function render_tab_downloads() {
		$fields = $this->prepare_downloads_fields();
		?>
		<div class='ms-settings'>
			<h3><?php  _e( 'Media / Download Settings', MS_TEXT_DOMAIN ) ; ?></h3>	
			<div class="metabox-holder">
				<form action="" method="post">
					<?php
						MS_Helper_Html::settings_box(
							$fields 
						);
					?>
					<?php MS_Helper_Html::settings_footer( null, false, true ); ?>
				</form>
			</div>
		</div>
		<?php
	}
	
	public function prepare_downloads_fields() {
		$upload_dir = wp_upload_dir();
 
		$settings = $this->data['settings'];
		
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING;
		$nonce = wp_create_nonce( $action );
		
		$fields = array(
				'protection_type' => array(
						'id' => 'protection_type',
						'name' => 'downloads[protection_type]',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
						'title' => __( 'Protection method', MS_TEXT_DOMAIN ),
						'value' => $settings->downloads['protection_type'],
						'field_options' => MS_Model_Rule_Media::get_protection_types(),
						'class' => 'ms-ajax-update',
						'data_ms' => array(
								'field' => 'protection_type',
								'action' => $action,
								'_wpnonce' => $nonce,
						),
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
						'value' => $settings->downloads['masked_url'],
						'class' => 'ms-ajax-update',
						'data_ms' => array(
								'field' => 'masked_url',
								'action' => $action,
								'_wpnonce' => $nonce,
						),
				),
		);
		return apply_filters( 'ms_view_settings_prepare_downloads_fields', $fields );
	}
	
}