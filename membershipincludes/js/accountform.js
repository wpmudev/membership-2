function M_ToggleView() {
	jQuery('#account-form div.formleft form').slideToggle('slow').toggleClass('closed').toggleClass('open');
}

function M_AccountReady() {

	jQuery('#membershipaccounttoggle').click(M_ToggleView);

}


jQuery(document).ready(M_AccountReady);