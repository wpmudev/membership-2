<?php
/**
 * Call when you really need a hooker...
 *
 * Base hooker class provides generic interface to hook on actions and filters.
 *
 * This file is the base object of all other objects in the Membership plugin.
 * All Membership objects inherit from this class.
 *
 * The Membership base class all other classes build on. No hooks defined here.
 *
 * @since  1.0.0
 *
 * @package Membership2
 */
class MS_Hooker {

	/**
	 * A unique object ID which is only used for debugging and tracing data.
	 *
	 * @since  1.0.0
	 * @var null
	 */
	protected $_factory_id = null;

	/**
	 * Internal list of messages which is passed between functions.
	 * The message list is shared between all controller objects!
	 *
	 * @since  1.0.0
	 *
	 * @see _message()
	 * @var array
	 */
	static private $_messages = array();

	/**
	 * Called before loading the model.
	 *
	 * @since  1.0.0
	 */
	public function before_load() {
		do_action( 'ms_hooker_before_load', $this );
	}

	/**
	 * Load the model data.
	 *
	 * @since  1.0.0
	 */
	public function load( $model_id = false ) {
		throw new Exception( 'Method to be implemented in child class' );
	}

	/**
	 * Called after loading model data.
	 *
	 * @since  1.0.0
	 */
	public function after_load() {
		do_action( 'ms_hooker_after_load', $this );
	}

	/**
	 * Called my MS_Factory after an object was either created or loaded.
	 *
	 * @since  1.0.0
	 */
	public function prepare_obj() {
		do_action( 'ms_hooker_prepare_obj', $this );
	}

	/**
	 * Called by MS_Factory when the first object of the class is created/loaded
	 *
	 * @since  1.0.0
	 */
	static public function prepare_class() {
		do_action( 'ms_hooker_prepare_class' );
	}

	/**
	 * Returns the callback array for the specified method
	 *
	 * @since  1.0.0
	 * @param  string $tag The tag that is addressed by the callback.
	 * @param  string|array $method The callback method.
	 * @return array A working callback.
	 */
	private function get_callback( $tag, $method ) {
		if ( is_array( $method ) ) {
			$callback = $method;
		} else {
			$callback = array( $this, ! empty( $method ) ? $method : $tag );
		}

		return $callback;
	}

	/**
	 * Registers an action hook.
	 *
	 * @since  1.0.0
	 *
	 * @uses add_action() To register action hook.
	 *
	 * @param  string $tag The name of the action to which the $method is hooked.
	 * @param  string $method The name of the method to be called.
	 * @param  int $priority optional. Used to specify the order in which the
	 *         functions associated with a particular action are executed
	 *         (default: 10). Lower numbers correspond with earlier execution,
	 *         and functions with the same priority are executed in the order in
	 *         which they were added to the action.
	 * @param  int $accepted_args optional. The number of arguments the function
	 *         accept (default 1).
	 * @return MS_Hooker The Object.
	 */
	protected function add_action( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		add_action(
			$tag,
			$this->get_callback( $tag, $method ),
			$priority,
			$accepted_args
		);

		return $this;
	}

	/**
	 * Executes the callback function instantly if the specified action was
	 * already fired. If the action was not fired yet then the action handler
	 * is registered via add_action().
	 *
	 * Important note:
	 * If the callback is executed instantly, then the functionr receives NO
	 * parameters!
	 *
	 * @since  1.0.0
	 *
	 * @uses add_action() To register action hook.
	 *
	 * @param  string $tag
	 * @param  string $method
	 * @param  int $priority
	 * @param  int $accepted_args
	 * @return MS_Hooker
	 */
	protected function run_action( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		$callback = $this->get_callback( $tag, $method );

		if ( did_action( $tag ) ) {
			// Note: No argument is passed to the callback!
			call_user_func( $callback );
		} else {
			add_action(
				$tag,
				$callback,
				$priority,
				$accepted_args
			);
		}

		return $this;
	}

	/**
	 * Removes an action hook.
	 *
	 * @since  1.0.0
	 * @uses remove_action() To remove action hook.
	 *
	 * @param  string $tag The name of the action to which the $method is hooked.
	 * @param  string $method The name of the method to be called.
	 * @param  int $priority optional. Used to specify the order in which the
	 *         functions associated with a particular action are executed
	 *         (default: 10). Lower numbers correspond with earlier execution,
	 *         and functions with the same priority are executed in the order in
	 *         which they were added to the action.
	 * @return MS_Hooker
	 */
	protected function remove_action( $tag, $method = null, $priority = 10 ) {
		if ( null === $method ) {
			remove_all_actions( $tag );
		} else {
			remove_action(
				$tag,
				$this->get_callback( $tag, $method ),
				$priority
			);
		}

		return $this;
	}

