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
	public static function html_input( $field_args ) {
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
		switch ( $type )
		{
			case self::INPUT_TYPE_HIDDEN:
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				echo "<input class='ms-field-input ms-hidden' type='hidden' id='$id' name='$name' value='$value' />";
				break;
			case self::INPUT_TYPE_TEXT:
			case self::INPUT_TYPE_PASSWORD:
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				echo ($desc != '') ? "<span class='ms-field-description'>$desc</span><br />" : '';
				$max_attr = empty($maxlength)?'':"maxlength='$maxlength'";
				echo "<input class='ms-field-input ms-$type $class' type='$type' id='$id' name='$name' value='$value' $max_attr />";
				break;
			case self::INPUT_TYPE_TEXT_AREA:
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				echo ($desc != '') ? "<span class='ms-field-description'>$desc</span><br />" : '';
				$max_attr = empty($maxlength)?'':"maxlength='$maxlength'";
				echo "<textarea class='ms-field-input ms-textarea $class' type='text' id='$id' name='$name'>$value</textarea>";
				break;
			case self::INPUT_TYPE_SELECT:
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				echo "<select id='$id' class='ms-field-input ms-select $class' name='$name' $multiple >";
				foreach ($field_options as $key => $option ) {
					$selected = selected( $key, $value, false );
					$key = esc_attr( $key );
					echo "<option $selected value='$key'>$option</option>";
				}
				echo "</select>";
				break;
			case self::INPUT_TYPE_RADIO:
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				foreach ($field_options as $key => $option ) {
					$checked = checked( $key, $value, false );
					echo "<input class='ms-field-input ms-radio $class' type='radio' id='{$id}_{$key}' name='$name' value='$key' $checked /> ";
					echo "<label for='{$id}_{$key}'>$option</label>";
				}
				break;
			case self::INPUT_TYPE_CHECKBOX:
				$checked = checked( $value, true, false );
				echo "<div class='ms-field-container'>";
				echo "<span class=''>";
				echo "<input class='ms-field-input ms-field-checkbox $class' type='checkbox' id='$id' name='$name' value='1' $checked />";
				echo "</span>";
				echo "<span class='vds_label_check'>";
				echo "<label for='$id'>$title</label>";
				echo "</span>";
				echo "</div>";
				break;
			case self::INPUT_TYPE_WP_EDITOR:
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				wp_editor( $value, $id, $field_options );
				break;
			case self::INPUT_TYPE_BUTTON:
				echo "<input class='ms-field-input button $class' type='button' id='$id' name='$name' value='$value' />";
				break;
			case self::INPUT_TYPE_SUBMIT:
				echo "<input class='ms-field-input ms-submit button-primary $class' type='submit' id='$id' name='$name' value='$value' />";
				break;
			case self::INPUT_TYPE_IMAGE:
				echo "<input type='image' name='$name' border='0' src='$value' class='ms-field-input ms-input-image $class' alt='$alt' />";
				break;
			case self::INPUT_TYPE_RADIO_SLIDER:
				$turned = ( $value ) ? 'on' : ''; 
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				echo "<div class='ms-radio-slider $turned'>";
		    	echo "<div class='toggle'></div>";
				echo "<input class='ms-field-input ms-hidden' type='hidden' id='$id' name='$name' value='$value' />";
				echo "</div>";
				break;
				
		}		
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
	public static function html_link( $args = array() ) {
		$defaults = array(
			'id' 		=> '',
			'title'		=> '',
			'value'     => '',
			'class'   	=> '',
			'url'		=> '',	
			);
		extract( wp_parse_args( $args, $defaults ) );
		$url = esc_url( $url );
		echo "<a id='$id' title='$title' class='$class' href='$url'>$value</a>";
	}
	/**
	 * Method for outputting vertical tabs. 
	 *
	 * Returns the active tab key. Vertical tabs need to be wrapped in additional code.
	 *
	 * @since 4.0.0
	 *
	 * @return void But does output HTML.
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
}