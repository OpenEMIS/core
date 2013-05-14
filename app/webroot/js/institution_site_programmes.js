/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

$(document).ready(function() {
	InstitutionSiteProgrammes.init();
});

var InstitutionSiteProgrammes = {
    yearId: '#SchoolYearId',
	programmeId: '#EducationProgrammeId',
	
	init: function() {
		$('#programmes .btn_save').click(InstitutionSiteProgrammes.saveStudentList);
		$('#programmes .icon_plus').click(InstitutionSiteProgrammes.addProgramme);
	},
	
	navigate: function() {
		var href = $('.content_wrapper > form').attr('action') + '/' + $(this.yearId).val();
		if($(this.programmeId).length==1) {
			href += '/' + $(this.programmeId).val();
		}
		window.location.href = href;
	},
	
	addProgramme: function() {
		var alertOpt = {
			id: 'programme_alert',
			parent: '.content_wrapper',
			type: alertType.error,
			position: 'center'
		}
		var maskId;
		var table = $('.table_body');
		var select = table.find('select');
		var url = getRootURL() + $('.icon_plus').attr('url');
		if(select.length>0) {
			if(select.val().isEmpty()) {
				alertOpt['text'] = i18n.InstitutionSites.textProgrammeSelect;
				$.alert(alertOpt);
			} else { // add selected programme
				var programmeId = select.val();
				var ajaxParams = {programmeId: programmeId};
				var ajaxSuccess = function(data, textStatus) {
					var callback = function() {
						if(data.type != ajaxType.success) {
							alertOpt['text'] = data.msg;
							$.alert(alertOpt);
						} else {
							var option = select.find('> option:selected');
							var row = $('.not_highlight');
							row.attr('row-id', programmeId);
							row.find('[attr="system"]').html(option.attr('system'));
							row.find('[attr="name"]').html(option.attr('name'));
							row.removeClass('not_highlight');
							jsTable.attachHoverOnClickEvent();
						}
					};
					$.unmask({id: maskId, callback: callback});
				};
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: url,
					data: ajaxParams,
					beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
					success: ajaxSuccess
				});
			}
		} else { // fetch programme list
			var ajaxSuccess = function(data, textStatus) {
				var callback = function() {
					if(!$(data).hasClass('alert')) {
						table.append(data);
						jsTable.fixTable(table.parent());
					} else {
						alertOpt['type'] = $(data).attr('type');
						alertOpt['text'] = $(data).html();
						$.alert(alertOpt);
					}
				};
				$.unmask({id: maskId, callback: callback});
			};
			$.ajax({
				type: 'GET',
				dataType: 'text',
				url: url,
				beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
				success: ajaxSuccess
			});
		}
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
			alertOpt['text'] = i18n.Search.textNoCriteria;
			$.alert(alertOpt);
		} else {
			InstitutionSiteProgrammes.searchMaster(obj, searchStr);
		}
	},
	
	searchMaster: function(obj, searchStr) {
		var alertOpt = {
			id: 'search_alert',
			parent: '#search_group',
			position: 'center'
		}
		var yearId = $(InstitutionSiteProgrammes.yearId).val();
		var programmeId = $(InstitutionSiteProgrammes.programmeId).val();
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
					jsTable.toggleTableScrollable('#search_group');
					jsTable.fixTable('#search_group .list_wrapper .table');
					$('.icon_plus[student-id]').click(InstitutionSiteProgrammes.addStudentToList);
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
	
	addStudentToList: function() {
		var obj = $(this);
		var studentId = obj.attr('student-id');
		var name = $(obj).closest('.table_row').find('.table_cell[name]').attr('name');
		var idNo = $(obj).closest('.table_row').find('.cell_id_no').html();
		var i = $('#student_group .table_row').length;
		
		$(obj).unbind('click');
		var maskId;
		var url = $('#search_group .table_scrollable').attr('url');
		var ajaxParams = {studentId: studentId, name: name, idNo: idNo, i: i};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				obj.closest('.table_row').fadeOut(300, function() {
					$(this).remove();
					$('#student_group .list_wrapper .table_body').prepend(data);
					jsTable.toggleTableScrollable('#search_group');
					jsTable.toggleTableScrollable('#student_group');
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
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#search_group', text: i18n.General.textAdding}); },
			success: ajaxSuccess
		});
	},
	
	removeStudentFromList: function(obj) {
		var row = $(obj).closest('.table_row');
		var id = row.length>0 ? row.attr('row-id') : -1;
		
		var maskId;
		var url = $('#student_group .table_scrollable').attr('url');
		var ajaxParams = {rowId: id};
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
				jsTable.toggleTableScrollable('#student_group');
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#student_group', text: i18n.General.textRemoving}); },
			success: ajaxSuccess
		});
	},
	
	saveStudentList: function() {
		var form = $('#student_group form');
		
		var maskId;
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				var alertOpt = {
					parent: '#student_group',
					text: i18n.General.textRecordUpdateSuccess,
					position: 'center'
				};
				$.alert(alertOpt);
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'POST',
			dataType: 'text',
			url: form.attr('action'),
			data: form.serialize(),
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#student_group', text: i18n.General.textSaving}); },
			success: ajaxSuccess
		});
	}
}