<?php
/**
 * Template functions that can be used to create new templates
 * for several M2 pages
 *
 * @since 1.0.3
 *
 * @package Membership2
 */

function is_ms_admin_user() {
    return MS_Model_Member::is_admin_user();
}

/***************************** Single Membership Box *****************************/

function get_ms_single_box_membership_obj() {
    return MS_Helper_Template::$ms_single_box['m2_obj'];
}

function get_ms_single_box_membership_id() {
    return MS_Helper_Template::$ms_single_box['membership_id'];
}

function get_ms_single_box_wrapper_classes() {
    return MS_Helper_Template::$ms_single_box['membership_wrapper_classes'];
}

function get_ms_single_box_membership_name() {
    return MS_Helper_Template::$ms_single_box['membership_name'];
}

function get_ms_single_box_membership_description() {
    return MS_Helper_Template::$ms_single_box['membership_description'];
}

function get_ms_single_box_membership_price() {
    return MS_Helper_Template::$ms_single_box['membership_price'];
}

function is_ms_single_box_msg() {
    return isset( MS_Helper_Template::$ms_single_box['msg'] );
}

function get_ms_single_box_msg() {
    return '' . MS_Helper_Template::$ms_single_box['msg'];
}

function is_ms_single_box_action_pay() {
    return MS_Helper_Membership::MEMBERSHIP_ACTION_PAY === MS_Helper_Template::$ms_single_box['action'];
}

function get_ms_single_box_payment_btn() {
    $html = '';
    ob_start();
    MS_Helper_Html::html_link( MS_Helper_Template::$ms_single_box['link'] );
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}

function get_ms_single_box_hidden_fields() {
    $html = '';
    ob_start();
    foreach ( MS_Helper_Template::$ms_single_box['fields'] as $field ) {
        $html .= MS_Helper_Html::html_element( $field );
    }
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}

function get_ms_single_box_btn() {
    
    $html = '';
    ob_start();
    MS_Helper_Html::html_element( MS_Helper_Template::$ms_single_box['button'] );
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}



/***************************** M2 Registration Form *****************************/

function ms_registration_form_obj() {
    return MS_Helper_Template::$ms_registration_form['m2_obj'];
}

function is_ms_registration_form_title_exists() {    
    return isset( MS_Helper_Template::$ms_registration_form['title'] );
}

function get_ms_registration_form_title() {
    return MS_Helper_Template::$ms_registration_form['title'];
}

function get_ms_registration_form_fields() {
    
    $html = '';
    ob_start();
    foreach ( MS_Helper_Template::$ms_registration_form['fields'] as $field ) {
        if ( is_string( $field ) ) {
                MS_Helper_Html::html_element( $field );
        } elseif ( MS_Helper_Html::INPUT_TYPE_HIDDEN == $field['type'] ) {
                MS_Helper_Html::html_element( $field );
        } else {
                ?>
                <div class="ms-form-element ms-form-element-<?php echo esc_attr( $field['id'] ); ?>">
                        <?php MS_Helper_Html::html_element( $field ); ?>
                </div>
                <?php
        }
    }
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}

function ms_registration_form_extra_fields() {
    
    /**
    * Trigger default WordPress action to allow other plugins
    * to add custom fields to the registration form.
    *
    * signup_extra_fields Defined in wp-signup.php which is used
    *              for Multisite signup process.
    *
    * register_form Defined in wp-login.php which is only used for
    *              Single site registration process.
    *
    * @since  1.0.0
    */
    if ( is_multisite() ) {
        do_action( 'signup_extra_fields', MS_Helper_Template::$ms_registration_form['empty_error'] );
    } else {
        do_action( 'register_form' );
    }
}

function get_ms_registration_form_register_button() {
    $html = '';
    ob_start();
    MS_Helper_Html::html_element( MS_Helper_Template::$ms_registration_form['register_button'] );
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}

function ms_registration_form_error() {
    if ( is_wp_error( MS_Helper_Template::$ms_registration_form['m2_reg_error'] ) ) {
        /**
         * Display registration errors.
         *
         * @since  1.0.0
         */
        do_action( 'registration_errors', MS_Helper_Template::$ms_registration_form['m2_reg_error'] );
    }
}

function is_ms_registration_form_login_link_exists() {
    return isset( MS_Helper_Template::$ms_registration_form['login_link_exists'] );
}

