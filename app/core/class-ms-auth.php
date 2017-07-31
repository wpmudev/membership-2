<?php
/**
 * Membership Auth Class
 *
 * Handle the ajax login
 *
 * @since  1.0.3.7
 */
class MS_Auth {

    /**
     * Handle Ajax Login requests
     *
     */
    public static function check_ms_ajax() {

        if ( isset( $_REQUEST['ms_ajax'] ) ) {
            if ( 1 == $_REQUEST['ms_ajax'] ) {
                add_action( 'wp_ajax_ms_login', 'ms_ajax_login' );
                add_action( 'wp_ajax_nopriv_ms_login', 'ms_ajax_login' );

                function ms_ajax_login() {
                    $resp = array();
                    check_ajax_referer( 'ms-ajax-login' );

                    if ( empty( $_POST['username'] ) && ! empty( $_POST['log'] ) ) {
                        $_POST['username'] = $_POST['log'];
                    }
                    if ( empty( $_POST['password'] ) && ! empty( $_POST['pwd'] ) ) {
                        $_POST['password'] = $_POST['pwd'];
                    }
                    if ( empty( $_POST['remember'] ) && ! empty( $_POST['rememberme'] ) ) {
                        $_POST['remember'] = $_POST['rememberme'];
                    }

                    // Nonce is checked, get the POST data and sign user on
                    $info = array(
                        'user_login' 	=> $_POST['username'],
                        'user_password' => $_POST['password'],
                        'remember' 		=> (bool) isset( $_POST['remember'] ) ? $_POST['remember'] : false,
                    );

                    $user_signon = wp_signon( $info, false );

                    if ( is_wp_error( $user_signon ) ) {
                        $resp['error'] = __( 'Wrong username or password', 'membership2' );
                    } else {
                        $resp['loggedin'] = true;
                        $resp['success'] = __( 'Logging in...', 'membership2' );

                        /**
                        * Allows a custom redirection after login.
                        * Empty value will use the default redirect option of the login form.
                        */

                        // TODO: These filters are never called!
                        //       This code is too early to allow any other plugin to register a filter handler...
                        $enforce = false;
                        if ( isset( $_POST['redirect_to'] ) ) {
                            $resp['redirect'] = apply_filters(
                                'ms-ajax-login-redirect',
                                $_POST['redirect_to'],
                                $user_signon->ID
                            );
                        } else {
                            $resp['redirect'] = apply_filters(
                                'ms_url_after_login',
                                $_POST['redirect_to'],
                                $enforce
                            );
                        }

                        //checking domains
                        if ( is_plugin_active_for_network( 'domain-mapping/domain-mapping.php' ) ) {
                            $url1 = parse_url( home_url() );
                            $url2 = parse_url( $resp['redirect'] );
                            if (strpos($url2['host'], $url1['host']) === false) {
                                //add 'auth' param for set cookie when mapped domains
                                $resp['redirect'] = add_query_arg( array('auth' => wp_generate_auth_cookie( $user_signon->ID, time() + MINUTE_IN_SECONDS )), $resp['redirect']);
                            }
                        }
                    }

                    echo json_encode( $resp );
                    exit();
                }
            }
        }
    }
}
?>