<?php

// Added some extra helpfull shortcodes to the membership system
if (!class_exists('M_extrashortcodes')) {

    class M_extrashortcodes {

        // PHP 5+ constructor
        function __construct() {

            add_action('membership_register_shortcodes', array(&$this, 'register_shortcodes'));
        }

        // PHP 4 constructor
        function M_shortcodes() {
            $this->__construct();
        }

        function register_shortcodes() {

            // Registration form building shortcodes
            add_shortcode('subscriptiontitle', array(&$this, 'do_subscriptiontitle_shortcode'));
            add_shortcode('subscriptiondetails', array(&$this, 'do_subscriptiondetails_shortcode'));
            add_shortcode('subscriptionprice', array(&$this, 'do_subscriptionprice_shortcode'));
            add_shortcode('subscriptionbutton', array(&$this, 'do_subscriptionbutton_shortcode'));

            add_shortcode('membershiplogin', array(&$this, 'do_membershiplogin_shortcode'));
        }

        // Based on an original plugin by Pippin - http://pippinsplugins.com/wordpress-login-form-short-code/
        function do_membershiplogin_shortcode($atts, $content = null, $code = "") {

            global $wp_query;

            $defaults = array(
                "holder" => apply_filters('membership_short_login_form_holder', ''),
                "holderclass" => apply_filters('membership_short_login_form_holderclass', ''),
                "item" => apply_filters('membership_short_login_form_item', ''),
                "itemclass" => apply_filters('membership_short_login_form_itemclass', ''),
                "postfix" => apply_filters('membership_short_login_form_postfix', ''),
                "prefix" => apply_filters('membership_short_login_form_prefix', ''),
                "wrapwith" => apply_filters('membership_short_login_form_wrapwith', ''),
                "wrapwithclass" => apply_filters('membership_short_login_form_wrapwithclass', ''),
                //"subscription" => '',
                "redirect" => apply_filters('membership_short_login_form_redirect', ''),
            );

            extract(shortcode_atts($defaults, $atts));

            /* if (empty($subscription)) {
              return '';
              } */

            $html = '';

            if (!empty($holder)) {
                $html .= "<{$holder} class='{$holderclass}'>";
            }

            if (!empty($item)) {
                $html .= "<{$item} class='{$itemclass}'>";
            }

            $html .= $prefix;

            // The title
            if (!empty($wrapwith)) {
                $html .= "<{$wrapwith} class='{$wrapwithclass}'>";
            }

            if (!is_user_logged_in()) {

                if ($redirect) {
                    $redirect_url = $redirect;
                } else {
                    $redirect_url = get_permalink();
                }
                $html .= wp_login_form(array('echo' => false, 'redirect' => $redirect_url));
            }

            if (!empty($wrapwith)) {
                $html .= "</{$wrapwith}>";
            }

            $html .= $postfix;

            if (!empty($item)) {
                $html .= "</{$item}>";
            }

            if (!empty($holder)) {
                $html .= "</{$holder}>";
            }

            return $html;
        }

        function do_subscriptiontitle_shortcode($atts, $content = null, $code = "") {

            global $wp_query;

            $defaults = array("holder" => '',
                "holderclass" => '',
                "item" => '',
                "itemclass" => '',
                "postfix" => '',
                "prefix" => '',
                "wrapwith" => '',
                "wrapwithclass" => '',
                "subscription" => ''
            );

            extract(shortcode_atts($defaults, $atts));

            if (empty($subscription)) {
                return '';
            }

            $html = '';

            if (!empty($holder)) {
                $html .= "<{$holder} class='{$holderclass}'>";
            }

            if (!empty($item)) {
                $html .= "<{$item} class='{$itemclass}'>";
            }

            $html .= $prefix;

            // The title
            if (!empty($wrapwith)) {
                $html .= "<{$wrapwith} class='{$wrapwithclass}'>";
            }

            $sub = new M_Subscription((int) $subscription);
            $html .= $sub->sub_name();

            if (!empty($wrapwith)) {
                $html .= "</{$wrapwith}>";
            }

            $html .= $postfix;
            if (!empty($item)) {
                $html .= "</{$item}>";
            }
            if (!empty($holder)) {
                $html .= "</{$holder}>";
            }
            return $html;
        }

        function do_subscriptiondetails_shortcode($atts, $content = null, $code = "") {

            global $wp_query;

            $defaults = array("holder" => '',
                "holderclass" => '',
                "item" => '',
                "itemclass" => '',
                "postfix" => '',
                "prefix" => '',
                "wrapwith" => '',
                "wrapwithclass" => '',
                "subscription" => ''
            );

            extract(shortcode_atts($defaults, $atts));

            if (empty($subscription)) {
                return '';
            }

            $html = '';

            if (!empty($holder)) {
                $html .= "<{$holder} class='{$holderclass}'>";
            }
            if (!empty($item)) {
                $html .= "<{$item} class='{$itemclass}'>";
            }
            $html .= $prefix;

            // The title
            if (!empty($wrapwith)) {
                $html .= "<{$wrapwith} class='{$wrapwithclass}'>";
            }

            $sub = new M_Subscription((int) $subscription);
            $html .= stripslashes($sub->sub_description());

            if (!empty($wrapwith)) {
                $html .= "</{$wrapwith}>";
            }

            $html .= $postfix;
            if (!empty($item)) {
                $html .= "</{$item}>";
            }
            if (!empty($holder)) {
                $html .= "</{$holder}>";
            }

            return $html;
        }

        function do_subscriptionprice_shortcode($atts, $content = null, $code = "") {

            global $wp_query;

            $defaults = array("holder" => '',
                "holderclass" => '',
                "item" => '',
                "itemclass" => '',
                "postfix" => '',
                "prefix" => '',
                "wrapwith" => '',
                "wrapwithclass" => '',
                "subscription" => ''
            );

            extract(shortcode_atts($defaults, $atts));

            if (empty($subscription)) {
                return '';
            }

            $html = '';

            if (!empty($holder)) {
                $html .= "<{$holder} class='{$holderclass}'>";
            }
            if (!empty($item)) {
                $html .= "<{$item} class='{$itemclass}'>";
            }
            $html .= $prefix;

            // The title
            if (!empty($wrapwith)) {
                $html .= "<{$wrapwith} class='{$wrapwithclass}'>";
            }

            $sub = new M_Subscription((int) $subscription);
            $first = $sub->get_level_at_position(1);

            if (!empty($first)) {
                $price = $first->level_price;
                if ($price == 0) {
                    $price = "Free";
                } else {

                    $M_options = get_option('membership_options', array());

                    switch ($M_options['paymentcurrency']) {
                        case "USD": $price = "$" . $price;
                            break;

                        case "GBP": $price = "&pound;" . $price;
                            break;

                        case "EUR": $price = "&euro;" . $price;
                            break;

                        default: $price = apply_filters('membership_currency_symbol_' . $M_options['paymentcurrency'], $M_options['paymentcurrency']) . $price;
                    }
                }
            }


            $html .= $price;

            if (!empty($wrapwith)) {
                $html .= "</{$wrapwith}>";
            }

            $html .= $postfix;
            if (!empty($item)) {
                $html .= "</{$item}>";
            }
            if (!empty($holder)) {
                $html .= "</{$holder}>";
            }

            return $html;
        }

        function do_subscriptionbutton_shortcode($atts, $content = null, $code = "") {

            global $wp_query, $M_options;

            $defaults = array("holder" => '',
                "holderclass" => '',
                "item" => '',
                "itemclass" => '',
                "postfix" => '',
                "prefix" => '',
                "wrapwith" => '',
                "wrapwithclass" => '',
                "subscription" => '',
                "color" => 'blue',
                'buttontext' => __('Subscribe', 'membership')
            );

            extract(shortcode_atts($defaults, $atts));

            if (isset($M_options['formtype']) && $M_options['formtype'] == 'new') {
                // pop up form
                $link = admin_url('admin-ajax.php');
                $link .= '?action=buynow&amp;subscription=' . (int) $subscription;
                $class = 'popover';
            } else {
                // original form
                $link = M_get_registration_permalink();
                $link .= '?action=registeruser&amp;subscription=' . (int) $subscription;
                $class = '';
            }

            if (empty($content)) {
                $content = $buttontext;
            }

            $html = "<a href='" . $link . "' class='popover button " . $color . "'>" . $content . "</a>";

            //$html = do_shortcode("[button class='popover' link='{$link}']Buy Now[/button]");


            return $html;
        }

    }

}

$M_extrashortcodes = new M_extrashortcodes();
?>