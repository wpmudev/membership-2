<?php

class MS_View_Shortcode_Account extends MS_View {
	
	protected $data;
	
	protected $fields;
	
	protected $personal_info;
	
	public function to_html() {
		$this->prepare_fields();
		$signup_url = MS_Factory::load( 'MS_Model_Pages')->get_ms_page_url( MS_Model_Pages::MS_PAGE_REGISTER );
		ob_start();
		?>
		<div class="ms-account-wrapper">
			<?php if( MS_Model_Member::is_logged_user() ): ?>
				<h2>
					<?php echo sprintf( '%s <a href="%s" class="ms-edit-profile">%s</a>', 
							__( 'Your Membership', MS_TEXT_DOMAIN ), 
							$signup_url, 
							__( 'Change', MS_TEXT_DOMAIN ) ); 
					?>
				</h2>
				<?php if( ! empty( $this->data['membership'] ) ) :?>
					<table>
						<tr>
							<th><?php _e( 'Membership name', MS_TEXT_DOMAIN );?></th>
							<th><?php _e( 'Status', MS_TEXT_DOMAIN );?></th>
							<?php if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ):  ?>
								<th><?php _e( 'Trial expire date', MS_TEXT_DOMAIN );?></th>
							<?php endif; ?>
							<th><?php _e( 'Expire date', MS_TEXT_DOMAIN );?></th>
						</tr>
						<?php foreach( $this->data['membership'] as $membership ):
								$ms_relationship = $this->data['member']->ms_relationships[ $membership->id ]; 
						?>
							<tr>
								<td><?php echo $membership->name; ?></td>
								<td><?php echo $ms_relationship->status; ?></td>
								<?php if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ):  ?>
									<td><?php echo ( $ms_relationship->trial_expire_date ) ? $ms_relationship->trial_expire_date : __( 'No trial', MS_TEXT_DOMAIN ); ?></td>
								<?php endif; ?>
								<td><?php echo $ms_relationship->expire_date; ?></td>
							</tr>
						<?php endforeach;?>
					</table>
				<?php else: ?>
					<?php _e( 'No memberships', MS_TEXT_DOMAIN );?>
				<?php endif;?>
				<h2>
					<?php echo sprintf( '%s <a href="%s" class="ms-edit-profile">%s</a>', 
							__( 'Personnal details', MS_TEXT_DOMAIN ), 
							add_query_arg( array( 'action' => MS_Controller_Frontend::ACTION_EDIT_PROFILE ) ), 
							__( 'Edit', MS_TEXT_DOMAIN ) ); 
					?>
				</h2>
				<table>
					<?php foreach( $this->personal_info as $field => $title ): ?>
						<tr>
							<th class="ms-label-title"><?php echo $title; ?>: </th>
							<td class="ms-label-field"><?php echo $this->data['member']->$field;?></td>
						</tr>
					<?php endforeach;?>
				</table>
				<?php do_action( 'ms_view_shortcode_account_card_info', $this->data );?>
				<h2>
					<?php echo sprintf( '%s <a href="%s" class="ms-edit-profile">%s</a>', 
							__( 'Invoices', MS_TEXT_DOMAIN ), 
							add_query_arg( array( 'action' => MS_Controller_Frontend::ACTION_VIEW_INVOICES ) ), 
							__( 'View all', MS_TEXT_DOMAIN ) ); 
					?>
				</h2>
				<table>
					<thead>
						<tr>
							<th><?php _e( 'Invoice #', MS_TEXT_DOMAIN );?></th>
							<th><?php _e( 'Status', MS_TEXT_DOMAIN );?></th>
							<th><?php echo __( 'Total', MS_TEXT_DOMAIN ) . ' ('. MS_Plugin::instance()->settings->currency . ')';?></th>
							<th><?php _e( 'Membership', MS_TEXT_DOMAIN );?></th>
							<th><?php _e( 'Due date', MS_TEXT_DOMAIN );?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach( $this->data['invoices'] as $invoice ): ?>
						<tr>
							<td><?php echo sprintf( '<a href="%s">%s</a>', get_permalink(  $invoice->id ),  $invoice->id );?></td>
							<td><?php echo $invoice->status;?></td>
							<td><?php echo $invoice->total;?></td>
							<td><?php echo MS_Factory::load( 'MS_Model_Membership', $invoice->membership_id )->name;?></td>
							<td><?php echo $invoice->due_date;?></td>
						</tr>
					<?php endforeach;?>
					</tbody>
				</table>				
				<h2>
					<?php echo sprintf( '%s <a href="%s" class="ms-edit-profile">%s</a>', 
							__( 'Activities', MS_TEXT_DOMAIN ), 
							add_query_arg( array( 'action' => MS_Controller_Frontend::ACTION_VIEW_ACTIVITIES ) ), 
							__( 'View all', MS_TEXT_DOMAIN ) ); 
					?>
				</h2>
				<table>
					<thead>
						<tr>
							<th><?php _e( 'Date', MS_TEXT_DOMAIN );?></th>
							<th><?php _e( 'Actvity', MS_TEXT_DOMAIN );?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach( $this->data['events'] as $event ): ?>
						<tr>
							<td><?php echo $event->post_modified;?></td>
							<td><?php echo $event->description;?></td>
						</tr>
					<?php endforeach;?>
					</tbody>
				</table>
			<?php else: ?>
				<?php
					$redirect = add_query_arg( array() );
					$title = __( 'Your account', MS_TEXT_DOMAIN );
					echo do_shortcode( "[ms-membership-login redirect='$redirect' title='$title']" );
				?>
			<?php endif;?>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}
	
	public function prepare_fields() {
		$this->personal_info = array( 
			'first_name' => __( 'First name', MS_TEXT_DOMAIN ), 
			'last_name' => __( 'Last name', MS_TEXT_DOMAIN ),
			'username' => __( 'Username', MS_TEXT_DOMAIN ),
			'email' => __( 'Email', MS_TEXT_DOMAIN ),
		);
	}
	
	private function login_html() {
	?>
		<div class="ms-membership-form-wrapper">
			<legend><?php _e( 'Your Account', MS_TEXT_DOMAIN ) ?></legend>
			<div class="ms-alert-box ms-alert-error">
				<?php echo __( 'You are not currently logged in. Please login to view your membership information.', MS_TEXT_DOMAIN ); ?>
			</div>
			<?php
				$redirect = add_query_arg( array() );
				echo do_shortcode( "[ms-membership-login redirect='$redirect']" );
			?>
		</div>		
	<?php
	}

}