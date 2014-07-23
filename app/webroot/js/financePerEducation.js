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
	Finance.init();

	$('#areapicker.areapicker').on('change', 'select', function() {
		if ($(this).val() != '' && $(this).val() > 0) {
			currentAreaId = $(this).val();
		} else {
			var parentAreaSelect = $(this).parents('.form-group').prev('.form-group').find('select.form-control');
			if (parentAreaSelect.length > 0) {
				var parentAreaId = parentAreaSelect.val();
				currentAreaId = parentAreaId;
			} else {
				currentAreaId = 0;
			}
		}

		if ($('#financePerEducation').hasClass('edit')) {
			Finance.fetchDataByArea(currentAreaId, 'edit');
		} else {
			Finance.fetchDataByArea(currentAreaId, '');
		}

		$('a.withLatestAreaId').each(function() {
			var newHref = $(this).attr('href').replace(/(\/\d{4}\/)\d*(\/\d*)/, '$1' + currentAreaId + '$2');
			$(this).attr('href', newHref);
		});

		$('a.btn_cancel').each(function() {
			var newCancelHref = $(this).attr('href').replace(/(\/\d{4}\/)\d*(\/\d*)/, '$1' + currentAreaId + '$2');
			$(this).attr('href', newCancelHref);
		});

		$('form#FinanceFinancePerEducationLevelEditForm').each(function() {
			var newAction = $(this).attr('action').replace(/(\/\d{4}\/)\d*(\/\d*)/, '$1' + currentAreaId + '$2');
			$(this).attr('action', newAction);
		});
	});

	$('select#financeYear').on('change', function() {
		var eduLevelId = $('select#educationLevel').val();
		
		if ($('#financePerEducation').hasClass('edit')) {
			location.href = Finance.base + 'financePerEducationLevelEdit/' + $(this).val() + '/' + currentAreaId + '/' + eduLevelId;
		} else {
			location.href = Finance.base + 'financePerEducationLevel/' + $(this).val() + '/' + currentAreaId + '/' + eduLevelId;
		}
	});
	
	$('select#educationLevel').on('change', function() {
		var currentEduLevelId = $(this).val();
		
		if ($('#financePerEducation').hasClass('edit')) {
			Finance.fetchDataByArea(currentAreaId, 'edit');
		} else {
			Finance.fetchDataByArea(currentAreaId, '');
		}
		
		$('a.withLatestAreaId').each(function() {
			var newHref = $(this).attr('href').replace(/(\/\d{4}\/\d+\/)\d*/, '$1' + currentEduLevelId);
			$(this).attr('href', newHref);
		});

		$('a.btn_cancel').each(function() {
			var newCancelHref = $(this).attr('href').replace(/(\/\d{4}\/\d+\/)\d*/, '$1' + currentEduLevelId);
			$(this).attr('href', newCancelHref);
		});

		$('form#FinanceFinancePerEducationLevelEditForm').each(function() {
			var newAction = $(this).attr('action').replace(/(\/\d{4}\/\d+\/)\d*/, '$1' + currentEduLevelId);
			$(this).attr('action', newAction);
		});
	});
});


var Finance = {
	// properties
	year: 0000,
	parentAreaIds: new Array(),
	currentAreaId: 0,
	isEditable: false,
	base: getRootURL() + 'Finance/',
	id: '#finance',
	ajaxUrl: 'financePerEducationAjax',
	// methods
	init: function() {
		this.changeView();
	},
	changeView: function() {
		var pageTitle = $('h1 > span').text();
		var urlLinks = new Array();
		urlLinks['Total Public Expenditure'] = getRootURL() + "Finance";
		urlLinks['Total Public Expenditure Per Education Level'] = getRootURL() + "Finance/financePerEducationLevel";

		$("select#view option").each(function() {
			this.selected = (this.text == pageTitle);
		});

		// if urlLinks and pageTitle tallies, redirect to the page
		$('#view').bind('change', function() {
			var url = urlLinks[$(this).val()];
			// console.log($(this).val());
			// console.log(url);

			if (url) { // require a URL
				window.location = url; // redirect
			}
			return false;
		});
	},
	fetchDataByArea: function(areaId, mode) {
		var year = $('select#financeYear').val();
		var eduLevelId = $('select#educationLevel').val();

		var url;
		if (mode === 'edit') {
			url = Finance.base + 'loadPerEducationForm/' + year + '/' + areaId + '/' + eduLevelId;
		} else {
			url = Finance.base + 'loadPerEducationData/' + year + '/' + areaId + '/' + eduLevelId;
		}

		$.ajax({
			type: 'GET',
			dataType: 'html',
			url: url,
			success: function(data, textStatus) {
				var replaceHolder = $('.replaceHolder');

				if (data.length > 0 && replaceHolder.length === 1) {
					if (replaceHolder.length > 0) {
						replaceHolder.html(data);
					}
				}
			}
		});
	}
}
