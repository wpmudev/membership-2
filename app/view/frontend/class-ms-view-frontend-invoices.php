<?php

class MS_View_Frontend_Invoices extends MS_View {

	public function to_html() {
		ob_start();
		?>
		<div class="ms-account-wrapper">
			<?php if ( MS_Model_Member::is_logged_in() ): ?>
				<h2>
					<?php _e( 'Invoice', MS_TEXT_DOMAIN ); ?>
				</h2>
				<table>
					<thead>
						<tr>
							<th><?php _e( 'Invoice #', MS_TEXT_DOMAIN ); ?></th>
							<th><?php _e( 'Status', MS_TEXT_DOMAIN ); ?></th>
							<th><?php printf(
								'%s (%s)',
								__( 'Total', MS_TEXT_DOMAIN ),
								MS_Plugin::instance()->settings->currency
							); ?></th>
							<th><?php _e( 'Membership', MS_TEXT_DOMAIN ); ?></th>
							<th><?php _e( 'Due date', MS_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $this->data['invoices'] as $invoice ) : ?>
						<tr>
							<td><?php printf( '<a href="%s">%s</a>', get_permalink(  $invoice->id ),  $invoice->id ); ?></td>
							<td><?php echo $invoice->status; ?></td>
							<td><?php echo $invoice->total; ?></td>
							<td><?php echo MS_Factory::load( 'MS_Model_Membership', $invoice->membership_id )->name; ?></td>
							<td><?php echo $invoice->due_date; ?></td>
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