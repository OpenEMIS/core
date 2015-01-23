$(document).ready(function() {
    ReportList.init();
});

var ReportList = {
	init: function(id) {
		var selector = '.progress .progress-bar';

		if (id != undefined) {
			selector = '[row-id="' + id + '"] ' + selector;
		}
		$(selector).progressbar({
			display_text: 'center',
			done: function(e) {
				var current = $(e).attr('data-transitiongoal');
				var rowId = $(e).closest('tr').attr('row-id');

				if (current < 100 || $(e).closest('tr').find('.expiry').html() == '') {
					ReportList.getProgress(rowId);
				} else {
					$(e).closest('.progress').fadeOut(1000, function() {
						$(e).closest('td').find('a.none').removeClass('none');
						$(e).closest('.progress').remove();
					});
				}
			}
		});
	},

	getProgress: function(id) {
		var url = $('#ReportList').attr('url');
		var selector = '[row-id="' + id + '"]';
		$.ajax({
			type: 'GET',
			dataType: 'json',
			data: {'id': id},
			url: url,
			success: function(data, textStatus) {
				if (data['percent'] != undefined) {
					$('[row-id="' + id + '"] [role="progressbar"]').attr('data-transitiongoal', data['percent']);
					ReportList.init(id);
					if (data['expiry'] != null) {
						$(selector).find('.expiry').html(data['expiry']);
					}
				}
			}
		});
	}
};
