<div class="<?php echo get_ms_pm_membership_wrapper_class(); ?>">
        <legend><?php _e( 'Join Membership', 'membership2' ) ?></legend>
        <p class="ms-alert-box <?php echo get_ms_pm_alert_box_class(); ?>">
                <?php echo get_ms_pm_message(); ?>
        </p>
        <table class="ms-purchase-table">
                <tr>
                        <td class="ms-title-column">
                                <?php _e( 'Name', 'membership2' ); ?>
                        </td>
                        <td class="ms-details-column">
                                <?php echo get_ms_pm_membership_name(); ?>
                        </td>
                </tr>

                <?php if ( is_ms_pm_membership_description() ) : ?>
                        <tr>
                                <td class="ms-title-column">
                                        <?php _e( 'Description', 'membership2' ); ?>
                                </td>
                                <td class="ms-desc-column">
                                        <span class="ms-membership-description"><?php
                                                echo get_ms_pm_membership_description();
                                        ?></span>
                                </td>
                        </tr>
                <?php endif; ?>

                <?php if ( ! is_ms_pm_membership_free() ) : ?>
                        <?php if ( is_ms_pm_invoice_discount() || is_ms_pm_invoice_pro_rate() || is_ms_pm_invoice_tax_rate() ) : ?>
                        <tr>
                                <td class="ms-title-column">
                                        <?php _e( 'Price', 'membership2' ); ?>
                                </td>
                                <td class="ms-details-column">
                                        <?php
                                        if ( get_ms_pm_membership_price() > 0 ) {
                                                echo get_ms_pm_membership_formatted_price();
                                        } else {
                                                _e( 'Free', 'membership2' );
                                        }
                                        ?>
                                </td>
                        </tr>
                        <?php endif; ?>

                        <?php if ( is_ms_pm_invoice_discount() ) : ?>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php _e( 'Coupon Discount', 'membership2' ); ?>
                                        </td>
                                        <td class="ms-price-column">
                                                <?php echo get_ms_pm_invoice_formatted_discount(); ?>
                                        </td>
                                </tr>
                        <?php endif; ?>

                        <?php if ( is_ms_pm_invoice_pro_rate() ) : ?>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php _e( 'Pro-Rate Discount', 'membership2' ); ?>
                                        </td>
                                        <td class="ms-price-column">
                                                <?php echo get_ms_pm_invoice_formatted_pro_rate(); ?>
                                        </td>
                                </tr>
                        <?php endif; ?>

                        <?php if ( is_ms_pm_show_tax() ) : ?>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php echo get_ms_pm_invoice_tax_name(); ?>
                                        </td>
                                        <td class="ms-price-column">
                                                <?php echo get_ms_pm_invoice_formatted_tax(); ?>
                                        </td>
                                </tr>
                        <?php endif; ?>

                        <tr>
                                <td class="ms-title-column">
                                        <?php _e( 'Total', 'membership2' ); ?>
                                </td>
                                <td class="ms-price-column ms-total">
                                        <?php
                                        if ( get_ms_pm_invoice_total() > 0 ) {
                                            if ( is_ms_admin_user() ) {
                                                echo get_ms_pm_invoice_formatted_total_for_admin();
                                            }else{
                                                echo get_ms_pm_invoice_formatted_total();
                                            }
                                        } else {
                                                _e( 'Free', 'membership2' );
                                        }
                                        ?>
                                </td>
                        </tr>

                        <?php if ( is_ms_pm_trial() ) : ?>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php _e( 'Payment due', 'membership2' ); ?>
                                        </td>
                                        <td class="ms-desc-column"><?php
                                                echo get_ms_pm_invoice_formatted_due_date();
                                        ?></td>
                                </tr>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php _e( 'Trial price', 'membership2' ); ?>
                                        </td>
                                        <td class="ms-desc-column">
                                        <?php
                                        if ( get_ms_pm_invoice_trial_price() > 0 ) {
                                                echo get_ms_pm_invoice_formatted_trial_price();
                                        } else {
                                                _e( 'Free', 'membership2' );
                                        }
                                        ?>
                                        </td>
                                </tr>
                        <?php endif; ?>

                        <?php
                        do_action(
                                'ms_view_frontend_payment_after_total_row',
                                get_ms_payment_subscription(),
                                get_ms_payment_invoice(),
                                get_ms_payment_obj()
                        );
                        ?>

                        <tr>
                                <td class="ms-desc-column" colspan="2">
                                        <span class="ms-membership-description"><?php
                                                echo get_ms_pm_invoice_payment_description();
                                        ?></span>
                                </td>
                        </tr>
                <?php endif; ?>

                <?php if ( is_ms_pm_cancel_warning() ) : ?>
                        <tr>
                                <td class="ms-desc-warning" colspan="2">
                                        <span class="ms-cancel-other-memberships"><?php
                                                echo get_ms_pm_cancel_warning();
                                        ?></span>
                                </td>
                        </tr>
                <?php endif;

                if ( is_ms_admin_user() ) : ?>
                        <tr>
                                <td class="ms-desc-adminnote" colspan="2">
                                        <em><?php
                                        _e( 'As admin user you already have access to this membership', 'membership2' );
                                        ?></em>
                                </td>
                        </tr>
                <?php else :
                        do_action(
                                'ms_view_frontend_payment_purchase_button',
                                get_ms_payment_subscription(),
                                get_ms_payment_invoice(),
                                get_ms_payment_obj()
                        );
                endif;
                ?>
        </table>
</div>
<?php
do_action( 'ms_view_frontend_payment_after', get_ms_payment_obj_data(), get_ms_payment_obj() );
do_action( 'ms_show_prices' );

if ( is_ms_pm_show_tax() ) {
        do_action( 'ms_tax_editor', get_ms_payment_invoice() );
}
?>
<div style="clear:both;"></div>