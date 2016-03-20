<?php if ( ! empty( $title ) ) : ?>
        <legend><?php echo $title; ?></legend>
<?php endif; ?>

<?php foreach ( $fields as $field ) {
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

echo '<div class="ms-extra-fields">';

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
        do_action( 'signup_extra_fields', $empty_error );
} else {
        do_action( 'register_form' ); // Always on the register form.
}

echo '</div>';

MS_Helper_Html::html_element( $register_button );

if ( is_wp_error( $m2_reg_error ) ) {
        /**
         * Display registration errors.
         *
         * @since  1.0.0
         */
        do_action( 'registration_errors', $m2_reg_error );
}

/**
 * This hook is intended to output hidden fields or JS code
 * at the end of the form tag.
 *
 * @since  1.0.1.0
 */
do_action( 'ms_shortcode_register_form_end', $m2_reg_obj );
?>
<br><br>
<?php
if ( $login_link_exists ) {
        MS_Helper_Html::html_link( $login_link );
}