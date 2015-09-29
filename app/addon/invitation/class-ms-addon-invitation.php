<?php
/**
 * Add-On controller for: Invitations
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Addon_Invitation extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'invitation';

	/**
	 * The menu slug for the admin page to manage invitation codes.
	 *
	 * @since  1.0.0
	 */
	const SLUG = 'invitation';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.0
	 */
	public function init() {
		if ( self::is_active() ) {
			$hook = 'membership-2_page_' . MS_Controller_Plugin::MENU_SLUG . '-' . self::SLUG;

			$this->add_action( 'load-' . $hook, 'admin_manager' );
			$this->add_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
			$this->add_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );

			// Add Coupon menu item to Membership2 menu (Admin)
			$this->add_filter(
				'ms_plugin_menu_pages',
				'menu_item',
				10, 3
			);

			// Handle the submenu item - display the add-on page.
			$this->add_filter(
				'ms_route_submenu_request',
				'route_submenu_request'
			);

			// Tell Membership2 about the Coupon Post Type
			$this->add_filter(
				'ms_plugin_register_custom_post_types',
				'register_ms_posttypes'
			);

			$this->add_filter(
				'ms_rule_cptgroup_model_get_ms_post_types',
				'update_ms_posttypes'
			);

			// Display a "needs invitation" switch for each membership.
			$this->add_action(
				'ms_view_membership_tab_payment_form',
				'membership_option',
				10, 2
			);

			// ---------- FRONTEND ----------

			// Prevent free memberships from auto-subscribing
			$this->add_filter(
				'ms_view_frontend_payment_skip_form',
				'do_not_skip',
				10, 3
			);

			// Check if an invitation code was specified or not.
			$this->add_filter(
				'ms_view_frontend_payment_data',
				'check_invitation_code',
				10, 4
			);

			$this->add_filter(
				'ms_shortcode_register_form_end',
				'sticky_registration_invitation'
			);

			$this->add_filter(
				'ms_shortcode_signup_form_end',
				'sticky_registration_invitation'
			);
		}
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Invitation Codes', 'membership2' ),
			'description' => __( 'Users need an invitation code to subscribe to a membership.', 'membership2' ),
			'icon' => 'wpmui-fa wpmui-fa-unlock-alt',
		);

		return $list;
	}

	/**
	 * Add the Coupons menu item to the Membership2 menu.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $items List of the current admin menu items.
	 * @param  bool $limited_mode True means either First-Setup or site-admin
	 *         in network wide protection.
	 * @param  MS_Controller $controller
	 * @return array The modified menu array.
	 */
	public function menu_item( $items, $limited_mode, $controller ) {
		if ( ! $limited_mode ) {
			$menu_item = array(
				self::ID => array(
					'title' => __( 'Invitation Codes', 'membership2' ),
					'slug' => self::SLUG,
				)
			);
			lib3()->array->insert( $items, 'before', 'addon', $menu_item );
		}

		return $items;
	}

	/**
	 * Handles all sub-menu clicks. We check if the menu item of our add-on was
	 * clicked and if it was we display the correct page.
	 *
	 * The $handler value is ONLY changed when the current menu is displayed.
	 * If another menu item was clicked then don't do anythign here!
	 *
	 * @since  1.0.0
	 * @param  array $handler {
	 *         Menu-item handling information.
	 *
	 *         0 .. any|network|site  The admin-area that can handle our menu item.
	 *         1 .. callable          A callback to handle the menu item.
	 * @return array Menu-item handling information.
	 */
	public function route_submenu_request( $handler ) {
		if ( MS_Controller_Plugin::is_page( self::SLUG ) ) {
			$handler = array(
				'network',
				array( $this, 'admin_invitation' ),
			);
		}

		return $handler;
	}

	/**
	 * Register the Coupon Post-Type; this is done in MS_Plugin.
	 *
	 * @since  1.0.0
	 * @param  array $cpts
	 * @return array
	 */
	public function register_ms_posttypes( $cpts ) {
		$cpts[MS_Addon_Invitation_Model::get_post_type()] = MS_Addon_Invitation_Model::get_register_post_type_args();

		return $cpts;
	}

	/**
	 * Add the Coupon Post-Type to the list of internal post-types.
	 *
	 * @since  1.0.0
	 * @param  array $cpts
	 * @return array
	 */
	public function update_ms_posttypes( $cpts ) {
		$cpts[] = MS_Addon_Invitation_Model::get_post_type();

		return $cpts;
	}

	/**
	 * Manages invitation admin actions.
	 *
	 * Verifies GET and POST requests to manage billing.
	 *
	 * @since  1.0.0
	 */
	public function admin_manager() {
		$edit_fields = array( 'submit', 'action', 'invitation_id' );
		$action_fields = array( 'action', 'invitation_id' );
		$bulk_fields = array( 'invitation_id' );
		$redirect = false;

		if ( self::validate_required( $edit_fields, 'POST', false )
			&& 'edit' == $_POST['action']
			&& $this->verify_nonce()
			&& $this->is_admin_user()
		) {
			// Save invitation add/edit
			$msg = $this->save_item( $_POST );
			$redirect =	esc_url_raw(
				add_query_arg(
					array( 'msg' => $msg ),
					remove_query_arg(
						array( 'invitation_id')
					)
				)
			);
		} elseif ( self::validate_required( $action_fields, 'GET' )
			&& $this->verify_nonce( $_GET['action'], 'GET' )
			&& $this->is_admin_user()
		) {
			// Execute table single action.
			$msg = $this->do_action( $_GET['action'], array( $_GET['invitation_id'] ) );
			$redirect = esc_url_raw(
				add_query_arg(
					array( 'msg' => $msg ),
					remove_query_arg( array( 'invitation_id', 'action', '_wpnonce' ) )
				)
			);
		} elseif ( self::validate_required( $bulk_fields )
			&& $this->verify_nonce( 'bulk' )
			&& $this->is_admin_user()
		) {
			// Execute bulk actions.
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->do_action( $action, $_POST['invitation_id'] );
			$redirect = esc_url_raw( add_query_arg( array( 'msg' => $msg ) ) );
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Perform actions for each invitation.
	 *
	 * @since  1.0.0
	 * @param string $action The action to perform on selected coupons
	 * @param int[] $coupons The list of coupons ids to process.
	 */
	public function do_action( $action, $item_ids ) {
		if ( ! $this->is_admin_user() ) {
			return;
		}

		if ( is_array( $item_ids ) ) {
			foreach ( $item_ids as $item_id ) {
				switch ( $action ) {
					case 'delete':
						$item = MS_Factory::load( 'MS_Addon_Invitation_Model', $item_id );
						$item->delete();
						break;
				}
			}
		}
	}

	/**
	 * Render the Invitation admin manager.
	 *
	 * @since  1.0.0
	 */
	public function admin_invitation() {
		$isset = array( 'action', 'invitation_id' );

		if ( self::validate_required( $isset, 'GET', false )
			&& 'edit' == $_GET['action']
		) {
			// Edit action view page request
			$invitation_id = ! empty( $_GET['invitation_id'] ) ? $_GET['invitation_id'] : 0;
			$data['invitation'] = MS_Factory::load( 'MS_Addon_Invitation_Model', $invitation_id );
			$data['memberships'] = array( __( 'Any', 'membership2' ) );
			$data['memberships'] += MS_Model_Membership::get_membership_names();
			$data['action'] = $_GET['action'];

			$view = MS_Factory::create( 'MS_Addon_Invitation_View_Edit' );
			$view->data = apply_filters( 'ms_addon_invitation_view_edit_data', $data );
			$view->render();
		} else {
			// Invitation admin list page
			$view = MS_Factory::create( 'MS_Addon_Invitation_View_List' );
			$view->render();
		}
	}

	/**
	 * Save invitation using the invitation model.
	 *
	 * @since  1.0.0
	 *
	 * @param mixed $fields Invitation fields
	 * @return boolean True in success saving.
	 */
	private function save_item( $fields ) {
		$invitation = null;
		$msg = false;

		if ( $this->is_admin_user() ) {
			if ( is_array( $fields ) ) {
				$invitation_id = ( $fields['invitation_id'] ) ? $fields['invitation_id'] : 0;
				$invitation = MS_Factory::load( 'MS_Addon_Invitation_Model', $invitation_id );

				foreach ( $fields as $field => $value ) {
					$invitation->$field = $value;
				}

				$invitation->save();
				$msg = true;
			}
		}

		return apply_filters(
			'ms_addon_invitation_model_save_invitation',
			$msg,
			$fields,
			$invitation,
			$this
		);
	}

	/**
	 * Displays a flag in the payment options page to enable/disable invitation
	 * codes for a specific membership.
	 *
	 * @since  1.0.1.0
	 * @param  MS_View $view The view that called the action.
	 * @param  MS_Model_Membership $membership Membership being edited.
	 */
	public function membership_option( $view, $membership ) {
		$action = MS_Controller_Membership::AJAX_ACTION_SET_CUSTOM_FIELD;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			array(
				'id' => 'no_invitation',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Does this Membership require an Invitation code?', 'membership2' ),
				'value' => lib3()->is_true( $membership->get_custom_data( 'no_invitation' ) ),
				'before' => sprintf(
					'%s <i class="wpmui-fa wpmui-fa-lock"></i>',
					__( 'Yes', 'membership2' )
				),
				'after' => sprintf(
					'<i class="wpmui-fa wpmui-fa-unlock"></i> %s',
					__( 'No (public membership)', 'membership2' )
				),
				'class' => 'reverse',
				'ajax_data' => array(
					'action' => $action,
					'_wpnonce' => $nonce,
					'membership_id' => $membership->id,
					'field' => 'no_invitation',
				),
			),
		);

		foreach ( $fields as $field ) {
			MS_Helper_Html::html_element( $field );
		}
	}

	/**
	 * Load specific styles.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_styles() {
		if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
			lib3()->ui->add( 'jquery-ui' );
		}

		do_action( 'ms_addon_invitation_enqueue_styles', $this );
	}

	/**
	 * Load specific scripts.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
			$plugin_url = MS_Plugin::instance()->url;

			wp_enqueue_script( 'jquery-validate' );
			lib3()->ui->add( 'jquery-ui' );
		}

		do_action( 'ms_addon_invitation_enqueue_scripts', $this );
	}

	/**
	 * Called right before the payment form on the front end is displayed.
	 * We check if the user already specified an invitation code or not.
	 *
	 * If no code was specified then we remove all payment buttons and display
	 * an input field for the invitation code instead.
	 *
	 * @since  1.0.1.0
	 * @param  bool $flag The original Skip-Form flag.
	 * @param  MS_Model_Invoice $invoice
	 * @param  MS_View $view The parent view.
	 * @return bool The modified Skip-Form flag.
	 */
	public function do_not_skip( $flag, $invoice, $view ) {
		$membership = $invoice->get_membership();

		$is_public = lib3()->is_true(
			$membership->get_custom_data( 'no_invitation' )
		);

		if ( ! $is_public ) {
			// Do not skip the form!
			$flag = false;
		}

		return $flag;
	}

	/**
	 * When an invitation code is passed to the registration form then make sure
	 * that the invitation code is preserved upon registration.
	 *
	 * The code can be passed to the registration form either via URL param or
	 * via a (hidden) form input value (i.e. GET or POST)
	 *
	 * @since  1.0.1.2
	 * @filter ms_shortcode_register_form_end (MS_View_Shortcode_RegisterUser)
	 * @filter ms_shortcode_signup_form_end (MS_View_Shortcode_MembershipSignup)
	 */
	public function sticky_registration_invitation() {
		if ( isset( $_REQUEST['invitation_code'] ) ) {
			$field = array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'invitation_code',
				'value' => $_REQUEST['invitation_code'],
			);
			MS_Helper_Html::html_element( $field );
		}
	}

	/**
	 * Called right before the payment form on the front end is displayed.
	 * We check if the user already specified an invitation code or not.
	 *
	 * If no code was specified then we remove all payment buttons and display
	 * an input field for the invitation code instead.
	 *
	 * @since  1.0.1.0
	 * @param  array $data
	 * @param  int $membership_id
	 * @param  MS_Model_Relationship $subscription
	 * @param  MS_Model_Member $member
	 */
	public function check_invitation_code( $data, $membership_id, $subscription, $member ) {
		$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );

		$is_public = lib3()->is_true(
			$membership->get_custom_data( 'no_invitation' )
		);

		if ( $is_public ) {
			// This membership is not require an invitation code.
			return $data;
		}

		// invitation_code can be set via URL param or input field in form.
		if ( ! empty( $_REQUEST['invitation_code'] ) ) {
			$invitation = apply_filters(
				'ms_addon_invitation_model',
				MS_Addon_Invitation_Model::load_by_code( $_REQUEST['invitation_code'] )
			);

			$invitation->save_application( $subscription );
		} else {
			$invitation = MS_Addon_Invitation_Model::get_application(
				$member->id,
				$membership_id
			);
		}

		if ( $invitation && ! empty( $_POST['remove_invitation_code'] ) ) {
			$invitation->remove_application( $member->id, $membership_id );
			$invitation = false;
		}

		$data['invitation'] = $invitation;
		if ( $invitation ) {
			$data['invitation_valid'] = $invitation->is_valid( $membership_id );
		} else {
			$data['invitation_valid'] = false;
		}

		if ( ! $data['invitation_valid'] ) {
			// User has no valid invitation yet, hide all payment buttons
			remove_all_actions( 'ms_view_frontend_payment_purchase_button' );

			// Also remove any other input fields from the payment form, like coupon.
			remove_all_actions( 'ms_view_frontend_payment_after_total_row' );
			remove_all_actions( 'ms_view_frontend_payment_after' );
		}

		// Show Coupon form in the payment-form.
		$this->add_action(
			'ms_view_frontend_payment_purchase_button',
			'payment_form_fields',
			5, 3
		);

		return $data;
	}

	/**
	 * Output a form where the member can enter a invitation code
	 *
	 * @since  1.0.0
	 * @param  MS_Model_Relationship $subscription
	 * @param  MS_Model_Invoice $invoice
	 * @param  MS_View $view The parent view that renders the payment form.
	 * @return string HTML code
	 */
	public function payment_form_fields( $subscription, $invoice, $view ) {
		$data = $view->data;
		$invitation = $data['invitation'];
		$fields = array();
		$message = '';
		$code = '';

		if ( $invitation ) {
			$message = $invitation->invitation_message;
			$code = $invitation->code;
		}

		if ( ! empty( $data['invitation_valid'] ) ) {
			$fields = array(
				'invitation_code' => array(
					'id' => 'invitation_code',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $code,
				),
				'remove_invitation_code' => array(
					'id' => 'remove_invitation_code',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Remove', 'membership2' ),
					'label_class' => 'inline-label',
					'title' => sprintf(
						__( 'Using invitation code %s.', 'membership2' ),
						$code
					),
					'button_value' => 1,
				),
			);
		} else {
			$fields = array(
				'invitation_code' => array(
					'id' => 'invitation_code',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $code,
				),
				'apply_invitation_code' => array(
					'id' => 'apply_invitation_code',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Apply Invitation', 'membership2' ),
				),
			);

			if ( ! $message ) {
				$message = __( 'You need an invitation to register for this Membership', 'membership2' );
			}
		}

		$fields['membership_id'] = array(
			'id' => 'membership_id',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $data['membership']->id,
		);
		$fields['move_from_id'] = array(
			'id' => 'move_from_id',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $data['ms_relationship']->move_from_id,
		);
		$fields['step'] = array(
			'id' => 'step',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => MS_Controller_Frontend::STEP_PAYMENT_TABLE,
		);

		if ( ! empty( $data['invitation_valid'] ) ) {
			$class = 'ms-alert-success';
		} else {
			$class = 'ms-alert-error';
		}
		?>
		<tr class="ms-invitation-code">
			<td colspan="2">
				<form method="post">
					<?php if ( $message ) : ?>
					<p class="ms-alert-box <?php echo esc_attr( $class ); ?>"><?php
						echo $message;
					?></p>
					<?php endif; ?>
					<div class="invitation-entry">
						<?php if ( ! isset( $data['invitation_valid'] ) ) : ?>
							<div class="invitation-question"><?php
							_e( 'Have an invitation code?', 'membership2' );
							?></div>
						<?php endif;

						foreach ( $fields as $field ) {
							MS_Helper_Html::html_element( $field );
						}
						?>
					</div></form>
			</td>
		</tr>
		<?php
	}

}