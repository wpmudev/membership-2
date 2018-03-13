<?php
/**
 * The Debug component.
 * Access via function `lib3()->debug`.
 *
 * @since 1.1.4
 */
class TheLib_Debug extends TheLib  {

	/**
	 * If set to true or false it will override the WP_DEBUG value
	 * If set to null the WP_DEBUG and WDEV_DEBUG values are used.
	 *
	 * @since 1.1.4
	 * @internal
	 * @var bool
	 */
	protected $enabled = null;

	/**
	 * If set to true each debug output will contain a stack-trace.
	 * Otherwise only the variable will be dumped.
	 *
	 * @since 1.1.4
	 * @internal
	 * @var bool
	 */
	protected $stacktrace = true;

	/**
	 * Toggles the plain-text / HTML output of the debug.
	 * All Ajax requests will ignore this flag and use plain-text format.
	 *
	 * @since 1.1.4
	 * @internal
	 * @var bool
	 */
	protected $plain_text = false;

	/**
	 * Constructor.
	 * Setup action hooks for debugger.
	 *
	 * @since 2.0.0
	 * @internal
	 */
	public function __construct() {
		remove_all_actions( 'wdev_debug_log' );
		remove_all_actions( 'wdev_debug_log_trace' );
		remove_all_actions( 'wdev_debug_dump' );
		remove_all_actions( 'wdev_debug_trace' );

		add_action(
			'wdev_debug_log',
			array( $this, 'log' ),
			10, 99
		);

		add_action(
			'wdev_debug_log_trace',
			array( $this, 'log_trace' )
		);

		add_action(
			'wdev_debug_dump',
			array( $this, 'dump' ),
			10, 99
		);

		add_action(
			'wdev_debug_trace',
			array( $this, 'trace' )
		);
	}

	/**
	 * Resets all debug-output flags.
	 *
	 * @since  1.1.4
	 * @api
	 */
	public function reset() {
		$this->enabled = null;
		$this->stacktrace = true;
	}

	/**
	 * Force-Enable debugging.
	 *
	 * @since  1.1.4
	 * @api
	 */
	public function enable() {
		$this->enabled = true;
	}

	/**
	 * Force-Disable debugging.
	 *
	 * @since  1.1.4
	 * @api
	 */
	public function disable() {
		$this->enabled = false;
	}

	/**
	 * Returns the debugging status. False means no debug output is made.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = $this->enabled;
		$is_ajax = false;
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { $is_ajax = true; }
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) { $is_ajax = true; }

		if ( null === $enabled ) {
			if ( $is_ajax ) {
				$enabled = WDEV_AJAX_DEBUG;
			} else {
				$enabled = WDEV_DEBUG;
			}
		}

		return $enabled;
	}

	/**
	 * Enable stack-trace.
	 *
	 * @since  1.1.4
	 * @api
	 */
	public function stacktrace_on() {
		$this->stacktrace = true;
	}

	/**
	 * Disable stack-trace.
	 *
	 * @since  1.1.4
	 * @api
	 */
	public function stacktrace_off() {
		$this->stacktrace = false;
	}

	/**
	 * Do not format debug output.
	 *
	 * @since  1.1.4
	 * @api
	 */
	public function format_text() {
		$this->plain_text = true;
	}

	/**
	 * Use HTML to format debug output.
	 *
	 * @since  1.1.4
	 * @api
	 */
	public function format_html() {
		$this->plain_text = false;
	}

	/**
	 * Determines if the debug output should be made in plain text.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @return bool
	 */
	public function is_plain_text() {
		$plain_text = $this->plain_text;

		$is_ajax = false;
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { $is_ajax = true; }
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) { $is_ajax = true; }
		if ( $is_ajax ) { $plain_text = true; }

