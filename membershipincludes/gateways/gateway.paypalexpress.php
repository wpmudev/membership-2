<?php
/*
  Addon Name: PayPal Payments Standard Gateway
  Author: Incsub
  Author URI: http://premium.wpmudev.org
  Gateway ID: paypalexpress
 */

class paypalexpress extends Membership_Gateway {

    var $gateway = 'paypalexpress';
    var $title = 'PayPal Payments Standard';

	public function __construct() {
		parent::__construct();

		add_action( 'M_gateways_settings_' . $this->gateway, array( &$this, 'mysettings' ) );

		// If I want to override the transactions output - then I can use this action
		//add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

		if ( $this->is_active() ) {
			// Subscription form gateway
			add_action( 'membership_purchase_button', array( &$this, 'display_subscribe_button' ), 1, 3 );

			// Payment return
			add_action( 'membership_handle_payment_return_' . $this->gateway, array( &$this, 'handle_paypal_return' ) );
			add_filter( 'membership_subscription_form_subscription_process', array( &$this, 'signup_free_subscription' ), 10, 2 );
		}
	}

    function mysettings() {

        global $M_options;
        ?>
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row"><?php _e('PayPal Merchant Account ID', 'membership') ?></th>
                    <td>
						<input type="text" name="paypal_email" value="<?php esc_attr_e(get_option($this->gateway . "_paypal_email")); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('PayPal Site', 'membership') ?></th>
                    <td><select name="paypal_site">
        <?php
        $paypal_site = get_option($this->gateway . "_paypal_site");
        $sel_locale = empty($paypal_site) ? 'US' : $paypal_site;

        $locales = array(
            'AX' => __('ÃLAND ISLANDS', 'membership'),
            'AL' => __('ALBANIA', 'membership'),
            'DZ' => __('ALGERIA', 'membership'),
            'AS' => __('AMERICAN SAMOA', 'membership'),
            'AD' => __('ANDORRA', 'membership'),
            'AI' => __('ANGUILLA', 'membership'),
            'AQ' => __('ANTARCTICA', 'membership'),
            'AG' => __('ANTIGUA AND BARBUDA', 'membership'),
            'AR' => __('ARGENTINA', 'membership'),
            'AM' => __('ARMENIA', 'membership'),
            'AW' => __('ARUBA', 'membership'),
            'AU' => __('AUSTRALIA', 'membership'),
            'AT' => __('AUSTRIA', 'membership'),
            'AZ' => __('AZERBAIJAN', 'membership'),
            'BS' => __('BAHAMAS', 'membership'),
            'BH' => __('BAHRAIN', 'membership'),
            'BD' => __('BANGLADESH', 'membership'),
            'BB' => __('BARBADOS', 'membership'),
            'BE' => __('BELGIUM', 'membership'),
            'BZ' => __('BELIZE', 'membership'),
            'BJ' => __('BENIN', 'membership'),
            'BM' => __('BERMUDA', 'membership'),
            'BT' => __('BHUTAN', 'membership'),
            'BA' => __('BOSNIA-HERZEGOVINA', 'membership'),
            'BW' => __('BOTSWANA', 'membership'),
            'BV' => __('BOUVET ISLAND', 'membership'),
            'BR' => __('BRAZIL', 'membership'),
            'IO' => __('BRITISH INDIAN OCEAN TERRITORY', 'membership'),
            'BN' => __('BRUNEI DARUSSALAM', 'membership'),
            'BG' => __('BULGARIA', 'membership'),
            'BF' => __('BURKINA FASO', 'membership'),
            'CA' => __('CANADA', 'membership'),
            'CV' => __('CAPE VERDE', 'membership'),
            'KY' => __('CAYMAN ISLANDS', 'membership'),
            'CF' => __('CENTRAL AFRICAN REPUBLIC', 'membership'),
            'CL' => __('CHILE', 'membership'),
            'CN' => __('CHINA', 'membership'),
            'CX' => __('CHRISTMAS ISLAND', 'membership'),
            'CC' => __('COCOS (KEELING) ISLANDS', 'membership'),
            'CO' => __('COLOMBIA', 'membership'),
            'CK' => __('COOK ISLANDS', 'membership'),
            'CR' => __('COSTA RICA', 'membership'),
            'CY' => __('CYPRUS', 'membership'),
            'CZ' => __('CZECH REPUBLIC', 'membership'),
            'DK' => __('DENMARK', 'membership'),
            'DJ' => __('DJIBOUTI', 'membership'),
            'DM' => __('DOMINICA', 'membership'),
            'DO' => __('DOMINICAN REPUBLIC', 'membership'),
            'EC' => __('ECUADOR', 'membership'),
            'EG' => __('EGYPT', 'membership'),
            'SV' => __('EL SALVADOR', 'membership'),
            'EE' => __('ESTONIA', 'membership'),
            'FK' => __('FALKLAND ISLANDS (MALVINAS)', 'membership'),
            'FO' => __('FAROE ISLANDS', 'membership'),
            'FJ' => __('FIJI', 'membership'),
            'FI' => __('FINLAND', 'membership'),
            'FR' => __('FRANCE', 'membership'),
            'GF' => __('FRENCH GUIANA', 'membership'),
            'PF' => __('FRENCH POLYNESIA', 'membership'),
            'TF' => __('FRENCH SOUTHERN TERRITORIES', 'membership'),
            'GA' => __('GABON', 'membership'),
            'GM' => __('GAMBIA', 'membership'),
            'GE' => __('GEORGIA', 'membership'),
            'DE' => __('GERMANY', 'membership'),
            'GH' => __('GHANA', 'membership'),
            'GI' => __('GIBRALTAR', 'membership'),
            'GR' => __('GREECE', 'membership'),
            'GL' => __('GREENLAND', 'membership'),
            'GD' => __('GRENADA', 'membership'),
            'GP' => __('GUADELOUPE', 'membership'),
            'GU' => __('GUAM', 'membership'),
            'GG' => __('GUERNSEY', 'membership'),
            'GY' => __('GUYANA', 'membership'),
            'HM' => __('HEARD ISLAND AND MCDONALD ISLANDS', 'membership'),
            'VA' => __('HOLY SEE (VATICAN CITY STATE)', 'membership'),
            'HN' => __('HONDURAS', 'membership'),
            'HK' => __('HONG KONG', 'membership'),
            'HU' => __('HUNGARY', 'membership'),
            'IS' => __('ICELAND', 'membership'),
            'IN' => __('INDIA', 'membership'),
            'ID' => __('INDONESIA', 'membership'),
            'IE' => __('IRELAND', 'membership'),
            'IM' => __('ISLE OF MAN', 'membership'),
            'IL' => __('ISRAEL', 'membership'),
            'IT' => __('ITALY', 'membership'),
            'JM' => __('JAMAICA', 'membership'),
            'JP' => __('JAPAN', 'membership'),
            'JE' => __('JERSEY', 'membership'),
            'JO' => __('JORDAN', 'membership'),
            'KZ' => __('KAZAKHSTAN', 'membership'),
            'KI' => __('KIRIBATI', 'membership'),
            'KR' => __('KOREA, REPUBLIC OF', 'membership'),
            'KW' => __('KUWAIT', 'membership'),
            'KG' => __('KYRGYZSTAN', 'membership'),
            'LV' => __('LATVIA', 'membership'),
            'LS' => __('LESOTHO', 'membership'),
            'LI' => __('LIECHTENSTEIN', 'membership'),
            'LT' => __('LITHUANIA', 'membership'),
            'LU' => __('LUXEMBOURG', 'membership'),
            'MO' => __('MACAO', 'membership'),
            'MK' => __('MACEDONIA', 'membership'),
            'MG' => __('MADAGASCAR', 'membership'),
            'MW' => __('MALAWI', 'membership'),
            'MY' => __('MALAYSIA', 'membership'),
            'MT' => __('MALTA', 'membership'),
            'MH' => __('MARSHALL ISLANDS', 'membership'),
            'MQ' => __('MARTINIQUE', 'membership'),
            'MR' => __('MAURITANIA', 'membership'),
            'MU' => __('MAURITIUS', 'membership'),
            'YT' => __('MAYOTTE', 'membership'),
            'MX' => __('MEXICO', 'membership'),
            'FM' => __('MICRONESIA, FEDERATED STATES OF', 'membership'),
            'MD' => __('MOLDOVA, REPUBLIC OF', 'membership'),
            'MC' => __('MONACO', 'membership'),
            'MN' => __('MONGOLIA', 'membership'),
            'ME' => __('MONTENEGRO', 'membership'),
            'MS' => __('MONTSERRAT', 'membership'),
            'MA' => __('MOROCCO', 'membership'),
            'MZ' => __('MOZAMBIQUE', 'membership'),
            'NA' => __('NAMIBIA', 'membership'),
            'NR' => __('NAURU', 'membership'),
            'NP' => __('NEPAL', 'membership'),
            'NL' => __('NETHERLANDS', 'membership'),
            'AN' => __('NETHERLANDS ANTILLES', 'membership'),
            'NC' => __('NEW CALEDONIA', 'membership'),
            'NZ' => __('NEW ZEALAND', 'membership'),
            'NI' => __('NICARAGUA', 'membership'),
            'NE' => __('NIGER', 'membership'),
            'NU' => __('NIUE', 'membership'),
            'NF' => __('NORFOLK ISLAND', 'membership'),
            'MP' => __('NORTHERN MARIANA ISLANDS', 'membership'),
            'NO' => __('NORWAY', 'membership'),
            'OM' => __('OMAN', 'membership'),
            'PW' => __('PALAU', 'membership'),
            'PS' => __('PALESTINE', 'membership'),
            'PA' => __('PANAMA', 'membership'),
            'PY' => __('PARAGUAY', 'membership'),
            'PE' => __('PERU', 'membership'),
            'PH' => __('PHILIPPINES', 'membership'),
            'PN' => __('PITCAIRN', 'membership'),
            'PL' => __('POLAND', 'membership'),
            'PT' => __('PORTUGAL', 'membership'),
            'PR' => __('PUERTO RICO', 'membership'),
            'QA' => __('QATAR', 'membership'),
            'RE' => __('REUNION', 'membership'),
            'RO' => __('ROMANIA', 'membership'),
            'RU' => __('RUSSIAN FEDERATION', 'membership'),
            'RW' => __('RWANDA', 'membership'),
            'SH' => __('SAINT HELENA', 'membership'),
            'KN' => __('SAINT KITTS AND NEVIS', 'membership'),
            'LC' => __('SAINT LUCIA', 'membership'),
            'PM' => __('SAINT PIERRE AND MIQUELON', 'membership'),
            'VC' => __('SAINT VINCENT AND THE GRENADINES', 'membership'),
            'WS' => __('SAMOA', 'membership'),
            'SM' => __('SAN MARINO', 'membership'),
            'ST' => __('SAO TOME AND PRINCIPE', 'membership'),
            'SA' => __('SAUDI ARABIA', 'membership'),
            'SN' => __('SENEGAL', 'membership'),
            'RS' => __('SERBIA', 'membership'),
            'SC' => __('SEYCHELLES', 'membership'),
            'SG' => __('SINGAPORE', 'membership'),
            'SK' => __('SLOVAKIA', 'membership'),
            'SI' => __('SLOVENIA', 'membership'),
            'SB' => __('SOLOMON ISLANDS', 'membership'),
            'ZA' => __('SOUTH AFRICA', 'membership'),
            'GS' => __('SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS', 'membership'),
            'ES' => __('SPAIN', 'membership'),
            'SR' => __('SURINAME', 'membership'),
            'SJ' => __('SVALBARD AND JAN MAYEN', 'membership'),
            'SZ' => __('SWAZILAND', 'membership'),
            'SE' => __('SWEDEN', 'membership'),
            'CH' => __('SWITZERLAND', 'membership'),
            'TW' => __('TAIWAN, PROVINCE OF CHINA', 'membership'),
            'TZ' => __('TANZANIA, UNITED REPUBLIC OF', 'membership'),
            'TH' => __('THAILAND', 'membership'),
            'TL' => __('TIMOR-LESTE', 'membership'),
            'TG' => __('TOGO', 'membership'),
            'TK' => __('TOKELAU', 'membership'),
            'TO' => __('TONGA', 'membership'),
            'TT' => __('TRINIDAD AND TOBAGO', 'membership'),
            'TN' => __('TUNISIA', 'membership'),
            'TR' => __('TURKEY', 'membership'),
            'TM' => __('TURKMENISTAN', 'membership'),
            'TC' => __('TURKS AND CAICOS ISLANDS', 'membership'),
            'TV' => __('TUVALU', 'membership'),
            'UG' => __('UGANDA', 'membership'),
            'UA' => __('UKRAINE', 'membership'),
            'AE' => __('UNITED ARAB EMIRATES', 'membership'),
            'GB' => __('UNITED KINGDOM', 'membership'),
            'US' => __('UNITED STATES', 'membership'),
            'UM' => __('UNITED STATES MINOR OUTLYING ISLANDS', 'membership'),
            'UY' => __('URUGUAY', 'membership'),
            'UZ' => __('UZBEKISTAN', 'membership'),
            'VU' => __('VANUATU', 'membership'),
            'VE' => __('VENEZUELA', 'membership'),
            'VN' => __('VIET NAM', 'membership'),
            'VG' => __('VIRGIN ISLANDS, BRITISH', 'membership'),
            'VI' => __('VIRGIN ISLANDS, U.S.', 'membership'),
            'WF' => __('WALLIS AND FUTUNA', 'membership'),
            'EH' => __('WESTERN SAHARA', 'membership'),
            'ZM' => __('ZAMBIA', 'membership')
        );


        /* $locales = array(
          'AU'	=> __('Australia', 'membership'),
          'AT'	=> __('Austria', 'membership'),
          'BE'	=> __('Belgium', 'membership'),
          'CA'	=> __('Canada', 'membership'),
          'CN'	=> __('China', 'membership'),
          'FR'	=> __('France', 'membership'),
          'DE'	=> __('Germany', 'membership'),
          'HK'	=> __('Hong Kong', 'membership'),
          'IT'	=> __('Italy', 'membership'),
          'jp_JP' => __('Japan','membership'),
          'MX'	=> __('Mexico', 'membership'),
          'NL'	=> __('Netherlands', 'membership'),
          'NZ'	=> __('New Zealand', 'membership'),
          'PL'	=> __('Poland', 'membership'),
          'SG'	=> __('Singapore', 'membership'),
          'ES'	=> __('Spain', 'membership'),
          'SE'	=> __('Sweden', 'membership'),
          'CH'	=> __('Switzerland', 'membership'),
          'GB'	=> __('United Kingdom', 'membership'),
          'US'	=> __('United States', 'membership')
          ); */

        $locales = apply_filters('membership_gateway_locals', $locales, $this->gateway);

        foreach ($locales as $key => $value) {
            echo '<option value="' . esc_attr($key) . '"';
            if ($key == $sel_locale)
                echo 'selected="selected"';
            echo '>' . esc_html($value) . '</option>' . "\n";
        }
        ?>
                        </select>
                        <br />
                        <?php //_e('Format: 00.00 - Ex: 1.25', 'supporter')  ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Paypal Currency', 'membership') ?></th>
                    <td><?php
                        if (empty($M_options['paymentcurrency'])) {
                            $M_options['paymentcurrency'] = 'USD';
                        }
                        echo esc_html($M_options['paymentcurrency']);
                        ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('PayPal Mode', 'membership') ?></th>
                    <td><select name="paypal_status">
                            <option value="live" <?php if (get_option($this->gateway . "_paypal_status") == 'live') echo 'selected="selected"'; ?>><?php _e('Live Site', 'membership') ?></option>
                            <option value="test" <?php if (get_option($this->gateway . "_paypal_status") == 'test') echo 'selected="selected"'; ?>><?php _e('Test Mode (Sandbox)', 'membership') ?></option>
                        </select>
                        <br />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Subscription button', 'membership') ?></th>
                    <?php
                    $button = get_option($this->gateway . "_paypal_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif');
                    ?>
                    <td><input type="text" name="paypal_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
                        <br />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Upgrade button', 'membership') ?></th>
                    <?php
                    $button = get_option($this->gateway . "_paypal_upgrade_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif');
                    ?>
                    <td><input type="text" name="_paypal_upgrade_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
                        <br />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Cancel button', 'membership') ?></th>
                    <?php
                    $button = get_option($this->gateway . "_paypal_cancel_button", 'https://www.paypal.com/en_US/i/btn/btn_unsubscribe_LG.gif');
                    ?>
                    <td><input type="text" name="_paypal_cancel_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
                        <br />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    function build_custom($user_id, $sub_id, $amount, $fromsub_id = false) {

        global $M_options;

        $custom = '';

        //fake:user:sub:key

        $custom = time() . ':' . $user_id . ':' . $sub_id . ':';
        $key = md5('MEMBERSHIP' . apply_filters('membership_amount_' . $M_options['paymentcurrency'], $amount));

        $custom .= $key;

        if ($fromsub_id !== false) {
            $custom .= ":" . $fromsub_id;
        } else {
            $custom .= ":0";
        }

        return $custom;
    }

    function single_sub_button($pricing, $subscription, $user_id, $norepeat = false) {

        global $M_options;

        if (empty($M_options['paymentcurrency'])) {
            $M_options['paymentcurrency'] = 'USD';
        }

        $form = '';

        //if($pricing[0]['type'] == 'indefinite') $pricing[0]['days'] = 365;

        if (get_option($this->gateway . "_paypal_status") == 'live') {
            $form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
        } else {
            $form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
        }
        $form .= '<input type="hidden" name="charset" value="utf-8">';
        $form .= '<input type="hidden" name="business" value="' . esc_attr(get_option($this->gateway . "_paypal_email")) . '">';
        $form .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
        $form .= '<input type="hidden" name="item_name" value="' . $subscription->sub_name() . '">';
        $form .= '<input type="hidden" name="item_number" value="' . $subscription->sub_id() . '">';
        $form .= '<input type="hidden" name="currency_code" value="' . $M_options['paymentcurrency'] . '">';
        $form .= '<input type="hidden" name="a3" value="' . apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($pricing[0]['amount'], 2)) . '">';
        $form .= '<input type="hidden" name="p3" value="' . $pricing[0]['period'] . '">';
        $form .= '<input type="hidden" name="t3" value="' . strtoupper($pricing[0]['unit']) . '"> <!-- Set recurring payments until canceled. -->';

        $form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, number_format($pricing[0]['amount'], 2)) . '">';

        $form .= '<input type="hidden" name="return" value="' . apply_filters('membership_return_url_' . $this->gateway, M_get_returnurl_permalink()) . '">';
        $form .= '<input type="hidden" name="cancel_return" value="' . apply_filters('membership_cancel_url_' . $this->gateway, M_get_subscription_permalink()) . '">';

        $form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option($this->gateway . "_paypal_site")) . '">';
        $form .= '<input type="hidden" name="notify_url" value="' . apply_filters('membership_notify_url_' . $this->gateway, trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway)) . '">';

