<?php
/**
 * Template functions that can be used to create new templates
 * for several M2 pages
 *
 * @since 1.0.3
 *
 * @package Membership2
 */

$ms_single_box = array();

$ms_registration_form = array();

$ms_payment = array();

$ms_memberships = array();
$ms_invoices = array();
$ms_events = array();

function is_ms_admin_user() {
    return MS_Model_Member::is_admin_user();
}

/***************************** Single Membership Box *****************************/

function ms_single_box_prepare( $data = array() ) {
    global $ms_single_box;
    $ms_single_box = $data;
}

function get_ms_single_box_membership_id() {
    global $ms_single_box;
    return $ms_single_box['membership_id'];
}

function get_ms_single_box_wrapper_classes() {
    global $ms_single_box;
    return $ms_single_box['membership_wrapper_classes'];
}

function get_ms_single_box_membership_name() {
    global $ms_single_box;
    return $ms_single_box['membership_name'];
}

function get_ms_single_box_membership_description() {
    global $ms_single_box;
    return $ms_single_box['membership_description'];
}

function get_ms_single_box_membership_price() {
    global $ms_single_box;
    return $ms_single_box['membership_price'];
}

function is_ms_single_box_msg() {
    global $ms_single_box;
    return isset( $ms_single_box['msg'] );
}

function get_ms_single_box_msg() {
    global $ms_single_box;
    return '' . $ms_single_box['msg'];
}

function is_ms_single_box_action_pay() {
    global $ms_single_box;
    return MS_Helper_Membership::MEMBERSHIP_ACTION_PAY === $ms_single_box['action'];
}

function get_ms_single_box_payment_btn() {
    global $ms_single_box;
    $html = '';
    ob_start();
    MS_Helper_Html::html_link( $ms_single_box['link'] );
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}

function get_ms_single_box_hidden_fields() {
    global $ms_single_box;
    $html = '';
    ob_start();
    foreach ( $ms_single_box['fields'] as $field ) {
        $html .= MS_Helper_Html::html_element( $field );
    }
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}

function get_ms_single_box_btn() {
    global $ms_single_box;
    $html = '';
    ob_start();
    MS_Helper_Html::html_element( $ms_single_box['button'] );
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}



/***************************** M2 Registration Form *****************************/

function ms_registration_form_prepare( $data = array() ) {
    global $ms_registration_form;
    $ms_registration_form = $data;
}

function is_ms_registration_form_title_exists() {
    global $ms_registration_form;
    return isset( $ms_registration_form['title'] );
}

function get_ms_registration_form_title() {
    global $ms_registration_form;
    return $ms_registration_form['title'];
}

function get_ms_registration_form_fields() {
    global $ms_registration_form;
    global $ms_single_box;
    $html = '';
    ob_start();
    foreach ( $ms_registration_form['fields'] as $field ) {
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
    global $ms_registration_form;
    
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
        do_action( 'signup_extra_fields', $ms_registration_form['empty_error'] );
    } else {
        do_action( 'register_form' );
    }
}

function get_ms_registration_form_register_button() {
    global $ms_registration_form;
    $html = '';
    ob_start();
    MS_Helper_Html::html_element( $ms_registration_form['register_button'] );
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}

function ms_registration_form_error() {
    global $ms_registration_form;
    if ( is_wp_error( $ms_registration_form['m2_reg_error'] ) ) {
        /**
         * Display registration errors.
         *
         * @since  1.0.0
         */
        do_action( 'registration_errors', $ms_registration_form['m2_reg_error'] );
    }
}

function is_ms_registration_form_login_link_exists() {
    global $ms_registration_form;
    return isset( $ms_registration_form['login_link_exists'] );
}

function get_ms_registration_form_login_link() {
    global $ms_registration_form;
    $html = '';
    ob_start();
    MS_Helper_Html::html_link( $ms_registration_form['login_link'] );
    $html = ob_get_contents();
    ob_end_clean();
    
    return $html;
}



/************************** M2 Frontend Payment Table ***************************/
function ms_payment_prepare( $data = array() ) {
    global $ms_payment;
    $ms_payment = $data;
}

