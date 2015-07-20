<?php
/**
 * Display an edit form where a single member can be added or details of a
 * member can be edited.
 *
 * @since 1.0.1.0
 */
class MS_View_Member_Editor extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.1.0
	 * @return string
	 */
	public function to_html() {

		if ( 'add' == $this->data['action'] ) {
			$title = __( 'Add or Select Member', MS_TEXT_DOMAIN );
			$groups = $this->prepare_fields_add();
		} else {
			$title = __( 'Edit Member', MS_TEXT_DOMAIN );
			$groups = $this->prepare_fields_edit();
		}

		ob_start();
		?>
		<div class="ms-wrap ms-add-member">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title_icon_class' => 'wpmui-fa wpmui-fa-user',
					'title' => $title,
					'desc' => '',
				)
			);
			?>
			<div class="ms-settings ms-add-member">
			<?php foreach ( $groups as $class => $fields ) : ?>
				<div class="ms-field-group ms-group-<?php echo esc_attr( $class ); ?>">
				<div class="ms-field-group-inner">
				<form method="post">
				<?php
				foreach ( $fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				?>
				</div></div>
				</form>
			<?php endforeach; ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Input fields displayed in the "Add or Select Member" screen.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	public function prepare_fields_add() {
		$action_add = MS_Controller_Member::ACTION_ADD_MEMBER;
		$action_select = MS_Controller_Member::ACTION_SELECT_MEMBER;

		$fields = array();
		$fields['create'] = array(
			'title' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'class' => 'group-title',
				'value' => __( 'Create a new WordPress user', MS_TEXT_DOMAIN ),
			),
			'username' => array(
				'id' => 'username',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'User name', MS_TEXT_DOMAIN ) . ' *',
				'after' => ' ',
				'class' => 'required ms-text-medium',
			),
			'email' => array(
				'id' => 'email',
				'type' => MS_Helper_Html::INPUT_TYPE_EMAIL,
				'title' => __( 'Email address', MS_TEXT_DOMAIN ) . ' *',
				'after' => ' ',
				'class' => 'required ms-text-medium',
			),
			'first_name' => array(
				'id' => 'first_name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'First name', MS_TEXT_DOMAIN ),
				'class' => 'ms-text-medium',
			),
			'last_name' => array(
				'id' => 'last_name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Last name', MS_TEXT_DOMAIN ),
				'class' => 'ms-text-medium',
			),
			'password' => array(
				'id' => 'password',
				'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
				'title' => __( 'Password', MS_TEXT_DOMAIN ),
				'class' => 'ms-text-medium',
			),
			'info' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => __( 'We create a new WordPress user without sending a confirmation email.', MS_TEXT_DOMAIN ),
				'class' => 'info-field',
			),
			'sep' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'button' => array(
				'id' => 'btn_create',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Create user', MS_TEXT_DOMAIN ) . ' &raquo;',
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action_add,
			),
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action_add ),
			),
		);

		$fields['select'] = array(
			'title' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'class' => 'group-title',
				'value' => __( 'Select an existing WordPress user', MS_TEXT_DOMAIN ),
			),
			'select_user' => array(
				'id' => 'user_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'title' => __( 'Existing WordPress users', MS_TEXT_DOMAIN ),
				'class' => 'manual-init no-auto-init widefat',
			),
			'sep' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'button' => array(
				'id' => 'btn_select',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Select', MS_TEXT_DOMAIN ) . ' &raquo;',
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action_select,
			),
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action_select ),
			),
		);

		return apply_filters(
			'ms_view_member_editor_fields_add',
			$fields
		);
	}

	/**
	 * Input fields displayed in the "Edit Member" screen.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	public function prepare_fields_edit() {
		$action_update = MS_Controller_Member::ACTION_UPDATE_MEMBER;
		$action_modify = MS_Controller_Member::ACTION_MODIFY_SUBSCRIPTIONS;

		$user_id = $this->data['user_id'];
		$user = MS_Factory::load( 'MS_Model_Member', $user_id );
		$unused_memberships = array();
		$temp_memberships = MS_Model_Membership::get_memberships(
			array( 'include_guest' => 0 )
		);
		foreach ( $temp_memberships as $membership ) {
			$unused_memberships[$membership->id] = $membership;
		}

		$fields = array();
		$fields['editor'] = array(
			'title' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'class' => 'group-title',
				'value' => __( 'Basic Profile details', MS_TEXT_DOMAIN ),
			),
			'username' => array(
				'id' => 'username',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Username', MS_TEXT_DOMAIN ),
				'value' => $user->username,
				'class' => 'ms-text-medium',
				'config' => array(
					'disabled' => 'disabled',
				),
			),
			'email' => array(
				'id' => 'email',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Email', MS_TEXT_DOMAIN ),
				'value' => $user->email,
				'class' => 'ms-text-medium',
			),
			'first_name' => array(
				'id' => 'first_name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'First Name', MS_TEXT_DOMAIN ),
				'value' => $user->first_name,
				'class' => 'ms-text-medium',
			),
			'last_name' => array(
				'id' => 'last_name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Last Name', MS_TEXT_DOMAIN ),
				'value' => $user->last_name,
				'class' => 'ms-text-medium',
			),
			'displayname' => array(
				'id' => 'displayname',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Display Name', MS_TEXT_DOMAIN ),
				'value' => $user->get_user()->display_name,
				'class' => 'ms-text-medium',
			),
			'sep' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'user_id' => array(
				'id' => 'user_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $user->id,
			),
			'button' => array(
				'id' => 'btn_save',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save', MS_TEXT_DOMAIN ),
			),
			'profile' => array(
				'id' => 'user_profile',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Full User Profile', MS_TEXT_DOMAIN ) . ' &raquo;',
				'url' => admin_url( 'user-edit.php?user_id=' . $user->id ),
				'class' => 'button wpmui-field-input',
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action_update,
			),
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action_update ),
			),
		);

		$fields['subscriptions'] = array();

		// Section: Edit existing subscriptions.
		$fields['subscriptions'][] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TEXT,
			'class' => 'group-title',
			'value' => __( 'Manage Subscriptions', MS_TEXT_DOMAIN ),
		);
		if ( $user->subscriptions ) {
			$gateways = MS_Model_Gateway::get_gateway_names( false, true );

			foreach ( $user->subscriptions as $subscription ) {
				if ( MS_Model_Relationship::STATUS_DEACTIVATED == $subscription->status ) {
					continue;
				}

				$the_membership = $subscription->get_membership();
				unset( $unused_memberships[$the_membership->id] );

				$status_options = array(
					MS_Model_Relationship::STATUS_PENDING => __( 'Pending (activate on next payment)', MS_TEXT_DOMAIN ),
					MS_Model_Relationship::STATUS_WAITING => __( 'Waiting (activate on start date)', MS_TEXT_DOMAIN ),
					MS_Model_Relationship::STATUS_TRIAL => __( 'Trial Active', MS_TEXT_DOMAIN ),
					MS_Model_Relationship::STATUS_ACTIVE => __( 'Active', MS_TEXT_DOMAIN ),
					MS_Model_Relationship::STATUS_CANCELED => __( 'Cancelled (deactivate on expire date)', MS_TEXT_DOMAIN ),
					MS_Model_Relationship::STATUS_TRIAL_EXPIRED => __( 'Trial Expired (activate on next payment)', MS_TEXT_DOMAIN ),
					MS_Model_Relationship::STATUS_EXPIRED => __( 'Expired (no access) ', MS_TEXT_DOMAIN ),
					MS_Model_Relationship::STATUS_DEACTIVATED => __( 'Deactivated (no access)', MS_TEXT_DOMAIN ),
				);

				if ( ! $the_membership->has_trial() ) {
					unset( $status_options[MS_Model_Relationship::STATUS_TRIAL] );
					unset( $status_options[MS_Model_Relationship::STATUS_TRIAL_EXPIRED] );
				}

				if ( isset( $gateways[ $subscription->gateway_id ] ) ) {
					$gateway_name = $gateways[ $subscription->gateway_id ];
				} elseif ( empty( $subscription->gateway_id ) ) {
					$gateway_name = __( '- No Gateway -', MS_TEXT_DOMAIN );
				} else {
					$gateway_name = '(' . $subscription->gateway_id . ')';
				}

				$field_start = array(
					'name' => 'mem_' . $the_membership->id . '[start]',
					'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
					'value' => $subscription->start_date,
				);
				$field_expire = array(
					'name' => 'mem_' . $the_membership->id . '[expire]',
					'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
					'value' => $subscription->expire_date,
				);
				$field_status = array(
					'name' => 'mem_' . $the_membership->id . '[status]',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $subscription->status,
					'field_options' => $status_options,
				);

				$fields['subscriptions'][] = array(
					'name' => 'memberships[]',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $the_membership->id,
				);

				$fields['subscriptions'][] = array(
					'title' => $the_membership->get_name_tag(),
					'type' => MS_Helper_Html::TYPE_HTML_TABLE,
					'value' => array(
						array(
							__( 'Subscription ID', MS_TEXT_DOMAIN ),
							$subscription->id,
						),
						array(
							__( 'Payment Gateway', MS_TEXT_DOMAIN ),
							$gateway_name,
						),
						array(
							__( 'Payment Type', MS_TEXT_DOMAIN ),
							$subscription->get_payment_description( null, true ),
						),
						array(
							__( 'Start Date', MS_TEXT_DOMAIN ) . ' <sup>*)</sup>',
							MS_Helper_Html::html_element( $field_start, true ),
						),
						array(
							__( 'Expire Date', MS_TEXT_DOMAIN ) . ' <sup>*)</sup>',
							MS_Helper_Html::html_element( $field_expire, true ),
						),
						array(
							__( 'Status', MS_TEXT_DOMAIN ) . ' <sup>*)</sup>',
							MS_Helper_Html::html_element( $field_status, true ),
						),
					),
					'field_options' => array(
						'head_col' => true,
					),
				);
			}
		} else {
			$fields['subscriptions'][] = array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => __( 'This user does not have any subscriptions yet.', MS_TEXT_DOMAIN ),
			);
		}

		// Section: Add new subscription.
		if ( count( $unused_memberships ) ) {
			$options = array();

			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
				$field_type = MS_Helper_Html::INPUT_TYPE_CHECKBOX;
				$group_title = __( 'Add Subscriptions', MS_TEXT_DOMAIN );
			} else {
				$field_type = MS_Helper_Html::INPUT_TYPE_RADIO;
				$group_title = __( 'Set Subscription', MS_TEXT_DOMAIN );
			}

			$fields['subscriptions'][] = array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			);
			$fields['subscriptions'][] = array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'class' => 'group-title',
				'value' => $group_title,
			);
			foreach ( $unused_memberships as $the_membership ) {
				$options[$the_membership->id] = $the_membership->get_name_tag();
			}
			$fields['subscriptions'][] = array(
				'id' => 'subscribe',
				'type' => $field_type,
				'field_options' => $options,
			);
			$fields['subscriptions'][] = array(
				'id' => 'user_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $user->id,
			);
		}

		if ( $user->subscriptions ) {
			$fields['subscriptions'][] = array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			);
			$fields['subscriptions'][] = array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => '<sup>*)</sup> ' . __( 'Subscription Dates and Status are validated when saved and might result in a different value then the one specified above.', MS_TEXT_DOMAIN ),
				'class' => 'info-field',
			);
		}
		$fields['subscriptions'][] = array(
			'id' => 'btn_modify',
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
		);
		$fields['subscriptions'][] = array(
			'id' => 'history',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => '<i class="dashicons dashicons-id"></i>' . __( 'History and logs', MS_TEXT_DOMAIN ),
			'url' => '#history',
			'class' => 'button wpmui-field-input',
			'config' => array(
				'data-ms-dialog' => 'View_Member_Dialog',
				'data-ms-data' => array( 'member_id' => $member->id ),
			),
		);
		$fields['subscriptions'][] = array(
			'id' => 'action',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $action_modify,
		);

		$fields['subscriptions'][] = array(
			'id' => '_wpnonce',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => wp_create_nonce( $action_modify ),
		);

		return apply_filters(
			'ms_view_member_editor_fields_edit',
			$fields
		);
	}
}
