<?php
/**
 * Add-On controller for: Invitations
 *
 * @since 1.0.0.3
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Addon_Invitation extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.0.0.3
	 */
	const ID = 'invitation';

	/**
	 * The menu slug for the admin page to manage invitation codes.
	 *
	 * @since 1.0.0.3
	 */
	const SLUG = 'invitation';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0.3
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.0.3
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

			// Show Coupon form in the payment-form (Frontend)
			$this->add_action(
				'ms_view_frontend_payment_after',
				'payment_form_fields'
			);
		}
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0.3
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Invitation Codes', MS_TEXT_DOMAIN ) . ' (BETA)',
			'description' => __( 'Users need an invitation code to subscribe to a membership.', MS_TEXT_DOMAIN ),
			'icon' => 'wpmui-fa wpmui-fa-unlock-alt',
		);

		return $list;
	}

	/**
	 * Add the Coupons menu item to the Membership2 menu.
	 *
	 * @since 1.0.0.3
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
					'title' => __( 'Invitation Codes', MS_TEXT_DOMAIN ),
					'slug' => self::SLUG,
				)
			);
			lib2()->array->insert( $items, 'before', 'addon', $menu_item );
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
	 * @since  2.0.0
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
	 * @since  1.0.0.3
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
	 * @since  1.0.0.3
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
	 * @since 1.0.0
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
					remove_query_arg( array( 'invitation_id') )
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
	 * @since 1.0.0.3
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
	 * @since 1.0.0.3
	 */
	public function admin_invitation() {
		$isset = array( 'action', 'invitation_id' );

		if ( self::validate_required( $isset, 'GET', false )
			&& 'edit' == $_GET['action']
		) {
			// Edit action view page request
			$invitation_id = ! empty( $_GET['invitation_id'] ) ? $_GET['invitation_id'] : 0;
			$data['invitation'] = MS_Factory::load( 'MS_Addon_Invitation_Model', $invitation_id );
			$data['memberships'] = array( __( 'Any', MS_TEXT_DOMAIN ) );
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
	 * @since 1.0.0.3
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
	 * Load specific styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
			lib2()->ui->add( 'jquery-ui' );
		}

		do_action( 'ms_addon_invitation_enqueue_styles', $this );
	}

	/**
	 * Load specific scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
			$plugin_url = MS_Plugin::instance()->url;

			wp_enqueue_script( 'jquery-validate' );
			lib2()->ui->add( 'jquery-ui' );
/*
			wp_enqueue_script(
				'ms-view-invitation-edit',
				$plugin_url . '/app/addon/invitation/assets/js/edit.js',
				array( 'jquery' )
			);
*/
		}

		do_action( 'ms_addon_invitation_enqueue_scripts', $this );
	}

	/**
	 * Output a form where the member can enter a coupon code
	 *
	 * @since  1.0.0
	 * @return string HTML code
	 */
	public function payment_form_fields( $data ) {
		echo 'Invitation';
	}

}