		return $plain_text;
	}

	/**
	 * Write debug information to error log file.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param mixed <dynamic> Each param will be dumped
	 */
	public function log( $first_arg ) {
		if ( $this->is_enabled() ) {
			$plain_text = $this->plain_text;
			$this->format_text();
			$log_file = WP_CONTENT_DIR . '/lib3.log';
			$time = date( "Y-m-d\tH:i:s\t" );

			foreach ( func_get_args() as $param ) {
				if ( is_scalar( $param ) ) {
					$dump = $param;
				} else {
					$dump = var_export( $param, true );
				}
				error_log( $time . $dump . "\n", 3, $log_file );
			}

			$this->plain_text = $plain_text;
		}
	}

	/**
	 * Write stacktrace information to error log file.
	 *
	 * @since  2.0.0
	 * @api
	 */
	public function log_trace() {
		if ( $this->is_enabled() ) {
			$plain_text = $this->plain_text;
			$this->format_text();
			$log_file = WP_CONTENT_DIR . '/lib3.log';

			// Display the backtrace.
			$trace = $this->trace( false );
			error_log( $trace, 3, $log_file );

			$this->plain_text = $plain_text;
		}
	}

	/**
	 * Adds a log-message to the HTTP response header.
	 * This is very useful to debug Ajax requests or redirects.
	 *
	 * @since  2.0.3
	 * @param  string $message The debug message
	 */
	public function header( $message ) {
		static $Number = 0;
		if ( ! $this->is_enabled() ) { return; }

		$Number += 1;
		if ( headers_sent() ) {
			// HTTP Headers already sent, so add the response as HTML comment.
			$message = str_replace( '-->', '--/>', $message );
			printf( "<!-- Debug-Note[%s]: %s -->\n", $Number, $message );
		} else {
			// No output was sent yet so add the message to the HTTP headers.
			$message = str_replace( array( "\n", "\r" ), ' ', $message );
			header( "X-Debug-Note[$Number]: $message", false );
		}
	}

	/**
	 * Displays a debug message at the current position on the page.
	 *
	 * @since  1.0.14
	 * @api
	 *
	 * @param mixed <dynamic> Each param will be dumped.
	 */
	public function dump( $first_arg ) {
		if ( ! $this->is_enabled() ) { return; }
		$plain_text = $this->is_plain_text();

		$this->add_scripts();

		if ( ! $plain_text ) {
			$block_id = 'wdev-debug-' . md5( rand() );
			$block_label = '';
			if ( is_scalar( $first_arg ) && ! empty( $first_arg ) ) {
				$block_label = ': ' . (string) $first_arg;
			}
			?>
			<div class="wdev-debug">
			<span class="wdev-debug-label" onclick="toggleBlock('<?php echo esc_attr( $block_id ); ?>')">
				DEBUG<?php echo esc_html( $block_label ); ?>
			</span>
			<div class="<?php echo esc_attr( $block_id ); ?>">
			<table cellspacing="0" cellpadding="0" width="100%" border="0" class="wdev-dump">
			<?php
			foreach ( func_get_args() as $param ) {
				$this->_dump_var( $param );
			}
			?>
			</table>
			<?php
		} else {
			foreach ( func_get_args() as $param ) {
				$dump = var_export( $param, true );
				echo "\r\n" . $dump;
			}
		}

		// Display the backtrace.
		if ( $this->stacktrace ) {
			$this->trace();
		}

		if ( ! $plain_text ) {
			echo '</div>';
			echo '<div class="wdev-debug-clear"></div>';
			echo '</div>';
		}
	}

	/**
	 * Output a stack-trace.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param bool $output Optional. If false then the trace will be returned
	 *                     instead of echo'ed. Default: true (echo)
	 * @return string Returns the stack-trace contents.
	 */
	public function trace( $output = true ) {
		if ( ! $this->is_enabled() ) { return; }
		$plain_text = $this->is_plain_text();

		$this->add_scripts();
		$trace_str = '';

		if ( ! $plain_text ) {
			$block_id = 'wdev-debug-' . md5( rand() );
			$trace_str .= sprintf(
				'<span class="wdev-trace-toggle" onclick="toggleBlock(\'%1$s-trace\')">
					<b>Back-Trace</b>
				</span>
				<div class="%1$s-trace" style="display:none">
				<table class="wdev-trace" width="100%%" cellspacing="0" cellpadding="3" border="1">
				',
				esc_attr( $block_id )
			);
		}

		$trace = debug_backtrace();
		$trace_num = count( $trace );
		$line = 0;

		for ( $i = 0; $i < $trace_num; $i += 1 ) {
			$item = $trace[$i];
			$line_item = $item;
			$j = $i;

			while ( empty( $line_item['line'] ) && $j < $trace_num ) {
				$line_item = $trace[$j];
				$j += 1;
			}

			self::$core->array->equip( $line_item, 'file', 'line', 'class', 'type', 'function' );
			self::$core->array->equip( $item, 'args', 'file', 'line', 'class', 'type', 'function' );
			if ( 0 === strpos( $item['class'], 'TheLib_' ) ) { continue; }

			$line += 1;
			$args = '';
			$arg_num = '';
			$dummy = array();

			if ( $i > 0 && is_array( $item['args'] ) && count( $item['args'] ) ) {
				foreach ( $item['args'] as $arg ) {
					if ( is_scalar( $arg ) ) {
						if ( is_bool( $arg ) ) {
							$dummy[] = ( $arg ? 'true' : 'false' );
						} elseif ( is_string( $arg ) ) {
							$dummy[] = '"' . $arg . '"';
						} else {
							$dummy[] = $arg;
						}
					} elseif ( is_array( $arg ) ) {
						$dummy[] = '<i>[Array]</i>';
					} elseif ( is_object( $arg ) ) {
						$dummy[] = '<i>[' . get_class( $arg ) . ']</i>';
					} elseif ( is_null( $arg ) ) {
						$dummy[] = '<i>NULL</i>';
					} else {
						$dummy[] = '<i>[???]</i>';
					}
				}

				$args = implode( '</font></span><span class="trc-param"><font>', $dummy );
				$args = '<span class="trc-param"><font>' . $args . '</font></span>';
			}

			if ( $plain_text ) {
				$file = $line_item['file'];
				if ( strlen( $file ) > 80 ) {
					$file = '...' . substr( $line_item['file'], -77 );
				} else {
					$file = str_pad( $file, 80, ' ', STR_PAD_RIGHT );
				}

				$trace_str .= sprintf(
					"\r\n  %s. \t %s \t by %s",
					str_pad( $line, 2, ' ', STR_PAD_LEFT ),
					$file . ': ' . str_pad( $line_item['line'], 5, ' ', STR_PAD_LEFT ),
					$item['class'] . $item['type'] . $item['function'] . '(' . strip_tags( $args ) . ')'
				);
			} else {
				$trace_str .= sprintf(
					"<tr onclick='_m(this)'><td class='trc-num'>%s</td><td class='trc-loc'>%s</td><td class='trc-arg'>%s</td></tr>\r\n",
					$line,
					$line_item['file'] . ': ' . $line_item['line'],
					$item['class'] . $item['type'] . $item['function'] . '(' . $args . ')'
				);
			}
		}

		if ( $plain_text ) {
			$trace_str .= "\r\n-----\r\n";
		} else {
			$trace_str .= '</table>';
			$trace_str .= '</div>';
		}

		if ( $output ) {
			echo '' . $trace_str;
		}

		return $trace_str;
	}

	/**
	 * Outputs an advanced var dump.
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  any $input The variable/object/value to dump.
	 * @param  int $default_depth Deeper items will be collapsed
	 * @param  int $level Do not change this value!
	 */
	protected function _dump_var( $data, $item_key = null, $default_depth = 3, $level = array( null ), $args = array() ) {
		if ( ! is_string( $data ) && is_callable( $data ) ) {
			$type = 'Callable';
		} else {
			$type = ucfirst( gettype( $data ) );
		}

		if ( empty( $level ) ) { $level = array( null ); }
		$args['containers'] = self::$core->array->get( $args['containers'] );
		$args['collapsed'] = self::$core->array->get( $args['collapsed'] );

		$type_data = null;
		$type_length = null;
		$full_dump = false;

		switch ( $type ) {
			case 'String':
				$type_length = strlen( $data );
				$type_data = '"' . htmlentities( $data ) . '"';
				break;

			case 'Double':
			case 'Float':
				$type = 'Float';
				$type_length = strlen( $data );
				$type_data = htmlentities( $data );
				break;

			case 'Integer':
				$type_length = strlen( $data );
				$type_data = htmlentities( $data );
				break;

			case 'Boolean':
				$type_length = strlen( $data );
				$type_data = $data ? 'TRUE' : 'FALSE';
				break;

			case 'NULL':
				$type_length = 0;
				$type_data = 'NULL';
				break;

			case 'Array':
				$type_length = count( $data );
				break;

			case 'Object':
				$full_dump = true;
				break;
		}

		$type_label = $type . ( $type_length !== null ? '(' . $type_length . ')' : '' );

		if ( in_array( $type, array( 'Object', 'Array' ) ) ) {
			$populated = false;

			$dump_data = (array) $data;
			ksort( $dump_data );

			if ( 'Object' == $type ) {
				$type_label .= ' [' . get_class( $data ) . ']';
			}

			$last_key = end( (array_keys( $dump_data )) );
			reset( $dump_data );

			foreach ( $dump_data as $key => $value ) {
				if ( ! $populated ) {
					$populated = true;

					$id = substr( md5( rand() . ':' . $key . ':' . count( $level ) ), 0, 8 );
					$args['containers'][] = $id;
					$collapse = count( $args['containers'] ) >= $default_depth;
					if ( $collapse ) {
						$args['collapsed'][] = $id;
					}

					$title_args = $args;
					$title_args['toggle'] = $id;

					$this->_dump_line(
						$item_key,
						$type_label,
						'',
						$level,
						$title_args
					);
					unset( $args['protected'] );
					unset( $args['private'] );
				}

				// Tree right before the item-name
				$new_level = $level;

				if ( $last_key == $key ) {
					$new_level[] = false;
					$args['lastkey'] = true;
				} else {
					$new_level[] = true;
					$args['lastkey'] = false;
				}

				$encode_key = json_encode( $key );
				$matches = null;
				if ( 1 === strpos( $encode_key, '\\u0000*\\u0000' ) ) {
					$args['protected'] = true;
					$key = substr( $key, 3 );
				} elseif ( 1 === preg_match( '/\\\\u0000(\w+)\\\\u0000/i', $encode_key, $matches ) ) {
					$args['private'] = true;
					$key = substr( $key, 2 + strlen( $matches[1] ) );
				}

				$this->_dump_var(
					$value,
					$key,
					$default_depth,
					$new_level,
					$args
				);

				unset( $args['protected'] );
				unset( $args['private'] );
			} // end of array/object loop.

			if ( ! $populated ) {
				$this->_dump_line(
					$item_key,
					$type_label,
					'',
					$level,
					$args
				);
			}
		} else {
			$this->_dump_line(
				$item_key,
				$type_label,
				$type_data,
				$level,
				$args
			);
		}
	}

	/**
	 * Outputs a single line of the dump_var output.
	 *
	 * @since  1.1.4
	 * @internal
	 */
	protected function _dump_line( $key, $type, $value, $level, $args = array() ) {
		$type_color = '#999';
		$type_key = strtolower( $type );
		if ( strlen( $type_key ) > 4 ) { $type_key = substr( $type_key, 0, 4 ); }

		$custom_type_colors = array(
			'stri' => 'green',
			'doub' => '#0099c5',
			'floa' => '#0099c5',
			'inte' => 'red',
			'bool' => '#92008d',
			'null' => '#AAA',
		);

		if ( isset( $custom_type_colors[ $type_key ] ) ) {
			$type_color = $custom_type_colors[ $type_key ];
		}

		$collapse = array_intersect( $args['containers'], $args['collapsed'] );
		$args['do_collapse'] = is_array( $collapse ) && count( $collapse ) > 0;
		if ( ! empty( $args['toggle'] ) ) {
			$args['containers'] = array_diff( $args['containers'], array( $args['toggle'] ) );
			$args['collapsed'] = array_diff( $args['collapsed'], array( $args['toggle'] ) );

			$collapse_this = array_intersect( $args['containers'], $args['collapsed'] );
			$args['do_collapse_next'] = $args['do_collapse'];
			$args['do_collapse'] = is_array( $collapse_this ) && count( $collapse_this ) > 0;
		}

		$row_class = '';
		$row_attr = '';
		if ( ! empty( $args['containers'] ) ) {
			$row_class = implode( ' ', $args['containers'] );
		}
		if ( ! empty( $args['do_collapse'] ) ) {
			$row_attr = 'style="display:none;"';
		}
		echo '<tr class="' . $row_class . '"' . $row_attr . '><td>';

		// Property-key, if set.
		if ( $key === null ) {
			// Full Tree-level.
			echo '<span class="dev-tree">';
			for ( $i = 0; $i < count( $level ); $i += 1 ) {
				if ( null === $level[$i] ) { continue; }
				if ( $level[$i] ) { echo '&nbsp;│&nbsp;'; }
				else { echo '&nbsp;&nbsp;&nbsp;'; }
			}
			echo '</span>';
		} else {
			echo '<span class="dev-tree">';
			// Tree-level without last level.
			for ( $i = 0; $i < count( $level ) - 1; $i += 1 ) {
				if ( null === $level[$i] ) { continue; }
				if ( $level[$i] ) { echo '&nbsp;│&nbsp;'; }
				else { echo '&nbsp;&nbsp;&nbsp;'; }
			}

			if ( empty( $args['lastkey'] ) ) {
				echo '&nbsp;├─';
			} else {
				echo '&nbsp;└─';
			}
			echo '</span>';

			$key_style = '';
			if ( ! empty( $args['protected'] ) ) {
				$key_style .= 'color:#900;';
				$prefix = '';
			} elseif ( ! empty( $args['private'] ) ) {
				$key_style .= 'color:#C00;font-style:italic;';
				$prefix = 'PRIVATE ';
			} else {
				$key_style .= 'color:#000;';
				$prefix = '';
			}

			$valid_ids = array( 'ID', 'id' );
			$is_id = in_array( (string) $key, $valid_ids );
			if ( $is_id ) {
				$key_style .= 'background:#FDA;';
			}

			echo '<span class="dev-item dev-item-key" style="' . $key_style . '">[ ' . $prefix . $key . ' ]</span>';
			echo '<span class="dev-item"> => </span>';
		}

		// Data-Type.
		if ( ! empty( $args['toggle'] ) ) {
			echo '<a href="javascript:toggleDisplay(\''. $args['toggle'] . '\',\'' . trim( $row_class . ' ' . $args['toggle'] ) . '\');" class="dev-item dev-toggle-item">';
			echo '<span style="color:#666666">' . $type . '</span>&nbsp;&nbsp;';
			echo '</a>';
		} else {
			echo '<span class="dev-item" style="color:#666666">' . $type . '&nbsp;&nbsp;</span>';
		}

		if ( ! empty( $args['toggle'] ) ) {
			$collapsed = ! empty( $args['do_collapse_next'] );
			$toggle_style = 'display: ' . ( $collapsed ? 'inline' : 'none' );
			echo '<span id="plus' . $args['toggle'] . '" class="plus dev-item" style="' . $toggle_style . '">&nbsp;&#10549;</span>';
		}

		// Value.
		if ( $value !== null ) {
			$value_style = '';
			if ( isset( $args['highlight'] ) ) {
				$value_style = $args['highlight'];
			}
			echo '<span class="dev-item" style="color:' . $type_color . ';' . $value_style . '">' . $value . '</span>';
		}

		echo '</td></tr>';
		echo "\r\n";
	}

	/**
	 * Outputs the CSS and JS scripts required to display the debug dump/trace.
	 *
	 * @since 2.0.0
	 * @internal
	 */
	protected function add_scripts() {
		if ( $this->is_plain_text() ) { return; }
		if ( defined( '__DEBUG_SCRIPT' ) ) { return; }
		define( '__DEBUG_SCRIPT', true );

		if ( ! headers_sent() ) {
			header( 'Content-type: text/html; charset=utf-8' );
		}

		?>
		<style>
		.wdev-debug {
			clear: both;
			border: 1px solid #C00;
			background: rgba(255, 200, 200, 1);
			padding: 10px;
			margin: 10px;
			position: relative;
			z-index: 99999;
			box-shadow: 0 1px 5px rgba(0,0,0,0.3);
			font-size: 12px;
			font-family: sans-serif;
			font-weight: 200;
			line-height: 1;
		}
		.wdev-debug .dev-tree {
			color: #000;
			opacity: .2;
			font-family: monospace;
			font-size: 19px;
			line-height: 16px;
			float: left;
		}
		.wdev-debug .dev-item {
			float: left;
			line-height: 16px;
			white-space: pre;
		}
		.wdev-debug .dev-toggle-item {
			text-decoration: none;
			background: rgba(255,255,255,0.2);
			display: inline-block;
		}
		.wdev-debug .wdev-dump {
			margin: 0;
			border-collapse: collapse;
			padding: 0;
			border: 0;
		}
		.wdev-debug .wdev-trace-toggle {
			display: block;
			margin: 10px 0 0 0;
		}
		.wdev-debug .wdev-dump td {
			font-size: 12px;
			line-height: 1;
			font-family: sans-serif;
			font-weight: 200;
			background: transparent;
			cursor: default;
			padding: 0;
			border: 0;
		}
		.wdev-debug .wdev-dump tr:hover td {
			background-color: #FFF;
			background-color: rgba(255,255,255,0.3);
		}
		.wdev-debug-clear {
			clear: both;
			display: table;
			padding: 0;
			margin: 0;
		}
		.wdev-debug-label {
			font-size: 11px;
			float: right;
			margin: -10px;
			color: #FFF;
			background-color: #D88;
			padding: 2px 8px;
			cursor: pointer;
			max-width: 50%;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.wdev-debug-label:hover {
			background-color: #E66;
		}
		.wdev-debug pre {
			font-size: 12px !important;
			margin: 1px 0 !important;
			background: rgba(255, 200, 200, 0.8);
		}
		.wdev-trace td {
			padding: 1px 2px !important;
			font-size: 12px;
			vertical-align: top;
		}
		.wdev-trace {
			margin: 4px 0 0 0;
			background: #EBB;
			border-collapse: collapse;
		}
		.wdev-trace tr.mark td {
			background: #EC9;
		}
		.wdev-trace tr td {
			cursor: default;
		}
		.wdev-trace-toggle {
			cursor: pointer;
		}
		.wdev-debug .trc-num {
			width: 40px;
		}
		.wdev-debug .trc-loc {
			width: 60%;
		}
		.wdev-debug .trc-arg {
			width: 40%;
			font-size: 11px;
			white-space: nowrap;
		}
		.wdev-debug .trc-param {
			padding: 0 3px;
			display: block;
			margin: 1px 0 1px 8px;
		}
		.wdev-debug .trc-param font {
			background: rgba( 0,0,0,0.05 );
		}
		.wdev-debug .trc-param:after {
			content: ',';
		}
		.wdev-debug .trc-param:last-child:after {
			content: '';
		}
		</style>
		<script>
			// Toggle whole block (debug/trace)
			function toggleBlock( clsname ) {
				var wrap = document.getElementsByClassName( clsname );

				for ( var i = 0; i < wrap.length; i += 1 ) {
					var state = (wrap[i].style.display == 'none' ? 'block' : 'none');
					wrap[i].style.display = state;
				}
			}
			// Mark a table row
			function _m( row ) {
				row.classList.toggle( 'mark' );
			}
			// Toggle a single debug-output-level
			function toggleDisplay( clsname, full_class ) {
				var elements = document.getElementsByClassName( clsname ),
					plus = document.getElementById( "plus" + clsname ),
					plus_state = (plus.style.display == 'none' ? 'inline' : 'none'),
					el_state = (plus_state == 'none' ? 'table-row' : 'none' ),
					sub_id = '',
					sub_state = el_state;

				plus.style.display = plus_state;

				for ( var i = 0; i < elements.length; i += 1 ) {
					var sub_plus = elements[i].getElementsByClassName( 'plus' );

					if ( elements[i].className == full_class ) {
						if ( sub_plus.length ) { sub_plus[0].style.display = 'inline'; }
						elements[i].style.display = el_state;
					} else {
						if ( sub_plus.length ) { sub_plus[0].style.display = 'inline'; }
						elements[i].style.display = 'none';
					}
				}
			}
			</script>
		<?php
	}

	/**
	 * Returns an HTML element that displays a colored label. By default the
	 * label is a random/unique MD5 hash.
	 * This marker is intended for debugging to identify changes in objects
	 * that are loaded via ajax.
	 *
	 * @since  2.0.1
	 * @api
	 *
	 * @param  string $label Optional. The label to display. Default is a
	 *         random MD5 string.
	 * @param  array $styles Optional. Array of CSS styles to apply.
	 * @return object {
	 *         Marker details
	 *
	 *         $html
	 *         $hash
	 *         $text
	 *         $color
	 * }
	 */
	public function marker_html( $label = null, $styles = array() ) {
		$hash = md5( rand( 1000, 9999 ) . time() );

		if ( null === $label ) {
			$label = $hash;
		} else {
			$hash = md5( $label );
		}

		$color = substr( $hash, 0, 3 );
		$def_styles = array(
			'background' => '#' . $color,
			'color' => '#fff',
			'width' => '280px',
			'font-size' => '12px',
			'text-transform' => 'uppercase',
			'font-family' => 'monospace',
			'text-align' => 'center',
			'margin' => '0 auto 5px',
			'border-radius' => '3px',
			'padding' => '4px',
			'text-shadow' => '0 0 1px #666',
			'box-shadow' => '0 0 1px #000 inset',
		);
		$styles = wp_parse_args(
			$styles,
			$def_styles
		);

		$style = '';
		foreach ( $styles as $key => $val ) {
			$style .= $key . ':' . $val . ';';
		}

		$marker = sprintf(
			'<div style="%1$s">%2$s</div>',
			esc_attr( $style ),
			$label
		);

		return (object) array(
			'html' => $marker,
			'hash' => $hash,
			'text' => $label,
			'color' => '#' . $color,
		);
	}
}