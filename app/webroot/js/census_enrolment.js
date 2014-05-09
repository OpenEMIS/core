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
        viewUrl: 'enrolment/',
	
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
                
        computeByAgeGender: function(obj){
                var row = $(obj).closest('tr');
                var totalByAgeMaleValue = 0;
                var totalByAgeFemaleValue = 0;
                var totalAll = 0;
                var currentFieldset = $(obj).closest('fieldset');
                var rowTotalRightCol = currentFieldset.find('td.rowTotalRightCol');
                
                if($(obj).attr('id') === 'CensusStudentMale'){
                    var totalByAgeMaleTd = row.find('td.totalByAgeMale');
                    var femaleRow = row.next("tr[gender='female']");
                    
                    row.find('input#CensusStudentMale').each(function(){
                        if($(this).val() !== ''){
                            totalByAgeMaleValue += $(this).val().toInt();
                        }
                    });
                    totalByAgeMaleTd.html(totalByAgeMaleValue);
                    
                    if(femaleRow.find('td.totalByAgeFemale').html() !== ''){
                        totalByAgeFemaleValue = femaleRow.find('td.totalByAgeFemale').html().toInt();
                    }
                    
                    row.find('td.totalByAgeAllGender').html(totalByAgeMaleValue + totalByAgeFemaleValue);
                    
                }else{
                     var totalByAgeFemaleTd = row.find('td.totalByAgeFemale');
                     var maleRow = row.prev("tr[gender='male']");
                     
                     row.find('input#CensusStudentFemale').each(function(){
                         if($(this).val() !== ''){
                             totalByAgeFemaleValue += $(this).val().toInt();
                         }
                     });
                     totalByAgeFemaleTd.html(totalByAgeFemaleValue);
                     
                     if(maleRow.find('td.totalByAgeMale').html() !== ''){
                         totalByAgeMaleValue = maleRow.find('td.totalByAgeMale').html().toInt();
                     }
                     
                     maleRow.find('td.totalByAgeAllGender').html(totalByAgeMaleValue + totalByAgeFemaleValue);
                }
                
                currentFieldset.find('td.totalByAgeAllGender').each(function(){
                    if($(this).html() !== ''){
                        totalAll += $(this).html().toInt();
                    }
                });
                
                rowTotalRightCol.html(totalAll);
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
		var categoryId = parent.find('#StudentCategoryId').val();
		var edit = $(CensusEnrolment.id).hasClass('edit');
                var programmeId = parent.attr('programme_id');
		
		var maskId;
                var ajaxParams = {categoryId: categoryId, programmeId: programmeId, edit: edit};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				parent.find('.ajaxContentHolder').html(data);
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
		var rowNum = parent.find("tr[type='input'][gender='male']").length;
		var last = "tr[type='input'][gender='male']:last";
		//var gradeId = parent.find('#EducationGradeId').val();
		
		var age = parent.attr('admission_age');
                var programmeId = parent.attr('programme_id');
                if (rowNum > 0) {
                    lastAge = parent.find(last);
                    if (lastAge.length > 0) {
                        age = lastAge.attr('age').toInt() + 1;
                        while (CensusEnrolment.isAgeExistInList(parent, age)) {
                            age++;
                        }
                    }
                }

                var maskId;
                var ajaxParams = {programmeId: programmeId, age: age};
                var ajaxSuccess = function(data, textStatus) {
                    var callback = function() {
                        if(rowNum === 0){
                            var lastHeaderRow = parent.find("tr.th_bg:last");
                            lastHeaderRow.after(data);
                        }else{
                            parent.find("tr[type='input'][gender='female']:last").after(data);
                        }
                    };
                    $.unmask({id: maskId, callback: callback});
                };
                
                $.ajax({
                    type: 'GET',
                    dataType: 'text',
                    url: getRootURL() + $(this).attr('url'),
                    data: ajaxParams,
                    beforeSend: function(jqXHR) {
                        maskId = $.mask({parent: parent});
                    },
                    success: ajaxSuccess
                });
	},
	
	removeRow: function(obj) {
		var row = $(obj).closest('tr');
                var tbody = row.closest('tbody');
                var age = row.attr('age');
                var rowFemale = row.next("tr[gender='female']");
                var currentFieldset = $(obj).closest('fieldset');
                var totalAll = 0;

                row.find('.input_wrapper').each(function(){
                    var censusId = $(this).attr('census_id');
                    if(censusId !== 0){
                        CensusEnrolment.deletedRecords.push(censusId);
                    }
                });
                
		row.remove();
                rowFemale.remove();
                
                var rowTotalRightCol = currentFieldset.find('td.rowTotalRightCol');
                
                currentFieldset.find('td.totalByAgeAllGender').each(function(){
                    if($(this).html() !== ''){
                        totalAll += $(this).html().toInt();
                    }
                });
                
                rowTotalRightCol.html(totalAll);
                
	},
	
	validateData: function() {
		var duplicate = false;
		$('fieldset tbody').each(function() {
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
                    var alertOpt = {
			text: i18n.Enrolment.textDuplicateAges,
			type: alertType.warn,
			position: 'center'
                    };
                    $.alert(alertOpt);
                    return false;
		}
                var yearId = $('#CensusStudentSchoolYearId').val();
                var id, age, male, female;
                var obj, gradeId, data = [];
                var objTrMale, inputFieldMale;
                $('fieldset').each(function() {
                    obj = $(this);
                    obj.find("tr[gender='male'][type='input']").each(function() {
                        objTrMale = $(this);
                        if(objTrMale.find("input#CensusStudentAge").length > 0){
                            age = objTrMale.find("input#CensusStudentAge").val();
                        }else{
                            age = objTrMale.attr('age');
                        }
                        
                        objTrMale.find("input#CensusStudentMale").each(function(){
                            inputFieldMale = $(this);
                            id = inputFieldMale.parent(".input_wrapper").attr('census_id');
                            gradeId = inputFieldMale.parent(".input_wrapper").attr('grade_id');
                            male = inputFieldMale.val();
                            female = objTrMale.next("tr[gender='female']").find(".input_wrapper[census_id=" + id + "][grade_id='" + gradeId + "']").find("input#CensusStudentFemale").val();
                            
                            if(!age.isEmpty()) {
					data.push({
						id: id,
						age: age,
						male: male,
						female: female,
						education_grade_id: gradeId,
						student_category_id: obj.find('#StudentCategoryId').val(),
						school_year_id: yearId
					});
				}
                        });
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
                                        window.onbeforeunload = null;
                                        location.href = CensusEnrolment.base + CensusEnrolment.viewUrl + yearId;
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	}
};
