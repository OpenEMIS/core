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
	$('#staff .table_row input').keyup(function() {
		staff.computeTotal(this);
	});
});

var staff = {
	computeTotal: function(obj) {
		var row = $(obj).closest('.table_row');
		var male = row.find('#CensusStaffMale');
		var female = row.find('#CensusStaffFemale');
		if(male.val().isEmpty()) {
			male.val(0);
			obj.select();
		}
		if(female.val().isEmpty()) {
			female.val(0);
			obj.select();
		}
		
		row.find('.cell_total').html(male.val().toInt() + female.val().toInt());
		
		var total = 0;
		$('#staff .table_row').each(function() {
			total += $(this).find('.cell_total').html().toInt();
		});
		$('#staff .table_foot .cell_value').html(total);
	}
};