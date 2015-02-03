/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

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
	InstitutionSiteTeachers.init();
});

var InstitutionSiteTeachers = {
	periodId: '#AcademicPeriodId',
	
	init: function() {
		this.attachSortOrder();
		$('#teachersEdit .icon_plus').click(InstitutionSiteTeachers.addPosition);
	},
	
	navigate: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $(this.periodId).val();
	},
	
	attachSortOrder:function() {
		$('[orderby]').click(function() {
			var order = $(this).attr('orderby');
			var sort = $(this).hasClass('icon_sort_up') ? 'desc' : 'asc';
			$('.orderBy').val(order);
			$('.order').val(sort);
			var action = $('form').attr('action')+'/page:'+$('.page').val();
			$('form').attr('action', action);
			$('form').submit();
		});
	},
	
	search: function(obj, evt) {
		var alertOpt = {
			id: 'search_alert',
			parent: '#search',
			position: 'center'
		}
		
		var doSearch = false;
		if(evt != undefined) {
			if(utility.getKeyPressed(evt) == 13) { // enter key
				doSearch = true;
			}
		} else {
			doSearch = true;
		}
		
		if(doSearch) {
			var searchString = '';
			var url = $('.icon_search').attr('url');
			if(evt != undefined) {
				searchString = $(obj).val();
			} else {
				searchString = $(obj).siblings('.search_wrapper').find('input').val();
			}
			
			if(!searchString.isEmpty()) {
				var maskId;
				var ajaxParams = {searchString: searchString};
				var ajaxSuccess = function(data, textStatus) {
					var callback = function() {
						if(!$(data).hasClass('alert')) {
							var parent = '#search';
							$(parent).find('.table_body').empty();
							jsTable.tableScrollableAdd(parent, data);
						} else {
							alertOpt['type'] = $(data).attr('type');
							alertOpt['text'] = $(data).html();
							$.alert(alertOpt);
						}
					};
					$.unmask({id: maskId, callback: callback});
				};
				$('#TeacherId').val(0);
				$.ajax({
					type: 'GET',
					dataType: 'text',
					url: getRootURL() + url,
					data: {searchString: searchString},
					beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper', text: i18n.Search.textSearching}); },
					success: ajaxSuccess
				});
			}
		}
	},
	
	addTeacher: function(obj) {
		var row = $(obj);
		var id = row.attr('row-id');
		var idNo = row.attr('id-no');
		var fName = row.attr('first-name');
                var mName = row.attr('middle-name');
		var lName = row.attr('last-name');
                var pName = row.attr('preferred-name');
		var gender = row.attr('gender');
		
		$('#TeacherId').val(id);
		$('#IdentificationNo').html(idNo);
		$('#FirstName').html(fName);
                $('#MiddleName').html(mName);
		$('#LastName').html(lName);
                $('#PreferredName').html(pName);
		$('#Gender').html(gender);
	},
	
	validateTeacherAdd: function() {
		return $('#TeacherId').val()!=0;
	},
	
	addPosition: function() {
		var index = 0;
		var lastRow = $('#employment .table_body .table_row:last');
		if(lastRow.length > 0) {
			index = lastRow.attr('row-id');
		}
		var alertOpt = {
			id: 'teacher_alert',
			parent: '.content_wrapper',
			type: alertType.error,
			position: 'center'
		}
		var maskId;
		var ajaxParams = {index: index};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				if(!$(data).hasClass('alert')) {
					$('#employment .table_body').append(data);
					jsDate.initDatepicker();
					jsTable.fixTable('#employment .table');
				} else {
					alertOpt['parent'] = '#employment';
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
			url: getRootURL() + $(this).attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#employment'}); },
			success: ajaxSuccess
		});
	},
	
	deletePosition: function(obj) {
		var keys = $(obj).closest('.table_cell').siblings('.key').val();
		$('#employment').before($('<input>').attr({type: 'hidden', name: 'delete[]', value: keys}));
		jsTable.doRemove(obj);
	}
}
