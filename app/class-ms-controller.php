<?php
/**
 * Abstract class for all Controllers.
 *
 * All controllers will extend or inherit from the MS_Controller class.
 * Methods of this class will control the flow and behaviour of the plugin
 * by using MS_Model and MS_View objects.
 *
 * @since  1.0.0
 *
 * @uses MS_Model
 * @uses MS_View
 *
 * @package Membership2
 */
class MS_Controller extends MS_Hooker {

	/**
	 * Ajax response flag.
	 *
	 * @since  1.0.0
	 *
	 * @see _resp_ok()
	 * @var bool
	 */
	private $_resp_valid = true;

	/**
	 * Ajax response error-code.
	 *
	 * @since  1.0.0
	 *
	 * @see _resp_code()
	 * @var string
	 */
	private $_resp_code = '';

	/**
	 * Parent constuctor of all controllers.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		/**
		 * Actions to execute when constructing the parent controller.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Controller object.
		 */
		do_action( 'ms_controller_construct', $this );
	}

	/**
	 * Does admin-side initialization. This function is called by the
	 * MS_Controller_Plugin object and is only executed when is_admin() is true.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		// Nothing by default. Can be overwritten by child classes.
	}

	/**
	 * Get action from request.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function get_action() {
		if ( empty( $_REQUEST['action'] ) ) {
			$action = '';
		} else {
			$action = $_REQUEST['action'];
		}

		return apply_filters( 'ms_controller_get_action', $action, $this );
	}

	/**
	 * Verify nonce.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $action The action name to verify nonce.
	 * @param  string $request_method POST or GET
	 * @param  string $nonce_field The nonce field name
	 * @return boolean True if verified, false otherwise.
	 */
	public function verify_nonce( $action = null, $request_method = 'POST', $nonce_field = '_wpnonce' ) {
		$verified = false;
		switch ( $request_method ) {
			case 'GET':
				$request_fields = $_GET;
				break;

			case 'REQUEST':
			case 'any':
				$request_fields = $_REQUEST;
				break;

			case 'POST':
			default:
				$request_fields = $_POST;
				break;
		}

		if ( empty( $action ) ) {
			$action = ! empty( $request_fields['action'] ) ? $request_fields['action'] : '';
		}

		if ( ! empty( $request_fields[ $nonce_field ] )
			&& wp_verify_nonce( $request_fields[ $nonce_field ], $action )
		) {
			$verified = true;
		}

		return apply_filters(
			'ms_controller_verify_nonce',
			$verified,
			$action,
			$request_method,
			$nonce_field,
			$this
		);
	}

	/**
	 * Verify if current user can perform management actions.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean True if can, false otherwise.
	 */
	public function is_admin_user() {
		$is_admin_user = MS_Model_Member::is_admin_user();

		return apply_filters(
			'ms_controller_is_admin_user',
			$is_admin_user
		);
	}

	/**
	 * Verify required fields aren't empty.
	 *
	 * @since  1.0.0
	 *
	 * @param  string[] $fields The array of fields to validate.
	 * @param  string $request_method POST or GET
	 * @param  bool $not_empty If true use empty method, else use isset method.
	 * @return bool True all fields are validated
	 */
	static public function validate_required( $fields, $request_method = 'POST', $not_empty = true ) {
		$validated = true;
		$request_fields = null;

		switch ( $request_method ) {
			case 'GET':
				$request_fields = $_GET;
				break;

			case 'REQUEST':
			case 'any':
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
					break;
				}
			} else {
				if ( ! isset( $request_fields[ $field ] ) ) {
					$validated = false;
					break;
				}
			}
		}

		return apply_filters(
			'ms_controller_validate_required',
			$validated,
			$fields
		);
	}

	/**
	 * Get field from request parameters.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $id The field ID
	 * @param  mixed $default The default value of the field.
	 * @param  string $request_method POST or GET
	 * @return mixed The value of the request field.
	 */
	static public function get_request_field( $id, $default = '', $request_method = 'POST' ) {
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

		return apply_filters(
			'ms_controller_get_request_field',
			$value,
			$id,
			$default
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
		return ( true === $this->_resp_valid );
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
