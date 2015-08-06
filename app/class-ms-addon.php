<?php
/**
 * Add-On controller
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
abstract class MS_Addon extends MS_Controller {

	/**
	 * Ajax action used to update the addon settings.
	 *
	 * @since  1.0.1.0
	 * @var  string
	 */
	const AJAX_UPDATE = 'ms_addon_update';

	/**
	 * Reference to the MS_Model_Addon instance.
	 *
	 * @type MS_Model_Addon
	 */
	static protected $model = null;

	/**
	 * Reference to the MS_Model_Settings instance.
	 *
	 * @type MS_Model_Addon
	 */
	static protected $settings = null;

	/**
	 * Initialize the Add-On.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		self::$model = MS_Factory::load( 'MS_Model_Addon' );
		self::$settings = MS_Factory::load( 'MS_Model_Settings' );

		$this->add_filter( 'ms_model_addon_register', 'register' );
		$this->add_action( 'ms_model_addon_initialize', 'init_addon' );

		$this->add_ajax_action(
			$this->ajax_action(),
			'ajax_update_settings'
		);
	}

	/**
	 * Initializes the Add-on.
	 *
	 * @since  1.0.0
	 */
	public function init_addon() {
		$this->init();
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		trigger_error( 'get_id() not implemented by Add-on', E_USER_WARNING );
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.0
	 */
	abstract public function init();

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	abstract public function register( $addons );

	/**
	 * Returns the Ajax action string used to update settings for an add-on.
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	protected function ajax_action() {
		return self::AJAX_UPDATE . '-' . $this->get_id();
	}

	/**
	 * Returns a single Add-on setting value that was previously saved via
	 * the AJAX_UPDATE ajax action.
	 *
	 * @since  1.0.1.0
	 * @param  stirng $key The setting key.
	 * @return string The value.
	 */
	public function get_setting( $key ) {
		$value = self::$settings->get_custom_setting(
			$this->get_id(),
			$key
		);

		return $value;
	}

	/**
	 * Ajax handler that updates the Add-on settings.
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_update_settings() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		$fields = array( 'field', 'value' );

		if ( $this->verify_nonce()
			&& self::validate_required( $fields, 'POST', false )
			&& $this->is_admin_user()
		) {
			self::$settings->set_custom_setting(
				$this->get_id(),
				$_POST['field'],
				$_POST['value']
			);

			self::$settings->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		echo $msg;
		exit;
	}

}