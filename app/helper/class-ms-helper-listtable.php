<?php
/**
 * Base class for displaying a list of items in an ajaxified HTML table.
 *
 * Modifications to use more than a list table within a page, avoiding element-id collisions.
 *
 * Copied from WordPress 3.8.3 as its recommendation
 * http://codex.wordpress.org/Class_Reference/WP_ListTable
 * This class's access is marked as private. That means it is not intended for use by plugin
 * and theme developers as it is subject to change without warning in any future WordPress release.
 * If you would still like to make use of the class, you should make a copy to use and distribute
 * with your own project, or else use it at your own risk.
 *
 * @since 1.0.0
 *
 */
class MS_Helper_ListTable {

	/**
	 * The default number of items per list page.
	 *
	 * @since 1.1.0
	 * @var   int
	 */
	const DEFAULT_PAGE_SIZE = 20;

	/**
	 * The list table id
	 *
	 * @since 1.0.0
	 * @var array
	 * @access protected
	 */
	protected $id = 'ms_listtable';

	/**
	 * The current list of items
	 *
	 * @since 1.0.0
	 * @var array
	 * @access protected
	 */
	protected $items;

	/**
	 * Various information about the current table
	 *
	 * @since 1.0.0
	 * @var array
	 * @access private
	 */
	private $_args;

	/**
	 * Various information needed for displaying the pagination
	 *
	 * @since 1.0.0
	 * @var array
	 * @access private
	 */
	private $_pagination_args = array();

	/**
	 * The current screen
	 *
	 * @since 1.0.0
	 * @var object
	 * @access protected
	 */
	protected $screen;

	/**
	 * Cached bulk actions
	 *
	 * @since 1.0.0
	 * @var array
	 * @access private
	 */
	private $_actions;

	/**
	 * Cached pagination output
	 *
	 * @since 1.0.0
	 * @var string
	 * @access private
	 */
	private $_pagination;


	/**
	 * If the user did a search, this is the search term
	 *
	 * @var string
	 */
	protected $search_string = '';

