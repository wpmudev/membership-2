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
 * Renders Help and Documentation Page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.1.0
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
	 * @since 1.1.0
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
					'title' => __( 'Help and documentation', MS_TEXT_DOMAIN ),
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
				echo '' . $html;
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the General help contents
	 *
	 * @since  1.1.0
	 * @return string
	 */
	function render_tab_general() {
		ob_start();
		?>
		<h2>
			<?php _ex( 'Overview', 'help', MS_TEXT_DOMAIN ); ?>
		</h2>
		<p>
			<?php _ex( 'Thank you for using Protected Content!', 'help', MS_TEXT_DOMAIN ); ?>
		</p>
		<hr />
		<h2>
			<?php _ex( 'Plugin menu', 'help', MS_TEXT_DOMAIN ); ?>
		</h2>
		<table cellspacing="0" cellpadding="0">
			<tr>
				<td>
					<span class="top-menu">
					<div class="menu-image dashicons dashicons-lock"></div>
					<?php _e( 'Protect Content', MS_TEXT_DOMAIN ); ?>
					</span>
				</td>
				<td></td>
			</tr>
			<tr>
				<td><span class="menu-item"><?php _e( 'Memberships', MS_TEXT_DOMAIN ); ?></span></td>
				<td><?php _ex( 'Create and manage Membership-Plans that users can sign up for. Here you can grant access to previously protected content (<em>see "Protected Content" below</em>)', 'help', MS_TEXT_DOMAIN ); ?></td>
			</tr>
			<tr>
				<td><span class="menu-item"><?php _e( 'Members', MS_TEXT_DOMAIN ); ?></span></td>
				<td><?php _ex( 'Lists all your WordPress users and allows you to manage their Memberships', 'help', MS_TEXT_DOMAIN ); ?></td>
			</tr>
			<tr>
				<td><span class="menu-item"><?php _e( 'Protected Content', MS_TEXT_DOMAIN ); ?></span></td>
				<td><?php _ex( 'Set the global protection options, i.e. which pages are protected', 'help', MS_TEXT_DOMAIN ); ?></td>
			</tr>
			<tr>
				<td><span class="menu-item"><?php _e( 'Billing', MS_TEXT_DOMAIN ); ?></span></td>
				<td><?php _ex( 'Manage sent invoices, including details such as the payment status. <em>Only visible when you have at least one paid membership</em>', 'help', MS_TEXT_DOMAIN ); ?></td>
			</tr>
			<tr>
				<td><span class="menu-item"><?php _e( 'Coupons', MS_TEXT_DOMAIN ); ?></span></td>
				<td><?php _ex( 'Manage your discount coupons. <em>Requires Add-on "Coupons"</em>', 'help', MS_TEXT_DOMAIN ); ?></td>
			</tr>
			<tr>
				<td><span class="menu-item"><?php _e( 'Add-ons', MS_TEXT_DOMAIN ); ?></span></td>
				<td><?php _ex( 'Activate Add-ons', 'help', MS_TEXT_DOMAIN ); ?></td>
			</tr>
			<tr>
				<td><span class="menu-item"><?php _e( 'Settings', MS_TEXT_DOMAIN ); ?></span></td>
				<td><?php _ex( 'Global plugin options, such as Membership pages, payment options and email templates', 'help', MS_TEXT_DOMAIN ); ?></td>
			</tr>
			<tr>
				<td><span class="menu-item"><strong><?php _e( 'Help', MS_TEXT_DOMAIN ); ?></strong></span></td>
				<td><?php _ex( '', 'help', MS_TEXT_DOMAIN ); ?></td>
			</tr>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the Shortcode help contents
	 *
	 * @since  1.1.0
	 * @return string
	 */
	function render_tab_shortcodes() {
		ob_start();
		?>

		<?php
		/*********
		**********   ms-protect-content   **************************************
		*********/
		?>
		<h2><?php _ex( 'Common shortcodes', 'help', MS_TEXT_DOMAIN ); ?></h2>

		<div class="ms-help-box">
			<h3><code>[ms-protect-content]</code></h3>

			<?php _ex( 'Wrap this around any content to protect it for/from certain members (based on their Membership level)', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(ID list)', 'help', MS_TEXT_DOMAIN ); ?>
						<strong><?php _ex( 'Required', 'help', MS_TEXT_DOMAIN ); ?></strong>.
						<?php _ex( 'One or more membership IDs. Shortcode is triggered when the user belongs to at least one of these memberships', 'help', MS_TEXT_DOMAIN ); ?>
					</li>
					<li>
						<code>access</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Defines if members of the memberships can see or not see the content', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							yes
						</span>
					</li>
					<li>
						<code>silent</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Silent protection removes content without displaying any message to the user', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							no
						</span>
					</li>
					<li>
						<code>msg</code>
						<?php _ex( '(Text)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Provide a custom protection message. <em>This will only be displayed when silent is not true</em>', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p>
					<code>[ms-protect-content id="1"]</code>
					<?php _ex( 'Only members of membership-1 can see this!', 'help', MS_TEXT_DOMAIN ); ?>
					<code>[/ms-protect-content]</code>
				</p>
				<p>
					<code>[ms-protect-content id="2,3" access="no" silent="yes"]</code>
					<?php _ex( 'Everybody except members of memberships 2 or 3 can see this!', 'help', MS_TEXT_DOMAIN ); ?>
					<code>[/ms-protect-content]</code>
				</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-user   *************************************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-user]</code></h3>

			<?php _ex( 'Shows the content only to certain users (ignoring the Membership level)', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>type</code>
						<?php _ex( '(all|loggedin|guest|admin)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Decide, which type of users will see the message', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"loggedin"
						</span>
					</li>
					<li>
						<code>msg</code>
						<?php _ex( '(Text)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Provide a custom protection message that is displayed to users that have no access to the content', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p>
					<code>[ms-user]</code>
					<?php _ex( 'You are logged in', 'help', MS_TEXT_DOMAIN ); ?>
					<code>[/ms-user]</code>
				</p>
				<p>
					<code>[ms-user type="guest"]</code>
					<?php printf( htmlspecialchars( _x( '<a href="">Sign up now</a>! <a href="">Already have an account</a>?', 'help', MS_TEXT_DOMAIN ) ) ); ?>
					<code>[/ms-user]</code>
				</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-register-user   *****************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-register-user]</code></h3>

			<?php _ex( 'Displays a registration form. Visitors can create a WordPress user account with this form', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>title</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Title of the register form', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Create an Account', MS_TEXT_DOMAIN ); ?>"
						</span>
					</li>
					<li>
						<code>first_name</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Initial value for first name', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>last_name</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Initial value for last name', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>username</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Initial value for username', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>email</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Initial value for email address', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>membership_id</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Membership ID to assign to the new user. This field is hidden and cannot be changed during registration. <em>Note: If this membership requires payment, the user will be redirected to the payment gateway after registration</em>', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>loginlink</code>
						<?php _ex( '(yes|no)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Display a login-link below the form', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"yes"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-membership-register-user]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-signup   ************************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-signup]</code></h3>

			<?php _ex( 'Shows a list of all memberships which the current user can sign up for', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php _ex( 'Common options', 'help', MS_TEXT_DOMAIN ); ?></h4>
				<ul>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP ); ?>_text</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Button label', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Signup', MS_TEXT_DOMAIN ); ?>"
						</span>
					</li>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_MOVE ); ?>_text</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Button label', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Change', MS_TEXT_DOMAIN ); ?>"
						</span>
					</li>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL ); ?>_text</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Button label', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Cancel', MS_TEXT_DOMAIN ); ?>"
						</span>
					</li>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW  ); ?>_text</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Button label', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Renew', MS_TEXT_DOMAIN ); ?>"
						</span>
					</li>
					<li>
						<code><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_PAY ); ?>_text</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Button label', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Complete Payment', MS_TEXT_DOMAIN ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-membership-signup]</code></p>
			</div>
		</div>



		<?php
		/*********
		**********   ms-membership-login   *************************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-login]</code></h3>

			<?php _ex( 'Displays the login/lost-password form, or for logged in users a logout link', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php _ex( 'Common options', 'help', MS_TEXT_DOMAIN ); ?></h4>
				<ul>
					<li>
						<code>title</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'The title above the login form', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>show_labels</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Set to "yes" to display the labels for username and password in front of the input fields', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							no
						</span>
					</li>
					<li>
						<code>redirect_login</code>
						<?php _ex( '(URL)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'The page to display after the user was logged in', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php echo MS_Model_Pages::get_url_after_login(); ?>"
						</span>
					</li>
					<li>
						<code>redirect_logout</code>
						<?php _ex( '(URL)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'The page to display after the user was logged out', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php echo MS_Model_Pages::get_url_after_logout(); ?>"
						</span>
					</li>
					<li>
						<code>header</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							yes
						</span>
					</li>
					<li>
						<code>register</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							yes
						</span>
					</li>
				</ul>

				<h4><?php _ex( 'More options', 'help', MS_TEXT_DOMAIN ); ?></h4>
				<ul>
					<li>
						<code>holder</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"div"
						</span>
					</li>
					<li>
						<code>holderclass</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"ms-login-form"
						</span>
					</li>
					<li>
						<code>item</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>itemclass</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>prefix</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>postfix</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>wrapwith</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>wrapwithclass</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>form</code>
						<?php _ex( '(login|lost|logout)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Defines which form should be displayed. An empty value allows the plugin to automatically choose between login/logout', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>nav_pos</code>
						<?php _ex( '(top|bottom)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"top"
						</span>
					</li>
				</ul>

				<h4><?php
				printf(
					__( 'Options only for <code>%s</code>', MS_TEXT_DOMAIN ),
					'form="login"'
				);
				?></h4>
				<ul>
					<li>
						<code>show_note</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Show a "You are not logged in" note above the login form', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							yes
						</span>
					</li>
					<li>
						<code>label_username</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Username' ); ?>"
						</span>
					</li>
					<li>
						<code>label_password</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Password' ); ?>"
						</span>
					</li>
					<li>
						<code>label_remember</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Remember Me' ); ?>"
						</span>
					</li>
					<li>
						<code>label_log_in</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Log In' ); ?>"
						</span>
					</li>
					<li>
						<code>id_login_form</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"loginform"
						</span>
					</li>
					<li>
						<code>id_username</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"user_login"
						</span>
					</li>
					<li>
						<code>id_password</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"user_pass"
						</span>
					</li>
					<li>
						<code>id_remember</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"rememberme"
						</span>
					</li>
					<li>
						<code>id_login</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"wp-submit"
						</span>
					</li>
					<li>
						<code>show_remember</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							yes
						</span>
					</li>
					<li>
						<code>value_username</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
					<li>
						<code>value_remember</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Set this to "yes" to default the "Remember me" checkbox to checked', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							no
						</span>
					</li>
				</ul>

				<h4><?php
				printf(
					__( 'Options only for <code>%s</code>', MS_TEXT_DOMAIN ),
					'form="lost"'
				);
				?></h4>
				<ul>
					<li>
						<code>label_lost_username</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Username or E-mail', MS_TEXT_DOMAIN ); ?>"
						</span>
					</li>
					<li>
						<code>label_lostpass</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Reset Password', MS_TEXT_DOMAIN ); ?>"
						</span>
					</li>
					<li>
						<code>id_lost_form</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"lostpasswordform"
						</span>
					</li>
					<li>
						<code>id_lost_username</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"user_login"
						</span>
					</li>
					<li>
						<code>id_lostpass</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"wp-submit"
						</span>
					</li>
					<li>
						<code>value_username</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-membership-login]</code></p>
				<p>
					<code>[ms-membership-login form="logout"]</code>
					<?php _ex( 'is identical to', 'help', MS_TEXT_DOMAIN ); ?>
					<code>[ms-membership-logout]</code>
				</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-note   *************************************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-note]</code></h3>

			<?php _ex( 'Displays a info/success message to the user', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>type</code>
						<?php _ex( '(info|warning)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'The type of the notice. Info is green and warning red', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"info"
						</span>
					</li>
					<li>
						<code>class</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'An additional CSS class that should be added to the notice', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p>
					<code>[ms-note type="info"]</code>
					<?php _ex( 'Thanks for joining our Premium Membership!', 'help', MS_TEXT_DOMAIN ); ?>
					<code>[/ms-note]</code>
				</p>
				<p>
					<code>[ms-note type="warning"]</code>
					<?php _ex( 'Please log in to access this page!', 'help', MS_TEXT_DOMAIN ); ?>
					<code>[/ms-note]</code>
				</p>
			</div>
		</div>



		<hr />

		<h2><?php _ex( 'Membership shortcodes', 'help', MS_TEXT_DOMAIN ); ?></h2>


		<?php
		/*********
		**********   ms-membership-title   *************************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-title]</code></h3>

			<?php _ex( 'Displays the name of a specific membership', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', MS_TEXT_DOMAIN ); ?>
						<strong><?php _ex( 'Required', 'help', MS_TEXT_DOMAIN ); ?></strong>.
						<?php _ex( 'The membership ID', 'help', MS_TEXT_DOMAIN ); ?>
					</li>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Displayed in front of the title', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Membership title:', MS_TEXT_DOMAIN ) ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-membership-title id="5" label=""]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-price   *************************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-price]</code></h3>

			<?php _ex( 'Displays the price of a specific membership', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', MS_TEXT_DOMAIN ); ?>
						<strong><?php _ex( 'Required', 'help', MS_TEXT_DOMAIN ); ?></strong>.
						<?php _ex( 'The membership ID', 'help', MS_TEXT_DOMAIN ); ?>
					</li>
					<li>
						<code>currency</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							yes
						</span>
					</li>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Displayed in front of the price', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Membership price:', MS_TEXT_DOMAIN ) ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-membership-price id="5" currency="no" label="Only today:"]</code> $</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-details   ***********************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-details]</code></h3>

			<?php _ex( 'Displays the description of a specific membership', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', MS_TEXT_DOMAIN ); ?>
						<strong><?php _ex( 'Required', 'help', MS_TEXT_DOMAIN ); ?></strong>.
						<?php _ex( 'The membership ID', 'help', MS_TEXT_DOMAIN ); ?>
					</li>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Displayed in front of the description', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Membership details:', MS_TEXT_DOMAIN ) ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-membership-details id="5"]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-buy   *************************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-buy]</code></h3>

			<?php _ex( 'Displays a button to buy/sign-up for the specified membership', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', MS_TEXT_DOMAIN ); ?>
						<strong><?php _ex( 'Required', 'help', MS_TEXT_DOMAIN ); ?></strong>.
						<?php _ex( 'The membership ID', 'help', MS_TEXT_DOMAIN ); ?>
					</li>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'The button label', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Signup', MS_TEXT_DOMAIN ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-membership-buy id="5" label="Buy now!"]</code></p>
			</div>
		</div>



		<hr />

		<h2><?php _ex( 'Less common shortcodes', 'help', MS_TEXT_DOMAIN ); ?></h2>


		<?php
		/*********
		**********   ms-membership-logout   ************************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-logout]</code></h3>

			<?php _ex( 'Displays a logout link. When the user is not logged in then the shortcode will return an empty string', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php _ex( 'Common options', 'help', MS_TEXT_DOMAIN ); ?></h4>
				<ul>
					<li>
						<code>redirect</code>
						<?php _ex( '(URL)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'The page to display after the user was logged out', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							Home URL
						</span>
					</li>
				</ul>

				<h4><?php _ex( 'More options', 'help', MS_TEXT_DOMAIN ); ?></h4>
				<ul>
					<li>
						<code>holder</code>
						<?php _ex( 'Wrapper element (div, span, p)', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"div"
						</span>
					</li>
					<li>
						<code>holder_class</code>
						<?php _ex( 'Class for the wrapper', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"ms-logout-form"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-membership-logout]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-membership-account-link   ******************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-account-link]</code></h3>

			<?php _ex( 'Inserts a simple link to the Account page', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>label</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'The contents of the link', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							"<?php _e( 'Visit your account page for more information', MS_TEXT_DOMAIN ) ?>"
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p>
					<?php _ex( 'Manage subscriptions in', 'help', MS_TEXT_DOMAIN ); ?>
					<code>[ms-membership-account-link label="<?php _ex( 'your Account', 'help', MS_TEXT_DOMAIN ); ?>"]!</code>
				</p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-protection-message   ***********************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-protection-message]</code></h3>

			<?php _ex( 'Displays the protection message on pages that the user cannot access. This shortcode should only be used on the Membership Page "Protected Content"', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li><em><?php _ex( 'no arguments', 'help', MS_TEXT_DOMAIN ); ?></em></li>
				</ul>

				<p>
					<?php _ex( 'Tipp: If the user is not logged in this shortcode will also display the default login form. <em>If you provide your own login form via the shortcode [ms-membership-login] then this shortcode will not add a second login form.</em>', 'help', MS_TEXT_DOMAIN ); ?>
				</p>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-protection-message]</code></p>
			</div>
		</div>

		<?php
		/*********
		**********   ms-membership-account   ***********************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-membership-account]</code></h3>

			<?php _ex( 'Displays the "My Account" page of the currently logged in user', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li><em><?php _ex( 'no arguments', 'help', MS_TEXT_DOMAIN ); ?></em></li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-membership-account]</code></p>
			</div>
		</div>


		<?php
		/*********
		**********   ms-invoice   **********************************************
		*********/
		?>

		<div class="ms-help-box">
			<h3><code>[ms-invoice]</code></h3>

			<?php _ex( 'Display an invoice to the user. Not very useful in most cases, as the invoice can only be viewed by the invoice recipient', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', MS_TEXT_DOMAIN ); ?>
						<strong><?php _ex( 'Required', 'help', MS_TEXT_DOMAIN ); ?></strong>.
						<?php _ex( 'The Invoice ID', 'help', MS_TEXT_DOMAIN ); ?>
					</li>
					<li>
						<code>pay_button</code>
						<?php _ex( '(yes|no)', 'shotcode help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'If the invoice should contain a "Pay" button', 'help', MS_TEXT_DOMAIN ); ?>
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							yes
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[ms-invoice id="123"]</code></p>
			</div>
		</div>

		<hr />
		<?php
		return ob_get_clean();
	}
}