<?php
/**
 * The Array component.
 * Access via function `lib3()->array`.
 *
 * @since  1.1.4
 */
class TheLib_Array extends TheLib {

	/**
	 * If the specified variable is an array it will be returned. Otherwise
	 * an empty array is returned.
	 *
	 * @since  1.0.14
	 * @api
	 *
	 * @param  mixed $val1 Value that maybe is an array.
	 * @param  mixed $val2 Optional, Second value that maybe is an array.
	 * @return array
	 */
	public function get( &$val1, $val2 = array() ) {
		if ( is_array( $val1 ) ) {
			return $val1;
		} else if ( is_array( $val2 ) ) {
			return $val2;
		} else {
			return array();
		}
	}

	/**
	 * Inserts any number of scalars or arrays at the point
	 * in the haystack immediately after the search key ($needle) was found,
	 * or at the end if the needle is not found or not supplied.
	 * Modifies $haystack in place.
	 *
	 * @since  1.1.3
	 * @api
	 *
	 * @param array &$haystack The associative array to search. This will be modified by the function
	 * @param string $where [after|before]
	 * @param string $needle The key to search for
	 * @param mixed $stuff One or more arrays or scalars to be inserted into $haystack
	 * @return int The index at which $needle was found
	 */
	public function insert( &$haystack, $where, $needle, $stuff ){
		if ( ! is_array( $haystack ) ) { return $haystack; }

		$new_array = array();
		for ( $i = 3; $i < func_num_args(); ++$i ){
			$arg = func_get_arg( $i );
			if ( is_array( $arg ) ) {
				$new_array = array_merge( $new_array, $arg );
			} else {
				$new_array[] = $arg;
			}
		}

		$i = 0;
		foreach ( $haystack as $key => $value ) {
			$i += 1;

			if ( $key == $needle ) {
				if ( 'before' == $where ) {
					$i -= 1;
				}

				break;
			}
		}

		$haystack = array_merge(
			array_slice( $haystack, 0, $i, true ),
			$new_array,
			array_slice( $haystack, $i, null, true )
		);

		return $i;
	}

	/**
	 * Tests if the given array is sequential or associative.
	 *
	 * It is considered sequential, when all array keys are integers.
	 * Otherwise the result is false (meaning: associative array)
	 *
	 * @since  1.1.3
	 * @api
	 *
	 * @param  array $array
	 * @return bool
	 */
	public function is_seq( $array ) {
		for (
			reset( $array );
			is_int( key( $array ) );
			next( $array )
		) {}
		return is_null( key( $array ) );
	}

	/**
	 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
	 * keys to arrays rather than overwriting the value in the first array with the duplicate
	 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
	 * this happens (documented behavior):
	 *
	 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('org value', 'new value'));
	 *
	 * merge_recursive_distinct does not change the datatypes of the values in the arrays.
	 * Matching keys' values in the second array overwrite those in the first array, as is the
	 * case with array_merge, i.e.:
	 *
	 * merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('new value'));
	 *
	 * Parameters are passed by reference, though only for performance reasons. They're not
	 * altered by this function.
	 *
	 * @since 1.1.2
	 * @api
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	 */
	public function merge_recursive_distinct( array &$array1, array &$array2 ) {
		$merged = $array1;

		foreach ( $array2 as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) ) {
				if ( $this->is_seq( $value ) && $this->is_seq( $merged[$key] ) ) {
					$merged[$key] = array_merge( $merged[$key], $value );
				} else {
					$merged[$key] = $this->merge_recursive_distinct( $merged[$key], $value );
				}
			} else {
				$merged[$key] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Checks if the given array contains all the specified fields.
	 * If fields are not defined then they will be added to the source array
	 * with the boolean value false.
	 *
	 * This function is used to initialize optional fields.
	 * It is optimized and tested to yield best performance.
	 *
	 * @since  1.0.14
	 * @api
	 *
	 * @param  Array|Object $arr The array or object to check.
	 * @param  strings|Array $fields List of fields to check for.
	 * @return int Number of missing fields that were initialized.
	 */
	public function equip( &$arr, $fields ) {
		$missing = 0;
		$is_obj = false;

		if ( is_object( $arr ) ) { $is_obj = true; }
		else if ( ! is_array( $arr ) ) { return -1; }

		if ( ! is_array( $fields ) ) {
			$fields = func_get_args();
			array_shift( $fields ); // Remove $arr from the field list.
		}

		foreach ( $fields as $field ) {
			if ( $is_obj ) {
				if ( ! property_exists( $arr, $field ) ) {
					$arr->$field = false;
					$missing += 1;
				}
			} else {
				if ( ! isset( $arr[ $field ] ) ) {
					$arr[ $field ] = false;
					$missing += 1;
				}
			}
		}

		return $missing;
	}

	/**
	 * Short function for lib3()->equip( $_POST, ... )
	 *
	 * @since  1.0.14
	 * @api
	 * @uses equip()
	 *
	 * @param  strings|Array <param list>
	 * @return int Number of missing fields that were initialized.
	 */
	public function equip_post( $fields ) {
		$fields = is_array( $fields ) ? $fields : func_get_args();
		return $this->equip( $_POST, $fields );
	}

	/**
	 * Short function for lib3()->equip( $_REQUEST, ... )
	 *
	 * @since  1.0.14
	 * @api
	 * @uses equip()
	 *
	 * @param  strings|Array <param list>
	 * @return int Number of missing fields that were initialized.
	 */
	public function equip_request( $fields ) {
		$fields = is_array( $fields ) ? $fields : func_get_args();
		return $this->equip( $_REQUEST, $fields );
	}

	/**
	 * Short function for lib3()->equip( $_GET, ... )
	 *
	 * @since  1.1.3
	 * @api
	 * @uses equip()
	 *
	 * @param  strings|Array <param list>
	 * @return int Number of missing fields that were initialized.
	 */
	public function equip_get( $fields ) {
		$fields = is_array( $fields ) ? $fields : func_get_args();
		return $this->equip( $_GET, $fields );
	}

	/**
	 * By default WordPress escapes all GPC values with slashes.
	 * {@see wp-includes/load.php wp_magic_quotes()}
	 *
	 * This function can be used to strip slashes of a list of parameters. This
	 * ensures that not all parameters are un-escaped but only ones that are
	 * used by the current function.
	 *
	 * @since  1.1.4
	 * @api
	 *
	 * @param  Array $arr The array or object to check.
	 * @param  strings|Array $fields List of fields to check for.
	 * @return int Number of fields that were un-escaped.
	 */
	public function strip_slashes( &$arr, $fields ) {
		$modified = 0;
		if ( ! is_array( $arr ) ) { return -1; }

		if ( ! is_array( $fields ) ) {
			$fields = func_get_args();
			array_shift( $fields ); // Remove $arr from the field list.
		}

		foreach ( $fields as $field ) {
			if ( isset( $arr[ $field ] ) ) {
				$arr[ $field ] = stripslashes_deep( $arr[$field] );
				$modified += 1;
			}
		}

		return $modified;
	}

}