	/**
	 * Constructor. The child class should call this constructor from its own constructor
	 *
	 * @param array $args An associative array with information about the current table
	 * @access protected
	 */
	protected function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'plural' => '',
				'singular' => '',
				'ajax' => false,
				'screen' => null,
			)
		);

		$this->screen = convert_to_screen( $args['screen'] );

		add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );

		if ( ! $args['plural'] ) {
			$args['plural'] = $this->screen->base;
		}

		$args['plural'] = sanitize_key( $args['plural'] );
		$args['singular'] = sanitize_key( $args['singular'] );

		$this->_args = $args;

		if ( $args['ajax'] ) {
			// wp_enqueue_script( 'list-table' );
			add_action( 'admin_footer', array( $this, '_js_vars' ) );
		}
	}

	/**
	 * Checks the current user's permissions
	 * @uses wp_die()
	 *
	 * @since 1.0.0
	 * @access public
	 * @abstract
	 */
	public function ajax_user_can() {
		die( 'function WP_ListTable::ajax_user_can() must be over-ridden in a sub-class.' );
	}

	/**
	 * Prepares the list of items for displaying.
	 * @uses WP_ListTable::set_pagination_args()
	 *
	 * @since 1.0.0
	 * @access public
	 * @abstract
	 */
	public function prepare_items() {
		die( 'function WP_ListTable::prepare_items() must be over-ridden in a sub-class.' );
	}

	/**
	 * An internal method that sets all the necessary pagination arguments
	 *
	 * @param array $args An associative array with information about the pagination
	 * @access protected
	 */
	protected function set_pagination_args( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'total_items' => 0,
				'total_pages' => 0,
				'per_page' => 0,
			)
		);

		if ( ! $args['total_pages'] && $args['per_page'] > 0 ) {
			$args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );
		} else {
			$args['total_pages'] = 1;
		}

		// redirect if page number is invalid and headers are not already sent
		if ( ! headers_sent() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) && $args['total_pages'] > 0 && $this->get_pagenum() > $args['total_pages'] ) {
			wp_redirect( add_query_arg( 'paged', $args['total_pages'] ) );
			exit;
		}

		$this->_pagination_args = $args;
	}

	/**
	 * Access the pagination args
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $key
	 * @return array
	 */
	public function get_pagination_arg( $key ) {
		if ( 'page' == $key ) {
			return $this->get_pagenum();
		}

		if ( isset( $this->_pagination_args[$key] ) ) {
			return $this->_pagination_args[$key];
		}
	}

	/**
	 * Whether the table has items to display or not
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return bool
	 */
	public function has_items() {
		return ! empty( $this->items );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function no_items() {
		if ( $this->is_search() ) {
			_e( 'No items found.' );
		} else {
			_e( 'No items available.' );
		}
	}

	/**
	 * Display the search box.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $text The search button text
	 * @param string $input_id The search input id
	 */
	public function search_box( $text = null, $input_id = 'search' ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		if ( $this->need_pagination() || $this->is_search() ) {
			if ( empty( $text ) ) {
				$text = __( 'Search', MS_TEXT_DOMAIN );
			} else {
				$text = sprintf(
					__( 'Search %1$s', MS_TEXT_DOMAIN ),
					$text
				);
			}

			$input_id = $input_id . '-search-input';
			$fields = array(
				'orderby',
				'order',
				'post_mime_type',
				'detached',
			);

			if ( $this->is_search() ) {
				?>
				<span class="ms-search-info">
					<?php
					printf(
						__( 'Search results for &#8220;%s&#8221;' ),
						sprintf(
							'<span class="ms-search-term" title="%1$s">%2$s</span>',
							esc_attr( $this->search_string ),
							$this->display_search()
						)
					);
					printf(
						' <a href="%1$s" title="%3$s" class="ms-clear-search">%2$s</a>',
						lib2()->net->current_url(),
						'<span class="dashicons dashicons-dismiss"></span>',
						__( 'Clear search results', MS_TEXT_DOMAIN )
					);
					?>
				</span>
				<?php
			}
			?>
			<form class="search-box" action="" method="post">
				<?php
				foreach ( $fields as $field ) {
					if ( ! empty( $_REQUEST[$field] ) ) {
						$value = $_REQUEST[$field];
					} else {
						$value = '';
					}

					printf(
						'<input type="hidden" name="%1$s" value="%2$s" />',
						esc_attr( $field ),
						esc_attr( $value )
					);
				}

				?>
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ) ?>">
					<?php echo esc_html( $text ); ?>:
				</label>
				<?php do_action( 'ms_helper_listtable_searchbox_start', $this ); ?>
				<input
					type="search"
					id="<?php echo esc_attr( $input_id ) ?>"
					name="s"
					value="<?php echo esc_attr( _admin_search_query() ); ?>"
					/>
				<?php submit_button( $text, 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
			</form>
			<?php
		}
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_views() {
		return array();
	}

	/**
	 * Displays text or additional filters above the list-table
	 *
	 * @since  1.1.0
	 */
	protected function list_head() {
		// Child classes can overwrite this to output a description or filters...
	}

	/**
	 * Display the list of views available on this table.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function views() {
		$this->list_head();

		if ( ! $this->has_items() && ! $this->is_search() && ! $this->is_view() ) {
			return '';
		}

		$views = $this->get_views();

		/**
		 * Filter the list of available list table views.
		 *
		 * The dynamic portion of the hook name, $this->screen->id, refers
		 * to the ID of the current screen, usually a string.
		 *
		 * @since 1.0.0
		 *
		 * @param array $views An array of available list table views.
		 */
		$views = apply_filters( "views_{$this->screen->id}", $views );

		if ( empty( $views ) ) {
			return;
		}

		echo '<ul class="subsubsub">';
		$this->display_filter_links( $views );
		echo '</ul>';
	}

	/**
	 * Outputs a list of filter-links. This is used to display the views above
	 * the list but can also be used in other parts of the table.
	 *
	 * @since  1.1.0
	 * @param  array $links {
	 *     The list of links to display
	 *
	 *     string <array-key> .. Class of the link.
	 *     string $label .. Link title
	 *     string $url .. Link URL
	 *     int    $count .. Optional.
	 *     bool   $current .. Optional.
	 *     bool   $separator .. Optional. If false then no ' | ' will be added.
	 *
	 */
	protected function display_filter_links( array $links ) {
		end( $links );
		$last_class = key( $links );
		reset( $links );

		foreach ( $links as $class => $data ) {
			lib2()->array->equip( $data, 'label', 'url' );

			$sep = '|';
			if ( $last_class === $class ) { $sep = ''; }
			if ( isset( $data['separator'] ) && false === $data['separator'] ) { $sep = ''; }

			$count = (empty( $data['count'] ) ? '' : '(' . $data['count'] . ')');
			if ( ! isset( $data['url'] ) ) { $data['url'] = ''; }
			if ( ! isset( $data['label'] ) ) { $data['label'] = ''; }

			if ( empty( $data['url'] ) ) {
				printf(
					'<li class="%1$s"><span class="group-label">%2$s</span></li>',
					esc_attr( $class ),
					$data['label'],
					esc_html( $sep )
				);
			} else {
				if ( isset( $data['current'] ) ) {
					$is_current = $data['current'];
				} else {
					$is_current = MS_Helper_Utility::is_current_url( $data['url'] );
				}

				printf(
					'<li class="%1$s"><a href="%2$s" class="%6$s">%3$s <span class="count">%4$s</span></a> %5$s</li>',
					esc_attr( $class ),
					$data['url'],
					$data['label'],
					$count,
					esc_html( $sep ),
					( $is_current ? 'current' : '' )
				);
			}
		}
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array();
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param  bool $echo Output or return the HTML code? Default is output.
	 */
	public function bulk_actions( $echo = true ) {
		if ( ! $this->is_search() && ! $this->has_items() ) {
			return '';
		}

		if ( is_null( $this->_actions ) ) {
			$no_new_actions = $this->_actions = $this->get_bulk_actions();
			/**
			 * Filter the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, $this->screen->id, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 1.0.0
			 *
			 * @param array $actions An array of the available bulk actions.
			 */
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
			$this->_actions = MS_Helper_Utility::array_intersect_assoc_deep( $this->_actions, $no_new_actions );
			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return '';
		}

		if ( ! $echo ) { ob_start(); }

		/**
		 * Allow other files to add additional code before the bulk actions.
		 *
		 * Note that this action is only triggered when bulk actions are
		 * actually displayed.
		 *
		 * @since 1.1.0
		 */
		do_action( 'ms_listtable_before_bulk_actions', $this );

		printf( '<select name="action%s">', esc_attr( $two ) );
		printf( '<option value="-1" selected="selected">%s</option>', __( 'Bulk Actions' ) );

		foreach ( $this->_actions as $name => $title ) {
			if ( is_array( $title ) ) {
				printf( '<optgroup label="%s">', esc_attr( $name ) );

				foreach ( $title as $value => $label ){
					printf(
						'<option value="%s">%s</option>',
						esc_attr( $value ),
						esc_attr( $label )
					);
				}
				echo '</optgroup>';
			} else {
				printf(
					'<option value="%s">%s</option>',
					esc_attr( $name ),
					esc_attr( $title )
				);
			}
		}

		echo '</select>';

		submit_button(
			__( 'Apply' ),
			'action',
			false,
			false,
			array( 'id' => 'doaction' . esc_attr( $two ) )
		);

		if ( ! $echo ) { return ob_get_clean(); }
	}

	/**
	 * Get the current action selected from the bulk actions dropdown.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string|bool The action name or False if no action was selected
	 */
	public function current_action() {
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
			return $_REQUEST['action'];
		}

		if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
			return $_REQUEST['action2'];
		}

		return false;
	}

	/**
	 * Generate row actions div
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param array $actions The list of actions
	 * @param bool $always_visible Whether the actions should be always visible
	 * @return string
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i = 0;

		if ( ! $action_count ) {
			return '';
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			$sep = ( $i == $action_count ? '' : ' | ' );
			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		return $out;
	}

	/**
	 * Display a monthly dropdown for filtering items
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function months_dropdown( $post_type ) {
		global $wpdb, $wp_locale;

		$months = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
				FROM $wpdb->posts
				WHERE post_type = %s
				ORDER BY post_date DESC
				",
				$post_type
			)
		);

		/**
		 * Filter the 'Months' drop-down results.
		 *
		 * @since 3.7.0
		 *
		 * @param object $months    The months drop-down query results.
		 * @param string $post_type The post type.
		 */
		$months = apply_filters( 'months_dropdown_results', $months, $post_type );

		$month_count = count( $months );

		if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
		<select name='m'>
			<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates' ); ?></option>
		<?php
		foreach ( $months as $arc_row ) {
			if ( 0 == $arc_row->year ) {
				continue;
			}

			$month = zeroise( $arc_row->month, 2 );
			$year = $arc_row->year;

			printf(
				'<option %s value="%s">%s</option>',
				selected( $m, $year . $month, false ),
				esc_attr( $arc_row->year . $month ),
				/* translators: 1: month name, 2: 4-digit year */
				sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
			);
		}
		?>
		</select>
		<?php
	}

	/**
	 * Display a view switcher
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function view_switcher( $current_mode ) {
		$modes = array(
			'list'    => __( 'List View' ),
			'excerpt' => __( 'Excerpt View' ),
		);

		?>
		<input type="hidden" name="mode" value="<?php echo esc_attr( $current_mode ); ?>" />
		<div class="view-switch">
		<?php
			foreach ( $modes as $mode => $title ) {
				$class = ( $current_mode == $mode ) ? 'class="current"' : '';
				echo "<a href='" . esc_url( add_query_arg( 'mode', $mode, $_SERVER['REQUEST_URI'] ) ) . "' $class><img id='view-switch-$mode' src='" . esc_url( includes_url( 'images/blank.gif' ) ) . "' width='20' height='20' title='$title' alt='$title' /></a>\n";
			}
		?>
		</div>
	<?php
	}

	/**
	 * Display a comment count bubble
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int $post_id
	 * @param int $pending_comments
	 */
	protected function comments_bubble( $post_id, $pending_comments ) {
		$pending_phrase = sprintf( __( '%s pending' ), number_format( $pending_comments ) );

		if ( $pending_comments ) {
			echo '<strong>';
		}

		printf(
			'<a href="%1$s" title="%2$s" class="post-com-count"><span class="comment-count">%3$s</span></a>',
			esc_url( add_query_arg( 'p', $post_id, admin_url( 'edit-comments.php' ) ) ),
			esc_attr( $pending_phrase ),
			number_format_i18n( get_comments_number() )
		);

		if ( $pending_comments ) {
			echo '</strong>';
		}
	}

	/**
	 * Get the current page number
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return int
	 */
	protected function get_pagenum() {
		lib2()->array->equip_request( 'paged' );
		$pagenum = absint( $_REQUEST['paged'] );

		if ( isset( $this->_pagination_args['total_pages'] )
			&& $pagenum > $this->_pagination_args['total_pages']
		) {
			$pagenum = $this->_pagination_args['total_pages'];
		}

		return max( 1, $pagenum );
	}

	/**
	 * Get number of items to display on a single page
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return int
	 */
	protected function get_items_per_page( $option, $default_value = null ) {
		$per_page = (int) get_user_option( $option );

		if ( empty( $per_page ) || $per_page < 1 ) {
			if ( is_numeric( $default_value ) ) {
				$per_page = $default_value;
			} else {
				$per_page = self::DEFAULT_PAGE_SIZE;
			}
		}

		/**
		 * Filter the number of items to be displayed on each page of the list table.
		 *
		 * The dynamic hook name, $option, refers to the per page option depending
		 * on the type of list table in use. Possible values may include:
		 * 'edit_comments_per_page', 'sites_network_per_page', 'site_themes_network_per_page',
		 * 'themes_netework_per_page', 'users_network_per_page', 'edit_{$post_type}', etc.
		 *
		 * @since 1.0.0
		 *
		 * @param int $per_page Number of items to be displayed. Default 20.
		 */
		return (int) apply_filters( $option, $per_page, $default_value );
	}

	/**
	 * Checks if pagination is needed for the current table.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True if the table has more than 1 page.
	 */
	public function need_pagination() {
		if ( ! isset( $this->_pagination_args['total_pages'] ) ) {
			$this->_pagination_args['total_pages'] = 1;
		}

		$total = (int) $this->_pagination_args['total_pages'];
		return $total >= 2;
	}

	/**
	 * Checks if the current list displays search results.
	 *
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public function is_search() {
		return ! empty( $this->search_string );
	}

	/**
	 * Return true if the current list is a view except "all"
	 *
	 * Override this in the specific class!
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	public function is_view() {
		return false;
	}

	/**
	 * Returns the current search-string for display.
	 *
	 * @since  1.1.0
	 * @return string
	 */
	public function display_search() {
		$term = wp_unslash( $this->search_string );
		$ext = '';
		$max_len = 30;

		if ( strlen( $term ) > $max_len ) {
			$term = substr( $term, 0, $max_len );
			$ext = '&hellip;';
		}

		return htmlspecialchars( $term ) . $ext;
	}

	/**
	 * Display the pagination.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param  string $which Either 'top' or 'bottom'
	 * @param  bool $echo Output or return the HTML code? Default is output.
	 */
	protected function pagination( $which, $echo = true ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}
		if ( ! $this->need_pagination() && ! $this->is_search() && ! $this->has_items() ) {
			return;
		}

		if ( $this->is_search()
			|| empty( $this->_pagination_args['total_items'] )
		) {
			$this->_pagination_args['total_items'] = count( $this->items );

			if ( empty( $this->_pagination_args['per_page'] ) ) {
				$this->_pagination_args['total_pages'] = 1;
			} else {
				$this->_pagination_args['total_pages'] = ceil(
					$this->_pagination_args['total_items'] /
					$this->_pagination_args['per_page']
				);
			}
		}

		extract( $this->_pagination_args, EXTR_SKIP );

		$output = '<span class="displaying-num">' .
			sprintf(
				_n( '1 item', '%s items', $total_items ),
				number_format_i18n( $total_items )
			) .
			'</span>';

		if ( $this->need_pagination() && ! $this->is_search() ) {
			$current = $this->get_pagenum();

			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

			$current_url = remove_query_arg(
				array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url
			);

			$page_links = array();

			$disable_first = $disable_last = '';
			if ( $current == 1 ) {
				$disable_first = ' disabled';
			}
			if ( $current == $total_pages ) {
				$disable_last = ' disabled';
			}

			$page_links[] = sprintf(
				'<a class="%s" title="%s" href="%s">%s</a>',
				'first-page' . $disable_first,
				esc_attr__( 'Go to the first page' ),
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				'&laquo;'
			);

			$page_links[] = sprintf(
				'<a class="%s" title="%s" href="%s">%s</a>',
				'prev-page' . $disable_first,
				esc_attr__( 'Go to the previous page' ),
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				'&lsaquo;'
			);

			if ( 'bottom' == $which ){
				$html_current_page = $current;
			} else {
				$html_current_page = sprintf(
					'<input class="current-page" title="%s" type="text" name="paged" value="%s" size="%d" />',
					esc_attr__( 'Current page' ),
					$current,
					strlen( $total_pages )
				);
			}

			$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
			$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

			$page_links[] = sprintf(
				'<a class="%s" title="%s" href="%s">%s</a>',
				'next-page' . $disable_last,
				esc_attr__( 'Go to the next page' ),
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				'&rsaquo;'
			);

			$page_links[] = sprintf(
				'<a class="%s" title="%s" href="%s">%s</a>',
				'last-page' . $disable_last,
				esc_attr__( 'Go to the last page' ),
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				'&raquo;'
			);

			$pagination_links_class = 'pagination-links';
			if ( ! empty( $infinite_scroll ) ) {
				$pagination_links_class = ' hide-if-js';
			}
			$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';
		}

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}


		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		if ( $echo ) {
			echo '' . $this->_pagination;
		} else {
			return $this->_pagination;
		}
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since 1.0.0
	 * @access protected
	 * @abstract
	 *
	 * @return array
	 */
	protected function get_columns() {
		die( 'function WP_ListTable::get_columns() must be over-ridden in a sub-class.' );
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 * Get a list of all, hidden and sortable columns, with filter applied
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_column_info() {
		if ( isset( $this->_column_headers ) ) {
			return $this->_column_headers;
		}

		$columns = get_column_headers( $this->screen );
		$hidden = get_hidden_columns( $this->screen );

		$sortable_columns = $this->get_sortable_columns();
		/**
		 * Filter the list table sortable columns for a specific screen.
		 *
		 * The dynamic portion of the hook name, $this->screen->id, refers
		 * to the ID of the current screen, usually a string.
		 *
		 * @since 1.0.0
		 *
		 * @param array $sortable_columns An array of sortable columns.
		 */
		$_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) ) {
				continue;
			}

			$data = (array) $data;
			if ( ! isset( $data[1] ) ) {
				$data[1] = false;
			}

			$sortable[$id] = $data;
		}

		$this->_column_headers = array( $columns, $hidden, $sortable );

		return $this->_column_headers;
	}

	/**
	 * Return number of visible columns
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return int
	 */
	public function get_column_count() {
		list ( $columns, $hidden ) = $this->get_column_info();
		$hidden = array_intersect( array_keys( $columns ), array_filter( $hidden ) );
		return count( $columns ) - count( $hidden );
	}

	/**
	 * Print column headers, accounting for hidden and sortable columns.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param bool $with_id Whether to set the id attribute or not
	 */
	protected function print_column_headers( $with_id = true ) {
		static $cb_counter = 1;
		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );

		if ( isset( $_GET['orderby'] ) ) {
			$current_orderby = $_GET['orderby'];
		} else {
			$current_orderby = '';
		}

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) {
			$current_order = 'desc';
		} else {
			$current_order = 'asc';
		}

		if ( ! empty( $columns['cb'] ) ) {
			$columns['cb'] = sprintf(
				'<label class="screen-reader-text" for="cb-select-all-%1$s">%2$s</label>' .
				'<input id="cb-select-all-%1$s" type="checkbox" />',
				$cb_counter,
				__( 'Select All' )
			);
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) ) {
				$style = 'display:none;';
			}

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) ) {
				$class[] = 'num';
			}

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = sprintf(
					'<a href="%1$s"><span>%2$s</span><span class="sorting-indicator"></span></a>',
					esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ),
					$column_display_name
				);
			}

			$id = $with_id ? $column_key : '';

			if ( ! empty( $class ) ) {
				$class = join( ' ', $class );
			} else {
				$class = '';
			}

			printf(
				'<th scope="col" id="%2$s" class="%3$s" $style>%1$s</th>',
				$column_display_name,
				esc_attr( $id ),
				esc_attr( $class )
			);
		}
	}

	/**
	 * Display the table
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function display() {
		extract( $this->_args );

		$this->display_tablenav( 'top' );

		$attr = '';
		if ( $singular ) {
			$attr = sprintf( 'data-wp-lists="list:%1$s"', $singular );
		}

		?>
		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>" cellspacing="0">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>

			<tbody id="the-list-<?php echo esc_attr( $this->id ); ?>" <?php echo '' . $attr; ?>>
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Get a list of CSS classes for the <table> tag
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', $this->_args['plural'] );
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk' );
		}

		$bulk_actions = $this->bulk_actions( false );
		$extra = $this->extra_tablenav( $which, false );
		$pagination = $this->pagination( $which, false );

		// Don't display empty tablenav elements.
		if ( ! $bulk_actions && ! $extra && ! $pagination ) {
			return;
		}

		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<div class="alignleft actions bulkactions">
				<?php echo '' . $bulk_actions; ?>
			</div>
			<?php
			echo '' . $extra . $pagination;
			?>

			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param  string $which Either 'top' or 'bottom'
	 * @param  bool $echo Output or return the HTML code? Default is output.
	 */
	protected function extra_tablenav( $which, $echo = true ) {}

	/**
	 * Generate the <tbody> part of the table
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();

			// Add an inline edit form.
			$inline_nonce = wp_create_nonce( 'inline' );
			?>
			<tr id="inline-edit" style="display:none" class="inline-edit-row"><td>
			<?php $this->inline_edit(); ?>
			<p class="submit inline-edit-save">
				<a accesskey="c" href="#inline-edit" class="button-secondary cancel alignleft"><?php _e( 'Cancel', MS_TEXT_DOMAIN ); ?></a>
				<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr( $inline_nonce ); ?>">
				<a accesskey="s" href="#inline-edit" class="button-primary save alignright"><?php _e( 'Update', MS_TEXT_DOMAIN ); ?></a>
				<span class="error" style="display:none"></span>
				<br class="clear">
			</p>
			</td></tr>
			<?php
		} else {
			list( $columns, $hidden ) = $this->get_column_info();
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
			$this->no_items();
			echo '</td></tr>';
		}
	}

	/**
	 * Generate the table rows
	 *
	 * @since 1.0.0
	 *
	 * @param array $items Optional. An array of items to display. This is used
	 *           to generate HTML code of a single row only -> in Ajax response.
	 */
	public function display_rows( $items = null ) {
		if ( empty( $items ) ) { $items = $this->items; }

		foreach ( $items as $item ) {
			$this->single_row( $item );
		}
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param object $item The current item.
	 */
	protected function single_row( $item ) {
		static $Row_Class = '';
		static $Row_Num = 0;

		$Row_Class = ( $Row_Class === '' ? ' alternate' : '' );
		$Row_Num += 1;
		$row_id = 'item-' . $item->id;

		$class_list = trim(
			'row-' . $Row_Num . ' ' .
			$row_id .
			$Row_Class .
			' item ' .
			$this->single_row_class( $item )
		);

		echo '<tr id="' . esc_attr( $row_id ) . '" class="' . esc_attr( $class_list ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Returns the row-class to be used for the specified table item.
	 *
	 * @since  1.0.4.4
	 * @access protected
	 *
	 * @param  object $item The current item.
	 * @return string Class to be added to the table row.
	 */
	protected function single_row_class( $item ) {
		return ''; // Can be overridden by child classes.
	}

	/**
	 * Generates the columns for a single row of the table
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param object $item The current item
	 */
	protected function single_row_columns( $item ) {
		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			if ( 'cb' == $column_name ) {
				printf(
					'<th scope="row" class="check-column">%1$s</th>',
					$this->column_cb( $item, $column_name )
				);
			} else {
				$class = "$column_name column-$column_name";

				$style = '';
				if ( in_array( $column_name, $hidden ) ) {
					$style = 'display:none;';
				}

				if ( method_exists( $this, 'column_' . $column_name ) ) {
					$code = call_user_func(
						array( $this, 'column_' . $column_name ),
						$item,
						$column_name
					);
				} else {
					$code = $this->column_default(
						$item,
						$column_name
					);
				}

				$code = apply_filters(
					'ms_helper_listtable_' . $this->id . '-column_' . $column_name,
					$code,
					$item,
					$column_name
				);

				printf(
					'<td class="%1$s" style="%3$s">%2$s</td>',
					esc_attr( $class ),
					$code,
					$style
				);
			}
		}
	}

	/**
	 * Handle an incoming ajax request (called from admin-ajax.php)
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function ajax_response() {
		$this->prepare_items();

		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}

		$rows = ob_get_clean();

		$response = array( 'rows' => $rows );

		if ( isset( $total_items ) ) {
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );
		}

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		die( json_encode( $response ) );
	}

	/**
	 * Adds a hidden row that contains an inline editor.
	 *
	 * To customize the inline form overwrite this function in a child class.
	 *
	 * @since 1.1.0
	 * @access protected
	 */
	protected function inline_edit() {
		?>
		Inline edit form
		<?php
	}

	/**
	 * Send required variables to JavaScript land
	 *
	 * @access private
	 */
	private function _js_vars() {
		$args = array(
			'class'  => get_class( $this ),
			'screen' => array(
				'id'   => $this->screen->id,
				'base' => $this->screen->base,
			)
		);

		printf( "<script type='text/javascript'>list_args = %s;</script>\n", json_encode( $args ) );
	}
}
