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
	 * The metabox wrapper ID.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $metabox_id = 'ms-metabox';
	
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
		$dripped = array();
		ob_start();
		
		$edit_link = array(
				'id' => 'page_rule_edit',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Manage Protected Content', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', $this->data['rule_type'] ),
		);
		
		?>
		<div id="<?php echo $this->metabox_id;?>" class="ms_metabox ms-wrap">
			<?php if( ! empty( $this->data['special_page'] ) ): ?>
				<div>Membership Special Page</div>
			<?php elseif( ! empty( $this->data['not_protected'] ) ): ?>
				<div>Not protected</div>
				<?php MS_Helper_Html::html_element( $edit_link ); ?>
			<?php else :?>
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
							<?php
								if( $data['dripped'] && $data['has_access'] ) {
									// Using array to notify users which Memberships has dripped content.
									$dripped[] = sprintf( __( '%s membership', MS_TEXT_DOMAIN ), $data['name'] );
								} 
							?>
							<tr>
								<td> 
									<?php echo $data['name']; ?>
								</td>
								<td>
									<?php
										$toggle = array(
												'id' => "access_{$membership_id}",
												'name' => "ms_access[{$membership_id}]",
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
				<?php if( count( $dripped ) > 0 ) : ?>
						<div class="dripped" title="<?php printf( __( "Set as dripped in '%s'.", MS_TEXT_DOMAIN ), implode( "', '", $dripped ) ); ?>"><?php _e( 'This is dripped content.', MS_TEXT_DOMAIN ); ?>
						<div class="tooltip">
							<div class="tooltip-content">
							<?php echo '- ' . implode( ',<br />- ', $dripped ); ?>
							</div>
						</div>
						</div>
				<?php endif; ?>
			<?php endif;?>
		</div>
		<div style='clear:both;'></div>
		<?php 
		$html = ob_get_clean();
		
		return apply_filters( 'ms_view_membership_metabox_to_html', $html );
	}
}