<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Helper class for rendering HTML components.
 *
 * Methods for creating form INPUT components.
 * Method for creating vertical tabbed navigation.
 *
 * @todo Create add methods to parent class or remove 'extends MS_Helper' to use standalone.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_Helper_Html extends MS_Helper {

	/** Constants for HTML elements. */
	const INPUT_TYPE_HIDDEN = 'hidden';
	const INPUT_TYPE_TEXT = 'text';
	const INPUT_TYPE_DATEPICKER = 'datepicker';
	const INPUT_TYPE_TEXT_AREA = 'textarea';
	const INPUT_TYPE_SELECT = 'select';
	const INPUT_TYPE_RADIO = 'radio';
	const INPUT_TYPE_SUBMIT = 'submit';
	const INPUT_TYPE_BUTTON = 'button';
	const INPUT_TYPE_CHECKBOX = 'checkbox';
	const INPUT_TYPE_WP_EDITOR = 'wp_editor';
	const INPUT_TYPE_IMAGE = 'image';
	const INPUT_TYPE_PASSWORD = 'password';
	const INPUT_TYPE_RADIO_SLIDER = 'radio_slider';

	const TYPE_HTML_LINK = 'html_link';
	const TYPE_HTML_SEPARATOR = 'html_separator';
	const TYPE_HTML_TEXT = 'html_text';

	/**
	 * Method for creating FORM elements/fields.
	 *
	 * Pass in array with field arguments. See $defaults for argmuments.
	 * Use constants to specify field type. e.g. MS_Helper_Html::INPUT_TYPE_TEXT
	 *
	 * @since 4.0.0
	 *
	 * @return void But does output HTML.
	 */
	public static function html_element( $field_args, $return = false, $input_args = array() ) {

		/** Field arguments */
		$defaults = array(
			'id'        => '',
			'name'		=> '',
			'section'	=> '',
			'title'     => '',
			'desc'      => '',
			'value'     => '',
			'type'      => 'text',
			'class'     => '',
			'maxlength' => '',
			'equalTo'	=> '',
			'field_options' => array(),
			'multiple'	=> false,
			'tooltip'   => '',
			'alt'		=> '',
			'read_only' => false,
			'placeholder' => '',
			'data_placeholder' => '',
			'data_ms' => '',
			);
		$field_args = wp_parse_args( $field_args, $defaults );
		extract( $field_args );

		if ( empty( $name ) ) {
			if ( ! empty( $section ) ) {
				$name = $section . "[$id]";
			}
			else {
				$name = $id;
			}
		}

		/* Input arguments */
		$input_defaults = array(
			'label_element' => 'label',
		);
		extract( wp_parse_args( $input_args, $input_defaults ) );

		$tooltip_output = MS_Helper_Html::tooltip( $tooltip, true );

		$attr_placeholder = '';
		$attr_data_placeholder = '';

		if ( '' !== $placeholder && false !== $placeholder ) {
			$attr_placeholder = 'placeholder="' . esc_attr( $placeholder ) . '" ';
		}
		if ( '' !== $data_placeholder && false !== $data_placeholder ) {
			$attr_data_placeholder = 'data-placeholder="' . esc_attr( $data_placeholder ) . '" ';
	}

		if ( ! empty( $data_ms ) ) {
			if ( empty( $data_ms['_wpnonce'] ) && ! empty( $data_ms['action'] ) ) {
				$data_ms['_wpnonce'] = wp_create_nonce( $data_ms['action'] );
			}

			$data_ms = 'data-ms="' . esc_attr( json_encode( $data_ms ) ) . '" ';
		}

		$max_attr = empty( $maxlength ) ? '' : 'maxlength="' . esc_attr( $maxlength ) . '" ';

		$read_only = empty( $read_only ) ? '' : 'readonly="readonly" ';

		$multiple = empty( $multiple ) ? '' : 'multiple="multiple" ';


		// Capture to output buffer
		if ( $return ) {
			ob_start();
		}
		switch ( $type ) {

			case self::INPUT_TYPE_HIDDEN:
				printf(
					'<input class="ms-field-input ms-hidden" type="hidden" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case self::INPUT_TYPE_TEXT:
			case self::INPUT_TYPE_PASSWORD:
				self::html_element_label( $title, $label_element, $id, $tooltip_output );
				self::html_element_desc( $desc );

				printf(
					'<input class="ms-field-input ms-%1$s %2$s" type="%1$s" id="%3$s" name="%4$s" value="%5$s" %6$s />',
					esc_attr( $type ),
					esc_attr( $class ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					$max_attr . $attr_placeholder . $data_ms
				);

				self::html_element_hint( $title, $tooltip_output );
				break;

			case self::INPUT_TYPE_DATEPICKER:
				self::html_element_label( $title, $label_element, $id, $tooltip_output );
				self::html_element_desc( $desc );

				printf(
					'<span class="ms-datepicker-wrapper ms-field-input"><input class="ms-datepicker %1$s" type="text" id="%2$s" name="%3$s" value="%4$s" %5$s /><i class="ms-icon fa fa-calendar"></i></span>',
					esc_attr( $class ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					$max_attr . $attr_placeholder . $data_ms
				);

				self::html_element_hint( $title, $tooltip_output );
				break;

			case self::INPUT_TYPE_TEXT_AREA:
				self::html_element_label( $title, $label_element, $id, $tooltip_output );
				self::html_element_desc( $desc );

				printf(
					'<textarea class="ms-field-input ms-textarea %1$s" type="text" id="%2$s" name="%3$s" %4$s>%5$s</textarea>',
					esc_attr( $class ),
					esc_attr( $id ),
					esc_attr( $name ),
					$read_only . $attr_placeholder . $data_ms,
					esc_textarea( $value )
				);

				self::html_element_hint( $title, $tooltip_output );
				break;

			case self::INPUT_TYPE_SELECT:
				self::html_element_label( $title, $label_element, $id, $tooltip_output );
				self::html_element_desc( $desc );

				printf(
					'<select id="%1$s" class="ms-field-input ms-select %2$s" name="%3$s" %4$s>',
					esc_attr( $id ),
					esc_attr( $class ),
					esc_attr( $name ),
					$multiple . $read_only . $data_placeholder . $data_ms
				);
				foreach( $field_options as $key => $option ) {
					$selected = '';
					if( is_array( $value ) ) {
						$selected = selected( array_key_exists( $key, $value ), true, false );
					}
					else {
						$selected = selected( $key, $value, false );
					}
					printf(
						'<option %1$s value="%2$s">%3$s</option>',
						$selected,
						esc_attr( $key ),
						$option
					);
				}
				echo '</select>';

				self::html_element_hint( $title, $tooltip_output );
				break;

			case self::INPUT_TYPE_RADIO:
				self::html_element_label( $title, $label_element, $id, $tooltip_output );
				self::html_element_desc( $desc );

				printf(
					'<div class="ms-radio-wrapper wrapper-%1$s">',
					esc_attr( $id )
				);
				foreach( $field_options as $key => $option ) {
					if( is_array( $option ) ) {
						$item_text = $option['text'];
						$item_desc = $option['desc'];
					}
					else {
						$item_text = $option;
						$item_desc = '';
					}
					$checked = checked( $value, $key, false );
					$radio_desc = '';
					if( ! empty( $item_desc ) ) {
						$radio_desc = sprintf( '<div class="ms-input-description"><p>%1$s</p></div>', $item_desc );
					}
					printf(
						'<div class="ms-radio-input-wrapper %1$s ms-%2$s"><label class="ms-field-input-label"><input class="ms-field-input ms-radio" type="radio" name="%3$s" id="%4$s_%2$s" value="%2$s" %5$s /><div class="ms-radio-caption">%6$s</div>%7$s</label></div>',
						esc_attr( $class ),
						esc_attr( $key ),
						esc_attr( $name ),
						esc_attr( $id ),
						$data_ms . $checked,
						$item_text,
						$radio_desc
					);
				}

				self::html_element_hint( $title, $tooltip_output );
				echo '</div>';
				break;

			case self::INPUT_TYPE_CHECKBOX:
				$checked = checked( $value, true, false );

				$item_desc = '';
				if( ! empty( $desc ) ) {
					$item_desc = sprintf( '<div class="ms-field-description"><p>%1$s</p></div>', $desc );
				}

				$item_label = '';
				if ( empty( $field_options['checkbox_position'] ) ||  'left' == $field_options['checkbox_position'] ) {
					$item_label = sprintf(
						'<div class="ms-checkbox-caption">%1$s %2$s</div>',
						$title,
						$tooltip
					);
				}

				printf(
					'<label class="ms-checkbox-wrapper ms-field-input-label"><input id="%1$s" class="ms-field-input ms-checkbox %2$s" type="checkbox" name="%3$s" value="1" %4$s />%5$s %6$s</label>',
					esc_attr( $id ),
					esc_attr( $class ),
					esc_attr( $name ),
					$data_ms . $checked,
					$item_label,
					$item_desc
				);

				self::html_element_hint( $title, $tooltip_output );
				break;

			case self::INPUT_TYPE_WP_EDITOR:
				self::html_element_label( $title, $label_element, $id, $tooltip_output );
				self::html_element_desc( $desc );

				wp_editor( $value, $id, $field_options );
				break;

			case self::INPUT_TYPE_BUTTON:
				printf(
					'<button class="ms-field-input button %1$s" type="button" id="%2$s" name="%3$s" %5$s>%4$s</button>',
					esc_attr( $class ),
					esc_attr( $id ),
					esc_attr( $name ),
					$value,
					$data_ms
				);

				self::html_element_hint( $title, $tooltip_output );
				break;

			case self::INPUT_TYPE_SUBMIT:
				printf(
					'<button class="ms-field-input ms-submit button-primary %1$s" type="submit" id="%2$s" name="%3$s" %5$s>%4$s</button>',
					esc_attr( $class ),
					esc_attr( $id ),
					esc_attr( $name ),
					$value,
					$data_ms
				);

				self::html_element_hint( $title, $tooltip_output );
				break;

			case self::INPUT_TYPE_IMAGE:
				printf(
					'<input type="image" class="ms-field-input ms-input-image %1$s" id="%2$s" name="%3$s" border="0" src="%4$s" alt="%5$s" %6$s/>',
					esc_attr( $class ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_url( $value ),
					esc_attr( $alt ),
					$data_ms
				);

				self::html_element_hint( $title, $tooltip_output );
				break;

			case self::INPUT_TYPE_RADIO_SLIDER:
				$turned = ( $value ) ? 'on' : '';
				$link_url = ! empty( $url ) ? '<a href="' . esc_url( $url ) . '"></a>' : '';

				$attr_input = '';
				if( ! $read_only ) {
					$attr_input = sprintf(
						'<input class="ms-field-input ms-hidden" type="hidden" id="%1$s" name="%2$s" value="%3$s" />',
						esc_attr( $id ),
						esc_attr( $name ),
						esc_attr( $value )
					);
				}

				self::html_element_label( $title, $label_element, $id, $tooltip_output );
				self::html_element_desc( $desc );

				printf(
					'<div class="ms-radio-slider %1$s ms-slider-%5$s"><div class="ms-toggle" %2$s>%3$s</div>%4$s</div>',
					esc_attr( $turned ),
					$data_ms,
					$link_url,
					$attr_input,
					esc_attr( $id )
				);

				self::html_element_hint( $title, $tooltip_output );
				break;

			case self::TYPE_HTML_LINK:
				self::html_link( $field_args );
				break;

			case self::TYPE_HTML_SEPARATOR:
				self::html_separator();
				break;

			case self::TYPE_HTML_TEXT:
				if( empty( $wrapper ) ) { $wrapper = 'span'; }
				echo '<div class="ms-html-text-wrapper">';

				self::html_element_label( $title, $label_element, $id, $tooltip_output );

				printf(
					'<%1$s class="%2$s">%3$s</%1$s>',
					$wrapper,
					esc_attr( $class ),
					$value
				);

				self::html_element_hint( $title, $tooltip_output );
				echo '</div>';
				break;
		}

		// Return the output buffer
		if ( $return ) {
			return ob_get_clean();
		}

	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.0.0
	 */
	private static function html_element_label( $title, $label_element, $id, $tooltip_output ) {
		if( ! empty( $title ) ) {
			printf(
				'<%1$s for="%2$s" class="ms-field-label ms-field-input-label">%3$s %4$s</%1$s>',
				$label_element,
				esc_attr( $id ),
				$title,
				$tooltip_output
			);
		}
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.0.0
	 */
	private static function html_element_desc( $desc ) {
		if( $desc != '' ) {
			printf(
				'<span class="ms-field-description">%1$s</span>',
				$desc
			);
		}
	}

	/**
	 * Helper function used by `html_element`
	 *
	 * @since  1.0.0
	 */
	private static function html_element_hint( $title, $tooltip_output ) {
		if ( empty( $title ) ) {
			echo $tooltip_output;
		}
	}


	public static function settings_header( $args = null ) {
		$defaults = array(
				'title' => '',
				'title_icon_class' => '',
				'desc' => '',
				'bread_crumbs' => null,
		);
		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'ms_helper_html_settings_header_args', $args );
		extract($args);

		if( ! is_array( $desc ) ) {
			$desc = array( $desc );
		}
		?>
			<?php MS_Helper_Html::bread_crumbs( $bread_crumbs );?>
			<h2 class='ms-settings-title'>
				<i class="<?php echo esc_attr( $title_icon_class ); ?>"></i>
				<?php echo $title; ?>
			</h2>
			<div class="ms-settings-desc-wrapper">
				<?php foreach( $desc as $description ): ?>
					<div class="ms-settings-desc ms-description">
						<?php echo $description; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php
	}

	public static function settings_footer( $args = null, $merge_fields = true, $hide_next_button = false ) {
		$action = 'next';
		$nonce = wp_create_nonce( $action );
		$defaults = array(
			'saving_text' => __( 'Saving changes...', MS_TEXT_DOMAIN ),
			'saved_text' => __( 'All Changes Saved', MS_TEXT_DOMAIN ),
			'fields' => array(
				'next' => array(
						'id' => 'next',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Next', MS_TEXT_DOMAIN ),
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $action,
				),
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $nonce,
				),
			),
		);
		if ( $hide_next_button ) {
			unset( $defaults['fields']['next'] );
		}

		$args = wp_parse_args( $args, $defaults );

		if ( $merge_fields ) {
			foreach ( $defaults['fields'] as $key => $field ) {
				if ( ! isset( $args['fields'][ $key ] ) ) {
					$args['fields'][ $key ] = $field;
				}
			}
		}

		$args = apply_filters( 'ms_helper_html_settings_footer_args', $args );
		extract( $args );

		?>
			<div class="ms-settings-footer">
				<form method="post" >
					<span class="ms-save-text-wrapper ms-init">
						<?php
							foreach ( $fields as $field ) {
								MS_Helper_Html::html_element( $field );
							}
						?>
						<span class="ms-saving-text">
							<div class="loading-animation"></div>
							<?php echo $saving_text; ?>
						</span>
						<span class="ms-saved-text"><?php echo $saved_text ;?></span>
					</span>
				</form>
			</div>
		<?php
	}

	public static function settings_tab_header( $args = null ) {
		$defaults = array(
				'title' => '',
				'desc' => '',
		);
		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'ms_helper_html_settings_header_args', $args );
		extract( $args );

		if( ! is_array( $desc ) ) {
			$desc = array( $desc );
		}
		?>
		<div class="ms-header">
			<div class="ms-settings-tab-title">
				<h3><?php echo $title; ?></h3>
			</div>
			<div class="ms-settings-description">
				<?php foreach ( $desc as $description ): ?>
					<div class="ms-description">
						<?php echo $description; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Echo a single content box including the header and footer of the box.
	 * The fields-list will be used to render the box body.
	 *
	 * @since  1.0.0
	 * @param  array $fields_in List of fields to render
	 * @param  string $title Box title
	 * @param  string $description Description to display
	 * @param  array $args Optional arguments used to render the fields
	 * @param  string $state Toggle-state of the box: static/open/closed
	 */
	public static function settings_box( $fields_in, $title = '', $description = '', $args = array(), $state = 'static' ) {
		/** If its a fields array, great, if not, make a fields array */
		$fields = $fields_in;
		if ( ! is_array( $fields_in ) ) {
			$fields = array();
			$fields[] = $fields_in;
		}
		self::settings_box_header( $title, $description, $state );
		foreach ( $fields as $field ) {
			MS_Helper_Html::html_element( $field, false, $args );
		}
		self::settings_box_footer();
	}

	/**
	 * Echo the header of a content box. That box has a similar layout to a
	 * normal WordPress meta-box.
	 * The box has a title and desription and can optionally be collapsable.
	 *
	 * @since  1.0.0
	 * @param  string $title Box title displayed in the top
	 * @param  string $description Description to display
	 * @param  string $state Toggle-state of the box: static/open/closed
	 */
	public static function settings_box_header( $title = '', $description = '', $state = 'static' ) {
		do_action( 'ms_helper_settings_box_header_init', $title, $description, $state );

		$handle = '';
		if ( $state !== 'static' ) {
			$state = ('closed' === $state ? 'closed' : 'open');
			$handle = sprintf(
				'<div class="handlediv" title="%s"></div>',
				__( 'Click to toggle' ) // Intentionally no text-domain, so we use WordPress default translation.
			);
		}
		$box_class = $state;
		if ( ! strlen( $title ) && ! strlen( $description ) ) {
			$box_class .= ' nohead';
		}

		?>
		<div class="ms-settings-box-wrapper">
			<div class="ms-settings-box <?php echo esc_attr( $box_class ); ?>">
				<div class="ms-header">
					<?php echo $handle; ?>
					<?php if ( ! empty( $title ) ) : ?>
						<h3><?php echo $title; ?></h3>
					<?php endif; ?>
					<span class="ms-settings-description ms-description"><?php echo $description; ?></span>
				</div>
				<div class="inside">
		<?php
		do_action( 'ms_helper_settings_box_header_end', $title, $description, $state );
	}

	/**
	 * Echo the footer of a content box.
	 *
	 * @since  1.0.0
	 */
	public static function settings_box_footer() {
		do_action( 'ms_helper_settings_box_footer_init' );
		?>
		</div> <!-- .inside -->
		</div> <!-- .ms-settings-box -->
		</div> <!-- .ms-settings-box-wrapper -->
		<?php
		do_action( 'ms_helper_settings_box_footer_end' );
	}

	/**
	 * Method for creating submit button.
	 *
	 * Pass in array with field arguments. See $defaults for argmuments.
	 *
	 * @since 4.0.0
	 *
	 * @return void But does output HTML.
	 */
	public static function html_submit( $field_args = array() ) {
		$defaults = array(
			'id'        => 'submit',
			'value'     => __('Save Changes', MS_TEXT_DOMAIN ),
			'class'     => 'button button-primary',
			);
		extract( wp_parse_args( $field_args, $defaults ) );

		echo "<input class='ms-field-input ms-submit $class' type='submit' id='$id' name='$id' value='$value'/>";
	}

	/**
	 * Method for creating html link.
	 *
	 * Pass in array with link arguments. See $defaults for argmuments.
	 *
	 * @since 4.0.0
	 *
	 * @return void But does output HTML.
	 */
	public static function html_link( $args = array(), $return = false ) {
		$defaults = array(
			'id'        => '',
			'title'		=> '',
			'value'     => '',
			'class'     => '',
			'url'		=> '',
			);
		extract( wp_parse_args( $args, $defaults ) );
		$url = esc_url( $url );
		$html = "<a id='$id' title='$title' class='ms-link $class' href='$url'>$value</a>";
		if( $return ) {
			return $html;
		}
		else {
			echo $html;
		}
	}

	/**
	 * Method for outputting vertical tabs.
	 *
	 * Returns the active tab key. Vertical tabs need to be wrapped in additional code.
	 *
	 * @since 4.0.0
	 *
	 * @return string Active tab.
	 */
	public static function html_admin_vertical_tabs( $tabs, $active_tab = null ) {

		reset($tabs);
		$first_key = key($tabs);

		/** Setup navigation tabs. */
		if( empty( $active_tab ) ) {
			$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : $first_key;
		}

		if ( !array_key_exists( $active_tab, $tabs ) ) { $active_tab = $first_key; }

		/** Render tabbed interface. */
		?>
			<div class='ms-tab-container'>
				<ul id="sortable-units" class="ms-tabs" style="">
					<?php foreach( $tabs as $tab_name => $tab ) { ?>
						<li class="ms-tab <?php echo $tab_name == $active_tab ? 'active' : ''; ?> ">
							<a class="ms-tab-link" href="<?php echo $tab['url']; ?>"><?php echo $tab['title']; ?></a>
						</li>
					<?php } ?>
				</ul>
			</div>
		<?php

		/** Return current active tab. */
		return $active_tab;
	}

	/**
	 * Method for outputting tooltips.
	 *
	 * @since 4.0.0
	 *
	 * @return void But does output HTML.
	 */
	public static function tooltip( $tip = '', $return = false ) {
		if ( empty( $tip ) ) {
			return;
		}

		if ( $return ) {
			ob_start();
		}
		?>
		<div class="ms-tooltip-wrapper">
		<div class="ms-tooltip-info"><i class="fa fa-info-circle"></i></div>
		<div class="ms-tooltip">
			<div class="ms-tooltip-button">&times;</div>
			<div class="ms-tooltip-content">
			<?php echo $tip; ?>
			</div>
		</div>
		</div>
		<?php
		if ( $return ) {
			return ob_get_clean();
		}
	}

	public static function html_separator() {
		echo '<div class="ms-separator"></div>';
	}

	public static function content_desc( $descriptions ) {
		if( ! is_array( $descriptions ) ) {
			$descriptions = array( $descriptions );
		}
		foreach( $descriptions as $desc ) {
			echo '<span class="ms-content-desc">' . $desc . '</span>';
		}
	}

	public static function bread_crumbs( $bread_crumbs ) {
		$crumbs = array();
		$html = '';
		if( is_array( $bread_crumbs ) ) {
			foreach( $bread_crumbs as $key => $bread_crumb ) {
				if( ! empty( $bread_crumb['url'] ) ) {
					$crumbs[] = sprintf( '<span class="ms-bread-crumb-%s"><a href="%s">%s</a></span>', $key, $bread_crumb['url'], $bread_crumb['title'] );
				}
				elseif( ! empty( $bread_crumb['title'] ) ) {
					$crumbs[] = sprintf( '<span class="ms-bread-crumb-%s">%s</span>', $key, $bread_crumb['title'] );
				}
			}
			if( count( $crumbs ) > 0 ) {
				$html = '<div class="ms-bread-crumb">';
				$html .= implode( '<span class="ms-bread-crumb-sep"> &raquo; </span>', $crumbs );
				$html .= '</div>';
			}
		}
		echo apply_filters( 'ms_helper_html_bread_crumbs', $html );
	}

	public static function period_desc( $period, $class = '' ) {
		$html = sprintf( '<span class="ms-period-desc %s"> <span class="ms-period-unit">%s</span> <span class="ms-period-type">%s</span></span>',
			$class,
			$period['period_unit'],
			$period['period_type']
		);

		return apply_filters( 'ms_helper_html_period_desc', $html );
	}

}