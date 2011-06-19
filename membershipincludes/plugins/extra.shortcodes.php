<?php

class M_Extra_shortcodes {

	function __construct() {
		add_action('membership_register_shortcodes', array(&$this, 'register_shortcodes'));
	}

	function M_Extra_shortcodes() {
		$this->__construct();
	}

	function register_shortcodes() {

		add_shortcode('subscriptiontitle', array(&$this, 'do_subscriptiontitle_shortcode') );
		add_shortcode('subscriptiondetails', array(&$this, 'do_subscriptiondetails_shortcode') );
		add_shortcode('subscriptionprice', array(&$this, 'do_subscriptionprice_shortcode') );

	}

	function do_subscriptiontitle_shortcode($atts, $content = null, $code = "") {

		global $wp_query;

		$defaults = array(	"holder"				=>	'',
							"holderclass"			=>	'',
							"item"					=>	'',
							"itemclass"				=>	'',
							"postfix"				=>	'',
							"prefix"				=>	'',
							"wrapwith"				=>	'',
							"wrapwithclass"			=>	'',
							"subscription"			=>	''
						);

		extract(shortcode_atts($defaults, $atts));

		if(empty($subscription)) {
			return '';
		}

		if(!empty($holder)) {
			$html .= "<{$holder} class='{$holderclass}'>";
		}
		if(!empty($item)) {
			$html .= "<{$item} class='{$itemclass}'>";
		}
		$html .= $prefix;

		// The title
		if(!empty($wrapwith)) {
			$html .= "<{$wrapwith} class='{$wrapwithclass}'>";
		}

		$sub = new M_Subscription( (int) $subscription );
		$html .= $sub->sub_name();

		if(!empty($wrapwith)) {
			$html .= "</{$wrapwith}>";
		}

		$html .= $postfix;
		if(!empty($item)) {
			$html .= "</{$item}>";
		}
		if(!empty($holder)) {
			$html .= "</{$holder}>";
		}


		return $html;
	}

	function do_subscriptiondetails_shortcode($atts, $content = null, $code = "") {

		global $wp_query;

		$defaults = array(	"holder"				=>	'',
							"holderclass"			=>	'',
							"item"					=>	'',
							"itemclass"				=>	'',
							"postfix"				=>	'',
							"prefix"				=>	'',
							"wrapwith"				=>	'',
							"wrapwithclass"			=>	'',
							"subscription"			=>	''
						);

		extract(shortcode_atts($defaults, $atts));

		if(empty($subscription)) {
			return '';
		}

		if(!empty($holder)) {
			$html .= "<{$holder} class='{$holderclass}'>";
		}
		if(!empty($item)) {
			$html .= "<{$item} class='{$itemclass}'>";
		}
		$html .= $prefix;

		// The title
		if(!empty($wrapwith)) {
			$html .= "<{$wrapwith} class='{$wrapwithclass}'>";
		}

		$sub = new M_Subscription( (int) $subscription );
		$html .= stripslashes($sub->sub_description());

		if(!empty($wrapwith)) {
			$html .= "</{$wrapwith}>";
		}

		$html .= $postfix;
		if(!empty($item)) {
			$html .= "</{$item}>";
		}
		if(!empty($holder)) {
			$html .= "</{$holder}>";
		}

		return $html;
	}

	function do_subscriptionprice_shortcode($atts, $content = null, $code = "") {

		global $wp_query;

		$defaults = array(	"holder"				=>	'',
							"holderclass"			=>	'',
							"item"					=>	'',
							"itemclass"				=>	'',
							"postfix"				=>	'',
							"prefix"				=>	'',
							"wrapwith"				=>	'',
							"wrapwithclass"			=>	'',
							"subscription"			=>	''
						);

		extract(shortcode_atts($defaults, $atts));

		if(empty($subscription)) {
			return '';
		}

		if(!empty($holder)) {
			$html .= "<{$holder} class='{$holderclass}'>";
		}
		if(!empty($item)) {
			$html .= "<{$item} class='{$itemclass}'>";
		}
		$html .= $prefix;

		// The title
		if(!empty($wrapwith)) {
			$html .= "<{$wrapwith} class='{$wrapwithclass}'>";
		}

		$sub = new M_Subscription( (int) $subscription );
		$first = $sub->get_level_at_position(1);

		if(!empty($first)) {
			$price = $first->level_price;
			if($price == 0) {
				$price = "Free";
			} else {

				$M_options = get_option('membership_options', array());

				switch( $M_options['paymentcurrency'] ) {
					case "USD": $price = "$" . $price;
								break;

					case "GBP":	$price = "&pound;" . $price;
								break;

					case "EUR":	$price = "&euro;" . $price;
								break;

					default:	$price = apply_filters('membership_currency_symbol_' . $M_options['paymentcurrency'], $M_options['paymentcurrency']) . $price;
				}
			}
		}


		$html .= $price;

		if(!empty($wrapwith)) {
			$html .= "</{$wrapwith}>";
		}

		$html .= $postfix;
		if(!empty($item)) {
			$html .= "</{$item}>";
		}
		if(!empty($holder)) {
			$html .= "</{$holder}>";
		}

		return $html;
	}

}

$M_Extra_shortcodes = new M_Extra_shortcodes();

?>