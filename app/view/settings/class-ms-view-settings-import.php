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
 * Displays the Import preview.
 *
 * @since 1.1.0
 * @package Membership
 * @subpackage Model
 */
class MS_View_Settings_Import extends MS_View {

	/**
	 * Displays the import preview form.
	 *
	 * @since  1.1.0
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();

		ob_start();
		MS_Helper_Html::settings_box(
			array(
				$fields['object'],
				$fields['details'],
				$fields['sep'],
				$fields['clear_all'],
				$fields['back'],
				$fields['import'],
				$fields['download'],
				$fields['nonce'],
				$fields['action'],
			),
			__( 'Import Overview', MS_TEXT_DOMAIN )
		);

		MS_Helper_Html::settings_box(
			array( $fields['memberships'] ),
			__( 'List of all Memberships', MS_TEXT_DOMAIN ),
			'',
			'open'
		);

		MS_Helper_Html::settings_box(
			array( $fields['members'] ),
			__( 'List of all Members', MS_TEXT_DOMAIN ),
			'',
			'open'
		);

		MS_Helper_Html::settings_box(
			array( $fields['settings'] ),
			__( 'Imported Settings', MS_TEXT_DOMAIN ),
			'',
			'open'
		);

		$html = ob_get_clean();

		return apply_filters(
			'ms_import_preview_object',
			$html,
			$data
		);
	}

	/**
	 * Prepare the HTML fields that can be displayed
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	protected function prepare_fields() {
		$data = apply_filters(
			'ms_import_preview_data_before',
			$this->data['model']->source
		);

		// List of known Membership types; used to display the nice-name
		$ms_types = MS_Model_Membership::get_types();

		// Prepare the "Memberships" table
		$memberships = array(
			array(
				__( 'Membership name', MS_TEXT_DOMAIN ),
				__( 'Membership Type', MS_TEXT_DOMAIN ),
				__( 'Description', MS_TEXT_DOMAIN ),
			),
		);

		foreach ( $data->memberships as $item ) {
			if ( ! isset( $ms_types[$item->type] ) ) {
				$item->type = MS_Model_Membership::TYPE_SIMPLE;
			}

			if ( ! empty( $item->special ) ) {
				$memberships[] = array(
					'<em>' . __( '(Special Membership)', MS_TEXT_DOMAIN ) . '</em>',
					'-',
					$item->description,
				);
			} else {
				$memberships[] = array(
					$item->name,
					$ms_types[$item->type],
					$item->description,
				);
			}
		}

		// Prepare the "Members" table
		$members = array(
			array(
				__( 'Username', MS_TEXT_DOMAIN ),
				__( 'Email', MS_TEXT_DOMAIN ),
				__( 'Subscriptions', MS_TEXT_DOMAIN ),
				__( 'Invoices', MS_TEXT_DOMAIN ),
			),
		);

		foreach ( $data->members as $item ) {
			$inv_count = 0;
			if ( isset( $item->subscriptions )
				&& is_array( $item->subscriptions )
			) {
				foreach ( $item->subscriptions as $registration ) {
					$inv_count += count( $registration->invoices );
				}
			}

			$members[] = array(
				$item->username,
				$item->email,
				count( $item->subscriptions ),
				$inv_count,
			);
		}

		$settings = array();
		foreach ( $data->settings as $setting => $value ) {
			switch ( $setting ) {
				case 'addons':
					$model = MS_Factory::load( 'MS_Model_Addon' );
					$list = $model->get_addon_list();
					$code = '';
					foreach ( $value as $addon => $state ) {
						if ( $state ) {
							$code .= __( 'Activate: ', MS_TEXT_DOMAIN );
						} else {
							$code .= __( 'Dectivate: ', MS_TEXT_DOMAIN );
						}
						$code .= $list[$addon]->name . '<br/>';
					}
					$settings[] = array(
						__( 'Add-Ons' ),
						$code,
					);
					break;
			}
		}

		// Prepare the return value.
		$fields = array();

		$fields['object'] = array(
			'id' => 'object',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => json_encode( $data ),
		);

		// Export-Notes
		$notes = '';
		if ( isset( $data->notes ) ) {
			if ( is_scalar( $data->notes ) ) {
				$notes = array( $data->notes );
			}

			$in_sub = false;
			$notes = '<ul class="ms-import-notes">';
			foreach ( $data->notes as $line => $text ) {
				$is_sub = ( strpos( $text, '- ' ) === 0 );
				if ( $in_sub != $is_sub ) {
					$in_sub = $is_sub;
					if ( $is_sub ) {
						$notes .= '<ul>';
					} else {
						$notes .= '</ul>';
					}
				}
				if ( $in_sub ) {
					$text = substr( $text, 2 );
				}
				$notes .= '<li>' . $text;
			}
			$notes .= '</ul>';
		}

		$fields['details'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TABLE,
			'class' => 'ms-import-preview',
			'value' => array(
				array(
					__( 'Data source', MS_TEXT_DOMAIN ),
					$data->source .
					' &emsp; <small>' .
					sprintf(
						__( 'exported on %1$s', MS_TEXT_DOMAIN ),
						$data->export_time
					) .
					'</small>',
				),
				array(
					__( 'Content', MS_TEXT_DOMAIN ),
					sprintf(
						_n(
							'%1$s Membership',
							'%1$s Memberships',
							count( $data->memberships ),
							MS_TEXT_DOMAIN
						),
						'<b>' . count( $data->memberships ) . '</b>'
					) . ' / ' . sprintf(
						_n(
							'%1$s Member',
							'%1$s Members',
							count( $data->members ),
							MS_TEXT_DOMAIN
						),
						'<b>' . count( $data->members ) . '</b>'
					),
				),
			),
			'field_options' => array(
				'head_col' => true,
				'head_row' => false,
				'col_class' => array( 'preview-label', 'preview-data' ),
			)
		);

		if ( ! empty( $notes ) ) {
			$fields['details']['value'][] = array(
				__( 'Please note', MS_TEXT_DOMAIN ),
				$notes,
			);
		}

		$fields['clear_all'] = array(
			'id' => 'clear_all',
			'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
			'title' => __( 'Replace current content with import data (removes existing Memberships/Members before importing data)', MS_TEXT_DOMAIN ),
			'class' => 'widefat',
		);

		$fields['memberships'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TABLE,
			'class' => 'ms-import-preview',
			'value' => $memberships,
			'field_options' => array(
				'head_col' => false,
				'head_row' => true,
				'col_class' => array( 'preview-name', 'preview-type', 'preview-desc', 'preview-count' ),
			)
		);

		$fields['members'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TABLE,
			'class' => 'ms-import-preview',
			'value' => $members,
			'field_options' => array(
				'head_col' => false,
				'head_row' => true,
				'col_class' => array( 'preview-name', 'preview-email', 'preview-count', 'preview-count' ),
			)
		);

		$fields['settings'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TABLE,
			'class' => 'ms-import-preview',
			'value' => $settings,
			'field_options' => array(
				'head_col' => true,
				'head_row' => false,
			)
		);

		$fields['sep'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
		);

		$fields['back'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'class' => 'wpmui-field-button button',
			'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
			'url' => $_SERVER['REQUEST_URI'],
		);

		$fields['import'] = array(
			'name' => 'submit',
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Import', MS_TEXT_DOMAIN ),
			'button_value' => MS_Controller_Import::ACTION_IMPORT,
		);

		$fields['download'] = array(
			'name' => 'submit',
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Download as Export File', MS_TEXT_DOMAIN ),
			'button_value' => MS_Controller_Import::ACTION_DOWNLOAD,
			'class' => 'button-link',
		);

		$fields['action'] = array(
			'id' => 'action',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => MS_Controller_Import::ACTION_IMPORT,
		);

		$fields['nonce'] = array(
			'id' => '_wpnonce',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => wp_create_nonce( MS_Controller_Import::ACTION_IMPORT ),
		);

		return $fields;
	}

}
