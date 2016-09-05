<?php
/**
 * Controller for Membership widgets.
 *
 * This is not doing much, since most of the widget logic is handled by
 * WordPress itself. We mainly need to register available widgets.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Widget extends MS_Controller {

	/**
	 * Register available widgets.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		// Load the add-on manager model.
		$this->add_action(
			'widgets_init',
			'register_widgets'
		);
	}

	/**
	 * Register available widgets.
	 *
	 * @since  1.0.0
	 */
	public function register_widgets() {
		register_widget( 'MS_Widget_Login' );
	}

}