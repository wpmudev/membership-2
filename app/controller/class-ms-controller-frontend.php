<?php
/**
 * This file defines the MS_Controller_Registration class.
 *
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
 * Creates the controller for Membership/User registration.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Frontend extends MS_Controller {

	/**
	 * Signup/register process step constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const STEP_CHOOSE_MEMBERSHIP = 'choose_membership';
	const STEP_REGISTER_FORM = 'register_form';
	const STEP_REGISTER_SUBMIT = 'register_submit';
	const STEP_PAYMENT_TABLE = 'payment_table';
	const STEP_GATEWAY_FORM = 'gateway_form';
	const STEP_PROCESS_PURCHASE = 'process_purchase';

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ACTION_EDIT_PROFILE = 'edit_profile';
	const ACTION_VIEW_INVOICES = 'view_invoices';
	const ACTION_VIEW_ACTIVITIES = 'view_activities';
	const ACTION_VIEW_RESETPASS = 'rp';

	/**
	 * User registration errors.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $register_errors;

	/**
	 * Allowed actions to execute in template_redirect hook.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $allowed_actions = array( 'signup_process', 'register_user' );

	/**
	 * Prepare for Member registration.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		if ( MS_Plugin::is_enabled() ) {
			do_action( 'ms_controller_frontend_construct', $this );

			$this->add_action( 'parse_query', 'process_actions', 1 );
			$this->add_action( 'pre_get_posts', 'check_for_membership_pages', 1 );

			// Add classes for all memberships the user is registered to.
			$this->add_filter( 'body_class', 'body_class' );

			$this->add_filter( 'wp_signup_location', 'signup_location', 999 );
			$this->add_filter( 'register_url', 'signup_location', 999 );
			$this->add_action( 'wp_login', 'propagate_ssl_cookie', 10, 2 );

			$this->add_filter( 'login_redirect', 'login_redirect', 10, 3 );

			$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );
		}
	}

	/**
	 * Handle URI actions for registration.
	 *
	 * Matches returned 'action' to method to execute.
	 *
	 * Related Action Hooks:
	 * - parse_query
	 *
	 * @since 1.0.0
	 */
	public function process_actions() {
		// Only execute this handler once!
		$this->remove_action( 'parse_query', 'process_actions', 1 );

		$action = $this->get_action();

		/**
		 * If $action is set, then call relevant method.
		 *
		 * Methods:
		 * @see $allowed_actions property
		 *
		 */
		if ( ! empty( $action )
			&& method_exists( $this, $action )
			&& in_array( $action, $this->allowed_actions )
		) {
			$this->$action();
		}
	}

	/**
	 * Check pages for the presence of Membership special pages.
	 *
	 * Related Action Hooks:
	 * - pre_get_posts
	 *
	 * @since 1.0.0
	 */
	public function check_for_membership_pages( &$query ) {
		global $post;
		if ( ! is_main_query() ) { return; }

		// Only execute this handler once!
		$this->remove_action( 'pre_get_posts', 'check_for_membership_pages', 1 );

		//For invoice page purchase process
		$fields = array( 'gateway', 'ms_relationship_id', 'step' );

		if ( isset( $post->post_type )
			&& $post->post_type == MS_Model_Invoice::$POST_TYPE
			&& $this->validate_required( $fields )
			&& 'process_purchase' == $_POST['step']
		) {
			do_action(
				'ms_controller_frontend_signup_process_purchase',
				$this
			);
		}

		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );
		if ( isset( $query->query[ MS_Model_Page::$POST_TYPE ] ) ) {
			$ms_page = $ms_pages->current_page_info( false, $query->query[ MS_Model_Page::$POST_TYPE ] );
		} else {
			$ms_page = $ms_pages->current_page_info();
		}

		if ( $ms_page ) {
			// Fix the main query flags for best theme support:
			// Our Membership-Pages are always single pages...

			$query->query_vars['page'] = $ms_page->slug;
			$query->query_vars['pagename'] = $ms_page->slug;
			$query->query_vars['post_type'] = $ms_page->post_type;
			unset( $query->query_vars['name'] );

			$query->query['page'] = $ms_page->slug;
			$query->query['pagename'] = $ms_page->slug;
			$query->query['post_type'] = $ms_page->post_type;
			unset( $query->query['name'] );

			$query->is_single = false;
			$query->is_page = true;
			$query->is_singular = true;
			$query->is_home = false;
			$query->is_frontpage = false;
			$query->tax_query = null;

			$query->queried_object = get_post( $ms_page->id );
			$query->queried_object_id = $ms_page->id;
		}

		switch ( $ms_page->type ) {
			case MS_Model_Pages::MS_PAGE_MEMBERSHIPS:
				if ( ! MS_Model_Member::is_logged_user() ) {
					$this->add_filter( 'the_content', 'display_login_form' );
					break;
				}
				// no break;

			case MS_Model_Pages::MS_PAGE_REGISTER:
				if ( MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL == $this->get_action() ) {
					$this->membership_cancel();
				} else {
					$this->signup_process();
				}
				break;

			case MS_Model_Pages::MS_PAGE_ACCOUNT:
				$this->user_account_mgr();
				break;

			case MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT:
				$this->add_filter( 'the_content', 'protected_page', 1 );
				break;

			case MS_Model_Pages::MS_PAGE_REG_COMPLETE:
				$this->add_filter( 'the_content', 'reg_complete_page', 1 );
				break;

			default:
				break;
		}
	}

	/**
	 * Appends classes to the HTML body that identify all memberships that the
	 * current user is registered to. This allows webdesigners to adjust layout
	 * or hide elements based on the membership a user has.
	 *
	 * @since  1.0.2
	 *
	 * @param  array $class Class-names to attach to the body.
	 * @return array Modified class-names to attach to the body.
	 */
	public function body_class( $class ) {
		$info = MS_Plugin::instance()->controller->get_access_info();
		foreach ( $info['memberships'] as $membership_id ) {
			$class[] = 'ms-' . absint( $membership_id );
		}

		return $class;
	}

	/**
	 * Handle entire signup process.
	 *
	 * @since 1.0.0
	 */
	public function signup_process() {
		$step = $this->get_signup_step();

		switch ( $step ) {
			/**
			 * Initial state.
			 */
			case self::STEP_CHOOSE_MEMBERSHIP:
				$this->add_filter( 'the_content', self::STEP_CHOOSE_MEMBERSHIP, 1 );
				break;

			/**
			 * If not registered.
			 */
			case self::STEP_REGISTER_FORM:
				$this->add_filter( 'the_content', self::STEP_REGISTER_FORM, 1 );
				break;

			/**
			 * Process user registration.
			 */
			case self::STEP_REGISTER_SUBMIT:
				$this->register_user();
				break;

			/**
			 * Show payment table.
			 */
			case self::STEP_PAYMENT_TABLE:
				$this->add_filter( 'the_content', self::STEP_PAYMENT_TABLE, 1 );
				break;

			/**
			 * Show gateway extra form.
			 * Handled by MS_Controller_Gateway.
			 */
			case self::STEP_GATEWAY_FORM:
				do_action( 'ms_controller_frontend_signup_gateway_form', $this );
				break;

			/**
			 * Process the purchase action.
			 * Handled by MS_Controller_Gateway.
			 */
			case self::STEP_PROCESS_PURCHASE:
				do_action( 'ms_controller_frontend_signup_process_purchase', $this );
				break;

			default:
				MS_Helper_Debug::log( "No handler for step: $step" );
				break;
		}
	}

	/**
	 * Get signup process step (multi step form).
	 *
	 * @since 1.0.0
	 *
	 * @return string The current signup step after validation.
	 */
	private function get_signup_step() {
		static $steps;

		if ( empty( $steps ) ) {
			$steps = apply_filters(
				'ms_controller_frontend_signup_steps',
				array(
					self::STEP_CHOOSE_MEMBERSHIP,
					self::STEP_REGISTER_FORM,
					self::STEP_REGISTER_SUBMIT,
					self::STEP_PAYMENT_TABLE,
					self::STEP_GATEWAY_FORM,
					self::STEP_PROCESS_PURCHASE,
				)
			);
		}

		if ( in_array( @$_REQUEST['step'], $steps ) ) {
			$step = $_REQUEST['step'];
		}

		// Initial step
		else {
			$step = self::STEP_CHOOSE_MEMBERSHIP;
		}

		if ( self::STEP_PAYMENT_TABLE == $step ) {
			if ( ! MS_Model_Member::is_logged_user() ) {
				$step = self::STEP_REGISTER_FORM;
			}
			if ( ! MS_Model_Membership::is_valid_membership( @$_REQUEST['membership_id'] ) ) {
				$step = self::STEP_CHOOSE_MEMBERSHIP;
			}
		}

		if ( self::STEP_CHOOSE_MEMBERSHIP == $step && ! empty( $_GET['membership_id'] ) ) {
			$step = self::STEP_PAYMENT_TABLE;
		}

		return apply_filters(
			'ms_controller_frontend_get_signup_step',
			$step,
			$this
		);
	}

	/**
	 * Show choose membership form.
	 *
	 * Search for signup shortcode, injecting if not found.
	 *
	 * Related Filter Hooks:
	 * * the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function choose_membership( $content ) {
		remove_filter( 'the_content', 'wpautop' );

		if ( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_SIGNUP, $content ) ) {
			$content .= do_shortcode( '['. MS_Helper_Shortcode::SCODE_SIGNUP .']' );
		}

		return apply_filters(
			'ms_controller_frontend_choose_membership_content',
			$content,
			$this
		);
	}

	/**
	 * Show register user form.
	 *
	 * Search for register user shortcode, injecting if not found.
	 *
	 * Related Filter Hooks:
	 * * the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function register_form( $content ) {
		remove_filter( 'the_content', 'wpautop' );

		if ( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_REGISTER_USER, $content ) ) {
			$scode = sprintf(
				'[%s errors="%s"]',
				MS_Helper_Shortcode::SCODE_REGISTER_USER,
				esc_attr( $this->register_errors )
			);
			$reg_form = do_shortcode( $scode );

			if ( ! MS_Model_Member::is_logged_user() ) {
				$content = $reg_form;
			}
			else {
				$content .= $reg_form;
			}
		}

		return apply_filters(
			'ms_controller_frontend_register_form_content',
			$content,
			$this
		);
	}

	/**
	 * Handles register user submit.
	 *
	 * On validation errors, step back to register form.
	 *
	 * @since 1.0.0
	 */
	public function register_user() {
		do_action( 'ms_controller_frontend_register_user_before', $this );

		if ( ! $this->verify_nonce() ) {
			return;
		}
		try {
			$user = MS_Factory::create( 'MS_Model_Member' );

			foreach ( $_REQUEST as $field => $value ) {
				$user->$field = $value;
			}

			$user->save();
			$user->signon_user();

			if ( ! MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_REGISTERED, $user ) ) {
				wp_new_user_notification( $user->id, $user->password );
			}

			do_action( 'ms_controller_frontend_register_user_complete', $user );

			// Go to membership signup payment form.
			$redirect = add_query_arg(
				array(
					'step' => self::STEP_PAYMENT_TABLE,
					'membership_id' => absint( @$_REQUEST['membership_id'] ),
				)
			);
			wp_safe_redirect( $redirect );
			exit;

		}
		catch( Exception $e ) {
			$this->register_errors = $e->getMessage();
			MS_Helper_Debug::log( $this->register_errors );

			// step back
			$this->add_action( 'the_content', self::STEP_REGISTER_FORM, 1 );
			do_action(
				'ms_controller_frontend_register_user_error',
				$this->register_errors
			);
		}
	}

	/**
	 * Render membership payment information.
	 *
	 * Related Filter Hooks:
	 * - the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function payment_table( $content ) {
		$data = array();
		$ms_relationship = null;
		$member = MS_Model_Member::get_current_member();
		$membership_id = 0;

		// First time loading
		if ( ! empty( $_REQUEST['membership_id'] ) ) {
			$membership_id = $_REQUEST['membership_id'];
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
			$move_from_id = absint( @$_REQUEST['move_from_id'] );
			$ms_relationship = MS_Model_Membership_Relationship::create_ms_relationship(
				$membership_id,
				$member->id,
				'',
				$move_from_id
			);
		}
		// Error path, showing payment table again with error msg
		elseif ( ! empty( $_POST['ms_relationship_id'] ) ) {
			$ms_relationship = MS_Factory::load(
				'MS_Model_Membership_Relationship',
				absint( $_POST['ms_relationship_id'] )
			);
			$membership = $ms_relationship->get_membership();
			$membership_id = $membership->id;

			if ( ! empty( $_POST['error'] ) ) {
				$data['error'] = $_POST['error'];
			}
		}
		else {
			MS_Helper_Debug::log( 'Error: missing POST params' );
			MS_Helper_Debug::log( $_POST );
			return $content;
		}

		if ( ! empty( $_POST['coupon_code'] ) ) {
			$coupon = apply_filters(
				'ms_model_coupon',
				MS_Model_Coupon::load_by_coupon_code( $_POST['coupon_code'] )
			);

			if ( ! empty( $_POST['remove_coupon_code'] ) ) {
				$coupon->remove_coupon_application( $member->id, $membership_id );
				$coupon = MS_Factory::create( ' MS_Model_Coupon' );
			}
			elseif ( isset( $_POST['apply_coupon_code'] ) ) {
				if ( $coupon->is_valid_coupon( $membership_id ) ) {
					$coupon->save_coupon_application( $ms_relationship );
					$data['coupon_valid'] = true;
				}
				else {
					$data['coupon_valid'] = false;
				}
			}
		}
		else {
			$coupon = MS_Factory::create( 'MS_Model_Coupon' );
		}

		$data['coupon'] = $coupon;
		$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
		$data['invoice'] = $invoice;

		if ( $invoice->trial_period ) {
			$next_invoice = MS_Model_Invoice::get_next_invoice( $ms_relationship );
			$data['next_invoice'] = $next_invoice;
		}

		$data['membership'] = $membership;
		$data['member'] = $member;
		$data['ms_relationship'] = $ms_relationship;

		$view = MS_Factory::create( 'MS_View_Frontend_Payment' );
		$view->data = apply_filters( 'ms_view_frontend_payment_data', $data, $this );

		return apply_filters(
			'ms_controller_frontend_payment_table',
			$view->to_html(),
			$this
		);
	}

	/**
	 * Handles membership_cancel action.
	 *
	 * @since 1.0.0
	 */
	public function membership_cancel() {
		if ( ! empty( $_POST['membership_id'] ) && $this->verify_nonce() ) {
			$membership_id = absint( $_POST['membership_id'] );
			$member = MS_Model_Member::get_current_member();
			$member->cancel_membership( $membership_id );
			$member->save();

			$ms_pages = MS_Factory::load( 'MS_Model_Pages' );
			$url = $ms_pages->get_ms_page_url( MS_Model_Pages::MS_PAGE_REGISTER );
			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * Manage user account actions.
	 *
	 * @since 1.0.0
	 *
	 */
	public function user_account_mgr() {
		$action = $this->get_action();
		$member = MS_Model_Member::get_current_member();

		switch ( $action ) {
			case self::ACTION_EDIT_PROFILE:
				$data = array();

				if ( $this->verify_nonce() ) {
					if ( is_array( $_POST ) ) {
						foreach ( $_POST as $field => $value ) {
							$member->$field = $value;
						}
					}
					try {
						$member->validate_member_info();
						$member->save();
						wp_safe_redirect( remove_query_arg( 'action' ) );
						exit;

					}
					catch (Exception $e) {
						$data['errors']  = $e->getMessage();
					}
				}
				$view = MS_Factory::create( 'MS_View_Frontend_Profile' );
				$data['member'] = $member;
				$data['action'] = $action;
				$view->data = apply_filters( 'ms_view_frontend_profile_data', $data, $this );
				$view->add_filter( 'the_content', 'to_html', 1 );
				break;

			case self::ACTION_VIEW_INVOICES:
				$data['invoices'] = MS_Model_Invoice::get_invoices(
					array(
						'author' => $member->id,
						'posts_per_page' => -1,
						'meta_query' => array(
							array(
								'key' => 'amount',
								'value' => '0',
								'compare' => '!=',
							),
						)
					)
				);

				$view = MS_Factory::create( 'MS_View_Frontend_Invoices' );
				$view->data = apply_filters(
					'ms_view_frontend_frontend_invoices',
					$data,
					$this
				);
				$view->add_filter( 'the_content', 'to_html', 1 );
				break;

			case self::ACTION_VIEW_ACTIVITIES:
				$data['events'] = MS_Model_Event::get_events(
					array(
						'author' => $member->id,
						'posts_per_page' => -1,
					)
				);

				$view = MS_Factory::create( 'MS_View_Frontend_Activities' );
				$view->data = apply_filters(
					'ms_view_frontend_frontend_activities',
					$data,
					$this
				);
				$view->add_filter( 'the_content', 'to_html', 1 );
				break;

			case self::ACTION_VIEW_RESETPASS:
				/**
				 * Reset password action.
				 * This action is accessed via the password-reset email
				 * @see  class-ms-controller-dialog.php
				 *
				 * The action is targeted to the Account-page but actually calls
				 * the Login-Shortcode.
				 */
				$view = MS_Factory::create( 'MS_View_Shortcode_Membership_Login' );
				$view->data = array( 'action' => 'resetpass' );

				$view->add_filter( 'the_content', 'to_html', 1 );
				break;

			default:
				do_action( 'ms_controller_frontend_user_account_mgr_' . $action, $this );
				$this->add_filter( 'the_content', 'user_account', 1 );
				break;
		}
	}

	/**
	 * Show user account page.
	 *
	 * Search for account shortcode, injecting if not found.
	 *
	 * Related Filter Hooks:
	 * * the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function user_account( $content ) {
		remove_filter( 'the_content', 'wpautop' );

		if ( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_MS_ACCOUNT, $content ) ) {
			$content .= do_shortcode( '['. MS_Helper_Shortcode::SCODE_MS_ACCOUNT .']' );
		}

		return apply_filters(
			'ms_controller_frontend_user_account',
			$content,
			$this
		);
	}

	/**
	 * Show protected page.
	 *
	 * Search for login shortcode, injecting if not found.
	 *
	 * Related Filter Hooks:
	 * - the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function protected_page( $content ) {
		$setting = MS_Plugin::instance()->settings;
		$protection_msg = $setting->get_protection_message(
			MS_Model_Settings::PROTECTION_MSG_CONTENT
		);

		if ( ! empty( $protection_msg ) ) {
			$content .= $protection_msg;
		}

		if ( ! MS_Model_Member::is_logged_user()
			&& ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_LOGIN, $content ) ) {

			$scode = '[' . MS_Helper_Shortcode::SCODE_LOGIN . ']';
			$content .= do_shortcode( $scode );
		}

		return apply_filters(
			'ms_controller_frontend_protected_page',
			$content,
			$this
		);
	}

	/**
	 * Show registration complete page.
	 *
	 * Related Filter Hooks:
	 * * the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function reg_complete_page( $content ) {
		return apply_filters(
			'ms_controller_frontend_reg_complete_page',
			$content,
			$this
		);
	}

	/**
	 * Display login form.
	 *
	 * Search for login shortcode, injecting if not found.
	 *
	 * Related Filter Hooks:
	 * * the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function display_login_form( $content ) {
		if ( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_LOGIN, $content ) ) {
			$scode = '[' . MS_Helper_Shortcode::SCODE_LOGIN . ']';
			$content = do_shortcode( $scode );
		}

		return apply_filters(
			'ms_controller_frontend_display_login_form',
			$content,
			$this
		);
	}

	/**
	 * Get the URL the user used to register for a subscription.
	 *
	 * Uses the default registration page unless the registration was embedded on another page (e.g. using a shortcode).
	 *
	 * Related Filter Hooks:
	 *
	 * * wp_signup_location
	 * * register_url
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The url to filter.
	 * @return The new signup url.
	 */
	public function signup_location( $url ) {
		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );
		$url = $ms_pages->get_ms_page_url( MS_Model_Pages::MS_PAGE_REGISTER );

		return apply_filters(
			'ms_controller_frontend_signup_location',
			$url,
			$this
		);
	}

	/**
	 * Propagates SSL cookies when user logs in.
	 *
	 * Related Action Hooks:
	 * - wp_login
	 *
	 * @since 1.0.0
	 *
	 * @param type $login The login info.
	 * @param WP_User $user The user to login.
	 */
	public function propagate_ssl_cookie( $login, WP_User $user ) {
		if ( ! is_ssl() ) {
			wp_set_auth_cookie( $user->ID, true, true );
		}

		do_action(
			'ms_controller_frontend_propagate_ssl_cookie',
			$login,
			$user,
			$this
		);
	}

	/**
	 * Redirect user to account page.
	 *
	 * Only redirect when no previous redirect_to is set or when going to /wp-admin/.
	 *
	 * @since 1.0.0
	 *
	 * @param string $redirect_to URL to redirect to.
	 * @param string $request URL the user is coming from.
	 * @param object $user Logged user's data.
	 * @return string The redirect url.
	 */
	public function login_redirect( $redirect_to, $request, $user ) {
		if ( ! empty( $user->ID )
			&& ! MS_Model_Member::is_admin_user( $user->ID )
			&& ( empty( $redirect_to ) || admin_url() == $redirect_to )
		) {
			$ms_pages = MS_Factory::load( 'MS_Model_Pages' );
			$redirect_to = $ms_pages->get_ms_page_url( MS_Model_Pages::MS_PAGE_ACCOUNT );
		}

		return apply_filters(
			'ms_controller_frontend_login_redirect',
			$redirect_to,
			$request,
			$user,
			$this
		);
	}

	/**
	 * Adds CSS and JS for Membership special pages used in the front end.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		do_action(
			'ms_controller_frontend_enqueue_scripts',
			$this->get_signup_step(),
			$this->get_action(),
			$this
		);

		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );
		$is_ms_page = $ms_pages->is_ms_page();
		$is_profile = self::ACTION_EDIT_PROFILE == $this->get_action()
			&& $ms_pages->is_ms_page( null, MS_Model_Pages::MS_PAGE_ACCOUNT );

		if ( $is_ms_page ) {
			wp_enqueue_style( 'ms-styles' );
		}

		if ( $is_profile ) {
			wp_enqueue_script( 'ms-view-frontend-profile' );
		}
	}
}
