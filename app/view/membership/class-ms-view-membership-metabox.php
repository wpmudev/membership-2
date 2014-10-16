<?php
/**
 * Render Membership Metabox
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage View
 */
class MS_View_Membership_Metabox extends MS_View {

	/**
	 * Data set by controller.
	 *
	 * @since 1.0.0
	 * @var mixed $data
	 */
	protected $data;
	
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
			<?php if( ! empty( $this->data['special_page'] ) ): ?>
				<div><?php _e( 'Membership Special Page', MS_TEXT_DOMAIN ); ?></div>
			<?php else :?>
				<?php 
					$membership_id = $this->data['protected_content']->id;
					$toggle = array(
							'id' => sprintf( 'access_%s', $membership_id ),
							'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
							'title' => __( 'Enable Protection', MS_TEXT_DOMAIN ),
							'value' => $this->data['protected_content_enabled'],
							'class' => 'ms-protect-content',
							'read_only' => ! empty( $this->data['read_only'] ),
							'data_ms' => array(
									'action' => MS_Controller_Membership_Metabox::AJAX_ACTION_TOGGLE_ACCESS,
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
							
							<?php foreach( $this->data['access'] as $membership_id => $data ): ?>
								<tr>
									<td> 
										<?php echo $data['name']; ?>
									</td>
									<td>
										<?php
											$toggle = array(
													'id' => sprintf( 'access_%s', $membership_id ),
													'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
													'value' => $data['has_access'],
													'class' => '',
													'read_only' => ! empty( $this->data['read_only'] ),
													'data_ms' => array(
															'action' => MS_Controller_Membership_Metabox::AJAX_ACTION_TOGGLE_ACCESS,
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
		<div style='clear:both;'></div>
		<?php 
		$html = ob_get_clean();
		
		return apply_filters( 'ms_view_membership_metabox_to_html', $html );
	}
}