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
		$('#permissions input[type="hidden"]:disabled').removeAttr('disabled');
		$('#permissions .module_checkbox').change(Security.toggleModule);
		
		// $('input:not(:disabled)');
		$('[checkbox-toggle] input[type="checkbox"]:not(:disabled)').on('ifChecked', function(event) {
			console.log(event.type);
		});
		// $('#_edit:not(:disabled)').change(Security.toggleOperation);
		// $('#_add:not(:disabled)').change(Security.toggleOperation);
		// $('#_delete:not(:disabled)').change(Security.toggleOperation);
		// $('#_execute:not(:disabled)').change(Security.toggleOperation);
	},
	
	toggleModule: function() {
		var checked = $(this).is(':checked');console.log(checked);
		var parent = $(this).closest('.section_group');
		
		parent.find('tr input[type="checkbox"]').each(function() {
			if(!$(this).is(':disabled')) {
				$(this).prop('checked', checked);
			}
			Security.checkModuleToggled($(this));
		});
	},
	
	checkModuleToggled: function(obj) {
		var checked = false;
		var section = obj.closest('.section_group');
		section.find('tr input[type="checkbox"]').each(function() {
			if(!$(this).closest('tr').hasClass('none')) {
				if($(this).is(':checked')) checked = true;
			}
		});
		section.find('.module_checkbox').prop('checked', checked);
		// enable parent function to show top navigation
		
		$('tr.none').each(function() {
			var parentId = $(this).attr('parent-id');
			var functionId = $(this).attr('function-id');
			var isChecked = false;
			var selector = parentId!=-1 
						 ? ('tr[function-id="' + parentId + '"]')
						 : ('tr[parent-id="' + functionId + '"]');
			
			$(selector).each(function() {
				if($(this).find('#_view').is(':checked') && !isChecked) {
					isChecked = true;
					return false;
				}
			});
			$(this).find('input[type="checkbox"]:not(:disabled)').each(function() {
				$(this).prop('checked', isChecked);
			});
		});
	},
	
	toggleOperation: function() {
		var obj = $(this);
		var checked = obj.is(':checked');
		var parent = obj.closest('tr');
		var id = obj.attr('id');
		var operations = Security.operations.slice();
		var op, opObj, selector;
		
		if(!checked) operations.reverse();
		for(var i in operations) {
			op = operations[i];
			if(id !== op) {
				selector = '#'+op+':not(:disabled)';
				parent.find(selector).prop('checked', checked);
			} else {
				break;
			}
		}
		Security.checkModuleToggled(obj);
	}
};
