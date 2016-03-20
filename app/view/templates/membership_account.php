<div class="ms-account-wrapper">
        <?php if ( $is_user_logged_in ) : ?>

                <?php
                // ================================================= MEMBERSHIPS
                if ( $show_membership ) : ?>
                <div id="account-membership">
                <h2>
                        <?php
                        echo $membership_title;
                        
                        if ( $show_membership_change ) {
                                echo $signup_modified_url;
                        }
                        ?>
                </h2>
                <?php
                /**
                 * Add custom content right before the memberships list.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_memberships_top', $member, $m2_obj );

                if ( MS_Model_Member::is_admin_user() ) {
                        _e( 'You are an admin user and have access to all memberships', 'membership2' );
                } else {
                        if ( ! empty( $m2_subscriptions ) ) {
                                ?>
                                <table>
                                        <tr>
                                                <th class="ms-col-membership"><?php
                                                        _e( 'Membership name', 'membership2' );
                                                ?></th>
                                                <th class="ms-col-status"><?php
                                                        _e( 'Status', 'membership2' );
                                                ?></th>
                                                <th class="ms-col-expire-date"><?php
                                                        _e( 'Expire date', 'membership2' );
                                                ?></th>
                                        </tr>
                                        <?php
                                        $empty = true;
                                        foreach ( $m2_subscriptions as $subscription ) :
                                                $empty = false;
                                                ms_account_the_membership( $subscription );
                                                ?>
                                                <tr class="<?php echo get_ms_account_classes(); ?>">
                                                        <td class="ms-col-membership"><?php echo get_ms_account_membership_name(); ?></td>
                                                        <td class="ms-col-status"><?php echo get_ms_account_membership_status(); ?></td>
                                                        <td class="ms-col-expire-date"><?php echo get_ms_account_expire_date(); ?></td>
                                                </tr>
                                        <?php
                                        endforeach;

                                        if ( $empty ) {
                                                echo get_ms_no_account_membership_status();
                                        }
                                        ?>
                                </table>
                        <?php
                        } else {
                                _e( 'No memberships', 'membership2' );
                        }
                }
                /**
                 * Add custom content right after the memberships list.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_memberships_bottom', $member, $m2_obj );
                ?>
                </div>
                <?php
                endif;
                // END: if ( $show_membership )
                // =============================================================
                ?>

                <?php
                // ===================================================== PROFILE
                if ( $show_profile ) : ?>
                <div id="account-profile">
                <h2>
                        <?php
                        echo $profile_title;

                        if ( $show_profile_change ) {
                                echo $profile_change_formatted_label;
                        }
                        ?>
                </h2>
                <?php
                /**
                 * Add custom content right before the profile overview.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_profile_top', $member, $m2_obj );
                ?>
                <table>
                        <?php foreach ( $fields['personal_info'] as $field => $title ) : ?>
                                <tr>
                                        <th class="ms-label-title"><?php echo esc_html( $title ); ?>: </th>
                                        <td class="ms-label-field"><?php echo esc_html( $m2_obj->data['member']->$field ); ?></td>
                                </tr>
                        <?php endforeach; ?>
                </table>
                <?php
                do_action( 'ms_view_shortcode_account_card_info', $m2_obj->data );

                /**
                 * Add custom content right after the profile overview.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_profile_bottom', $member, $m2_obj );
                ?>
                </div>
                <?php
                endif;
                // END: if ( $show_profile )
                // =============================================================
                ?>

                <?php
                // ==================================================== INVOICES
                if ( $show_invoices ) : ?>
                <div id="account-invoices">
                <h2>
                        <?php
                        echo $invoices_title;

                        if ( $show_all_invoices ) {
                                echo $invoices_details_formatted_label;
                        }
                        ?>
                </h2>
                <?php
                /**
                 * Add custom content right before the invoice overview list.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_invoices_top', $member, $m2_obj );
                ?>
                <table>
                        <thead>
                                <tr>
                                        <th class="ms-col-invoice-no"><?php
                                                _e( 'Invoice #', 'membership2' );
                                        ?></th>
                                        <th class="ms-col-invoice-status"><?php
                                                _e( 'Status', 'membership2' );
                                        ?></th>
                                        <th class="ms-col-invoice-total"><?php
                                        printf(
                                                '%s (%s)',
                                                __( 'Total', 'membership2' ),
                                                MS_Plugin::instance()->settings->currency
                                        );
                                        ?></th>
                                        <th class="ms-col-invoice-title"><?php
                                                _e( 'Membership', 'membership2' );
                                        ?></th>
                                        <th class="ms-col-invoice-due"><?php
                                                _e( 'Due date', 'membership2' );
                                        ?></th>
                                </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $m2_obj->data['invoices'] as $invoice ) :
                                ms_account_the_invoice( $invoice );
                                ?>
                                <tr class="<?php echo get_ms_invoice_classes(); ?>">
                                        <td class="ms-col-invoice-no"><?php echo get_ms_invoice_number(); ?></td>
                                        <td class="ms-col-invoice-status"><?php echo get_ms_invoice_next_status(); ?></td>
                                        <td class="ms-col-invoice-total"><?php echo get_ms_invoice_total(); ?></td>
                                        <td class="ms-col-invoice-title"><?php echo get_ms_invoice_name(); ?></td>
                                        <td class="ms-col-invoice-due"><?php echo get_ms_invoice_due_date(); ?></td>
                                </tr>
                        <?php endforeach; ?>
                        </tbody>
                </table>
                <?php
                /**
                 * Add custom content right after the invoices overview list.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_invoices_bottom', $member, $m2_obj );
                ?>
                </div>
                <?php
                endif;
                // END: if ( $show_invoices )
                // =============================================================
                ?>

                <?php
                // ==================================================== ACTIVITY
                if ( $show_activity ) : ?>
                <div id="account-activity">
                <h2>
                        <?php
                        echo $activity_title;

                        if ( $show_all_activities ) {
                                echo $activity_details_formatted_label;
                        }
                        ?>
                </h2>
                <?php
                /**
                 * Add custom content right before the activities overview list.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_activity_top', $member, $m2_obj );
                ?>
                <table>
                        <thead>
                                <tr>
                                        <th class="ms-col-activity-date"><?php
                                                _e( 'Date', 'membership2' );
                                        ?></th>
                                        <th class="ms-col-activity-title"><?php
                                                _e( 'Activity', 'membership2' );
                                        ?></th>
                                </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $m2_obj->data['events'] as $event ) :
                                ms_account_the_event( $event );
                                ?>
                                <tr class="<?php echo get_ms_event_classes(); ?>">
                                        <td class="ms-col-activity-date"><?php echo get_ms_event_date(); ?></td>
                                        <td class="ms-col-activity-title"><?php echo get_ms_event_description(); ?></td>
                                </tr>
                        <?php endforeach; ?>
                        </tbody>
                </table>
                <?php
                /**
                 * Add custom content right after the activities overview list.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_activity_bottom', $member, $m2_obj );
                ?>
                </div>
                <?php
                endif;
                // END: if ( $show_activity )
                // =============================================================
                ?>

        <?php else :
                
                if ( ! $has_login_form ) {
                        echo $login_form_sc;
                }
        endif; ?>
</div>