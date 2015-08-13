$(document).ready(function() {
    ReportList.init();
});

var ReportList = {
	init: function(id, data) {
		var selector = '.progress .progress-bar';

		if (id != undefined) {
			selector = '[row-id="' + id + '"] ' + selector;
		}
		$(selector).progressbar({
			display_text: 'center',
			done: function(e) {
				var current = $(e).attr('data-transitiongoal');
				var rowId = $(e).closest('tr').attr('row-id');

				if (current < 100 || $(e).closest('tr').find('.modified').html() == '') {
					if (data == undefined || (data != undefined && data['status'] != -1)) {
						ReportList.getProgress(rowId);
					}
				} else {
					$(e).closest('.progress').fadeOut(1000, function() {
						$(e).closest('td').find('a.download').removeClass('none');
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
				console.log(data);
				if (data['percent'] != undefined) {
					var progressbar = $('[row-id="' + id + '"] [role="progressbar"]');
					progressbar.attr('data-transitiongoal', data['percent']);
					ReportList.init(id, data);

					if (data['status'] != -1 && data['percent'] == 100 && data['modified'] != null) {
						$(selector).find('.modified').html(data['modified']);
					} else if (data['status'] == -1) {
						progressbar.closest('.progress').fadeOut(1000, function() {
							$('[data-toggle="tooltip"]').removeClass('none').tooltip();
							progressbar.closest('.progress').remove();
						});
					}
				}
			}
		});
	}
};
