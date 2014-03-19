function m_addnewcomm() {
	window.location = "?page=membershipcommunication&action=edit&comm=";

	return false;
}

function m_deactivatecomm() {
	if(confirm(membership.deactivatecomm)) {
		return true;
	} else {
		return false;
	}
}

function m_deletecomm() {
	if(confirm(membership.deletecomm)) {
		return true;
	} else {
		return false;
	}
}

function m_commsReady() {
	jQuery('.addnewmessagebutton').click(m_addnewcomm);

	jQuery('.deactivate a').click(m_deactivatecomm);
	jQuery('.delete a').click(m_deletecomm);
}

jQuery(document).ready(m_commsReady);