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
				'ms_admin_settings_manager_' . self::ID,
				'save_settings'
			);

			$this->add_filter(
				'ms_shortcode_register_form_fields',
				'customize_register_form',
				10, 2
			);

			$this->add_action(
				'ms_controller_frontend_register_user_before',
				'register_user'
			);

			$this->add_filter(
				'ms_shortcode_register_form_rules',
				'register_rules'
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
				'descirption' => array(
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
		$all_fields = self::list_fields();
		$settings = MS_Plugin::instance()->settings;
		$config = $settings->get_custom_setting( 'profilefields', 'register' );
		$data = $view->data;

		if ( empty( $config ) ) {
			// No configuration defined yet, use default fields.
			return $fields;
		}

		$membership_id = $fields['membership_id'];
		$action = $fields['action'];
		$step = $fields['step'];

		$custom_fields = array(
			$membership_id,
			$action,
			$step,
		);

		foreach ( $all_fields as $id => $defaults ) {
			$setting = 'off';
			if ( isset( $config[$id] ) ) {
				$setting = $config[$id];
			}

			if ( 'off' == $setting ) { continue; }

			if ( 0 === strpos( $id, 'xprofile_' ) ) {
				$field_id = substr( $id, 9 );
				$custom_fields[] = $this->render_xprofile_field( $field_id );
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
				}
				if ( isset( $defaults['type'] ) ) {
					$type = $defaults['type'];
				}

				$custom_fields[] = array(
					'id' => $id,
					'title' => $label,
					'placeholder' => $hint,
					'type' => $type,
					'value' => $value,
				);
			}
		}

		return $custom_fields;
	}

	/**
	 * Modifies the JS validation rules that are used on the registration form.
	 *
	 * @since  1.0.1.0
	 * @param  array $rules The default validation rules.
	 * @return array Modified rules.
	 */
	public function register_rules( $rules ) {
		$settings = MS_Plugin::instance()->settings;
		$config = $settings->get_custom_setting( 'profilefields', 'register' );
		$rules = array();

		foreach ( $config as $field => $setting ) {
			if ( 'off' == $setting ) { continue; }
			$rules[$field] = array();

			if ( 'required' == $setting ) {
				$rules[$field]['required'] = true;
			} else {
				$rules[$field]['required'] = false;
			}

			switch ( $field ) {
				case 'email':
					$rules[$field]['email'] = true;
					break;

				case 'password':
					$rules[$field]['minlength'] = 5;
					break;

				case 'password2':
					$rules[$field]['equalTo'] = '#password';
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
	 * @param  MS_Controller_Frontent $controller
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
	 * Generates the HTML code for a single XProfile input field.
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

					<?php if ( !bp_get_the_profile_field_is_required() ) : ?>
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