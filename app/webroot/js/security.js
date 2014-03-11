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
	Security.init();
});

var Security = {
	operations: ['_view', '_edit', '_add', '_delete', '_execute'],
	init: function() {
		$('#permissions.edit input[type="hidden"]:disabled').removeAttr('disabled');
		$('#permissions.edit .module_checkbox').change(Security.toggleModule);
		
		$('#_view, #_edit:not(:disabled), #_add:not(:disabled), #_delete:not(:disabled), #_execute:not(:disabled)').change(Security.toggleOperation);
		$('fieldset[type] .icon_plus').click(function() { Security.addRoleArea(this); });
                Security.autoCheckInstitutionSiteView();
	},
	
	navigate: function(obj) {
		window.location.href = obj.href + '/' + $('#SecuritySecurityRoleId').val();
		return false;
	},
	
	usersSearch: function(obj) {
		var searchString = '';
		var dataType = 'json';
		if($(obj).prop('tagName')==='SPAN') {
			dataType = 'text';
			searchString = $(obj).siblings('.search_wrapper').find('input').val();
		} else {
			searchString = $(obj).val();
		}
		
		var alertOpt = {
			id: 'search_alert',
			parent: '#group_admin',
			type: alertType.error,
			position: 'center'
		}
		
		if(!searchString.isEmpty()) {
			$(obj).closest('.table_row').find('#UserId').val(0);
			var params = {searchString: searchString};
			if($('#module').length==1) {
				params['module'] = $('#module').val();
			}
			var maskId;
			$.ajax({
				type: 'GET',
				dataType: dataType,
				url: getRootURL() + $(obj).attr('url'),
				data: params,
				beforeSend: function (jqXHR) {
					maskId = $.mask({parent: '.content_wrapper', text: i18n.Search.textSearching});
				},
				success: function (data, textStatus) {
					var callback = function() {
						if(dataType==='json') {
							if(data.type==='error') {
								alertOpt['text'] = i18n.Search.textNoResult;
								$.alert(alertOpt);
							} else {
								$(obj).closest('.table_cell').siblings('.name').html(data.name);
								$(obj).closest('.table_row').find('#UserId').val(data.id);
							}
						} else {
							if(!$(data).hasClass('alert')) {
								var parent = '#search';
								$(parent).find('.table_body').empty();
								jsTable.tableScrollableAdd(parent, data);
							} else {
								alertOpt['parent'] = '#search';
								alertOpt['type'] = $(data).attr('type');
								alertOpt['text'] = $(data).html();
								$.alert(alertOpt);
							}
						}
					};
					$.unmask({id: maskId, callback: callback});
				}
			});
		}
	},
	
	usersSearchOnPage: function(obj) {
		var searchString = $(obj).siblings('.search_wrapper').find('input').val().toLowerCase();
		if(!searchString.isEmpty()) {
			$('.list_wrapper .table_row').css('display', 'none');
			$('[tags*="' + searchString + '"]').css('display', 'table-row');
			jsTable.toggleTableScrollable('.section_break');
			jsTable.fixTable();
		}
	},
	
	cancelUsersSearchOnPage: function(obj) {
		$('#SearchField').val('');
		$('.list_wrapper .table_row').css('display', 'table-row');
		jsTable.toggleTableScrollable('.section_break');
		jsTable.fixTable();
	},
	
	addAccessUser: function(obj) {
		var table = $(obj).closest('.table');
		var tableId = $(obj).attr('user-id');
		$('#TableId').val(tableId);
		$('form').submit();
	},
	
	removeAccessUser: function(obj) {
		var maskId;
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				var row = $(obj).closest('.table_row');
				row.remove();
				jsTable.fixTable();
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'POST',
			dataType: 'text',
			url: getRootURL() + $(obj).attr('url'),
			data: {},
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textRemoving}); },
			success: ajaxSuccess
		});
	},
	
	addGroupAdmin: function(obj) {
		var index = $('#group_admin .table_row').length;
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + $(obj).attr('url'),
			data: {index: index},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '.content_wrapper', text: i18n.Search.textSearching});
			},
			success: function (data, textStatus) {
				var callback = function() {
					$('#group_admin .table_body').append(data);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	addGroupUser: function(obj) {
		var userId = $(obj).attr('user-id');
		var roleId = $(obj).closest('.table_cell').siblings('.cell_role').find('select').val();
		$('#SecurityUserId').val(userId);
		$('#SecurityRoleId').val(roleId);
		$('form').submit();
	},
	
	removeGroupUser: function(obj) {
		var row = $(obj).closest('.table_row');
		
		var maskId;
		var ajaxParams = {};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				var count = row.closest('.section_break').find('.user_count');
				var table = row.closest('.table');
				count.html(count.html().toInt()-1);
				row.remove();
				jsTable.fixTable(table);
				jsTable.toggleTableScrollable(table.closest('.section_break'));
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + $(obj).attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textRemoving}); },
			success: ajaxSuccess
		});
	},
	
	addGroupAccessOptions: function(obj) {
		var parent = $(obj).closest('.section_break');
		var index = parent.find('.table_row').length;
		var exclude = [];
		parent.find('.value_id').each(function() {
			exclude.push($(this).val());
		});
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + $(obj).attr('url'),
			data: {index: index, exclude: exclude},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: parent, text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					parent.find('.table_body').append(data);
					jsTable.init(parent);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	addGroupValueOptions: function(obj) {
		var parent = $(obj).closest('.section_break');
		var parentId = $(obj).val();
		var exclude = [];
		parent.find('.value_id').each(function() {
			exclude.push($(this).val());
		});
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + $(obj).attr('url'),
			data: {parentId: parentId, exclude: exclude},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: parent, text: i18n.General.textLoadingList});
			},
			success: function (data, textStatus) {
				var callback = function() {
					$(obj).closest('.table_row').find('.value_id').html(data);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	validateGroupAdd: function(obj) {
		var name = $('#SecurityGroupName').val();
		
		if($('#SecurityGroupName').attr('default')!=undefined) {
			if($('#SecurityGroupName').attr('default') === name.trim()) {
				return true;
			}
		}
		
		var alertOpt = {
			id: 'add_alert',
			parent: '#group_info',
			type: alertType.error,
			css: {left: '410px', top: '17px'}
		};
		
		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: getRootURL() + 'Security/groupsAddValidate',
			data: {name: name},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textValidating});
			},
			success: function (data, textStatus) {
				var callback = function() {
					if(data.type=='error') {
						alertOpt['text'] = data.msg;
						$.alert(alertOpt);
					} else {
						obj.submit();
					}
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
		return false;
	},
	
	addRole: function(obj) {
		var parent = $(obj).closest('.section_group');
		var size = $('.table_view li').length;
		var order = parent.find('.table_view li').length;
		var groupId = parent.find('#SecurityGroupId').val();
		var maskId;
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + 'Security/rolesAdd',
			data: {size: size, order: order, groupId: groupId},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					parent.find('.table_view').append(data);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	toggleModule: function() {
		var checked = $(this).is(':checked');
		var parent = $(this).closest('.section_group');
		parent.find('.table_row input[type="checkbox"]').each(function() {
			if(!$(this).is(':disabled')) {
				if(checked) {
					$(this).attr('checked', 'checked');
				} else {
					$(this).removeAttr('checked');
				}
			}
			Security.checkModuleToggled($(this));
		});
	},
	
	checkModuleToggled: function(obj) {
		var checked = false;
		var section = obj.closest('.section_group');
		section.find('.table_row input[type="checkbox"]').each(function() {
			if(!$(this).closest('.table_row').hasClass('none')) {
				if($(this).is(':checked')) checked = true;
			}
		});
		if(checked) {
			section.find('.module_checkbox').attr('checked', 'checked');
		} else {
			section.find('.module_checkbox').removeAttr('checked');
		}
		// enable parent function to show top navigation
		$('.table_row.none').each(function() {
			var parentId = $(this).attr('parent-id');
			var functionId = $(this).attr('function-id');
			var isChecked = false;
			var selector = parentId!=-1 
						 ? ('.table_row[function-id="' + parentId + '"]')
						 : ('.table_row[parent-id="' + functionId + '"]');
			
			$(selector).each(function() {
				if($(this).find('#_view').is(':checked') && !isChecked) {
					isChecked = true;
					return false;
				}
			});
			$(this).find('input[type="checkbox"]:not(:disabled)').each(function() {
				if(isChecked) {
					$(this).attr('checked', 'checked');
				} else {
					$(this).removeAttr('checked');
				}
			});
		});
	},
	
	toggleOperation: function() {
		var obj = $(this);
		var checked = obj.is(':checked');
		var parent = obj.closest('.table_row');
		var id = obj.attr('id');
		var operations = Security.operations.slice();
		var op, opObj, selector;
		
		if(!checked) operations.reverse();
		for(var i in operations) {
			op = operations[i];
			if(id !== op) {
				selector = '#'+op+':not(:disabled)';
				if(checked) {
					parent.find(selector).attr('checked', 'checked');
				} else {
					parent.find(selector).removeAttr('checked');
				}
			} else {
				break;
			}
		}
		Security.checkModuleToggled(obj);
	},
                
        autoCheckInstitutionSiteView: function(){
            var arrFinal = new Array();
            var arrFunctionIdSiteDetails = [13, 15, 16, 17, 19, 20, 21, 23, 24, 25, 102, 27, 28, 29, 103];
            var arrFunctionIdSiteTotals = [30, 31, 32, 33, 34, 99, 35, 36, 37, 38, 39, 40, 41, 42];
            var arrFunctionIdSiteQuality = [174, 175, 183];
            var arrFunctionIdSiteReports = [127, 128, 176];
            arrFinal = arrFinal.concat(arrFunctionIdSiteDetails);
            arrFinal = arrFinal.concat(arrFunctionIdSiteTotals);
            arrFinal = arrFinal.concat(arrFunctionIdSiteQuality);
            arrFinal = arrFinal.concat(arrFunctionIdSiteReports);
            
            for(var i in arrFinal){
                var currentFunctionId = arrFinal[i];
                $(".table_row[function-id='"+currentFunctionId+"'] .table_cell").find(":checkbox:not(:disabled)").click(function(){
                    var checkboxInstitutionView = $(".table_row[function-id='1'] .table_cell").find("#_view:checkbox");
                    var checkboxInstitutionSiteView = $(".table_row[function-id='8'] .table_cell").find("#_view:checkbox");
                    checkboxInstitutionView.attr('checked', 'checked');// check Institution View
                    checkboxInstitutionView.closest('.section_group').find('.module_checkbox').attr('checked', 'checked');//check Institution group checkbox
                    checkboxInstitutionSiteView.attr('checked', 'checked');// check Institution Site View
                    checkboxInstitutionSiteView.closest('.section_group').find('.module_checkbox').attr('checked', 'checked');//check Institution group checkbox
                });
            }
        }
};
