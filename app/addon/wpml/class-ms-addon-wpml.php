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
	 * @since 1.1.0
	 */
	const ID = 'addon_wpml';

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
	 * @since  1.1.0
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
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {
		static $Init_Done = false;

		if ( $Init_Done ) { return; }
		$Init_Done = true;

		if ( self::is_active() ) {
			$this->init_settings();

			$this->check_requirements();
			$this->register_strings();

			$this->add_action( 'admin_print_styles', 'enqueue_styles' );
			$this->add_filter( 'ms_translation_flag', 'show_flag', 10, 3 );

			$this->add_filter(
				'ms_factory_set_MS_Model_Membership',
				'translate_membership',
				10, 2
			);
		} else {
			$this->add_action( 'ms_model_addon_enable', 'enable_addon' );
		}
	}

	/**
	 * Registers the Add-On.
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'icon' => 'dashicons dashicons-translation',
			'name' => __( 'WPML Integration', MS_TEXT_DOMAIN ),
			'description' => __( 'Use WPML to translate plugin messages.', MS_TEXT_DOMAIN ),
		);

		if ( ! self::wpml_active() ) {
			$list[ self::ID ]->description .= sprintf(
				'<br /><b>%s</b>',
				__( 'Activate WPML to use this Add-on', MS_TEXT_DOMAIN )
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

	/**
	 * Check if WPML is up to date and required modules are active.
	 *
	 * @since 1.0.1.0
	 */
	protected function check_requirements() {
		if ( ! self::wpml_active() ) {
			lib2()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'WPML not active!', MS_TEXT_DOMAIN ),
					__( 'Heads up: Activate the WPML plugin to enable all translation features of Membership 2 Pro.', MS_TEXT_DOMAIN )
				),
				'err'
			);
			return;
		}

		if ( version_compare( ICL_SITEPRESS_VERSION, '3.2', 'lt' ) ) {
			lib2()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'Great, you\'re using WPML!', MS_TEXT_DOMAIN ),
					__( 'Heads up: Your version of WPML is outdated. Please update WPML to version <b>3.2 or higher</b> to enable all translation features of Membership 2 Pro.', MS_TEXT_DOMAIN )
				),
				'err'
			);
			return;
		}

		if ( ! defined( 'WPML_ST_VERSION' ) ) {
			lib2()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'Great, you\'re using WPML!', MS_TEXT_DOMAIN ),
					__( 'Heads up: To enable all the translation features of Membership 2 Pro you need to activate the <b>WPML String Translation</b> module.', MS_TEXT_DOMAIN )
				),
				'err'
			);
			return;
		}

		if ( ! get_user_meta( get_current_user_id(), 'icl_admin_language_for_edit', true ) ) {
			lib2()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'Great, you\'re using WPML!', MS_TEXT_DOMAIN ),
					sprintf(
						__( 'Heads up: For the best translation experience we recommend to enable "<b>Set admin language as editing language</b>" in your %suser profile%s.', MS_TEXT_DOMAIN ),
						'<a href="' . get_edit_user_link() . '#wpml">',
						'</a>'
					)
				),
				'err'
			);
			return;
		}
	}

	/**
	 * This Add-on registers all single strings that can be translated.
	 *
	 * This is an attempt to keep the core code clean of the translation logic.
	 *
	 * @since  1.0.1.0
	 */
	protected function register_strings() {

	}

	/**
	 * Translate a single membership after it was loaded.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  MS_Model_Membership $obj The membership object which was loaded.
	 * @param  int $id The Membership ID.
	 * @return MS_Model_Membership The translated membership.
	 */
	public function translate_membership( $obj, $id ) {
		if ( ! $obj || ! is_object( $obj ) ) { return $obj; }
		if ( $obj->is_base() ) { return $obj; }
		if ( ! $obj->id ) { return $obj; }

		$package = array(
			'kind' => 'M2',
			'name' => 'membership',
			'title' => 'Membership',
			'edit_link' => '',
			'view_link' => '',
		);

		// Register the fields that can be translated.
		do_action(
			'wpml_register_string',
			$obj->name,
			'ms-name-' . $obj->id,
			$package,
			'Name',
			'LINE'
		);

		do_action(
			'wpml_register_string',
			$obj->description,
			'ms-description-' . $obj->id,
			$package,
			'Description',
			'AREA'
		);
/*
		// Apply translations.
		$obj->name = apply_filters(
			'wpml_translate_string',
			$obj->name,
			'ms-name-' . $obj->id,
			$package
		);

		$obj->description = apply_filters(
			'wpml_translate_string',
			$obj->description,
			'ms-description-' . $obj->id,
			$package
		);
*/
		return $obj;
	}

	/**
	 * Displays a translation flag to indicate that the item will be translated
	 * by this add-on.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  string $content The content that might get a visual flag.
	 * @param  string $type [cpt|string] Type of the field.
	 * @param  string $field An unique field ID.
	 * @return string Content with the translation flag attached.
	 */
	public function show_flag( $content, $type, $field ) {
		if ( 'string' == $type &&
			! ( defined( 'WPML_ST_VERSION' ) && defined( 'WPML_TM_VERSION' ) )
		) {
			$supported = false;
		} else {
			$supported = true;
		}

		$classes = array(
			'ms-wpml-support',
			'field-' . $field,
			'type-' . $type,
		);
		$icon = '<i class="dashicons dashicons-translation wpml-icon"></i>';

		if ( $supported ) {
			$label = __( 'WPML Support', MS_TEXT_DOMAIN );
			if ( 'cpt' == $type ) {
				$info = __( 'Translate by using the language switch in the toolbar.', MS_TEXT_DOMAIN );
			} else {
				$info = __( 'Translate this text on WPML > String Translation', MS_TEXT_DOMAIN );
			}
		} else {
			$label = __( 'WPML not supported', MS_TEXT_DOMAIN );
			$info = __( 'Required module missing', MS_TEXT_DOMAIN );
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
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$plugin_url = MS_Plugin::instance()->url;

		wp_enqueue_style(
			'ms-view-wpml',
			$plugin_url . '/app/addon/wpml/assets/css/wpml.css'
		);

		do_action( 'ms_addon_wpml_enqueue_styles', $this );
	}
}