function get_ms_registration_form_login_link() {
    $html = '';
    ob_start();
    MS_Helper_Html::html_link( MS_Helper_Template::$ms_registration_form['login_link'] );
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}



/************************** M2 Frontend Payment Table ***************************/

function get_ms_payment_obj() {
    return MS_Helper_Template::$ms_front_payment['m2_payment_obj'];
}

function get_ms_payment_obj_data() {
    return MS_Helper_Template::$ms_front_payment['m2_payment_obj']->data;
}

function get_ms_payment_subscription() {
    return MS_Helper_Template::$ms_front_payment['subscription'];
}

function get_ms_payment_invoice() {
    return MS_Helper_Template::$ms_front_payment['invoice'];
}

function get_ms_pm_membership_wrapper_class() {    
    return MS_Helper_Template::$ms_front_payment['membership_wrapper_class'];
}

function get_ms_pm_alert_box_class() {
    return MS_Helper_Template::$ms_front_payment['alert_box_class'];
}

function get_ms_pm_message() {
    return MS_Helper_Template::$ms_front_payment['msg'];
}

function get_ms_pm_membership_name() {
    return MS_Helper_Template::$ms_front_payment['membership_name'];
}

function is_ms_pm_membership_description() {
    return lib3()->is_true( MS_Helper_Template::$ms_front_payment['is_membership_description'] );
}

function get_ms_pm_membership_description() {
    return MS_Helper_Template::$ms_front_payment['membership_description'];
}

function is_ms_pm_membership_free() {
    return lib3()->is_true( MS_Helper_Template::$ms_front_payment['is_membership_free'] );
}

function is_ms_pm_invoice_discount() {
    return lib3()->is_true( MS_Helper_Template::$ms_front_payment['invoice_discount'] );
}

function is_ms_pm_invoice_pro_rate() {
    return lib3()->is_true( MS_Helper_Template::$ms_front_payment['invoice_pro_rate'] );
}

function is_ms_pm_invoice_tax_rate() {
    return lib3()->is_true( MS_Helper_Template::$ms_front_payment['invoice_tax_rate'] );
}

function get_ms_pm_membership_price() {
    return MS_Helper_Template::$ms_front_payment['membership_price'];
}

function get_ms_pm_membership_formatted_price() {
    return MS_Helper_Template::$ms_front_payment['membership_formatted_price'];
}

function get_ms_pm_invoice_formatted_discount() {
    return MS_Helper_Template::$ms_front_payment['invoice_formatted_discount'];
}

function get_ms_pm_invoice_formatted_pro_rate() {
    return MS_Helper_Template::$ms_front_payment['invoice_formatted_pro_rate'];
}

function is_ms_pm_show_tax() {
    return lib3()->is_true( MS_Helper_Template::$ms_front_payment['show_tax'] );
}

function get_ms_pm_invoice_tax_name() {
    return MS_Helper_Template::$ms_front_payment['invoice_tax_name'];
}

function get_ms_pm_invoice_formatted_tax() {
    return MS_Helper_Template::$ms_front_payment['invoice_formatted_tax'];
}

function get_ms_pm_invoice_total() {
    return MS_Helper_Template::$ms_front_payment['invoice_total'];
}

function get_ms_pm_invoice_formatted_total_for_admin() {
    return MS_Helper_Template::$ms_front_payment['invoice_formatted_total_for_admin'];
}

function get_ms_pm_invoice_formatted_total() {
    return MS_Helper_Template::$ms_front_payment['invoice_formatted_total'];
}

function is_ms_pm_trial() {
    return lib3()->is_true( MS_Helper_Template::$ms_front_payment['is_trial'] );
}

function get_ms_pm_invoice_formatted_due_date() {
    return MS_Helper_Template::$ms_front_payment['invoice_formatted_due_date'];
}

function get_ms_pm_invoice_trial_price() {
    return MS_Helper_Template::$ms_front_payment['invoice_trial_price'];
}

function get_ms_pm_invoice_formatted_trial_price() {
    return MS_Helper_Template::$ms_front_payment['invoice_formatted_trial_price'];
}

function get_ms_pm_invoice_payment_description() {
    return MS_Helper_Template::$ms_front_payment['invoice_payment_description'];
}

function is_ms_pm_cancel_warning() {
    return lib3()->is_true( MS_Helper_Template::$ms_front_payment['cancel_warning'] );
}

function get_ms_pm_cancel_warning() {
    return MS_Helper_Template::$ms_front_payment['cancel_warning'];
}



