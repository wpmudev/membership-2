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
 * Renders Admin Bar.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_View_Admin_Bar extends MS_View {
	
	protected $simulate_period_unit;
	
	protected $simulate_period_type;
	
	protected $simulate_date;
	
	protected $fields;
	
	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @todo Could use callback functions to call dynamic methods from within the helper, thus
	 * creating the navigation with a single method call and passing method pointers in the $tabs array.
	 *
	 * @since 4.0.0
	 *
	 * @return object
	 */
	public function to_html() {		
		$this->prepare_fields();
		ob_start();
		?>
		<form action="" method="post">
			<?php  
				if( isset( $this->simulate_date ) ) {
					MS_Helper_Html::html_input( $this->fields['simulate_date'] );
				}
				elseif( isset( $this->simulate_period_type ) ) {
					MS_Helper_Html::html_input( $this->fields['simulate_period_unit'] );
					MS_Helper_Html::html_input( $this->fields['simulate_period_type'] );
				}
				MS_Helper_Html::html_input( $this->fields['simulate_submit'] );
			?>
		</form>
		<?php
		$html = ob_get_clean();
		return $html;
	}
	public function prepare_fields() {
		$this->fields = array(
				'simulate_period_unit' => array(
						'id' => 'simulate_period_unit',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $this->simulate_period_unit,
						'class' => 'ms-admin-bar-period-unit',
				),
				'simulate_period_type' => array(
						'id' => 'simulate_period_type',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'value' => $this->simulate_period_type,
						'field_options' => MS_Helper_Period::get_periods(),
						'class' => 'ms-admin-bar-period-type',
				),
				'simulate_date' => array(
						'id' => 'simulate_date',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $this->simulate_date,
						'class' => 'ms-admin-bar-date ms-date',
				),
				'simulate_submit' => array(
						'id' => 'simulate_submit',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'OK', MS_TEXT_DOMAIN ),
						'class' => 'ms-admin-bar-submit',
				),
		);
	}
}