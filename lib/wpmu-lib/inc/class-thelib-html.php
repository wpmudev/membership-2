<?php
/**
 * HTML Helper functions
 * Access via function `lib3()->html`.
 *
 * @since 1.1.0
 */
class TheLib_Html extends TheLib  {

	/* Constants for default HTML input elements. */
	const INPUT_TYPE_HIDDEN = 'hidden';
	const INPUT_TYPE_TEXT_AREA = 'textarea';
	const INPUT_TYPE_SELECT = 'select';
	const INPUT_TYPE_RADIO = 'radio';
	const INPUT_TYPE_SUBMIT = 'submit';
	const INPUT_TYPE_BUTTON = 'button';
	const INPUT_TYPE_CHECKBOX = 'checkbox';
	const INPUT_TYPE_IMAGE = 'image';
	// Different input types
	const INPUT_TYPE_TEXT = 'text';
	const INPUT_TYPE_PASSWORD = 'password';
	const INPUT_TYPE_NUMBER = 'number';
	const INPUT_TYPE_EMAIL = 'email';
	const INPUT_TYPE_URL = 'url';
	const INPUT_TYPE_TIME = 'time';
	const INPUT_TYPE_SEARCH = 'search';
	const INPUT_TYPE_FILE = 'file';

	/* Constants for advanced HTML input elements. */
	const INPUT_TYPE_WP_EDITOR = 'wp_editor';
	const INPUT_TYPE_DATEPICKER = 'datepicker';
	const INPUT_TYPE_RADIO_SLIDER = 'radio_slider';
	const INPUT_TYPE_TAG_SELECT = 'tag_select';
	const INPUT_TYPE_WP_PAGES = 'wp_pages';

	/* Constants for default HTML elements. */
	const TYPE_HTML_LINK = 'html_link';
	const TYPE_HTML_SEPARATOR = 'html_separator';
	const TYPE_HTML_TEXT = 'html_text';
	const TYPE_HTML_TABLE = 'html_table';


	/**
	 * Class constructor
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		parent::__construct();
	}


	/*================================*\
	====================================
	==                                ==
	==           WP POINTER           ==
	==                                ==
	====================================
	\*================================*/


	/**
	 * Displays a WordPress pointer on the current admin screen.
	 *
	 * @since  1.1.0
	 * @api
	 *
	 * @param  array|string $pointer_id Internal ID of the pointer, make sure it is unique!
	 *                Optionally this param can contain all params in array notation.
	 * @param  string $html_el HTML element to point to (e.g. '#menu-appearance')
	 * @param  string $title The title of the pointer.
	 * @param  string $body Text of the pointer.
	 *
	 * @return TheLib_Html Reference to $this for chaining.
	 */
	public function pointer( $args, $html_el = '', $title = false, $body = '' ) {
		if ( ! is_admin() ) {
			return;
		}

		if ( is_array( $args ) ) {
			if ( isset( $args['target'] ) && ! isset( $args['html_el'] ) ) {
				$args['html_el'] = $args['target'];
			}
			if ( isset( $args['id'] ) && ! isset( $args['pointer_id'] ) ) {
				$args['pointer_id'] = $args['id'];
			}
			if ( isset( $args['modal'] ) && ! isset( $args['blur'] ) ) {
				$args['blur'] = $args['modal'];
			}
			if ( ! isset( $args['once'] ) ) {
				$args['once'] = true;
			}

			self::$core->array->equip( $args, 'pointer_id', 'html_el', 'title', 'body', 'once', 'modal', 'blur' );

			extract( $args );
		} else {
			$pointer_id = $args;
			$once = true;
			$modal = true;
			$blur = true;
		}

		$once = self::$core->is_true( $once );
		$modal = self::$core->is_true( $modal );
		$blur = self::$core->is_true( $blur );

		$this->_add( 'init_pointer', compact( 'pointer_id', 'html_el', 'title', 'body', 'once', 'modal', 'blur' ) );
		$this->add_action( 'init', '_init_pointer' );

		return $this;
	}

	/**
	 * Action handler for plugins_loaded. This decides if the pointer will be displayed.
	 *
	 * @since  1.0.2
	 * @internal
	 */
	public function _init_pointer() {
		$items = $this->_get( 'init_pointer' );
		foreach ( $items as $item ) {
			extract( $item ); // pointer_id, html_el, title, body, once, modal, blur
			$show = true;

			if ( $once ) {
				// Find out which pointer IDs this user has already seen.
				$seen = (string) get_user_meta(
					get_current_user_id(),
					'dismissed_wp_pointers',
					true
				);
				$seen_list = explode( ',', $seen );
				$show = ! in_array( $pointer_id, $seen_list );
			} else {
				$show = true;
			}

			// Include all scripts and code to display the pointer!
			if ( $show ) {
				$this->add_action( 'admin_print_footer_scripts', '_pointer_print_scripts' );
				$this->add_action( 'admin_enqueue_scripts', '_enqueue_pointer' );

				$this->_add( 'pointer', $item );
			}
		}
	}

	/**
	 * Enqueue wp-pointer
	 *
	 * @since  1.0.1
	 * @internal
	 */
	public function _enqueue_pointer() {
		// Load the JS/CSS for WP Pointers
		wp_enqueue_script( 'wp-pointer' );
		wp_enqueue_style( 'wp-pointer' );
	}

