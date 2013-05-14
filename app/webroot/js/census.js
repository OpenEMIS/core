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
	Census.init();
});

var Census = {
	yearId: '#SchoolYearId',
	init: function() {
		$('#edit-link').click(Census.navigate);
		$(Census.yearId).change(Census.changeYear);
		$('.controls .btn_cancel').click(Census.navigate);
	},
	
	navigate: function() {
		window.location.href = $('#edit-link').attr('href') + '/' + $(Census.yearId).val();
		return false;
	},
	
	changeYear: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $(Census.yearId).val();
	},
	
	loadGradeList: function(obj) {
		var programmeId = $(obj).val();
		var index = $(obj).attr('index');
		var maskId;
		var ajaxParams = {programmeId: programmeId};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				var html = '';
				for(var i in data) {
					html += '<option value="' + i + '">' + data[i] + '</option>';
				}
				$(obj).closest('.table_cell').siblings('.grade_list').find('select[index="' + index + '"]').html(html);
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: getRootURL() + $(obj).attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.multi'});	},
			success: ajaxSuccess
		});
	},
	
	addMultiGradeRow: function() {
		var index = $('.table_row').length;
		var tableBody = $('.multi .table_body').length;
		var maskId;
		var ajaxParams = {index: index, tableBody: tableBody};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				if(tableBody==1) {
					$('.multi .table_body').append(data);
				} else {
					$('.multi .table_head').after(data);
				}
				jsTable.fixTable();
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + $(this).attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.multi'}); },
			success: ajaxSuccess
		});
	},
	
	addMultiGrade: function(obj) {
		var parent = $(obj).closest('.table_row');
		var index = parent.find('.programme_list .table_cell_row').length;
		var maskId;
		var ajaxParams = {index: index, row: parent.attr('row')};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				parent.find('.programme_list .row.last').before(data.programmes);
				parent.find('.grade_list').append(data.grades);
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: getRootURL() + $(obj).attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.multi'});	},
			success: ajaxSuccess
		});
	}
}