<?php

class MS_View_Shortcode_Account extends MS_View {

	public function to_html() {
		global $post;

		$fields = $this->prepare_fields();
		$signup_url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER );

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

				<?php
				if ( MS_Model_Member::is_admin_user() ) {
					_e( 'You are an admin user and have access to all memberships', MS_TEXT_DOMAIN );
				} else {
					if ( ! empty( $this->data['membership'] ) ) {
						?>
						<table>
							<tr>
								<th><?php _e( 'Membership name', MS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Status', MS_TEXT_DOMAIN ); ?></th>
								<?php if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) :  ?>
									<th><?php _e( 'Trial expire date', MS_TEXT_DOMAIN ); ?></th>
								<?php endif; ?>
								<th><?php _e( 'Expire date', MS_TEXT_DOMAIN ); ?></th>
							</tr>
							<?php
							$empty = true;

							foreach ( $this->data['membership'] as $membership ) :
								if ( $membership->is_system() ) { continue; }
								$empty = false;
								$ms_relationship = $this->data['member']->subscriptions[ $membership->id ];
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
							<?php
							endforeach;

							if ( $empty ) {
								$cols = 3;
								if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) {
									$cols += 1;
								}

								printf(
									'<tr><td colspan="%1$s">%2$s</td></tr>',
									$cols,
									__( '(No Membership)', MS_TEXT_DOMAIN )
								);
							}
							?>
						</table>
					<?php
					} else {
						_e( 'No memberships', MS_TEXT_DOMAIN );
					}
				}
				?>

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
							<td><?php echo esc_html( number_format( $invoice->total, 2 ) ); ?></td>
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
				$has_login_form = MS_Helper_Shortcode::has_shortcode(
					MS_Helper_Shortcode::SCODE_LOGIN,
					$post->post_content
				);

				if ( ! $has_login_form ) {
					$redirect = add_query_arg( array() );
					$title = __( 'Your account', MS_TEXT_DOMAIN );
					$scode = sprintf(
						'[%1$s redirect="%2$s" title="%3$s"]',
						MS_Helper_Shortcode::SCODE_LOGIN,
						esc_url( $redirect ),
						esc_attr( $title )
					);
					echo '' . do_shortcode( $scode );
				}
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

}