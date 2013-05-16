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
	CensusEnrolment.init();
});

var CensusEnrolment = {
	base: getRootURL() + 'Census/',
	id: '#enrolment',
	deletedRecords: [],
	ajaxUrl: 'enrolmentAjax',
	
	init: function() {
		$('.icon_plus').click(CensusEnrolment.addRow);
	},
	
	computeSubtotal: function(obj) {
		var row = $(obj).closest('.table_row');
		var male = row.find('#CensusStudentMale');
		var female = row.find('#CensusStudentFemale');
		
		if(male.val().isEmpty()) {
			male.val(0);
			obj.select();
		}
		if(female.val().isEmpty()) {
			female.val(0);
			obj.select();
		}
		
		row.find('.cell_total').html(male.val().toInt() + female.val().toInt());
		var table = $(obj).closest('.table');
		CensusEnrolment.computeTotal(table);
	},
	
	computeTotal: function(table) {
		var total = 0;
		table.find('.cell_total').each(function() {
			total += $(this).html().toInt();
		});
		table.find('.table_foot .cell_value').html(total);
	},
	
	get: function(obj) {
		var parent = $(obj).closest('fieldset');
		var gradeId = parent.find('#EducationGradeId').val();
		var categoryId = parent.find('#StudentCategoryId').val();
		var edit = $(CensusEnrolment.id).hasClass('edit');
		
		if(gradeId == 0) {
			var alertOpt = {
				parent: parent,
				text: i18n.Enrolment.textNoGrades,
				type: alertType.warn,
				position: 'center'
			};
			$.alert(alertOpt);
		} else {
			var maskId;
			var ajaxParams = {gradeId: gradeId, categoryId: categoryId, edit: edit};
			var ajaxSuccess = function(data, textStatus) {
				var callback = function() {
					parent.find('.table_body').remove();
					parent.find('.table_foot').remove();
					parent.find('.table').append(data);
				};
				$.unmask({id: maskId, callback: callback});
			};
			$.ajax({
				type: 'GET',
				dataType: 'text',
				url: getRootURL() + parent.attr('url'),
				data: ajaxParams,
				beforeSend: function (jqXHR) { maskId = $.mask({parent: parent, text: i18n.General.textRetrieving}); },
				success: ajaxSuccess
			});
		}
	},
	
	isAgeExistInList: function(obj, age) {
		var found = false;
		obj.find('#CensusStudentAge').each(function() {
			if($(this).val().toInt()==age) {
				found = true;
				return false;
			}
		});
		return found;
	},
	
	checkExistingAge: function(obj) {
		var parent = $(obj).closest('fieldset');
		var age = $(obj).val().toInt();
		var count = 0;
		parent.find('#CensusStudentAge').each(function() {
			if($(this).val().toInt()==age) {
				count++;
			}
		});
		if(count>1) {
			var alertOpt = {
				parent: parent,
				text: i18n.Enrolment.textDuplicateAge,
				type: alertType.error,
				position: 'center'
			};
			$.alert(alertOpt);
			setTimeout(function() { $(obj).select(); }, 300);
		}
	},
	
	addRow: function() {
		var parent = $(this).closest('fieldset');
		var rowNum = parent.find('.table_row').length;
		var last = '.table_body .table_row:last';
		var gradeId = parent.find('#EducationGradeId').val();
		
		if(gradeId == 0) {
			var alertOpt = {
				parent: parent,
				text: i18n.Enrolment.textNoGrades,
				type: alertType.warn,
				position: 'center'
			};
			$.alert(alertOpt);
		} else {
			var age = 0;
			if(rowNum > 0) {
				lastAge = parent.find(last).find('#CensusStudentAge').val();
				if(lastAge.length>0) {
					age = lastAge.toInt() + 1;
					while(CensusEnrolment.isAgeExistInList(parent, age)) {
						age++;
					}
				}
			}
			
			var maskId;
			var ajaxParams = {rowNum: rowNum, age: age, gradeId: gradeId};
			var ajaxSuccess = function(data, textStatus) {
				var callback = function() {
					var tableBody = parent.find('.table_body');
					if(tableBody.length==0) {
						parent.find('.table .table_head').after('<div class="table_body">' + data + '</div>');
					} else {
						tableBody.append(data);
					}
				};
				$.unmask({id: maskId, callback: callback});
			};
			
			$.ajax({
				type: 'GET',
				dataType: 'text',
				url: getRootURL() + $(this).attr('url'),
				data: ajaxParams,
				beforeSend: function (jqXHR) { maskId = $.mask({parent: parent}); },
				success: ajaxSuccess
			});
		}
	},
	
	removeRow: function(obj) {
		var row = $(obj).closest('.table_row');
		var tableBody = row.closest('.table_body');
		var table = row.closest('.table');
		var id = row.attr('record-id');
		if(id!=0) {
			CensusEnrolment.deletedRecords.push(id);
		}
		row.remove();
		CensusEnrolment.computeTotal(table);
		if(tableBody.find('.table_row').length==0) {
			tableBody.remove();
		}
		jsTable.init();
	},
	
	validateData: function() {
		var duplicate = false;
		$('.table_body').each(function() {
			var ageList = [];
			$(this).find('#CensusStudentAge').each(function() {
				var age = $(this).val().toInt();
				if($.inArray(age, ageList) == -1) {
					ageList.push(age);
				} else {
					duplicate = true;
					return false;
				}
			});
			if(duplicate) {
				return false;
			}
		});
		return duplicate != true;
	},
	
	save: function() {
		if(!CensusEnrolment.validateData()) {
			return false;
		}
		var yearId = $('#SchoolYearId').val();
		var id, age, male, female, index=0;
		var obj, records, gradeId, categoryId, data = [];
		$('fieldset').each(function() {
			obj = $(this);
			obj.find('.table_body .table_row').each(function() {
				id = $(this).attr('record-id');
				age = $(this).find('#CensusStudentAge').val();
				male = $(this).find('#CensusStudentMale').val();
				female = $(this).find('#CensusStudentFemale').val();
				
				if(!age.isEmpty()) {
					index++;
					data.push({
						id: id,
						age: age,
						male: male,
						female: female,
						education_grade_id: obj.find('#EducationGradeId').val(),
						student_category_id: obj.find('#StudentCategoryId').val(),
						school_year_id: yearId
					});
				}
				
				if(id==0) {
					$(this).attr('index', index);
				}
			});
		});
		
		var maskId;
		var url = this.base + this.ajaxUrl;
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: url,
			data: {data: data, deleted: CensusEnrolment.deletedRecords},
			beforeSend: function (jqXHR) {
				maskId = $.mask({id: maskId, text: i18n.General.textSaving});
			},
			success: function (data, textStatus) {
				var callback = function() {
					var row, index, ageInput, maleInput, femaleInput;
					$(CensusEnrolment.id + ' .table_row').each(function() {
						row = $(this);
						index = row.attr('index');
						ageInput = row.find('#CensusStudentAge');
						maleInput = row.find('#CensusStudentMale');
						femaleInput = row.find('#CensusStudentFemale');
						
						if(index!=undefined) {
							row.attr('record-id', data[index]).removeAttr('index');
						}
						
						if(row.attr('record-id') > 0 && maleInput.val().toInt() == 0 && femaleInput.val().toInt() == 0) {
							row.attr('record-id', 0);
						}
						
						ageInput.attr('defaultValue', ageInput.val().toInt());
						ageInput.val(ageInput.val().toInt());
						maleInput.attr('defaultValue', maleInput.val().toInt());
						maleInput.val(maleInput.val().toInt());
						femaleInput.attr('defaultValue', femaleInput.val().toInt());
						femaleInput.val(femaleInput.val().toInt());
					});
					CensusEnrolment.deletedRecords = [];
					$.alert({text: i18n.General.textRecordUpdateSuccess});
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	}
}