<?php
/**
 * Integration for WPML translations
 *
 * @since  1.0.1.0
 */
class MS_Addon_Wpml extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'addon_wpml';

	/**
	 * WPML Translation context.
	 *
	 * @since 1.0.1.0
	 */
	const CONTEXT = 'Membership 2';

	/**
	 * Value from WPML: The site default language.
	 *
	 * @var string
	 */
	protected $default_lang = '';

	/**
	 * Value from WPML: Currently selected language.
	 *
	 * @var string
	 */
	protected $current_lang = '';

	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		if ( ! self::wpml_active()
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
		static $Init_Done = false;

		if ( $Init_Done ) { return; }
		$Init_Done = true;

		if ( self::is_active() ) {
			$this->init_settings();

			$this->check_requirements();
			$this->add_action( 'admin_print_styles', 'enqueue_styles' );
			$this->add_filter( 'ms_translation_flag', 'show_flag', 10, 2 );

			// Load translations from DB.
			$this->add_filter(
				'ms_factory_populate',
				'populate_communication',
				10, 4
			);

			$this->add_filter(
				'ms_factory_populate',
				'populate_gateway',
				10, 4
			);

			$this->add_filter(
				'ms_factory_populate-ms_model_membership',
				'populate_membership',
				10, 3
			);

			$this->run_action(
				'plugins_loaded',
				'translate_settings'
			);

			// Save translations to DB.
			$this->add_filter(
				'ms_factory_serialize',
				'serialize_communication',
				10, 3
			);

			$this->add_filter(
				'ms_factory_serialize',
				'serialize_gateway',
				10, 3
			);

			$this->add_filter(
				'ms_factory_serialize-ms_model_membership',
				'serialize_membership',
				10, 2
			);

			$this->add_filter(
				'ms_factory_serialize-ms_model_settings',
				'serialize_settings',
				10, 2
			);

			// Fix stuff in the Membership 2 admin pages.
			$this->add_filter(
				'ms_model_pages_membership_page_id',
				'translate_page_id',
				10, 2
			);
			$this->add_filter(
				'ms_model_pages_current_page_id',
				'translate_page_id',
				10, 2
			);

			$this->add_filter(
				'ms_model_pages_get_ms_page_url',
				'translate_page_url',
				10, 3
			);

			$this->add_filter(
				'ms_view_settings_page_setup_prepare_fields',
				'change_general_settings',
				10, 2
			);
		} else {
			$this->add_action( 'ms_model_addon_enable', 'enable_addon' );
		}
	}

	/**
	 * Registers the Add-On.
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'icon' => 'dashicons dashicons-translation',
			'name' => __( 'WPML Integration', 'membership2' ),
			'description' => __( 'Use WPML to translate plugin messages.', 'membership2' ),
		);

		if ( ! self::wpml_active() ) {
			$list[ self::ID ]->description .= sprintf(
				'<br /><b>%s</b>',
				__( 'Activate WPML to use this Add-on', 'membership2' )
			);
			$list[ self::ID ]->action = '-';
		}

		return $list;
	}

	/**
	 * Checks if the WPML plugin is active.
	 *
	 * @since  1.0.1.0
	 * @return bool
	 */
	static public function wpml_active() {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	/**
	 * Function is triggered every time an add-on is enabled.
	 *
	 * We flush the Factory Cache when the WPML Add-on is enabled so all strings
	 * are properly registered for translation.
	 *
	 * @since  1.0.1.0
	 * @param  string $addon The Add-on ID
	 */
	public function enable_addon( $addon ) {
		if ( self::ID == $addon ) {
			MS_Factory::clear();
		}
	}

	/**
	 * Initializes the Add-on settings
	 *
	 * @since  1.0.1.0
	 */
	protected function init_settings() {
		$this->default_lang = apply_filters( 'wpml_default_language', '' );
		$this->current_lang = apply_filters( 'wpml_current_language', '' );
	}


	/*========================*\
	============================
	==                        ==
	==           UI           ==
	==                        ==
	============================
	\*========================*/


	/**
	 * Check if WPML is up to date and required modules are active.
	 *
	 * @since 1.0.1.0
	 */
	protected function check_requirements() {
		if ( ! self::wpml_active() ) {
			lib3()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'WPML not active!', 'membership2' ),
					__( 'Heads up: Activate the WPML plugin to enable all translation features of Membership 2 Pro.', 'membership2' )
				),
				'err'
			);
			return;
		}

		if ( version_compare( ICL_SITEPRESS_VERSION, '3.2', 'lt' ) ) {
			lib3()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'Great, you\'re using WPML!', 'membership2' ),
					__( 'Heads up: Your version of WPML is outdated. Please update WPML to version <b>3.2 or higher</b> to enable all translation features of Membership 2 Pro.', 'membership2' )
				),
				'err'
			);
			return;
		}

		if ( ! defined( 'WPML_ST_VERSION' ) ) {
			lib3()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'Great, you\'re using WPML!', 'membership2' ),
					__( 'Heads up: To enable all the translation features of Membership 2 Pro you need to activate the <b>WPML String Translation</b> module.', 'membership2' )
				),
				'err'
			);
			return;
		}

		if ( is_user_logged_in() ) {
			if ( ! get_user_meta( get_current_user_id(), 'icl_admin_language_for_edit', true ) ) {
				lib3()->ui->admin_message(
					sprintf(
						'<b>%s</b><br>%s',
						__( 'Great, you\'re using WPML!', 'membership2' ),
						sprintf(
							__( 'Heads up: For the best translation experience we recommend to enable "<b>Set admin language as editing language</b>" in your %suser profile%s.', 'membership2' ),
							'<a href="' . get_edit_user_link() . '#wpml">',
							'</a>'
						)
					),
					'err'
				);
				return;
			}
		}
	}

	/**
	 * Displays a translation flag to indicate that the item will be translated
	 * by this add-on.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  string $content The content that might get a visual flag.
	 * @param  string $field An unique field ID.
	 * @return string Content with the translation flag attached.
	 */
	public function show_flag( $content, $field ) {
		$supported = defined( 'WPML_ST_VERSION' );

		$classes = array(
			'ms-wpml-support',
			'field-' . $field,
		);
		$icon = '<i class="dashicons dashicons-translation wpml-icon"></i>';

		if ( $supported ) {
			$label = __( 'WPML Support', 'membership2' );
			$info = __( 'Translate by using the language switch in the toolbar.', 'membership2' );
		} else {
			$label = __( 'WPML not supported', 'membership2' );
			$info = __( 'Required module missing', 'membership2' );
			$classes[] = 'warning';
			$icon .= '<i class="wpmui-fa wpmui-fa-exclamation-circle warning-icon"></i>';
		}

		$flag = sprintf(
			'<span class="%s">%s<span class="details"><div class="detail-title">%s</div><div class="detail-infos">%s</div></span></span>',
			implode( ' ', $classes ),
			$icon,
			$label,
			$info
		);

		$content .= ' ' . $flag;
		return $content;
	}

	/**
	 * Load Coupon specific styles.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_styles() {
		$plugin_url = MS_Plugin::instance()->url;

		wp_enqueue_style(
			'ms-view-wpml',
			$plugin_url . '/app/addon/wpml/assets/css/wpml.css'
		);

		do_action( 'ms_addon_wpml_enqueue_styles', $this );
	}

	/**
	 * Correctly translate Ids of Membership Pages.
	 *
	 * @since  1.0.1.0
	 * @param  int|false $page_id The current page_id.
	 * @param  string|false $page_type The MS_Model_Pages type.
	 * @return int Page_id in the default language
	 */
	public function translate_page_id( $page_id, $page_type ) {
		if ( $this->current_lang != $this->default_lang ) {
			if ( empty( $page_type ) && empty( $page_id ) ) {
				$page_id = get_the_ID();
			}

			$page_id = apply_filters(
				'wpml_object_id',
				$page_id,
				'page',
				true,
				$this->default_lang
			);
		}

		return $page_id;
	}

	/**
	 * Localize the URL to the current language.
	 *
	 * @since  1.0.1.0
	 * @param  string $url The default URL.
	 * @param  string $page_type Not used.
	 * @param  bool $ssl Should the URL use ssl protocol?
	 * @return string Localized URL.
	 */
	public function translate_page_url( $url, $page_type, $ssl ) {
		if ( $this->current_lang != $this->default_lang ) {
			$post_id = url_to_postid( $url );

			$tr_post_id = apply_filters(
				'wpml_object_id',
				$post_id,
				'page',
				true,
				$this->current_lang
			);

			if ( $tr_post_id ) {
				$url = get_permalink( $tr_post_id );

				if ( null === $ssl ) { $ssl = is_ssl(); }
				if ( $ssl ) {
					$url = MS_Helper_Utility::get_ssl_url( $url );
				}
			}
		}

		return $url;
	}

	/**
	 * Modify settings/fields before the Page-Setup Settings page is displayed.
	 *
	 * We use this filter to hide the "Membership 2 Pages" section when not in
	 * default-language mode.
	 *
	 * @since  1.0.1.0
	 * @param  array $fields List of HTML field defintions.
	 * @param  MS_View_Settings $view
	 * @return array Modified list of HTML field definitions.
	 */
	public function change_general_settings( $fields, $view ) {
		if ( $this->current_lang != $this->default_lang ) {
			$fields['pages'] = sprintf(
				__( 'You can change your settings for the Membership 2 Pages only when you switch to your <b>default langauge</b> (%s)', 'membership2' ),
				strtoupper( $this->default_lang )
			);
		}

		return $fields;
	}


	/*==============================*\
	==================================
	==                              ==
	==           POPULATE           ==
	==                              ==
	==================================
	\*==============================*/


	/**
	 * Translate a single membership after it was loaded from DB.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  MS_Model_Membership $obj The membership object which was loaded.
	 * @param  array $settings The serialized settings.
	 * @param  bool|string $postmeta Post-meta flag for de-serialization.
	 * @return MS_Model_Membership The translated membership.
	 */
	public function populate_membership( $obj, $settings, $postmeta ) {
		if ( $this->current_lang == $this->default_lang ) { return $obj; }
		if ( ! $obj || ! is_object( $obj ) ) { return $obj; }
		if ( $obj->is_base() ) { return $obj; }
		if ( ! $obj->id ) { return $obj; }

		// Apply translations.
		$obj->name = apply_filters(
			'wpml_translate_single_string',
			$obj->name,
			self::CONTEXT,
			'ms-mem-name-' . $obj->id,
			$this->current_lang
		);

		$obj->description = apply_filters(
			'wpml_translate_single_string',
			$obj->description,
			self::CONTEXT,
			'ms-mem-description-' . $obj->id,
			$this->current_lang
		);

		return $obj;
	}

	/**
	 * Translate the plugin settings after they were loaded from DB.
	 *
	 * @since  1.0.1.0
	 */
	public function translate_settings() {
		if ( $this->current_lang == $this->default_lang ) { return; }
		$obj = MS_Factory::load( 'MS_Model_Settings' );

		// Apply translations.
		$translations = $obj->protection_messages;
		foreach ( $translations as $key => $message ) {
			$translation = apply_filters(
				'wpml_translate_single_string',
				$message,
				self::CONTEXT,
				'ms-set-protection-' . $key,
				$this->current_lang
			);
			$translations[ $key ] = $translation;
		}
		$obj->protection_messages = $translations;
	}

	/**
	 * Translate a single communication after it was loaded from DB.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  MS_Model_Communication $obj The object which was loaded.
	 * @param  string $class The class name of the object.
	 * @param  array $settings The serialized settings.
	 * @param  bool|string $postmeta Post-meta flag for de-serialization.
	 * @return MS_Model_Communication The translated object.
	 */
	public function populate_communication( $obj, $class, $settings, $postmeta ) {
		if ( $this->current_lang == $this->default_lang ) { return $obj; }
		if ( 0 !== strpos( $class, 'MS_Model_Communication' ) ) { return $obj; }
		if ( ! $obj || ! is_object( $obj ) ) { return $obj; }
		if ( ! $obj->id ) { return $obj; }

		// Apply translations.
		$obj->subject = apply_filters(
			'wpml_translate_single_string',
			$obj->subject,
			self::CONTEXT,
			'ms-com-subject-' . $obj->id,
			$this->current_lang
		);

		$obj->description = apply_filters(
			'wpml_translate_single_string',
			$obj->description,
			self::CONTEXT,
			'ms-com-message-' . $obj->id,
			$this->current_lang
		);

		return $obj;
	}

	/**
	 * Translate a single communication after it was loaded from DB.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  MS_Model_Communication $obj The object which was loaded.
	 * @param  string $class The class name of the object.
	 * @param  array $settings The serialized settings.
	 * @param  bool|string $postmeta Post-meta flag for de-serialization.
	 * @return MS_Model_Communication The translated object.
	 */
	public function populate_gateway( $obj, $class, $settings, $postmeta ) {
		if ( $this->current_lang == $this->default_lang ) { return $obj; }
		if ( 0 !== strpos( $class, 'MS_Gateway' ) ) { return $obj; }
		if ( ! $obj || ! is_object( $obj ) ) { return $obj; }
		if ( ! $obj->id ) { return $obj; }

		// Apply translations.
		$obj->pay_button_url = apply_filters(
			'wpml_translate_single_string',
			$obj->pay_button_url,
			self::CONTEXT,
			'ms-pay-button-' . $obj->id,
			$this->current_lang
		);

		if ( isset( $obj->payment_info ) ) {
			$obj->payment_info = apply_filters(
				'wpml_translate_single_string',
				$obj->payment_info,
				self::CONTEXT,
				'ms-pay-info-' . $obj->id,
				$this->current_lang
			);
		}

		return $obj;
	}


	/*===============================*\
	===================================
	==                               ==
	==           SERIALIZE           ==
	==                               ==
	===================================
	\*===============================*/

	/**
	 * Translate a single communication before it is saved to DB.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  array $obj The serialized data collection.
	 * @param  string $class The class name of the object.
	 * @param  MS_Model_Communication $model The source object.
	 * @return array The serialized data collection.
	 */
	public function serialize_communication( $data, $class, $model ) {
		if ( $this->current_lang == $this->default_lang ) { return $data; }
		if ( 0 !== strpos( $class, 'MS_Model_Communication' ) ) { return $data; }

		// Save the translated values before resetting the model.
		$tr_subject = $data['subject'];
		$tr_message = $data['description'];

		// Reset the values to Default language.
		$data['subject'] = $model->reset_field( 'subject' );
		$data['description'] = $model->reset_field( 'description' );
		$data['message'] = $data['description'];

		// Store translations via WPML.
		if ( function_exists( 'icl_add_string_translation' ) ) {
			// 1. Subject.
			$string_name = 'ms-com-subject-' . $model->id;
			do_action(
				'wpml_register_single_string',
				self::CONTEXT,
				$string_name,
				$data['subject']
			);
			$string_id = icl_get_string_id(
				$data['subject'],
				self::CONTEXT,
				$string_name
			);
			if ( $string_id ) {
				icl_add_string_translation(
					$string_id,
					$this->current_lang,
					$tr_subject,
					ICL_TM_COMPLETE
				);
			}

			// 1. Message Body.
			$string_name = 'ms-com-message-' . $model->id;
			do_action(
				'wpml_register_single_string',
				self::CONTEXT,
				$string_name,
				$data['message']
			);
			$string_id = icl_get_string_id(
				$data['message'],
				self::CONTEXT,
				$string_name
			);
			if ( $string_id ) {
				icl_add_string_translation(
					$string_id,
					$this->current_lang,
					$tr_message,
					ICL_TM_COMPLETE
				);
			}
		}

		return $data;
	}

	/**
	 * Translate a single payment gateway before it is saved to DB.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  array $obj The serialized data collection.
	 * @param  string $class The class name of the object.
	 * @param  MS_Gateway $model The source object.
	 * @return array The serialized data collection.
	 */
	public function serialize_gateway( $data, $class, $model ) {
		if ( $this->current_lang == $this->default_lang ) { return $data; }
		if ( 0 !== strpos( $class, 'MS_Gateway' ) ) { return $data; }

		// Save the translated values before resetting the model.
		$tr_button = $data['pay_button_url'];
		$tr_info = isset( $data['payment_info'] ) ? $data['payment_info'] : '';

		// Reset the values to Default language.
		$data['pay_button_url'] = $model->reset_field( 'pay_button_url' );
		$data['payment_info'] = $model->reset_field( 'payment_info' );

		// Store translations via WPML.
		if ( function_exists( 'icl_add_string_translation' ) ) {
			// 1. Subject.
			$string_name = 'ms-pay-button-' . $model->id;
			do_action(
				'wpml_register_single_string',
				self::CONTEXT,
				$string_name,
				$data['pay_button_url']
			);
			$string_id = icl_get_string_id(
				$data['pay_button_url'],
				self::CONTEXT,
				$string_name
			);
			if ( $string_id ) {
				icl_add_string_translation(
					$string_id,
					$this->current_lang,
					$tr_button,
					ICL_TM_COMPLETE
				);
			}

			// 1. Message Body.
			$string_name = 'ms-pay-info-' . $model->id;
			do_action(
				'wpml_register_single_string',
				self::CONTEXT,
				$string_name,
				$data['payment_info']
			);
			$string_id = icl_get_string_id(
				$data['payment_info'],
				self::CONTEXT,
				$string_name
			);
			if ( $string_id ) {
				icl_add_string_translation(
					$string_id,
					$this->current_lang,
					$tr_info,
					ICL_TM_COMPLETE
				);
			}
		}

		return $data;
	}

	/**
	 * Translate the plugin settings before they are saved to DB.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  array $obj The serialized data collection.
	 * @param  MS_Model_Membership $model The source object.
	 * @return array The serialized data collection.
	 */
	public function serialize_membership( $data, $model ) {
		if ( $this->current_lang == $this->default_lang ) { return $data; }

		// Save the translated values before resetting the model.
		$tr_name = $data['name'];
		$tr_description = $data['description'];

		// Reset the values to Default language.
		$data['name'] = $model->reset_field( 'name' );
		$data['description'] = $model->reset_field( 'description' );

		// Store translations via WPML.
		if ( function_exists( 'icl_add_string_translation' ) ) {
			$string_name = 'ms-mem-name-' . $model->id;
			do_action(
				'wpml_register_single_string',
				self::CONTEXT,
				$string_name,
				$data['name']
			);
			$string_id = icl_get_string_id(
				$data['name'],
				self::CONTEXT,
				$string_name
			);
			if ( $string_id ) {
				icl_add_string_translation(
					$string_id,
					$this->current_lang,
					$tr_name,
					ICL_TM_COMPLETE
				);
			}

			$string_name = 'ms-mem-description-' . $model->id;
			do_action(
				'wpml_register_single_string',
				self::CONTEXT,
				$string_name,
				$data['description']
			);
			$string_id = icl_get_string_id(
				$data['description'],
				self::CONTEXT,
				$string_name
			);
			if ( $string_id ) {
				icl_add_string_translation(
					$string_id,
					$this->current_lang,
					$tr_description,
					ICL_TM_COMPLETE
				);
			}
		}

		return $data;
	}

	/**
	 * Translate the plugin settings before they are saved to DB.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  array $obj The serialized data collection.
	 * @param  MS_Model_Settings $model The source object.
	 * @return array The serialized data collection.
	 */
	public function serialize_settings( $data, $model ) {
		if ( $this->current_lang == $this->default_lang ) { return $data; }

		// Save the translated values before resetting the model.
		$translations = array();
		foreach ( $model->protection_messages as $key => $message ) {
			$translations[ $key ] = $message;
		}

		// Reset the values to Default language.
		$data['protection_messages'] = $model->reset_field( 'protection_messages' );

		// Store translations via WPML.
		if ( function_exists( 'icl_add_string_translation' ) ) {
			foreach ( $translations as $key => $tr_message ) {
				$string_name = 'ms-set-protection-' . $key;
				do_action(
					'wpml_register_single_string',
					self::CONTEXT,
					$string_name,
					$model->protection_messages[ $key ]
				);
				$string_id = icl_get_string_id(
					$model->protection_messages[ $key ],
					self::CONTEXT,
					$string_name
				);
				if ( $string_id ) {
					icl_add_string_translation(
						$string_id,
						$this->current_lang,
						$tr_message,
						ICL_TM_COMPLETE
					);
				}
			}
		}

		return $data;
	}
}
