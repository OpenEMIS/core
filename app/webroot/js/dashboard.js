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
	dashboard.init();
	$('input[type="radio"][name="visible"]').click(function(e){
		// console.info($(this).val());
		dashboard.updateactiveFile($(this).val());
	});
});

var dashboard = {
	init: function() {
		$('.icon_plus').click(dashboard.addRow);
	},
	
	selectFile: function(obj) {
		var parent = $(obj).closest('.file_input');
		parent.find('input[type="file"]').click();
	},
	
	clearFile: function(obj) {
		
	},
	
	updateFile: function(obj) {
		var parent = $(obj).closest('.file_input');
		parent.find('.file input[type="text"]').val($(obj).val());
	},

	updateactiveFile: function(id){
		var maskId;
		var controller = $('#controller').text();
		var url = getRootURL() + controller + '/dashboardUpdateVisible/';
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: url,
			data: {id: id},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '.content_wrapper', text: i18n.Attachments.textUpdatingAttachment });
			},
			success: function (data, textStatus) {
				var callback = function() {
					$('div[file-id="'+data['visibleRecord']+'"] input[type="radio"]').attr('checked', 'checked');
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	deleteFile: function(id) {
		var dlgId = 'deleteDlg';
		var btn = {
			value: i18n.General.textDelete,
			callback: function() {
				var maskId;
				var controller = $('#controller').text();
				var url = getRootURL() + controller + '/dashboardDelete/';
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: url,
					data: {id: id},
					beforeSend: function (jqXHR) {
						maskId = $.mask({parent: '.content_wrapper', text: i18n.Attachments.textDeletingAttachment});
					},
					success: function (data, textStatus) {
						var callback = function() {
							var closeEvent = function() {
								var successHandler = function() {
									$('[file-id=' + id + ']').fadeOut(600, function() {
										$(this).remove();
										dashboard.renderTable();
										if(data['visibleRecord'] !== undefined){
											$('div[file-id="'+data['visibleRecord']+'"] input[type="radio"]').attr('checked', 'checked');
										}
									});
								};
								jsAjax.result({data: data, callback: successHandler});
							};
							$.closeDialog({id: dlgId, onClose: closeEvent});
						};
						$.unmask({id: maskId, callback: callback});
					}
				});
			}
		};
		
		var dlgOpt = {
			id: dlgId,
			title: i18n.Attachments.titleDeleteAttachment,
			content: i18n.Attachments.contentDeleteAttachment,
			buttons: [btn]
		};
		
		$.dialog(dlgOpt);
	},
	
	addRow: function() {
		var size = $('.table_row').length;
		var maskId;
		var controller = $('#controller').text();
		var url = getRootURL() + controller + '/dashboardAdd';
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {size: size},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					$('.file_upload .table_body').append(data);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	deleteRow: function(obj) {
		$(obj).closest('.table_row').remove();
		dashboard.renderTable();
	},
	
	renderTable: function() {
		$('.table_row.even').removeClass('even');
		$('.table_row:odd').addClass('even');
	}
}
