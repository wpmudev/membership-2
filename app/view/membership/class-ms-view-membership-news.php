<?php

class MS_View_Membership_News extends MS_View {

	protected $data;
	
	public function to_html() {
		?>
			<div class="ms-overview-news-wrapper">
				<h3 class="hndle"><span><?php _e( 'News:', MS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<?php if( ! empty( $this->data['events'] ) ): ?>
						<table>
							<tr>
								<th><?php _e( 'Date', MS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'User', MS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Event', MS_TEXT_DOMAIN ); ?></th>
							</tr>
							<?php foreach( $this->data['events'] as $event ): ?>
								<tr>
									<td><?php echo date( MS_Helper_Period::DATE_TIME_FORMAT, strtotime( $event->post_modified ) ); ?></td>
									<td><?php echo MS_Model_Member::get_username( $event->user_id ); ?></td>
									<td><?php echo $event->description; ?></td>
								</td>
							<?php endforeach;?>
						</table>
					<?php else: ?>
						<p><?php _e( 'There will be some interesting news here when your site gets going.', MS_TEXT_DOMAIN ); ?>		
					<?php endif;?>
					<br class="clear">
				</div>
			</div>
		<?php 
	}
}