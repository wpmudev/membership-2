<?php
/**
 * Base Query
 *
 * Base class for database queries
 * Modified version of WP_Query ( https://codex.wordpress.org/Function_Reference/WP_Query )
 *
 * @since  1.2
 */
class MS_Helper_Database_Query_Base_Core extends MS_Helper {

    /**
	 * Table name
	 *
	 * @since 1.2
	 * @access protected
	 * @var array
	 */
    protected $table_name;


	/**
	 * Meta Table name
	 *
	 * @since 1.2
	 * @access protected
	 * @var array
	 */
    protected $meta_table_name;

	/**
	 * Meta name
	 *
	 * @since 1.2
	 * @access protected
	 * @var array
	 */
    protected $meta_name = '';

	/**
	 * Date field
	 * The default date field for the table
	 *
	 * @since 1.2
	 * @access protected
	 * @var array
	 */
    protected $date_field;

	/**
	 * Name field
	 * USed for default order in search
	 *
	 * @since 1.2
	 * @access protected
	 * @var array
	 */
    protected $name_field;

	/**
	 * Search fields
	 *
	 * @since 1.2
	 * @access protected
	 * @var array
	 */
    protected $search_fields = array();

	/**
	 * Default table query keys
	 *
	 * @since 1.2
	 * @access public
	 * @var array
	 */
	public $default_query_keys = array();

	/**
	 * Query vars set by the user
	 *
	 * @since 1.2
	 * @access public
	 * @var array
	 */
	public $query;

	/**
	 * Query vars, after parsing
	 *
	 * @since 1.2
	 * @access public
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * Meta query
	 *
	 * @since 1.2
	 * @access public
	 * @var object MS_Helper_Database_Query_Base_Meta
	 */
	public $meta_query;

	/**
	 * Date query container
	 *
	 * @since 1.2
	 * @access public
	 * @var object MS_Helper_Database_Query_Base_Date
	 */
	public $date_query = false;

	/**
	 * Holds the data for a single object that is queried.
	 *
	 * Holds the contents of the current object
	 *
	 * @since 1.2
	 * @access public
	 * @var object|array
	 */
	public $queried_object;

	/**
	 * The ID of the queried object.
	 *
	 * @since 1.2
	 * @access public
	 * @var int
	 */
	public $queried_object_id;

	/**
	 * Get object database query.
	 *
	 * @since 1.2
	 * @access public
	 * @var string
	 */
	public $request;

	/**
	 * List of objects.
	 *
	 * @since 1.2
	 * @access public
	 * @var array
	 */
	public $objects;

	/**
	 * The amount of objects for the current query.
	 *
	 * @since 1.2
	 * @access public
	 * @var int
	 */
	public $object_count = 0;

	/**
	 * Index of the current item in the loop.
	 *
	 * @since 1.2
	 * @access public
	 * @var int
	 */
	public $current_object = -1;

	/**
	 * Whether the loop has started and the caller is in the loop.
	 *
	 * @since 1.2
	 * @access public
	 * @var bool
	 */
	public $in_the_loop = false;

	/**
	 * The current object.
	 *
	 * @since 1.2
	 * @access public
	 * @var Array|Object
	 */
	public $object;

	/**
	 * The amount of found objects for the current query.
	 *
	 * If limit clause was not used, equals $object_count.
	 *
	 * @since 1.2
	 * @access public
	 * @var int
	 */
	public $found_objects = 0;

	/**
	 * Set if query is part of a date.
	 *
	 * @since 1.2
	 * @access public
	 * @var bool
	 */
	public $is_date = false;

	/**
	 * Set if query contains a year.
	 *
	 * @since 1.2
	 * @access public
	 * @var bool
	 */
	public $is_year = false;

	/**
	 * Set if query contains a month.
	 *
	 * @since 1.2
	 * @access public
	 * @var bool
	 */
	public $is_month = false;

	/**
	 * Set if query contains a day.
	 *
	 * @since 1.2
	 * @access public
	 * @var bool
	 */
	public $is_day = false;

	/**
	 * Set if query contains time.
	 *
	 * @since 1.2
	 * @access public
	 * @var bool
	 */
	public $is_time = false;

	/**
	 * Set if query was part of a search result.
	 *
	 * @since 1.2
	 * @access public
	 * @var bool
	 */
	public $is_search = false;

	/**
	 * Set if query is paged
	 *
	 * @since 1.2
	 * @access public
	 * @var bool
	 */
	public $is_paged = false;

	/**
	 * Set if query is part of administration page.
	 *
	 * @since 1.2
	 * @access public
	 * @var bool
	 */
	public $is_admin = false;

