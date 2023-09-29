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
	InstitutionRubricAnswer.init();
});

var InstitutionRubricAnswer = {
	init: function() {
		$.each($('.criteriaRow'), function(key, row) {
			var id = $(row).children('td').find('input[type=hidden].criteriaAnswer').val();
			$("#criteriaOption_" + id).click();
		});
	},
	changeBgColor: function(obj) {
		var parent = $(obj).parents();
		$(parent).children('.criteriaCell').each(function() {
			$(this).css('background-color', 'white');
		});

		var bgColor = $(obj).find('input[type=hidden]').val();
		return $(obj).css('background-color', bgColor);
	}
};
