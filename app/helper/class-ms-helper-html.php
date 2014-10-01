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
			'equalTo'	=> '' ,
			'field_options' => array(),
			'multiple'	=> '',
			'tooltip'   => '',
			'alt'		=> '',
			'read_only' => false,
			'placeholder' => '',
			'data_placeholder' => '',
			'data_ms' => '',
			);
		$field_args = wp_parse_args( $field_args, $defaults );
		extract( $field_args );

		if( empty( $name ) ) {
			if( ! empty( $section ) ) {
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

		// Capture to output buffer
		if ( $return ) {
			ob_start();
		}
		$placeholder = empty( $placeholder ) ? '' : "placeholder='$placeholder'";
		if( ! empty( $data_ms ) ) {
			if( empty( $data_ms['_wpnonce'] ) && ! empty( $data_ms['action'] ) ) {
				$data_ms['_wpnonce'] = wp_create_nonce( $data_ms['action'] );
			}

			$data_ms = esc_attr( json_encode( $data_ms ) );
			$data_ms = "data-ms='{$data_ms}'";
		}

		switch ( $type )
		{
			case self::INPUT_TYPE_HIDDEN:
				echo "<input class='ms-field-input ms-hidden' type='hidden' id='$id' name='$name' value='$value' />";
				break;
			case self::INPUT_TYPE_TEXT:
			case self::INPUT_TYPE_PASSWORD:
				echo ($title != '') ? "<{$label_element} class='ms-field-label ms-field-input-label'>$title {$tooltip_output}</{$label_element}>" : '';
				echo ($desc != '') ? "<span class='ms-field-description'>$desc</span>" : '';
				$max_attr = empty($maxlength)?'':"maxlength='$maxlength'";
				echo "<input class='ms-field-input ms-$type $class' type='$type' id='$id' name='$name' value='$value' $max_attr $placeholder $data_ms/>";
				echo ( empty( $title ) ) ? $tooltip_output : '';
				break;
			case self::INPUT_TYPE_TEXT_AREA:
				echo ($title != '') ? "<{$label_element} for='$id' class='ms-field-label ms-field-input-label'>$title {$tooltip_output}</{$label_element}>" : '';
				echo ($desc != '') ? "<span class='ms-field-description'>$desc</span>" : '';
				$max_attr = empty($maxlength)?'':"maxlength='$maxlength'";
				echo "<textarea class='ms-field-input ms-textarea $class' type='text' id='$id' name='$name' $read_only $data_ms>$value</textarea>";
				echo ( empty( $title ) ) ? $tooltip_output : '';
				break;
			case self::INPUT_TYPE_SELECT:
				echo ($title != '') ? "<{$label_element} for='$id' class='ms-field-label ms-field-input-label'>$title {$tooltip_output}</{$label_element}>" : '';
				echo ($desc != '') ? "<span class='ms-field-description'>$desc</span>" : '';
				$data_placeholder = empty( $data_placeholder ) ? '' : "data-placeholder='$data_placeholder'";
				echo "<select id='$id' $read_only class='ms-field-input ms-select $class' name='$name' $multiple $data_placeholder $data_ms >";
				foreach( $field_options as $key => $option ) {
					$selected = '';
					if( is_array( $value ) ) {
						if( array_key_exists( $key, $value ) ) {
							$selected = selected( $key, $key, false );
						}
					}
					else {
						$selected = selected( $key, $value, false );
					}
					$key = esc_attr( $key );
					echo "<option $selected value='$key'>$option</option>";
				}
				echo "</select>";
				echo ( empty( $title ) ) ? $tooltip_output : '';
				break;
			case self::INPUT_TYPE_RADIO:
				echo ! empty( $title ) ? "<{$label_element} class='ms-field-label ms-field-input-label'>$title {$tooltip_output}</{$label_element}>" : '';
				echo ! empty( $desc ) ? "<div class='ms-field-description'>$desc</div>" : '';
				echo "<div class='ms-radio-wrapper'>";
				foreach( $field_options as $key => $option ) {
					if( is_array( $option ) ) {
						$text = $option['text'];
						$desc = $option['desc'];
					}
					else {
						$text = $option;
						$desc = '';
					}
					$checked = ( $value == $key ) ? 'checked="checked"' : '';
					echo "<div class='ms-radio-input-wrapper $class ms-{$key}'>";
					echo "<input class='ms-field-input ms-radio $class' type='radio' id='{$id}_{$key}' name='$name' value='$key' $checked $data_ms/> ";
					echo "<label for='{$id}_{$key}'>$text</label>";
					echo ! empty( $desc ) ? "<div><label for='{$id}_{$key}' class='ms-radio-description'>$desc</label></div>" : '';
					echo "</div>";
				}
				echo ( empty( $title ) ) ? $tooltip_output : '';
				echo "</div>";
				break;
			case self::INPUT_TYPE_CHECKBOX:
				$checked = ( $value == true ) ? 'checked="checked"' : '';
				echo "<input class='ms-field-input ms-field-checkbox $class' type='checkbox' id='$id' name='$name' value='1' $checked $data_ms/>";
				if ( empty( $field_options['checkbox_position'] ) ||  'left' == $field_options['checkbox_position'] ) {
					echo "<span class='ms-label-checkbox'>";
					echo "<label for='$id' class='ms-field-checkbox-label ms-field-input-label'>$title $tooltip</label>";
				}
				echo "</span>";
				echo ($desc != '') ? "<div class='ms-field-description'>$desc</div>" : '';
				echo ( empty( $title ) ) ? $tooltip_output : '';
				break;
			case self::INPUT_TYPE_WP_EDITOR:
				echo ($title != '') ? "<{$label_element} class='ms-field-label ms-field-input-label'>$title {$tooltip_output}</{$label_element}>" : '';
				echo ($desc != '') ? "<span class='ms-field-description'>$desc</span>" : '';
				wp_editor( $value, $id, $field_options );
				break;
			case self::INPUT_TYPE_BUTTON:
				echo "<input class='ms-field-input button $class' type='button' id='$id' name='$name' value='$value' />";
				echo ( empty( $title ) ) ? $tooltip_output : '';
				break;
			case self::INPUT_TYPE_SUBMIT:
				echo "<input class='ms-field-input ms-submit button-primary $class' type='submit' id='$id' name='$name' value='$value' />";
				echo ( empty( $title ) ) ? $tooltip_output : '';
				break;
			case self::INPUT_TYPE_IMAGE:
				echo "<input type='image' id='$id' name='$name' border='0' src='$value' class='ms-field-input ms-input-image $class' alt='$alt' />";
				echo ( empty( $title ) ) ? $tooltip_output : '';
				break;
			case self::INPUT_TYPE_RADIO_SLIDER:
				$turned = ( $value ) ? 'on' : '';
				$link_url = ! empty( $url ) ? "<a href='$url'></a>" : '';

				echo ($title != '') ? "<{$label_element} class='ms-field-label ms-field-input-label'>$title {$tooltip_output}</{$label_element}>" : '';
				echo "<div class='ms-radio-slider $turned'>";
				echo "<div class='ms-toggle' $data_ms>$link_url</div>";
				if( ! $read_only ) {
					echo "<input class='ms-field-input ms-hidden' type='hidden' id='$id' name='$name' value='$value' />";
				}
				echo "</div>";
				echo ( empty( $title ) ) ? $tooltip_output : '';
				break;
			case self::TYPE_HTML_LINK:
				self::html_link( $field_args );
				break;
			case self::TYPE_HTML_SEPARATOR:
				self::html_separator();
				break;
			case self::TYPE_HTML_TEXT:
				echo "<div class='ms-html-text-wrapper'>";
				if( empty( $wrapper ) ) {
					$wrapper = 'span';
				}
				echo ($title != '') ? "<{$label_element} class='ms-text-label'>$title {$tooltip_output}</{$label_element}>" : '';
				echo "<{$wrapper} class='{$class}'>{$value}</{$wrapper}>";
				echo "</div>";
				break;
		}

		// Return the output buffer
		if ( $return ) {
			return ob_get_clean();
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
					<div class="ms-settings-desc">
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
		if( $hide_next_button ) {
			unset( $defaults['fields']['next'] );
		}

		$args = wp_parse_args( $args, $defaults );

		if( $merge_fields ) {
			foreach( $defaults['fields'] as $key => $field ) {
				if( ! isset( $args['fields'][ $key ] ) ) {
					$args['fields'][ $key ] = $field;
				}
			}

		}
		$args = apply_filters( 'ms_helper_html_settings_footer_args', $args );
		extract($args);

		?>
			<div class="ms-settings-footer">
				<form method="post" >
					<span class="ms-save-text-wrapper ms-init">
						<span class="ms-saving-text">
							<div id="floatingCirclesG">
								<div class="f_circleG" id="frotateG_01">
								</div>
								<div class="f_circleG" id="frotateG_02">
								</div>
								<div class="f_circleG" id="frotateG_03">
								</div>
								<div class="f_circleG" id="frotateG_04">
								</div>
								<div class="f_circleG" id="frotateG_05">
								</div>
								<div class="f_circleG" id="frotateG_06">
								</div>
								<div class="f_circleG" id="frotateG_07">
								</div>
								<div class="f_circleG" id="frotateG_08">
								</div>
							</div>
							<?php echo $saving_text ;?>
						</span>
						<span class="ms-saved-text"><?php echo $saved_text ;?></span>
						<?php
							foreach( $fields as $field ) {
								MS_Helper_Html::html_element( $field );
							}
						?>
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
		extract($args);

		if( ! is_array( $desc ) ) {
			$desc = array( $desc );
		}
		?>
			<div class='ms-settings-tab-title'>
				<h3><?php echo $title; ?></h3>
			</div>
			<div class="ms-settings-tab-desc-wrapper">
				<?php foreach( $desc as $description ): ?>
					<div class="ms-settings-tab-desc">
						<?php echo $description; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php
	}
	public static function settings_box( $fields_in, $title = '', $description = '', $args = array() ) {

		/** If its a fields array, great, if not, make a fields array */
		$fields = $fields_in;
		if ( ! is_array( $fields_in ) ) {
			$fields = array();
			$fields[] = $fields_in;
		}
		self::settings_box_header( $title, $description );
		foreach( $fields as $field ) {
			MS_Helper_Html::html_element( $field, false, $args );
		}
		self::settings_box_footer();
	}

	public static function settings_box_header( $title = '', $description = '' ) {
		do_action( 'ms_helper_settings_box_header_init', $title, $description );
		echo '<div class="ms-settings-box-wrapper">';
		echo '<div class="ms-settings-box">';
		if( ! empty( $title ) ) {
			echo '<h3>' . $title . '</h3>';
		}
		echo '<span class="ms-settings-description">' . $description . '</span>';
		do_action( 'ms_helper_settings_box_header_end', $title, $description );
	}

	public static function settings_box_footer() {
		do_action( 'ms_helper_settings_box_footer_init' );
		echo '</div>';
		echo '</div>';
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
		echo "<div class='ms-separator'></div>";
	}

	public static function content_desc( $descriptions ) {
		if( ! is_array( $descriptions ) ) {
			$descriptions = array( $descriptions );
		}
		foreach( $descriptions as $desc ) {
			echo "<span class='ms-content-desc'>$desc</span>";
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
				$html .= implode( '<span class="ms-bread-crumb-sep"> >> </span>', $crumbs );
				$html .= '</div>';
			}
		}
		echo apply_filters( 'ms_helper_html_bread_crumbs', $html );
	}

	public static function period_desc( $period, $class = '' ) {
		$html = sprintf( "<span class='ms-period-desc %s'> <span class='ms-period-unit'>%s</span> <span class='ms-period-type'>%s</span></span>",
			$class,
			$period['period_unit'],
			$period['period_type']
		);

		return apply_filters( 'ms_helper_html_period_desc', $html );
	}

}