function get_ms_pm_membership_wrapper_class() {
    global $ms_payment;
    return $ms_payment['membership_wrapper_class'];
}

function get_ms_pm_alert_box_class() {
    global $ms_payment;
    return $ms_payment['alert_box_class'];
}

function get_ms_pm_message() {
    global $ms_payment;
    return $ms_payment['msg'];
}

function get_ms_pm_membership_name() {
    global $ms_payment;
    return $ms_payment['membership_name'];
}

function is_ms_pm_membership_description() {
    global $ms_payment;
    return lib3()->is_true( $ms_payment['is_membership_description'] );
}

function get_ms_pm_membership_description() {
    global $ms_payment;
    return $ms_payment['membership_description'];
}

function is_ms_pm_membership_free() {
    global $ms_payment;
    return lib3()->is_true( $ms_payment['is_membership_free'] );
}

function is_ms_pm_invoice_discount() {
    global $ms_payment;
    return lib3()->is_true( $ms_payment['invoice_discount'] );
}

function is_ms_pm_invoice_pro_rate() {
    global $ms_payment;
    return lib3()->is_true( $ms_payment['invoice_pro_rate'] );
}

function is_ms_pm_invoice_tax_rate() {
    global $ms_payment;
    return lib3()->is_true( $ms_payment['invoice_tax_rate'] );
}

function get_ms_pm_membership_price() {
    global $ms_payment;
    return $ms_payment['membership_price'];
}

function get_ms_pm_membership_formatted_price() {
    global $ms_payment;
    return $ms_payment['membership_formatted_price'];
}

function get_ms_pm_invoice_formatted_discount() {
    global $ms_payment;
    return $ms_payment['invoice_formatted_discount'];
}

function get_ms_pm_invoice_formatted_pro_rate() {
    global $ms_payment;
    return $ms_payment['invoice_formatted_pro_rate'];
}

function is_ms_pm_show_tax() {
    global $ms_payment;
    return lib3()->is_true( $ms_payment['show_tax'] );
}

function get_ms_pm_invoice_tax_name() {
    global $ms_payment;
    return $ms_payment['invoice_tax_name'];
}

function get_ms_pm_invoice_formatted_tax() {
    global $ms_payment;
    return $ms_payment['invoice_formatted_tax'];
}

function get_ms_pm_invoice_total() {
    global $ms_payment;
    return $ms_payment['invoice_total'];
}

function get_ms_pm_invoice_formatted_total_for_admin() {
    global $ms_payment;
    return $ms_payment['invoice_formatted_total_for_admin'];
}

function get_ms_pm_invoice_formatted_total() {
    global $ms_payment;
    return $ms_payment['invoice_formatted_total'];
}

function is_ms_pm_trial() {
    global $ms_payment;
    return lib3()->is_true( $ms_payment['is_trial'] );
}

function get_ms_pm_invoice_formatted_due_date() {
    global $ms_payment;
    return $ms_payment['invoice_formatted_due_date'];
}

function get_ms_pm_invoice_trial_price() {
    global $ms_payment;
    return $ms_payment['invoice_trial_price'];
}

function get_ms_pm_invoice_formatted_trial_price() {
    global $ms_payment;
    return $ms_payment['invoice_formatted_trial_price'];
}

function get_ms_pm_invoice_payment_description() {
    global $ms_payment;
    return $ms_payment['invoice_payment_description'];
}

function is_ms_pm_cancel_warning() {
    global $ms_payment;
    return lib3()->is_true( $ms_payment['cancel_warning'] );
}

function get_ms_pm_cancel_warning() {
    global $ms_payment;
    return $ms_payment['cancel_warning'];
}








/***************************** ACCOUNT PAGE *****************************/

function ms_account_the_membership( $subscription ) {
    global $ms_memberships;
    $ms_memberships['subscription'] = $subscription;
    $ms_memberships['membership'] = $subscription->get_membership();
}

function prepare_ms_account_classes() {
    global $ms_memberships;
    $subscription = $ms_memberships['subscription'];
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
    global $ms_memberships;
    return esc_attr( implode( ' ', prepare_ms_account_classes() ) );
}

function get_ms_account_membership_name() {
    global $ms_memberships;
    return esc_html( $ms_memberships['membership']->name );
}

