$(document).ready(function() {
	CensusEnrolment.init();
});

var CensusEnrolment = {
	base: getRootURL() + 'Census/',
	id: '#enrolment',
	deletedRecords: [],
	ajaxUrl: 'enrolmentAjax',
	
	init: function() {
		var id = CensusEnrolment.id;
		$('[programme-id]').each(function() {
			var id = $(this).attr('programme-id');
			$(this).find('.icon_plus').click(CensusEnrolment.addRow);
		});
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
	
	get: function(id) {
		var maskId = '#mask-' + id;
		var parent = '[programme-id="' + id + '"]';
		var yearId = $('#SchoolYearId').val();
		var gradeId = $(parent + ' #EducationGradeId').val();
		var categoryId = $(parent + ' #StudentCategoryId').val();
		var url = this.base + this.ajaxUrl;
		var edit = $(CensusEnrolment.id).hasClass('edit');
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {
				'year': yearId,
				'grade': gradeId,
				'category': categoryId,
				'edit': edit
			},
			beforeSend: function (jqXHR) {
				$.mask({id: maskId, parent: parent, text: i18n.General.textRetrieving});
			},
			success: function (data, textStatus) {
				var callback = function() {
					$(parent + ' .table_body').remove();
					$(parent + ' .table_foot').remove();
					$(parent + ' .table').append(data);
				};
				
				$.unmask({id: maskId, callback: callback});
			}
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
		var programmeId = parent.attr('programme-id');
		
		var rowNum = parent.find('.table_row').length;
		var last = '.table_body .table_row:last';
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
		var ajaxParams = {programmeId: programmeId, rowNum: rowNum, age: age};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				var tableBody = parent.find('.table_body');
				if(tableBody.length==0) {
					parent.find('.table .table_head').after('<div class="table_body"></div>');
				}
				tableBody.append(data);
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
		$('[programme-id]').each(function() {
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
						institution_site_programme_id: obj.attr('programme-id'),
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