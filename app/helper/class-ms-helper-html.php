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
	public static function html_input( $field_args, $return = false, $input_args = array() ) {
		
		/** Field arguments */
		$defaults = array(
			'id'      	=> '',
			'name'		=> '',
			'section'	=> '',
			'title'   	=> '',
			'desc'    	=> '',
			'value'     => '',
			'type'    	=> 'text',
			'class'   	=> '',
			'maxlength' => '',
			'equalTo'	=> '' ,
			'field_options' => array(),
			'multiple'	=> '',
			'tooltip'   => '',
		 	'alt'		=> '',
			);
		extract( wp_parse_args( $field_args, $defaults ) );
	
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
			'label_element' => 'span',
			'checkbox_position' => 'left',
		);
		extract( wp_parse_args( $input_args, $input_defaults ) );
		
		$tooltip_output = MS_Helper_Html::tooltip( $tooltip, true );
		
		// Capture to output buffer
		if ( $return ) {
			ob_start();
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
				echo "<input class='ms-field-input ms-$type $class' type='$type' id='$id' name='$name' value='$value' $max_attr />";
				echo ( empty( $title ) ) ? $tooltip_output : '';
				break;
			case self::INPUT_TYPE_TEXT_AREA:
				echo ($title != '') ? "<{$label_element} class='ms-field-label ms-field-input-label'>$title {$tooltip_output}</{$label_element}>" : '';
				echo ($desc != '') ? "<span class='ms-field-description'>$desc</span>" : '';
				$max_attr = empty($maxlength)?'':"maxlength='$maxlength'";
				echo "<textarea class='ms-field-input ms-textarea $class' type='text' id='$id' name='$name'>$value</textarea>";
				echo ( empty( $title ) ) ? $tooltip_output : '';				
				break;
			case self::INPUT_TYPE_SELECT:
				echo ($title != '') ? "<{$label_element} class='ms-field-label ms-field-input-label'>$title {$tooltip_output}</{$label_element}>" : '';
				echo "<select id='$id' class='ms-field-input ms-select $class' name='$name' $multiple >";
				foreach ($field_options as $key => $option ) {
					$selected = selected( $key, $value, false );
					$key = esc_attr( $key );
					echo "<option $selected value='$key'>$option</option>";
				}
				echo "</select>";
				echo ( empty( $title ) ) ? $tooltip_output : '';				
				break;
			case self::INPUT_TYPE_RADIO:
				echo ($title != '') ? "<{$label_element} class='ms-field-label ms-field-input-label'>$title {$tooltip_output}</{$label_element}>" : '';
				foreach ($field_options as $key => $option ) {
					$checked = checked( $key, $value, false );
					echo "<input class='ms-field-input ms-radio $class' type='radio' id='{$id}_{$key}' name='$name' value='$key' $checked /> ";
					echo "<label for='{$id}_{$key}'>$option</label>";
				}
				echo ( empty( $title ) ) ? $tooltip_output : '';				
				break;
			case self::INPUT_TYPE_CHECKBOX:
				$checked = checked( $value, true, false );
				echo "<div class='ms-field-container'>";
				if ( 'right' == $checkbox_position ) {
					echo "<span class='vds_label_check'>";
					echo "<label for='$id'><{$label_element} class='ms-field-label ms-field-input-label'>$title $tooltip</{$label_element}></label>";					
				}
				echo "<span class=''>";
				echo "<input class='ms-field-input ms-field-checkbox $class' type='checkbox' id='$id' name='$name' value='1' $checked />";
				echo "</span>";
				if ( 'right' != $checkbox_position ) {
					echo "<span class='vds_label_check'>";
					echo "<label for='$id'><{$label_element} class='ms-field-label ms-field-input-label'>$title $tooltip</{$label_element}></label>";					
				}
				echo "</span>";
				echo "</div>";
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
		    	echo "<div class='toggle'>$link_url</div>";
				echo "<input class='ms-field-input ms-hidden' type='hidden' id='$id' name='$name' value='$value' />";
				echo "</div>";
				echo ( empty( $title ) ) ? $tooltip_output : '';				
				break;
		}		
		
		// Return the output buffer
		if ( $return ) {
			return ob_get_clean();
		}

	}
	
	public static function settingsbox( $fields_in, $title = '', $description = '', $args = array() ) {
		
		// If its a fields array, great, if not, make a fields array
		$fields = $fields_in;
		if ( ! is_array( $fields_in[0] ) ) {
			$fields = array();
			$fields[] = $fields_in;
		}
		
		// Grab the title and tooltip of the first field if not set.
		$the_title = $title;
		if ( '' == $title ) {
			$the_title = $fields[0]['title'];
			$fields[0]['title'] = '';
		} 
		
		$the_description = $description;
		if ( empty ( $description ) ) {
			$the_description = $fields[0]['tooltip'];
			$fields[0]['tooltip'] = '';
		} 
		
		echo '<div class="ms-settings-box-wrapper">';
		echo '<div class="ms-settings-box">';
		echo '<h3>' . $the_title . '</h3>';
		echo '<div class="inside">';
		echo '<span class="ms-field-label">' . $the_description . '</span>';
		foreach( $fields as $field ) {
			MS_Helper_Html::html_input( $field, false, $args );
		}
		echo '</div>';
		echo '</div>';
		echo '</div>';
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
			'id'      	=> 'submit',
			'value'     => __('Save Changes', MS_TEXT_DOMAIN ),
			'class'   	=> 'button button-primary',
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
			'id' 		=> '',
			'title'		=> '',
			'value'     => '',
			'class'   	=> '',
			'url'		=> '',	
			);
		extract( wp_parse_args( $args, $defaults ) );
		$url = esc_url( $url );
		$html = "<a id='$id' title='$title' class='$class' href='$url'>$value</a>";
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
	public static function html_admin_vertical_tabs( $tabs ) {
		
		reset($tabs);
		$first_key = key($tabs);

		/** Setup navigation tabs. */		
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : $first_key;
		
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
	
}