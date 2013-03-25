$(document).ready(function() {
	InstitutionSiteStudents.init();
});

var InstitutionSiteStudents = {
	yearId: '#SchoolYearId',
	programmeId: '#InstitutionSiteProgrammeId',
	
	init: function() {
		$('.btn_save').click(InstitutionSiteStudents.saveStudentList);
		this.attachSortOrder();
	},
	
	navigate: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $(this.yearId).val() + '/' + $(this.programmeId).val();
	},
	
	attachSortOrder:function() {
		$('#students_search [orderby]').click(function(){
			var order = $(this).attr('orderby');
			var sort = $(this).hasClass('icon_sort_up') ? 'desc' : 'asc';
			$('#StudentOrderBy').val(order);
			$('#StudentOrder').val(sort);
			var action = $('#students_search form').attr('action')+'/page:'+$('#StudentPage').val();
			$('#students_search form').attr('action', action);
			$('#students_search form').submit();
		});
	},
	
	clearSearch: function(obj) {
		$(obj).siblings('input').val('');
	},
	
	doSearch: function(e) {
		if(utility.getKeyPressed(e)==13 ) { // enter
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
		var ajaxParams = {searchStr: searchStr, yearId: yearId, programmeId: programmeId};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				var scrollable = '#search_group .table_scrollable';
				var list = scrollable + ' .list_wrapper';
				var selector = list + ' .table_body';
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
					InstitutionSiteStudents.toggleTableScrollable('#search_group');
					jsTable.fixTable('#search_group .list_wrapper .table');
					$('.icon_plus[student-id]').click(InstitutionSiteStudents.addStudentToList);
				}
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + $(obj).attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#search_group'}); },
			success: ajaxSuccess
		});
	},
	
	toggleTableScrollable: function(parent) {
		var hide = 'hidden';
		var active = 'scroll_active';
		var scrollable = parent + ' .table_scrollable';
		var list = scrollable + ' .list_wrapper';
		var selector = list + ' .table_body';
		var rows = $(selector).find('.table_row').length;
		
		if(rows > $(list).attr('limit')) {
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
		var name = $(obj).closest('.table_row').find('.table_cell[name]').attr('name');
		var idNo = $(obj).closest('.table_row').find('.cell_id_no').html();
		var i = $('#student_group .table_row').length;
		
		var maskId;
		var url = $('#search_group .table_scrollable').attr('url');
		var ajaxParams = {studentId: studentId, yearId: yearId, programmeId: programmeId, name: name, idNo: idNo, i: i};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				obj.closest('.table_row').fadeOut(300, function() {
					$(this).remove();
					$('#student_group .list_wrapper .table_body').prepend(data);
					InstitutionSiteStudents.toggleTableScrollable('#search_group');
					InstitutionSiteStudents.toggleTableScrollable('#student_group');
					jsTable.fixTable('.list_wrapper .table');
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
		var yearId = $(InstitutionSiteStudents.yearId).val();
		var programmeId = $(InstitutionSiteStudents.programmeId).val();
		var row = $(obj).closest('.table_row');
		var id = row.length>0 ? row.attr('student-id') : -1;
		
		var maskId;
		var url = $('#student_group .table_scrollable').attr('url');
		var ajaxParams = {studentId: id, yearId: yearId, programmeId: programmeId};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				if(id != -1) {
					row.fadeOut(300, function() {
						row.remove();
						jsTable.fixTable('#student_group .list_wrapper .table');
					});
				} else {
					$('#student_group .table_row').remove();
					
				}
				InstitutionSiteStudents.toggleTableScrollable('#student_group');
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
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				console.log(data); // notify on success
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'POST',
			dataType: 'text',
			url: form.attr('action'),
			data: form.serialize(),
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#student_group', text: 'Saving...'}); },
			success: ajaxSuccess
		});
	}
}