        if ($norepeat) {
            $form .= '<input type="hidden" name="src" value="0">';
        } else {
            $form .= '<input type="hidden" name="src" value="1">';
        }

        $button = get_option($this->gateway . "_paypal_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif');

        $form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
        $form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
        $form .= '</form>';

        return $form;
    }

    function complex_sub_button($pricing, $subscription, $user_id) {

        global $M_options;

        if (empty($M_options['paymentcurrency'])) {
            $M_options['paymentcurrency'] = 'USD';
        }

        $form = '';

        if (get_option($this->gateway . "_paypal_status") == 'live') {
            $form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
        } else {
            $form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
        }
        $form .= '<input type="hidden" name="charset" value="utf-8">';
        $form .= '<input type="hidden" name="business" value="' . esc_attr(get_option($this->gateway . "_paypal_email")) . '">';
        $form .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
        $form .= '<input type="hidden" name="item_name" value="' . $subscription->sub_name() . '">';
        $form .= '<input type="hidden" name="item_number" value="' . $subscription->sub_id() . '">';
        $form .= '<input type="hidden" name="currency_code" value="' . $M_options['paymentcurrency'] . '">';

        // complex bits here
        $count = 1;
        $ff = array();
        foreach ((array) $pricing as $key => $price) {

            switch ($price['type']) {

                case 'finite': if (empty($price['amount']))
                        $price['amount'] = '0';
                    if ($count < 3) {
                        $ff['a' . $count] = apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.', ''));
                        $ff['p' . $count] = $price['period'];
                        $ff['t' . $count] = strtoupper($price['unit']);
                    } else {
                        // Or last finite is going to be the end of the subscription payments
                        $ff['a3'] = apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.', ''));
                        $ff['p3'] = $price['period'];
                        $ff['t3'] = strtoupper($price['unit']);
                        $ff['src'] = '0';
                    }
                    $count++;
                    break;

                case 'indefinite':
                    if (empty($price['amount']))
                        $price['amount'] = '0';

                    if ($price['amount'] == '0') {
                        // The indefinite rule is free, we need to move any previous
                        // steps up to this one as we can't have a free a3
                        if (isset($ff['a2']) && $ff['a2'] != '0.00') {
                            // we have some other earlier rule so move it up
                            $ff['a3'] = $ff['a2'];
                            $ff['p3'] = $ff['p2'];
                            $ff['t3'] = $ff['t2'];
                            unset($ff['a2']);
                            unset($ff['p2']);
                            unset($ff['t2']);
                            $ff['src'] = '0';
                        } elseif (isset($ff['a1']) && $ff['a1'] != '0.00') {
                            $ff['a3'] = $ff['a1'];
                            $ff['p3'] = $ff['p1'];
                            $ff['t3'] = $ff['t1'];
                            unset($ff['a1']);
                            unset($ff['p1']);
                            unset($ff['t1']);
                            $ff['src'] = '0';
                        }
                    } else {
                        $ff['a3'] = apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.', ''));
                        $ff['p3'] = 1;
                        $ff['t3'] = 'Y';
                        $ff['src'] = '0';
                    }
                    break;
                case 'serial':
                    if (empty($price['amount']))
                        $price['amount'] = '0';

                    if ($price['amount'] == '0') {
                        // The serial rule is free, we need to move any previous
                        // steps up to this one as we can't have a free a3
                        if (isset($ff['a2']) && $ff['a2'] != '0.00') {
                            // we have some other earlier rule so move it up
                            $ff['a3'] = $ff['a2'];
                            $ff['p3'] = $ff['p2'];
                            $ff['t3'] = $ff['t2'];
                            unset($ff['a2']);
                            unset($ff['p2']);
                            unset($ff['t2']);
                            $ff['src'] = '1';
                        } elseif (isset($ff['a1']) && $ff['a1'] != '0.00') {
                            $ff['a3'] = $ff['a1'];
                            $ff['p3'] = $ff['p1'];
                            $ff['t3'] = $ff['t1'];
                            unset($ff['a1']);
                            unset($ff['p1']);
                            unset($ff['t1']);
                            $ff['src'] = '1';
                        }
                    } else {
                        $ff['a3'] = apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.', ''));
                        $ff['p3'] = $price['period'];
                        $ff['t3'] = strtoupper($price['unit']);
                        $ff['src'] = '1';
                    }

                    break;
            }
        }

        if (!empty($ff)) {
            foreach ($ff as $key => $value) {
                $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
            }
        }

        $form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, $ff['a3']) . '">';

