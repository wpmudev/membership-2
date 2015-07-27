<?php
/**
 * Add-on: Add custom Attributes to memberships.
 *
 * @since  1.0.1.0
 */
class MS_Addon_Attributes extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.1.0
	 */
	const ID = 'addon_attribute';

	/**
	 * Ajax action identifier used on the Settings page.
	 *
	 * @since  1.0.1.0
	 */
	const AJAX_ACTION_SAVE_SETTING = 'addon_attribute_save_setting';

	/**
	 * Ajax action identifier used on the Settings page.
	 *
	 * @since  1.0.1.0
	 */
	const AJAX_ACTION_DELETE_SETTING = 'addon_attribute_delete_setting';

	/**
	 * Ajax action identifier used in the Membership settings.
	 *
	 * @since  1.0.1.0
	 */
	const AJAX_ACTION_SAVE_ATTRIBUTE = 'addon_attribute_save_attribute';

	/**
	 * The shortcode which can be used to access custom attributes.
	 *
	 * @since  1.0.1.0
	 */
	const SHORTCODE = 'ms-membership-attr';

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
			// --- Plugin settings ---

			// Display a new tab in settings page.
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'add_settings_tab'
			);

			// Display the new settings page contents.
			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_settings_callback',
				10, 3
			);

			// Add settings javascript.
			$this->add_action(
				'ms_controller_settings_enqueue_scripts_' . self::ID,
				'enqueue_settings_scripts'
			);

			// Ajax handler that saves a single attribute definition.
			$this->add_ajax_action(
				self::AJAX_ACTION_SAVE_SETTING,
				'ajax_save_setting'
			);

			// Ajax handler that deletes a single attribute definition.
			$this->add_ajax_action(
				self::AJAX_ACTION_DELETE_SETTING,
				'ajax_delete_setting'
			);

			// --- Membership settings ---

			// Display a new tab in edit page.
			$this->add_filter(
				'ms_controller_membership_tabs',
				'add_membership_tab'
			);

			// Display the new edit page contents.
			$this->add_filter(
				'ms_view_membership_edit_render_callback',
				'manage_membership_callback',
				10, 3
			);

			// Add settings javascript.
			$this->add_action(
				'ms_controller_membership_enqueue_scripts_tab-' . self::ID,
				'enqueue_membership_scripts'
			);

			// Ajax handler that deletes a single attribute definition.
			$this->add_ajax_action(
				self::AJAX_ACTION_SAVE_ATTRIBUTE,
				'ajax_save_attribute'
			);

			// --- Access/integration ---

			// Register the shortcode to access custom attributes.
			add_shortcode(
				self::SHORTCODE,
				array( $this, 'do_shortcode' )
			);

			// Output shortcode info on the help page.
			$this->add_action(
				'ms_view_help_shortcodes-membership',
				'help_page'
			);

			$this->add_filter(
				'ms_membership_attr',
				'get_attr_filter',
				10, 3
			);
		}
	}

	/**
	 * Registers the Add-On.
	 *
	 * @since  1.0.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Membership Attributes', MS_TEXT_DOMAIN ),
			'description' => __( 'Add custom attributes to your memberships that you can use in shortcodes and code.', MS_TEXT_DOMAIN ),
			'icon' => 'wpmui-fa wpmui-fa-tags',
		);
		return $list;
	}


	/*===========================================*\
	===============================================
	==                                           ==
	==           DATA ACCESS FUNCTIONS           ==
	==                                           ==
	===============================================
	\*===========================================*/


	/**
	 * Saves a single field definition to the database.
	 *
	 * @since  1.0.1.0
	 * @param  array $field The field details.
	 * @return array The data that was saved to database (or false on error).
	 */
	static public function save_field_def( $field ) {
		$res = false;

		// Sanitize new field data.
		lib2()->array->equip( $field, 'title', 'slug', 'type', 'info' );
		$field = (object) $field;

		$field->title = html_entity_decode( trim( $field->title ) );
		$field->slug = strtolower( trim( $field->slug ) );
		$field->type = strtolower( trim( $field->type ) );
		$field->info = html_entity_decode( trim( $field->info ) );

		if ( ! $field->title || ! $field->slug || ! $field->type ) {
			// Stop if a required property is empty.
			return $res;
		}

		// Load existing fields.
		$settings = MS_Plugin::instance()->settings;
		$fields = $settings->get_custom_setting( self::ID, 'fields' );
		$fields = lib2()->array->get( $fields );

		// Check for duplicates.
		$duplicate = false;
		if ( $field->slug != $field->old_slug ) {
			foreach ( $fields as $saved_field ) {
				if ( $saved_field->slug == $field->slug ) {
					$duplicate = true;
					break;
				}
			}
		}

		// Determine the item that is updated or inserted.
		$insert_at = count( $fields );
		if ( $field->old_slug ) {
			foreach ( $fields as $index => $saved_field ) {
				if ( $saved_field->slug == $field->old_slug ) {
					$insert_at = $index;
					break;
				}
			}
		}

		// Save new field if everything is okay.
		if ( ! $duplicate ) {
			$fields[$insert_at] = $field;
			$fields = array_values( $fields );
			$settings->set_custom_setting( self::ID, 'fields', $fields );
			$settings->save();
			$res = $field;
		}

		return $res;
	}

	/**
	 * Deletes a single field definition from the database.
	 *
	 * @since  1.0.1.0
	 * @param  string $slug The slug that identifies the field.
	 * @return bool
	 */
	static public function remove_field_def( $slug ) {
		$res = false;

		// Load existing fields.
		$settings = MS_Plugin::instance()->settings;
		$fields = $settings->get_custom_setting( self::ID, 'fields' );
		$fields = lib2()->array->get( $fields );

		// Find the field and remove it.
		foreach ( $fields as $index => $saved_field ) {
			if ( $saved_field->slug == $slug ) {
				unset( $fields[ $index ] );
				$res = true;
				break;
			}
		}

		// Save modified field if everything is okay.
		if ( $res ) {
			$settings->set_custom_setting( self::ID, 'fields', $fields );
			$settings->save();
		}

		return $res;
	}

	/**
	 * Returns a single field definition.
	 *
	 * @since  1.0.1.0
	 * @param  string $slug The slug to identify the field.
	 * @return false|object The field definition or false.
	 */
	static public function get_field_def( $slug ) {
		$res = false;

		$settings = MS_Plugin::instance()->settings;
		$fields = $settings->get_custom_setting( self::ID, 'fields' );
		$fields = lib2()->array->get( $fields );

		foreach ( $fields as $field ) {
			if ( $field->slug == $slug ) {
				$res = $field;
				break;
			}
		}

		return $fields;
	}

	/**
	 * Returns a list of all field definition.
	 *
	 * @since  1.0.1.0
	 * @return array A list of field definitions.
	 */
	static public function list_field_def() {
		$settings = MS_Plugin::instance()->settings;
		$fields = $settings->get_custom_setting( self::ID, 'fields' );
		$fields = lib2()->array->get( $fields );

		return $fields;
	}

	/**
	 * Returns a custom attribute of the specified membership.
	 *
	 * @since  1.0.1.0
	 * @param  string $slug The field to fetch.
	 * @param  int|MS_Model_Membership $membership_id The Membership.
	 * @return false|string The field value.
	 */
	static public function get_attr( $slug, $membership_id = 0 ) {
		$res = false;

		if ( ! $membership_id ) {
			$auto_id = apply_filters( 'ms_detect_membership_id' );
			$membership = MS_Factory::load( 'MS_Model_Membership', $auto_id );
		} elseif ( $membership_id instanceof MS_Model_Membership ) {
			$membership = $membership_id;
		} else {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		}

		if ( $membership->is_valid() ) {
			$res = $membership->get_custom_data( 'attr_' . $slug );
		}

		return $res;
	}

	/**
	 * Saves a custom attribute to the specified membership.
	 *
	 * @since 1.0.1.0
	 * @param string $slug The field to update.
	 * @param string $value The new value to assign.
	 * @param int $membership_id The membership.
	 */
	static public function set_attr( $slug, $value, $membership_id = 0 ) {
		if ( ! $membership_id ) {
			$auto_id = apply_filters( 'ms_detect_membership_id' );
			$membership = MS_Factory::load( 'MS_Model_Membership', $auto_id );
		} elseif ( $membership_id instanceof MS_Model_Membership ) {
			$membership = $membership_id;
		} else {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		}

		if ( $membership->is_valid() ) {
			$membership->set_custom_data(
				'attr_' . $_POST['field'],
				$_POST['value']
			);
			$membership->save();
		}
	}


	/*======================================*\
	==========================================
	==                                      ==
	==           GENERAL SETTINGS           ==
	==                                      ==
	==========================================
	\*======================================*/


	/**
	 * Add the Attributes tab to the Plugin Settings page.
	 *
	 * @filter ms_controller_settings_get_tabs
	 *
	 * @since  1.0.1.0
	 * @param  array $tabs The default list of edit tabs.
	 * @return array The modified list of tabs.
	 */
	public function add_settings_tab( $tabs ) {
		$tabs[ self::ID ] = array(
			'title' => __( 'Membership Attributes', MS_TEXT_DOMAIN ),
			'url' => MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
		);

		return $tabs;
	}

	/**
	 * Add callback to display our custom edit tab contents.
	 *
	 * @since  1.0.1.0
	 *
	 * @filter ms_view_settings_edit_render_callback
	 *
	 * @param  array $callback The current function callback.
	 * @param  string $tab The current membership edit tab.
	 * @param  array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_settings_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view = MS_Factory::load( 'MS_Addon_Attributes_View_Settings' );
			$view->data = $data;
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Enqueue admin scripts in the settings screen.
	 *
	 * @since  1.0.1.0
	 */
	public function enqueue_settings_scripts() {
		$addon_url = MS_Plugin::instance()->url . '/app/addon/attributes/';

		$data = array(
			'lang' => array(
				'edit_title' => __( 'Edit Attribute', MS_TEXT_DOMAIN ),
			),
		);

		lib2()->ui->data( 'ms_data', $data );
		lib2()->ui->add( 'jquery-ui' );
		lib2()->ui->add( 'jquery-ui-sortable' );
		lib2()->ui->add( $addon_url . 'assets/js/settings.js' );
		lib2()->ui->add( $addon_url . 'assets/css/attributes.css' );
	}

	/**
	 * Ajax handler that saves an attribute definition.
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_save_setting() {
		$res = (object) array();
		$fields = array( 'title', 'slug', 'type' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			lib2()->array->equip_post( 'info', 'old_slug' );
			lib2()->array->strip_slashes( $_POST, 'name', 'info' );
			$field = array(
				'title' => esc_attr( $_POST['title'] ),
				'old_slug' => sanitize_html_class( $_POST['old_slug'] ),
				'slug' => sanitize_html_class( $_POST['slug'] ),
				'type' => esc_html( $_POST['type'] ),
				'info' => esc_attr( $_POST['info'] ),
			);

			$ok = self::save_field_def( $field );

			if ( $ok ) {
				$res->items = self::list_field_def();
				$res->ok = true;
			}
		}

		echo json_encode( $res );
		exit;
	}

	/**
	 * Ajax handler that deletes an attribute definition.
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_delete_setting() {
		$res = (object) array();
		$fields = array( 'slug' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			$ok = self::remove_field_def( $_POST['slug'] );

			if ( $ok ) {
				$res->items = self::list_field_def();
				$res->ok = true;
			}
		}

		echo json_encode( $res );
		exit;
	}


	/*=========================================*\
	=============================================
	==                                         ==
	==           MEMBERSHIP SETTINGS           ==
	==                                         ==
	=============================================
	\*=========================================*/


	/**
	 * Add the Attributes tab to the Membership editor.
	 *
	 * @filter ms_controller_membership_tabs
	 *
	 * @since  1.0.1.0
	 * @param  array $tabs The default list of edit tabs.
	 * @return array The modified list of tabs.
	 */
	public function add_membership_tab( $tabs ) {
		$tabs[ self::ID ] = array(
			'title' => __( 'Membership Attributes', MS_TEXT_DOMAIN ),
		);

		return $tabs;
	}

	/**
	 * Add callback to display our custom edit tab contents.
	 *
	 * @since  1.0.1.0
	 *
	 * @filter ms_view_membership_edit_render_callback
	 *
	 * @param  array $callback The current function callback.
	 * @param  string $tab The current membership edit tab.
	 * @param  array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_membership_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view = MS_Factory::load( 'MS_Addon_Attributes_View_Membership' );
			$view->data = $data;
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Enqueue admin scripts in the membership settings screen.
	 *
	 * @since  1.0.1.0
	 */
	public function enqueue_membership_scripts() {
		$addon_url = MS_Plugin::instance()->url . '/app/addon/attributes/';

		lib2()->ui->add( $addon_url . 'assets/css/attributes.css' );
	}

	/**
	 * Ajax handler that saves a membership attribute.
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_save_attribute() {
		$res = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		$fields = array( 'field', 'value', 'membership_id' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			$id = intval( $_POST['membership_id'] );
			self::set_attr( $_POST['field'], $_POST['value'], $id );
			$res = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		echo $res;
		exit;
	}


	/*========================================*\
	============================================
	==                                        ==
	==           ACCESS/INTEGRATION           ==
	==                                        ==
	============================================
	\*========================================*/


	/**
	 * Parses the custom shortcode and returns the value of the attribute.
	 *
	 * @since  1.0.1.0
	 * @param  array $atts The shortcode attributes.
	 * @param  string $content Content between the shortcode open/close tags.
	 * @return string The attribute value.
	 */
	public function do_shortcode( $atts, $content = '' ) {
		$data = apply_filters(
			'ms_addon_attributes_shortcode_atts',
			shortcode_atts(
				array(
					'id' => 0,
					'slug' => '',
					'title' => false,
					'default' => '',
				),
				$atts
			)
		);

		$value = $data['default'];
		$title = '';
		$field = self::get_field_def( $data['slug'] );

		if ( $field ) {
			$membership_id = apply_filters(
				'ms_detect_membership_id',
				$data['id']
			);

			// Fetch the attribute value.
			if ( $membership_id ) {
				$attr = self::get_attr( $data['slug'], $membership_id );

				if ( false !== $attr ) {
					$value = $attr;
				}
			}

			// Prepare the field title.
			if ( lib2()->is_true( $data['title'] ) ) {
				$title = '<span class="ms-title">' . $field->title . '</span> ';
			}
		}

		$value = '<span class="ms-value">' . do_shortcode( $value ) . '</span>';

		$html = sprintf(
			'<span class="ms-attr ms-attr-%s">%s%s</span>',
			$data['slug'],
			$title,
			$value
		);

		return apply_filters(
			'ms_addon_attributes_shortcode',
			$html,
			$data,
			$content
		);
	}

	/**
	 * Output shortcode info on the Help page.
	 *
	 * @since  1.0.1.0
	 */
	public function help_page() {
		?>
		<div id="<?php echo self::SHORTCODE; ?>" class="ms-help-box">
			<h3><code>[<?php echo self::SHORTCODE; ?>]</code></h3>

			<?php _ex( 'Output the value of a Custom Membership Attribute.', 'help', MS_TEXT_DOMAIN ); ?>
			<div class="ms-help-toggle"><?php _ex( 'Expand', 'help', MS_TEXT_DOMAIN ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>slug</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<strong><?php _ex( 'Required', 'help', MS_TEXT_DOMAIN ); ?></strong>.
						<?php _ex( 'Slug of the custom attribute', 'help', MS_TEXT_DOMAIN ); ?>.
					</li>
					<li>
						<code>id</code>
						<?php _ex( '(Single ID)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'The membership ID', 'help', MS_TEXT_DOMAIN ); ?>.
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							<?php _e( 'Automatic detection', MS_TEXT_DOMAIN ); ?>
						</span><br />
						<em><?php _ex( 'If not specified the plugin attempts to identify the currently displayed membership by examining the URL, request data and subscriptions of the current member', 'help', MS_TEXT_DOMAIN ); ?></em>.
					</li>
					<li>
						<code>title</code>
						<?php _ex( '(yes|no)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Prefix the field title to the output', 'help', MS_TEXT_DOMAIN ); ?>.
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							no
						</span>
					</li>
					<li>
						<code>default</code>
						<?php _ex( '(Text)', 'help', MS_TEXT_DOMAIN ); ?>
						<?php _ex( 'Default value to display if no membership was found or the membership did not define the attribute', 'help', MS_TEXT_DOMAIN ); ?>.
						<span class="ms-help-default">
							<?php _ex( 'Default:', 'help', MS_TEXT_DOMAIN ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php _ex( 'Example:', 'help', MS_TEXT_DOMAIN ); ?></em></p>
				<p><code>[<?php echo self::SHORTCODE; ?> slug="intro"]</code></p>
				<p><code>[<?php echo self::SHORTCODE; ?> slug="intro" id="5" default="An awesome offer!"]</code></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles a filter function and returns a single membership attribute.
	 *
	 * @since  1.0.1.0
	 * @param  string $default Default value.
	 * @param  string $slug Attribute slug.
	 * @param  int $membership_id Optional. Membership-ID.
	 * @return string The attribute value.
	 */
	public function get_attr_filter( $default, $slug, $membership_id = 0 ) {
		$val = self::get_attr( $slug, $membership_id );

		if ( $val ) {
			$default = $val;
		}

		return $default;
	}

}

/**
 * Convenience function to access a membership attribute value.
 *
 * @since  1.0.1.0
 * @param  string $slug The attribute slug.
 * @param  int $membership_id Membership ID.
 * @return string|false The attribute value or false.
 */
function ms_membership_attr( $slug, $membership_id = 0 ) {
	return MS_Addon_Attributes::get_attr( $slug, $membership_id );
}

/**
 * Convenience function to modify a membership attribute value.
 *
 * @since  1.0.1.0
 * @param  string $slug The attribute slug.
 * @param  string $value The attribute value to assign.
 * @param  int $membership_id Membership ID.
 */
function ms_membership_attr_set( $slug, $value, $membership_id = 0 ) {
	MS_Addon_Attributes::set_attr( $slug, $value, $membership_id );
}