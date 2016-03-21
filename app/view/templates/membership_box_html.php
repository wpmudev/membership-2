<div id="ms-membership-wrapper-<?php echo get_ms_single_box_membership_id(); ?>" class="<?php echo get_ms_single_box_wrapper_classes(); ?>">
        <div class="ms-top-bar">
                <h4><span class="ms-title"><?php echo get_ms_single_box_membership_name(); ?></span></h4>
        </div>
        <div class="ms-price-details">
                <div class="ms-description"><?php echo get_ms_single_box_membership_description(); ?></div>
                <div class="ms-price price"><?php echo get_ms_single_box_membership_price(); ?></div>

                <?php if ( is_ms_single_box_msg() ) : ?>
                        <div class="ms-bottom-msg"><?php echo get_ms_single_box_msg(); ?></div>
                <?php endif; ?>
        </div>

        <div class="ms-bottom-bar">
                <?php
                if ( is_ms_single_box_action_pay() ) {
                    echo get_ms_single_box_payment_btn();
                }
                
                echo get_ms_single_box_hidden_fields();

                /**
                 * It's possible to add custom fields to the signup box.
                 *
                 * @since  1.0.1.2
                 */
                do_action( 'ms_shortcode_signup_form_end', $m2_obj );

                echo get_ms_single_box_btn();
                ?>
        </div>
</div>