	/**
	 * Stores the ->query_vars state like md5(serialize( $this->query_vars ) ) so we know
	 * whether we have to re-parse because something has changed
	 *
	 * @since 1.2
	 * @access private
	 * @var bool|string
	 */
	private $query_vars_hash = false;

	/**
	 * Whether query vars have changed since the initial parse_query() call
	 *
	 * @since 1.2
	 * @access private
	 */
	private $query_vars_changed = true;

    /**
	 * Constructor.
	 *
	 * Sets up the Database query, if parameter is not empty.
	 * @access public
	 *
	 * @param String|Array $query URL query string or array of vars.
	 *
	 * @since 1.2
	 */
    public function __construct( $query = '' ) {
        $this->init_query_options();
		$this->meta_table_name 	= MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        if ( empty( $this->table_name ) ){
            throw new MS_Exception( 'Class ' . get_class( $this ) . ' has no table name defined' );
        }

		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
    }

	/**
	 * Initialize the query options
	 * Method should be overriden in the child class
	 *
	 * @since 1.2
	 */
    protected function init_query_options() {
		
    }

	/**
	 * Handle custom where clauses
	 *
	 */
	protected function custom_where_clause( $where, $vars, $wpdb ) {

		return $where;
	}


	/**
	 * Resets query flags to false.
	 *
	 * The query flags are what page info WordPress was able to figure out.
	 *
	 * @since 1.2
	 * @access private
	 */
	private function init_query_flags() {
		$this->is_date 		= false;
		$this->is_year 		= false;
		$this->is_month 	= false;
		$this->is_day 		= false;
		$this->is_time 		= false;
		$this->is_search 	= false;
		$this->is_paged 	= false;
		$this->is_admin 	= false;
	}

	/**
	 * Initiates object properties and sets default values.
	 *
	 * @since 1.2
	 * @access public
	 */
	public function init() {
		unset( $this->objects );
		unset( $this->query );
		unset( $this->queried_object );
		unset( $this->queried_object_id );
		unset( $this->request );
		unset( $this->object );
		$this->objects 				= null;
		$this->query 				= null;
		$this->queried_object 		= null;
		$this->queried_object_id 	= null;
		$this->request 				= null;
		$this->object 				= null;
		$this->query_vars 			= array();
		$this->object_count 		= 0;
		$this->current_object 		= -1;
		$this->in_the_loop 			= false;
		$this->found_objects 		= 0;
		$this->init_query_flags();
	}

	/**
	 * Reparse the query vars.
	 *
	 * @since 1.2
	 * @access public
	 */
	public function parse_query_vars() {
		$this->parse_query();
	}

	/**
	 * Fills in the query variables, which do not exist within the parameter.
	 *
	 * @since 1.2
	 * @access public
	 *
	 * @param array $array Defined query variables.
	 * @return array Complete query variables with undefined ones filled in empty.
	 */
	public function fill_query_vars( $array ) {
		$keys = array(
			'm'
			, 'p'
			, 'second'
			, 'minute'
			, 'hour'
			, 'day'
			, 'monthnum'
			, 'year'
			, 'w'
			, 'paged'
			, 'meta_key'
			, 'meta_value'
			, 's'
			, 'fields'
			, 'per_page'
		);

		$keys = array_merge( $keys, $this->default_query_keys );

		foreach ( $keys as $key ) {
			if ( !isset($array[$key]) )
				$array[$key] = '';
		}

		$array_keys = array( 'object__in', 'object__not_in' );

		foreach ( $array_keys as $key ) {
			if ( !isset($array[$key]) )
				$array[$key] = array();
		}
		return $array;
	}

