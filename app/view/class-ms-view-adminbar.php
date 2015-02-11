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
 * Renders Admin Bar's simulation.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 */
class MS_View_Adminbar extends MS_View {

	protected $data;

	/**
	 * Overrides parent's to_html() method.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();

		ob_start();
		?>
		<form action="" method="post">
			<?php
			if ( MS_Model_Simulate::TYPE_DATE == $this->data['simulate_type'] ) {
				MS_Helper_Html::html_element( $fields['simulate_date'] );
			}
			MS_Helper_Html::html_element( $fields['simulate_type'] );
			MS_Helper_Html::html_element( $fields['simulate_submit'] );
			?>
		</form>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_admin_bar_to_html', $html, $this );
	}

	/**
	 * Prepare html fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function prepare_fields() {
		$fields = array(
			'simulate_type' => array(
				'id' => 'simulate_type',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['simulate_type'],
				'class' => 'ms-admin-bar-date ms-date',
			),

			'simulate_date' => array(
				'id' => 'simulate_date',
				'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'value' => $this->data['simulate_date'],
				'class' => 'ms-admin-bar-date ms-date',
			),

			'simulate_submit' => array(
				'id' => 'simulate_submit',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Go', MS_TEXT_DOMAIN ),
				'class' => 'ms-admin-bar-submit',
			),
		);

		return apply_filters( 'ms_view_admin_bar_prepare_fields', $fields, $this );
	}
}