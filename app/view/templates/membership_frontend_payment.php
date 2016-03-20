<div class="<?php echo $membership_wrapper_class; ?>">
        <legend><?php _e( 'Join Membership', 'membership2' ) ?></legend>
        <p class="ms-alert-box <?php echo $alert_box_class; ?>">
                <?php echo $msg; ?>
        </p>
        <table class="ms-purchase-table">
                <tr>
                        <td class="ms-title-column">
                                <?php _e( 'Name', 'membership2' ); ?>
                        </td>
                        <td class="ms-details-column">
                                <?php echo $membership_name; ?>
                        </td>
                </tr>

                <?php if ( $is_membership_description ) : ?>
                        <tr>
                                <td class="ms-title-column">
                                        <?php _e( 'Description', 'membership2' ); ?>
                                </td>
                                <td class="ms-desc-column">
                                        <span class="ms-membership-description"><?php
                                                echo $membership_description;
                                        ?></span>
                                </td>
                        </tr>
                <?php endif; ?>

                <?php if ( ! $is_membership_free ) : ?>
                        <?php if ( $invoice_discount || $invoice_pro_rate || $invoice_tax_rate ) : ?>
                        <tr>
                                <td class="ms-title-column">
                                        <?php _e( 'Price', 'membership2' ); ?>
                                </td>
                                <td class="ms-details-column">
                                        <?php
                                        if ( $membership_price > 0 ) {
                                                echo $membership_formatted_price;
                                        } else {
                                                _e( 'Free', 'membership2' );
                                        }
                                        ?>
                                </td>
                        </tr>
                        <?php endif; ?>

                        <?php if ( $invoice_discount ) : ?>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php _e( 'Coupon Discount', 'membership2' ); ?>
                                        </td>
                                        <td class="ms-price-column">
                                                <?php echo $invoice_formatted_discount ?>
                                        </td>
                                </tr>
                        <?php endif; ?>

                        <?php if ( $invoice_pro_rate ) : ?>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php _e( 'Pro-Rate Discount', 'membership2' ); ?>
                                        </td>
                                        <td class="ms-price-column">
                                                <?php echo $invoice_formatted_pro_rate; ?>
                                        </td>
                                </tr>
                        <?php endif; ?>

                        <?php if ( $show_tax ) : ?>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php echo $invoice_tax_name; ?>
                                        </td>
                                        <td class="ms-price-column">
                                                <?php echo $invoice_formatted_tax; ?>
                                        </td>
                                </tr>
                        <?php endif; ?>

                        <tr>
                                <td class="ms-title-column">
                                        <?php _e( 'Total', 'membership2' ); ?>
                                </td>
                                <td class="ms-price-column ms-total">
                                        <?php
                                        if ( $invoice_total > 0 ) {
                                            if ( $is_ms_admin_user ) {
                                                echo $invoice_formatted_total_for_admin;
                                            }else{
                                                echo $invoice_formatted_total;
                                            }
                                        } else {
                                                _e( 'Free', 'membership2' );
                                        }
                                        ?>
                                </td>
                        </tr>

                        <?php if ( $is_trial ) : ?>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php _e( 'Payment due', 'membership2' ); ?>
                                        </td>
                                        <td class="ms-desc-column"><?php
                                                echo $invoice_formatted_due_date;
                                        ?></td>
                                </tr>
                                <tr>
                                        <td class="ms-title-column">
                                                <?php _e( 'Trial price', 'membership2' ); ?>
                                        </td>
                                        <td class="ms-desc-column">
                                        <?php
                                        if ( $invoice_trial_price > 0 ) {
                                                echo $invoice_formatted_trial_price;
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
                                $subscription,
                                $invoice,
                                $m2_obj
                        );
                        ?>

                        <tr>
                                <td class="ms-desc-column" colspan="2">
                                        <span class="ms-membership-description"><?php
                                                echo $invoice_payment_description;
                                        ?></span>
                                </td>
                        </tr>
                <?php endif; ?>

                <?php if ( $cancel_warning ) : ?>
                        <tr>
                                <td class="ms-desc-warning" colspan="2">
                                        <span class="ms-cancel-other-memberships"><?php
                                                echo $cancel_warning;
                                        ?></span>
                                </td>
                        </tr>
                <?php endif;

                if ( $is_ms_admin_user ) : ?>
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
                                $subscription,
                                $invoice,
                                $m2_obj
                        );
                endif;
                ?>
        </table>
</div>
<?php
do_action( 'ms_view_frontend_payment_after', $m2_obj_data, $m2_obj );
do_action( 'ms_show_prices' );

if ( $show_tax ) {
        do_action( 'ms_tax_editor', $invoice );
}
?>
<div style="clear:both;"></div>