	/**
	 * Parse a query string and set query type booleans.
	 *
	 * @since 1.2
	 * @access public
	 *
	 * @param string|array $query {
	 *     Optional. Array or string of Query parameters.
	 *

	 *     @type array        $id__in            	   An array of IDs (OR in, no children).
	 *     @type array        $id__not_in        	   An array of IDs (NOT in).
	 *     @type array        $date_query              An associative array of MS_Helper_Database_Query_Base_Date arguments.
	 *                                                 See MS_Helper_Database_Query_Base_Date::__construct().
	 *     @type int          $day                     Day of the month. Default empty. Accepts numbers 1-31.
	 *     @type bool         $exact                   Whether to search by exact keyword. Default false.
	 *     @type string|array $fields                  Which fields to return. Single field or all fields (string),
	 *                                                 or array of fields. 'id=>parent' uses 'id' and 'post_parent'.
	 *                                                 Default all fields. Accepts 'ids', 'id=>parent'.
	 *     @type int          $hour                    Hour of the day. Default empty. Accepts numbers 0-23.
	 *     @type int          $m                       Combination YearMonth. Accepts any four-digit year and month
	 *                                                 numbers 1-12. Default empty.
	 *     @type string       $meta_compare            Comparison operator to test the 'meta_value'.
	 *     @type string       $meta_key                Custom field key.
	 *     @type array        $meta_query              An associative array of MS_Helper_Database_Query_Base_Meta arguments. See MS_Helper_Database_Query_Base_Meta.
	 *     @type string       $meta_value              Custom field value.
	 *     @type int          $meta_value_num          Custom field value number.
	 *     @type int          $monthnum                The two-digit month. Default empty. Accepts numbers 1-12.
	 *     @type bool         $nopaging                Show all objects (true) or paginate (false). Default false.
	 *     @type bool         $no_found_rows           Whether to skip counting the total rows found. Enabling can improve
	 *                                                 performance. Default false.
	 *     @type int          $offset                  The number of objects to offset before retrieval.
	 *     @type string       $order                   Designates ascending or descending order of objects. Default 'DESC'.
	 *                                                 Accepts 'ASC', 'DESC'.
	 *     @type string|array $orderby                 Sort retrieved objects by parameter. One or more options may be
	 *                                                 passed. To use 'meta_value', or 'meta_value_num',
	 *                                                 'meta_key=keyname' must be also be defined. To sort by a
	 *                                                 specific `$meta_query` clause, use that clause's array key.
	 *                                                 Default 'date'. Accepts 'none', 'date', 'ID', 'rand',
	 *                                                 'RAND(x)' (where 'x' is an integer seed value),
	 *                                                 'comment_count', 'meta_value', 'meta_value_num', 'object__in' and the array keys
	 *                                                 of `$meta_query`.
	 *     @type int          $p                       Object ID.
	 *     @type int          $page                    Show the number of objects that would show up on page X of a
	 *                                                 static front page.
	 *     @type int          $paged                   The number of the current page.
	 *
	 *     @type int          $second                  Second of the minute. Default empty. Accepts numbers 0-60.
	 *     @type bool         $sentence                Whether to search by phrase. Default false.
	 *     @type bool         $suppress_filters        Whether to suppress filters. Default false.
	 *
	 *     @type array        $meta_query              An associative array of MS_Helper_Database_Query_Base_Meta arguments.
	 *                                                 See MS_Helper_Database_Query_Base_Meta->queries.
	 *
	 *     @type int          $w                       The week number of the year. Default empty. Accepts numbers 0-53.
	 *     @type int          $year                    The four-digit year. Default empty. Accepts any four-digit year.
	 * }
	 */
	public function parse_query( $query =  '' ) {

		if ( ! empty( $query ) ) {
			$this->init();
			$this->query = $this->query_vars = wp_parse_args( $query );
		} elseif ( ! isset( $this->query ) ) {
			$this->query = $this->query_vars;
		}

		$this->query_vars = $this->fill_query_vars( $this->query_vars );
		$qv = &$this->query_vars;
		$this->query_vars_changed = true;

		$qv['year'] 	= absint( $qv['year'] );
		$qv['monthnum'] = absint( $qv['monthnum'] );
		$qv['day'] 		= absint( $qv['day'] );
		$qv['w'] 		= absint ($qv['w'] );
		$qv['m'] 		= is_scalar( $qv['m'] ) ? preg_replace( '|[^0-9]|', '', $qv['m'] ) : '';
		$qv['paged'] 	= absint( $qv['paged'] );
		if ( '' !== $qv['hour'] ) {
			$qv['hour'] = absint( $qv['hour'] );
		}
		if ( '' !== $qv['minute'] ) {
			$qv['minute'] = absint( $qv['minute'] );
		} 
		if ( '' !== $qv['second'] ) {
			$qv['second'] = absint( $qv['second'] );
		}

		// Fairly insane upper bound for search string lengths.
		if ( ! is_scalar( $qv['s'] ) || ( ! empty( $qv['s'] ) && strlen( $qv['s'] ) > 1600 ) ) {
			$qv['s'] = '';
		}

		if ( isset( $this->query['s'] ) ) {
			$this->is_search = true;
		}

		if ( '' !== $qv['second'] ) {
			$this->is_time = true;
			$this->is_date = true;
		}

		if ( '' !== $qv['minute'] ) {
			$this->is_time = true;
			$this->is_date = true;
		}

		if ( '' !== $qv['hour'] ) {
			$this->is_time = true;
			$this->is_date = true;
		}

		if ( $qv['day'] ) {
			if ( ! $this->is_date ) {
				$date = sprintf( '%04d-%02d-%02d', $qv['year'], $qv['monthnum'], $qv['day'] );
				if ( $qv['monthnum'] && $qv['year'] && ! wp_checkdate( $qv['monthnum'], $qv['day'], $qv['year'], $date ) ) {
					$qv['error'] = '404';
				} else {
					$this->is_day = true;
					$this->is_date = true;
				}
			}
		}

		if ( $qv['monthnum'] ) {
			if ( ! $this->is_date ) {
				if ( 12 < $qv['monthnum'] ) {
					$qv['error'] = '404';
				} else {
					$this->is_month = true;
					$this->is_date = true;
				}
			}
		}

		if ( $qv['year'] ) {
			if ( ! $this->is_date ) {
				$this->is_year = true;
				$this->is_date = true;
			}
		}

		if ( $qv['m'] ) {
			$this->is_date = true;
			if ( strlen($qv['m']) > 9 ) {
				$this->is_time 	= true;
			} elseif ( strlen( $qv['m'] ) > 7 ) {
				$this->is_day 	= true;
			} elseif ( strlen( $qv['m'] ) > 5 ) {
				$this->is_month = true;
			} else {
				$this->is_year 	= true;
			}
		}

		if ( '' != $qv['w'] ) {
			$this->is_date 		= true;
		}

		$this->query_vars_hash = false;

		if ( '' != $qv['paged'] && ( intval( $qv['paged'] ) > 1 ) )
			$this->is_paged = true;

		if ( is_admin() )
			$this->is_admin = true;

		$this->query_vars_hash 		= md5( serialize( $this->query_vars ) );
		$this->query_vars_changed 	= false;
	}

