(function($) {
	var timeout = false;

	function test_url() {
		if (timeout) {
			clearTimeout(timeout);
		}

		timeout = setTimeout(function() {
			var container = $('#urltestresults'),
				url = $.trim($('#url2test').val()),
				rules = $('#groupurls').val().split("\n");

			if (url == '') {
				container.html('<div><i>' + membership.nothingtest + '</i></div>');
				return;
			}

			container.empty();
			$.each(rules, function(i, rule) {
				var line, result, reg;

				rule = $.trim(rule);
				if (rule == '') {
					return;
				}

				result = $('<span></span>');

				line = $('<div></div>');
				line.html(rule);
				line.append(result);

				reg = new RegExp(rule, 'i');
				if (reg.test(url)) {
					line.addClass('rule-valid');
					result.text(membership.validrule);
				} else {
					line.addClass('rule-invalid');
					result.text(membership.invalidrule);
				}

				container.append(line);
			});

			if (container.find('> div').length == 0) {
				container.html('<div><i>' + membership.emptyrules + '</i></div>');
				return;
			}
		}, 500);
	}

	$(document).ready(function() {
		$('.addnewgroupbutton').click(function() {
			window.location = "?page=membershipurlgroups&action=edit&group=";
			return false;
		});

		$('.delete a').click(function() {
			return confirm(membership.deletegroup);
		});

		$('#url2test, #groupurls').keyup(test_url);
	});
})(jQuery);