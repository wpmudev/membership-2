<?php
/**
 * Tab: Edit Upgrade Paths
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage View
 */
class MS_View_Membership_Tab_Upgrade extends MS_View {

	/**
	 * Returns the contens of the dialog
	 *
	 * @since  1.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		$fields = $this->get_fields();
		$membership = $this->data['membership'];

		ob_start();
		?>
		<div>
			<p>
			<?php
			_e( 'Here you can define which members are allowed to subscribe to the current membership. By default anyone can subscribe.', MS_TEXT_DOMAIN );
			?>
			</p>
			<?php
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_membership_upgrades_to_html', $html );
	}

	/**
	 * Prepares fields for the edit form.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	protected function get_fields() {
		$memberships = MS_Model_Membership::get_memberships();
		$membership = $this->data['membership'];
		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce = wp_create_nonce( $action );

		$fields = array();

		foreach ( $memberships as $item ) {
			if ( $item->id == $membership->id ) { continue; }
			if ( $item->is_guest() ) { continue; }

			$fields[] = array(
				'id' => 'allow_' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'before' => sprintf(
					__( '%s can subscribe', MS_TEXT_DOMAIN ),
					$item->get_name_tag()
				),
				'after' => sprintf(
					__( '%s cannot subscribe', MS_TEXT_DOMAIN ),
					$item->get_name_tag()
				),
				'class' => 'reverse',
				'wrapper_class' => 'ms-block centered',
				'ajax_data' => array( 1 ),
			);

			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
				$fields[] = array(
					'id' => 'update_mode_' . $item->id,
					'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
					'before' => sprintf(
						__( 'Keep %s on subscription', MS_TEXT_DOMAIN ),
						$item->get_name_tag()
					),
					'after' => sprintf(
						__( 'Disable %s on subscription', MS_TEXT_DOMAIN ),
						$item->get_name_tag()
					),
					'class' => 'reverse',
					'wrapper_class' => 'ms-block centered',
					'ajax_data' => array( 1 ),
				);
			}
			$fields[] = array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			);
		}

		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['ajax_data'] ) ) {
				if ( ! empty( $field['ajax_data']['action'] ) ) {
					continue;
				}

				if ( ! isset( $fields[ $key ]['ajax_data']['field'] ) ) {
					$fields[ $key ]['ajax_data']['field'] = $fields[ $key ]['id'];
				}
				$fields[ $key ]['ajax_data']['_wpnonce'] = $nonce;
				$fields[ $key ]['ajax_data']['action'] = $action;
				$fields[ $key ]['ajax_data']['membership_id'] = $membership->id;
			}
		}

		return $fields;
	}

};