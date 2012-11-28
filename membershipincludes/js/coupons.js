function m_deactivatesub() {
	if(confirm(membership.deactivatesub)) {
		return true;
	} else {
		return false;
	}
}

function m_deletecoupon() {
	if(confirm(membership.deletecoupon)) {
		return true;
	} else {
		return false;
	}
}

function m_couponsReady() {

	jQuery('.delete a').click(m_deletecoupon);

	jQuery.datepicker.setDefaults(jQuery.datepicker.regional[membership.setlanguage]);
 	jQuery('.pickdate').datetimepicker( {timeFormat: 'hh:mm tt', changeMonth: true, changeYear: true, minDate: 0, firstDay: membership.start_of_week} );

}

jQuery(document).ready(m_couponsReady);