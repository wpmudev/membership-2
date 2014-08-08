<?php

class MS_View_Membership_Metabox extends MS_View {

	const MEMBERSHIP_METABOX_NONCE = 'membership_metabox_save';
	
	protected $data;
	
	protected $read_only;
	
	protected $special_page;
	
	public function to_html() {
		
		ob_start();
		wp_nonce_field( self::MEMBERSHIP_METABOX_NONCE, self::MEMBERSHIP_METABOX_NONCE );
		?>
		<div id="<?php echo $this->metabox_id;?>" class="ms_metabox">
			<?php if( $this->special_page ): ?>
				<div>Membership Special Page</div>
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
						
						<?php $dripped = array(); ?>
						<?php foreach( $this->data as $membership_id => $data ): ?>
							<?php
								
								if ( $data['dripped'] && $data['has_access'] ) { 
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
												'read_only' => $this->read_only,
										);
										 MS_Helper_Html::html_input( $toggle );
									?>
								</td>
							</tr>
						<?php endforeach; ?>				
											
				</tbody>
				</table>
				<?php if ( ! empty( $dripped ) ) : ?>
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
		echo $html;
	}
}