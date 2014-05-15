<?php

class MS_View_Membership_Metabox extends MS_View {

	const MEMBERSHIP_METABOX_NONCE = 'membership_metabox_save';
	
	protected $data;
	
	protected $read_only;
	
	public function to_html() {
		
		ob_start();
		wp_nonce_field( self::MEMBERSHIP_METABOX_NONCE, self::MEMBERSHIP_METABOX_NONCE );
		?>
		<div id="<?php echo $this->metabox_id;?>" class="ms_metabox">
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
							if ( $data['dripped'] ) { 
								// Using array to notify users which Memberships has dripped content.
								$dripped[] = $data['name'] . ' ' . __( 'membership', MS_TEXT_DOMAIN );
							} 
						?>
						<tr>
							<td> 
								<?php echo $data['name']; ?>
							</td>
							<td>
								<div class="ms-radio-slider  <?php echo $data['has_access'] ? 'on' : ''; ?>">
	    							<div class="toggle"></div>
	    							<?php
										if( ! $this->read_only ) {
											MS_Helper_Html::html_input(
												array(
													'id' => "access_{$membership_id}",
													'name' => "ms_access[{$membership_id}]",
													'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
													'value' => $data['has_access'],
												)
											);
										}
									?>
	  							</div> 
							</td>
						</tr>
					<?php endforeach; ?>				
										
			</tbody>
			</table>
			<?php if ( ! empty( $dripped ) ) : ?>
					<div class="dripped" title="<?php echo __( 'Set as dripped in: ', MS_TEXT_DOMAIN ) . implode( ', ', $dripped ); ?>"><?php _e( 'This is dripped content.', MS_TEXT_DOMAIN ); ?>
					<div class="tooltip">
						<div class="tooltip-content">
						<?php echo '- ' . implode( ',<br />- ', $dripped ); ?>
						</div>
					</div>
					</div>
			<?php endif; ?>
			
			
		</div>
		<div style='clear:both;'></div>
		<?php 
		$html = ob_get_clean();
		echo $html;
	}
}