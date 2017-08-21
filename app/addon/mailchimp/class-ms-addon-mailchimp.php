<?php
/**
 * Add-On controller for: MailChimp
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Addon_Mailchimp extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'mailchimp';

	/**
	 * Mailchimp API object
	 *
	 * @var M2_Mailchimp
	 */
	static protected $mailchimp_api = null;

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
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'settings_tabs',
				10, 2
			);

			$this->add_action(
				'ms_controller_settings_enqueue_scripts_' . self::ID,
				'enqueue_scripts'
			);

			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_render_callback',
				10, 3
			);

			// Watch for REGISTER event: Subscribe user to list.
			$this->add_action(
				'ms_model_event_'. MS_Model_Event::TYPE_MS_REGISTERED,
				'subscribe_registered',
				10, 2
			);

			// Watch for SIGN UP event: Subscribe user to list.
			$this->add_action(
				'ms_model_event_'. MS_Model_Event::TYPE_MS_SIGNED_UP,
				'subscribe_members',
				10, 2
			);

			// Watch for DEACTIVATE event: Subscribe user to list.
			$this->add_action(
				'ms_model_event_'. MS_Model_Event::TYPE_MS_DEACTIVATED,
				'subscribe_deactivated',
				10, 2
			);

			$this->add_filter(
				'ms_view_membership_details_tab',
				'mc_fields_for_ms',
				10, 3
			);

			$this->add_filter(
				'ms_view_membership_edit_to_html',
				'mc_custom_html',
				10, 3
			);

			$this->add_action(
				'ms_model_membership__set_after',
				'ms_model_membership__set_after_cb',
				10, 3
			);

			$this->add_action(
				'ms_model_membership__get',
				'ms_model_membership__get_cb',
				10, 3
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
			'name' => __( 'MailChimp Integration', 'membership2' ),
			'description' => __( 'Enable MailChimp integration.', 'membership2' ),
			'icon' => 'dashicons dashicons-email',
		);

		return $list;
	}

	/**
	 * A new user registered (not a Member yet).
	 *
	 * @since  1.0.0
	 * @param  mixed $event
	 * @param  mixed $member
	 */
	public function subscribe_registered( $event, $member ) {
		try {
			if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' ) ) {
				if ( ! self::is_user_subscribed( $member->email, $list_id ) ) {
					self::subscribe_user( $member, $list_id );
				}
			}
		} catch ( Exception $e ) {
			// MS_Helper_Debug::debug_log( $e->getMessage() );
		}
	}

	/**
	 * A user subscribed to a membership.
	 *
	 * @since  1.0.0
	 * @param  mixed $event
	 * @param  mixed $member
	 */
	public function subscribe_members( $event, $subscription ) {
		try {
			$member = $subscription->get_member();

			$mail_list_registered = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' );
			$mail_list_deactivated = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' );
			$mail_list_members = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_members' );

			if ( $mail_list_members != $mail_list_registered ) {
				/** Verify if is subscribed to registered mail list and remove it. */
				if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' ) ) {
					if ( self::is_user_subscribed( $member->email, $list_id ) ) {
						self::unsubscribe_user( $member->email, $list_id );
					}
				}
			}

			if ( $mail_list_members != $mail_list_deactivated ) {
				/** Verify if is subscribed to deactivated mail list and remove it. */
				if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' ) ) {
					if ( self::is_user_subscribed( $member->email, $list_id ) ) {
						self::unsubscribe_user( $member->email, $list_id );
					}
				}
			}

			/** Subscribe to members mail list. */
			$custom_list_id = get_option( 'ms_mc_m_id_' . $subscription->membership_id );

			if ( isset( $custom_list_id ) && 0 != $custom_list_id ) {
				$list_id = $custom_list_id;
			} else {
				$list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_members' );
			}

			if ( $list_id ) {
				if ( ! self::is_user_subscribed( $member->email, $list_id ) ) {
					self::subscribe_user( $member, $list_id );
				}
			}
		} catch ( Exception $e ) {
			// MS_Helper_Debug::debug_log( $e->getMessage() );
		}
	}

	/**
	 * A membership was deactivated (e.g. expired or manually cancelled)
	 *
	 * @since  1.0.0
	 * @param  mixed $event
	 * @param  mixed $member
	 */
	public function subscribe_deactivated( $event, $subscription ) {
		try {
			$member = $subscription->get_member();

			//Check if member has a new subscription
			$membership 	= $subscription->get_membership();
			$new_membership = MS_Factory::load(
				'MS_Model_Membership',
				$membership->on_end_membership_id
			);
			if ( !$new_membership->is_valid() ) {

				$mail_list_registered = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' );
				$mail_list_deactivated = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' );
				$mail_list_members = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_members' );

				if ( $mail_list_deactivated == $mail_list_registered ) {
					// Verify if is subscribed to registered mail list and remove it.
					if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' ) ) {
						if ( self::is_user_subscribed( $member->email, $list_id ) ) {
							self::unsubscribe_user( $member->email, $list_id );
						}
					}
				}

				if ( $mail_list_deactivated == $mail_list_members ) {
					// Verify if is subscribed to members mail list and remove it.
					if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_members' ) ) {
						if ( self::is_user_subscribed( $member->email, $list_id ) ) {
							self::unsubscribe_user( $member->email, $list_id );
						}
					}
				}

				// Subscribe to deactiveted members mail list.
				if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' ) ) {
					if ( ! self::is_user_subscribed( $member->email, $list_id ) ) {
						self::subscribe_user( $member, $list_id );
					}
				}
			}
		} catch ( Exception $e ) {
			// MS_Helper_Debug::debug_log( $e->getMessage() );
		}
	}

	/**
	 * Add mailchimp settings tab in settings page.
	 *
	 * @since  1.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param  array $tabs The current tabs.
	 * @param  int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID  ] = array(
			'title' => __( 'MailChimp', 'membership2' ),
			'url' => MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
		);

		return $tabs;
	}

	/**
	 * Enqueue admin scripts in the settings screen.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array( 'view_settings_mailchimp' ),
		);

		lib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

	/**
	 * Add mailchimp views callback.
	 *
	 * @since  1.0.0
	 *
	 * @filter ms_view_settings_edit_render_callback
	 *
	 * @param  array $callback The current function callback.
	 * @param  string $tab The current membership rule tab.
	 * @param  array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view = MS_Factory::load( 'MS_Addon_Mailchimp_View' );
			$view->data = $data;
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Get mailchimp api lib status.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean true on successfully loaded api, false otherwise.
	 */
	public static function get_api_status() {
		$status = false;

		try {
			self::load_mailchimp_api();
			$status = true;
		} catch ( Exception $e ) {
			// MS_Helper_Debug::debug_log( $e );
		}

		return $status;
	}

	/**
	 * Load the Mailchimp API
	 *
	 * @since  1.0.0
	 *
	 * @return M2_Mailchimp Object
	 */
	public static function load_mailchimp_api() {
		if ( empty( self::$mailchimp_api ) ) {
			$options = apply_filters(
				'ms_addon_mailchimp_load_mailchimp_api_options',
				array(
					'timeout' => false,
					'ssl_verifypeer' => false,
					'ssl_verifyhost' => false,
					'ssl_cainfo' => false,
					'debug' => false,
				)
			);

			if ( ! class_exists( 'M2_Mailchimp' ) ) {
				require_once MS_Plugin::instance()->dir . '/lib/mailchimp-api/Mailchimp.php';
			}
			$api_key = self::$settings->get_custom_setting( 'mailchimp', 'api_key' );
			$exploded = explode( '-', $api_key );
			$data_center = end( $exploded );

			$api = new M2_Mailchimp( $api_key, $data_center );

			self::$mailchimp_api = $api;
		}

		return self::$mailchimp_api;
	}

	/**
	 * Get the lists of a Mailchimp account.
	 *
	 * @return Array Lists info
	 */
	public static function get_mail_lists( $default = null ) {
		static $Mail_lists = null;

		if ( null === $default ) {
			$default = __( 'None', 'membership2' );
		}

		if ( null === $Mail_lists ) {
			$Mail_lists = array( 0 => $default );

			if ( self::get_api_status() ) {
				$page = 0;
				$items_per_page = 25;
				$iterations = 0;

				do {
					$lists = self::$mailchimp_api->get_lists(
						$items_per_page,
						$page
					);

					$page += 1;
					$iterations += 1;

					if ( is_wp_error( $lists ) ) {
						$has_more = false;
						// MS_Helper_Debug::debug_log( $lists );
					} else {
						$has_more = count( $lists['data'] ) >= $items_per_page;
						foreach ( $lists['data'] as $list ) {
							$Mail_lists[ $list['id'] ] = $list['name'];
						}
					}

					// Force to exit the loop after max. 100 API calls (2500 lists).
					if ( $iterations > 100 ) {
						$has_more = false;
					}
				} while ( $has_more );
			}
		}

		return $Mail_lists;
	}

	/**
	 * Check if a user is subscribed in the list
	 *
	 * @param  string $user_email
	 * @param  string $list_id
	 * @return bool True if the user is subscribed already to the list
	 */
	public static function is_user_subscribed( $user_email, $list_id ) {
		$subscribed = false;

		if ( is_email( $user_email ) && self::get_api_status() ) {

			$results = self::$mailchimp_api->check_email( $list_id, $user_email );

			if ( !is_wp_error( $results ) ) {
				$subscribed = true;
			} else {
				$this->log( $results->get_error_message() );
				
			}
		}

		return $subscribed;
	}

	/**
	 * Subscribe a user to a Mailchimp list
	 *
	 * @since  1.0.0
	 *
	 * @param  MS_Model_Member $member
	 * @param  int $list_id
	 */
	public static function subscribe_user( $member, $list_id ) {
		if ( is_email( $member->email ) && self::get_api_status() ) {
			$auto_opt_in = self::$settings->get_custom_setting(
				'mailchimp',
				'auto_opt_in'
			);
			$auto_opt_in = lib3()->is_true( $auto_opt_in );

			$update = apply_filters(
				'ms_addon_mailchimp_subscribe_user_update',
				true,
				$member,
				$list_id
			);

			$subscribe_data = array(
				'email_address' => $member->email,
				'status'        => ( $auto_opt_in ) ? 'subscribed' : 'pending'
			);

			$merge_vars = array();
			$merge_vars['FNAME'] = $member->first_name;
			$merge_vars['LNAME'] = $member->last_name;

			if ( empty( $merge_vars['FNAME'] ) ) {
				unset( $merge_vars['FNAME'] );
			}
			if ( empty( $merge_vars['LNAME'] ) ) {
				unset( $merge_vars['LNAME'] );
			}

			$merge_vars = apply_filters(
				'ms_addon_mailchimp_subscribe_user_merge_vars',
				$merge_vars,
				$member,
				$list_id
			);

			$subscribe_data['merge_fields'] = $merge_vars;


			$res = self::$mailchimp_api->subscribe( $list_id, $subscribe_data );

			if ( is_wp_error( $res ) ) {
				echo $res->errorMessage();
			}
		}
	}

	/**
	 * Update a user data in a list
	 *
	 * @since  1.0.0
	 *
	 * @param  string $user_email
	 * @param  string $list_id
	 * @param  array $merge_vars {
	 *     $FNAME => First name
	 *     $LNAME => Last Name
	 * }
	 */
	public static function update_user( $user_email, $list_id, $merge_vars ) {
		if ( self::get_api_status() ) {

			return self::$mailchimp_api->update_subscription(
				$list_id,
				$user_email,
				$merge_vars
			);
		}
	}

	/**
	 * Unsubscribe a user from a list
	 *
	 * @since 1.0.4
	 * @param  string $user_email
	 * @param  string $list_id
	 */
	public static function unsubscribe_user( $user_email, $list_id ) {
		if ( self::get_api_status() ) {
			return self::$mailchimp_api->unsubscribe(
				$list_id,
				$user_email
			);
		}
	}

	/**
	 * Add additional field to show a list of mailchimp list
	 *
	 * @since 1.0.3.0
	 */
	public function mc_fields_for_ms( $fields, $membership, $data ) {

		$mail_list = self::get_mail_lists( __( 'Default', 'membership2' ) );

		$fields['ms_mc'] = array(
			'id' => 'ms_mc',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'title' => __( 'Mailchimp List', 'membership2' ),
			'desc' => __( 'You can select a list for this membership.', 'membership2' ),
			'class' => 'ms-mc',
			'before' => __( 'Select a list', 'membership2' ),
			'value' => $membership->ms_mc,
			'field_options' => $mail_list,
			'ajax_data' => array( 1 ),
		);

		return $fields;

	}

	/**
	 * Modify the edit membership basic settings page
	 *
	 * @since 1.0.3.0
	 */
	public function mc_custom_html( $html, $field, $membership ) {
		ob_start();
		?>
		<div>
			<form class="ms-form wpmui-ajax-update ms-edit-membership" data-wpmui-ajax="<?php echo esc_attr( 'save' ); ?>">
				<div class="ms-form wpmui-form wpmui-grid-8">
					<div class="col-5">
						<?php
						MS_Helper_Html::html_element( $field['name'] );
						if ( ! $membership->is_system() ) {
							MS_Helper_Html::html_element( $field['description'] );
						}
						?>
					</div>
					<div class="col-3">
						<?php
						MS_Helper_Html::html_element( $field['active'] );
						if ( ! $membership->is_system() ) {
							MS_Helper_Html::html_element( $field['public'] );
							MS_Helper_Html::html_element( $field['paid'] );
						}
						?>
					</div>
				</div>
				<div class="ms-form wpmui-form wpmui-grid-8">
					<div class="col-8">
					<?php
					if ( ! $membership->is_system() ) {
						MS_Helper_Html::html_element( $field['priority'] );
					}
					echo '<hr>';
					MS_Helper_Html::html_element( $field['ms_mc'] );
					?>
					</div>
				</div>
			</form>
		</div>
		<?php
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Save custom list for individual membership
	 *
	 * @since 1.0.3.0
	 */
	public function ms_model_membership__set_after_cb( $property, $value, $membership ) {
		if ( 'ms_mc' == $property ) {
			update_option( 'ms_mc_m_id_' . $membership->id, $value );
		}
	}

	/**
	 * Retrieve custom list for indiviaul membership
	 *
	 * @since 1.0.3.0
	 */
	public function ms_model_membership__get_cb( $value, $property, $membership ) {
		if ( 'ms_mc' == $property ) {
			return get_option( 'ms_mc_m_id_' . $membership->id );
		}

		return $value;
	}
}
