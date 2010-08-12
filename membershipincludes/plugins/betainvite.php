<?php

add_filter('membership_subscriptionform_registration', 'show_beta_invite');

function show_beta_invite( $content ) {

	$content .= '<div class="alignleft">';
    $content .= '<label>' . __('Beta invite key','membership') . ' <span>*</span></label>';
    $content .= '<input type="text" autocomplete="off" class="regtext" name="betainvitekey">';
    $content .= '</div>';

	return $content;

}

add_filter( 'membership_subscriptionform_preregistration_process', 'check_beta_invite' );

function check_beta_invite( $error ) {

	$availablekeys = array('beta-0937-afa1' => 'barry+beta@mapinated.com',
	'beta-df2c-d710' => 'barry@mapinated.com',
	'beta-b6a9-bd10' => 'garri.rayner@gmail.com',
	'beta-d3b0-4942' => 'jamie@clearboxit.com',
	'beta-2d47-4366' => 'chadwbennett@gmail.com',
	'beta-0579-7c66' => 'sarah@untame.net',
	'beta-2abd-9867' => 'emailaddress',
	'beta-b9b4-c1c4' => 'emailaddress',
	'beta-a472-f29e' => 'emailaddress',
	'beta-07dd-adbc' => 'emailaddress',
	'beta-a9c7-1ad9' => 'emailaddress',
	'beta-531c-154c' => 'emailaddress',
	'beta-01cd-c979' => 'emailaddress',
	'beta-6fd5-cb08' => 'emailaddress',
	'beta-7579-9d11' => 'emailaddress',
	'beta-46d4-f4b9' => 'emailaddress',
	'beta-3c13-360a' => 'emailaddress',
	'beta-632a-8d58' => 'emailaddress',
	'beta-f078-6252' => 'emailaddress',
	'beta-d29f-7939' => 'emailaddress',
	'beta-7179-747e' => 'emailaddress',
	'beta-89f2-9fe6' => 'emailaddress',
	'beta-da15-07de' => 'emailaddress',
	'beta-53f9-a08e' => 'emailaddress',
	'beta-cc26-f517' => 'emailaddress',
	'beta-f406-b58d' => 'emailaddress',
	'beta-a146-d13b' => 'emailaddress',
	'beta-112c-773b' => 'emailaddress',
	'beta-8d6e-ec8d' => 'emailaddress',
	'beta-54a2-7d36' => 'emailaddress',
	'beta-3a58-e6b9' => 'emailaddress',
	'beta-edb4-911a' => 'emailaddress',
	'beta-3107-ffd6' => 'emailaddress',
	'beta-149a-4a11' => 'emailaddress',
	'beta-0fd5-67fc' => 'emailaddress',
	'beta-0a07-100a' => 'emailaddress',
	'beta-c46d-9478' => 'emailaddress',
	'beta-a0ac-1aa4' => 'emailaddress',
	'beta-cf4c-7fc6' => 'emailaddress',
	'beta-9df8-70e2' => 'emailaddress',
	'beta-a926-0d33' => 'emailaddress',
	'beta-b184-f2d3' => 'emailaddress',
	'beta-2c25-6e80' => 'emailaddress',
	'beta-f7cb-7d22' => 'emailaddress',
	'beta-e1ee-a70a' => 'emailaddress',
	'beta-057b-f9df' => 'emailaddress',
	'beta-360f-9418' => 'emailaddress',
	'beta-6cbe-49b9' => 'emailaddress',
	'beta-7ec5-d6ab' => 'emailaddress',
	'beta-53de-3c00' => 'emailaddress',
	'beta-8cfa-da18' => 'emailaddress',
	'beta-0375-25a8' => 'emailaddress',
	'beta-8ee2-e66b' => 'emailaddress',
	'beta-501b-cdea' => 'emailaddress',
	'beta-210e-9cb5' => 'emailaddress',
	'beta-0d8f-d1a1' => 'emailaddress',
	'beta-34ba-3b15' => 'emailaddress',
	'beta-1bc2-3d61' => 'emailaddress',
	'beta-b2e6-60e0' => 'emailaddress',
	'beta-3982-9079' => 'emailaddress',
	'beta-5fc9-ab4d' => 'emailaddress',
	'beta-d33c-a9d0' => 'emailaddress',
	'beta-9a6f-80c8' => 'emailaddress',
	'beta-8367-13f5' => 'emailaddress',
	'beta-55fd-deb3' => 'emailaddress',
	'beta-6dd4-cf05' => 'emailaddress',
	'beta-e1ea-d87d' => 'emailaddress',
	'beta-eb89-e6d3' => 'emailaddress',
	'beta-84b0-9b0a' => 'emailaddress',
	'beta-726c-645b' => 'emailaddress',
	'beta-b105-d557' => 'emailaddress',
	'beta-f2c3-9d41' => 'emailaddress',
	'beta-0cfc-277c' => 'emailaddress',
	'beta-3e6d-3ef8' => 'emailaddress',
	'beta-f4c0-460d' => 'emailaddress',
	'beta-588d-ef1e' => 'emailaddress',
	'beta-f954-a14d' => 'emailaddress',
	'beta-31d6-7988' => 'emailaddress',
	'beta-b42d-07b9' => 'emailaddress',
	'beta-f1e1-2ee5' => 'emailaddress',
	'beta-77af-18d2' => 'emailaddress',
	'beta-f35d-b487' => 'emailaddress',
	'beta-ab58-4a9f' => 'emailaddress',
	'beta-7dfd-1220' => 'emailaddress',
	'beta-43f4-7e27' => 'emailaddress',
	'beta-c037-7f25' => 'emailaddress',
	'beta-cc41-7a7c' => 'emailaddress',
	'beta-89f1-6f90' => 'emailaddress',
	'beta-59ec-12bb' => 'emailaddress',
	'beta-90fb-40d0' => 'emailaddress',
	'beta-ea7f-52ba' => 'emailaddress',
	'beta-2907-fc69' => 'emailaddress',
	'beta-50f0-e107' => 'emailaddress',
	'beta-4e22-1cd7' => 'emailaddress',
	'beta-a8c8-b742' => 'emailaddress',
	'beta-5cf0-7239' => 'emailaddress',
	'beta-dd4a-265d' => 'emailaddress',
	'beta-8e8e-d338' => 'emailaddress',
	'beta-1a54-3eb3' => 'emailaddress',
	'beta-1457-5ecc' => 'emailaddress',
	);

	$thekey = $_POST['betainvitekey'];

	if(empty($thekey)) {
		$error[] = __('You need to enter an invite key in order to register.','membership');
	} else {
		if(!array_key_exists( $thekey, $availablekeys )) {
			$error[] = __('Sorry, but we do not seem to have that key on file, please try another.','membership');
		} else {
			if( $availablekeys[$thekey] != $_POST['user_email'] ) {
				$error[] = __('Sorry, but that key does not seem to be linked to your email address, please try another.','membership');
			}
		}
	}

	return $error;

}

add_filter( 'membership_subscriptionform_postsubscriptions', 'override_membership_page_two', 10, 2 );

function override_membership_page_two( $content, $user_id ) {

	$content = '';

	$content .= '<div id="reg-form">'; // because we can't have an enclosing form for this part
	$content .= '<div class="formleft">';

	$content .= "<h2>" . __('Thank you for electing to be a beta tester','membership') . "</h2>";
	$content .= '<p>';
	$content .= __('If you now <a href="http://staypress.com/wp-login.php?redirect_to=http://staypress.com/download/">login and then pop on over to the download page</a>, then you will get access to the files.','membership');
	$content .= '</p>';
	$content .= '<p>';
	$content .= __('If you have any problems download the beta files, then please let us know - we are still testing this invite system.','membership');
	$content .= '</p>';

	$content .= '</div>';
	$content .= "</div>";

	if($user_id && function_exists('wp_set_auth_cookie')) {
		wp_set_auth_cookie($user_id);
	}


	return $content;

}


?>