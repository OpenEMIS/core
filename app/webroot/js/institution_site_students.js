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
	
	attachSortOrder: function() {
		$('#students_search [orderby]').click(function(){
			var order = $(this).attr('orderby');
			var sort = $(this).hasClass('icon_sort_up') ? 'desc' : 'asc';
			$('#InstitutionSiteStudentOrderBy').val(order);
			$('#InstitutionSiteStudentOrder').val(sort);
			var action = $('#students_search form').attr('action')+'/page:'+$('#InstitutionSiteStudentPage').val();
			$('#students_search form').attr('action', action);
			$('#students_search form').submit();
		});
	},
	
	getProgrammeOptions: function(obj) {
		
		var $this = $(obj);
		var maskId;
		var yearURL = getRootURL() + $this.attr('yearUrl');
		var ajaxParams = {yearId: $this.val()};
		var ajaxSuccess = function(data, textStatus) {
			$('#InstitutionSiteProgrammeId').html(data);
			$.ajax({
				type: 'GET',
				dataType: 'json',
				url: yearURL,
				data: ajaxParams,
				success: ajaxYearSuccess
			});
		//	$.unmask({id: maskId, callback: callback});
		};
		
		var ajaxYearSuccess = function(data, textStatus) {
			var callback = function() {
				if(data['dateData'] != ''){
					$('#startDate').datepicker("remove");
					$('#startDate').datepicker({startDate:data['dateData']['startDate'],endDate:data['dateData']['endDate']}).data('datepicker');
					$('#startDate').datepicker('update', data['dateData']['startDate']);
				}
			};
			$.unmask({id: maskId, callback: callback});
		};
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + $this.attr('url'),
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '#AddStudentForm'}); },
			success: ajaxSuccess
		});
		
		
	},
	
	search: function(obj, evt) {
		var alertOpt = {
			id: 'search_alert',
			parent: '#search',
			position: 'center'
		}
		
		var doSearch = false;
		if(evt != undefined) {
			if(utility.getKeyPressed(evt) == 13) { // enter key
				doSearch = true;
			}
		} else {
			doSearch = true;
		}
		
		if(doSearch) {
			var searchString = '';
			var url = $('.icon_search').attr('url');
			if(evt != undefined) {
				searchString = $(obj).val();
			} else {
				searchString = $(obj).siblings('.search_wrapper').find('input').val();
			}
			
			if(!searchString.isEmpty()) {
				var maskId;
				var ajaxParams = {searchString: searchString};
				var ajaxSuccess = function(data, textStatus) {
					var callback = function() {
						if(!$($.parseHTML(data)).hasClass('alert')) {
							var parent = '#search';
							$(parent).find('.table_body').empty();
							jsTable.tableScrollableAdd(parent, data);
						} else {
							alertOpt['type'] = alertType.error;
							alertOpt['text'] = 'No record matched.';
							$.alert(alertOpt);
						}
					};
					$.unmask({id: maskId, callback: callback});
				};
				$('#StudentId').val(0);
				$.ajax({
					type: 'GET',
					dataType: 'text',
					url: getRootURL() + url,
					data: {searchString: searchString},
					beforeSend: function (jqXHR) { maskId = $.mask({parent: '#search', text: i18n.Search.textSearching}); },
					success: ajaxSuccess
				});
			}
		}
	},
	
	addStudent: function(obj) {
		var row = $(obj);
		var id = row.attr('row-id');
		var idNo = row.attr('id-no');
		var fName = row.attr('first-name');
		var mName = row.attr('middle-name');
		var lName = row.attr('last-name');
		var pName = row.attr('preferred-name');
		var gender = row.attr('gender');
		
		$('#StudentId').val(id);
		$('#IdentificationNo').html(idNo);
		$('#FirstName').html(fName);
		$('#MiddleName').html(mName);
		$('#LastName').html(lName);
		$('#PreferredName').html(pName);
		$('#Gender').html(gender);
	},
	
	validateStudentAdd: function() {
		return $('#StudentId').val()!=0 && $('#InstitutionSiteProgrammeId').val()>0;
	}
}