	/**
	 * Action hook for admin footer scripts
	 *
	 * @since  1.0.1
	 * @internal
	 */
	public function _pointer_print_scripts() {
		$items = $this->_get( 'pointer' );
		foreach ( $items as $item ) {
			extract( $item ); // pointer_id, html_el, title, body, once, modal, blur
			include $this->_view_path( 'pointer.php' );
		}
	}


	/*===========================*\
	===============================
	==                           ==
	==           POPUP           ==
	==                           ==
	===============================
	\*===========================*/


	/**
	 * Display a wpmUi popup on page load.
	 *
	 * @since  1.1.0
	 * @api
	 *
	 * @param  array $args Popup options.
	 * @return Reference to $this for chaining.
	 */
	public function popup( $args = array() ) {
		// Determine which hook should print the data.
		$hook = ( is_admin() ? 'admin_footer' : 'wp_footer' );

		self::$core->array->equip( $args, 'title', 'body', 'screen', 'modal', 'width', 'height', 'class' );

		// Don't add empty popups
		if ( empty( $args['title'] ) && empty( $args['body'] ) ) {
			return;
		}
		if ( ! isset( $args['close'] ) ) {
			$args['close'] = true;
		}
		if ( ! isset( $args['sticky'] ) ) {
			$args['sticky'] = false;
		}

		$args['width'] = absint( $args['width'] );
		$args['height'] = absint( $args['height'] );

		if ( $args['width'] < 20 ) {
			$args['width'] = -1;
		}
		if ( $args['height'] < 20 ) {
			$args['height'] = -1;
		}

		$args['modal'] = $args['modal'] ? 'true' : 'false';
		$args['persist'] = $args['sticky'] ? 'false' : 'true';
		$args['close'] = $args['close'] ? 'true' : 'false';

		self::_add( 'popup', $args );
		$this->add_action( $hook, '_popup_callback' );
		self::$core->ui->add( 'core' );

		return $this;
	}

	/**
	 * Add popup code to the page footer
	 *
	 * @since  1.1.3
	 * @internal
	 */
	public function _popup_callback() {
		$items = self::_get( 'popup' );
		self::_clear( 'popup' );
		$screen_info = get_current_screen();
		$screen_id = $screen_info->id;

		foreach ( $items as $item ) {
			extract( $item ); // title, body, modal, close, modal, persist, width, height, class

			if ( empty( $title ) ) {
				$close = false;
			}

			if ( empty( $screen ) || $screen_id == $screen ) {
				$body = '<div>' . $body . '</div>';
				echo '<script>jQuery(function(){wpmUi.popup()';
				printf( '.title( %1$s, %2$s )', json_encode( $title ), $close );
				printf( '.modal( %1$s, %2$s )', $modal, $persist );
				printf( '.size( %1$s, %2$s )', json_encode( $width ), json_encode( $height ) );
				printf( '.set_class( %1$s )', json_encode( $class ) );
				printf( '.content( %1$s )', json_encode( $body ) );
				echo '.show();})</script>';
			}
		}
	}


	/*=================================*\
	=====================================
	==                                 ==
	==           PLUGIN LIST           ==
	==                                 ==
	=====================================
	\*=================================*/


	/**
	 * Generates full code for a plugin list in WordPress 4.0 style, including
	 * the filter and search section in the top.
	 *
	 * All items are included in page load and displayed or filtered via
	 * javascript.
	 *
	 * @since  1.1
	 * @api
	 *
	 * @param  array $items {
	 *     List of all items to include. Each item has these properties:
	 *
	 *     @var string $title
	 *     @var string $description
	 *     @var string $version
	 *     @var string $author
	 *     @var array $action
	 *     @var array $details
	 *     @var bool $active
	 *     @var string $icon
	 * }
	 * @param  object $lang {
	 *     @var string $active_badge
	 *     @var string $show_details
	 *     @var string $close_details
	 * }
	 * @param  array $filters {
	 *     @var string $key
	 *     @var string $label
	 * }
	 *
	 * @return Reference to $this for chaining.
	 */
	public function addon_list( $items, $lang, $filters ) {
		self::$core->ui->css( $this->_css_url( 'wpmu-card-list.3.min.css' ) );
		self::$core->ui->js( $this->_js_url( 'wpmu-card-list.3.min.js' ) );
		include $this->_view_path( 'list.php' );
		return $this;
	}


	/*====================================*\
	========================================
	==                                    ==
	==           HTML STRUCTURE           ==
	==                                    ==
	========================================
	\*====================================*/


