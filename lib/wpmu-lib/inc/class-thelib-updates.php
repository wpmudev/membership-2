<?php
/**
 * The Queued Update component.
 *
 * This component makes it saver to perform plugin updates via code, e.g. when
 * database keys need to be changed when updating a plugin.
 *
 * First all updates are added to the update-queue and only when all updates are
 * prepared the queue is executed! This decreases the risk of being stuck with
 * an unknown database state when an error happens during the preparation.
 *
 * @since 1.1.4
 * @example inc/class-thelib-updates.php 20 11 Demo workflow.
 */
class TheLib_Updates extends TheLib  {
	/*
	Example:

	// make sure the queue is empty
	lib3()->updates->clear();

	// Enqueue some updates
	lib3()->updates->add( 'update_post_meta', 123, 'key', 'new-value' );
	$the_post = get_post( 123 );
	$the_post['post_type'] = 'new_type';
	lib3()->updates->add( 'update_post', $the_post );

	// Execute the changes
	lib3()->updates->execute();
	*/

	/**
	 * A list of all transaction commands that are queued up already
	 *
	 * @since 1.1.4
	 * @internal
	 *
	 * @type array
	 */
	protected $commands = array();

	/**
	 * Holds the last error message if something goes wrong during execution.
	 *
	 * @since 1.1.4
	 * @internal
	 *
	 * @var Exception|false
	 */
	protected $error = false;

	/**
	 * The plugin name. Used to log data in the uploads directory.
	 * @see write_to_file()
	 *
	 * @since 2.0.0
	 * @internal
	 *
	 * @type string
	 */
	protected $plugin = '';

	/**
	 * Clears all commands from the transaction queue.
	 *
	 * This should always be used before starting an update to guarantee that
	 * we start at a known (= empty) state.
	 *
	 * @since  1.1.4
	 * @api
	 */
	public function clear() {
		$this->commands = array();
		$this->error = false;
	}

	/**
	 * Adds a command to the transaction queue.
	 *
	 * We use this workflow to first collect all update-statements before
	 * the first update is actually written to database.
	 * This way we have opportunity to parse all available data and not end
	 * with inconsistent data when there is an error somehwere during upgrading.
	 *
	 * @since  1.1.4
	 * @api
	 *
	 * @param callable $command The command to execute.
	 * @param mixed $args Optional. All params that come after $command will be
	 *                    passed as function parameters to that function.
	 */
	public function add( $command ) {
		$this->commands[] = func_get_args();
	}

	/**
	 * Executes each command that is in the transaction queue.
	 *
	 * Each command that was executed without an error will be removed from the
	 * queue. All commands are executed in the same sequence as they were added.
	 *
	 * @since  1.1.4
	 * @api
	 */
	public function execute() {
		$this->error = false;

		foreach ( $this->commands as $key => $transaction ) {
			$done = false;
			$log_line = '';

			if ( count( $transaction ) < 1 ) {
				$done = false;
			} elseif ( ! is_callable( $transaction[0] ) ) {
				$done = false;
			} else {
				$func = array_shift( $transaction );

				if ( is_array( $func ) ) {
					if ( is_object( $func[0] ) ) {
						$log_line = get_class( $func[0] ) . '->';
					} elseif ( is_scalar( $func[0] ) ) {
						$log_line = $func[0] . '::';
					}
					if ( is_scalar( $func[1] ) ) {
						$log_line .= $func[1];
					}
				} else {
					$log_line = $func;
				}
				$log_line .= '(' . json_encode( $transaction ) . ')';

				try {
					call_user_func_array( $func, $transaction );
					$done = true;
				} catch( Exception $ex ) {
					$this->set_error( $ex, $func );
					return false;
				}
			}

			if ( $done ) {
				$this->log_action( $log_line );
				unset( $this->commands[$key] );
			}
		}

		return true;
	}

	/**
	 * Saves error details if a command fails during execution.
	 * Only one error can be saved.
	 *
	 * @since 1.1.0
	 * @internal
	 *
	 * @param Exception $exception The error that was raised.
	 * @param array $command The command that was executed.
	 */
	protected function set_error( $exception, $command ) {
		$this->error = $exception;
		$this->error->command = $command;
	}

	/**
	 * Returns the last error and resets the error-flag.
	 *
	 * @since  1.1.4
	 * @api
	 *
	 * @return Exception|false The error object
	 */
	public function last_error() {
		$error = $this->error;
		$this->error = false;

		return $error;
	}

	/**
	 * Debug function that will display the contents of the current queue.
	 *
	 * @since  1.1.4
	 */
	public function debug() {
		self::$core->debug->dump( $this->commands );
	}

