function M_CheckUnsubSubmit() {
	// return true until can work out why the confirm is returning undefined
	return true;

	if(confirm(membership.unsubscribe)) {
		return true;
	} else {
		return false;
	}
}

function M_RenewReady() {

	jQuery('.unsubbutton').click(M_CheckUnsubSubmit);

}


jQuery(document).ready(M_RenewReady);