	/**
	 * Generate SQL for the WHERE clause based on passed search terms.
	 *
	 * @since 1.2
	 *
	 * @param array $q Query variables.
	 * @return string WHERE clause.
	 */
	protected function parse_search( &$q ) {
		global $wpdb;

		$search = '';

		// added slashes screw with quote grouping when done early, so done later
		$q['s'] = stripslashes( $q['s'] );
		if ( empty( $_GET['s'] ) && $this->is_main_query() )
			$q['s'] = urldecode( $q['s'] );
		// there are no line breaks in <input /> fields
		$q['s'] = str_replace( array( "\r", "\n" ), '', $q['s'] );
		$q['search_terms_count'] = 1;
		if ( ! empty( $q['sentence'] ) ) {
			$q['search_terms'] 	= array( $q['s'] );
		} else {
			if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $q['s'], $matches ) ) {
				$q['search_terms_count'] 	= count( $matches[0] );
				$q['search_terms'] 			= $this->parse_search_terms( $matches[0] );
				// if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence
				if ( empty( $q['search_terms'] ) || count( $q['search_terms'] ) > 9 )
					$q['search_terms'] 		= array( $q['s'] );
			} else {
				$q['search_terms'] 			= array( $q['s'] );
			}
		}

		$n = ! empty( $q['exact'] ) ? '' : '%';
		$searchand 					= '';
		$q['search_orderby_name'] 	= array();


		$exclusion_prefix 			= '-';

		if ( !empty( $this->search_fields ) ) {
			$total_search_fields = count( $this->search_fields );
			foreach ( $q['search_terms'] as $term ) {
				// If there is an $exclusion_prefix, terms prefixed with it should be excluded.
				$exclude = $exclusion_prefix && ( $exclusion_prefix === substr( $term, 0, 1 ) );
				if ( $exclude ) {
					$like_op  = 'NOT LIKE';
					$andor_op = 'AND';
					$term     = substr( $term, 1 );
				} else {
					$like_op  = 'LIKE';
					$andor_op = 'OR';
				}

				if ( $n && ! $exclude ) {
					$like = '%' . $wpdb->esc_like( $term ) . '%';
					$q['search_orderby_name'][] = $wpdb->prepare( "{$this->table_name}.{$this->name_field} LIKE %s", $like );
				}

				$like 			= $n . $wpdb->esc_like( $term ) . $n;
				$search_query 	= "{$searchand}(";
				$current 		= 0;
				foreach ( $this->search_fields as $search_field ) {
					if ( $current == 0 ) {
						$inner_search_query = $wpdb->prepare( "({$this->table_name}.$search_field $like_op %s)", $like );
					} else {
						$inner_search_query = " ({$this->table_name}.$search_field $like_op %s)";
						if ( $current != ( $total_search_fields - 1 ) ) {
							$inner_search_query .= "  $andor_op";
						}
						
					}
					$search_query .= $wpdb->prepare( $inner_search_query, $like );
					$current++;
				}
				$search_query 	.= ")";
				$search 		.= $search_query;
				$searchand 		= ' AND ';
			}
		}

		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
		}

		return $search;
	}


	/**
	 * Check if the terms are suitable for searching.
	 *
	 * @since 1.2
	 *
	 * @param array $terms Terms to check.
	 * @return array Terms
	 */
	protected function parse_search_terms( $terms ) {
		$strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
		$checked 	= array();

		foreach ( $terms as $term ) {
			// keep before/after spaces when term is for exact match
			if ( preg_match( '/^".+"$/', $term ) )
				$term = trim( $term, "\"'" );
			else
				$term = trim( $term, "\"' " );

			// Avoid single A-Z and single dashes.
			if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) )
				continue;

			$checked[] = $term;
		}

		return $checked;
	}


	/**
	 * Generate SQL for the ORDER BY condition based on passed search terms.
	 *
	 * @param array $q Query variables.
	 * @return string ORDER BY clause.
	 */
	protected function parse_search_order( &$q ) {
		global $wpdb;

		if ( $q['search_terms_count'] > 1 ) {
			$num_terms = count( $q['search_orderby_name'] );

			// If the search terms contain negative queries, don't bother ordering by sentence matches.
			$like = '';
			if ( ! preg_match( '/(?:\s|^)\-/', $q['s'] ) ) {
				$like = '%' . $wpdb->esc_like( $q['s'] ) . '%';
			}

			$search_orderby = '';

			// sentence match in the seatch name field
			if ( $like ) {
				$search_orderby .= $wpdb->prepare( "WHEN {$this->table_name}.{$this->name_field} LIKE %s THEN 1 ", $like );
			}

			// sanity limit, sort as sentence when more than 6 terms
			// (few searches are longer than 6 terms and most titles are not)
			if ( $num_terms < 7 ) {
				// all words in title
				$search_orderby .= 'WHEN ' . implode( ' AND ', $q['search_orderby_name'] ) . ' THEN 2 ';
				// any word in title, not needed when $num_terms == 1
				if ( $num_terms > 1 )
					$search_orderby .= 'WHEN ' . implode( ' OR ', $q['search_orderby_name'] ) . ' THEN 3 ';
			}

			if ( $search_orderby ) {
				$search_orderby = '(CASE ' . $search_orderby . 'ELSE 6 END)';
			}
		} else {
			// single word or sentence search
			$search_orderby = reset( $q['search_orderby_name'] ) . ' DESC';
		}

		return $search_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 *
	 * @since 1.2
	 * @access protected
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	/**
	 * Retrieve the objects based on query variables.
	 *
	 * There are a few filters and actions that can be used to modify the post
	 * database query.
	 *
	 * @since 1.2
	 * @access public
	 *
	 * @return array List of objects.
	 */
	public function get_objects() {
		global $wpdb;

		$this->parse_query();

		do_action_ref_array( 'membership_pre_get_objects', array( &$this ) );

		// Shorthand.
		$q = &$this->query_vars;

		$q = $this->fill_query_vars($q);

		// Parse meta query
		if ( !empty( $this->meta_name ) ) {
			$this->meta_query = new MS_Helper_Database_Query_Base_Meta( $this->meta_name );
			$this->meta_query->parse_query_vars( $q );
		}

		$hash = md5( serialize( $this->query_vars ) );
		if ( $hash != $this->query_vars_hash ) {
			$this->query_vars_changed = true;
			$this->query_vars_hash = $hash;
		}
		unset($hash);

		// First let's clear some variables
		$distinct = '';
		$whichauthor = '';
		$whichmimetype = '';
		$where = '';
		$limits = '';
		$join = '';
		$search = '';
		$groupby = '';
		$object_status_join = false;
		$page = 1;

		if ( !isset( $q['cache_results'] ) ) {
			if ( wp_using_ext_object_cache() )
				$q['cache_results'] = false;
			else
				$q['cache_results'] = true;
		}

		if ( !isset( $q['nopaging'] ) ) {
			if ( $q['per_page'] == -1 ) {
				$q['nopaging'] = true;
			} else {
				$q['nopaging'] = false;
			}
		}

		$q['per_page'] = (int) $q['per_page'];

		if ( $q['per_page'] < -1 )
			$q['per_page'] = abs( $q['per_page'] );
		elseif ( $q['per_page'] == 0 )
			$q['per_page'] = 1;

		if ( isset( $q['page'] ) ) {
			$q['page'] = trim( $q['page'], '/' );
			$q['page'] = absint( $q['page'] );
		}

		if ( isset( $q['no_found_rows'] ) )
			$q['no_found_rows'] = (bool) $q['no_found_rows'];
		else
			$q['no_found_rows'] = false;

		switch ( $q['fields'] ) {
			case 'ids':
				$fields = "{$this->table_name}.ID";
				break;
			default:
				$fields = "{$this->table_name}.*";
		}

		// The "m" parameter is meant for months but accepts datetimes of varying specificity
		if ( $q['m'] ) {
			$where .= " AND YEAR({$this->table_name}.$this->date_field)=" . substr($q['m'], 0, 4);
			if ( strlen($q['m']) > 5 ) {
				$where .= " AND MONTH({$this->table_name}.$this->date_field)=" . substr($q['m'], 4, 2);
			}
			if ( strlen($q['m']) > 7 ) {
				$where .= " AND DAYOFMONTH({$this->table_name}.$this->date_field)=" . substr($q['m'], 6, 2);
			}
			if ( strlen($q['m']) > 9 ) {
				$where .= " AND HOUR({$this->table_name}.$this->date_field)=" . substr($q['m'], 8, 2);
			}
			if ( strlen($q['m']) > 11 ) {
				$where .= " AND MINUTE({$this->table_name}.$this->date_field)=" . substr($q['m'], 10, 2);
			}
			if ( strlen($q['m']) > 13 ) {
				$where .= " AND SECOND({$this->table_name}.$this->date_field)=" . substr($q['m'], 12, 2);
			}
		}

		// Handle the other individual date parameters
		$date_parameters = array();

		if ( '' !== $q['hour'] )
			$date_parameters['hour'] = $q['hour'];

		if ( '' !== $q['minute'] )
			$date_parameters['minute'] = $q['minute'];

		if ( '' !== $q['second'] )
			$date_parameters['second'] = $q['second'];

		if ( $q['year'] )
			$date_parameters['year'] = $q['year'];

		if ( $q['monthnum'] )
			$date_parameters['monthnum'] = $q['monthnum'];

		if ( $q['w'] )
			$date_parameters['week'] = $q['w'];

		if ( $q['day'] )
			$date_parameters['day'] = $q['day'];

		if ( $date_parameters ) {
			$date_query = new MS_Helper_Database_Query_Base_Date( array( $date_parameters ), $this->date_field );
			$where 		.= $date_query->get_sql();
		}
		unset( $date_parameters, $date_query );

		// Handle complex date queries
		if ( ! empty( $q['date_query'] ) ) {
			$this->date_query 	= new MS_Helper_Database_Query_Base_Date( $q['date_query'], $this->date_field );
			$where 				.= $this->date_query->get_sql();
		}

		// If a post number is specified, load that object
		if ( $q['p'] ) {
			$where .= " AND {$this->table_name}.ID = " . $q['p'];
		} elseif ( $q['object__in'] ) {
			$object__in = implode(',', array_map( 'absint', $q['object__in'] ));
			$where .= " AND {$this->table_name}.ID IN ($object__in)";
		} elseif ( $q['object__not_in'] ) {
			$object__not_in = implode(',',  array_map( 'absint', $q['object__not_in'] ));
			$where .= " AND {$this->table_name}.ID NOT IN ($object__not_in)";
		}

		$where = $this->custom_where_clause( $where, $q , $wpdb );

		// If a search pattern is specified, load the posts that match.
		if ( strlen( $q['s'] ) ) {
			$search = $this->parse_search( $q );
		}

		if ( isset( $this->meta_query ) && ! empty( $this->meta_query->queries ) ) {
			$clauses = $this->meta_query->get_sql( $this->table_name, 'ID', $this );
			$join   .= $clauses['join'];
			$where  .= $clauses['where'];
		}

		$rand = ( isset( $q['orderby'] ) && 'rand' === $q['orderby'] );
		if ( ! isset( $q['order'] ) ) {
			$q['order'] = $rand ? '' : 'DESC';
		} else {
			$q['order'] = $rand ? '' : $this->parse_order( $q['order'] );
		}

		// Order by.
		if ( empty( $q['orderby'] ) ) {
			/*
			 * Boolean false or empty array blanks out ORDER BY,
			 * while leaving the value unset or otherwise empty sets the default.
			 */
			if ( isset( $q['orderby'] ) && ( is_array( $q['orderby'] ) || false === $q['orderby'] ) ) {
				$orderby = '';
			} else {
				$orderby = "{$this->table_name}.$this->date_field " . $q['order'];
			}
		} elseif ( 'none' == $q['orderby'] ) {
			$orderby = '';
		} elseif ( $q['orderby'] == 'object__in' && ! empty( $object__in ) ) {
			$orderby = "FIELD( {$this->table_name}.ID, $object__in )";
		} else {
			$orderby_array = array();
			if ( is_array( $q['orderby'] ) ) {
				foreach ( $q['orderby'] as $_orderby => $order ) {
					$orderby = addslashes_gpc( urldecode( $_orderby ) );
					$parsed  = $this->parse_orderby( $orderby );

					if ( ! $parsed ) {
						continue;
					}

					$orderby_array[] = $parsed . ' ' . $this->parse_order( $order );
				}
				$orderby = implode( ', ', $orderby_array );

			} else {
				$q['orderby'] = urldecode( $q['orderby'] );
				$q['orderby'] = addslashes_gpc( $q['orderby'] );

				foreach ( explode( ' ', $q['orderby'] ) as $i => $orderby ) {
					$parsed = $this->parse_orderby( $orderby );
					// Only allow certain values for safety.
					if ( ! $parsed ) {
						continue;
					}

					$orderby_array[] = $parsed;
				}
				$orderby = implode( ' ' . $q['order'] . ', ', $orderby_array );

				if ( empty( $orderby ) ) {
					$orderby = "{$this->table_name}.$this->date_field " . $q['order'];
				} elseif ( ! empty( $q['order'] ) ) {
					$orderby .= " {$q['order']}";
				}
			}
		}

		// Order search results by relevance only when another "orderby" is not specified in the query.
		if ( ! empty( $q['s'] ) ) {
			$search_orderby = '';
			if ( ! empty( $q['search_orderby_name'] ) && ( empty( $q['orderby'] ) && ! $this->is_feed ) || ( isset( $q['orderby'] ) && 'relevance' === $q['orderby'] ) )
				$search_orderby = $this->parse_search_order( $q );

			if ( $search_orderby )
				$orderby = $orderby ? $search_orderby . ', ' . $orderby : $search_orderby;
		}


		// Paging
		if ( empty( $q['nopaging'] ) && !$this->is_singular ) {
			$page = absint($q['paged']);
			if ( !$page )
				$page = 1;

			// If 'offset' is provided, it takes precedence over 'paged'.
			if ( isset( $q['offset'] ) && is_numeric( $q['offset'] ) ) {
				$q['offset'] = absint( $q['offset'] );
				$pgstrt = $q['offset'] . ', ';
			} else {
				$pgstrt = absint( ( $page - 1 ) * $q['per_page'] ) . ', ';
			}
			$limits = 'LIMIT ' . $pgstrt . $q['per_page'];
		}

		if ( ! empty($groupby) )
			$groupby = 'GROUP BY ' . $groupby;
		if ( !empty( $orderby ) )
			$orderby = 'ORDER BY ' . $orderby;

		$found_rows = '';
		if ( !$q['no_found_rows'] && !empty( $limits ) )
			$found_rows = 'SQL_CALC_FOUND_ROWS';

		$this->request = $old_request = "SELECT $found_rows $distinct $fields FROM {$this->table_name} $join WHERE 1=1 $where $groupby $orderby $limits";

		if ( 'ids' == $q['fields'] ) {
			if ( null === $this->objects ) {
				$this->objects 	= $wpdb->get_col( $this->request );
			}

			$this->objects 		= array_map( 'intval', $this->objects );
			$this->post_count 	= count( $this->objects );
			$this->set_found_objects( $q, $limits );

			return $this->objects;
		}

		$this->objects = $wpdb->get_results( $this->request );
		$this->set_found_objects( $q, $limits );

		// Ensure that any objects added/modified via one of the filters above are
		// of the type WP_Post and are filtered.
		if ( $this->objects ) {
			$this->object_count = count( $this->objects );
			$this->object 		= reset( $this->objects );
		} else {
			$this->object_count = 0;
			$this->objects 		= array();
		}
		return $this->objects;
	}


	/**
	 * If the passed orderby value is allowed, convert the alias to a
	 * properly-prefixed orderby value.
	 *
	 * @since 1.2
	 * @access protected
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @return string|false Table-prefixed value to used in the ORDER clause. False otherwise.
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb;

		// Used to filter values.
		$allowed_keys 		= $this->default_query_keys;
		$primary_meta_key 	= '';
		$primary_meta_query = false;
		if ( isset ( $this->meta_query ) ) {
			$meta_clauses = $this->meta_query->get_clauses();
			if ( ! empty( $meta_clauses ) ) {
				$primary_meta_query = reset( $meta_clauses );

				if ( ! empty( $primary_meta_query['key'] ) ) {
					$primary_meta_key 	= $primary_meta_query['key'];
					$allowed_keys[] 	= $primary_meta_key;
				}

				$allowed_keys[] = 'meta_value';
				$allowed_keys[] = 'meta_value_num';
				$allowed_keys   = array_merge( $allowed_keys, array_keys( $meta_clauses ) );
			}
		}

		// If RAND() contains a seed value, sanitize and add to allowed keys.
		$rand_with_seed = false;
		if ( preg_match( '/RAND\(([0-9]+)\)/i', $orderby, $matches ) ) {
			$orderby 		= sprintf( 'RAND(%s)', intval( $matches[1] ) );
			$allowed_keys[] = $orderby;
			$rand_with_seed = true;
		}

		if ( ! in_array( $orderby, $allowed_keys, true ) ) {
			return false;
		}

		switch ( $orderby ) {
			case 'ID':
				$orderby_clause = "{$this->table_name}.{$orderby}";
				break;
			case 'rand':
				$orderby_clause = 'RAND()';
				break;
			case $primary_meta_key:
			case 'meta_value':
				if ( isset ( $this->meta_query ) ) {
					if ( ! empty( $primary_meta_query['type'] ) ) {
						$orderby_clause = "CAST({$primary_meta_query['alias']}.meta_value AS {$primary_meta_query['cast']})";
					} else {
						$orderby_clause = "{$primary_meta_query['alias']}.meta_value";
					}
				}
				break;
			case 'meta_value_num':
				if ( isset ( $this->meta_query ) ) {
					$orderby_clause = "{$primary_meta_query['alias']}.meta_value+0";
				}
				break;
			default:
				if ( isset ( $this->meta_query ) && array_key_exists( $orderby, $meta_clauses ) ) {
					// $orderby corresponds to a meta_query clause.
					$meta_clause 	= $meta_clauses[ $orderby ];
					$orderby_clause = "CAST({$meta_clause['alias']}.meta_value AS {$meta_clause['cast']})";
				} elseif ( $rand_with_seed ) {
					$orderby_clause = $orderby;
				} else {
					// Default: order by post field.
					$orderby_clause = "{$this->table_name}." . sanitize_key( $orderby );
				}

				break;
		}

		return $orderby_clause;
	}

	/**
	 * Set up the amount of found objects and the number of pages (if limit clause was used)
	 * for the current query.
	 *
	 * @since 1.2
	 * @access private
	 *
	 * @param array  $q      Query variables.
	 * @param string $limits LIMIT clauses of the query.
	 */
	private function set_found_objects( $q, $limits ) {
		global $wpdb;
		// Bail if objects is an empty array. Continue if objects is an empty string,
		// null, or false to accommodate caching plugins that fill objects later.
		if ( $q['no_found_rows'] || ( is_array( $this->objects ) && ! $this->objects ) )
			return;

		if ( ! empty( $limits ) ) {
			$this->found_objects = $wpdb->get_var( apply_filters_ref_array( 'membership_found_objects_query', array( 'SELECT FOUND_ROWS()', &$this ) ) );
		} else {
			$this->found_objects = count( $this->objects );
		}


		$this->found_objects = apply_filters_ref_array( 'membership_found_objects', array( $this->found_objects, &$this ) );

		if ( ! empty( $limits ) )
			$this->max_num_pages = ceil( $this->found_objects / $q['per_page'] );
	}

	/**
	 * Set up the next object and iterate current object index.
	 *
	 * @since 1.2
	 * @access public
	 *
	 */
	public function next_object() {

		$this->current_object++;

		$this->object = $this->objects[$this->current_object];
		return $this->object;
	}


	/**
	 * Sets up the WordPress query by parsing query string.
	 *
	 * @since 1.2
	 * @access public
	 *
	 * @param string $query URL query string.
	 * @return array List of posts.
	 */
	public function query( $query ) {
		$this->init();
		$this->query = $this->query_vars = wp_parse_args( $query );
		return $this->get_objects();
	}


	/**
	 * Is the query the main query?
	 *
	 * @since 1.2
	 *
	 * @global WP_Query $wp_query Global WP_Query instance.
	 *
	 * @return bool
	 */
	public function is_main_query() {
		global $wp_the_query;
		return $wp_the_query === $this;
	}
}

?>