	/**
	 * Sets the plugin name (i.e. the sub-folder for the write_to_file function)
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param string $name The plugin name, must be a valid folder name.
	 */
	public function plugin( $name ) {
		$this->plugin = sanitize_html_class( $name );
	}

	/**
	 * Writes data to a file in the uploads directory.
	 *
	 * @since  2.0.0
	 * @internal
	 *
	 * @param  string $name Snapshot name, used as file-name.
	 * @param  string $ext File extension.
	 * @param  string $data Data to write into the file.
	 * @param  bool $silent_fail See $this->snapshot()
	 *
	 * @return string|false The filename that was created. False on failure.
	 */
	private function write_to_file( $file, $ext, $data, $silent_fail = false ) {
		// Find the uploads-folder.
		$upload = wp_upload_dir();

		if ( false !== $upload['error'] ) {
			return $this->write_file_failed(
				$silent_fail,
				1,
				$upload['error']
			);
		}

		// Create the Snapshot sub-folder.
		if ( empty( $this->plugin ) ) {
			$this->plugin( 'wpmudev-plugin' );
		}
		$target = trailingslashit( $upload['basedir'] ) . $this->plugin . '/';

		if ( ! is_dir( $target ) ) {
			mkdir( $target );
		}

		if ( ! is_dir( $target ) ) {
			return $this->write_file_failed(
				$silent_fail,
				2,
				'Could not create sub-directory ' . $target
			);
		}

		// Create the empty snapshot file.
		$filename = sanitize_html_class( $file );
		$filename .= '-' . date( 'Ymd-His' );
		$ext = '.' . $ext;
		$i = '';
		$sep = '';

		while ( file_exists( $target . $filename . $sep . $i . $ext ) ) {
			if ( empty( $i ) ) { $i = 1; }
			else { $i += 1; }
			$sep = '-';
		}
		$filename = $target . $filename . $sep . $i . $ext;

		file_put_contents( $filename, '' );

		if ( ! file_exists( $filename ) ) {
			return $this->write_file_failed(
				$silent_fail,
				3,
				'Could not create file ' . $filename
			);
		}

		// Write data to file.
		file_put_contents( $filename, $data );

		return $filename;
	}

	/**
	 * Reads data from a file in the uploads directory.
	 *
	 * @since  2.0.0
	 * @internal
	 *
	 * @param  string $name Snapshot name, used as file-name.
	 * @return string The file contents as string.
	 */
	private function read_from_file( $file ) {
		// Find the uploads-folder.
		$upload = wp_upload_dir();

		if ( false !== $upload['error'] ) {
			return '';
		}

		// Build the full file name.
		if ( empty( $this->plugin ) ) {
			$this->plugin( 'wpmudev-plugin' );
		}
		$target = trailingslashit( $upload['basedir'] ) . $this->plugin . '/';

		if ( ! is_dir( $target ) ) {
			return '';
		}

		$filename = $target . $file;

		if ( ! is_file( $filename ) ) {
			return '';
		}

		$data = file_get_contents( $filename );

		return $data;
	}

	/**
	 * Returns a list with all backup files in the plugins upload folder.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  string $ext File extension to include. Empty means all files.
	 *                     Only specify extension, without the dot (e.g. 'txt')
	 * @return array List of filenames (only file name without path)
	 */
	public function list_files( $ext = '' ) {
		$res = array();

		// Find the uploads-folder.
		$upload = wp_upload_dir();

		if ( false !== $upload['error'] ) {
			return $res;
		}

		// Build the full file name.
		if ( empty( $this->plugin ) ) {
			$this->plugin( 'wpmudev-plugin' );
		}
		$target = trailingslashit( $upload['basedir'] ) . $this->plugin . '/';

		if ( ! is_dir( $target ) ) {
			return $res;
		}

		if ( empty( $ext ) ) {
			$ext = '*';
		}

		$pattern = $target . '*.' . $ext;
		$res = glob( $pattern );
		foreach ( $res as $key => $path ) {
			$res[$key] = str_replace( $target, '', $path );
		}

		return $res;
	}

	/**
	 * Saves a snapshot of certain database values to the uploads directory.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  string $name Snapshot name, used as file-name.
	 * @param  array $data_list {
	 *                List of db-items to backup.
	 *
	 *                @options .. array of option keys
	 *                @posts .. array of post_ids
	 * }
	 * @param  bool $silent_fail If set to false then failure will be silent.
	 *                Otherwise the script will wp_die() on failure (default)
	 */
	public function log_action( $data, $silent_fail = false ) {
		static $Logfile = null;

		$data .= "\n-----\n";
		if ( null === $Logfile ) {
			$Logfile = $this->write_to_file( 'update_log', 'log', $data, $silent_fail );
		} else {
			file_put_contents( $Logfile, $data, FILE_APPEND );
		}
	}

