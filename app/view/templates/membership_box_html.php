<div id="ms-membership-wrapper-<?php echo $membership_id; ?>" class="<?php echo $membership_wrapper_classes; ?>">
        <div class="ms-top-bar">
                <h4><span class="ms-title"><?php echo $membership_name; ?></span></h4>
        </div>
        <div class="ms-price-details">
                <div class="ms-description"><?php echo $membership_description; ?></div>
                <div class="ms-price price"><?php echo $membership_price; ?></div>

                <?php if ( $msg ) : ?>
                        <div class="ms-bottom-msg"><?php echo '' . $msg; ?></div>
                <?php endif; ?>
        </div>

        <div class="ms-bottom-bar">
                <?php
                if ( MS_Helper_Membership::MEMBERSHIP_ACTION_PAY === $action ) {
                    MS_Helper_Html::html_link( $link );
                }
                
                /**
                 * This is generating HTML, you can customize the fields in template in theme
                 */
                foreach ( $fields as $field ) {
                        MS_Helper_Html::html_element( $field );
                }

                /**
                 * It's possible to add custom fields to the signup box.
                 *
                 * @since  1.0.1.2
                 */
                do_action( 'ms_shortcode_signup_form_end', $this );

                MS_Helper_Html::html_element( $button );
                ?>
        </div>
</div>