function get_ms_account_membership_status() {
    global $ms_memberships;
    if ( MS_Model_Relationship::STATUS_PENDING == $ms_memberships['subscription']->status ) {
            // Display a "Purchase" link when status is Pending
            $code = sprintf(
                    '[%s id="%s" label="%s"]',
                    MS_Helper_Shortcode::SCODE_MS_BUY,
                    $ms_memberships['membership']->id,
                    __( 'Pending', 'membership2' )
            );
            return do_shortcode( $code );
    } else {
            return esc_html( $ms_memberships['subscription']->status_text() );
    }
}

function get_ms_account_expire_date() {
    global $ms_membership;
    
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
    
    if ( in_array( $ms_memberships['subscription']->status, $no_expire_list ) ) {
            return '&nbsp;';
    } elseif ( in_array( $ms_memberships['subscription']->status, $trial_expire_list ) ) {
            return esc_html(
                    MS_Helper_Period::format_date( $ms_memberships['subscription']->trial_expire_date )
            );
    } elseif ( $ms_memberships['subscription']->expire_date ) {
            return esc_html(
                    MS_Helper_Period::format_date( $ms_memberships['subscription']->expire_date )
            );
    } else {
            return __( 'Never', 'membership2' );
    }
}

function get_ms_no_account_membership_status() {
    global $ms_membership;
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

function ms_account_the_invoice( $invoice ) {
    global $ms_invoices;
    $ms_invoices['invoice'] = $invoice;
    $ms_invoices['inv_membership'] = MS_Factory::load( 'MS_Model_Membership', $invoice->membership_id );
}

function prepare_ms_invoice_classes() {
    global $ms_invoices;
    return array(
                'ms-invoice-' . $ms_invoices['invoice']->id,
                'ms-subscription-' . $ms_invoices['invoice']->ms_relationship_id,
                'ms-invoice-' . $ms_invoices['invoice']->status,
                'ms-gateway-' . $ms_invoices['invoice']->gateway_id,
                'ms-membership-' . $ms_invoices['invoice']->membership_id,
                'ms-type-' . $ms_invoices['invoice']->type,
                'ms-payment-' . $ms_invoices['invoice']->payment_type,
        );
}

function get_ms_invoice_classes() {
    global $ms_invoices;
    return esc_attr( implode( ' ', prepare_ms_invoice_classes() ) );
}

function get_ms_invoice_number() {
    global $ms_invoices;
    return sprintf(
            '<a href="%s">%s</a>',
            get_permalink( $ms_invoices['invoice']->id ),
            $ms_invoices['invoice']->get_invoice_number()
    );
}

function get_ms_invoice_next_status() {
    global $ms_invoices;
    return esc_html( $ms_invoices['invoice']->status_text() );
}

function get_ms_invoice_total() {
    global $ms_invoices;
    return esc_html( MS_Helper_Billing::format_price( $ms_invoices['invoice']->total ) );
}

function get_ms_invoice_name() {
    global $ms_invoices;
    return esc_html( $ms_invoices['inv_membership']->name );
}

function get_ms_invoice_due_date() {
    global $ms_invoices;
    return esc_html(
                    MS_Helper_Period::format_date(
                            $ms_invoices['invoice']->due_date,
                            __( 'F j', 'membership2' )
                    )
            );
}

function ms_account_the_event( $event ) {
    global $ms_events;
    $ms_events['event'] = $event;
}

function prepare_ms_event_classes() {
    global $ms_events;
    return array(
                    'ms-activity-topic-' . $ms_events['event']->topic,
                    'ms-activity-type-' . $ms_events['event']->type,
                    'ms-membership-' . $ms_events['event']->membership_id,
            );
}

function get_ms_event_classes() {
    global $ms_events;
    return esc_attr( implode( ' ', prepare_ms_event_classes() ) );
}

function get_ms_event_date() {
    global $ms_events;
    return esc_html(
            MS_Helper_Period::format_date(
                    $ms_events['event']->post_modified
            )
    );
}

function get_ms_event_description() {
    global $ms_events;
    return esc_html( $ms_events['event']->description );
}