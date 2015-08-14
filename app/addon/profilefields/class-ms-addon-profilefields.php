<?php
/**
 * Add-On controller for: User Profile Fields
 *
 * @since  1.0.1.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Addon_Profilefields extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.1.0
	 */
	const ID = 'profilefields';

	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since  1.0.1.0
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
	 * @since  1.0.1.0
	 */
	public function init() {
		if ( self::is_active() ) {
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'settings_tabs',
				10, 2
			);

			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_render_callback',
				10, 3
			);

			$this->add_action(
				'ms_admin_settings_manager-' . self::ID,
				'save_settings'
			);

			$this->add_filter(
				'ms_shortcode_register_form_fields',
				'customize_register_form',
				9, 2
			);

			$this->add_filter(
				'ms_model_member_create_user_required_fields',
				'required_fields'
			);

			$this->add_action(
				'ms_controller_frontend_register_user_before',
				'register_user'
			);

			$this->add_filter(
				'ms_shortcode_register_form_rules',
				'register_rules'
			);

			$this->add_action(
				'signup_finished',
				'save_xprofile'
			);

			$this->add_filter(
				'ms_view_profile_fields',
				'customize_profile_form',
				10, 2
			);

			$this->add_filter(
				'ms_view_profile_form_rules',
				'profile_rules'
			);

			$this->add_action(
				'ms_frontend_user_account_manager_submit-' . MS_Controller_Frontend::ACTION_EDIT_PROFILE,
				'save_xprofile'
			);
		}
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Profile Fields', MS_TEXT_DOMAIN ),
			'description' => __( 'Customize fields in the user profile and registration form.', MS_TEXT_DOMAIN ),
			'icon' => 'dashicons dashicons-id',
		);

		return $list;
	}

	/**
	 * Add Add-on settings tab in settings page.
	 *
	 * @since  1.0.1.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit.
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID  ] = array(
			'title' => __( 'Profile Fields', MS_TEXT_DOMAIN ),
			'url' => MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
		);

		return $tabs;
	}

	/**
	 * Add Add-on views callback.
	 *
	 * @since  1.0.1.0
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
			$view = MS_Factory::load( 'MS_Addon_Profilefields_View_Settings' );
			$view->data = $data;
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Returns a list with all known user profile fields.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	static public function list_fields() {
		static $Profile_Fields = null;

		if ( null === $Profile_Fields ) {
			$Profile_Fields = array(
				'username' => array(
					'label' => __( 'Username', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'default_reg' => 'required',
					'allowed_reg' => array( 'off', 'required' ),
					'allowed_edit' => array( 'off', 'readonly' ),
				),
				'first_name' => array(
					'label' => __( 'First Name', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'default_reg' => 'optional',
					'default_edit' => 'optional',
				),
				'last_name' => array(
					'label' => __( 'Last Name', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'default_reg' => 'optional',
					'default_edit' => 'optional',
				),
				'nickname' => array(
					'label' => __( 'Nickname', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				),
				'display_name' => array(
					'label' => __( 'Display As', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				),
				'email' => array(
					'label' => __( 'Email', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_EMAIL,
					'default_reg' => 'required',
					'default_edit' => 'required',
					'allowed_reg' => array( 'required' ),
					'allowed_edit' => array( 'required' ),
				),
				'website' => array(
					'label' => __( 'Website', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				),
				'description' => array(
					'label' => __( 'Biographic Info', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
				),
				'password' => array(
					'label' => __( 'Password', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
					'default_reg' => 'required',
					'default_edit' => 'optional',
					'allowed_reg' => array( 'off', 'required' ),
					'allowed_edit' => array( 'off', 'optional' ),
				),
				'password2' => array(
					'label' => __( 'Password Confirmation', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
					'default_reg' => 'required',
					'default_edit' => 'optional',
					'allowed_reg' => array( 'off', 'required' ),
					'allowed_edit' => array( 'off', 'optional' ),
				),
			);

			if ( is_user_logged_in() ) {
				$member = MS_Model_Member::get_current_member();
				$user = $member->get_user();

				$Profile_Fields['username']['value'] = $member->username;
				$Profile_Fields['first_name']['value'] = $member->first_name;
				$Profile_Fields['last_name']['value'] = $member->last_name;
				$Profile_Fields['email']['value'] = $member->email;
				$Profile_Fields['nickname']['value'] = $member->get_meta( 'nickname' );
				$Profile_Fields['display_name']['value'] = $user->display_name;
				$Profile_Fields['website']['value'] = $user->user_url;
				$Profile_Fields['description']['value'] = $member->get_meta( 'description' );
			}

			if ( function_exists( 'bp_is_active' ) && bp_is_active( 'xprofile' ) ) {
				$profile_groups = BP_XProfile_Group::get(
					array( 'fetch_fields' => true )
				);
				$profile_groups = lib2()->array->get( $profile_groups );

				foreach ( $profile_groups as $profile_group ) {
					$fields = lib2()->array->get( $profile_group->fields );
					foreach ( $fields as $field ) {
						$Profile_Fields['xprofile_' . $field->id] = array(
							'label' => $field->name,
						);
					}
				}
			}
		}

		return $Profile_Fields;
	}

	/**
	 * Save the settings form provided by view/settings.
	 * Permissions/Nonce have already been validated.
	 *
	 * @since  1.0.1.0
	 */
	public function save_settings() {
		if ( empty( $_POST['register'] ) ) { return; }
		if ( empty( $_POST['profile'] ) ) { return; }
		if ( ! is_array( $_POST['register'] ) ) { return; }
		if ( ! is_array( $_POST['profile'] ) ) { return; }

		$settings = MS_Plugin::instance()->settings;

		$settings->set_custom_setting(
			'profilefields',
			'register',
			$_POST['register']
		);
		$settings->set_custom_setting(
			'profilefields',
			'profile',
			$_POST['profile']
		);

		$settings->save();
	}

	/**
	 * Customizes the fields displayed in the registration form.
	 *
	 * @since  1.0.1.0
	 * @param  array $fields List of default fields.
	 * @param  MS_View $view The registration view.
	 * @return array Modified list of fields.
	 */
	public function customize_register_form( $fields, $view ) {
		$settings = MS_Plugin::instance()->settings;
		$config = $settings->get_custom_setting( 'profilefields', 'register' );
		$data = $view->data;

		if ( empty( $config ) ) {
			// No configuration defined yet, use default fields.
			return $fields;
		}

		$data['xprofile_field_ids'] = 'signup_profile_field_ids';

		$custom_fields = array(
			$fields['membership_id'],
			$fields['action'],
			$fields['step'],
		);

		$custom_fields = $this->customize_form( $custom_fields, $data, $config );

		return $custom_fields;
	}

	/**
	 * Filters the list of required fields that is checked during user
	 * registration.
	 *
	 * @since  1.0.1.0
	 * @param  array $fields List of field IDs.
	 * @return array List of field IDs.
	 */
	public function required_fields( $fields ) {
		$settings = MS_Plugin::instance()->settings;
		$config = $settings->get_custom_setting( 'profilefields', 'register' );
		$all_fields = self::list_fields();

		$required = array();
		foreach ( $config as $field => $setting ) {
			if ( 'off' == $setting ) { continue; }
			$key = $field;
			if ( 0 === strpos( $field, 'xprofile_' ) ) {
				$key = 'field_' . substr( $field, 9 );
			}

			if ( 'required' == $setting ) {
				$required[$key] = $all_fields[$field]['label'];
			}
		}

		return $required;
	}

	/**
	 * Customizes the fields displayed in the profile form.
	 *
	 * @since  1.0.1.0
	 * @param  array $fields List of default fields.
	 * @param  MS_View $view The registration view.
	 * @return array Modified list of fields.
	 */
	public function customize_profile_form( $fields, $view ) {
		$settings = MS_Plugin::instance()->settings;
		$config = $settings->get_custom_setting( 'profilefields', 'profile' );
		$data = $view->data;

		if ( empty( $config ) ) {
			// No configuration defined yet, use default fields.
			return $fields;
		}

		$data['xprofile_field_ids'] = 'xprofile_field_ids';

		$submit_field = $fields['submit'];
		$custom_fields = array(
			$fields['_wpnonce'],
			$fields['action'],
		);

		$custom_fields = $this->customize_form( $custom_fields, $data, $config );

		$custom_fields[] = $submit_field;

		return $custom_fields;
	}

	/**
	 * Customizes the fields displayed in the registration form.
	 *
	 * @since  1.0.1.0
	 * @param  array $fields List of default fields.
	 * @param  array $data Configuration options (field values, titles, etc).
	 * @param  array $config Form configuration from M2 Settings.
	 * @return array Modified list of fields.
	 */
	protected function customize_form( $fields, $data, $config ) {
		$all_fields = self::list_fields();
		$xprofile_fields = array();

		foreach ( $all_fields as $id => $defaults ) {
			$setting = 'off';
			if ( isset( $config[$id] ) ) {
				$setting = $config[$id];
			}

			if ( 'off' == $setting ) { continue; }

			if ( 0 === strpos( $id, 'xprofile_' ) ) {
				$field_id = substr( $id, 9 );
				$fields[] = $this->render_xprofile_field( $field_id );
				$xprofile_fields[] = $field_id;
			} else {
				$hint = '';
				$label = $defaults['label'];
				$type = MS_Helper_Html::INPUT_TYPE_TEXT;
				$value = '';

				if ( isset( $data['hint_' . $id] ) ) {
					$hint = $data['hint_' . $id];
				}
				if ( isset( $data['label_' . $id] ) ) {
					$label = $data['label_' . $id];
				}
				if ( isset( $data[$id] ) ) {
					$value = $data[$id];
				} elseif ( isset( $data['value_' . $id] ) ) {
					$value = $data['value_' . $id];
				} elseif ( isset( $defaults['value'] ) ) {
					$value = $defaults['value'];
				}
				if ( isset( $defaults['type'] ) ) {
					$type = $defaults['type'];
				}

				$fields[] = array(
					'id' => $id,
					'title' => $label,
					'placeholder' => $hint,
					'type' => $type,
					'value' => $value,
				);
			}
		}

		if ( count( $xprofile_fields ) ) {
			$fields[] = array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => $data['xprofile_field_ids'],
				'value' => implode( ',', $xprofile_fields ),
			);
		}

		return $fields;
	}

	/**
	 * Modifies the JS validation rules that are used in the registration form.
	 *
	 * @since  1.0.1.0
	 * @param  array $rules The default validation rules.
	 * @return array Modified rules.
	 */
	public function register_rules( $rules ) {
		$settings = MS_Plugin::instance()->settings;
		$config = $settings->get_custom_setting( 'profilefields', 'register' );

		return $this->validation_rules( $config );
	}

	/**
	 * Modifies the JS validation rules that are used in the profile form.
	 *
	 * @since  1.0.1.0
	 * @param  array $rules The default validation rules.
	 * @return array Modified rules.
	 */
	public function profile_rules( $rules ) {
		$settings = MS_Plugin::instance()->settings;
		$config = $settings->get_custom_setting( 'profilefields', 'profile' );

		return $this->validation_rules( $config );
	}

	/**
	 * Generates the JS validation rule object from given configuration.
	 *
	 * @since  1.0.1.0
	 * @param  array $config The form configuration from M2 Settings.
	 * @return array Modified rules.
	 */
	public function validation_rules( $config ) {
		$rules = array();

		foreach ( $config as $field => $setting ) {
			if ( 'off' == $setting ) { continue; }
			$key = $field;
			if ( 0 === strpos( $field, 'xprofile_' ) ) {
				$key = 'field_' . substr( $field, 9 );
			}
			$rules[$key] = array();

			if ( 'required' == $setting ) {
				$rules[$key]['required'] = true;
			} else {
				$rules[$key]['required'] = false;
			}

			switch ( $field ) {
				case 'email':
					$rules[$key]['email'] = true;
					break;

				case 'password':
					$rules[$key]['minlength'] = 5;
					break;

				case 'password2':
					$rules[$key]['equalTo'] = '#password';
					break;
			}
		}

		return $rules;
	}

	/**
	 * After the registration form was submitted this function pre-processes the
	 * $_REQUEST parameters if required.
	 *
	 * @since  1.0.1.0
	 * @param  MS_Controller_Frontend $controller
	 */
	public function register_user( $controller ) {
		if ( ! isset( $_REQUEST['step'] ) ) {
			// We should never end up in this situation.
			// But StarTrek taught us to prepare for the impossible!
			return;
		}

		$step = $_REQUEST['step'];
		if ( $step != MS_Controller_Frontend::STEP_REGISTER_SUBMIT ) {
			// The registration form was redirected. Do not handle again.
			return;
		}
		if ( ! isset( $_REQUEST['email'] ) ) {
			// Also this is not supposed to happen. Ever. Would mess things up.
			return;
		}

		// Username might be missing, then use email address for username.
		if ( ! isset( $_REQUEST['username'] ) ) {
			$_REQUEST['username'] = $_REQUEST['email'];
		}

		// Password confirmation is optional.
		if ( isset( $_REQUEST['password'] ) && ! isset( $_REQUEST['password2'] ) ) {
			$_REQUEST['password2'] = $_REQUEST['password'];
		}
	}

	/**
	 * Save data from the REQUEST collection to the XProfile fields.
	 *
	 * This action is called in two cases:
	 * 1. After a new user was created in the Database.
	 * 2. When the user saves his profile in M2 frontend.
	 *
	 * @since  1.0.1.0
	 */
	public function save_xprofile() {
		$fields = false;
		$user = false;

		if ( ! isset( $_REQUEST['email'] ) ) {
			// Seems like the user was not created by M2. Not our call.
			return;
		}
		if ( isset( $_REQUEST['signup_profile_field_ids'] ) ) {
			// A new user was created in the database. Great job!
			$user = get_user_by( 'email', $_REQUEST['email'] );
			$fields = explode( ',', $_REQUEST['signup_profile_field_ids'] );
		} elseif ( isset( $_REQUEST['xprofile_field_ids'] ) ) {
			// A new user was created in the database. Great job!
			$user = MS_Model_Member::get_current_member()->get_user();
			$fields = explode( ',', $_REQUEST['xprofile_field_ids'] );
		}

		if ( $fields && $user ) {
			foreach ( $fields as $field_id ) {
				if ( ! isset( $_REQUEST['field_' . $field_id] ) ) {
					continue;
				}

				xprofile_set_field_data(
					$field_id,
					$user->ID,
					$_REQUEST['field_' . $field_id]
				);
			}
		}
	}

	/**
	 * Generates the HTML code for a single XProfile input field.
	 *
	 * Code is taken from the BuddyPress default theme file:
	 * plugins/buddypress/bp-themes/bp-default/registration/register.php
	 *
	 * @since  1.0.1.0
	 * @param  int $field_id The XProfile field ID.
	 * @param  mixed $field_value Value of the field.
	 * @return string The HTML code to display the field.
	 */
	public function render_xprofile_field( $field_id, $field_value = null, $visibility = false ) {
		global $field;
		$field = xprofile_get_field( $field_id );

		ob_start();
		?>
		<div class="ms-form-element ms-form-element-xprofile editfield field-<?php echo $field_id; ?>">

			<?php if ( 'textarea' == bp_get_the_profile_field_type() ) { ?>

				<label for="<?php bp_the_profile_field_input_name(); ?>"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></label>
				<?php do_action( bp_get_the_profile_field_errors_action() ); ?>
				<textarea rows="5" cols="40" name="<?php bp_the_profile_field_input_name(); ?>" id="<?php bp_the_profile_field_input_name(); ?>"><?php bp_the_profile_field_edit_value(); ?></textarea>

			<?php } elseif ( 'selectbox' == bp_get_the_profile_field_type() ) { ?>

				<label for="<?php bp_the_profile_field_input_name(); ?>"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></label>
				<?php do_action( bp_get_the_profile_field_errors_action() ); ?>
				<select name="<?php bp_the_profile_field_input_name(); ?>" id="<?php bp_the_profile_field_input_name(); ?>">
					<?php bp_the_profile_field_options(); ?>
				</select>

			<?php } elseif ( 'multiselectbox' == bp_get_the_profile_field_type() ) { ?>

				<label for="<?php bp_the_profile_field_input_name(); ?>"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></label>
				<?php do_action( bp_get_the_profile_field_errors_action() ); ?>
				<select name="<?php bp_the_profile_field_input_name(); ?>" id="<?php bp_the_profile_field_input_name(); ?>" multiple="multiple">
					<?php bp_the_profile_field_options(); ?>
				</select>

			<?php } elseif ( 'radio' == bp_get_the_profile_field_type() ) { ?>

				<div class="radio">
					<span class="label"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></span>

					<?php do_action( bp_get_the_profile_field_errors_action() ); ?>
					<?php bp_the_profile_field_options(); ?>

					<?php if ( ! bp_get_the_profile_field_is_required() ) : ?>
						<a class="clear-value" href="javascript:clear( '<?php bp_the_profile_field_input_name(); ?>' );"><?php _e( 'Clear', 'buddypress' ); ?></a>
					<?php endif; ?>
				</div>

			<?php } elseif ( 'checkbox' == bp_get_the_profile_field_type() ) { ?>

				<div class="checkbox">
					<span class="label"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></span>

					<?php do_action( bp_get_the_profile_field_errors_action() ); ?>
					<?php bp_the_profile_field_options(); ?>
				</div>

			<?php } elseif ( 'datebox' == bp_get_the_profile_field_type() ) { ?>

				<div class="datebox">
					<label for="<?php bp_the_profile_field_input_name(); ?>_day"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></label>
					<?php do_action( bp_get_the_profile_field_errors_action() ); ?>

					<select name="<?php bp_the_profile_field_input_name(); ?>_day" id="<?php bp_the_profile_field_input_name(); ?>_day">
						<?php bp_the_profile_field_options( 'type=day' ); ?>
					</select>

					<select name="<?php bp_the_profile_field_input_name(); ?>_month" id="<?php bp_the_profile_field_input_name(); ?>_month">
						<?php bp_the_profile_field_options( 'type=month' ); ?>
					</select>

					<select name="<?php bp_the_profile_field_input_name(); ?>_year" id="<?php bp_the_profile_field_input_name(); ?>_year">
						<?php bp_the_profile_field_options( 'type=year' ); ?>
					</select>
				</div>

			<?php } else { ?>

				<label for="<?php bp_the_profile_field_input_name(); ?>"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></label>
				<?php do_action( bp_get_the_profile_field_errors_action() ); ?>
				<input type="<?php bp_the_profile_field_type() ?>" name="<?php bp_the_profile_field_input_name(); ?>" id="<?php bp_the_profile_field_input_name(); ?>" value="<?php bp_the_profile_field_edit_value(); ?>" />

			<?php } ?>

			<?php if ( $visibility ) : ?>
				<?php do_action( 'bp_custom_profile_edit_fields_pre_visibility' ); ?>

				<?php if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>
					<p class="field-visibility-settings-toggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
						<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', 'buddypress' ), bp_get_the_profile_field_visibility_level_label() ) ?> <a href="#" class="visibility-toggle-link"><?php _ex( 'Change', 'Change profile field visibility level', 'buddypress' ); ?></a>
					</p>

					<div class="field-visibility-settings" id="field-visibility-settings-<?php bp_the_profile_field_id() ?>">
						<fieldset>
							<legend><?php _e( 'Who can see this field?', 'buddypress' ) ?></legend>

							<?php bp_profile_visibility_radio_buttons() ?>

						</fieldset>
						<a class="field-visibility-settings-close" href="#"><?php _e( 'Close', 'buddypress' ) ?></a>

					</div>
				<?php else : ?>
					<p class="field-visibility-settings-notoggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
						<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', 'buddypress' ), bp_get_the_profile_field_visibility_level_label() ) ?>
					</p>
				<?php endif ?>
			<?php endif ?>

			<?php do_action( 'bp_custom_profile_edit_fields' ); ?>

			<p class="description"><?php bp_the_profile_field_description(); ?></p>

		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}
}