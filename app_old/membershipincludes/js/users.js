function m_loadusereditform() {
	tb_show(membership.useredittitle, jQuery(this).attr('href'));
	return false;
}

function m_usersready() {
	jQuery('a.membershipeditlink').click(m_loadusereditform);
}

jQuery(document).ready(m_usersready);