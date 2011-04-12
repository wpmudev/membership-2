function M_CheckUnsubSubmit() {
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