<?php
class MS_Addon_BuddyPress extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'buddypress';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		if ( ! self::buddypress_active()
			&& MS_Model_Addon::is_enabled( self::ID )
		) {
			$model = MS_Factory::load( 'MS_Model_Addon' );
			$model->disable( self::ID );
		}

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
			$this->add_filter( 'ms_controller_membership_tabs', 'rule_tabs' );
			MS_Factory::load( 'MS_Addon_BuddyPress_Rule' );

			$this->add_filter(
				'ms_frontend_custom_registration_form',
				'registration_form'
			);

			$this->add_action(
				'ms_controller_frontend_register_user_before',
				'prepare_create_user'
			);

			$this->add_action(
				'ms_controller_frontend_register_user_complete',
				'save_custom_fields'
			);

			// Disable BuddyPress Email activation.
			add_filter(
				'bp_core_signup_send_activation_key',
				'__return_false'
			);

			add_filter(
				'bp_registration_needs_activation',
				'__return_false'
			);

			$this->add_action(
				'bp_core_signup_user',
				'disable_validation'
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
			'name' => __( 'BuddyPress Integration', MS_TEXT_DOMAIN ),
			'description' => __( 'Integrate BuddyPress with Membership 2.', MS_TEXT_DOMAIN ),
			'icon' => 'dashicons dashicons-groups',
			'details' => array(
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => __( 'Protection Rules', MS_TEXT_DOMAIN ),
					'desc' => __( 'Adds BuddyPress rules in the "Protection Rules" page.', MS_TEXT_DOMAIN ),
				),
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => __( 'Registration page', MS_TEXT_DOMAIN ),
					'desc' =>
						__( 'The BuddyPress registration page will be used instead of the default Membership 2 registration page.', MS_TEXT_DOMAIN ) .
						'<br />' .
						__( 'New users are automatically activated by Membership 2 and no confirmation email is sent to the user!', MS_TEXT_DOMAIN ),
				),
			),
		);

		if ( ! self::buddypress_active() ) {
			$list[ self::ID ]->description .= sprintf(
				'<br /><b>%s</b>',
				__( 'Activate BuddyPress to use this Add-on', MS_TEXT_DOMAIN )
			);
			$list[ self::ID ]->action = '-';
		}

		return $list;
	}

	/**
	 * Returns true, when the BuddyPress plugin is activated.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function buddypress_active() {
		global $bp;

		return ( ! empty( $bp ) && function_exists( 'bp_buffer_template_part' ) );
	}

	/**
	 * Add buddypress rule tabs in membership level edit.
	 *
	 * @since  1.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function rule_tabs( $tabs ) {
		$rule = MS_Addon_Buddypress_Rule::RULE_ID;
		$tabs[ $rule ] = true;

		return $tabs;
	}

	/**
	 * Display the BuddyPress registration form instead of the default
	 * Membership2 registration form.
	 *
	 * @since  1.0.0
	 * @return string HTML code of the registration form or empty string to use
	 *                the default form.
	 */
	public function registration_form( $code ) {
		global $bp;

		if ( self::buddypress_active() ) {
			// Add Membership2 fields to the form so we know what comes next.
			$this->add_action( 'bp_custom_signup_steps', 'membership_fields' );

			// Redirect everything after the submit button to output buffer...
			$this->add_action(
				'bp_after_registration_submit_buttons',
				'catch_nonce_field',
				9999
			);

			// Tell BuddyPress that we want the registration form.
			$bp->signup->step = 'request-details';

			// Get the BuddyPress registration page.
			$code = bp_buffer_template_part( 'members/register', null, false );

			// Don't add <p> tags, the form is already formatted!
			remove_filter( 'the_content', 'wpautop' );
		}

		return $code;
	}

	/**
	 * Redirects all output to the Buffer, so we can easily discard it later...
	 *
	 * @since  1.0.0
	 */
	public function catch_nonce_field() {
		ob_start();
	}

	/**
	 * Output hidden form fields that are parsed by Membership2 when the
	 * registration was completed.
	 *
	 * This is used to recognize that the registration should be handled by
	 * Membership2 and which screen to display next.
	 *
	 * Note that the form is submitted to Membership2, so we need to
	 * handle the background stuff. BuddyPress will not do it for us...
	 *
	 * @since  1.0.0
	 */
	public function membership_fields() {
		/*
		 * Discard the contents of the output buffer. It only contains the
		 * BuddyPress nonce fields.
		 */
		ob_end_clean();

		$field_membership = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'membership_id',
			'value' => $_REQUEST['membership_id'],
		);
		$field_action = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'action',
			'value' => 'register_user',
		);
		$field_step = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'step',
			'value' => MS_Controller_Frontend::STEP_REGISTER_SUBMIT,
		);

		MS_Helper_Html::html_element( $field_membership );
		MS_Helper_Html::html_element( $field_action );
		MS_Helper_Html::html_element( $field_step );
		wp_nonce_field( $field_action['value'] );
	}

	/**
	 * The Registration form was submitted and the nonce-check verified.
	 * We have to match the BuddyPress field-names with the
	 * Membership2 names.
	 *
	 * This preparation only ensures that the user can be created.
	 * XProfile fields are not handled here...
	 *
	 * @since  1.0.0
	 */
	public function prepare_create_user() {
		$_REQUEST['first_name'] = $_REQUEST['signup_username'];
		$_REQUEST['last_name'] = '';
		$_REQUEST['username'] = $_REQUEST['signup_username'];
		$_REQUEST['email'] = $_REQUEST['signup_email'];
		$_REQUEST['password'] = $_REQUEST['signup_password'];
		$_REQUEST['password2'] = $_REQUEST['signup_password_confirm'];
	}

	/**
	 * After the user was successfully created we now have the opportunity to
	 * save the XProfile fields.
	 *
	 * @see bp-xprofile-screens.php function xprofile_screen_edit_profile()
	 *
	 * @since  1.0.0
	 * @param  WP_User $user The new user.
	 */
	public function save_custom_fields( $user ) {
		if ( ! bp_is_active( 'xprofile' ) ) { return; }

		// Make sure hidden field is passed and populated
		if ( isset( $_POST['signup_profile_field_ids'] )
			&& ! empty( $_POST['signup_profile_field_ids'] )
		) {
			// Let's compact any profile field info into an array
			$profile_field_ids = wp_parse_id_list( $_POST['signup_profile_field_ids'] );

			// Loop through the posted fields formatting any datebox values then add to usermeta
			foreach ( (array) $profile_field_ids as $field_id ) {
				$value = '';
				$visibility = 'public';

				if ( ! isset( $_POST['field_' . $field_id] ) ) {
					// Build the value of date-fields.
					if ( ! empty( $_POST['field_' . $field_id . '_day'] )
						&& ! empty( $_POST['field_' . $field_id . '_month'] )
						&& ! empty( $_POST['field_' . $field_id . '_year'] )
					) {
						// Concatenate the values.
						$date_value =
							$_POST['field_' . $field_id . '_day'] . ' ' .
							$_POST['field_' . $field_id . '_month'] . ' ' .
							$_POST['field_' . $field_id . '_year'];

						// Turn the concatenated value into a timestamp.
						$_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $date_value ) );
					}
				}

				if ( ! empty( $_POST['field_' . $field_id] ) ) {
					$value = $_POST['field_' . $field_id];
				}

				if ( ! empty( $_POST['field_' . $field_id . '_visibility'] ) ) {
					$visibility = $_POST['field_' . $field_id . '_visibility'];
				}

				xprofile_set_field_visibility_level( $field_id, $user->id, $visibility );
				xprofile_set_field_data( $field_id, $user->id, $value, false );
			}
		}
	}

	/**
	 * Automatically confirms new registrations.
	 *
	 * @since  1.0.0
	 * @param  int $user_id The new User-ID
	 */
	public function disable_validation( $user_id ) {
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		$member->confirm();
	}

}