	/**
	 * Saves a snapshot of certain database values to the uploads directory.
	 *
	 * CAREFUL! THIS FUNCTION CAN CAUSE MEMORY ISSUES IF THE $data_list IS TOO
	 * LARGE.
	 * FUNCTION IS STILL EXPERIMENTAL.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  string $name Snapshot name, used as file-name.
	 * @param  array $data_list {
	 *                List of db-items to backup.
	 *
	 *                @options .. array of option keys
	 *                @posts .. array of post_ids
	 * }
	 * @param  bool $silent_fail If set to false then failure will be silent.
	 *                Otherwise the script will wp_die() on failure (default)
	 */
	public function snapshot( $name, $data_list, $silent_fail = false ) {
		// Collect data from the DB that was specified by the user.
		$data = $this->snapshot_collect( $data_list );

		/*
		 * Data is serialized using json_encode.
		 * While is method is slightly slowe then phps `serialize` the output
		 * string is about 30% smaller, resulting in smaller backup files...
		 *
		 * http://techblog.procurios.nl/k/618/news/view/34972/14863/cache-a-large-array-json-serialize-or-var_export.html
		 */
		$data = json_encode( $data );

		$this->write_to_file( $name, 'json', $data, $silent_fail );
	}

	/**
	 * Displays an error message (Snapshot failed) and then die()
	 *
	 * @since  2.0.0
	 * @internal
	 *
	 * @param  bool $silent_fail See $this->snapshot()
	 * @param  string $err_code An error-code to display.
	 * @param  string $error The full error message.
	 */
	private function write_file_failed( $silent_fail, $err_code, $error = '' ) {
		if ( $silent_fail ) { return false; }

		if ( empty( $this->plugin ) ) {
			$this->plugin( 'wpmudev-plugin' );
		}

		$msg = sprintf(
			'<b>Abborting update of %s!</b> '.
			'Could not create a restore-point [%s]<br />%s',
			ucwords( $this->plugin ),
			$err_code,
			$error
		);

		wp_die( $msg );
	}

	/**
	 * Collects data from the current sites DB and returns a structured object.
	 *
	 * @since  2.0.0
	 * @internal
	 *
	 * @param array $data_list See $this->snapshot()
	 */
	private function snapshot_collect( $data_list ) {
		$dump = (object) array();

		// Options.
		$dump->options = array();
		if ( isset( $data_list->options )
			&& is_array( $data_list->options )
		) {
			foreach ( $data_list->options as $option ) {
				$dump->options[$option] = get_option( $option );
			}
		}

		// Posts and Post-Meta
		$dump->posts = array();
		$dump->postmeta = array();
		if ( isset( $data_list->posts )
			&& is_array( $data_list->posts )
		) {
			foreach ( $data_list->posts as $id ) {
				$post = get_post( $id );
				$meta = get_post_meta( $id );

				// Flatten the meta values.
				foreach ( $meta as $key => $values ) {
					if ( is_array( $values ) && isset( $values[0] ) ) {
						$meta[ $key ] = $values[0];
					}
				}

				// Append the data to the dump.
				if ( ! isset( $dump->posts[$post->post_type] ) ) {
					$dump->posts[$post->post_type] = array();
					$dump->postmeta[$post->post_type] = array();
				}
				$dump->posts[$post->post_type][$post->ID] = $post;
				$dump->postmeta[$post->post_type][$post->ID] = $meta;
			}
		}

		return $dump;
	}

