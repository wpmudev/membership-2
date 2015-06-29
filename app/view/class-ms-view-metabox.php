<?php
/**
 * Render Membership Metabox
 *
 * @since 1.0.0
 * @package Membership2
 * @subpackage View
 */
class MS_View_Metabox extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		ob_start();
		?>
		<div id="ms-metabox-wrapper" class="ms_metabox ms-wrap">
			<?php if ( ! empty( $this->data['special_page'] ) ) : ?>
				<div>
					<?php _e( 'Membership Special Page', MS_TEXT_DOMAIN ); ?>
				</div>
			<?php else :
				$membership_id = $this->data['base_id'];
				$toggle = array(
					'id' => sprintf( 'access_%s', $membership_id ),
					'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
					'title' => __( 'Enable Protection', MS_TEXT_DOMAIN ),
					'value' => $this->data['is_protected'],
					'class' => 'ms-protect-content',
					'read_only' => ! empty( $this->data['read_only'] ),
					'ajax_data' => array(
						'action' => MS_Controller_Metabox::AJAX_ACTION_TOGGLE_ACCESS,
						'post_id' => $this->data['post_id'],
						'rule_type' => $this->data['rule_type'],
						'membership_id' => $membership_id,
					),
				);
				MS_Helper_Html::html_element( $toggle );
				?>
				<div id="ms-metabox-access-wrapper">
					<hr />
					<table>
						<tbody>
							<tr>
								<th>
									<?php _e( 'Membership', MS_TEXT_DOMAIN ); ?>
								</th>
								<th>
									<?php _e( 'Access', MS_TEXT_DOMAIN ); ?>
								</th>
							</tr>

							<?php foreach ( $this->data['access'] as $membership_id => $data ) : ?>
								<tr class="ms-membership-<?php echo esc_attr( $membership_id ); ?>">
									<td>
										<?php echo esc_html( $data['name'] ); ?>
									</td>
									<td>
										<?php
										$toggle = array(
											'id' => sprintf( 'access_%s', $membership_id ),
											'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
											'value' => $data['has_access'],
											'class' => 'ms-protection-rule',
											'read_only' => ! empty( $this->data['read_only'] ),
											'ajax_data' => array(
												'action' => MS_Controller_Metabox::AJAX_ACTION_TOGGLE_ACCESS,
												'post_id' => $this->data['post_id'],
												'rule_type' => $this->data['rule_type'],
												'membership_id' => $membership_id,
											),
										);

										MS_Helper_Html::html_element( $toggle );
										?>
									</td>
								</tr>
							<?php endforeach; ?>
					</tbody>
					</table>
				</div>
			<?php endif;?>
		</div>
		<div style="clear:both;"></div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_membership_metabox_to_html', $html );
	}
}