<?php
/**
 * Controller for Plugin documentation and help.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Help extends MS_Controller {

	/**
	 * Prepare the component.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		$hook = MS_Controller_Plugin::admin_page_hook( 'help' );

		$this->run_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
	}

	/**
	 * Load and render the Documentation view.
	 *
	 * @since  1.0.0
	 */
	public function admin_page() {
		/**
		 * Create / Filter the view.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Controller_Help object.
		 */
		$view = MS_Factory::create( 'MS_View_Help' );
		$data = array();
		$data['tabs'] = $this->get_tabs();

		$view->data = apply_filters( 'ms_view_help_data', $data );
		$view->render();
	}

	/**
	 * Get available tabs.
	 *
	 * @since  1.0.0
	 *
	 * @return array The tabs configuration.
	 */
	public function get_tabs() {
		$tabs = array(
			'general' => array(
				'title' => __( 'General', 'membership2' ),
			),
			'shortcodes' => array(
				'title' => __( 'Shortcodes', 'membership2' ),
			),
			'network' => array(
				'title' => __( 'Network-Wide Protection', 'membership2' ),
			),
			'advanced' => array(
				'title' => __( 'Advanced Settings', 'membership2' ),
			),
			'branding' => array(
				'title' => __( 'Customize Membership 2', 'membership2' ),
			),
			'emails' => array(
				'title' => __( 'Email history', 'membership2' ),
			),
			'api' => array(
				'title' => __( 'API Docs', 'membership2' ),
				'url' => MS_Plugin::instance()->url . 'docs/namespaces/default.html',
				'target' => '_blank',
			),
		);

		if ( ! is_multisite() ) {
			unset( $tabs['network'] );
		}
		if ( ! defined( 'MS_LOG_EMAILS' ) || ! MS_LOG_EMAILS ) {
			unset( $tabs['emails'] );
		}

		lib3()->array->equip_get( 'page' );
		$def_key = MS_Controller_Plugin::MENU_SLUG . '-help';
		$page = sanitize_html_class( $_GET['page'], $def_key );

		foreach ( $tabs as $key => $tab ) {
			if ( empty( $tabs[ $key ]['url'] ) ) {
				$tabs[ $key ]['url'] = sprintf(
					'admin.php?page=%1$s&tab=%2$s',
					esc_attr( $page ),
					esc_attr( $key )
				);
			}
		}

		return apply_filters(
			'ms_controller_help_get_tabs',
			$tabs,
			$this
		);
	}

	/**
	 * Load specific scripts.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array( 'view_help' ),
		);

		lib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

}