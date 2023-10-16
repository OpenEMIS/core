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

// dependent on icheck plugin, please refer to scriptBottom.ctp for Checkable object
var Security = {
	operations: ['_view', '_edit', '_add', '_delete', '_execute'],
	init: function() {
		// to check the module checkbox if any of the function within the module is checked
		$('#permissions [checkbox-toggle-target]').each(function() {
			var obj = $(this);
			var target = obj.attr('checkbox-toggle-target');

			$('[checkbox-toggle="' + target + '"] input[type="checkbox"]').each(function() {
				if (!$(this).is(':disabled')) {
					if ($(this).is(':checked')) {
						obj.iCheck('check');
						return false;
					}
				}
			});
		});
		
		$('#permissions [checkbox-toggle-target]').on('ifToggled', Security.toggleModule);
		$('[checkbox-toggle] input[type="checkbox"]:not(:disabled)').on('ifToggled', Security.toggleOperation);
	},
	
	// this function allows the user to enable/disable permission for the entire module
	toggleModule: function() {
		var obj = $(this);
		var checked = obj.is(':checked') ? 'check' : 'uncheck';
		var target = obj.attr('checkbox-toggle-target');

		$('[checkbox-toggle="' + target + '"] input[type="checkbox"]').each(function() {
			if (!$(this).is(':disabled')) {
				$(this).off('ifToggled');
				$(this).iCheck(checked);
				$(this).on('ifToggled', Security.toggleOperation);
			}
		});
	},
	
	// this function will set the module checkbox to 'check' state if any permission is enabled
	checkModuleToggled: function(obj, checked) {
		var parent = obj.closest('[checkbox-toggle]');
		var module = $('[checkbox-toggle-target="' + parent.attr('checkbox-toggle') + '"]');

		module.off('ifToggled');
		if (checked == 'check') {
			module.iCheck('check');
		} else {
			var found = false;
			parent.find('input[type="checkbox"]').each(function() {
				if ($(this).is(':checked')) {
					found = true;
					return false; // break the loop
				}
			});
			if (!found) {
				module.iCheck('uncheck');
			}
		}
		module.on('ifToggled', Security.toggleModule);
	},
	
	// this function will enable/disable permissions based on priority
	toggleOperation: function() {
		var obj = $(this);
		var operations = Security.operations.slice();
		var op, selector;

		var checked = obj.is(':checked') ? 'check' : 'uncheck';
		var parent = obj.closest('tr');
		var id = obj.attr('id');

		if (id == '_execute') {
			if (checked == 'check') {
				parent.find('#_view:not(:disabled)').iCheck(checked);
			}
		} else {
			if (checked == 'uncheck') operations.reverse();
			for (var i in operations) {
				op = operations[i];

				if (id !== op) {
					selector = '#'+op+':not(:disabled)';
					parent.find(selector).iCheck(checked);
				} else {
					break;
				}
			}
		}
		Security.checkModuleToggled(obj, checked);
	}
};
