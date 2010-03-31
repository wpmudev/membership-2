var m_levelcount = 1;

function m_removesublevel() {

	jQuery(this).parents('li.sortable-levels').remove();

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

					cloned = cloned.replace('%startingpoint%', movingtitle);
					cloned = cloned.replace('%templateid%', moving + '-' + m_levelcount);
					cloned = cloned.replace(/%level%/gi, moving + '-' + m_levelcount);

					m_levelcount++;
					//%level%

					jQuery(cloned).appendTo('#membership-levels-holder');

					jQuery('a.removelink').unbind('click').click(m_removesublevel);

					jQuery('#level-order').val(',' + jQuery('#membership-levels-holder').sortable('toArray').join(','));
					//alert(cloned);


					/*
					ruleplace = jQuery(this).attr('id');
					if(moving != '') {
						jQuery('#main-' + moving).appendTo('#' + ruleplace + '-holder');
						jQuery('#' + moving).hide();

						// put the name in the relevant holding input field
						jQuery('#in-' + ruleplace).val( jQuery('#in-' + ruleplace).val() + ',' + moving );

					}
					*/
				}
	});

	jQuery('#membership-levels-holder').sortable({
		opacity: 0.7,
		helper: 'clone',
		placeholder: 'placeholder-levels',
		update: function(event, ui) {
				jQuery('#level-order').val(',' + jQuery('#membership-levels-holder').sortable('toArray').join(','));
			}
	});

	jQuery('.addnewsubbutton').click(m_addnewsub);

	jQuery('.deactivate a').click(m_deactivatesub);
	jQuery('.delete a').click(m_deletesub);

}

jQuery(document).ready(m_subsReady);