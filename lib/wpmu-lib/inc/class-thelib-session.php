<?php
/**
 * The Session storage component.
 * Access via function `lib3()->session`.
 *
 * @since  1.1.4
 */
class TheLib_Session extends TheLib {

	/**
	 * Adds a value to the data collection in the user session.
	 *
	 * @since  1.0.15
	 * @api
	 *
	 * @param  string $key The key of the value.
	 * @param  mixed $value Value to store.
	 */
	public function add( $key, $value ) {
		self::_sess_add( 'store:' . $key, $value );
	}

	/**
	 * Returns the current data array of the specified value from user session.
	 *
	 * @since  1.0.15
	 * @api
	 *
	 * @param  string $key The key of the value.
	 * @return array The value, or an empty array if no value was assigned yet.
	 */
	public function get( $key ) {
		$vals = self::_sess_get( 'store:' . $key );
		foreach ( $vals as $key => $val ) {
			if ( null === $val ) { unset( $vals[ $key ] ); }
		}
		$vals = array_values( $vals );
		return $vals;
	}

	/**
	 * Returns the current data array of the specified value from user session
	 * and then clears the values from the session.
	 *
	 * @since  1.0.15
	 * @api
	 *
	 * @param  string $key The key of the value.
	 * @return array The value, or an empty array if no value was assigned yet.
	 */
	public function get_clear( $key ) {
		$val = $this->get( $key );
		self::_sess_clear( 'store:' . $key );
		return $val;
	}

}