        // Remainder of the easy bits

        $form .= '<input type="hidden" name="return" value="' . apply_filters('membership_return_url_' . $this->gateway, M_get_returnurl_permalink()) . '">';
        $form .= '<input type="hidden" name="cancel_return" value="' . apply_filters('membership_cancel_url_' . $this->gateway, M_get_subscription_permalink()) . '">';


        $form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option($this->gateway . "_paypal_site")) . '">';
        $form .= '<input type="hidden" name="notify_url" value="' . apply_filters('membership_notify_url_' . $this->gateway, trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway)) . '">';

        $button = get_option($this->gateway . "_paypal_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif');

        $form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
        $form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
        $form .= '</form>';

        return $form;
    }

    function build_subscribe_button($subscription, $pricing, $user_id) {

        if (!empty($pricing)) {

            // check to make sure there is a price in the subscription
            // we don't want to display free ones for a payment system
            $free = true;
            foreach ($pricing as $key => $price) {
                if (!empty($price['amount']) && $price['amount'] > 0) {
                    $free = false;
                }
            }

            if (!$free) {

                if (count($pricing) == 1) {
                    // A basic price or a single subscription
                    if (in_array($pricing[0]['type'], array('indefinite', 'finite'))) {
                        // one-off payment
                        return $this->single_sub_button($pricing, $subscription, $user_id, true);
                    } else {
                        // simple subscription
                        return $this->single_sub_button($pricing, $subscription, $user_id);
                    }
                } else {
                    // something much more complex

                    return $this->complex_sub_button($pricing, $subscription, $user_id);
                }
            } else {
                // Free subscription - so we'll use the free code
                return $this->single_free_button($pricing, $subscription, $user_id, true);
            }
        }
    }

    function single_upgrade_button($pricing, $subscription, $user_id, $norepeat = false, $fromsub_id = false) {

        global $M_options;

        if (empty($M_options['paymentcurrency'])) {
            $M_options['paymentcurrency'] = 'USD';
        }

        $form = '';

        //if($pricing[0]['type'] == 'indefinite') $pricing[0]['days'] = 365;

        if (get_option($this->gateway . "_paypal_status") == 'live') {
            $form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
        } else {
            $form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
        }
        $form .= '<input type="hidden" name="charset" value="utf-8">';
        $form .= '<input type="hidden" name="business" value="' . esc_attr(get_option($this->gateway . "_paypal_email")) . '">';
        $form .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
        $form .= '<input type="hidden" name="item_name" value="' . $subscription->sub_name() . '">';
        $form .= '<input type="hidden" name="item_number" value="' . $subscription->sub_id() . '">';
        $form .= '<input type="hidden" name="currency_code" value="' . $M_options['paymentcurrency'] . '">';
        $form .= '<input type="hidden" name="a3" value="' . apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($pricing[0]['amount'], 2, '.', '')) . '">';
        $form .= '<input type="hidden" name="p3" value="' . $pricing[0]['period'] . '">';
        $form .= '<input type="hidden" name="t3" value="' . strtoupper($pricing[0]['unit']) . '"> <!-- Set recurring payments until canceled. -->';

        $form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, number_format($pricing[0]['amount'], 2, '.', ''), $fromsub_id) . '">';

        $form .= '<input type="hidden" name="return" value="' . apply_filters('membership_return_url_' . $this->gateway, M_get_returnurl_permalink()) . '">';
        $form .= '<input type="hidden" name="cancel_return" value="' . apply_filters('membership_cancel_url_' . $this->gateway, M_get_subscription_permalink()) . '">';

        $form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option($this->gateway . "_paypal_site")) . '">';
        $form .= '<input type="hidden" name="notify_url" value="' . apply_filters('membership_notify_url_' . $this->gateway, trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway)) . '">';

        if ($norepeat) {
            $form .= '<input type="hidden" name="src" value="0">';
        } else {
            $form .= '<input type="hidden" name="src" value="1">';
        }

        $form .= '<input type="hidden" name="modify" value="2">';

        $button = get_option($this->gateway . "_paypal_upgrade_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif');

        $form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
        $form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
        $form .= '</form>';

        return $form;
    }

    function complex_upgrade_button($pricing, $subscription, $user_id, $fromsub_id = false) {

        global $M_options;

        if (empty($M_options['paymentcurrency'])) {
            $M_options['paymentcurrency'] = 'USD';
        }

        $form = '';

        if (get_option($this->gateway . "_paypal_status") == 'live') {
            $form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
        } else {
            $form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
        }
        $form .= '<input type="hidden" name="business" value="' . esc_attr(get_option($this->gateway . "_paypal_email")) . '">';
        $form .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
        $form .= '<input type="hidden" name="item_name" value="' . $subscription->sub_name() . '">';
        $form .= '<input type="hidden" name="item_number" value="' . $subscription->sub_id() . '">';
        $form .= '<input type="hidden" name="currency_code" value="' . $M_options['paymentcurrency'] . '">';

        // complex bits here
        $count = 1;
        $ff = array();
        foreach ((array) $pricing as $key => $price) {

            switch ($price['type']) {

                case 'finite': if (empty($price['amount']))
                        $price['amount'] = '0';
                    if ($count < 3) {
                        $ff['a' . $count] = apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.', ''));
                        $ff['p' . $count] = $price['period'];
                        $ff['t' . $count] = strtoupper($price['unit']);
                    } else {
                        // Or last finite is going to be the end of the subscription payments
                        $ff['a3'] = number_format($price['amount'], 2, '.', '');
                        $ff['p3'] = $price['period'];
                        $ff['t3'] = strtoupper($price['unit']);
                        $ff['src'] = '0';
                    }
                    $count++;
                    break;

                case 'indefinite':
                    if (empty($price['amount']))
                        $price['amount'] = '0';

                    if ($price['amount'] == '0') {
                        // The indefinite rule is free, we need to move any previous
                        // steps up to this one as we can't have a free a3
                        if (isset($ff['a2']) && $ff['a2'] != '0.00') {
                            // we have some other earlier rule so move it up
                            $ff['a3'] = $ff['a2'];
                            $ff['p3'] = $ff['p2'];
                            $ff['t3'] = $ff['t2'];
                            unset($ff['a2']);
                            unset($ff['p2']);
                            unset($ff['t2']);
                            $ff['src'] = '0';
                        } elseif (isset($ff['a1']) && $ff['a1'] != '0.00') {
                            $ff['a3'] = $ff['a1'];
                            $ff['p3'] = $ff['p1'];
                            $ff['t3'] = $ff['t1'];
                            unset($ff['a1']);
                            unset($ff['p1']);
                            unset($ff['t1']);
                            $ff['src'] = '0';
                        }
                    } else {
                        $ff['a3'] = apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.', ''));
                        $ff['p3'] = 1;
                        $ff['t3'] = 'Y';
                        $ff['src'] = '0';
                    }
                    break;
                case 'serial':
                    if (empty($price['amount']))
                        $price['amount'] = '0';

                    if ($price['amount'] == '0') {
                        // The serial rule is free, we need to move any previous
                        // steps up to this one as we can't have a free a3
                        if (isset($ff['a2']) && $ff['a2'] != '0.00') {
                            // we have some other earlier rule so move it up
                            $ff['a3'] = $ff['a2'];
                            $ff['p3'] = $ff['p2'];
                            $ff['t3'] = $ff['t2'];
                            unset($ff['a2']);
                            unset($ff['p2']);
                            unset($ff['t2']);
                            $ff['src'] = '1';
                        } elseif (isset($ff['a1']) && $ff['a1'] != '0.00') {
                            $ff['a3'] = $ff['a1'];
                            $ff['p3'] = $ff['p1'];
                            $ff['t3'] = $ff['t1'];
                            unset($ff['a1']);
                            unset($ff['p1']);
                            unset($ff['t1']);
                            $ff['src'] = '1';
                        }
                    } else {
                        $ff['a3'] = apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.', ''));
                        $ff['p3'] = $price['period'];
                        $ff['t3'] = strtoupper($price['unit']);
                        $ff['src'] = '1';
                    }

                    break;
            }
        }

        if (!empty($ff)) {
            foreach ($ff as $key => $value) {
                $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
            }
        }

        $form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, $ff['a3'], $fromsub_id) . '">';

        // Remainder of the easy bits

        $form .= '<input type="hidden" name="return" value="' . apply_filters('membership_return_url_' . $this->gateway, M_get_returnurl_permalink()) . '">';
        $form .= '<input type="hidden" name="cancel_return" value="' . apply_filters('membership_cancel_url_' . $this->gateway, M_get_subscription_permalink()) . '">';


        $form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option($this->gateway . "_paypal_site")) . '">';
        $form .= '<input type="hidden" name="notify_url" value="' . trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway) . '">';

        $form .= '<input type="hidden" name="modify" value="2">';

        $button = get_option($this->gateway . "_paypal_upgrade_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif');

        $form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
        $form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
        $form .= '</form>';

        return $form;
    }

    function build_upgrade_button($subscription, $pricing, $user_id, $fromsub_id = false) {

        if (!empty($pricing)) {

            // check to make sure there is a price in the subscription
            // we don't want to display free ones for a payment system
            $free = true;
            foreach ($pricing as $key => $price) {
                if (!empty($price['amount']) && $price['amount'] > 0) {
                    $free = false;
                }
            }

            if (!$free) {
                if (count($pricing) == 1) {
                    // A basic price or a single subscription
                    if (in_array($pricing[0]['type'], array('indefinite', 'finite'))) {
                        // one-off payment
                        return $this->single_upgrade_button($pricing, $subscription, $user_id, true, $fromsub_id);
                    } else {
                        // simple subscription
                        return $this->single_upgrade_button($pricing, $subscription, $user_id, false, $fromsub_id);
                    }
                } else {
                    // something much more complex
                    return $this->complex_upgrade_button($pricing, $subscription, $user_id, $fromsub_id);
                }
            }
        }
    }

    function display_subscribe_button($subscription, $pricing, $user_id) {
        echo $this->build_subscribe_button($subscription, $pricing, $user_id);
    }

    function display_upgrade_button($subscription, $pricing, $user_id, $fromsub_id = false) {
        echo $this->build_upgrade_button($subscription, $pricing, $user_id, $fromsub_id);
    }

    function display_cancel_button($subscription, $pricing, $user_id) {

        if ($pricing[0]['amount'] < 1) {
            // a free first level, so we can just cancel without having to go to paypal
            echo '<form class="unsubbutton" action="" method="post">';
            wp_nonce_field('cancel-sub_' . $subscription->sub_id());
            echo "<input type='hidden' name='action' value='unsubscribe' />";
            echo "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
            echo "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
            echo "<input type='hidden' name='user' value='" . $user_id . "' />";
            echo "<input type='submit' name='submit' value=' " . __('Unsubscribe', 'membership') . " ' class='button blue' />";
            echo "</form>";
        } else {
            $form = '';

            if (get_option($this->gateway . "_paypal_status") == 'live') {
                $form .= '<a class="unsubbutton" href="https://www.paypal.com/cgi-bin/webscr';
            } else {
                $form .= '<a class="unsubbutton" href="https://www.sandbox.paypal.com/cgi-bin/webscr';
            }

            $form .= '?cmd=_subscr-find&alias=' . urlencode(esc_attr(get_option($this->gateway . "_paypal_email"))) . '">';

            $button = get_option($this->gateway . "_paypal_cancel_button", 'https://www.paypal.com/en_US/i/btn/btn_unsubscribe_LG.gif');
            $form .= '<img border="0" src="' . esc_attr($button) . '">';
            $form .= '</a>';

            echo $form;
        }
    }

    function update() {

        if (isset($_POST['paypal_email'])) {
            update_option($this->gateway . "_paypal_email", $_POST['paypal_email']);
            update_option($this->gateway . "_paypal_site", $_POST['paypal_site']);
            update_option($this->gateway . "_currency", (isset($_POST['currency'])) ? $_POST['currency'] : 'USD' );
            update_option($this->gateway . "_paypal_status", $_POST['paypal_status']);
            update_option($this->gateway . "_paypal_button", $_POST['paypal_button']);
            update_option($this->gateway . "_paypal_upgrade_button", $_POST['_paypal_upgrade_button']);
            update_option($this->gateway . "_paypal_cancel_button", $_POST['_paypal_cancel_button']);
        }

        // default action is to return true
        return true;
    }

    function display_free_upgrade_button($subscription, $pricing, $user_id, $fromsub_id = false) {

        echo '<form class="upgradebutton" action="' . M_get_subscription_permalink() . '" method="post">';
        wp_nonce_field('upgrade-sub_' . $subscription->sub_id());
        echo "<input type='hidden' name='action' value='upgradesolo' />";
        echo "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
        echo "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
        echo "<input type='hidden' name='user' value='" . $user_id . "' />";
        echo "<input type='hidden' name='fromsub_id' value='" . $fromsub_id . "' />";
        echo "<input type='submit' name='submit' value=' " . __('Upgrade', 'membership') . " ' class='button blue' />";
        echo "</form>";
    }

    function display_upgrade_from_free_button($subscription, $pricing, $user_id, $fromsub_id = false) {

        if (!empty($pricing)) {

            $free = true;
            foreach ($pricing as $key => $price) {
                if (!empty($price['amount']) && $price['amount'] > 0) {
                    $free = false;
                }
            }

            if ($free) {

                $this->display_free_upgrade_button($subscription, $pricing, $user_id, $fromsub_id);
            } else {
                $this->display_upgrade_button($subscription, $pricing, $user_id, $fromsub_id);
            }
        }
    }

    // IPN stuff
    function handle_paypal_return() {
        // PayPal IPN handling code

        if ((isset($_POST['payment_status']) || isset($_POST['txn_type'])) && isset($_POST['custom'])) {

            if (get_option($this->gateway . "_paypal_status") == 'live') {
                $domain = 'https://www.paypal.com';
            } else {
                $domain = 'https://www.sandbox.paypal.com';
            }

            membership_debug_log(__('Received PayPal IPN from - ', 'membership') . $domain);

            $req = 'cmd=_notify-validate';
            if (!isset($_POST))
                $_POST = $HTTP_POST_VARS;
            foreach ($_POST as $k => $v) {
                if (get_magic_quotes_gpc())
                    $v = stripslashes($v);
                $req .= '&' . $k . '=' . $v;
            }

            $header = 'POST /cgi-bin/webscr HTTP/1.0' . "\r\n"
                    . 'Content-Type: application/x-www-form-urlencoded' . "\r\n"
                    . 'Content-Length: ' . strlen($req) . "\r\n"
                    . "\r\n";

            @set_time_limit(60);
            if ($conn = @fsockopen($domain, 80, $errno, $errstr, 30)) {
                fputs($conn, $header . $req);
                socket_set_timeout($conn, 30);

                $response = '';
                $close_connection = false;
                while (true) {
                    if (feof($conn) || $close_connection) {
                        fclose($conn);
                        break;
                    }

                    $st = @fgets($conn, 4096);
                    if ($st === false) {
                        $close_connection = true;
                        continue;
                    }

                    $response .= $st;
                }

                $error = '';
                $lines = explode("\n", str_replace("\r\n", "\n", $response));
                // looking for: HTTP/1.1 200 OK
                if (count($lines) == 0)
                    $error = 'Response Error: Header not found';
                else if (substr($lines[0], -7) != ' 200 OK')
                    $error = 'Response Error: Unexpected HTTP response';
                else {
                    // remove HTTP header
                    while (count($lines) > 0 && trim($lines[0]) != '')
                        array_shift($lines);

                    // first line will be empty, second line will have the result
                    if (count($lines) < 2)
                        $error = 'Response Error: No content found in transaction response';
                    else if (strtoupper(trim($lines[1])) != 'VERIFIED')
                        $error = 'Response Error: Unexpected transaction response';
                }

                if ($error != '') {
                    echo $error;
                    membership_debug_log($error);
                    exit;
                }
            }

            // process PayPal response
            switch (filter_input( INPUT_POST, 'payment_status' ) ) {
                case 'Completed':
                case 'Processed':
                    // case: successful payment
                    $amount = $_POST['mc_gross'];
                    $currency = $_POST['mc_currency'];
                    list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

                    $this->_record_transaction($user_id, $sub_id, $amount, $currency, current_time( 'timestamp' ), $_POST['txn_id'], $_POST['payment_status'], '');

                    membership_debug_log(__('Processed transaction received - ', 'membership') . print_r($_POST, true));
                    // Added for affiliate system link
                    do_action('membership_payment_processed', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
                    break;

                case 'Reversed':
                    // case: charge back
                    $note = __('Last transaction has been reversed. Reason: Payment has been reversed (charge back)', 'membership');
                    $amount = $_POST['mc_gross'];
                    $currency = $_POST['mc_currency'];
                    list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

                    $this->_record_transaction($user_id, $sub_id, $amount, $currency, current_time( 'timestamp' ), $_POST['txn_id'], $_POST['payment_status'], $note);

                    membership_debug_log(__('Reversed transaction received - ', 'membership') . print_r($_POST, true));

                    $member = new M_Membership($user_id);
                    if ($member) {
                        $member->expire_subscription($sub_id);
                        if (defined('MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION') && MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION == true) {
                            $member->deactivate();
                        }
                    }

                    do_action('membership_payment_reversed', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
                    break;

                case 'Refunded':
                    // case: refund
                    $note = __('Last transaction has been reversed. Reason: Payment has been refunded', 'membership');
                    $amount = $_POST['mc_gross'];
                    $currency = $_POST['mc_currency'];
                    list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

                    $this->_record_transaction($user_id, $sub_id, $amount, $currency, current_time( 'timestamp' ), $_POST['txn_id'], $_POST['payment_status'], $note);

                    membership_debug_log(__('Refunded transaction received - ', 'membership') . print_r($_POST, true));

                    $member = new M_Membership($user_id);
                    if ($member) {
                        $member->expire_subscription($sub_id);
                    }

                    do_action('membership_payment_refunded', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
                    break;

                case 'Denied':
                    // case: denied
                    $note = __('Last transaction has been reversed. Reason: Payment Denied', 'membership');
                    $amount = $_POST['mc_gross'];
                    $currency = $_POST['mc_currency'];
                    list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

                    $this->_record_transaction($user_id, $sub_id, $amount, $currency, current_time( 'timestamp' ), $_POST['txn_id'], $_POST['payment_status'], $note);

                    membership_debug_log(__('Denied transaction received - ', 'membership') . print_r($_POST, true));

                    $member = new M_Membership($user_id);
                    if ($member) {
                        $member->expire_subscription($sub_id);
                        if (defined('MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION') && MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION == true) {
                            $member->deactivate();
                        }
                    }

                    do_action('membership_payment_denied', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
                    break;

                case 'Pending':
                    // case: payment is pending
                    $pending_str = array(
                        'address' => __('Customer did not include a confirmed shipping address', 'membership'),
                        'authorization' => __('Funds not captured yet', 'membership'),
                        'echeck' => __('eCheck that has not cleared yet', 'membership'),
                        'intl' => __('Payment waiting for aproval by service provider', 'membership'),
                        'multi-currency' => __('Payment waiting for service provider to handle multi-currency process', 'membership'),
                        'unilateral' => __('Customer did not register or confirm his/her email yet', 'membership'),
                        'upgrade' => __('Waiting for service provider to upgrade the PayPal account', 'membership'),
                        'verify' => __('Waiting for service provider to verify his/her PayPal account', 'membership'),
                        '*' => ''
                    );
                    $reason = @$_POST['pending_reason'];
                    $note = __('Last transaction is pending. Reason: ', 'membership') . (isset($pending_str[$reason]) ? $pending_str[$reason] : $pending_str['*']);
                    $amount = $_POST['mc_gross'];
                    $currency = $_POST['mc_currency'];
                    list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

                    membership_debug_log(__('Pending transaction received - ', 'membership') . print_r($_POST, true));

                    $this->_record_transaction($user_id, $sub_id, $amount, $currency, current_time( 'timestamp' ), $_POST['txn_id'], $_POST['payment_status'], $note);

                    do_action('membership_payment_pending', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
                    break;
            }

            //check for subscription details
            switch ($_POST['txn_type']) {
                case 'subscr_signup':
					// start the subscription
					$amount = $_POST['mc_amount3'];
					list( $timestamp, $user_id, $sub_id, $key ) = explode( ':', $_POST['custom'] );

					$member = new M_Membership( $user_id );

					$newkey = md5( 'MEMBERSHIP' . $amount );
					if ( $key != $newkey ) {
						if ( defined( 'MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION' ) && MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION == true ) {
							$member->deactivate();
						}

						membership_debug_log( sprintf( __( 'Key does not match for amount - not creating subscription for user %d with key ', 'membership' ), $user_id ) . $newkey );
					} else {
						// create_subscription
						$member->create_subscription( $sub_id, $this->gateway );

						membership_debug_log( sprintf( __( 'Creating subscription %d for user %d', 'membership' ), $sub_id, $user_id ) );

						do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );
					}
					break;

				case 'subscr_modify':
                    // modify the subscription
					list( $timestamp, $user_id, $sub_id, $key ) = explode( ':', $_POST['custom'] );

					$member = new M_Membership( $user_id );

					$member->drop_subscription( $sub_id );
					$member->create_subscription( (int)$_POST['item_number'], $this->gateway );

					// Timestamp the update
					update_user_meta( $user_id, '_membership_last_upgraded', time() );

					membership_debug_log( sprintf( __( 'Moved from subscription - %d to subscription %d for user %d', 'membership' ), $sub_id, (int)$_POST['item_number'], $user_id ) );

					do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );
					break;

				case 'recurring_payment_profile_canceled':
                case 'subscr_cancel':
                    // mark for removal
                    list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

                    $member = new M_Membership($user_id);
					$member->mark_for_expire($sub_id);

					membership_debug_log(sprintf(__('Marked for expiration %d on %d', 'membership'), $user_id, $sub_id));

                    do_action('membership_payment_subscr_cancel', $user_id, $sub_id);
                    break;

				case 'recurring_payment_suspended':
					$member = new M_Membership( $user_id );
					$member->deactivate();

					membership_debug_log( sprintf( __( 'Recurring payment has been suspended - for %d', 'membership' ), $user_id ) );
					break;

				case 'recurring_payment_suspended_due_to_max_failed_payment':
				case 'recurring_payment_failed':
					$member = new M_Membership( $user_id );
					$member->deactivate();

					membership_debug_log( sprintf( __( 'Recurring payment failed - the number of attempts to collect payment has exceeded the value specified for "max failed payments" - for %d', 'membership' ), $user_id ) );
					break;

				case 'new_case':
                    // a dispute
                    if ($_POST['case_type'] == 'dispute') {
                        // immediately suspend the account
                        $member = new M_Membership($user_id);
						$member->deactivate();

						membership_debug_log(sprintf(__('Dispute for %d', 'membership'), $user_id));
                    }

                    do_action('membership_payment_new_case', $user_id, $sub_id, $_POST['case_type']);
                    break;
            }
        } else {
            // Did not find expected POST variables. Possible access attempt from a non PayPal site.
            header('Status: 404 Not Found');
            echo 'Error: Missing POST variables. Identification is not possible.';
            membership_debug_log('Error: Missing POST variables. Identification is not possible.');
            exit;
        }
    }

}

Membership_Gateway::register_gateway( 'paypalexpress', 'paypalexpress' );