	/**
	 * Restores a saved snapshot.
	 *
	 * We're using a lot of SQL queries here to get as much performance as
	 * possible. Using functions like wp_set_option() takes much longer than
	 * a direct SQL query.
	 *
	 * NOTE THAT THIS FUNCTION IS FOR DEVELOPMENT AND DEBUGGING. IT MIGHT CAUSE
	 * MEMORY ISSUES FOR LARGE SNAPSHOTS OR EVEN BREAK THINGS.
	 * FUNCTION IS STILL EXPERIMENTAL.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  string $snapshot Exact filename of the snapshot, including ext.
	 * @return bool True, if the restore-process was successful
	 */
	public function restore( $snapshot ) {
		global $wpdb;

		// Get the contents of the snapshot file.
		$data = $this->read_from_file( $snapshot );
		if ( empty( $data ) ) {
			return false;
		}

		// Decode the snapshot data to an PHP object.
		$data = json_decode( $data, true );
		if ( empty( $data ) ) {
			return false;
		}

		// The restore-process is handled as execution transaction.
		$this->clear();

		// Options
		if ( ! empty( $data['options'] ) && is_array( $data['options'] ) ) {
			$sql_delete = "DELETE FROM {$wpdb->options} WHERE option_name IN ";
			$sql_idlist = array();
			$sql_insert = "INSERT INTO {$wpdb->options} (option_name, option_value) VALUES ";
			$sql_values = array();

			foreach ( $data['options'] as $key => $value ) {
				$sql_idlist[] = $wpdb->prepare( '%s', $key );
				$sql_values[] = $wpdb->prepare( '(%s,%s)', $key, maybe_serialize( $value ) );
			}

			if ( ! empty( $sql_values ) ) {
				$this->add( $sql_delete . '(' . implode( ',', $sql_idlist ) . ')' );
				$this->add( $sql_insert . implode( ",\n", $sql_values ) );
			}
		}

		// Posts
		if ( ! empty( $data['posts'] ) && is_array( $data['posts'] ) ) {
			foreach ( $data['posts'] as $posttype => $items ) {
				$sql_delete_post = "DELETE FROM {$wpdb->posts} WHERE ID IN ";
				$sql_delete_meta = "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ";
				$sql_idlist = array();
				$sql_insert = "INSERT INTO {$wpdb->posts} (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count) VALUES ";
				$sql_values = array();

				foreach ( $items as $id => $post ) {
					self::$core->array->equip(
						$post,
						'post_author',
						'post_date',
						'post_date_gmt',
						'post_content',
						'post_title',
						'post_excerpt',
						'post_status',
						'comment_status',
						'ping_status',
						'post_password',
						'post_name',
						'to_ping',
						'pinged',
						'post_modified',
						'post_modified_gmt',
						'post_content_filtered',
						'post_parent',
						'guid',
						'menu_order',
						'post_type',
						'post_mime_type',
						'comment_count'
					);
					$sql_idlist[] = $wpdb->prepare( '%s', $id );
					$sql_values[] = $wpdb->prepare(
						'(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)',
						$id,
						$post['post_author'],
						$post['post_date'],
						$post['post_date_gmt'],
						$post['post_content'],
						$post['post_title'],
						$post['post_excerpt'],
						$post['post_status'],
						$post['comment_status'],
						$post['ping_status'],
						$post['post_password'],
						$post['post_name'],
						$post['to_ping'],
						$post['pinged'],
						$post['post_modified'],
						$post['post_modified_gmt'],
						$post['post_content_filtered'],
						$post['post_parent'],
						$post['guid'],
						$post['menu_order'],
						$post['post_type'],
						$post['post_mime_type'],
						$post['comment_count']
					);
				}

				while ( ! empty( $sql_idlist ) ) {
					$values = array();
					for ( $i = 0; $i < 100; $i += 1 ) {
						if ( empty( $sql_idlist ) ) { break; }
						$values[] = array_shift( $sql_idlist );
					}
					$this->add( $sql_delete_post . '(' . implode( ',', $values ) . ')' );
					$this->add( $sql_delete_meta . '(' . implode( ',', $values ) . ')' );
				}
				while ( ! empty( $sql_values ) ) {
					$values = array();
					for ( $i = 0; $i < 100; $i += 1 ) {
						if ( empty( $sql_values ) ) { break; }
						$values[] = array_shift( $sql_values );
					}
					$this->add( $sql_insert . implode( ",\n", $values ) );
				}
			}
		}

		// Postmeta
		if ( ! empty( $data['postmeta'] ) && is_array( $data['postmeta'] ) ) {
			foreach ( $data['postmeta'] as $posttype => $items ) {
				foreach ( $items as $id => $entries ) {
					$sql_meta = "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ";
					$sql_values = array();

					foreach ( $entries as $key => $value ) {
						$sql_values[] = $wpdb->prepare( '(%s,%s,%s)', $id, $key, $value );
					}

					if ( ! empty( $sql_values ) ) {
						$this->add( $sql_meta . implode( ",\n", $sql_values ) );
					}
				}
			}
		}

		// Run all scheduled queries
		foreach ( $this->commands as $key => $params ) {
			if ( ! isset( $params[0] ) ) { continue; }
			$query = $params[0];

			$res = $wpdb->query( $query );
		}

		return true;
	}

}