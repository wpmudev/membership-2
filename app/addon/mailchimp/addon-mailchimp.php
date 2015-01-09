<?php
/**
 * An Addon controller.
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
 * Add-On controller for: MailChimp
 *
 * @since 1.1.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Addon_Mailchimp extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.0.0
	 */
	const ID = 'mailchimp';

	static protected $mailchimp_api = '';

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $addons ) {
		$addons[ self::ID ] = (object) array(
			'name' => __( 'MailChimp Integration', MS_TEXT_DOMAIN ),
			'description' => __( 'Enable MailChimp integration.', MS_TEXT_DOMAIN ),
		);

		return $addons;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {

	}

	/**
	 * Activates the Add-on logic, only executed when add-on is active.
	 *
	 * @since  1.1.0
	 */
	public function activate() {
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

		$this->add_action(
			'ms_model_event_'. MS_Model_Event::TYPE_MS_REGISTERED,
			'subscribe_registered',
			10, 2
		);
		$this->add_action(
			'ms_model_event_'. MS_Model_Event::TYPE_MS_SIGNED_UP,
			'subscribe_members',
			10, 2
		);
		$this->add_action(
			'ms_model_event_'. MS_Model_Event::TYPE_MS_DEACTIVATED,
			'subscribe_deactivated',
			10, 2
		);
		$this->add_action(
			'ms_model_event_'. MS_Model_Event::TYPE_UPDATED_INFO,
			'update_info',
			10, 2
		);
	}

	/**
	 * A new user registered (not a Member yet).
	 *
	 * @param mixed $event
	 * @param mixed $member
	 */
	public function subscribe_registered( $event, $member ) {
		if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' ) ) {
			if ( ! self::is_user_subscribed( $member->email, $list_id ) ) {
				self::subscribe_user( $member, $list_id );
			}
		}
	}

	/**
	 * A user subscribed to a membership.
	 *
	 * @param mixed $event
	 * @param mixed $member
	 */
	public function subscribe_members( $event, $ms_relationship ) {
		$member = $ms_relationship->get_member();

		/** Verify if is subscribed to registered mail list and remove it. */
		if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' ) ) {
			if ( self::is_user_subscribed( $member->email, $list_id ) ) {
				self::unsubscribe_user( $member->email, $list_id );
			}
		}

		/** Subscribe to members mail list. */
		if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_members' ) ) {
			if ( ! self::is_user_subscribed( $member->email, $list_id ) ) {
				self::subscribe_user( $member, $list_id );
			}
		}

		/** Verify if is subscribed to deactivated mail list and remove it. */
		if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' ) ) {
			if ( self::is_user_subscribed( $member->email, $list_id ) ) {
				self::unsubscribe_user( $member->email, $list_id );
			}
		}

	}

	/**
	 * A membership was deactivated (e.g. expired or manually cancelled)
	 *
	 * @param mixed $event
	 * @param mixed $member
	 */
	public function subscribe_deactivated( $event, $ms_relationship ) {
		$member = $ms_relationship->get_member();

		// Verify if is subscribed to registered mail list and remove it.
		if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' ) ) {
			if ( self::is_user_subscribed( $member->email, $list_id ) ) {
				self::unsubscribe_user( $member->email, $list_id );
			}
		}

		// Verify if is subscribed to members mail list and remove it.
		if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_members' ) ) {
			if ( self::is_user_subscribed( $member->email, $list_id ) ) {
				self::unsubscribe_user( $member->email, $list_id );
			}
		}

		// Subscribe to deactiveted members mail list.
		if ( $list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' ) ) {
			if ( ! self::is_user_subscribed( $member->email, $list_id ) ) {
				self::subscribe_user( $member, $list_id );
			}
		}
	}

	/**
	 * A membership was updated
	 *
	 * @param mixed $event
	 * @param mixed $member
	 */
	public function update_info( $event, $data ) {

	}

	/**
	 * Add mailchimp settings tab in settings page.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID  ] = array(
			'title' => __( 'MailChimp', MS_TEXT_DOMAIN ),
			'url' => 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG . '-settings&tab=' . self::ID,
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
			'ms_init' => 'view_settings_mailchimp',
		);

		wp_localize_script( 'ms-admin', 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

	/**
	 * Add mailchimp views callback.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_view_membership_edit_render_callback
	 *
	 * @param array $callback The current function callback.
	 * @param string $tab The current membership rule tab.
	 * @param array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view = new MS_Addon_Mailchimp_View_Settings();
			$view->data = $data;
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Get mailchimp api lib status.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean true on successfully loaded api, false otherwise.
	 */
	public static function get_api_status() {
		$status = false;

		try {
			self::load_mailchimp_api();
			$status = true;
		}
		catch( Exception $e ) {
			MS_Helper_Debug::log( $e );
		}

		return $status;
	}

	/**
	 * Load the Mailchimp API
	 *
	 * @since 1.0.0
	 *
	 * @return Mailchimp Object
	 */
	public static function load_mailchimp_api() {
		if ( empty( self::$mailchimp_api ) ) {

			if ( empty( $mailchimp_sync->api ) ) {

				$options = apply_filters(
					'ms_integration_mailchimp_load_mailchimp_api_options',
					array(
						'timeout' => false,
						'ssl_verifypeer' => false,
						'ssl_verifyhost' => false,
						'ssl_cainfo' => false,
						'debug' => false,
					)
				);

				if ( ! class_exists( 'Mailchimp' ) ) {
					require_once MS_Plugin::instance()->dir . '/lib/mailchimp-api/Mailchimp.php';
				}

				$api = new Mailchimp(
					self::$settings->get_custom_setting( 'mailchimp', 'api_key' ),
					$options
				);

				// Pinging the server
				$ping = $api->helper->ping();

				if ( is_wp_error( $ping ) ) {
					throw new Exception( $ping );
				}

				self::$mailchimp_api = $api;
			} else {
				self::$mailchimp_api = $mailchimp_sync->api;
			}
		}

		return self::$mailchimp_api;
	}

	/**
	 * Get the lists of a Mailchimp account.
	 *
	 * @return Array Lists info
	 */
	public static function get_mail_lists() {
		$mail_lists = array( 0 => __( 'none', MS_TEXT_DOMAIN ) );

		if ( self::get_api_status() ) {
			$lists = self::$mailchimp_api->lists->getList();

			if ( is_wp_error( $lists ) ) {
				MS_Helper_Debug::log( $lists );
			} else {
				foreach ( $lists['data'] as $list ) {
					$mail_lists[ $list['id'] ] = $list['name'];
				}
			}
		}

		return $mail_lists;
	}

	/**
	 * Check if a user is subscribed in the list
	 *
	 * @param String $user_email
	 * @param String $list_id
	 * @return Boolean. True if the user is subscribed already to the list
	 */
	public static function is_user_subscribed( $user_email, $list_id ) {
		$subscribed = false;

		if ( is_email( $user_email ) && self::get_api_status() ) {
			$emails = array(
				array( 'email' => $user_email ),
			);

			$results = self::$mailchimp_api->lists->memberInfo( $list_id, $emails );

			if ( is_wp_error( $results ) ) {
				MS_Helper_Debug::log( $results );
			} elseif ( ! empty( $results['success_count'] )
				&& ! empty( $results['data'][0]['status'] )
				&& 'subscribed' == $results['data'][0]['status']
			) {
				$subscribed = true;
			}
		}

		return $subscribed;
	}

	/**
	 * Subscribe a user to a Mailchimp list
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Member $member
	 * @param int $list_id
	 */
	public static function subscribe_user( $member, $list_id ) {
		if ( is_email( $member->email ) && self::get_api_status() ) {
			$auto_opt_in = self::$settings->get_custom_setting( 'mailchimp', 'auto_opt_in' );
			$update = apply_filters( 'ms_integration_mailchimp_subscribe_user_update', true, $member, $list_id );

			$merge_vars = array();
			if ( ! empty( $member->first_name ) ) {
				$merge_vars['FNAME'] = $member->first_name;
			}

			if ( ! empty( $member->last_name ) ) {
				$merge_vars['LNAME'] = $member->last_name;
			}

			if ( $auto_opt_in ) {
				$merge_vars['optin_ip'] = $_SERVER['REMOTE_ADDR'];
				$merge_vars['optin_time'] = MS_Helper_Period::current_time();
			}

			$merge_vars = apply_filters(
				'ms_integration_mailchimp_subscribe_user_merge_vars',
				$merge_vars,
				$member,
				$list_id
			);

			self::$mailchimp_api->lists->subscribe(
				$list_id,
				array( 'email' => $member->email ),
				$merge_vars,
				'html',
				( ! $auto_opt_in ),
				$update
			);
		}
	}

	/**
	 * Update a user data in a list
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_email
	 * @param string $list_id
	 * @param Array $merge_vars {
	 *     $FNAME => First name
	 *     $LNAME => Last Name
	 * }
	 */
	public static function update_user( $user_email, $list_id, $merge_vars ) {
		if ( self::get_api_status() ) {
			$merge_vars['update_existing'] = true;

			return self::$mailchimp_api->lists->updateMember(
				$list_id,
				array( 'email' => $user_email ),
				$merge_vars
			);
		}
	}

	/**
	 * Unsubscribe a user from a list
	 *
	 * @param string $user_email
	 * @param string $list_id
	 * @param boolean $delete True if the user is gonna be deleted from the list (not only unsubscribed)
	 */
	public static function unsubscribe_user( $user_email, $list_id, $delete = false ) {
		if ( self::get_api_status() ) {
			return self::$mailchimp_api->lists->unsubscribe(
				$list_id,
				array( 'email' => $user_email ),
				$delete
			);
		}
	}
}