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
	InstitutionSiteStudents.init();
});

var InstitutionSiteStudents = {
	yearId: '#SchoolYearId',
	programmeId: '#InstitutionSiteProgrammeId',
	
	init: function() {
		$('.btn_save').click(InstitutionSiteStudents.saveStudentList);
		this.attachSortOrder();
	},
	
	navigate: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $(this.yearId).val() + '/' + $(this.programmeId).val();
	},
	
	attachSortOrder:function() {
		$('#students_search [orderby]').click(function(){
			var order = $(this).attr('orderby');
			var sort = $(this).hasClass('icon_sort_up') ? 'desc' : 'asc';
			$('#StudentOrderBy').val(order);
			$('#StudentOrder').val(sort);
			var action = $('#students_search form').attr('action')+'/page:'+$('#StudentPage').val();
			$('#students_search form').attr('action', action);
			$('#students_search form').submit();
		});
	}
}