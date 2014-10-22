<?php

class MS_View_Frontend_Activities extends MS_View {

	protected $data;

	protected $fields;

	public function to_html() {
		ob_start();
		?>
		<div class="ms-account-wrapper">
			<?php if ( MS_Model_Member::is_logged_user() ) : ?>
				<h2>
					<?php _e( 'Activity', MS_TEXT_DOMAIN ); ?>
				</h2>
				<table>
					<thead>
						<tr>
							<th><?php _e( 'Date', MS_TEXT_DOMAIN ); ?></th>
							<th><?php _e( 'Actvity', MS_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $this->data['events'] as $event ) : ?>
						<tr>
							<td><?php echo $event->post_modified; ?></td>
							<td><?php echo $event->description; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
				<?php
					$redirect = add_query_arg( array() );
					$title = __( 'Your account', MS_TEXT_DOMAIN );
					echo do_shortcode( "[ms-membership-login redirect='$redirect' title='$title']" );
				?>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	private function login_html() {
	?>
		<div class="ms-membership-form-wrapper">
			<legend><?php _e( 'Your Account', MS_TEXT_DOMAIN ) ?></legend>
			<div class="ms-alert-box ms-alert-error">
				<?php _e( 'You are not currently logged in. Please login to view your membership information.', MS_TEXT_DOMAIN ); ?>
			</div>
			<?php
				$redirect = add_query_arg( array() );
				echo do_shortcode( "[ms-membership-login redirect='$redirect']" );
			?>
		</div>
	<?php
	}

}