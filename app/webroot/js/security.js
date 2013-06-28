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
		
		$('#_view, #_edit:not(:disabled), #_add:not(:disabled), #_delete:not(:disabled)').change(Security.toggleOperation);
		$('#roles .icon_plus').click(Security.addRole);
		$('fieldset[type] .icon_plus').click(function() { Security.addRoleArea(this); });
	},
	
	navigate: function(obj) {
		window.location.href = obj.href + '/' + $('#SecuritySecurityRoleId').val();
		return false;
	},
	
	switchRole: function(obj) {
		window.location.href = getRootURL() + $(obj).attr('href') + '/' + $(obj).val();
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
			$.ajax({
				type: 'GET',
				dataType: dataType,
				url: getRootURL() + $(obj).attr('url'),
				data: {searchString: searchString},
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
							var parent = '#search_user';
							$(parent).find('.table_body').empty();
							jsTable.tableScrollableAdd(parent, data);
						}
					};
					$.unmask({id: maskId, callback: callback});
				}
			});
		}
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
	
	addRole: function() {
		var size = $('.table_view li').length;
		var maskId;
		var url = getRootURL() + 'Security/rolesAdd';
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {order: size},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					$('.table_view').append(data);
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
	}
};
