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
					
					<?php foreach( $this->data as $membership_id => $data ): ?>
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
		</div>
		<div style='clear:both;'></div>
		<?php 
		$html = ob_get_clean();
		echo $html;
	}
}