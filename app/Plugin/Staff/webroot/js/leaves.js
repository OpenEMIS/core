
$(document).ready(function() {
	objStaffLeaves.init();
	$('#StaffLeaveDateFromDay').on('change', function(){objStaffLeaves.compute_work_days();});
	$('#StaffLeaveDateToDay').on('change', function(){objStaffLeaves.compute_work_days();});
});

var objStaffLeaves = {
	init: function() {
		objStaffLeaves.compute_work_days();
	},
	validateFileSize: function(obj) {
		//this.files[0].size gets the size of your file.
		var fileSize = obj.files[0].size;
		var fileAttr = $(obj).attr('index');
		if (fileSize / 1024 > 2050) {
			$('.file_index_' + fileAttr).parent().append('<div id="fileinput_message_' + fileAttr + '" class="error-message custom-file-msg">Invalid File Size</div>');
		} else {
			$("#fileinput_message_" + fileAttr).remove();

		}
	},
	compute_work_days: function() {
		var startDate = $('#StaffLeaveDateFromDay').val();
		var newStartDate = startDate.split("-").reverse().join("-");

		var endDate = $('#StaffLeaveDateToDay').val();
		var newEndDate = endDate.split("-").reverse().join("-");

		var dateFrom = new Date(newStartDate);
		var dateTo = new Date(newEndDate);
		var flag = true;
		var day, daycount = 0;

		if (dateFrom > dateTo) {
			flag = false;
		}
		while (flag)
		{
			day = dateFrom.getDay();
			if (day != 0 && day != 6) {
				daycount++;
			}
			dateFrom.setDate(dateFrom.getDate() + 1);
			if (dateFrom > dateTo)
			{
				flag = false;
			}
		}

		$('#StaffLeaveNumberOfDays').val(daycount);
	},
	errorFlag: function() {
		var errorMsg = $('.custom-file-msg').length;
		if (errorMsg == 0) {
			return true;
		} else {
			return false;
		}
	}


}