	/**
	 * Registers AJAX action hook.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $tag The name of the AJAX action to which the $method is
	 *         hooked.
	 * @param  string $method Optional. The name of the method to be called.
	 *         If the name of the method is not provided, tag name will be used
	 *         as method name.
	 * @param  boolean $private Optional. Determines if we should register hook
	 *         for logged in users.
	 * @param  boolean $public Optional. Determines if we should register hook
	 *         for not logged in users.
	 * @return MS_Hooker
	 */
	protected function add_ajax_action( $tag, $method = '', $private = true, $public = false ) {
		if ( $private ) {
			$this->run_action( 'wp_ajax_' . $tag, $method );
		}

		if ( $public ) {
			$this->run_action( 'wp_ajax_nopriv_' . $tag, $method );
		}

		return $this;
	}

	/**
	 * Removes AJAX action hook.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $tag The name of the AJAX action to which the $method is
	 *         hooked.
	 * @param  string $method Optional. The name of the method to be called. If
	 *         the name of the method is not provided, tag name will be used as
	 *         method name.
	 * @param  boolean $private Optional. Determines if we should register hook
	 *         for logged in users.
	 * @param  boolean $public Optional. Determines if we should register hook
	 *         for not logged in users.
	 * @return MS_Hooker
	 */
	protected function remove_ajax_action( $tag, $method = null, $private = true, $public = false ) {
		if ( $private ) {
			$this->remove_action( 'wp_ajax_' . $tag, $method );
		}

		if ( $public ) {
			$this->remove_action( 'wp_ajax_nopriv_' . $tag, $method );
		}

		return $this;
	}

	/**
	 * Registers a filter hook.
	 *
	 * @since  1.0.0
	 *
	 * @uses add_filter() To register filter hook.
	 *
	 * @param  string $tag The name of the filter to hook the $method to.
	 * @param  string $method The name of the method to be called when the
	 *         filter is applied.
	 * @param  int $priority optional. Used to specify the order in which the
	 *         functions associated with a particular action are executed
	 *         (default: 10). Lower numbers correspond with earlier execution,
	 *         and functions with the same priority are executed in the order in
	 *         which they were added to the action.
	 * @param  int $accepted_args optional. The number of arguments the function
	 *         accept (default 1).
	 * @return MS_Hooker
	 */
	protected function add_filter( $tag, $method = '', $priority = 10, $accepted_args = 1 ) {
		$args = func_get_args();

		add_filter(
			$tag,
			$this->get_callback( $tag, $method ),
			$priority,
			$accepted_args
		);
		return $this;
	}

	/**
	 * Removes a filter hook.
	 *
	 * @since  1.0.0
	 *
	 * @uses remove_filter() To remove filter hook.
	 *
	 * @param  string $tag The name of the filter to remove the $method to.
	 * @param  string $method The name of the method to remove.
	 * @param  int $priority optional. The priority of the function (default: 10).
	 * @return MS_Hooker
	 */
	protected function remove_filter( $tag, $method = null, $priority = 10 ) {
		if ( null === $method ) {
			remove_all_filters( $tag );
		} else {
			remove_filter(
				$tag,
				$this->get_callback( $tag, $method ),
				$priority
			);
		}

		return $this;
	}

	/**
	 * Unbinds all hooks previously registered for actions and/or filters.
	 *
	 * @since  1.0.0
	 *
	 * @param boolean $actions Optional. TRUE to unbind all actions hooks.
	 * @param boolean $filters Optional. TRUE to unbind all filters hooks.
	 */
	public function unbind( $actions = true, $filters = true ) {
		$types = array();

		if ( $actions ) {
			$types['actions'] = 'remove_action';
		}

		if ( $filters ) {
			$types['filters'] = 'remove_filter';
		}

		foreach ( $types as $hooks => $method ) {
			foreach ( $this->$hooks as $hook ) {
				call_user_func_array( $method, $hook );
			}
		}
	}

	/**
	 * Returns property associated with the render.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property
	 *         doesn't exist.
	 */
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			return $this->$property;
		}
	}

	/**
	 * Associates the render with specific property.
	 *
	 * @since  1.0.0
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			$this->$property = $value;
		}
	}

	/**
	 * Reset the message array.
	 *
	 * The _message_ functions are used to set and pass data between functions;
	 * the data is meant to be displayed and is not stored in the database.
	 *
	 * @since  1.0.0
	 */
	static protected function _message_reset() {
		MS_Hooker::$_messages = array();
	}

	/**
	 * Get or set a message.
	 *
	 * _message() .. return the array with all messages
	 * _message( 'key' ) .. return the message 'key'
	 * _message( 'key', 'value' ) .. set 'value' as message 'key'
	 *
	 * @since  1.0.0
	 */
	static protected function _message( $key = null, $value = null ) {
		if ( ! is_array( MS_Hooker::$_messages ) ) {
			$this->_message_reset();
		}

		if ( null === $key ) {
			return MS_Hooker::$_messages;
		} else if ( null !== $key && null === $value ) {
			if ( isset( MS_Hooker::$_messages[$key] ) ) {
				return MS_Hooker::$_messages[$key];
			} else {
				return '';
			}
		} else if ( null !== $key && null !== $value ) {
			MS_Hooker::$_messages[$key] = $value;
		}
	}

	/**
	 * Debug function for developers.
	 * This will dump the current object to the output stream.
	 *
	 * @since  1.0.0
	 */
	public function dump() {
		lib2()->debug->enable();
		lib2()->debug->dump( $this );
	}
}