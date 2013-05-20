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
	security.init();
});

var security = {
	operations: ['_view', '_edit', '_add', '_delete'],
	init: function() {
		$('#permissions.edit input[type="hidden"]:disabled').removeAttr('disabled');
		$('#permissions.edit .module_checkbox').change(security.toggleModule);
		$('#SecurityView, #SecurityEdit, #SecurityAdd, #SecurityDelete').each(function() {
			
		});
		
		$('#_view, #_edit:not(:disabled), #_add:not(:disabled), #_delete:not(:disabled)').change(security.toggleOperation);
		$('#roles .icon_plus').click(security.addRole);
		$('fieldset[type] .icon_plus').click(function() { security.addRoleArea(this); });
	},
	
	navigate: function(obj) {
		window.location.href = obj.href + '/' + $('#SecuritySecurityRoleId').val();
		return false;
	},
	
	switchRole: function(obj) {
		window.location.href = getRootURL() + $(obj).attr('href') + '/' + $(obj).val();
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
	
	addRoleArea: function(obj) {
		var parent = $(obj).closest('fieldset');
		var type = parent.attr('type');
		var orderObj = $('fieldset[type="' + type + '"] .table_row:last #order');
		var order = orderObj.length>0 ? orderObj.val() : 0;
		var roleId = $('#roleId').text();
		var url = getRootURL() + 'Security/' + $('#url').text();
		var exclude = [];
		$(type==='areas' ? '.area_id' : '.institution_site_id').each(function() {
			exclude.push($(this).val());
		});
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {type: type, order: order, roleId: roleId, exclude: exclude},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: 'fieldset[type="' + type + '"]', text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					parent.find('.table_body').append(data);
					jsTable.init();
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	loadOptionList: function(obj, type) {
		var parentId = $(obj).val();
		var url = getRootURL() + 'Security/loadOptionList';
		var id = type==='areas' ? 'area_id' : 'institution_site_id';
		var exclude = [];
		$('.' + id).each(function() {
			exclude.push($(this).val());
		});
		
		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: url,
			data: {type: type, parentId: parentId, exclude: exclude},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: 'fieldset[type="' + type + '"]', text: i18n.General.textLoadingList});
			},
			success: function (data, textStatus) {
				var callback = function() {
					var list = security.buildOptionList(data);
					$(obj).parent().parent().find('#' + id).html(list);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	buildOptionList: function(data) {
		var html = '';
		for(var i in data) {
			html += '<option value="' + i + '">' + data[i] + '</option>';
		}
		return html;
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
			security.checkModuleToggled($(this));
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
		var operations = security.operations.slice();
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
		security.checkModuleToggled(obj);
	}
};