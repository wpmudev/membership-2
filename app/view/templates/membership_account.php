<div class="ms-account-wrapper">
        <?php if ( ms_is_user_logged_in() ) : ?>
                
                <?php if( ms_show_users_membership() ) : ?>
                <div id="account-membership">
                <h2>
                        <?php
                        echo get_ms_ac_title();
                        
                        if ( show_membership_change_link() ) {
                                echo get_ms_ac_signup_modified_url();
                        }
                        ?>
                </h2>
                <?php
                /**
                 * Add custom content right before the memberships list.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_memberships_top', get_ms_ac_member_obj(), get_ms_ac_account_obj() );

                if ( is_ms_admin_user() ) {
                        _e( 'You are an admin user and have access to all memberships', 'membership2' );
                } else {
                        if ( has_ms_ac_subscriptions() ) {
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
                                        $m2_subscriptions = get_ms_ac_subscriptions();
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
                do_action( 'ms_view_account_memberships_bottom', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
                ?>
                </div>
                <?php endif; ?>
                

                <?php
                // ===================================================== PROFILE
                if ( is_ms_ac_show_profile() ) : ?>
                <div id="account-profile">
                <h2>
                        <?php
                        echo get_ms_ac_profile_title();

                        if ( is_ms_ac_show_profile_change() ) {
                                echo get_ms_ac_profile_change_link();
                        }
                        ?>
                </h2>
                <?php
                /**
                 * Add custom content right before the profile overview.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_profile_top', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
                ?>
                <table>
                        <?php $profile_fields = get_ms_ac_profile_fields(); ?>
                        <?php foreach ( $profile_fields as $field => $title ) : ?>
                                <tr>
                                        <th class="ms-label-title"><?php echo esc_html( $title ); ?>: </th>
                                        <td class="ms-label-field"><?php echo esc_html( get_ms_ac_profile_info( $field ) ); ?></td>
                                </tr>
                        <?php endforeach; ?>
                </table>
                <?php
                do_action( 'ms_view_account_profile_before_card', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
                
                
                do_action( 'ms_view_shortcode_account_card_info', get_ms_ac_data() );

                /**
                 * Add custom content right after the profile overview.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_profile_bottom', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
                ?>
                </div>
                <?php
                endif;
                // END: if ( $show_profile )
                // =============================================================
                ?>

                <?php
                // ==================================================== INVOICES
                if ( is_ms_ac_show_invoices() ) : ?>
                <div id="account-invoices">
                <h2>
                        <?php
                        echo get_ms_ac_invoices_title();

                        if ( is_ms_ac_show_all_invoices() ) {
                                echo get_ms_ac_invoices_detail_label();
                        }
                        ?>
                </h2>
                <?php
                /**
                 * Add custom content right before the invoice overview list.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_invoices_top', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
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
                        <?php $m2_invoices = get_ms_ac_invoices(); ?>
                        <?php foreach ( $m2_invoices as $invoice ) :
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
                do_action( 'ms_view_account_invoices_bottom', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
                ?>
                </div>
                <?php
                endif;
                ?>

                <?php
                // ==================================================== ACTIVITY
                if ( is_ms_ac_show_activity() ) : ?>
                <div id="account-activity">
                <h2>
                        <?php
                        echo get_ms_ac_activity_title();

                        if ( is_ms_ac_show_all_activities() ) {
                                echo get_ms_ac_activity_details_label();
                        }
                        ?>
                </h2>
                <?php
                /**
                 * Add custom content right before the activities overview list.
                 *
                 * @since  1.0.0
                 */
                do_action( 'ms_view_account_activity_top', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
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
                        <?php $m2_events = get_ms_ac_events(); ?>
                        <?php foreach ( $m2_events as $event ) :
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
                do_action( 'ms_view_account_activity_bottom', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
                ?>
                </div>
                <?php
                endif;
                ?>

        <?php else :
                
                if ( ! has_ms_ac_login_form() ) {
                        echo get_ms_ac_login_form();
                }
        endif; ?>
</div>