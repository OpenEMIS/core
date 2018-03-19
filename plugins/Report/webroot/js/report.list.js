$(document).ready(function() {
    ReportList.init();
});

var ids = [];
var ReportList = {
	init: function() { 
		var selector = '.progress .progress-bar';

		$(selector).progressbar({
			display_text: 'center',
			percent_format: function(percent) { 
				if (percent != 100) {
					return percent + '%'; 
				} 
				return 'Downloading...';
			},
			done: function(e) {
				var current = $(e).attr('data-transitiongoal');
				var status = $(e).attr('data-status');
				var rowId = $(e).closest('tr').attr('row-id');

				if (current < 100 || $(e).closest('tr').find('.modified').html() == '') {
					if ($.inArray(rowId, ids) == -1) {
						ids.push(rowId);
					}

					if (ids.length > 0) {
						ReportList.getProgress(ids);
					}
				} else {
					if (status == 0) {
						$(e).closest('.progress').fadeOut(1000, function() {
							$(e).closest('td').find('a.download').removeClass('none');
							$(e).closest('.progress').remove();
							ids.splice( $.inArray(rowId, ids), 1 );
						});
					} else {
						// lala
					}
				}
			}
		});
	},

	getProgress: function(ids) {
		var url = $('#ReportList').attr('url');

		$.ajax({
			type: 'GET',
			dataType: 'json',
			data: {'ids': ids},
			url: url,
			success: function(response, textStatus) {
				$.each(response, function(id, data) {
					var selector = '[row-id="' + id + '"]';

					if (data['percent'] != undefined) {
						var progressbar = $('[row-id="' + id + '"] [role="progressbar"]');
						progressbar.attr('data-transitiongoal', data['percent']);
						progressbar.attr('data-status', data['status']);

						if (data['status'] != -1 && data['percent'] == 100 && data['modified'] != null) {
							$(selector).find('.modified').html(data['modified']);
							$(selector).find('.expiryDate').html(data['expiry_date']);
							ReportList.init();
						} else if (data['status'] == -1) {
							progressbar.closest('.progress').fadeOut(1000, function() {
								$('[data-toggle="tooltip"]').removeClass('none').tooltip();
								progressbar.closest('.progress').remove();
							});
						}
					}
				});

				// delay 5s before send another ajax request again
				setTimeout(function() {
					ReportList.init();
				}, 5000);
			}
		});
	}
};
