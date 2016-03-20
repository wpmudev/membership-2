<?php

class MS_View_Shortcode_Account extends MS_View {

	public function to_html() {
		global $post;

		/**
		 * Provide a customized account page.
		 *
		 * @since  1.0.0
		 */
		$html = apply_filters(
			'ms_shortcode_custom_account',
			'',
			$this->data
		);

		if ( ! empty( $html ) ) {
			return $html;
		} else {
			$html = '';
		}

		$member = MS_Model_Member::get_current_member();
		$fields = $this->prepare_fields();

		// Extract shortcode options.
		extract( $this->data );

		ob_start();
		
                $m2_obj = $this;
                $is_user_logged_in = MS_Model_Member::is_logged_in();
                
                $signup_url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER );
                $signup_modified_url = sprintf(
                        '<a href="%s" class="ms-edit-profile">%s</a>',
                        $signup_url,
                        $membership_change_label
                );
                
                $m2_subscriptions = $this->data['subscription'];
                $edit_url = esc_url_raw(
                        add_query_arg(
                                array( 'action' => MS_Controller_Frontend::ACTION_EDIT_PROFILE )
                        )
                );

                $profile_change_formatted_label = sprintf(
                        '<a href="%s" class="ms-edit-profile">%s</a>',
                        $edit_url,
                        $profile_change_label
                );
                
                $detail_url = esc_url_raw(
                        add_query_arg(
                                array( 'action' => MS_Controller_Frontend::ACTION_VIEW_INVOICES )
                        )
                );

                $invoices_details_formatted_label = sprintf(
                        '<a href="%s" class="ms-all-invoices">%s</a>',
                        $detail_url,
                        $invoices_details_label
                );
                
                $detail_url = esc_url_raw(
                        add_query_arg(
                                array( 'action' => MS_Controller_Frontend::ACTION_VIEW_ACTIVITIES )
                        )
                );

                $activity_details_formatted_label = sprintf(
                        '<a href="%s" class="ms-all-activities">%s</a>',
                        $detail_url,
                        $activity_details_label
                );
                
                $has_login_form = MS_Helper_Shortcode::has_shortcode(
                        MS_Helper_Shortcode::SCODE_LOGIN,
                        $post->post_content
                );
                
                $redirect = esc_url_raw( add_query_arg( array() ) );
                $title = __( 'Your account', 'membership2' );
                $scode = sprintf(
                        '[%1$s redirect="%2$s" title="%3$s"]',
                        MS_Helper_Shortcode::SCODE_LOGIN,
                        esc_url( $redirect ),
                        esc_attr( $title )
                );
                $login_form_sc = do_shortcode( $scode );
                
                // These subscriptions have no expire date
                $no_expire_list = array(
                        MS_Model_Relationship::STATUS_PENDING,
                        MS_Model_Relationship::STATUS_WAITING,
                        MS_Model_Relationship::STATUS_DEACTIVATED,
                );

                // These subscriptions display the trial-expire date
                $trial_expire_list = array(
                        MS_Model_Relationship::STATUS_TRIAL,
                        MS_Model_Relationship::STATUS_TRIAL_EXPIRED,
                );
                
                if( $path = MS_Helper_Template::template_exists( 'membership_account.php' ) ) {
                    require $path;
                }
                
		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return apply_filters(
			'ms_shortcode_account',
			$html,
			$this->data
		);
	}

	/**
	 * Prepare some fields that are displayed in the account overview.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function prepare_fields() {
		$fields = array(
			'personal_info' => array(
				'first_name' => __( 'First name', 'membership2' ),
				'last_name' => __( 'Last name', 'membership2' ),
				'username' => __( 'Username', 'membership2' ),
				'email' => __( 'Email', 'membership2' ),
			)
		);

		$fields = apply_filters(
			'ms_shortcode_account_fields',
			$fields,
			$this
		);

		return $fields;
	}

}