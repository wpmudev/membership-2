<?php

class MS_View_Frontend_Activities extends MS_View {

	public function to_html() {
		ob_start();
		?>
		<div class="ms-account-wrapper">
			<?php if ( MS_Model_Member::is_logged_in() ) : ?>
				<h2>
					<?php _e( 'Activity', MS_TEXT_DOMAIN ); ?>
				</h2>
				<table>
					<thead>
						<tr>
							<th class="ms-col-activity-date"><?php
								_e( 'Date', MS_TEXT_DOMAIN );
							?></th>
							<th class="ms-col-activity-title"><?php
								_e( 'Activity', MS_TEXT_DOMAIN );
							?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $this->data['events'] as $event ) :
						$ev_classes = array(
							'ms-activity-topic-' . $event->topic,
							'ms-activity-type-' . $event->type,
							'ms-membership-' . $event->membership_id,
						);
						?>
						<tr class="<?php echo esc_attr( implode( ' ', $ev_classes ) ); ?>">
							<td class="ms-col-activity-date"><?php
								echo esc_html(
									MS_Helper_Period::format_date(
										$event->post_modified,
										__( 'F j (H:i)', MS_TEXT_DOMAIN )
									)
								);
							?></td>
							<td class="ms-col-activity-title"><?php
								echo esc_html( $event->description );
							?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			else :
				$redirect = esc_url_raw( add_query_arg( array() ) );
				$title = __( 'Your account', MS_TEXT_DOMAIN );
				echo do_shortcode( "[ms-membership-login redirect='$redirect' title='$title']" );
			endif;
			?>
		</div>
		<?php
		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return $html;
	}

}