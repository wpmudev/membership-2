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

class MS_Helper_Html extends MS_Helper {
	
	const INPUT_TYPE_TEXT = 'text';
	const INPUT_TYPE_TEXT_AREA = 'textarea';
	const INPUT_TYPE_SELECT = 'select';
	const INPUT_TYPE_RADIO = 'radio';
	const INPUT_TYPE_SUBMIT = 'submit';
	const INPUT_TYPE_CHECKBOX = 'checkbox';
	
	public function __construct() {

	}
	
	public static function html_input( $field_args ) {
		
		$defaults = array(
			'id'      	=> '',
			'section'	=> 'section',
			'title'   	=> '',
			'desc'    	=> '',
			'value'     => '',
			'type'    	=> 'text',
			'class'   	=> '',
			'maxlength' => '',
			'equalTo'	=> '' ,
			'field_options' => array(),
			);
		extract( wp_parse_args( $field_args, $defaults ) );
	
		switch ( $type )
		{
			case self::INPUT_TYPE_TEXT:
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				echo ($desc != '') ? "<span class='ms-field-description'>$desc</span><br />" : '';
				$max_attr = empty($maxlength)?'':"maxlength='$maxlength'";
				echo "<input class='ms-field-input ms-text $class' type='text' id='$id' name='" . $section . "[$id]' value='$value' $max_attr/>";
				break;
			case self::INPUT_TYPE_TEXT_AREA:
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				echo ($desc != '') ? "<span class='ms-field-description'>$desc</span><br />" : '';
				$max_attr = empty($maxlength)?'':"maxlength='$maxlength'";
				echo "<textarea class='ms-field-input ms-textarea $class' type='text' id='$id' name='" . $section . "[$id]'>$value</textarea>";
				break;
			case self::INPUT_TYPE_SELECT:
				echo ($title != '') ? "<span class='ms-field-label'>$title</span>" : '';
				echo "<select id='$id' class='ms-field-input ms-select $class' name='". $section. "[$id]'>";
				foreach ($field_options as $key => $option ) {
					$selected = selected( $key, $value, false );
					echo "<option $selected value='$key'>$option</option>";
				}
				echo "</select>";
				break;
			case self::INPUT_TYPE_CHECKBOX:
				$checked = checked( $value, true, false );
				echo "<div class='ms-field-container'>";
				echo "<span class=''>";
				echo "<input class='ms-field-input ms-field-checkbox $class' type='checkbox' id='$id' name='" . $section . "[$id]' value='1' $checked/>";
				echo "</span>";
				echo "<span class='vds_label_check'>";
				echo "<label for='$id'>$title</label>";
				echo "</span>";
				echo "</div>";
				break;
		
		}		
	}
	public static function html_submit( $field_args = array() ) {
		$defaults = array(
			'id'      	=> 'submit',
			'value'     => __('Save Changes', MS_TEXT_DOMAIN ),
			'class'   	=> 'button button-primary',
			);
		extract( wp_parse_args( $field_args, $defaults ) );
		
		echo "<input class='ms-field-input ms-submit $class' type='submit' id='$id' name='$id' value='$value'/>";
	}
}