$(document).ready(function() {
	enrolment.init();
});

var enrolment = {
	base: getRootURL() + 'Census/',
	id: '#enrolment',
	deletedRecords: [],
	ajaxUrl: 'enrolmentAjax',
	
	init: function() {
		var id = enrolment.id;
		$('[programme-id]').each(function() {
			var id = $(this).attr('programme-id');
			$(this).find('.link_add').click(function() { enrolment.addRow(id); });
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
		enrolment.computeTotal(table);
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
		var edit = $(enrolment.id).hasClass('edit');
		
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
	
	addRow: function(id) {
		var obj = $('[programme-id="' + id + '"]');
		
		if(obj.find('.table_body').length==0) {
			obj.find('.table .table_head').after('<div class="table_body"></div>');
		}
		var rowNum = obj.find('.table_body .table_row').length;
		var last = '.table_body .table_row:last';
		var age = 3;
		if(rowNum > 0) {
			lastAge = obj.find(last).find('#CensusStudentAge').val();
			if(lastAge.length>0) {
				age = lastAge.toInt() + 1;
			}
		}
		var cell = '<div class="table_cell">';
		var wrapper = '<div class="input_wrapper">';
		var html = '<div class="table_row' + ((rowNum+1)%2==0 ? ' even' : '') + '" record-id="0">';
		html += cell + wrapper + '<input id="CensusStudentAge" type="text" defaultValue="' + age + '" value="' + age + '" maxlength="2" /></div></div>';
		html += cell + wrapper + '<input id="CensusStudentMale" type="text" defaultValue="0" value="0" maxlength="10" /></div></div>';
		html += cell + wrapper + '<input id="CensusStudentFemale" type="text" defaultValue="0" value="0" maxlength="10" /></div></div>';
		html += '<div class="table_cell cell_total cell_number">0</div>';
		html += '<div class="table_cell"><span class="icon_delete" title="' + i18n.General.textDelete + '"></span></div>';
		html += '</div>';
		
		obj.find('.table_body').append(html);
		var lastRow = obj.find(last);
		lastRow.find('#CensusStudentAge').select();
		lastRow.find('input').keypress(function(evt) {
			return utility.integerCheck(evt);
		});
		
		lastRow.find('#CensusStudentMale').keyup(function() {
			enrolment.computeSubtotal(this);
		});
		
		lastRow.find('#CensusStudentFemale').keyup(function() {
			enrolment.computeSubtotal(this);
		});
		lastRow.find('.icon_delete').click(function() {
			enrolment.removeRow(this);
		});
	},
	
	removeRow: function(obj) {
		var row = $(obj).closest('.table_row');
		var tableBody = row.closest('.table_body');
		var table = row.closest('.table');
		var id = row.attr('record-id');
		if(id!=0) {
			enrolment.deletedRecords.push(id);
		}
		row.remove();
		enrolment.computeTotal(table);
		if(tableBody.find('.table_row').length==0) {
			tableBody.remove();
		}
		jsTable.init();
	},
	
	save: function() {
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
			data: {data: data, deleted: enrolment.deletedRecords},
			beforeSend: function (jqXHR) {
				maskId = $.mask({id: maskId, text: i18n.General.textSaving});
			},
			success: function (data, textStatus) {
				var callback = function() {
					var row, index, ageInput, maleInput, femaleInput;
					$(enrolment.id + ' .table_row').each(function() {
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
					enrolment.deletedRecords = [];
					$.alert({text: i18n.General.textRecordUpdateSuccess});
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	}
}