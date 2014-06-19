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
	ComboBox.init();
});

var ComboBox = {
	classname: '.combo_box',
	init: function() {
		$(ComboBox.classname).each(function() {
			ComboBox.attachFocusEvent($(this));
		});
	},
	
	attachFocusEvent: function(obj) {
		var input = obj.prop('tagName') === 'INPUT' ? obj : obj.find('input');
		input.focusin(function() {
			var rel = obj.attr('rel');
			var parent = obj.parent();
			var combo = parent.find('.combo_box_wrapper');
			if(combo.length==0) {
				var list = $('#' + rel).clone();
				list.css('display', 'block').width(obj.outerWidth()-2);
				obj.after(list);
				obj.siblings('.combo_box_wrapper').find('li').click(function() {
					input.val($(this).html());
				});
			} else {
				combo.css('visibility', 'visible');
			}
		}).focusout(function() {
			obj.siblings('.combo_box_wrapper').css('visibility', 'hidden');
		});
	}
};