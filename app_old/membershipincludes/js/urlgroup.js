function m_addnewgroup() {
	window.location = "?page=membershipurlgroups&action=edit&group=";

	return false;
}

function m_deletegroup() {
	if(confirm(membership.deletegroup)) {
		return true;
	} else {
		return false;
	}
}

function m_groupReady() {

	jQuery('.addnewgroupbutton').click(m_addnewgroup);

	jQuery('.delete a').click(m_deletegroup);

}

jQuery(document).ready(m_groupReady);