/***************************** ACCOUNT PAGE *****************************/

function get_ms_ac_data() {
    return MS_Helper_Template::$ms_account;
}

function ms_is_user_logged_in() {
    return MS_Helper_Template::$ms_account['is_user_logged_in'];
}

function get_ms_ac_title() {
    return MS_Helper_Template::$ms_account['membership_title'];
}

function show_membership_change_link() {
    return lib3()->is_true( MS_Helper_Template::$ms_account['show_membership_change'] );
}

function get_ms_ac_signup_modified_url() {
    return MS_Helper_Template::$ms_account['signup_modified_url'];
}

function get_ms_ac_member_obj() {
    return MS_Helper_Template::$ms_account['member'];
}

function get_ms_ac_account_obj() {
    return MS_Helper_Template::$ms_account['m2_account_obj'];
}

function has_ms_ac_subscriptions() {
    return ! empty( MS_Helper_Template::$ms_account['m2_subscriptions'] );
}

function get_ms_ac_subscriptions() {
    return MS_Helper_Template::$ms_account['m2_subscriptions'];
}

function ms_account_the_membership( $subscription ) {
    MS_Helper_Template::$ms_account['subscription'] = $subscription;
    MS_Helper_Template::$ms_account['membership'] = $subscription->get_membership();
}

function prepare_ms_account_classes() {
    $subscription = MS_Helper_Template::$ms_account['subscription'];
    return array(
                'ms-subscription-' . $subscription->id,
                'ms-status-' . $subscription->status,
                'ms-type-' . $membership->type,
                'ms-payment-' . $membership->payment_type,
                'ms-gateway-' . $subscription->gateway_id,
                'ms-membership-' . $subscription->membership_id,
                $subscription->has_trial() ? 'ms-with-trial' : 'ms-no-trial',
        );
}

function get_ms_account_classes() {
    return esc_attr( implode( ' ', prepare_ms_account_classes() ) );
}

function get_ms_account_membership_name() {
    return esc_html( MS_Helper_Template::$ms_account['membership']->name );
}

function get_ms_account_membership_status() {
    if ( MS_Model_Relationship::STATUS_PENDING == MS_Helper_Template::$ms_account['subscription']->status ) {
            // Display a "Purchase" link when status is Pending
            $code = sprintf(
                    '[%s id="%s" label="%s"]',
                    MS_Helper_Shortcode::SCODE_MS_BUY,
                    MS_Helper_Template::$ms_account['membership']->id,
                    __( 'Pending', 'membership2' )
            );
            return do_shortcode( $code );
    } else {
            return esc_html( MS_Helper_Template::$ms_account['subscription']->status_text() );
    }
}

function get_ms_account_expire_date() {
    
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
    
    if ( in_array( MS_Helper_Template::$ms_account['subscription']->status, $no_expire_list ) ) {
            return '&nbsp;';
    } elseif ( in_array( MS_Helper_Template::$ms_account['subscription']->status, $trial_expire_list ) ) {
            return esc_html(
                    MS_Helper_Period::format_date( MS_Helper_Template::$ms_account['subscription']->trial_expire_date )
            );
    } elseif ( MS_Helper_Template::$ms_account['subscription']->expire_date ) {
            return esc_html(
                    MS_Helper_Period::format_date( MS_Helper_Template::$ms_account['subscription']->expire_date )
            );
    } else {
            return __( 'Never', 'membership2' );
    }
}

function get_ms_no_account_membership_status() {
    $cols = 3;
    
    if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) {
            $cols += 1;
    }

    return sprintf(
            '<tr><td colspan="%1$s">%2$s</td></tr>',
            $cols,
            __( '(No Membership)', 'membership2' )
    );
}

function is_ms_ac_show_profile() {
    return lib3()->is_true( MS_Helper_Template::$ms_account['show_profile'] );
}

function get_ms_ac_profile_title() {
    return MS_Helper_Template::$ms_account['profile_title'];
}

function is_ms_ac_show_profile_change() {
    return lib3()->is_true( MS_Helper_Template::$ms_account['show_profile_change'] );
}

function get_ms_ac_profile_change_link() {
    return MS_Helper_Template::$ms_account['profile_change_formatted_label'];
}

function get_ms_ac_profile_fields() {
    return MS_Helper_Template::$ms_account['fields']['personal_info'];
}

function get_ms_ac_profile_info( $field ) {
    return MS_Helper_Template::$ms_account['member']->$field;
}

