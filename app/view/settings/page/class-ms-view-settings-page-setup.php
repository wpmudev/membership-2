<?php
/**
 * Displays the Setup form.
 * Used in both the success popup when creating the first membership and in the
 * settings page.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_View_Settings_Page_Setup extends MS_View {

	/**
	 * Type of form displayed. Used to determine height of the popup.
	 *
	 * @var string
	 */
	protected $form_type = 'full';

	/**
	 * Displays the settings form.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_html() {
		if ( ! empty( $_REQUEST['full_popup'] ) ) {
			$show_wizard_done = true;
		} else {
			$show_wizard_done = MS_Plugin::instance()->settings->is_first_membership;
		}

		if ( $show_wizard_done ) {
			$this->form_type = 'full';
			$code = $this->html_full_form();
		} else {
			$this->form_type = 'short';
			$code = $this->html_short_form();
		}

		return $code;
	}

	/**
	 * Display the small "completed" form
	 *
	 * @since  1.0.0
	 * @return string HTML Code
	 */
	public function html_short_form() {
		$code = sprintf(
			'<center>%1$s</center>',
			sprintf(
				__( 'You can now go to page %sProtection Rules%s to set up access levels for this Membership.', MS_TEXT_DOMAIN ),
				sprintf( '<a href="%1$s">', MS_Controller_Plugin::get_admin_url( 'protection' ) ),
				'</a>'
			)
		);

		return $code;
	}

	/**
	 * Display the full settings form, used either by first membership
	 * "completed" popup and also by the general settings tab.
	 *
	 * @since  1.0.0
	 * @return string HTML code
	 */
	public function html_full_form() {
		$fields = $this->prepare_fields();

		ob_start();
		?>
		<div class="ms-setup-form">
			<?php if ( ! MS_Plugin::is_network_wide() ) : ?>
			<div class="ms-setup-nav">
				<div class="ms-title">
					<i class="ms-icon dashicons dashicons-menu"></i>
					<?php _e( 'Please select pages you want to appear in your Navigation', MS_TEXT_DOMAIN ); ?>
				</div>
				<div class="ms-description">
					<?php
					printf(
						__( 'You can always change those later by going to %1$s in your admin sidebar.', MS_TEXT_DOMAIN ),
						sprintf(
							'<a href="%1$s" target="_blank">%2$s</a>',
							admin_url( 'nav-menus.php' ),
							__( 'Appearance' ) . ' &raquo; ' . __( 'Menus' )
						)
					);
					?>
				</div>
				<?php echo '' . $this->show_menu_controls(); ?>
			</div>
			<?php else : ?>
			<div class="ms-setup-site">
				<div class="ms-title">
					<i class="ms-icon dashicons dashicons-admin-network"></i>
					<?php _e( 'Select the Site that hosts Membership 2 Pages', MS_TEXT_DOMAIN ); ?>
				</div>
				<div class="ms-description">
					<?php _e( 'When you change the site new Membership 2 Pages are created on the selected site. You can customize or replace these pages at any time.', MS_TEXT_DOMAIN ); ?>
				</div>
				<?php
				$site_options = MS_Helper_Settings::get_blogs();
				$site_fields = array(
					array(
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'id' => 'network_site',
						'title' => __( 'Select the site that hosts the Membership 2 Pages', MS_TEXT_DOMAIN ),
						'value' => MS_Model_Pages::get_site_info( 'id' ),
						'field_options' => $site_options,
						'class' => 'ms-site-options',
					),
					array(
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'name' => 'action',
						'value' => 'network_site',
					),
					array(
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'name' => '_wpnonce',
						'value' => wp_create_nonce( 'network_site' ),
					),
					array(
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Save', MS_TEXT_DOMAIN ),
					),
					array(
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'class' => 'ms-setup-pages-cancel',
						'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
					),
				);
				?>
				<div class="ms-setup-pages-site">
					<div class="ms-setup-pages-site-info"><?php
					printf(
						__( 'Membership pages are located on site %s', MS_TEXT_DOMAIN ),
						'<strong>' . MS_Model_Pages::get_site_info( 'title' ) . '</strong>'
					);
					?>
					<a href="#change-site" class="ms-setup-pages-change-site"><?php
					_e( 'Change site...', MS_TEXT_DOMAIN );
					?></a></div>
					<div class="ms-setup-pages-site-form cf" style="display:none;">
						<?php
						foreach ( $site_fields as $field ) {
							MS_Helper_Html::html_element( $field );
						}
						?>
					</div>
				</div>
			</div>
			<?php endif; ?>
			<div class="ms-setup-pages">
				<div class="ms-title">
					<i class="ms-icon dashicons dashicons-admin-page"></i>
					<?php _e( 'Membership 2 Pages', MS_TEXT_DOMAIN ); ?>
				</div>
				<div class="ms-description">
					<?php _e( 'Set Up Membership 2 Pages that will be displayed on your website.', MS_TEXT_DOMAIN ); ?>
				</div>
				<?php

				if ( is_array( $fields['pages'] ) ) {
					$page_types = array_keys( $fields['pages'] );
					$page_types_menu = array(
						'memberships',
						'register',
						'account',
					);
					$page_types_rest = array_diff( $page_types, $page_types_menu );
					$groups = array(
						'in-menu' => $page_types_menu,
						'no-menu' => $page_types_rest,
					);

					$pages_site_id = MS_Model_Pages::get_site_info( 'id' );
					MS_Factory::select_blog( $pages_site_id );

					foreach ( $groups as $group_key => $group_items ) :
						printf( '<div class="ms-pages-group %1$s">', esc_attr( $group_key ) );

						foreach ( $group_items as $key ) :
							$field = $fields['pages'][$key];
							?>
							<div class="ms-settings-page-wrapper">
								<?php MS_Helper_Html::html_element( $field ); ?>
								<div class="ms-action">
									<?php
									MS_Helper_Html::html_link(
										array(
											'id' => 'url_page_' . $field['value'],
											'url' => '',
											'value' => __( 'View Page', MS_TEXT_DOMAIN ),
											'target' => '_blank',
											'data_ms' => array(
												'base' => get_home_url(
													$pages_site_id, 'index.php?page_id='
												)
											),
										)
									);
									?>
									<span> | </span>
									<?php
									MS_Helper_Html::html_link(
										array(
											'id' => 'edit_url_page_' . $field['value'],
											'url' => '',
											'value' => __( 'Edit Page', MS_TEXT_DOMAIN ),
											'target' => '_blank',
											'data_ms' => array(
												'base' => get_admin_url(
													$pages_site_id, 'post.php?action=edit&post='
												)
											),
										)
									);
									?>
								</div>
							</div>
							<?php
						endforeach;

						echo '</div>';
					endforeach;
				} else {
					echo $fields['pages'];
				}

				MS_Factory::revert_blog();
				?>
			</div>
		</div>
		<?php

		$html = ob_get_clean();

		return apply_filters(
			'ms_view_settings_page_setup_to_html',
			$html
		);
	}

	/**
	 * Prepare the HTML fields that can be displayed
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	protected function prepare_fields() {
		// Prepare the return value.
		$nav = array();
		$pages = array();

		MS_Model_Pages::create_missing_pages();
		$page_types = MS_Model_Pages::get_page_types();
		$page_types_menu = array(
			'memberships',
			'register',
			'account',
		);
		$page_types_rest = array_diff( $page_types, $page_types_menu );

		// Prepare NAV fields.
		$menu_action = MS_Controller_Pages::AJAX_ACTION_TOGGLE_MENU;
		$menu_nonce = wp_create_nonce( $menu_action );
		foreach ( $page_types_menu as $type ) {
			$nav_exists = MS_Model_Pages::has_menu( $type );
			$nav[$type] = array(
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'id' => 'nav_' . $type,
				'value' => $nav_exists,
				'title' => $page_types[$type],
				'ajax_data' => array(
					'action' => $menu_action,
					'item' => $type,
					'_wpnonce' => $menu_nonce,
				),
			);
		}

		$nav['sep'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
		);

		// Prepare PAGES fields.
		$pages_action = MS_Controller_Pages::AJAX_ACTION_UPDATE_PAGES;
		$pages_nonce = wp_create_nonce( $pages_action );

		foreach ( $page_types as $type => $label ) {
			$page_id = MS_Model_Pages::get_setting( $type );
			$title = sprintf(
				'<strong>%1$s</strong><span class="lbl-details">: %2$s</span>',
				$label,
				MS_Model_Pages::get_description( $type )
			);

			$pages[ $type ] = array(
				'id' => $type,
				'type' => MS_Helper_Html::INPUT_TYPE_WP_PAGES,
				'title' => $title,
				'value' => $page_id,
				'field_options' => array(
					'no_item' => __( '- Select a page -', MS_TEXT_DOMAIN ),
				),
				'ajax_data' => array(
					'field' => $type,
					'action' => $pages_action,
					'_wpnonce' => $pages_nonce,
				),
			);
		}

		$fields = array(
			'nav' => $nav,
			'pages' => $pages,
		);

		return apply_filters(
			'ms_view_settings_page_setup_prepare_fields',
			$fields,
			$this
		);
	}

	/**
	 * Outputs the HTML code to toggle Membership2 menu items.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function show_menu_controls() {
		$code = '';
		$can_create_nav = MS_Model_Pages::can_edit_menus();

		if ( $can_create_nav ) {
			$fields = $this->prepare_fields();
			foreach ( $fields['nav'] as $field ) {
				$code .= MS_Helper_Html::html_element( $field, true );
			}
		} else {
			$button = array(
				'id' => 'create_menu',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Okay, create the menu', MS_TEXT_DOMAIN ),
				'ajax_data' => array(
					'action' => MS_Controller_Pages::AJAX_ACTION_CREATE_MENU,
					'_wpnonce' => wp_create_nonce( MS_Controller_Pages::AJAX_ACTION_CREATE_MENU ),
				)
			);
			$code = sprintf(
				'<div style="padding-left:10px"><p><em>%s</em></p><p>%s</p></div>',
				__( 'Wait! You did not create a menu yet...<br>Let us create it now, so you can choose which pages to display to your visitors!', MS_TEXT_DOMAIN ),
				MS_Helper_Html::html_element( $button, true )
			);
		}

		return '<div class="ms-nav-controls">' . $code . '</div>';
	}

	/**
	 * Returns the height needed to display this dialog inside a popup without
	 * adding scrollbars
	 *
	 * @since  1.0.0
	 * @return int Popup height
	 */
	public function dialog_height() {
		switch ( $this->form_type ) {
			case 'short':
				$height = 200;
				break;

			case 'full':
			default:
				if ( MS_Model_Pages::can_edit_menus() ) {
					$height = 412;
				} else {
					$height = 460;
				}
				break;
		}

		return $height;
	}

}
