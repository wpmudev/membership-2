<?php
/**
 * Date Query
 *
 * Base class for Date query params parsing
 * Modified class of WP_Date_Query ( https://codex.wordpress.org/Function_Reference/WP_Query )
 *
 * @since  1.2
 */
class MS_Helper_Database_Query_Base_Date extends MS_Helper {

    /**
	 * Array of date queries.
	 *
	 * See WP_Date_Query::__construct() for information on date query arguments.
	 *
	 * @since 1.2
	 * @access public
	 * @var array
	 */
	public $queries = array();

	/**
	 * The default relation between top-level queries. Can be either 'AND' or 'OR'.
	 *
	 * @since 1.2
	 * @access public
	 * @var string
	 */
	public $relation = 'AND';

	/**
	 * The column to query against. Can be changed via the query arguments.
	 *
	 * @since 1.2
	 * @access public
	 * @var string
	 */
	public $column = 'date_created';

	/**
	 * The value comparison operator. Can be changed via the query arguments.
	 *
	 * @since 1.2
	 * @access public
	 * @var array
	 */
	public $compare = '=';

	/**
	 * Supported time-related parameter keys.
	 *
	 * @since 1.2
	 * @access public
	 * @var array
	 */
	public $time_keys = array( 'after', 'before', 'year', 'month', 'monthnum', 'week', 'w', 'dayofyear', 'day', 'dayofweek', 'dayofweek_iso', 'hour', 'minute', 'second' );


    public function __construct( $date_query, $default_column = 'date_created' ) {
        if ( ! is_array( $date_query ) ) {
			return;
		}

        if ( empty( $date_query ) ) {
			return;
		}

        if ( isset( $date_query['relation'] ) && 'OR' === strtoupper( $date_query['relation'] ) ) {
			$this->relation = 'OR';
		} else {
			$this->relation = 'AND';
		}

        // Support for passing time-based keys in the top level of the $date_query array.
		if ( ! isset( $date_query[0] ) && ! empty( $date_query ) ) {
			$date_query = array( $date_query );
		}

		if ( ! empty( $date_query['column'] ) ) {
			$date_query['column'] = esc_sql( $date_query['column'] );
		} else {
			$date_query['column'] = esc_sql( $default_column );
		}

		$this->column   = $date_query['column'];
		$this->compare  = $this->get_compare( $date_query );
		$this->queries  = $this->sanitize_query( $date_query );
    }


