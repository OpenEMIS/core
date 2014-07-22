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
			var newHref = $(this).attr('href').replace(/(\/\d{4}\/)\d*/, '$1' + currentAreaId);
			$(this).attr('href', newHref);
		});

		$('a.btn_cancel').each(function() {
			var newCancelHref = $(this).attr('href').replace(/(\/\d{4}\/)\d*/, '$1' + currentAreaId);
			$(this).attr('href', newCancelHref);
		});

		$('form#FinanceEditForm').each(function() {
			var newAction = $(this).attr('action').replace(/(\/\d{4}\/)\d*/, '$1' + currentAreaId);
			$(this).attr('action', newAction);
		});
	});

	$('select#financeYear').on('change', function() {
		if ($('#financePerEducation').hasClass('edit')) {
			location.href = Finance.base + 'financePerEducationLevelEdit/' + $(this).val() + '/' + currentAreaId;
		} else {
			location.href = Finance.base + 'financePerEducationLevel/' + $(this).val() + '/' + currentAreaId;
		}
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
	show: function(id) {
		$('#' + id).css("visibility", "visible");
	},
	hide: function(id) {
		$('#' + id).css("visibility", "hidden");
	},
	TotalPublicExpenditureBacktoList: function() {
		window.location = getRootURL() + "Finance";
	},
	TotalPublicExpenditurePerEducationLevelBacktoList: function() {
		window.location = getRootURL() + "Finance/financePerEducationLevel";
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
	addAreaSwitching: function() {

		$('select[name*="[area_level_"]').each(function(i, obj) {
			$(obj).change(function(d, o) {
				//console.info('trigger');
				//console.info(parseInt($(this).find(':selected').val()));

				var TotalAreaLevel = $('select[name*="[area_level_"]').length;
				var isAreaLevelForInput = $(this).parent().parent().parent().attr('id');
				var currentSelctedOptionValue = parseInt($(this).find(':selected').val());
				var currentSelect = $(this).attr('name').replace('data[Finance][area_level_', '');
				currentSelect = currentSelect.replace(']', '');
				currentSelect = parseInt(currentSelect);

				if (isAreaLevelForInput !== undefined && isAreaLevelForInput.match(/input/gi)) {
					isAreaLevelForInput = true;
				} else {
					isAreaLevelForInput = false
				}

				// console.info(currentSelect);
				//console.info(currentSelctedOptionValue);
				if (isAreaLevelForInput) {
					//console.info(areaLevelForInput);
					for (var i = currentSelect + 1; i < TotalAreaLevel; i++) {
						//disable the select element
						$('select[name=data\\[Finance\\]\\[area_level_' + i + '\\]][class=input_area_level_selector]').attr('disabled', 'disabled');
						$('select[name=data\\[Finance\\]\\[area_level_' + i + '\\]][class=input_area_level_selector]').parent().parent().find('.label').addClass('disabled');

						$('select[name=data\\[Finance\\]\\[area_level_' + i + '\\]][class=input_area_level_selector]').find('option').remove();
					}
					;
				} else {
					for (var i = currentSelect + 1; i < TotalAreaLevel; i++) {
						//disable the select element
						$('select[name=data\\[Finance\\]\\[area_level_' + i + '\\]][class!=input_area_level_selector]').attr('disabled', 'disabled');
						$('select[name=data\\[Finance\\]\\[area_level_' + i + '\\]][class!=input_area_level_selector]').parent().parent().find('.label').addClass('disabled');

						$('select[name=data\\[Finance\\]\\[area_level_' + i + '\\]][class!=input_area_level_selector]').find('option').remove();
					}
					;
				}

				// console.info('currentSelect: ' + currentSelect);
				// console.info('currentSelctedOptionValue: ' + currentSelctedOptionValue);
				// console.log(Finance.parentAreaIds);

				if (currentSelctedOptionValue > 0) {
					Finance.parentAreaIds[currentSelect] = currentSelctedOptionValue;
				} else {
					Finance.parentAreaIds.splice(currentSelect, Finance.parentAreaIds.length - currentSelect);
				}

				Finance.currentAreaId = currentSelctedOptionValue;

				if (currentSelctedOptionValue >= 0 && !isAreaLevelForInput && Finance.parentAreaIds.length > 0) {
					Finance.fetchData(this);
				} else {
					$('.mainlist .table .table_body').html('');
				}

				if (((currentSelect == 0 && currentSelctedOptionValue > 0) || (currentSelect != 0 && currentSelctedOptionValue > 1))) {
					Finance.fetchChildren(this);
				}

				if (currentSelect == 0 && currentSelctedOptionValue == 0) {
					$('.table_body').hide();
					//$('.table_body').show();
				}

			});
		});

	},
	fetchChildren: function(currentobj) {
		var selected = $(currentobj).val();
		var maskId;
		var url = Finance.base + 'viewAreaChildren/' + selected;

		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: url,
			beforeSend: function(jqXHR) {
				maskId = $.mask({parent: '.content_wrapper'});
			},
			success: function(data, textStatus) {
				//console.log(data)

				var callback = function(data) {
					tpl = '';
					var nextselect = $(currentobj).parent().parent().next().find('select');
					var nextLabel = nextselect.parent().parent().find('.label');
					//data[1] += nextLabel.text().toUpperCase(); // Add "ALL <text>" option in the select element
					$.each(data, function(i, o) {
						tpl += '<option value="' + i + '">' + data[i] + '</option>';
					})
					nextLabel.removeClass('disabled');
					nextselect.find('option').remove();
					nextselect.removeAttr('disabled');
					nextselect.append(tpl);

				};
				$.unmask({id: maskId, callback: callback(data)});
			}
			/*error: function(jqXHR, textStatus, errorThrown) {
			 $.unmask({ id: maskId});
			 //maskId = $.mask({parent: '#site', text:'Login Timeout.<br/>Redirection to login.'});
			 if(jqXHR.status === 403){
			 window.location = getRootURL()+'/Finance';;
			 }
			 }*/
		});
	},
	
	fetchDataByArea: function(areaId, mode) {
		var year = $('select#financeYear').val();

		var url;
		if (mode === 'edit') {
			url = Finance.base + 'loadPerEducationForm/' + year + '/' + areaId;
		} else {
			url = Finance.base + 'loadPerEducationData/' + year + '/' + areaId;
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
	},
	
	fetchData: function(currentObject) {

		// init values
		var selectedValue = Finance.currentAreaId;
		var parentAreaIds = Finance.parentAreaIds[Finance.parentAreaIds.length - 1 ];

		// if object exist update with later value
		if (currentObject !== undefined) {
			selectedValue = $(currentObject).val();
			parentAreaIds = Finance.parentAreaIds[Finance.parentAreaIds.length - 1 ];
			// parentAreaIds = selectedValue;
		}

		var maskId;
		var url = Finance.base + 'viewPerEducationData/' + this.year;

		//if(parseInt(selectedValue) > 0 ){
		url += '/' + selectedValue;
		//}
		//console.info(parentAreaIds);
		if (typeof parentAreaIds !== "undefined" && parseInt(parentAreaIds) !== 0) {
			url += '/' + parentAreaIds;
		}

		// console.info(url);

		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: url,
			beforeSend: function(jqXHR) {
				// maskId = $.mask({parent: '#site', text:'loading'});
				maskId = $.mask({parent: '.content_wrapper'});
			},
			success: function(data, textStatus) {

				var callback = function(data) {

					var tpl = '';
					// var tableBody;
					var tableBody = $('.mainlist .table .table_body');
					tableBody.children().remove();

					if (data !== 'false' && data !== false) {
						if (Finance.isEditable === true) {

							$.each(data, function(i, o) {
								var eduLevel = '#edu_level_' + i;
								var eduTableBody = $(eduLevel + ' .table .table_body');
								eduTableBody.children().remove();
								$(Finance.renderRecordToHtmlTableRowForEdit(data[i]['areas'])).appendTo(eduTableBody);

							});

//                                if(data.length > 0){
//                                    $('.btn_save').removeClass('btn_disabled');
//                                }else{
//                                    $('.btn_save').addClass('btn_disabled');
//                                }
							/*if (data.length <= 0) {
							 $('.btn_save').addClass('btn_disabled');
							 }*/
						} else {

							$.each(data, function(i, o) {
								var eduLevel = '#edu_level_' + i;
								var eduTableBody = $(eduLevel + ' .table .table_body');
								// eduTableBody.children().remove();
								$(Finance.renderRecordToHtmlTableRow(data[i]['areas'])).appendTo(eduTableBody);

							});

						}
						tableBody.append(tpl);
						if (tableBody.is(':visible') === false) {
							tableBody.show();
						}

						// tableBody.html(tpl);
					} else {
						tableBody.html('');
						tableBody.hide();
					}

				};
				$.unmask({id: maskId, callback: callback(data)});
			}
			/*error: function(jqXHR, textStatus, errorThrown) {
			 $.unmask({ id: maskId});
			 if(jqXHR.status === 403){
			 window.location = getRootURL()+'/Finance';
			 }
			 
			 }*/
		});
	},
	checkEdited: function() {
		var obj = $(Finance.id);
		var saveBtn = obj.find('.btn_save');
		var disabledClass = 'btn_disabled';
		var modified = false;
		if (obj.find('.table_row[record-id="0"]').length > 0) {
			modified = true;
		} else {
			obj.find('.table_body input').each(function() {
				if ($(this).attr('defaultValue') != this.value) {
					modified = true;
					return false;
				}
			});
		}

		if (modified) {
			if (saveBtn.hasClass(disabledClass)) {
				saveBtn.removeClass(disabledClass);
			}
		} else {
			if (!saveBtn.hasClass(disabledClass)) {
				saveBtn.addClass(disabledClass);
			}
		}
	},
	// Rending of htmls for view and edit
	renderRecordToHtmlTableRow: function(data) {

		// console.log(data);
		var html = '';

		$.each(data, function(i, o) {
			html += '<div id="" class="table_row ' + (((i + 1) % 2 === 0) ? 'even' : '') + '">';

			if ((i == 0) && i < data.length - 1) {
				// html += '<div class="table_cell cell_hide_bottom_border"></div>';
				html += '<div class="table_cell">' + data[i].area_level_name + '</div>';
			} else if (i == 0 && i == data.length - 1) {
				html += '<div class="table_cell cell_hide_background">' + data[i].area_level_name + '</div>';
				// } else if ((i == 1) && (i < data.length-1)) {
			} else if (i == 1 && (i < data.length - 1)) {
				html += '<div class="table_cell cell_hide_bottom_border cell_hide_background">' + data[i].area_level_name + '</div>';
			} else if (i == 1 && i == data.length - 1) {
				html += '<div class="table_cell">' + data[i].area_level_name + '</div>';
			} else if (i > 1 && i == data.length - 1) {
				// last row
				html += '<div class="table_cell cell_hide_background"></div>';
			} else {
				html += '<div class="table_cell cell_hide_bottom_border cell_hide_background"></div>';
			}

			html += '<div class="table_cell">' + data[i].name + '</div>';
			html += '<div class="table_cell cell_amount">' + data[i].value + '</div>';
			html += '</div>';
		});

		return html;
	},
	renderRecordToHtmlTableRowForEdit: function(data) {

		// console.log(data);
		var html = '';

		$.each(data, function(i, o) {
			// html += '<div id="" class="table_row" record-id="'+data[i].id+'">';
			html += '<div id="" class="table_row ' + (((i + 1) % 2 === 0) ? 'even' : '') + '" record-id="' + data[i].id + '" area-id="' + data[i].area_id + '">';


			if ((i == 0) && i < data.length - 1) {
				// html += '<div class="table_cell cell_hide_bottom_border"></div>';
				html += '<div class="table_cell">' + data[i].area_level_name;
			} else if (i == 0 && i == data.length - 1) {
				html += '<div class="table_cell cell_hide_background">' + data[i].area_level_name;
			} else if ((i == 1) && (i < data.length - 1)) {
				html += '<div class="table_cell cell_hide_bottom_border cell_hide_background">' + data[i].area_level_name;
			} else if (i == 1 && i == data.length - 1) {
				html += '<div class="table_cell">' + data[i].area_level_name;
			} else if (i > 1 && i == data.length - 1) {
				// last row
				html += '<div class="table_cell cell_hide_background">';
			} else {
				html += '<div class="table_cell cell_hide_bottom_border cell_hide_background">';
			}

			html += '<input type="hidden" name="data[areaId]" value="' + data[i].area_id + '" />';
			html += '<input type="hidden" id="educationLevelId" name="data[educationLevelId]" value="' + data[i].education_level_id + '" />';
			html += '</div>'

			html += '<div class="table_cell">' + data[i].name + '</div>';

			html += '<div class="table_cell">';
			html += '   <div class="input_wrapper">';
			html += '       <input type="text" id="amount" name="data[value]" value="' + data[i].value + '" maxlength="10" autocomplete="false" onkeypress="return utility.integerCheck(event)" onkeyup="Finance.checkEdited()" />';
			html += '   </div>';
			html += '</div>';

			html += '</div>';
		});

		return html;

	},
	save: function() {
//        if($('.btn_save').hasClass('btn_disabled')) {
//            return;
//        }

		var yearId = Finance.year;
		var id, areaId, educationLevelId, value, index = 0;
		var data = [];

		$('.table_body .table_row').each(function() {
			id = $(this).attr('record-id');
			areaId = $(this).find('[name=data\\[areaId\\]]').val();
			educationLevelId = $(this).find('#educationLevelId').val();
			value = $(this).find('#amount').val();

			if (!value.isEmpty()) {
				index++;
				data.push({
					id: id,
					index: index,
					value: value,
					education_level_id: educationLevelId,
					year: yearId,
					area_id: areaId
							// area_id: Finance.currentAreaId
				});

				if (id == 0) {
					$(this).attr('index', index);
				}
			}

		});

		var maskId;
		var url = this.base + this.ajaxUrl;
		// console.info(data);

		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: url,
			data: {data: data},
			beforeSend: function(jqXHR) {
				maskId = $.mask({id: maskId, parent: '.content_wrapper', text: i18n.General.textSaving});
			},
			success: function(data, textStatus) {
				var callback = function() {
					var row, index, totalPublicExpenditureInputInput, totalPublicExpenditureEducationInput;

					var alertOpt = {
						// id: 'alert-' + new Date().getTime(),
						parent: 'body',
						title: i18n.General.textDismiss,
						text: i18n.General.textRecordUpdateSuccess, //data.msg,
						type: alertType.ok, // alertType.info or alertType.warn or alertType.error
						position: 'top',
						css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
						autoFadeOut: true
					};

					if (data.type === 0) {
						alertOpt.type = alertType.ok;
					} else if (data.type === -1) {
						alertOpt.type = alertType.error;
						alertOpt.text = i18n.General.textError;
					}

					$.alert(alertOpt);

					$('.table_row').each(function() {
						row = $(this);
						index = $(this).attr('index');
						areaId = row.find('[name=data\\[areaId\\]]');
						educationLevelId = row.find('#educationLevelId');
						valueInput = row.find('#amount');

						if (index != undefined) {
							row.attr('record-id', data[index]).removeAttr('index');
						}

						if (row.attr('record-id') > 0 && valueInput.val().toInt() == 0 && valueInput.val().toInt() == 0) {
							row.attr('record-id', 0);
						}
					});

//                    $('.btn_save').addClass('btn_disabled');
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	}
}
