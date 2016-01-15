<?php
/**
 * View.
 * @package Membership2
 */

/**
 * Renders Help and Documentation Page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 *
 * @return object
 */
class MS_View_Help extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @since  1.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		$this->check_simulation();

		// Setup navigation tabs.
		$tabs = $this->data['tabs'];

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Help and documentation', 'membership2' ),
					'title_icon_class' => 'wpmui-fa wpmui-fa-info-circle',
				)
			);
			$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );

			// Call the appropriate form to render.
			$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
			$render_callback = apply_filters(
				'ms_view_help_render_callback',
				array( $this, $callback_name ),
				$active_tab,
				$this->data
			);
			?>
			<div class="ms-settings ms-help-content">
				<?php
				$html = call_user_func( $render_callback );
				$html = apply_filters( 'ms_view_help_' . $callback_name, $html );
				echo $html;
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the General help contents
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function render_tab_general() {
		ob_start();
		?>
		<h2>
			<?php _e( 'You\'re awesome :)', 'membership2' ); ?><br />
		</h2>
		<p>
			<em><?php _e( 'Thank you for using Membership 2', 'membership2' ); ?></em>
			<br/ ><br />
			<?php _ex( 'Here is a quick overview:', 'help', 'membership2' ); ?>
		</p>
		<div>
		<?php
		printf(
			_x( 'You use verion <strong>%s</strong> of Membership 2', 'help', 'membership2' ),
			MS_PLUGIN_VERSION
		);
		if ( function_exists( 'membership2_init_pro_app' ) ) {
			printf(
				'<br />' .
				_x( 'Hey, this is the <strong>PRO version</strong> of Membership 2 - thanks a lot for supporting us!', 'help', 'membership2' )
			);
		} else {
			printf(
				'<br />' .
				_x( 'This is the <strong>Free version</strong> of Membership 2 - did you check out our %sPRO version%s already?', 'help', 'membership2' ),
				'<a href="https://premium.wpmudev.org/project/membership/" target="_blank">',
				'</a>'
			);
		}
		if ( is_multisite() ) {
			if ( MS_Plugin::is_network_wide() ) {
				printf(
					'<br />' .
					_x( 'Your Protection mode is <strong>%s network-wide</strong>.', 'help', 'membership2' ),
					'<i class="wpmui-fa wpmui-fa-globe"></i>'
				);
			} else {
				printf(
					'<br />' .
					_x( 'Your Protection covers <strong>%s only this site</strong>.', 'help', 'membership2' ),
					'<i class="wpmui-fa wpmui-fa-home"></i>'
				);
			}
		}
		$admin_cap = MS_Plugin::instance()->controller->capability;
		if ( $admin_cap ) {
			printf(
				'<br />' .
				_x( 'All users with capability <strong>%s</strong> are M2 Admin-users.', 'help', 'membership2' ),
				$admin_cap
			);
		} else {
			printf(
				'<br />' .
				_x( 'Only the <strong>Network-Admin</strong> can manage M2.', 'help', 'membership2' )
			);
		}
		if ( defined( 'MS_STOP_EMAILS' ) && MS_STOP_EMAILS ) {
			printf(
				'<br />' .
				_x( 'Currently M2 is configured to <strong>not send</strong> any emails.', 'help', 'membership2' )
			);
		}
		if ( defined( 'MS_LOCK_SUBSCRIPTIONS' ) && MS_LOCK_SUBSCRIPTIONS ) {
			printf(
				'<br />' .
				_x( 'Currently M2 is configured <strong>not expire/change</strong> any subscription status.', 'help', 'membership2' )
			);
		}
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			printf(
				'<br />' .
				_x( 'Warning: DISABLE_WP_CRON is <strong>enabled</strong> on this site! M2 will not send all emails or change subscription status when expire date is reached!', 'help', 'membership2' )
			);
		}
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			printf(
				'<br />' .
				_x( 'WP_DEBUG is <strong>enabled</strong> on this site.', 'help', 'membership2' )
			);
		} else {
			printf(
				'<br />' .
				_x( 'WP_DEBUG is <strong>disabled</strong> on this site.', 'help', 'membership2' )
			);
		}
		?>
		</div>
		<?php MS_Helper_Html::html_separator(); ?>
		<h2>
			<?php _ex( 'Plugin menu', 'help', 'membership2' ); ?>
		</h2>
		<table cellspacing="0" cellpadding="4" border="0" width="100%">
			<tr>
				<td>
					<span class="top-menu">
					<div class="menu-image dashicons dashicons-lock"></div>
					<?php _e( 'Membership 2', 'membership2' ); ?>
					</span>
				</td>
				<td></td>
			</tr>
			<tr class="alternate">
				<td><span><?php _e( 'Memberships', 'membership2' ); ?></span></td>
				<td><?php _ex( 'Create and manage Membership-Plans that users can sign up for', 'help', 'membership2' ); ?></td>
			</tr>
			<tr>
				<td><span><?php _e( 'Protection Rules', 'membership2' ); ?></span></td>
				<td><?php _ex( 'Set the protection options, i.e. which pages are protected by which membership', 'help', 'membership2' ); ?></td>
			</tr>
			<tr class="alternate">
				<td><span><?php _e( 'All Members', 'membership2' ); ?></span></td>
				<td><?php _ex( 'Lists all your WordPress users and allows you to manage their Memberships', 'help', 'membership2' ); ?></td>
			</tr>
			<tr>
				<td><span><?php _e( 'Add Member', 'membership2' ); ?></span></td>
				<td><?php _ex( 'Create a new WP User or edit subscriptions of an existing user', 'help', 'membership2' ); ?></td>
			</tr>
			<tr class="alternate">
				<td><span><?php _e( 'Billing', 'membership2' ); ?></span></td>
				<td><?php _ex( 'Manage sent invoices, including details such as the payment status. <em>Only visible when you have at least one paid membership</em>', 'help', 'membership2' ); ?></td>
			</tr>
			<tr>
				<td><span><?php _e( 'Coupons', 'membership2' ); ?></span></td>
				<td><?php _ex( 'Manage your discount coupons. <em>Requires Add-on "Coupons"</em>', 'help', 'membership2' ); ?></td>
			</tr>
			<tr class="alternate">
				<td><span><?php _e( 'Invitation Codes', 'membership2' ); ?></span></td>
				<td><?php _ex( 'Manage your invitation codes. <em>Requires Add-on "Invitation Codes"</em>', 'help', 'membership2' ); ?></td>
			</tr>
			<tr>
				<td><span><?php _e( 'Add-ons', 'membership2' ); ?></span></td>
				<td><?php _ex( 'Activate Add-ons', 'help', 'membership2' ); ?></td>
			</tr>
			<tr class="alternate">
				<td><span><?php _e( 'Settings', 'membership2' ); ?></span></td>
				<td><?php _ex( 'Global plugin options, such as Membership pages, payment options and email templates', 'help', 'membership2' ); ?></td>
			</tr>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the Shortcode help contents
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function render_tab_shortcodes() {
		ob_start();
		?>

		<?php
		/*********
		**********   ms-protect-content   **************************************
		*********/
		?>
		<h2><?php _ex( 'Common shortcodes', 'help', 'membership2' ); ?></h2>

		<div id="ms-protect-content" class="ms-help-box">
			<h3><code>[ms-protect-content]</code></h3>

			<?php _ex( 'Wrap this around any content to protect it for/from certain members (based on their Membership level)', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(ID list)', 'help', 'membership2' ); ?>
						<strong><?php _ex( 'Required', 'help', 'membership2' ); ?></strong>.
						<?php _ex( 'One or more membership IDs. Shortcode is triggered when the user belongs to at least one of these memberships', 'help', 'membership2' ); ?>
					</li>
					<li>
						<code>access</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Defines if members of the memberships can see or not see the content', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>silent</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Silent protection removes content without displaying any message to the user', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							no
						</span>
					</li>
					<li>
						<code>msg</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Provide a custom protection message. <em>This will only be displayed when silent is not true</em>', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p>
					<code>[ms-protect-content id="1"]</code>
					<?php _ex( 'Only members of membership-1 can see this!', 'help', 'membership2' ); ?>
					<code>[/ms-protect-content]</code>
				</p>
				<p>
					<code>[ms-protect-content id="2,3" access="no" silent="yes"]</code>
					<?php _ex( 'Everybody except members of memberships 2 or 3 can see this!', 'help', 'membership2' ); ?>
					<code>[/ms-protect-content]</code>
				</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-user   *************************************************
		*********/
		?>

		<div id="ms-user" class="ms-help-box">
			<h3><code>[ms-user]</code></h3>

			<?php _ex( 'Shows the content only to certain users (ignoring the Membership level)', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>type</code>
						<?php _ex( '(all|loggedin|guest|admin|non-admin)', 'help', 'membership2' ); ?>
						<?php _ex( 'Decide, which type of users will see the message', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"loggedin"
						</span>
					</li>
					<li>
						<code>msg</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Provide a custom protection message that is displayed to users that have no access to the content', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p>
					<code>[ms-user]</code>
					<?php _ex( 'You are logged in', 'help', 'membership2' ); ?>
					<code>[/ms-user]</code>
				</p>
				<p>
					<code>[ms-user type="guest"]</code>
					<?php printf( htmlspecialchars( _x( '<a href="">Sign up now</a>! <a href="">Already have an account</a>?', 'help', 'membership2' ) ) ); ?>
					<code>[/ms-user]</code>
				</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-register-user   *****************************
		*********/
		?>

		<div id="ms-membership-register-user" class="ms-help-box">
			<h3><code>[ms-membership-register-user]</code></h3>

			<?php _ex( 'Displays a registration form. Visitors can create a WordPress user account with this form', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>title</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Title of the register form', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Create an Account', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>first_name</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Initial value for first name', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>last_name</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Initial value for last name', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>username</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Initial value for username', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>email</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Initial value for email address', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>membership_id</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Membership ID to assign to the new user. This field is hidden and cannot be changed during registration. <em>Note: If this membership requires payment, the user will be redirected to the payment gateway after registration</em>', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>loginlink</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Display a login-link below the form', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"yes"
						</span>
					</li>
				</ul>

				<h4><?php _e( 'Field labels', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code>label_first_name</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							"<?php _e( 'First Name', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>label_last_name</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							"<?php _e( 'Last Name', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>label_username</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							"<?php _e( 'Choose a Username', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>label_email</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							"<?php _e( 'Email Address', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>label_password</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							"<?php _e( 'Password', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>label_password2</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							"<?php _e( 'Confirm Password', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>label_register</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							"<?php _e( 'Register My Account', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>hint_first_name</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Placeholder inside Field', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<code>hint_last_name</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Placeholder inside Field', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<code>hint_username</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Placeholder inside Field', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<code>hint_email</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Placeholder inside Field', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<code>hint_password</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Placeholder inside Field', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<code>hint_password2</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Placeholder inside Field', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							""
						</span>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-membership-register-user]</code></p>
				<p><code>[ms-membership-register-user title="" hint_email="john@email.com" label_password2="Repeat"]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-signup   ************************************
		*********/
		?>

		<div id="ms-membership-signup" class="ms-help-box">
			<h3><code>[ms-membership-signup]</code></h3>

			<?php _ex( 'Shows a list of all memberships which the current user can sign up for', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php _ex( 'Common options', 'help', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP ); ?>_text</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Button label', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Signup', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_MOVE ); ?>_text</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Button label', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Change', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL ); ?>_text</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Button label', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Cancel', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW ); ?>_text</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Button label', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Renew', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_PAY ); ?>_text</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Button label', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Complete Payment', 'membership2' ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-membership-signup]</code></p>
			</div>
		</div>



		<?php
		/*********
		**********   ms-membership-login   *************************************
		*********/
		?>

		<div id="ms-membership-login" class="ms-help-box">
			<h3><code>[ms-membership-login]</code></h3>

			<?php _ex( 'Displays the login/lost-password form, or for logged in users a logout link', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php _ex( 'Common options', 'help', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code>title</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'The title above the login form', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>show_labels</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Set to "yes" to display the labels for username and password in front of the input fields', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							no
						</span>
					</li>
					<li>
						<code>redirect_login</code>
						<?php _ex( '(URL)', 'help', 'membership2' ); ?>
						<?php _ex( 'The page to display after the user was logged in', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php echo MS_Model_Pages::get_url_after_login(); ?>"
						</span>
					</li>
					<li>
						<code>redirect_logout</code>
						<?php _ex( '(URL)', 'help', 'membership2' ); ?>
						<?php _ex( 'The page to display after the user was logged out', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php echo MS_Model_Pages::get_url_after_logout(); ?>"
						</span>
					</li>
					<li>
						<code>header</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>register</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>autofocus</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Focus the login-form on page load', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
				</ul>

				<h4><?php _ex( 'More options', 'help', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code>holder</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"div"
						</span>
					</li>
					<li>
						<code>holderclass</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"ms-login-form"
						</span>
					</li>
					<li>
						<code>item</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>itemclass</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>prefix</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>postfix</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>wrapwith</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>wrapwithclass</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>form</code>
						<?php _ex( '(login|lost|logout)', 'help', 'membership2' ); ?>
						<?php _ex( 'Defines which form should be displayed. An empty value allows the plugin to automatically choose between login/logout', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>nav_pos</code>
						<?php _ex( '(top|bottom)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"top"
						</span>
					</li>
				</ul>

				<h4><?php
				printf(
					__( 'Options only for <code>%s</code>', 'membership2' ),
					'form="login"'
				);
				?></h4>
				<ul>
					<li>
						<code>show_note</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Show a "You are not logged in" note above the login form', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>label_username</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Username' ); ?>"
						</span>
					</li>
					<li>
						<code>label_password</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Password' ); ?>"
						</span>
					</li>
					<li>
						<code>label_remember</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Remember Me' ); ?>"
						</span>
					</li>
					<li>
						<code>label_log_in</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Log In' ); ?>"
						</span>
					</li>
					<li>
						<code>id_login_form</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"loginform"
						</span>
					</li>
					<li>
						<code>id_username</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"user_login"
						</span>
					</li>
					<li>
						<code>id_password</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"user_pass"
						</span>
					</li>
					<li>
						<code>id_remember</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"rememberme"
						</span>
					</li>
					<li>
						<code>id_login</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"wp-submit"
						</span>
					</li>
					<li>
						<code>show_remember</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>value_username</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>value_remember</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Set this to "yes" to default the "Remember me" checkbox to checked', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							no
						</span>
					</li>
				</ul>

				<h4><?php
				printf(
					__( 'Options only for <code>%s</code>', 'membership2' ),
					'form="lost"'
				);
				?></h4>
				<ul>
					<li>
						<code>label_lost_username</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Username or E-mail', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>label_lostpass</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Reset Password', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>id_lost_form</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"lostpasswordform"
						</span>
					</li>
					<li>
						<code>id_lost_username</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"user_login"
						</span>
					</li>
					<li>
						<code>id_lostpass</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"wp-submit"
						</span>
					</li>
					<li>
						<code>value_username</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-membership-login]</code></p>
				<p>
					<code>[ms-membership-login form="logout"]</code>
					<?php _ex( 'is identical to', 'help', 'membership2' ); ?>
					<code>[ms-membership-logout]</code>
				</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-note   *************************************************
		*********/
		?>

		<div id="ms-note" class="ms-help-box">
			<h3><code>[ms-note]</code></h3>

			<?php _ex( 'Displays a info/success message to the user', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>type</code>
						(info|warning)
						<?php _ex( 'The type of the notice. Info is green and warning red', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"info"
						</span>
					</li>
					<li>
						<code>class</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'An additional CSS class that should be added to the notice', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p>
					<code>[ms-note type="info"]</code>
					<?php _ex( 'Thanks for joining our Premium Membership!', 'help', 'membership2' ); ?>
					<code>[/ms-note]</code>
				</p>
				<p>
					<code>[ms-note type="warning"]</code>
					<?php _ex( 'Please log in to access this page!', 'help', 'membership2' ); ?>
					<code>[/ms-note]</code>
				</p>
			</div>
		</div>

		<?php
		/*********
		**********   ms-member-info   ******************************************
		*********/
		?>

		<div id="ms-member-info" class="ms-help-box">
			<h3><code>[ms-member-info]</code></h3>

			<?php _ex( 'Displays details about the current member, like the members first name or a list of memberships he subscribed to', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>value</code>
						(email|firstname|lastname|fullname|memberships|custom)
						<?php _ex( 'Defines which value to display.<br>A custom field can be set via the API (you find the API docs on the Advanced Settings tab)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"fullname"
						</span>
					</li>
					<li>
						<code>default</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Default value to display when the defined field is empty', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>before</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Display this text before the field value. Only used when the field is not empty', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"&lt;span&gt;"
						</span>
					</li>
					<li>
						<code>after</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Display this text after the field value. Only used when the field is not empty', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"&lt;/span&gt;"
						</span>
					</li>
					<li>
						<code>custom_field</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Only relevant for the value <code>custom</code>. This is the name of the custom field to get', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>list_separator</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Used when the field value is a list (i.e. Membership list or contents of a custom field)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							", "
						</span>
					</li>
					<li>
						<code>list_before</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Used when the field value is a list (i.e. Membership list or contents of a custom field)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>list_after</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Used when the field value is a list (i.e. Membership list or contents of a custom field)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							""
						</span>
					</li>
					<li>
						<code>user</code>
						<?php _ex( '(User-ID)', 'help', 'membership2' ); ?>
						<?php _ex( 'Use this to display data of any user. If not specified then the current user is displayed', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							0
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p>
					<code>[ms-member-info value="fullname" default="(Guest)"]</code>
				</p>
				<p>
					<code>[ms-member-info value="memberships" default="Sign up now!" list_separator=" | " before="Your Memberships: "]</code>
				</p>
			</div>
		</div>

		<?php
		/**
		 * Allow Add-ons to add their own shortcode documentation.
		 *
		 * @since  1.0.1.0
		 */
		do_action( 'ms_view_help_shortcodes-common' );
		?>



		<hr />

		<h2><?php _ex( 'Membership shortcodes', 'help', 'membership2' ); ?></h2>


		<?php
		/*********
		**********   ms-membership-title   *************************************
		*********/
		?>

		<div id="ms-membership-title" class="ms-help-box">
			<h3><code>[ms-membership-title]</code></h3>

			<?php _ex( 'Displays the name of a specific membership', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', 'membership2' ); ?>
						<strong><?php _ex( 'Required', 'help', 'membership2' ); ?></strong>.
						<?php _ex( 'The membership ID', 'help', 'membership2' ); ?>
					</li>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Displayed in front of the title', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Membership title:', 'membership2' ) ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-membership-title id="5" label=""]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-price   *************************************
		*********/
		?>

		<div id="ms-membership-price" class="ms-help-box">
			<h3><code>[ms-membership-price]</code></h3>

			<?php _ex( 'Displays the price of a specific membership', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', 'membership2' ); ?>
						<strong><?php _ex( 'Required', 'help', 'membership2' ); ?></strong>.
						<?php _ex( 'The membership ID', 'help', 'membership2' ); ?>
					</li>
					<li>
						<code>currency</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Displayed in front of the price', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Membership price:', 'membership2' ) ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-membership-price id="5" currency="no" label="Only today:"]</code> $</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-details   ***********************************
		*********/
		?>

		<div id="ms-membership-details" class="ms-help-box">
			<h3><code>[ms-membership-details]</code></h3>

			<?php _ex( 'Displays the description of a specific membership', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', 'membership2' ); ?>
						<strong><?php _ex( 'Required', 'help', 'membership2' ); ?></strong>.
						<?php _ex( 'The membership ID', 'help', 'membership2' ); ?>
					</li>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Displayed in front of the description', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Membership details:', 'membership2' ) ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-membership-details id="5"]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-buy   *************************************
		*********/
		?>

		<div id="ms-membership-buy" class="ms-help-box">
			<h3><code>[ms-membership-buy]</code></h3>

			<?php _ex( 'Displays a button to buy/sign-up for the specified membership', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', 'membership2' ); ?>
						<strong><?php _ex( 'Required', 'help', 'membership2' ); ?></strong>.
						<?php _ex( 'The membership ID', 'help', 'membership2' ); ?>
					</li>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'The button label', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Signup', 'membership2' ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-membership-buy id="5" label="Buy now!"]</code></p>
			</div>
		</div>

		<?php
		/**
		 * Allow Add-ons to add their own shortcode documentation.
		 *
		 * @since  1.0.1.0
		 */
		do_action( 'ms_view_help_shortcodes-membership' );
		?>


		<hr />

		<h2><?php _ex( 'Less common shortcodes', 'help', 'membership2' ); ?></h2>


		<?php
		/*********
		**********   ms-membership-logout   ************************************
		*********/
		?>

		<div id="ms-membership-logout" class="ms-help-box">
			<h3><code>[ms-membership-logout]</code></h3>

			<?php _ex( 'Displays a logout link. When the user is not logged in then the shortcode will return an empty string', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php _ex( 'Common options', 'help', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code>redirect</code>
						<?php _ex( '(URL)', 'help', 'membership2' ); ?>
						<?php _ex( 'The page to display after the user was logged out', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php echo MS_Model_Pages::get_url_after_logout(); ?>"
						</span>
					</li>
				</ul>

				<h4><?php _ex( 'More options', 'help', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code>holder</code>
						<?php _ex( 'Wrapper element (div, span, p)', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"div"
						</span>
					</li>
					<li>
						<code>holder_class</code>
						<?php _ex( 'Class for the wrapper', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"ms-logout-form"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-membership-logout]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-account-link   ******************************
		*********/
		?>

		<div id="ms-membership-account-link" class="ms-help-box">
			<h3><code>[ms-membership-account-link]</code></h3>

			<?php _ex( 'Inserts a simple link to the Account page', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', 'membership2' ); ?>
						<?php _ex( 'The contents of the link', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Visit your account page for more information', 'membership2' ) ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p>
					<?php _ex( 'Manage subscriptions in', 'help', 'membership2' ); ?>
					<code>[ms-membership-account-link label="<?php _ex( 'your Account', 'help', 'membership2' ); ?>"]!</code>
				</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-protection-message   ***********************************
		*********/
		?>

		<div id="ms-protection-message" class="ms-help-box">
			<h3><code>[ms-protection-message]</code></h3>

			<?php _ex( 'Displays the protection message on pages that the user cannot access. This shortcode should only be used on the Membership Page "Membership2"', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li><em><?php _ex( 'no arguments', 'help', 'membership2' ); ?></em></li>
				</ul>

				<p>
					<?php _ex( 'Tip: If the user is not logged in this shortcode will also display the default login form. <em>If you provide your own login form via the shortcode [ms-membership-login] then this shortcode will not add a second login form.</em>', 'help', 'membership2' ); ?>
				</p>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-protection-message]</code></p>
			</div>
		</div>

		<?php
		/*********
		**********   ms-membership-account   ***********************************
		*********/
		?>

		<div id="ms-membership-account" class="ms-help-box">
			<h3><code>[ms-membership-account]</code></h3>

			<?php _ex( 'Displays the "My Account" page of the currently logged in user', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php _e( 'Membership section', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code>show_membership</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Whether to display the users current memberships', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>membership_title</code>
						<?php _ex( '(text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Title of the current memberships section', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Your Membership', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>show_membership_change</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Display the link to subscribe to other memberships', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>membership_change_label</code>
						<?php _ex( '(text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Title of the link', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Change', 'membership2' ); ?>"
						</span>
					</li>
				</ul>

				<h4><?php _e( 'Profile section', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code>show_profile</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Whether to display the users profile details', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>profile_title</code>
						<?php _ex( '(text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Title of the user profile section', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Personal details', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>show_profile_change</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Display the link to edit the users profile', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>profile_change_label</code>
						<?php _ex( '(text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Title of the link', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Edit', 'membership2' ); ?>"
						</span>
					</li>
				</ul>

				<h4><?php _e( 'Invoices section', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code>show_invoices</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Whether to display the section listing recent invoices', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>invoices_title</code>
						<?php _ex( '(text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Title of the invoices section', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Invoices', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>limit_invoices</code>
						<?php _ex( '(Number)', 'help', 'membership2' ); ?>
						<?php _ex( 'Number of invoices to display in the recent invoices list', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							10
						</span>
					</li>
					<li>
						<code>show_all_invoices</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Display the link to the complete list of users invoices', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>invoices_details_label</code>
						<?php _ex( '(text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Title of the link', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'View all', 'membership2' ); ?>"
						</span>
					</li>
				</ul>

				<h4><?php _e( 'Activities section', 'membership2' ); ?></h4>
				<ul>
					<li>
						<code>show_activity</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Whether to display the section containing the users recent activities', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>activity_title</code>
						<?php _ex( '(text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Title of the invoices section', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'Activities', 'membership2' ); ?>"
						</span>
					</li>
					<li>
						<code>limit_activities</code>
						<?php _ex( '(Number)', 'help', 'membership2' ); ?>
						<?php _ex( 'Number of items to display in the recent activities list', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							10
						</span>
					</li>
					<li>
						<code>show_all_activities</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'Display the link to the complete list of users invoices', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
					<li>
						<code>activity_details_label</code>
						<?php _ex( '(text)', 'help', 'membership2' ); ?>
						<?php _ex( 'Title of the link', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							"<?php _e( 'View all', 'membership2' ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-membership-account]</code></p>
				<p><code>[ms-membership-account show_profile_change="no" show_activity="no" limit_invoices="3" invoices_title="Last 3 invoices"]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-invoice   **********************************************
		*********/
		?>

		<div id="ms-invoice" class="ms-help-box">
			<h3><code>[ms-invoice]</code></h3>

			<?php _ex( 'Display an invoice to the user. Not very useful in most cases, as the invoice can only be viewed by the invoice recipient', 'help', 'membership2' ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', 'membership2' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', 'membership2' ); ?>
						<strong><?php _ex( 'Required', 'help', 'membership2' ); ?></strong>.
						<?php _ex( 'The Invoice ID', 'help', 'membership2' ); ?>
					</li>
					<li>
						<code>pay_button</code>
						<?php _ex( '(yes|no)', 'help', 'membership2' ); ?>
						<?php _ex( 'If the invoice should contain a "Pay" button', 'help', 'membership2' ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', 'membership2' ); ?>
							yes
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', 'membership2' ); ?></em></p>
				<p><code>[ms-invoice id="123"]</code></p>
			</div>
		</div>

		<?php
		/**
		 * Allow Add-ons to add their own shortcode documentation.
		 *
		 * @since  1.0.1.0
		 */
		do_action( 'ms_view_help_shortcodes-other' );
		?>

		<hr />
		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_view_help_shortcodes',
			$html
		);
	}

	/**
	 * Renders the Network-Wide Protection help contents
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function render_tab_network() {
		ob_start();
		?>
		<h2><?php _ex( 'Network-Wide Protection', 'help', 'membership2' ); ?></h2>
		<?php if ( function_exists( 'membership2_init_pro_app' ) ) : ?>
		<p>
			<strong><?php _ex( 'Enable Network-Wide mode', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'In wp-config.php add the line <code>define( "MS_PROTECT_NETWORK", true );</code> to enable network wide protection. Important: Settings for Network-Wide mode are stored differently than normal (site-wide) settings. After switching to network wide mode the first time you have to set up the plugin again.<br />Note: The plugin will automatically enable itself network wide, you only need to add the option above.', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Disable Network-Wide mode', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'Simply remove the line <code>define( "MS_PROTECT_NETWORK", true );</code> from wp-config.php to switch back to site-wide protection. All your previous Memberships will still be there (if you created site-wide memberships before enabling network-wide mode)<br />Note: After this change the plugin will still be enabled network wide, you have to go to Network Admin > Plugins and disable it if you only want to protect certain sites in your network.', 'help', 'membership2' ); ?>
		</p>
		<?php else : ?>
		<p>
			<?php
			printf(
				_x( 'Network wide protection is a Pro feature. %sRead more about the Pro Version here%s!', 'help', 'membership2' ),
				'<a href="http://premium.wpmudev.org/project/membership" target="_blank">',
				'</a>'
			);
			?>
		</p>
		<?php endif; ?>
		<hr />
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the Advanced settings help contents
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function render_tab_advanced() {
		ob_start();
		?>
		<h2><?php _ex( 'Advanced Settings', 'help', 'membership2' ); ?></h2>
		<p>
			<strong><?php _ex( 'Reset', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'Open the Settings page and add <code>&reset=1</code> to the URL. A prompt is displayed that can be used to reset all Membership2 settings. Use this to clean all traces after testing the plugin.', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Stop Emails', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'In wp-config.php add the line <code>define( "MS_STOP_EMAILS", true );</code> to force Procted Content to <em>not</em> send any emails to Members. This can be used when testing to prevent your users from getting email notifications.', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Reduce Emails', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'By default your members will get an email for every event that is handled (see the "Settings > Automated Email Responses" section). However, you can reduce the emails sent to your users by adding the following line to your wp-config.php <code>define( "MS_DUPLICATE_EMAIL_HOURS", 24 );</code>. This will prevent the same email being sent more than once every 24 hours.', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Lock Subscription Status', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'In wp-config.php add the line <code>define( "MS_LOCK_SUBSCRIPTIONS", true );</code> to disable automatic status-checks of subscriptions. Registration is still possible, but after this the Subscription status will not change anymore. Effectively Subscriptions will not expire anymore.', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'No Admin Shortcode Preview', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'By default the user will see additional information on the page when using the shortcode <code>[ms-protect-content]</code>. To disable this additional output add the line <code>define( "MS_NO_SHORTCODE_PREVIEW", true );</code> in wp-config.php.', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Define Membership 2 Admin users', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'By default all users with capability <code>manage_options</code> are considered Membership 2 admin users and have unlimited access to the whole site (including protected content). To change the required capability add the line <code>define( "MS_ADMIN_CAPABILITY", "manage_options" );</code> in wp-config.php. When you set the value to <code>false</code> then only the Superadmin has full access to the site.', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Debugging incorrect page access', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'M2 has a small debugging tool built into it, that allows you to analyze access issues for the current user. To use this tool you have to set <code>define( "WP_DEBUG", true );</code> on your site. Next open the page that you want to analyze and add <code>?explain=access</code> to the page URL. As a result you will not see the normal page contents but a lot of useful details on the access permissions.', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Keep a log of all outgoing emails', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( 'If you want to keep track of all the emails that M2 sends to your members then add the line <code>define( "MS_LOG_EMAILS", true );</code> to your wp-config.php. A new navigation link will be displayed here in the Help page to review the email history.', 'help', 'membership2' ); ?>
		</p>
		<hr />
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the Customize Membership 2 help contents
	 *
	 * @since  1.0.1.2
	 * @return string
	 */
	public function render_tab_branding() {
		ob_start();
		?>
		<h2><?php _ex( 'Template Hierarchy', 'help', 'membership2' ); ?></h2>
		<p>
			<?php
			printf(
				_x( 'By default Membership 2 will render the page contents defined in your %sMembership 2 Pages%s using the themes standard template for single pages. However, you can customize this very easy by creating special %stemplate files%s in the theme.', 'help', 'membership2' ),
				'<a href="' . MS_Controller_Plugin::get_admin_url( 'settings' ) . '">',
				'</a>',
				'<a href="https://developer.wordpress.org/themes/basics/template-files/" target="_blank">',
				'</a>'
			);
			?>
		</p>
		<p>
			<strong><?php _ex( 'Account Page', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( '1. <tt>m2-account.php</tt>', 'help', 'membership2' ); ?><br />
			<?php _ex( '2. Default single-page template', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Membership List Page', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( '1. <tt>m2-memberships-100.php</tt> (Not the list, only checkout for Membership 100)', 'help', 'membership2' ); ?><br />
			<?php _ex( '2. <tt>m2-memberships.php</tt>', 'help', 'membership2' ); ?><br />
			<?php _ex( '3. Default single-page template', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Registration Page', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( '1. <tt>m2-register-100.php</tt> (Not the list, only checkout for Membership 100)', 'help', 'membership2' ); ?><br />
			<?php _ex( '2. <tt>m2-register.php</tt>', 'help', 'membership2' ); ?><br />
			<?php _ex( '3. Default single-page template', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Thank-You Page', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( '1. <tt>m2-registration-complete-100.php</tt> (After subscribing to Membership 100)', 'help', 'membership2' ); ?><br />
			<?php _ex( '2. <tt>m2-registration-complete.php</tt>', 'help', 'membership2' ); ?><br />
			<?php _ex( '3. Default single-page template', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Protected Content Page', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( '1. <tt>m2-protected-content-100.php</tt> (Page is protected by Membership 100)', 'help', 'membership2' ); ?><br />
			<?php _ex( '2. <tt>m2-protected-content.php</tt>', 'help', 'membership2' ); ?><br />
			<?php _ex( '3. Default single-page template', 'help', 'membership2' ); ?>
		</p>
		<p>
			<strong><?php _ex( 'Invoice Layout', 'help', 'membership2' ); ?></strong><br />
			<?php _ex( '1. <tt>m2-invoice-100.php</tt> (Used by all invoices for Membership 100)', 'help', 'membership2' ); ?><br />
			<?php _ex( '2. <tt>m2-invoice.php</tt>', 'help', 'membership2' ); ?><br />
			<?php _ex( '3. <tt>single-ms_invoice.php</tt>', 'help', 'membership2' ); ?><br />
			<?php _ex( '4. Default invoice template by Membership 2', 'help', 'membership2' ); ?>
		</p>
		<hr />
		<?php
		return ob_get_clean();
	}
}