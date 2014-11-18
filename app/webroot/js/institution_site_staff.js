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
	InstitutionSiteStaff.init();
});

var InstitutionSiteStaff = {
	init: function() {
		this.attachSortOrder();
	},
	attachSortOrder: function() {
		$('#staffs_search [orderby]').click(function() {
			var order = $(this).attr('orderby');
			var sort = $(this).hasClass('icon_sort_up') ? 'desc' : 'asc';
			$('#InstitutionSiteStaffOrderBy').val(order);
			$('#InstitutionSiteStaffOrder').val(sort);
			var action = $('#staffs_search form').attr('action') + '/page:' + $('#InstitutionSiteStaffPage').val();
			$('#staffs_search form').attr('action', action);
			$('#staffs_search form').submit();
		});
	}
}