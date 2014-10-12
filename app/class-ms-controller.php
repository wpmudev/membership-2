<?php
/**
 * This file defines the MS_Controller object.
 *
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Abstract class for all Controllers.
 *
 * All controllers will extend or inherit from the MS_Controller class.
 * Methods of this class will control the flow and behaviour of the plugin
 * by using MS_Model and MS_View objects.
 *
 * @since 4.0.0
 *
 * @uses MS_Model
 * @uses MS_View
 *
 * @package Membership
 */
class MS_Controller extends MS_Hooker {

	/**
	 * Capability required to use access metabox.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $capability
	 */
	protected $capability = 'manage_options';

	/**
	 * Ajax response flag.
	 *
	 * @see _resp_ok()
	 * @var bool
	 */
	private $_resp_valid = true;

	/**
	 * Ajax response error-code.
	 *
	 * @see _resp_code()
	 * @var string
	 */
	private $_resp_code = '';

	/**
	 * Parent constuctor of all controllers.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		/**
		 * Actions to execute when constructing the parent controller.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Controller object.
		 */
		do_action( 'membership_parent_controller_construct', $this );

		/**
		 * Register styles and scripts that are used in the dashboard.
		 *
		 * @since 4.0.0
		 */
		$this->add_action( 'admin_enqueue_scripts', 'register_admin_scripts' );
		$this->add_action( 'admin_enqueue_scripts', 'register_admin_styles' );

