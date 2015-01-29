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
	Area.init();
});

var Area = {
	init: function() {
		$('.areapicker').each(function() {
			var input = $(this).find('select:enabled:last');
			var value = input.val();
			if(parseInt(value) > 0) {	//handle Area Administrative, hide world
			} else {
				input = $(this).find('select:disabled:last');
			}
			Area.getList(input);
		});
	},
	reloadDiv: function(obj) {
		var parent = $(obj).closest('.areapicker');
		var value = $(obj).val();

		var maskId;
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + parent.attr('urlReload') + $(obj).val(),
			beforeSend: function (jqXHR) {
				maskId = $.mask({id: maskId, parent: parent});
			},
			success: function (data, textStatus) {
				var callback = function() {
					parent.html(data);
					var input = parent.find('select:enabled:last');
					Area.getList(input);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	getList: function(obj) {
		var wrapperClass = '.form-group';
		var controlClass = '.form-control';
		var parent = $(obj).closest('.areapicker');
		var value = $(obj).val();
		var level = $(obj).find('option:selected').attr('level');
		
		if(level != undefined) {
			$(obj).closest(wrapperClass).find('label').html(level);
		}

		if(parseInt(value) > 0) { //handle Area Administrative, hide world
			var child = $(obj).closest(wrapperClass).next(wrapperClass);
		} else {
			value = parent.attr('value');
			var child = $(obj).closest(wrapperClass);
		}

		if(child.length==1) {
			var next = child;
			do {
				next = next.next(wrapperClass);
				if(next.length==1) {
					next.find('select').attr('disabled', '');
				}
			} while(next.next(wrapperClass).length==1);

			var maskId;
			$.ajax({
				type: 'GET',
				dataType: 'text',
				url: getRootURL() + parent.attr('url') + value,
				beforeSend: function (jqXHR) {
					maskId = $.mask({id: maskId, parent: parent});
				},
				success: function (data, textStatus) {
					var callback = function() {
						var control = child.find(controlClass);
						control.html(data);
						
						if(data.length > 0) {
							control.removeAttr('disabled');
							value = control.find('option:first').val();
						} else {
							control.attr('disabled', '');
						}
					};
					$.unmask({id: maskId, callback: callback});
				}
			});
		}

		parent.find('input:hidden').val(value);
	}
};