	/**
	 * Method for creating HTML elements/fields.
	 *
	 * Pass in array with field arguments. See $defaults for argmuments.
	 * Use constants to specify field type. e.g. self::INPUT_TYPE_TEXT
	 *
	 * @since 1.1.0
	 * @api
	 *
	 * @param  array|string $field_args {
	 *                If this param is a string then the string is output.
	 *                Otherwise an array is expected that defines the output
	 *                field.
	 *
	 *                $type  string  Field type. {@see const definitions}
	 *                $id    string  Field ID (html attribute)
	 *                $class string  Field class (html attribute)
	 *                $value string  Field value (html attribute)
	 *
	 *                $title  string  Field label/caption to display.
	 *                $desc   string  Description.
	 *                $before string  Text before the input element.
	 *                $after  string  Text after the input element.
	 *
	 *                (more attributes available)
	 * }
	 * @param  bool $return Optional. If true the element is returned by
	 *                      function. Default: false (direct echo the HTML)
	 *
	 * @return void|string If $return param is false the HTML will be echo'ed,
	 *                     otherwise returned as string (default is echo)
	 */
	public function element( $field_args, $return = false ) {
		self::$core->ui->add( 'html_element' );

		if ( is_string( $field_args ) ) {
			if ( $return ) {
				return $field_args;
			} else {
				echo $field_args;
				return;
			}
		}

		// Field arguments.
		$defaults = array(
			'id'             => '',
			'name'           => '',
			'section'        => '', // Only used if name is empty
			'title'          => '', // Title above desc / element
			'desc'           => '', // Usually displayed in row above the element
			'before'         => '', // In same row as element
			'after'          => '', // In same row as element
			'value'          => '',
			'type'           => 'text',
			'class'          => '',
			'label_class'    => '',
			'maxlength'      => '',
			'equalTo'        => '',
			'field_options'  => array(),
			'multiple'       => false,
			'tooltip'        => '',
			'alt'            => '',
			'read_only'      => false,
			'placeholder'    => '',
			'data_placeholder' => '',
			'ajax_data'      => '',
			'data'           => array(),
			'label_type'     => 'label',
			'sticky'         => false, // populate $value from $_REQUEST struct
			'config'         => array(), // other, element-specific configurations
			'wrapper_class'  => '', // class added to the outermost element wrapper
			// Specific for type 'button', 'submit':
			'button_value'   => '',
			'button_type'    => '',  // for display [empty/'submit'/'button']
			// Specific for type 'tag_select':
			'title_selected' => '',
			'empty_text'     => '',
			'button_text'    => '',
			// Specific for type 'link':
			'target'         => '_self',
			// Specific for type 'radio_slider':
			'url'            => '',
		);

		$field_args = wp_parse_args( $field_args, $defaults );
		extract( $field_args );

		if ( empty( $name ) ) {
			if ( ! empty( $section ) ) {
				$name = $section . "[$id]";
			} else {
				$name = $id;
			}
		}

		// Input arguments

		$attr_placeholder = '';
		$attr_data_placeholder = '';

		if ( '' !== $placeholder && false !== $placeholder ) {
			$attr_placeholder = 'placeholder="' . esc_attr( $placeholder ) . '" ';
		}

		if ( '' !== $data_placeholder && false !== $data_placeholder ) {
			$attr_data_placeholder = 'data-placeholder="' . esc_attr( $data_placeholder ) . '" ';
		}

		if ( ! empty( $data_ms ) && empty( $ajax_data ) ) {
			$ajax_data = $data_ms;
		}

		if ( ! empty( $ajax_data ) ) {
			if ( ! empty( $ajax_data['action'] )
				&& ( empty( $ajax_data['_wpnonce'] )
					|| true === $ajax_data['_wpnonce']
				)
			) {
				$ajax_data['_wpnonce'] = wp_create_nonce( $ajax_data['action'] );
			}

			$ajax_data = ' data-wpmui-ajax="' . esc_attr( json_encode( $ajax_data ) ) . '" ';
		}

		$max_attr = empty( $maxlength ) ? '' : 'maxlength="' . esc_attr( $maxlength ) . '" ';
		$read_only = empty( $read_only ) ? '' : 'readonly="readonly" ';
		$multiple = empty( $multiple ) ? '' : 'multiple="multiple" ';

		$data_attr = '';
		foreach ( $data as $data_key => $data_value ) {
			$data_attr .= 'data-' . $data_key . '=' . json_encode( $data_value ) . ' ';
		}
		foreach ( $config as $conf_key => $conf_value ) {
			$data_attr .= $conf_key . '=' . json_encode( $conf_value ) . ' ';
		}

		if ( ! empty( $ajax_data ) ) {
			$class .= ' wpmui-ajax-update';
		}

		if ( $sticky ) {
			$sticky_key = $name;
			if ( '[]' == substr( $sticky_key, -2 ) ) {
				$sticky_key = substr( $sticky_key, 0, -2 );
			}

			if ( isset( $_POST[$sticky_key] ) ) {
				$value = $_POST[$sticky_key];
			} elseif ( isset( $_GET[$sticky_key] ) ) {
				$value = $_GET[$sticky_key];
			}
		}

		$field_options = self::$core->array->get( $field_options );

		$labels = (object) array(
			'title' => $title,
			'desc' => $desc,
			'before' => $before,
			'after' => $after,
			'tooltip' => $tooltip,
			'tooltip_code' => $this->tooltip( $tooltip, true ),
			'id' => $id,
			'class' => $label_class,
			'label_type' => $label_type,
		);

		// Capture to output buffer
		if ( $return ) { ob_start(); }

		switch ( $type ) {
			case self::INPUT_TYPE_HIDDEN:
				$this->element_hidden(
					$id,
					$name,
					$value,
					$class
				);
				break;

			case self::INPUT_TYPE_TEXT:
			case self::INPUT_TYPE_PASSWORD:
			case self::INPUT_TYPE_NUMBER:
			case self::INPUT_TYPE_EMAIL:
			case self::INPUT_TYPE_URL:
			case self::INPUT_TYPE_TIME:
			case self::INPUT_TYPE_SEARCH:
			case self::INPUT_TYPE_FILE:
				$this->element_input(
					$labels,
					$type,
					$class,
					$id,
					$name,
					$value,
					$read_only . $max_attr . $attr_placeholder . $ajax_data . $data_attr,
					$wrapper_class
				);
				break;

			case self::INPUT_TYPE_DATEPICKER:
				$this->element_datepicker(
					$labels,
					$class,
					$id,
					$name,
					$value,
					$max_attr . $attr_placeholder . $ajax_data . $data_attr,
					$wrapper_class
				);
				break;

			case self::INPUT_TYPE_TEXT_AREA:
				$this->element_textarea(
					$labels,
					$class,
					$id,
					$name,
					$value,
					$read_only . $attr_placeholder . $ajax_data . $data_attr,
					$wrapper_class
				);
				break;

			case self::INPUT_TYPE_SELECT:
				$this->element_select(
					$labels,
					$class,
					$id,
					$name,
					$value,
					$multiple . $read_only . $attr_data_placeholder . $ajax_data . $data_attr,
					$field_options,
					$wrapper_class
				);
				break;

			case self::INPUT_TYPE_RADIO:
				$this->element_radio(
					$labels,
					$class,
					$id,
					$name,
					$value,
					$ajax_data,
					$field_options,
					$wrapper_class
				);
				break;

			case self::INPUT_TYPE_CHECKBOX:
				$this->element_checkbox(
					$labels,
					$class,
					$id,
					$name,
					$value,
					$ajax_data . $data_attr,
					$field_options,
					$config
				);
				break;

			case self::INPUT_TYPE_WP_EDITOR:
				$this->element_wp_editor(
					$labels,
					$id,
					$value,
					$field_options
				);
				break;

			case self::INPUT_TYPE_BUTTON:
			case self::INPUT_TYPE_SUBMIT:
				if ( empty( $button_type ) ) {
					$button_type = $type;
				}

				if ( $button_type === self::INPUT_TYPE_SUBMIT ) {
					$class .= ' wpmui-submit button-primary';
				}

				$this->element_button(
					$labels,
					$type,
					$class,
					$id,
					$name,
					$value,
					$button_value,
					$ajax_data . $data_attr
				);
				break;

			case self::INPUT_TYPE_IMAGE:
				$this->element_image(
					$labels,
					$class,
					$id,
					$name,
					$value,
					$alt,
					$ajax_data . $data_attr
				);
				break;

			case self::INPUT_TYPE_RADIO_SLIDER:
				$this->element_radioslider(
					$labels,
					$class,
					$id,
					$name,
					$value,
					$url,
					$read_only,
					$ajax_data . $data_attr,
					$field_options,
					$wrapper_class
				);
				break;

			case self::INPUT_TYPE_TAG_SELECT:
				$this->element_tagselect(
					$labels,
					$class,
					$id,
					$name,
					$value,
					$field_options,
					$multiple . $read_only . $attr_data_placeholder . $data_attr,
					$ajax_data,
					$empty_text,
					$button_text,
					$title_selected,
					$wrapper_class
				);
				break;

			case self::INPUT_TYPE_WP_PAGES:
				$this->element_wp_pages(
					$labels,
					$class,
					$id,
					$name,
					$value,
					$multiple . $read_only . $attr_data_placeholder . $ajax_data . $data_attr,
					$field_options,
					$wrapper_class
				);
				break;

			case self::TYPE_HTML_LINK:
				$this->element_link(
					$labels,
					$class,
					$id,
					$value,
					$url,
					$ajax_data . $data_attr,
					$target
				);
				break;

			case self::TYPE_HTML_SEPARATOR:
				$this->element_separator(
					($value !== 'vertical' ? 'horizontal' : 'vertical')
				);
				break;

			case self::TYPE_HTML_TEXT:
				$this->element_wrapper(
					$labels,
					$class,
					$id,
					$value,
					'span',
					$wrapper_class
				);
				break;

			case self::TYPE_HTML_TABLE:
				$this->element_table(
					$labels,
					$class,
					$id,
					$value,
					$field_options,
					$wrapper_class
				);
				break;
		}

		// Return the output buffer
		if ( $return ) { return ob_get_clean(); }
	}


	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_hidden( $id, $name, $value, $class ) {
		printf(
			'<input class="wpmui-field-input wpmui-hidden %4$s" type="hidden" id="%1$s" name="%2$s" value="%3$s" />',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			esc_attr( $class )
		);
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_input( $labels, $type, $class, $id, $name, $value, $attr, $wrapper_class ) {
		$this->wrap_open( 'input', $class, 'span', $wrapper_class );
		$this->element_label( $labels );

		printf(
			'<input class="wpmui-field-input wpmui-%1$s %2$s wpmui-input-%4$s" type="%1$s" id="%3$s" name="%4$s" value="%5$s" %6$s />',
			esc_attr( $type ),
			esc_attr( $class ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			$attr
		);

		$this->element_hint( $labels );
		$this->wrap_close();
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_datepicker( $labels, $class, $id, $name, $value, $attr, $wrapper_class ) {
		$this->wrap_open( 'datepicker', $class, 'span', $wrapper_class );
		$this->element_label( $labels );

		if ( ! empty( $value ) ) {
			if ( ! preg_match( '/\d\d\d\d-\d\d-\d\d/', $value ) ) {
				$value = date( 'Y-m-d', strtotime( $value ) );
			}
		}

		printf(
			'<span class="wpmui-field-input"><input class="wpmui-datepicker %1$s" type="text" id="%2$s" name="%3$s" value="%4$s" %5$s /><i class="wpmui-icon wpmui-fa wpmui-fa-calendar"></i></span>',
			esc_attr( $class ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			$attr
		);

		$this->element_hint( $labels );
		$this->wrap_close();
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_textarea( $labels, $class, $id, $name, $value, $attr, $wrapper_class ) {
		$this->wrap_open( 'textarea', $class, 'span', $wrapper_class );
		$this->element_label( $labels );

		printf(
			'<textarea class="wpmui-field-input wpmui-textarea %1$s" type="text" id="%2$s" name="%3$s" %5$s>%4$s</textarea>',
			esc_attr( $class ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_textarea( $value ),
			$attr
		);

		$this->element_hint( $labels );
		$this->wrap_close();
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_select( $labels, $class, $id, $name, $value, $attr, $field_options, $wrapper_class ) {
		$options = $this->select_options( $field_options, $value );

		$this->wrap_open( 'select', $class, 'span', $wrapper_class );
		$this->element_label( $labels );

		printf(
			'<select id="%1$s" class="wpmui-field-input wpmui-field-select %2$s" name="%3$s" %4$s>%5$s</select>',
			esc_attr( $id ),
			esc_attr( $class ),
			esc_attr( $name ),
			$attr,
			$options
		);

		$this->element_hint( $labels );
		$this->wrap_close();
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_radio( $labels, $class, $id, $name, $value, $attr, $field_options, $wrapper_class ) {
		$this->wrap_open( 'radio', $class, 'span', $wrapper_class );
		$this->element_label( $labels );

		foreach ( $field_options as $key => $option ) {
			if ( is_array( $option ) ) {
				self::$core->array->equip( $option, 'text', 'desc', 'disabled' );
				$item_text = $option['text'];
				$item_desc = $option['desc'];
				$item_disabled = self::$core->is_true( $option['disabled'] );
			} else {
				$item_text = $option;
				$item_desc = '';
				$item_disabled = false;
			}

			$checked = checked( $value, $key, false );
			$item_attr = $attr;
			$item_class = $class;
			if ( $item_disabled ) {
				$item_attr .= ' disabled="disabled"';
				$item_class .= ' disabled';
			}
			$radio_desc = '';

			if ( ! empty( $item_desc ) ) {
				$radio_desc = sprintf( '<div class="wpmui-input-description"><p>%1$s</p></div>', $item_desc );
			}

			printf(
				'<div class="wpmui-radio-input-wrapper %1$s wpmui-%2$s"><label class="wpmui-field-label" for="%4$s_%2$s"><input class="wpmui-field-input wpmui-radio %1$s" type="radio" name="%3$s" id="%4$s_%2$s" value="%2$s" %5$s /><span class="wpmui-radio-caption">%6$s</span>%7$s</label></div>',
				esc_attr( $item_class ),
				esc_attr( $key ),
				esc_attr( $name ),
				esc_attr( $id ),
				$item_attr . $checked,
				$item_text,
				$radio_desc
			);
		}

		$this->element_hint( $labels );
		$this->wrap_close();
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_checkbox( $labels, $class, $id, $name, $value, $attr, $itemlist, $options ) {
		$item_desc = '';
		if ( ! empty( $labels->desc ) ) {
			$item_desc = sprintf( '<div class="wpmui-field-description"><p>%1$s</p></div>', $labels->desc );
		}
		$listitems = array();

		if ( ! empty( $itemlist ) ) {
			// Multiple items in the checkbox list.
			printf(
				'<div class="wpmui-checkbox-title">%1$s %2$s</div><div class="wpmui-checkbox-list wpmui-field-input">%3$s',
				$labels->title,
				$labels->tooltip,
				$item_desc
			);
			$item_desc = '';

			if ( ! is_array( $value ) ) {
				$value = array( $value );
			}

			foreach ( $itemlist as $key => $item ) {
				$tmp_items = array();
				if ( is_array( $item ) ) {
					$tmp_items[] = array(
						'name' => false,
						'label' => $key,
						'value' => false,
						'parent' => true,
					);
					foreach ( $item as $sub_key => $sub_item ) {
						$tmp_items[] = array(
							'name' => $name . '[]',
							'label' => $sub_item,
							'value' => $sub_key,
							'child' => true,
						);
					}
				} else {
					$tmp_items[] = array(
						'name' => $name . '[]',
						'label' => $item,
						'value' => $key,
					);
				}

				foreach ( $tmp_items as $tmp_item ) {
					$item_label = sprintf(
						'<div class="wpmui-checkbox-caption">%1$s</div>',
						$tmp_item['label']
					);

					$tmp_item['label'] = $item_label;
					$tmp_item['checked'] = checked( in_array( $tmp_item['value'], $value ), true, false );
					$tmp_item['child'] = empty( $tmp_item['child'] ) ? false : true;
					$tmp_item['parent'] = empty( $tmp_item['parent'] ) ? false : true;

					$listitems[] = $tmp_item;
				}
			}
		} else {
			// Single checkbox item.
			$item_label = '';
			if ( empty( $options['checkbox_position'] )
				|| 'left' === $options['checkbox_position']
			) {
				$item_label = sprintf(
					'<div class="wpmui-checkbox-caption">%1$s %2$s</div>',
					$labels->title,
					$labels->tooltip
				);
			}

			$listitems[] = array(
				'name' => $name,
				'label' => $item_label,
				'checked' => checked( $value, true, false ),
				'value' => 1,
				'child' => false,
				'parent' => false,
			);
		}

		$is_group = false;
		foreach ( $listitems as $item ) {
			$item_class = $class;
			if ( $item['parent'] ) {
				$is_group = true;
				echo '<div class="wpmui-group">';
				$item_class .= ' wpmui-parent';
			} elseif ( $item['child'] ) {
				$is_group = true;
				$item_class .= ' wpmui-child';
			} elseif ( $is_group ) {
				echo '</div>';
				$is_group = false;
			}

			if ( empty( $item['name'] ) ) {
				printf(
					'<label class="wpmui-checkbox-wrapper wpmui-field-label wpmui-no-checkbox %1$s">%2$s %3$s</label>',
					esc_attr( $item_class ),
					$item['label'],
					$item_desc
				);
			} else {
				printf(
					'<label class="wpmui-checkbox-wrapper wpmui-field-label %2$s"><input id="%1$s" class="wpmui-field-input wpmui-field-checkbox" type="checkbox" name="%3$s" value="%7$s" %4$s />%5$s %6$s</label>',
					esc_attr( $id ),
					esc_attr( $item_class ),
					esc_attr( $item['name'] ),
					$attr . $item['checked'],
					$item['label'],
					$item_desc,
					$item['value']
				);
			}
		}

		$this->element_hint( $labels );

		if ( ! empty( $itemlist ) ) {
			echo '</div>';
		}
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_wp_editor( $labels, $id, $value, $options ) {
		$this->element_label( $labels );

		wp_editor( $value, $id, $options );

		$this->element_hint( $labels );
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_button( $labels, $type, $class, $id, $name, $label, $value, $attr ) {
		$this->element_label( $labels );

		printf(
			'<button class="wpmui-field-input button %1$s" type="%7$s" id="%2$s" name="%3$s" value="%6$s" %5$s>%4$s</button>',
			esc_attr( $class ),
			esc_attr( $id ),
			esc_attr( $name ),
			$label,
			$attr,
			$value,
			$type
		);

		$this->element_hint( $labels );
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_image( $labels, $class, $id, $name, $value, $alt, $attr ) {
		$this->element_label( $labels );

		printf(
			'<input type="image" class="wpmui-field-input wpmui-input-image %1$s" id="%2$s" name="%3$s" border="0" src="%4$s" alt="%5$s" %6$s/>',
			esc_attr( $class ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_url( $value ),
			esc_attr( $alt ),
			$attr
		);

		$this->element_hint( $labels );
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_radioslider( $labels, $class, $id, $name, $state, $url, $read_only, $attr, $options, $wrapper_class ) {
		$options = self::$core->array->get( $options );
		if ( ! isset( $options['active'] ) ) { $options['active'] = true; }
		if ( ! isset( $options['inactive'] ) ) { $options['inactive'] = false; }

		if ( $state ) { $value = $options['active']; }
		else { $value = $options['inactive']; }

		$turned = ( $value ) ? 'on' : 'off';

		$this->wrap_open( 'radio-slider', $class, 'span', $turned . ' ' . $wrapper_class );
		$this->element_label( $labels );

		$attr .= ' data-states="' . esc_attr( json_encode( $options ) ) . '" ';
		$link_url = ! empty( $url ) ? '<a href="' . esc_url( $url ) . '"></a>' : '';

		$attr_input = '';
		if ( ! $read_only ) {
			$attr_input = sprintf(
				'<input class="wpmui-field-input wpmui-hidden" type="hidden" id="%1$s" name="%2$s" value="%3$s" />',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value )
			);
		}

		printf(
			'<div class="wpmui-radio-slider %1$s wpmui-slider-%5$s %7$s" %6$s>%8$s<div class="wpmui-toggle" %2$s>%3$s</div>%4$s%9$s</div>',
			esc_attr( $turned ),
			$attr,
			$link_url,
			$attr_input,
			esc_attr( $id ),
			$read_only,
			esc_attr( $class ),
			'<span class="before"></span>',
			'<span class="after"></span>'
		);

		$this->element_hint( $labels );
		$this->wrap_close();
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_tagselect( $labels, $class, $id, $name, $value, $field_options, $attr, $ajax_data, $empty_text, $button_text, $title_selected, $wrapper_class ) {
		$labels->id = '_src_' . $id;

		$this->wrap_open( 'tag-selector', $class, 'span', $wrapper_class );
		$this->element_label( $labels );

		$options_selected = '';
		$options_available = '<option value=""></option>';
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		if ( empty( $field_options ) ) {
			// No values available, display a note instead of the input elements.
			printf(
				'<div id="%1$s" class="wpmui-no-data wpmui-field-input %2$s">%3$s</div>',
				esc_attr( $id ),
				esc_attr( $class ),
				$empty_text
			);
		} else {
			// There are values to select or remove. Display the input elements.
			$options_selected .= $this->select_options( $field_options, $value );
			$options_available .= $this->select_options( $field_options, $value, 'taglist' );

			$src_class = str_replace( 'wpmui-ajax-update', '', $class );

			// First Select: The value selected here can be added to the tag-list.
			printf(
				'<select id="_src_%1$s" class="wpmui-field-input wpmui-tag-source %2$s" %4$s>%5$s</select>',
				esc_attr( $id ),
				esc_attr( $src_class ),
				esc_attr( $name ),
				$attr,
				$options_available
			);

			// Button: Add element from First Select to Second Select.
			printf(
				'<button id="_src_add_%1$s" class="wpmui-field-input wpmui-tag-button button %2$s" type="button">%3$s</button>',
				esc_attr( $id ),
				esc_attr( $src_class ),
				$button_text
			);

			$label_tag = $labels;
			$label_tag->id = $id;
			$label_tag->title = $title_selected;
			$label_tag->id = $id;
			$label_tag->tooltip = '';
			$label_tag->tooltip_code = '';
			$label_tag->class .= ' wpmui-tag-label';
			$this->element_label( $label_tag );

			// Second Select: The actual tag-list
			printf(
				'<select id="%1$s" class="wpmui-field-input wpmui-field-select wpmui-tag-data %2$s" multiple="multiple" readonly="readonly" %4$s>%5$s</select>',
				esc_attr( $id ),
				esc_attr( $class ),
				esc_attr( $name ),
				$ajax_data,
				$options_selected
			);
		}

		$this->element_hint( $labels );
		$this->wrap_close();
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_wp_pages( $labels, $class, $id, $name, $value, $attr, $field_options, $wrapper_class ) {
		$defaults = array(
			'hierarchical' => 1,
			'sort_column' => 'post_title',
			'sort_order' => 'ASC',
			'no_item' => '(Select a page)',
		);
		$args = wp_parse_args( $field_options, $defaults );

		$pages = get_pages( $args );
		$parent_list = array();
		$items = array();

		foreach ( $pages as $page ) {
			$parent_list[$page->ID] = $page;
		}

		if ( ! array_key_exists( $value, $parent_list ) ) {
			// In case no value is selected set the default to 'no item';
			$items[$value] = $args['no_item'];
		}

		foreach ( $pages as $page_id => $page ) {
			$level = 0;
			$parent = $page;
			while ( $parent->post_parent ) {
				$parent = $parent_list[$parent->post_parent];
				$level += 1;
			}

			if ( 0 === strlen( $page->post_title ) ) {
				$label = sprintf(
					'#%1$s (%2$s)',
					$page->ID,
					$page->post_name
				);
			} else {
				$label = $page->post_title;
			}

			$items[$page->ID] = str_repeat( '&nbsp;&mdash;&nbsp;', $level ) . $label;
		}

		$this->element_select(
			$labels,
			$class . ' wpmui-wp-pages',
			$id,
			$name,
			$value,
			$attr,
			$items,
			$wrapper_class
		);
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_separator( $type = 'horizontal' ) {
		if ( 'v' === $type[0] ) {
			echo '<div class="wpmui-divider"></div>';
		} else {
			echo '<div class="wpmui-separator"></div>';
		}
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_link( $labels, $class, $id, $label, $url, $attr, $target ) {
		$this->element_desc( $labels );

		if ( empty( $labels->title ) ) {
			$title = $label;
		} else {
			$title = $labels->title;
		}

		printf(
			'<a id="%1$s" title="%2$s" class="wpmui-link %3$s" href="%4$s" target="%7$s" %6$s>%5$s</a>',
			esc_attr( $id ),
			esc_attr( strip_tags( $title ) ),
			esc_attr( $class ),
			esc_url( $url ),
			$label,
			$attr,
			$target
		);

		$this->element_hint( $labels );
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_wrapper( $labels, $class, $id, $code, $wrap, $wrapper_class ) {
		if ( empty( $wrap ) ) { $wrap = 'span'; }

		$this->wrap_open( 'html-text', $class, 'span', $wrapper_class );
		$this->element_label( $labels );

		printf(
			'<%1$s class="%2$s">%3$s</%1$s>',
			esc_attr( $wrap ),
			esc_attr( $class ),
			$code
		);

		$this->element_hint( $labels );
		$this->wrap_close();
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_table( $labels, $class, $id, $rows, $args, $wrapper_class ) {
		self::$core->array->equip( $args, 'head_row', 'head_col', 'col_class' );

		$this->wrap_open( 'table', $class, 'span', $wrapper_class );
		$this->element_label( $labels );

		$code_body = '';
		$code_head = '';

		if ( is_array( $rows ) ) {
			$args['col_class'] = self::$core->array->get( $args['col_class'] );

			foreach ( $rows as $row_num => $row ) {
				$code_row = '';
				$is_head_row = false;
				$row_class = $row_num % 2 === 0 ? '' : 'alternate';

				if ( 0 === $row_num && $args['head_row'] ) {
					$is_head_row = true;
				}

				if ( is_array( $row ) ) {
					foreach ( $row as $col_num => $col ) {
						$is_head = $is_head_row
							|| ( 0 === $col_num && $args['head_col'] );

						$col_class = isset( $args['col_class'][$col_num] )
							? $args['col_class'][$col_num]
							: '';

						$code_row .= sprintf(
							'<%1$s class="%3$s">%2$s</%1$s>',
							($is_head ? 'th' : 'td'),
							$col,
							$col_class
						);
					}
				} else {
					$code_row = $row;
				}

				$code_row = sprintf(
					'<tr class="%2$s">%1$s</tr>',
					$code_row,
					$row_class
				);

				if ( $is_head_row ) {
					$code_head .= $code_row;
				} else {
					$code_body .= $code_row;
				}
			}

			printf(
				'<table class="wpmui-html-table %1$s">%2$s%3$s</table>',
				esc_attr( $class ),
				'<thead>' . $code_head . '</thead>',
				'<tbody>' . $code_body . '</tbody>'
			);
		}

		$this->element_hint( $labels );
		$this->wrap_close();
	}

	/**
	 * Returns HTML code containing options used to build a select tag.
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  array $list List items as 'key => value' pairs.
	 * @param  array|string $value The selected value.
	 * @param  string $type Either 'default' or 'taglist'.
	 *
	 * @return string
	 */
	private function select_options( $list, $value = '', $type = 'default' ) {
		$options = '';
		$list = self::$core->array->get( $list );

		foreach ( $list as $key => $option ) {
			if ( is_array( $option ) ) {
				if ( empty( $option ) ) { continue; }
				$options .= sprintf(
					'<optgroup label="%1$s">%2$s</optgroup>',
					$key,
					$this->select_options( $option, $value, $type )
				);
			} else {
				$attr = '';
				if ( is_object( $option ) ) {
					if ( isset( $option->attr ) ) { $attr = $option->attr; }
					if ( isset( $option->label ) ) { $option = $option->label; }
				}
				if ( empty( $option ) ) { continue; }

				if ( is_array( $value ) ) {
					$is_selected = ( in_array( $key, $value ) );
				} else {
					$is_selected = $key == $value;
				}

				switch ( $type ) {
					case 'default':
						$attr .= selected( $is_selected, true, false );
						$options .= sprintf(
							'<option value="%1$s" %2$s>%3$s</option>',
							esc_attr( $key ),
							$attr,
							$option
						);
						break;

					case 'taglist':
						$attr .= ($is_selected ? 'disabled="disabled"' : '');
						$options .= sprintf(
							'<option value="%1$s" %2$s>%3$s</option>',
							esc_attr( $key ),
							$attr,
							$option
						);
						break;
				}
			}
		}

		return $options;
	}

	/**
	 * Output the opening tag of an input wrapper.
	 *
	 * @since  2.0.0
	 * @internal
	 *
	 * @param  string $type Wrapper type, class is set to 'wpmui-$type-wrapper'.
	 * @param  string $classes String or array containing additional classes.
	 *         All classes are extended with '-wrapper'.
	 * @param  string $tag Optional. The tag name, default 'span'
	 * @param  string $raw_classes String or array containing additional classes.
	 *         These classes are not modified, but output as they appear.
	 */
	private function wrap_open( $type, $classes = '', $tag = 'span', $raw_classes = '' ) {
		if ( is_string( $classes ) ) {
			$classes = explode( ' ', $classes );
		}

		if ( count( $classes ) ) {
			$classes = array_filter( array_map( 'trim', $classes ) );
			$classes = array_map( 'strtolower', $classes );
			$extra_classes = implode( '-wrapper ', $classes );
			if ( ! empty( $extra_classes ) ) { $extra_classes .= '-wrapper'; }
		} else {
			$extra_classes = '';
		}

		if ( ! empty( $raw_classes ) ) {
			if ( is_array( $raw_classes ) ) {
				$extra_classes .= implode( ' ', $raw_classes );
			} else {
				$extra_classes .= ' ' . $raw_classes;
			}
		}
		$extra_classes = trim( $extra_classes );

		printf(
			'<%1$s class="wpmui-wrapper wpmui-%2$s-wrapper %3$s">',
			$tag,
			$type,
			$extra_classes
		);
	}

	/**
	 * Output the closing tag of an input wrapper.
	 *
	 * @since  2.0.0
	 * @internal
	 *
	 * @param  string $tag Optional. The tag name, default 'span'
	 */
	private function wrap_close( $tag = 'span' ) {
		printf( '</%1$s>', $tag );
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_label( $labels ) {
		if ( ! empty( $labels->title ) ) {
			printf(
				'<%5$s for="%1$s" class="wpmui-field-label %4$s">%2$s %3$s</%5$s>',
				esc_attr( $labels->id ),
				$labels->title,
				$labels->tooltip_code,
				esc_attr( ' wpmui-label-' . $labels->id . ' ' . $labels->class ),
				esc_attr( $labels->label_type )
			);
		}

		$this->element_desc( $labels );
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_desc( $labels ) {
		if ( ! empty( $labels->desc ) ) {
			printf(
				'<label class="wpmui-field-description %2$s" for="%3$s">%1$s</label >',
				$labels->desc,
				esc_attr( 'wpmui-description-' . $labels->id . ' ' . $labels->class ),
				esc_attr( $labels->id )
			);
		}

		if ( ! empty( $labels->before ) ) {
			printf(
				'<span class="wpmui-label-before">%s</span>',
				$labels->before
			);
		}
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.1.0
	 * @internal
	 */
	private function element_hint( $labels ) {
		if ( ! empty( $labels->after ) ) {
			printf(
				'<span class="wpmui-label-after">%s</span>',
				$labels->after
			);
		}

		if ( empty( $labels->title ) ) {
			echo $labels->tooltip_code;
		}
	}

	/**
	 * Method for outputting tooltips.
	 *
	 * @since 1.1.0
	 * @api
	 *
	 * @param  string $tip The tooltip to display.
	 * @param  bool $return Optional. If true then the HTML code is returned as
	 *                      function return value. Otherwise echo'ed.
	 *
	 * @return string|void Depending on param $return either nothing or HTML code.
	 */
	public function tooltip( $tip = '', $return = false ) {
		if ( empty( $tip ) ) { return; }

		if ( $return ) { ob_start(); }

		$this->wrap_open( 'tooltip', '', 'div' );
		?>
		<div class="wpmui-tooltip-wrapper">
		<div class="wpmui-tooltip-info"><i class="wpmui-fa wpmui-fa-info-circle"></i></div>
		<div class="wpmui-tooltip">
			<div class="wpmui-tooltip-button">&times;</div>
			<div class="wpmui-tooltip-content">
			<?php echo $tip; ?>
			</div>
		</div>
		<?php
		$this->wrap_close( 'div' );

		if ( $return ) { return ob_get_clean(); }
	}

}