		/**
		 * Register styles and scripts that are used on the front-end.
		 *
		 * @since 4.0.0
		 */
		$this->add_action( 'wp_enqueue_scripts', 'register_public_scripts' );
		$this->add_action( 'wp_enqueue_scripts', 'register_public_styles' );
	}

	/**
	 * Get action from request.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_action() {
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		return apply_filters( 'ms_controller_get_action', $action );
	}

	/**
	 * Verify nonce.
	 *
	 * @since 4.0.0
	 *
	 * @param string $action The action name to verify nonce.
	 * @param string $request_method POST or GET
	 * @param string $nonce_field The nonce field name
	 * @return bool True if verified, false otherwise.
	 */
	public function verify_nonce( $action = null, $request_method = 'POST', $nonce_field = '_wpnonce' ) {

		$verified = false;
		$request_fields = ( 'POST' == $request_method ) ? $_POST : $_GET;

		if ( empty( $action ) ) {
			$action = ! empty( $request_fields['action'] ) ? $request_fields['action'] : '';
		}
		if ( ! empty( $request_fields[ $nonce_field ] ) && wp_verify_nonce( $request_fields[ $nonce_field ], $action ) ) {
			$verified = true;
		}
		return $verified;
	}

	/**
	 * Verify if current user can perform management actions.
	 *
	 * @since 4.0.0
	 * @return bool True if can, false otherwise.
	 */
	public function is_admin_user() {
		$is_admin_user = false;
		$is_admin_user = MS_Model_Member::is_admin_user( null, $this->capability );
		return apply_filters( 'ms_controller_current_user_can', $is_admin_user, $this->capability );
	}

	/**
	 * Verify required fields aren't empty.
	 *
	 * @since 4.0.0
	 *
	 * @param string[] $fields The array of fields to validate.
	 * @param string $request_method POST or GET
	 * @param bool $not_empty if true use empty method, else use isset method.
	 * @return bool True all fields are validated
	 */
	public function validate_required( $fields, $request_method = 'POST', $not_empty = true ) {
		$validated = true;
		$request_fields = null;
		switch ( $request_method ) {
			case 'GET':
				$request_fields = $_GET;
				break;

			case 'REQUEST':
				$request_fields = $_REQUEST;
				break;

			default:
			case 'POST':
				$request_fields = $_POST;
				break;

		}

		foreach ( $fields as $field ) {
			if ( $not_empty ) {
				if ( empty( $request_fields[ $field ] ) ) {
					$validated = false;
				}
			}
			else {
				if ( ! isset( $request_fields[ $field ] ) ) {
					$validated = false;
				}
			}
		}

		return apply_filters( 'ms_controller_validate_required', $validated, $fields );
	}

	/**
	 * Get field from request parameters.
	 *
	 * @since 4.0.0
	 *
	 * @param string $id The field ID
	 * @param mixed $default The default value of the field.
	 * @param string $request_method POST or GET
	 * @return mixed The value of the request field.
	 */
	public function get_request_field( $id, $default = '', $request_method = 'POST' ) {
		$value = $default;
		$request_fields = null;
		switch ( $request_method ) {
			case 'GET':
				$request_fields = $_GET;
				break;

			case 'REQUEST':
				$request_fields = $_REQUEST;
				break;

			default:
			case 'POST':
				$request_fields = $_POST;
				break;

		}

		if ( isset( $request_fields[ $id ] ) ) {
			$value = $request_fields[ $id ];
		}

		return apply_filters( 'ms_controller_get_request_field', $value, $id, $default );
	}

	/**
	 * Register scripts that are used on the dashboard.
	 *
	 * @since  1.0.0
	 */
	public function register_admin_scripts() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;

		// The main plugin script.
		wp_register_script(
			'ms-admin',
			$plugin_url . 'app/assets/js/ms-admin.js',
			array( 'jquery', 'jquery-chosen', 'jquery-validate', 'jquery-plugins' ), $version
		);

		wp_register_script(
			'jquery-chosen',
			$plugin_url . 'app/assets/js/select2.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'jquery-plugins',
			$plugin_url . 'app/assets/js/jquery.plugins.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'jquery-validate',
			$plugin_url . 'app/assets/js/jquery.validate.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-tooltips',
			$plugin_url . 'app/assets/js/ms-tooltip.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-controller-admin-bar',
			$plugin_url . 'app/assets/js/ms-controller-admin-bar.js',
			array( 'jquery' ), $version
		);

		// View specific
		wp_register_script(
			'ms-view-membership-overview',
			$plugin_url . 'app/assets/js/ms-view-membership-overview.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-membership-setup-protected-content',
			$plugin_url . 'app/assets/js/ms-view-membership-setup-protected-content.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-membership-render-url-group',
			$plugin_url . 'app/assets/js/ms-view-membership-render-url-group.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-membership-create-child',
			$plugin_url . 'app/assets/js/ms-view-membership-create-child.js',
			array( 'jquery', 'jquery-validate' ), $version
		);
		wp_register_script(
			'ms-view-membership-setup-dripped',
			$plugin_url. 'app/assets/js/ms-view-membership-setup-dripped.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'membership-metabox',
			$plugin_url. 'app/assets/js/ms-view-membership-metabox.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-coupon-edit',
			$plugin_url . 'app/assets/js/ms-view-coupon-edit.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-billing-edit',
			$plugin_url . 'app/assets/js/ms-view-billing-edit.js',
			array( 'jquery' ), $version
		);
	}

	/**
	 * Register styles that are used on the dashboard.
	 *
	 * @since  1.0.0
	 */
	public function register_admin_styles() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;

		// The main plugin style.
		wp_register_style(
			'ms-admin-styles',
			$plugin_url . 'app/assets/css/ms-admin.css',
			null, $version
		);

		wp_register_style(
			'jquery-ui',
			$plugin_url . 'app/assets/css/jquery-ui.custom.css',
			null, $version
		);
		wp_register_style(
			'membership-admin',
			$plugin_url . 'app/assets/css/ms-settings.css',
			null, $version
		);
		wp_register_style(
			'membership-tooltip',
			$plugin_url . 'app/assets/css/ms-tooltip.css',
			null, $version
		);
		wp_register_style(
			'font-awesome',
			$plugin_url . 'app/assets/css/font-awesome.css',
			null, $version
		);
		wp_register_style(
			'jquery-chosen',
			$plugin_url . 'app/assets/css/select2.css',
			null, $version
		);
		wp_register_style(
			'ms_view_membership',
			$plugin_url . 'app/assets/css/ms-view-membership.css',
			null, $version
		);
		wp_register_style(
			'ms-view-settings-render-messages-automated',
			$plugin_url . 'app/assets/css/ms-view-settings-render-messages-automated.css',
			null, $version
		);
		wp_register_style(
			'ms-admin-bar',
			$plugin_url . 'app/assets/css/ms-admin-bar.css',
			null, $version
		);
	}

	/**
	 * Register scripts that are used on the front-end.
	 *
	 * @since  1.0.0
	 */
	public function register_public_scripts() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;

		wp_register_script(
			'jquery-validate',
			$plugin_url . 'app/assets/js/jquery.validate.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-shortcode',
			$plugin_url . 'app/assets/js/ms-shortcode.js',
			array( 'jquery-validate' ), $version
		);
		wp_register_script(
			'ms-view-frontend-profile',
			$plugin_url . 'app/assets/js/ms-view-frontend-profile.js',
			array( 'jquery-validate' ), $version
		);
		wp_register_script(
			'jquery-chosen',
			$plugin_url . 'app/assets/js/select2.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-gateway-authorize',
			$plugin_url . 'app/assets/js/ms-view-gateway-authorize.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-gateway-stripe',
			$plugin_url . 'app/assets/js/ms-view-gateway-stripe.js',
			array( 'jquery' ), $version
		);
	}

	/**
	 * Register styles that are used on the front-end.
	 *
	 * @since  1.0.0
	 */
	public function register_public_styles() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;

		wp_register_style(
			'jquery-ui',
			$plugin_url . 'app/assets/css/jquery-ui.custom.css',
			null, $version
		);
		wp_register_style(
			'membership-admin',
			$plugin_url . 'app/assets/css/ms-settings.css',
			null, $version
		);
		wp_register_style(
			'membership-shortcode',
			$plugin_url . 'app/assets/css/ms-shortcode.css',
			null, $version
		);
		wp_register_style(
			'jquery-chosen',
			$plugin_url . 'app/assets/css/select2.css',
			null, $version
		);
	}

	/**
	 * Reset the response flags.
	 * The _resp_ functions are mainly used by Ajax handlers to simplify error
	 * tracking.
	 *
	 * Implemented in file ms-class-controller-rule.php
	 *
	 * @since  1.0.0
	 */
	protected function _resp_reset() {
		$this->_resp_valid = true;
		$this->_resp_code = '';
	}

	/**
	 * Returns current state of the response-valid flag.
	 * The flag can only be set to true via _resp_reset()
	 * And set to false by _resp_err()
	 *
	 * @since  1.0.0
	 *
	 * @return bool
	 */
	protected function _resp_ok() {
		return (true === $this->_resp_valid);
	}

	/**
	 * Returns the error code.
	 * The error code can be defined via _resp_err()
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	protected function _resp_code() {
		if ( strlen( $this->_resp_code ) > 0 ) {
			return ':' . $this->_resp_code;
		}
		return '';
	}

	/**
	 * Flag the current response as invalid and optionally define an error code.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $code Optional error code
	 */
	protected function _resp_err( $code = '' ) {
		$this->_resp_valid = false;
		$this->_resp_code = (string) $code;
	}
}
