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
			printf(
				__( 'Here you can define which members are allowed to subscribe to %s. By default anyone can subscribe.', 'membership2' ),
				$membership->get_name_tag()
			);
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
		$args = array( 'include_guest' => false );
		$memberships = MS_Model_Membership::get_memberships( $args );
		$membership = $this->data['membership'];
		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce = wp_create_nonce( $action );

		$fields = array();

		/*
		 * The value of "allow_val" is negated, because the radio-slider is
		 * reversed. So allow_val == false means that upgrading is allowed.
		 *
		 * This is just a UI tweak, the function ->update_allowed() returns true
		 * when upgrading is allowed.
		 */
		$list = array();
		$list['guest'] = array(
			'allow' => __( 'Users without Membership can subscribe', 'membership2' ),
			'allow_val' => ! $membership->update_allowed( 'guest' ),
		);
		foreach ( $memberships as $item ) {
			if ( $item->id == $membership->id ) { continue; }

			$list[$item->id] = array(
				'allow' => sprintf(
					__( 'Members of %s can subscribe', 'membership2' ),
					$item->get_name_tag()
				),
				'allow_val' => ! $membership->update_allowed( $item->id ),
			);

			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
				$list[$item->id]['replace'] = sprintf(
					__( 'Cancel %s on subscription', 'membership2' ),
					$item->get_name_tag()
				);
				$list[$item->id]['replace_val'] = $membership->update_replaces( $item->id );
			}
		}

		foreach ( $list as $id => $data ) {
			$fields[] = array(
				'id' => 'deny_update[' . $id . ']',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => $data['allow'],
				'value' => $data['allow_val'],
				'before' => __( 'Allow', 'membership2' ),
				'after' => __( 'Deny', 'membership2' ),
				'class' => 'reverse',
				'wrapper_class' => 'ms-block inline-label ms-allow',
				'ajax_data' => array( 1 ),
			);

			if ( ! empty( $data['replace'] ) ) {
				if ( MS_Addon_Prorate::is_active() ) {
					$after_label = __( 'Cancel and Pro-Rate', 'membership2' );
				} else {
					$after_label = __( 'Cancel', 'membership2' );
				}

				$fields[] = array(
					'id' => 'replace_update[' . $id . ']',
					'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
					'title' => $data['replace'],
					'value' => $data['replace_val'],
					'before' => __( 'Keep', 'membership2' ),
					'after' => $after_label,
					'class' => 'reverse',
					'wrapper_class' => 'ms-block inline-label ms-update-replace',
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