function is_ms_ac_show_invoices() {
    return lib3()->is_true( MS_Helper_Template::$ms_account['show_invoices'] );
}

function get_ms_ac_invoices_title() {
    return MS_Helper_Template::$ms_account['invoices_title'];
}

function is_ms_ac_show_all_invoices() {
    return lib3()->is_true( MS_Helper_Template::$ms_account['show_all_invoices'] );
}

function get_ms_ac_invoices_detail_label() {
    return MS_Helper_Template::$ms_account['invoices_details_formatted_label'];
}

function get_ms_ac_invoices() {
    return MS_Helper_Template::$ms_account['invoices'];
}

function ms_account_the_invoice( $invoice ) {
    MS_Helper_Template::$ms_account['invoice'] = $invoice;
    MS_Helper_Template::$ms_account['inv_membership'] = MS_Factory::load( 'MS_Model_Membership', $invoice->membership_id );
}

function prepare_ms_invoice_classes() {
    return array(
                'ms-invoice-' . MS_Helper_Template::$ms_account['invoice']->id,
                'ms-subscription-' . MS_Helper_Template::$ms_account['invoice']->ms_relationship_id,
                'ms-invoice-' . MS_Helper_Template::$ms_account['invoice']->status,
                'ms-gateway-' . MS_Helper_Template::$ms_account['invoice']->gateway_id,
                'ms-membership-' . MS_Helper_Template::$ms_account['invoice']->membership_id,
                'ms-type-' . MS_Helper_Template::$ms_account['invoice']->type,
                'ms-payment-' . MS_Helper_Template::$ms_account['invoice']->payment_type,
        );
}

function get_ms_invoice_classes() {
    return esc_attr( implode( ' ', prepare_ms_invoice_classes() ) );
}

function get_ms_invoice_number() {
    return sprintf(
            '<a href="%s">%s</a>',
            get_permalink( MS_Helper_Template::$ms_account['invoice']->id ),
            MS_Helper_Template::$ms_account['invoice']->get_invoice_number()
    );
}

function get_ms_invoice_next_status() {
    return esc_html( MS_Helper_Template::$ms_account['invoice']->status_text() );
}

function get_ms_invoice_total() {
    return esc_html( MS_Helper_Billing::format_price( MS_Helper_Template::$ms_account['invoice']->total ) );
}

function get_ms_invoice_name() {
    return esc_html( MS_Helper_Template::$ms_account['inv_membership']->name );
}

function get_ms_invoice_due_date() {
    return esc_html(
                    MS_Helper_Period::format_date(
                            MS_Helper_Template::$ms_account['invoice']->due_date,
                            __( 'F j', 'membership2' )
                    )
            );
}

function is_ms_ac_show_activity() {
    return lib3()->is_true( MS_Helper_Template::$ms_account['show_activity'] );
}

function get_ms_ac_activity_title() {
    return MS_Helper_Template::$ms_account['activity_title'];
}

function is_ms_ac_show_all_activities() {
    return lib3()->is_true( MS_Helper_Template::$ms_account['show_all_activities'] );
}

function get_ms_ac_activity_details_label() {
    return MS_Helper_Template::$ms_account['activity_details_formatted_label'];
}

function get_ms_ac_events() {
    return MS_Helper_Template::$ms_account['events'];
}

function ms_account_the_event( $event ) {
    MS_Helper_Template::$ms_account['event'] = $event;
}

function prepare_ms_event_classes() {
    return array(
                    'ms-activity-topic-' . MS_Helper_Template::$ms_account['event']->topic,
                    'ms-activity-type-' . MS_Helper_Template::$ms_account['event']->type,
                    'ms-membership-' . MS_Helper_Template::$ms_account['event']->membership_id,
            );
}

function get_ms_event_classes() {
    return esc_attr( implode( ' ', prepare_ms_event_classes() ) );
}

function get_ms_event_date() {
    return esc_html(
            MS_Helper_Period::format_date(
                    MS_Helper_Template::$ms_account['event']->post_modified
            )
    );
}

function get_ms_event_description() {
    return esc_html( MS_Helper_Template::$ms_account['event']->description );
}

function has_ms_ac_login_form() {
    return lib3()->is_true( MS_Helper_Template::$ms_account['has_login_form'] );
}

function get_ms_ac_login_form() {
    return MS_Helper_Template::$ms_account['login_form_sc'];
}