    /**
	 * Determines and validates what comparison operator to use.
	 *
	 * @since 1.2
	 * @access public
	 *
	 * @param array $query A date query or a date subquery.
	 * @return string The comparison operator.
	 */
	private function get_compare( $query ) {
		if ( ! empty( $query['compare'] ) && in_array( $query['compare'], array( '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ) ) )
			return strtoupper( $query['compare'] );

		return $this->compare;
	}

    private function sanitize_query( $queries, $parent_query = null ) {

        $cleaned_query = array();

		$defaults = array(
			'column'   => $this->column,
			'compare'  => '=',
			'relation' => 'AND',
		);

		// Numeric keys should always have array values.
		foreach ( $queries as $qkey => $qvalue ) {
			if ( is_numeric( $qkey ) && ! is_array( $qvalue ) ) {
				unset( $queries[ $qkey ] );
			}
		}

		// Each query should have a value for each default key. Inherit from the parent when possible.
		foreach ( $defaults as $dkey => $dvalue ) {
			if ( isset( $queries[ $dkey ] ) ) {
				continue;
			}

			if ( isset( $parent_query[ $dkey ] ) ) {
				$queries[ $dkey ] = $parent_query[ $dkey ];
			} else {
				$queries[ $dkey ] = $dvalue;
			}
		}

		// Validate the dates passed in the query.
		if ( $this->is_first_order_clause( $queries ) ) {
			$this->validate_date_values( $queries );
		}

		foreach ( $queries as $key => $q ) {
			if ( ! is_array( $q ) || in_array( $key, $this->time_keys, true ) ) {
				// This is a first-order query. Trust the values and sanitize when building SQL.
				$cleaned_query[ $key ] = $q;
			} else {
				// Any array without a time key is another query, so we recurse.
				$cleaned_query[] = $this->sanitize_query( $q, $queries );
			}
		}

		return $cleaned_query;
    }

    /**
	 * Generate WHERE clause to be appended to a main query.
	 *
	 * @since 1.2
	 * @access public
	 *
	 * @return string.
	 */
	public function get_sql() {
		$sql = $this->get_sql_clauses();

		if ( isset( $sql['where'] ) ) {
            return $sql['where'];
        }
        return '';
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Called by the public WP_Date_Query::get_sql(), this method is abstracted
	 * out to maintain parity with the other Query classes.
	 *
	 * @since 1.2
	 *
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	private function get_sql_clauses() {
		$sql = $this->get_sql_for_query( $this->queries );

		if ( ! empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . $sql['where'];
		}

		return $sql;
	}

	/**
	 * Generate SQL clauses for a single query array.
	 *
	 * If nested subqueries are found, this method recurses the tree to
	 * produce the properly nested SQL.
	 *
	 * @since 1.2
	 *
	 * @param array $query Query to parse.
	 * @param int   $depth Optional. Number of tree levels deep we currently are.
	 *                     Used to calculate indentation. Default 0.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a single query array.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	private function get_sql_for_query( $query, $depth = 0 ) {
		$sql_chunks = array(
			'join'  => array(),
			'where' => array(),
		);

		$sql = array(
			'join'  => '',
			'where' => '',
		);

		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= "  ";
		}

		foreach ( $query as $key => $clause ) {
			if ( 'relation' === $key ) {
				$relation = $query['relation'];
			} elseif ( is_array( $clause ) ) {

				// This is a first-order clause.
				if ( $this->is_first_order_clause( $clause ) ) {
					$clause_sql = $this->get_sql_for_clause( $clause, $query );

					$where_count = count( $clause_sql['where'] );
					if ( ! $where_count ) {
						$sql_chunks['where'][] = '';
					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];
					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );
				// This is a subquery, so we recurse.
				} else {
					$clause_sql = $this->get_sql_for_query( $clause, $depth + 1 );

					$sql_chunks['where'][] = $clause_sql['where'];
					$sql_chunks['join'][]  = $clause_sql['join'];
				}
			}
		}

		// Filter to remove empties.
		$sql_chunks['join']  = array_filter( $sql_chunks['join'] );
		$sql_chunks['where'] = array_filter( $sql_chunks['where'] );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}

		// Filter duplicate JOIN clauses and combine into a single string.
		if ( ! empty( $sql_chunks['join'] ) ) {
			$sql['join'] = implode( ' ', array_unique( $sql_chunks['join'] ) );
		}

		// Generate a single WHERE clause with proper brackets and indentation.
		if ( ! empty( $sql_chunks['where'] ) ) {
			$sql['where'] = '( ' . "\n  " . $indent . implode( ' ' . "\n  " . $indent . $relation . ' ' . "\n  " . $indent, $sql_chunks['where'] ) . "\n" . $indent . ')';
		}

		return $sql;
	}

	/**
	 * Turns a single date clause into pieces for a WHERE clause.
	 *
	 * A wrapper for get_sql_for_clause(), included here for backward
	 * compatibility while retaining the naming convention across Query classes.
	 *
	 * @since  1.2
	 *
	 * @param  array $query Date query arguments.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	private function get_sql_for_subquery( $query ) {
		return $this->get_sql_for_clause( $query, '' );
	}

	/**
	 * Turns a first-order date query into SQL for a WHERE clause.
	 *
	 * @since  1.2
	 *
	 * @param  array $query        Date query clause.
	 * @param  array $parent_query Parent query of the current date query.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	private function get_sql_for_clause( $query, $parent_query ) {
		global $wpdb;

		// The sub-parts of a $where part.
		$where_parts = array();

		$column = ( ! empty( $query['column'] ) ) ? esc_sql( $query['column'] ) : $this->column;

		$column = $this->validate_column( $column );

		$compare = $this->get_compare( $query );

		$inclusive = ! empty( $query['inclusive'] );

		// Assign greater- and less-than values.
		$lt = '<';
		$gt = '>';

		if ( $inclusive ) {
			$lt .= '=';
			$gt .= '=';
		}

		// Range queries.
		if ( ! empty( $query['after'] ) ) {
			$where_parts[] = $wpdb->prepare( "$column $gt %s", $this->build_mysql_datetime( $query['after'], ! $inclusive ) );
		}
		if ( ! empty( $query['before'] ) ) {
			$where_parts[] = $wpdb->prepare( "$column $lt %s", $this->build_mysql_datetime( $query['before'], $inclusive ) );
		}
		// Specific value queries.

		if ( isset( $query['year'] ) && $value = $this->build_value( $compare, $query['year'] ) )
			$where_parts[] = "YEAR( $column ) $compare $value";

		if ( isset( $query['month'] ) && $value = $this->build_value( $compare, $query['month'] ) ) {
			$where_parts[] = "MONTH( $column ) $compare $value";
		} elseif ( isset( $query['monthnum'] ) && $value = $this->build_value( $compare, $query['monthnum'] ) ) {
			$where_parts[] = "MONTH( $column ) $compare $value";
		}
		if ( isset( $query['week'] ) && false !== ( $value = $this->build_value( $compare, $query['week'] ) ) ) {
			$where_parts[] = _wp_mysql_week( $column ) . " $compare $value";
		} elseif ( isset( $query['w'] ) && false !== ( $value = $this->build_value( $compare, $query['w'] ) ) ) {
			$where_parts[] = _wp_mysql_week( $column ) . " $compare $value";
		}
		if ( isset( $query['dayofyear'] ) && $value = $this->build_value( $compare, $query['dayofyear'] ) )
			$where_parts[] = "DAYOFYEAR( $column ) $compare $value";

		if ( isset( $query['day'] ) && $value = $this->build_value( $compare, $query['day'] ) )
			$where_parts[] = "DAYOFMONTH( $column ) $compare $value";

		if ( isset( $query['dayofweek'] ) && $value = $this->build_value( $compare, $query['dayofweek'] ) )
			$where_parts[] = "DAYOFWEEK( $column ) $compare $value";

		if ( isset( $query['dayofweek_iso'] ) && $value = $this->build_value( $compare, $query['dayofweek_iso'] ) )
			$where_parts[] = "WEEKDAY( $column ) + 1 $compare $value";

		if ( isset( $query['hour'] ) || isset( $query['minute'] ) || isset( $query['second'] ) ) {
			// Avoid notices.
			foreach ( array( 'hour', 'minute', 'second' ) as $unit ) {
				if ( ! isset( $query[ $unit ] ) ) {
					$query[ $unit ] = null;
				}
			}

			if ( $time_query = $this->build_time_query( $column, $compare, $query['hour'], $query['minute'], $query['second'] ) ) {
				$where_parts[] = $time_query;
			}
		}

		/*
		 * Return an array of 'join' and 'where' for compatibility
		 * with other query classes.
		 */
		return array(
			'where' => $where_parts,
			'join'  => array(),
		);
	}

	/**
	 * Builds and validates a value string based on the comparison operator.
	 *
	 * @since 1.2
	 *
	 * @param string $compare The compare operator to use
	 * @param string|array $value The value
	 * @return string|false|int The value to be used in SQL or false on error.
	 */
	private function build_value( $compare, $value ) {
		if ( ! isset( $value ) )
			return false;

		switch ( $compare ) {
			case 'IN':
			case 'NOT IN':
				$value = (array) $value;

				// Remove non-numeric values.
				$value = array_filter( $value, 'is_numeric' );

				if ( empty( $value ) ) {
					return false;
				}

				return '(' . implode( ',', array_map( 'intval', $value ) ) . ')';

			case 'BETWEEN':
			case 'NOT BETWEEN':
				if ( ! is_array( $value ) || 2 != count( $value ) ) {
					$value = array( $value, $value );
				} else {
					$value = array_values( $value );
				}

				// If either value is non-numeric, bail.
				foreach ( $value as $v ) {
					if ( ! is_numeric( $v ) ) {
						return false;
					}
				}

				$value = array_map( 'intval', $value );

				return $value[0] . ' AND ' . $value[1];

			default;
				if ( ! is_numeric( $value ) ) {
					return false;
				}

				return (int) $value;
		}
	}

	/**
	 * Builds a MySQL format date/time based on some query parameters.
	 *
	 * You can pass an array of values (year, month, etc.) with missing parameter values being defaulted to
	 * either the maximum or minimum values (controlled by the $default_to parameter). Alternatively you can
	 * pass a string that will be run through strtotime().
	 *
	 * @since 1.2
	 *
	 * @param string|array $datetime       An array of parameters or a strotime() string
	 * @param bool         $default_to_max Whether to round up incomplete dates. Supported by values
	 *                                     of $datetime that are arrays, or string values that are a
	 *                                     subset of MySQL date format ('Y', 'Y-m', 'Y-m-d', 'Y-m-d H:i').
	 *                                     Default: false.
	 * @return string|false A MySQL format date/time or false on failure
	 */
	private function build_mysql_datetime( $datetime, $default_to_max = false ) {
		$now = current_time( 'timestamp' );

		if ( ! is_array( $datetime ) ) {

			/*
			 * Try to parse some common date formats, so we can detect
			 * the level of precision and support the 'inclusive' parameter.
			 */
			if ( preg_match( '/^(\d{4})$/', $datetime, $matches ) ) {
				// Y
				$datetime = array(
					'year' => intval( $matches[1] ),
				);

			} elseif ( preg_match( '/^(\d{4})\-(\d{2})$/', $datetime, $matches ) ) {
				// Y-m
				$datetime = array(
					'year'  => intval( $matches[1] ),
					'month' => intval( $matches[2] ),
				);

			} elseif ( preg_match( '/^(\d{4})\-(\d{2})\-(\d{2})$/', $datetime, $matches ) ) {
				// Y-m-d
				$datetime = array(
					'year'  => intval( $matches[1] ),
					'month' => intval( $matches[2] ),
					'day'   => intval( $matches[3] ),
				);

			} elseif ( preg_match( '/^(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2})$/', $datetime, $matches ) ) {
				// Y-m-d H:i
				$datetime = array(
					'year'   => intval( $matches[1] ),
					'month'  => intval( $matches[2] ),
					'day'    => intval( $matches[3] ),
					'hour'   => intval( $matches[4] ),
					'minute' => intval( $matches[5] ),
				);
			}

			// If no match is found, we don't support default_to_max.
			if ( ! is_array( $datetime ) ) {
				// @todo Timezone issues here possibly
				return gmdate( 'Y-m-d H:i:s', strtotime( $datetime, $now ) );
			}
		}

		$datetime = array_map( 'absint', $datetime );

		if ( ! isset( $datetime['year'] ) )
			$datetime['year'] = gmdate( 'Y', $now );

		if ( ! isset( $datetime['month'] ) )
			$datetime['month'] = ( $default_to_max ) ? 12 : 1;

		if ( ! isset( $datetime['day'] ) )
			$datetime['day'] = ( $default_to_max ) ? (int) date( 't', mktime( 0, 0, 0, $datetime['month'], 1, $datetime['year'] ) ) : 1;

		if ( ! isset( $datetime['hour'] ) )
			$datetime['hour'] = ( $default_to_max ) ? 23 : 0;

		if ( ! isset( $datetime['minute'] ) )
			$datetime['minute'] = ( $default_to_max ) ? 59 : 0;

		if ( ! isset( $datetime['second'] ) )
			$datetime['second'] = ( $default_to_max ) ? 59 : 0;

		return sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $datetime['year'], $datetime['month'], $datetime['day'], $datetime['hour'], $datetime['minute'], $datetime['second'] );
	}

	/**
	 * Builds a query string for comparing time values (hour, minute, second).
	 *
	 * If just hour, minute, or second is set than a normal comparison will be done.
	 * However if multiple values are passed, a pseudo-decimal time will be created
	 * in order to be able to accurately compare against.
	 *
	 * @since 1.2
	 *
	 * @param string $column The column to query against. Needs to be pre-validated!
	 * @param string $compare The comparison operator. Needs to be pre-validated!
	 * @param int|null $hour Optional. An hour value (0-23).
	 * @param int|null $minute Optional. A minute value (0-59).
	 * @param int|null $second Optional. A second value (0-59).
	 * @return string|false A query part or false on failure.
	 */
	private function build_time_query( $column, $compare, $hour = null, $minute = null, $second = null ) {
		global $wpdb;

		// Have to have at least one
		if ( ! isset( $hour ) && ! isset( $minute ) && ! isset( $second ) )
			return false;

		// Complex combined queries aren't supported for multi-value queries
		if ( in_array( $compare, array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ) ) ) {
			$return = array();

			if ( isset( $hour ) && false !== ( $value = $this->build_value( $compare, $hour ) ) )
				$return[] = "HOUR( $column ) $compare $value";

			if ( isset( $minute ) && false !== ( $value = $this->build_value( $compare, $minute ) ) )
				$return[] = "MINUTE( $column ) $compare $value";

			if ( isset( $second ) && false !== ( $value = $this->build_value( $compare, $second ) ) )
				$return[] = "SECOND( $column ) $compare $value";

			return implode( ' AND ', $return );
		}

		// Cases where just one unit is set
		if ( isset( $hour ) && ! isset( $minute ) && ! isset( $second ) && false !== ( $value = $this->build_value( $compare, $hour ) ) ) {
			return "HOUR( $column ) $compare $value";
		} elseif ( ! isset( $hour ) && isset( $minute ) && ! isset( $second ) && false !== ( $value = $this->build_value( $compare, $minute ) ) ) {
			return "MINUTE( $column ) $compare $value";
		} elseif ( ! isset( $hour ) && ! isset( $minute ) && isset( $second ) && false !== ( $value = $this->build_value( $compare, $second ) ) ) {
			return "SECOND( $column ) $compare $value";
		}

		// Single units were already handled. Since hour & second isn't allowed, minute must to be set.
		if ( ! isset( $minute ) )
			return false;

		$format = $time = '';

		// Hour
		if ( null !== $hour ) {
			$format .= '%H.';
			$time   .= sprintf( '%02d', $hour ) . '.';
		} else {
			$format .= '0.';
			$time   .= '0.';
		}

		// Minute
		$format .= '%i';
		$time   .= sprintf( '%02d', $minute );

		if ( isset( $second ) ) {
			$format .= '%s';
			$time   .= sprintf( '%02d', $second );
		}

		return $wpdb->prepare( "DATE_FORMAT( $column, %s ) $compare %f", $format, $time );
	}
}

?>