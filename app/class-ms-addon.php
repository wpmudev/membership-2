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

}