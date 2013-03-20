$(document).ready(function() {
	InstitutionSiteStudents.init();
});

var InstitutionSiteStudents = {
	yearId: '#SchoolYearId',
	programmeId: '#InstitutionSiteProgrammeId',
	listLimit: 4,
	
	init: function() {
		this.attachPaginateByFirstLetterEvent();
		$('.btn_save').click(InstitutionSiteStudents.saveStudentList);
	},
	
	navigate: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $(this.yearId).val() + '/' + $(this.programmeId).val();
	},
	
	attachPaginateByFirstLetterEvent: function() {
		$('#pagination li a').each(function() {
			$(this).unbind('click');
			$(this).click(function() {
				InstitutionSiteStudents.getStudentListByFirstLetter(this, '');
			});
		});
	},
	
	getStudentListByFirstLetter: function(obj, first) {
		first = obj!=null ? $(obj).html() : first;
		var yearId = $(InstitutionSiteStudents.yearId).val();
		var programmeId = $(InstitutionSiteStudents.programmeId).val();
		var page = $('#pagination');
		var edit = $('.content_wrapper').hasClass('edit');
		
		var maskId;
		var url = getRootURL() + page.attr('url');
		var ajaxParams = {first: first, yearId: yearId, programmeId: programmeId, edit: edit};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				$('#student_group .table_body').html(data);
				var pagination = $('#student_group .table_body #pagination');
				page.html(pagination.html());
				pagination.remove();
				InstitutionSiteStudents.attachPaginateByFirstLetterEvent();
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#student_group'}); },
			success: ajaxSuccess
		});
	},
	
	clearSearch: function(obj) {
		$(obj).siblings('input').val('');
	},
	
	doSearch: function(e) {
		if(utility.getKeyPressed(e)==13) { // enter
			$('.icon_search').click();
			e.preventDefault(); // prevent enter key to submit form
		}
	},
	
	search: function(obj) {
		$('#search_alert').remove();
		var input = $(obj).siblings('.search_wrapper').find('input');
		var searchStr = input.val();
		
		var alertOpt = {
			id: 'search_alert',
			parent: '#search_group',
			type: alertType.error,
			position: 'center'
		}
		
		if(searchStr.isEmpty()) {
			alertOpt['text'] = 'Please enter a search criteria.'; // translation
			$.alert(alertOpt);
		} else {
			InstitutionSiteStudents.searchMaster(obj, searchStr);
		}
	},
	
	searchMaster: function(obj, searchStr) {
		var alertOpt = {
			id: 'search_alert',
			parent: '#search_group',
			position: 'center'
		}
		var yearId = $(InstitutionSiteStudents.yearId).val();
		var programmeId = $(InstitutionSiteStudents.programmeId).val();
		var hide = 'hidden';
		var active = 'scroll_active';
		var maskId;
		var url = $(obj).attr('url');
		var ajaxParams = {searchStr: searchStr, yearId: yearId, programmeId: programmeId};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				var scrollable = '.table_scrollable';
				var list = scrollable + ' .list_wrapper';
				var selector = scrollable + list + ' .table_body';
				var alertSelector = selector + ' > .alert.none';
				$(selector).html(data);
				if($(alertSelector).length==1) {
					alertOpt['type'] = $(alertSelector).attr('type');
					alertOpt['text'] = $(alertSelector).html();
					$(alertSelector).remove();
					$(scrollable).removeClass(active);
					if(!$(list).hasClass(hide)) {
						$(list).addClass(hide);
					}
					$.alert(alertOpt);
				} else {
					InstitutionSiteStudents.toggleTableScrollable();
					jsTable.fixTable('.list_wrapper .table');
					$('.icon_plus[student-id]').click(InstitutionSiteStudents.addStudentToList);
				}
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#search_group'}); },
			success: ajaxSuccess
		});
	},
	
	toggleTableScrollable: function() {
		var hide = 'hidden';
		var active = 'scroll_active';
		var scrollable = '.table_scrollable';
		var list = scrollable + ' .list_wrapper';
		var selector = scrollable + list + ' .table_body';
		var rows = $(selector).find('.table_row').length;
		if(rows > InstitutionSiteStudents.listLimit) {
			if(!$(scrollable).hasClass(active)) {							
				$(scrollable).addClass(active);
			}
		} else {
			if($(scrollable).hasClass(active)) {							
				$(scrollable).removeClass(active);
			}
		}
		if($(list).hasClass(hide)) {
			$(list).removeClass(hide);
		}
	},
	
	addStudentToList: function() {
		var obj = $(this);
		var studentId = obj.attr('student-id');
		var yearId = $(InstitutionSiteStudents.yearId).val();
		var programmeId = $(InstitutionSiteStudents.programmeId).val();
		
		var maskId;
		var url = $('#search_group .table_scrollable').attr('url');
		var ajaxParams = {studentId: studentId, yearId: yearId, programmeId: programmeId};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				obj.closest('.table_row').fadeOut(300, function() {
					$(this).remove();
					InstitutionSiteStudents.toggleTableScrollable();
					jsTable.fixTable('.list_wrapper .table');
					var name = obj.parent().siblings('[name]').attr('name');
					InstitutionSiteStudents.getStudentListByFirstLetter(null, name);
				});
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#search_group', text: 'Adding Student...'}); },
			success: ajaxSuccess
		});
	},
	
	removeStudentFromList: function(obj) {
		var row = $(obj).closest('.table_row');
		var id = row.attr('student-id');
		
		var maskId;
		var url = $('#student_group .table_body').attr('url');
		var ajaxParams = {studentId: id};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				row.fadeOut(300, function() {
					row.remove();
					jsTable.fixTable('#student_group .table');
				});
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#student_group', text: 'Removing Student...'}); },
			success: ajaxSuccess
		});
	},
	
	saveStudentList: function() {
		var form = $('#student_group form');
		
		var maskId;
		var url = form.attr('action');
		var ajaxParams = form.serialize();
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				console.log(data);
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'POST',
			dataType: 'text',
			url: url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#student_group', text: 'Saving...'}); },
			success: ajaxSuccess
		});
	}
}