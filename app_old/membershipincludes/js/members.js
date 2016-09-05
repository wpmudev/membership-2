function m_deactivatemember() {
	if(confirm(membership.deactivatemember)) {
		return true;
	} else {
		return false;
	}
}

function m_membersReady() {
	jQuery('.deactivate a').click(m_deactivatemember);
}

jQuery(document).ready(m_membersReady);