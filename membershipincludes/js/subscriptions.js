var m_levelcount = 1;

function m_colorsublevels() {

	jQuery('div.sub-operation').each(
		function () {
			if(jQuery(this).find('.sublevelmode').val() == 'serial') {
				//alert('serial found');
			} else {
				//alert('notserial found');
			}
		}
	);

}

function m_removesublevel() {
	var level = jQuery(this).parents('li.sortable-levels').attr('id');

	jQuery(this).parents('li.sortable-levels').remove();


	jQuery('#level-order').val( jQuery('#level-order').val().replace(',' + level, ''));

	m_colorsublevels();

	return false;
}

function m_addnewsub() {
	window.location = "?page=membershipsubs&action=edit&sub_id=";

	return false;
}

function m_deactivatesub() {
	if(confirm(membership.deactivatesub)) {
		return true;
	} else {
		return false;
	}
}

function m_deletesub() {
	if(confirm(membership.deletesub)) {
		return true;
	} else {
		return false;
	}
}

function m_clickactiontoggle() {
	if(jQuery(this).parent().hasClass('open')) {
		jQuery(this).parent().removeClass('open').addClass('closed');
		jQuery(this).parents('.action').find('.action-body').removeClass('open').addClass('closed');
	} else {
		jQuery(this).parent().removeClass('closed').addClass('open');
		jQuery(this).parents('.action').find('.action-body').removeClass('closed').addClass('open');
	}
}

function m_addtosubscription() {

	moving = jQuery(this).parents('.level-draggable').attr('id');

	var movingtitle = jQuery('#' + moving + ' div.action-top').html();

	var cloned = jQuery('#template-holder').clone().html();

	// remove the action link
	movingtitle = movingtitle.replace('<a href="#available-actions" class="action-button hide-if-no-js"></a>', '');

	cloned = cloned.replace('%startingpoint%', movingtitle);
	cloned = cloned.replace('%templateid%', moving + '-' + m_levelcount);
	cloned = cloned.replace(/%level%/gi, moving + '-' + m_levelcount);

	jQuery(cloned).appendTo('#membership-levels-holder');

	jQuery('a.removelink').unbind('click').click(m_removesublevel);

	jQuery('#level-order').val( jQuery('#level-order').val() + ',' + moving + '-' + m_levelcount);

	m_levelcount++;

	m_colorsublevels();

	return false;

}

function m_subsReady() {


	jQuery('.level-draggable').draggable({
			opacity: 0.7,
			helper: 'clone',
			start: function(event, ui) {
					jQuery('input#beingdragged').val( jQuery(this).attr('id') );
				 },
			stop: function(event, ui) {
					jQuery('input#beingdragged').val( '' );
				}
				});


	jQuery('.droppable-levels').droppable({
			hoverClass: 'hoveringover',
			drop: function(event, ui) {
					var moving = jQuery('input#beingdragged').val();
					var movingtitle = jQuery('#' + moving + ' div.action-top').html();

					var cloned = jQuery('#template-holder').clone().html();

					// remove the action link
					movingtitle = movingtitle.replace('<a href="#available-actions" class="action-button hide-if-no-js"></a>', '');

					cloned = cloned.replace('%startingpoint%', movingtitle);
					cloned = cloned.replace('%templateid%', moving + '-' + m_levelcount);
					cloned = cloned.replace(/%level%/gi, moving + '-' + m_levelcount);

					jQuery(cloned).appendTo('#membership-levels-holder');

					jQuery('a.removelink').unbind('click').click(m_removesublevel);

					jQuery('#level-order').val( jQuery('#level-order').val() + ',' + moving + '-' + m_levelcount);

					m_levelcount++;

					m_colorsublevels();
				}
	});

	jQuery('#membership-levels-holder').sortable({
		opacity: 0.7,
		helper: 'clone',
		placeholder: 'placeholder-levels',
		update: function(event, ui) {
				jQuery('#level-order').val(',' + jQuery('#membership-levels-holder').sortable('toArray').join(','));

				m_colorsublevels();
			}
	});

	jQuery('.addnewsubbutton').click(m_addnewsub);

	jQuery('.deactivate a').click(m_deactivatesub);
	jQuery('.delete a').click(m_deletesub);

	jQuery('a.removelink').click(m_removesublevel);

	jQuery('.action .action-top .action-button').click(m_clickactiontoggle);

	jQuery('a.action-to-subscription').click(m_addtosubscription);

}

jQuery(document).ready(m_subsReady);