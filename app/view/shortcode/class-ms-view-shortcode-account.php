<?php

class MS_View_Shortcode_Account extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );
		$signup_url = $ms_pages->get_page_url( MS_Model_Pages::MS_PAGE_REGISTER );

		ob_start();
		?>
		<div class="ms-account-wrapper">
			<?php if ( MS_Model_Member::is_logged_user() ) : ?>
				<h2>
					<?php printf(
						'%s <a href="%s" class="ms-edit-profile">%s</a>',
						__( 'Your Membership', MS_TEXT_DOMAIN ),
						$signup_url,
						__( 'Change', MS_TEXT_DOMAIN )
					); ?>
				</h2>
				<?php if ( ! empty( $this->data['membership'] ) ) : ?>
					<table>
						<tr>
							<th><?php _e( 'Membership name', MS_TEXT_DOMAIN ); ?></th>
							<th><?php _e( 'Status', MS_TEXT_DOMAIN ); ?></th>
							<?php if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) :  ?>
								<th><?php _e( 'Trial expire date', MS_TEXT_DOMAIN ); ?></th>
							<?php endif; ?>
							<th><?php _e( 'Expire date', MS_TEXT_DOMAIN ); ?></th>
						</tr>
						<?php foreach ( $this->data['membership'] as $membership ) :
							$ms_relationship = $this->data['member']->ms_relationships[ $membership->id ];
							?>
							<tr>
								<td><?php echo esc_html( $membership->name ); ?></td>
								<td><?php echo esc_html( $ms_relationship->status ); ?></td>
								<?php if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) : ?>
									<td><?php
									if ( $ms_relationship->trial_expire_date ) {
										echo esc_html( $ms_relationship->trial_expire_date );
									} else {
										_e( 'No trial', MS_TEXT_DOMAIN );
									}
									?></td>
								<?php endif; ?>
								<td><?php echo esc_html( $ms_relationship->expire_date ); ?></td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php else : ?>
					<?php _e( 'No memberships', MS_TEXT_DOMAIN ); ?>
				<?php endif; ?>

				<h2>
					<?php printf(
						'%s <a href="%s" class="ms-edit-profile">%s</a>',
						__( 'Personal details', MS_TEXT_DOMAIN ),
						add_query_arg( array( 'action' => MS_Controller_Frontend::ACTION_EDIT_PROFILE ) ),
						__( 'Edit', MS_TEXT_DOMAIN )
					); ?>
				</h2>
				<table>
					<?php foreach ( $fields['personal_info'] as $field => $title ) : ?>
						<tr>
							<th class="ms-label-title"><?php echo esc_html( $title ); ?>: </th>
							<td class="ms-label-field"><?php echo esc_html( $this->data['member']->$field ); ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php do_action( 'ms_view_shortcode_account_card_info', $this->data ); ?>
				<h2>
					<?php printf(
						'%s <a href="%s" class="ms-edit-profile">%s</a>',
						__( 'Invoices', MS_TEXT_DOMAIN ),
						add_query_arg( array( 'action' => MS_Controller_Frontend::ACTION_VIEW_INVOICES ) ),
						__( 'View all', MS_TEXT_DOMAIN )
					); ?>
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
						<?php $inv_membership = MS_Factory::load( 'MS_Model_Membership', $invoice->membership_id ); ?>
						<tr>
							<td><?php printf(
								'<a href="%s">%s</a>',
								get_permalink(  $invoice->id ),
								$invoice->id
							); ?></td>
							<td><?php echo esc_html( $invoice->status ); ?></td>
							<td><?php echo esc_html( $invoice->total ); ?></td>
							<td><?php echo esc_html( $inv_membership->name ); ?></td>
							<td><?php echo esc_html( $invoice->due_date ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<h2>
					<?php printf(
						'%s <a href="%s" class="ms-edit-profile">%s</a>',
						__( 'Activities', MS_TEXT_DOMAIN ),
						add_query_arg( array( 'action' => MS_Controller_Frontend::ACTION_VIEW_ACTIVITIES ) ),
						__( 'View all', MS_TEXT_DOMAIN )
					); ?>
				</h2>
				<table>
					<thead>
						<tr>
							<th><?php _e( 'Date', MS_TEXT_DOMAIN ); ?></th>
							<th><?php _e( 'Activity', MS_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $this->data['events'] as $event ) : ?>
						<tr>
							<td><?php echo esc_html( $event->post_modified ); ?></td>
							<td><?php echo esc_html( $event->description ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else :
				$redirect = add_query_arg( array() );
				$title = __( 'Your account', MS_TEXT_DOMAIN );
				echo do_shortcode( "[ms-membership-login redirect='$redirect' title='$title']" );
			endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	public function prepare_fields() {
		$fields = array(
			'personal_info' => array(
				'first_name' => __( 'First name', MS_TEXT_DOMAIN ),
				'last_name' => __( 'Last name', MS_TEXT_DOMAIN ),
				'username' => __( 'Username', MS_TEXT_DOMAIN ),
				'email' => __( 'Email', MS_TEXT_DOMAIN ),
			)
		);

		return $fields;
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