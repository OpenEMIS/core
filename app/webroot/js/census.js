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

var Census = {
	yearId: '#SchoolYearId',
	
	navigateYear: function(obj) {
		window.location.href = getRootURL() + $(obj).attr('url') + '/' + $(obj).val();
	},
	
	computeTotal: function(obj) {
		var row = $(obj).closest('tr');
		var subtotal = 0;
		row.find('.computeTotal').each(function() {
			if($(this).val().isEmpty()) {
				$(this).val(0).select();
			} else {
				subtotal += $(this).val().toInt();
			}
		});
		row.find('.cell-total').html(subtotal);
		table = row.closest('table');
		var total = 0;
		table.find('tbody tr').each(function() {
			total += $(this).find('.cell-total').html().toInt();
		});
		table.find('tfoot .cell-value').html(total);
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
				var gradeSelect  = $(obj).closest('.table_cell').siblings('.grade_list').find('select[index="' + index + '"]');
				gradeSelect.html(html);
				
				gradeSelect.parent().find('input.hiddenGradeId').val(gradeSelect.val());
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
		var index = ($('tr').length) * 2;
		var tableBody = $('.multi tbody').length;
		var maskId;
		var ajaxParams = {index: index, tableBody: tableBody};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				if(!$($.parseHTML(data)).hasClass('alert')) {
					if(tableBody==1) {
						$('.multi tbody').append(data);
					} else if(tableBody==0){
                                            $('.multi thead').after(data);
                                        }else {
						$('.multi tbody').after(data);
					}
					jsTable.fixTable();
				} else {
					var alertOpt = {
						id: 'multi_alert',
						parent: '.multi',
						type: $(data).attr('type'),
						text: $(data).html(),
						position: 'center'
					};
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
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.multi'}); },
			success: ajaxSuccess
		});
	},
	
	addMultiGrade: function(obj) {
		var parent = $(obj).closest('tr');
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
	},
	
	verify: function(obj, type) {
		var dlgId = 'verify_dialog';
		var url = $(obj).attr('href');
		if(type==='GET') {			
			var confirmBtn = {
				value: i18n.General.textConfirm, // change to validate
				callback: function() {
					Census.verify(obj, 'POST');
				}
			};
			var dlgOpt = {
				id: dlgId,
				title: i18n.General.textConfirmation,
				width: 400,
				ajaxUrl: url,
				buttons: [confirmBtn]
			};
			$.dialog(dlgOpt);
			return false;
		} else {
			var yearId = $('#SchoolYearId').val();
			var maskId;
			var ajaxSuccess = function(data, textStatus) {
				var callback = function() {
					$.closeDialog({id: dlgId});
					window.location.reload();
				};
				$.unmask({id: maskId, callback: callback});
			};
			$.ajax({
				type: 'POST',
				dataType: 'text',
				url: url,
				data: {school_year_id: yearId},
				beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
				success: ajaxSuccess
			});
		}
	}
}
