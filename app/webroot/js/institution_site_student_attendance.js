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
	InstitutionSiteStudentAttendance.init();
});

var InstitutionSiteStudentAttendance = {
	navigateYear: function(obj) {
		window.location.href = getRootURL() + $(obj).attr('url') + '/' + $(obj).val();
	},
	computeTotal: function(obj) {
		var row = $(obj).closest('.table_row');
		var subtotal = 0;
		row.find('.computeTotal').each(function() {
			if($(this).val().isEmpty()) {
				$(this).val(0).select();
			} else {
				subtotal += $(this).val().toInt();
			}
		});
		row.find('.cell_total').html(subtotal);
		table = row.closest('.table');
		var total = 0;
		table.find('.table_row').each(function() {
			total += $(this).find('.cell_total').html().toInt();
		});
		table.find('.table_foot .cell_value').html(total);
	}
}
