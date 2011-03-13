function m_addnewping() {
	window.location = "?page=membershippings&action=edit&ping=";

	return false;
}

function m_deleteping() {
	if(confirm(membership.deleteping)) {
		return true;
	} else {
		return false;
	}
}

function m_pingReady() {

	jQuery('.addnewpingbutton').click(m_addnewping);

	jQuery('.delete a').click(m_deleteping);

}

jQuery(document).ready(m_pingReady);