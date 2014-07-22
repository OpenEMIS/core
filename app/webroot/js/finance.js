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

    $('#year_id').change(function(d, o){
        Finance.year = $(this).val();
        Finance.fetchData();
        Finance.fetchGNP();
        $("#input_year").val(Finance.year);
    });

    $('#areapicker.areapicker select').first().change(function(event) {
        Finance.fetchGNP();
    });
	
	$('#areapicker.areapicker').on('change', 'select', function(){
		if($(this).val() != '' && $(this).val() > 0){
			currentAreaId = $(this).val();
		}else{
			var parentAreaSelect = $(this).parents('.form-group').prev('.form-group').find('select.form-control');
			if(parentAreaSelect.length > 0){
				var parentAreaId = parentAreaSelect.val();
				currentAreaId = parentAreaId;
			}else{
				currentAreaId = 0;
			}
		}
		
		if($('#finance').hasClass('edit')){
			Finance.fetchDataByArea(currentAreaId, 'edit');
		}else{
			Finance.fetchDataByArea(currentAreaId, '');
		}
		
		$('a.withLatestAreaId').each(function(){
			var newHref = $(this).attr('href').replace(/(\/\d{4}\/)\d*/, '$1'+currentAreaId);
			$(this).attr('href', newHref);
		});
		
		$('a.btn_cancel').each(function(){
			var newCancelHref = $(this).attr('href').replace(/(\/\d{4}\/)\d*/, '$1'+currentAreaId);
			$(this).attr('href', newCancelHref);
		});
		
		$('form#FinanceEditForm').each(function(){
			var newAction = $(this).attr('action').replace(/(\/\d{4}\/)\d*/, '$1'+currentAreaId);
			$(this).attr('action', newAction);
		});
	});
	
	$('select#financeYear').on('change', function(){
		if($('#finance').hasClass('edit')){
			location.href = Finance.base + 'edit/' + $(this).val() + '/' + currentAreaId;
		}else{
			location.href = Finance.base + 'index/' + $(this).val() + '/' + currentAreaId;
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
    ajaxUrl: 'financeAjax',
    numAreaSelectors: 0,
    changeOption: 0,

	// methods
    init: function() {
        //this.isEditable = false;
        this.changeView();
//        this.addAreaSwitching();
//        this.year = $('#year_id').val();
//        if(Finance.changeOption<1){
//            $("#FinanceAreaLevel0").trigger("change");
//            this.addAreaSwitching();
//        }
//        this.numAreaSelectors = $('fieldset#area_section_group div.row').length;
    },
	show : function(id){
		$('#'+id).css("visibility", "visible");
	},
	hide : function(id){
		$('#'+id).css("visibility", "hidden");
	},
    TotalPublicExpenditureBacktoList : function(){
        window.location = getRootURL()+"Finance";
    },
    TotalPublicExpenditurePerEducationLevelBacktoList : function(){
        window.location = getRootURL()+"Finance/financePerEducationLevel";
    },
	changeView : function() {
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
	addAreaSwitching : function(){

    	$('select[name*="[area_level_"]').each(function(i, obj){
            $(obj).change(function (d, o){
                Finance.changeOption = 1;
                //console.info('trigger');
                //console.info(parseInt($(this).find(':selected').val()));

                var TotalAreaLevel = $('select[name*="[area_level_"]').length;
                var isAreaLevelForInput = $(this).parent().parent().parent().attr('id'); 
                var currentSelctedOptionValue = parseInt($(this).find(':selected').val());
                var currentSelect = $(this).attr('name').replace('data[Finance][area_level_','');
                currentSelect = currentSelect.replace(']','');
                currentSelect = parseInt(currentSelect);

                if(isAreaLevelForInput !== undefined && isAreaLevelForInput.match(/input/gi)){
                    isAreaLevelForInput = true;
                }else {
                    isAreaLevelForInput = false
                }

                // console.info(currentSelect);
                //console.info(currentSelctedOptionValue);
                if(isAreaLevelForInput){
                    //console.info(areaLevelForInput);
                    for (var i = currentSelect+1; i < TotalAreaLevel; i++) {
                        //disable the select element
                        $('select[name=data\\[Finance\\]\\[area_level_'+i+'\\]][class=input_area_level_selector]').attr('disabled','disabled');
                        $('select[name=data\\[Finance\\]\\[area_level_'+i+'\\]][class=input_area_level_selector]').parent().parent().find('.label').addClass('disabled');
                        
                        $('select[name=data\\[Finance\\]\\[area_level_'+i+'\\]][class=input_area_level_selector]').find('option').remove();
                    };
                }else {
                    for (var i = currentSelect+1; i < TotalAreaLevel; i++) {
                        //disable the select element
                        $('select[name=data\\[Finance\\]\\[area_level_'+i+'\\]][class!=input_area_level_selector]').attr('disabled','disabled');
                        $('select[name=data\\[Finance\\]\\[area_level_'+i+'\\]][class!=input_area_level_selector]').parent().parent().find('.label').addClass('disabled');
                        
                        $('select[name=data\\[Finance\\]\\[area_level_'+i+'\\]][class!=input_area_level_selector]').find('option').remove();
                    };
                }

                // console.info('currentSelect: ' + currentSelect);
                // console.info('currentSelctedOptionValue: ' + currentSelctedOptionValue);

                if(currentSelctedOptionValue > 0 ){
                    Finance.parentAreaIds[currentSelect] = currentSelctedOptionValue;
                }else{
                    Finance.parentAreaIds.splice(currentSelect, Finance.parentAreaIds.length - currentSelect);
                }
                
                Finance.currentAreaId = currentSelctedOptionValue;
                Finance.renderLegendText(currentSelect, currentSelctedOptionValue);

                if(currentSelctedOptionValue >= 0 && !isAreaLevelForInput && Finance.parentAreaIds.length > 0 ) {
                    Finance.fetchData(this);
                    Finance.fetchGNP();
                }else{
                    $('#parentlist .table .table_body').html('');
                    $('#mainlist .table .table_body').html('');
                }

                if(((currentSelect == 0 && currentSelctedOptionValue > 0) || (currentSelect != 0 && currentSelctedOptionValue > 1))){
                    Finance.fetchChildren(this);
                }

                if( currentSelect == 0 && currentSelctedOptionValue == 0){
                    $('.table_body').hide();
                    //$('.table_body').show();
                }

            });
        });

    },
    fetchChildren :function (currentobj){
        var selected = $(currentobj).val();
        var maskId;
        var url =  Finance.base +'viewAreaChildren/'+selected;

        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            beforeSend: function (jqXHR) {
                //maskId = $.mask({parent: '#site',text:'loading'});
                maskId = $.mask({parent: '.content_wrapper'});
            },
            success: function (data, textStatus) {
                //console.log(data)
            
                var callback = function(data) {
                    tpl = '';
                    var nextselect = $(currentobj).parent().parent().next().find('select');
                    var nextLabel = nextselect.parent().parent().find('.label');
                    //data[1] += nextLabel.text().toUpperCase(); // Add "ALL <text>" option in the select element
                    $.each(data,function(i,o){
                        tpl += '<option value="'+i+'">'+data[i]+'</option>';
                    })
                    nextLabel.removeClass('disabled');
                    nextselect.find('option').remove();
                    nextselect.removeAttr('disabled');
                    nextselect.append(tpl);
                            
                };
                $.unmask({ id: maskId,callback: callback(data)});
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
    fetchGNP: function() {
        var currentYear = $('select#financeYear').val();
        var countryAreaId = $('#areapicker.areapicker select').first().val();
        var maskId;
        var url = getRootURL()+'Finance/viewGNP/'+currentYear+'/'+countryAreaId;

        if (countryAreaId > 0) {
            $.ajax({
                type: 'GET',
                dataType: 'json',
                url: url,
                success: function (data, textStatus) {
                    // update gnp on view
                     if ($('#gnp').is('input')) {
                        $('#gnp').val(data.gross_national_product);
                     } else {
                        (data.gross_national_product == null) ? grossNationalProduct = i18n.Finance.textNoData : grossNationalProduct = data.gross_national_product;
                        $('#gnp').text(grossNationalProduct);
                     }
                }
            });
        } else {

            if ($('#gnp').is('input')) {
                $('#gnp').val('');    
            } else {
                $('#gnp').text(i18n.Finance.textNoData);
            }
        }

    },
	
	fetchDataByArea: function(areaId, mode) {
		var year = $('select#financeYear').val();

		var url;
		if (mode === 'edit') {
			url = Finance.base + 'loadForm/' + year + '/' + areaId;
		} else {
			url = Finance.base + 'loadData/' + year + '/' + areaId;
		}

		$.ajax({
			type: 'GET',
			dataType: 'html',
			url: url,
			success: function(data, textStatus) {
				var replaceHolder = $('.replaceHolder');

				if (data.length > 0) {
					if (replaceHolder.length > 0) {
						replaceHolder.html(data);
					}
				}
				
				Finance.fetchGNP();
			}
		});
	},
	
    fetchData: function(currentObject){

        // init values
        var selectedValue = Finance.currentAreaId;
        var parentAreaIds = Finance.parentAreaIds[Finance.parentAreaIds.length - 1 ];
        // if object exist update with later value
        if(currentObject !== undefined){
            selectedValue = $(currentObject).val();
            parentAreaIds = Finance.parentAreaIds[Finance.parentAreaIds.length - 1 ];
            // parentAreaIds = selectedValue;
        }

        var maskId;
        var url =  Finance.base +'viewData/'+this.year;

        //if(parseInt(selectedValue) > 0 ){
            url += '/'+selectedValue;
        //}

        if(typeof parentAreaIds !== "undefined" && parseInt(parentAreaIds) !== 0){
            url += '/'+parentAreaIds;
        }

        // console.info(url);

        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            beforeSend: function (jqXHR) {
               // maskId = $.mask({parent: '#site', text:'loading'});
		      maskId = $.mask({parent: '.content_wrapper'});
            },
            success: function (data, textStatus) {

                    var callback = function(data) {

                        var parentTpl = '';
                        var parentTableBody = $('#parentlist .table .table_body');
                        parentTableBody.children().remove();

                        var tpl = '';
                        var tableBody = $('#mainlist .table .table_body');
                        tableBody.children().remove();
                        if (data !== 'false' && data !== false) {

                            if(Finance.isEditable === true){
                                parentTpl += Finance.renderRecordToHtmlTableRowForEdit(data['parent']);
                                tpl += Finance.renderRecordToHtmlTableRowForEdit(data['children']); //'<option value="'+i+'">'+data[i]+'</option>';
                                /*$.each(data,function(i,o){
                                    tpl += Finance.renderRecordToHtmlTableRowForEdit(data[i]);//'<option value="'+i+'">'+data[i]+'</option>';
                                });
                                */
//                                if(data.length > 0){
//                                    $('.btn_save').removeClass('btn_disabled');
//                                }else{
//                                    $('.btn_save').addClass('btn_disabled');
//                                }
                                /*if (data.length <= 0) {
                                    $('.btn_save').addClass('btn_disabled');
                                }*/
                            }else{
                                // $.each(data,function(i,o){
                                    if (data['parent'] != undefined) {
                                        parentTpl += Finance.renderRecordToHtmlTableRow(data['parent']);    
                                    }
                                    
                                    if (data['children'] != undefined) {
                                        tpl += Finance.renderRecordToHtmlTableRow(data['children']);//'<option value="'+i+'">'+data[i]+'</option>';
                                    }
                                // });
                            }
                            // parentTableBody.append(parentTpl);
                            // tableBody.append(tpl);

                            parentTableBody.html(parentTpl);
                            tableBody.html(tpl);
                            if(tableBody.is(':visible') === false || parentTableBody.is(':visible') === false){
                                tableBody.show();
                                parentTableBody.show();
                            }
                        } else {
                            parentTableBody.html('');
                            parentTableBody.hide('');
                            tableBody.html('');
                            tableBody.hide();
                        }
                        
                };
                $.unmask({ id: maskId,callback: callback(data)});
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
        if(obj.find('.table_row[record-id="0"]').length>0) {
            modified = true;
        } else {
            obj.find('.table_body input').each(function() {
                if($(this).attr('defaultValue') != this.value) {
                    modified = true;
                    return false;
                }
            });
        }

        if(modified) {
            if(saveBtn.hasClass(disabledClass)) {
                saveBtn.removeClass(disabledClass);
            }
        } else {
            if(!saveBtn.hasClass(disabledClass)) {
                saveBtn.addClass(disabledClass);
            }
        }
    },
    // Render the selection box to switch between Total Public Expenditure and Total Public Expenditure Per Education
    renderViewSelectBox: function() {

    },
    // Render the legend of the data_section_group with selected options text 
    renderLegendText: function (currentSelect, selectedOptionId, isChildren) {
        var parentLegend = $('legend#parent_level');
        var childrenLegend = $('legend#children_level');
        var childrenSelect = 1;
        var parentSelect = currentSelect;
        var selectedOptionId, parentLegendText, childrenLegendText = '';
        if (currentSelect != 0 && selectedOptionId == 0) {            
            parentSelect = currentSelect-1;
        } else if (currentSelect == 0 && selectedOptionId == 0) {
            parentSelect = childrenSelect = 0;            
        } else {
            if (currentSelect >= (this.numAreaSelectors - 1)) {
                parentSelect = (this.numAreaSelectors - 1) -1;
            }
        }
        childrenSelect = parentSelect + 1;

        if(Finance.parentAreaIds.length >= 0 ){
            selectedOptionId = Finance.parentAreaIds[Finance.parentAreaIds.length - 1];
            parentLegendText = $('select[name=data\\[Finance\\]\\[area_level_'+parentSelect+'\\]]').parent().parent().find('.label').html();
            childrenLegendText = $('select[name=data\\[Finance\\]\\[area_level_'+childrenSelect+'\\]]').parent().parent().find('.label').html();
        }
        parentLegend.html(parentLegendText);
        childrenLegend.html(i18n.Areas.AreaLevelText);
    },
    // Rending of htmls for view and edit
    renderRecordToHtmlTableRow: function (data){

        var html = '';

        $.each(data, function(i,o) {
            html += '<div id="" class="table_row ' + (((i+1)%2 === 0)? 'even':'') + '">';
            html += '<div class="table_cell">'+data[i].name+'</div>';
            html += '<div class="table_cell cell_amount">'+data[i].total_public_expenditure+'</div>';
            html += '<div class="table_cell cell_amount">'+data[i].total_public_expenditure_education+'</div>';
            html += '</div>';
        });
        
        return html;
    },

    renderRecordToHtmlTableRowForEdit: function (data) {

        var html = '';

        $.each(data,function(i,o){
            // html += '<div id="" class="table_row" record-id="'+data[i].id+'">';
            html += '<div id="" class="table_row ' + (((i+1)%2 === 0)?  'even':'') + '" record-id="'+data[i].id+'" area-id="'+data[i].area_id+'">';
            
            html += '<div class="table_cell">';
            html += '   <input type="hidden" name="data[Finance][areaId]" value="'+data[i].area_id+'" />';
            html +=     data[i].name;
            html += '</div>';
            html += '<div class="table_cell">';
            html += '   <div class="input_wrapper">';
            html += '       <input type="text" id="totalPublicExpenditure" name="data[Finance][totalPublicExpenditure]" value="'+data[i].total_public_expenditure+'" maxlength="10" autocomplete="false" onkeypress="return utility.integerCheck(event)" onkeyup="Finance.checkEdited()" />';
            html += '   </div>';
            html += '</div>';
            html += '<div class="table_cell">';
            html += '   <div class="input_wrapper">';
            html += '       <input type="text" id="totalPublicExpenditureEducation" name="data[Finance][totalPublicExpenditureEducation]" value="'+data[i].total_public_expenditure_education+'" onkeypress="return utility.integerCheck(event)" onkeyup="Finance.checkEdited()"/>';
            html += '   </div>';
            html += '</div>';
            html += '</div>';
        });
        return html;

    },
    save: function () {
        if($('.btn_save').hasClass('btn_disabled')) {
            return;
        }

        var alertOpt = {
            // id: 'alert-' + new Date().getTime(),
            parent: 'body',
            title: i18n.General.textDismiss,
            text: 'Error has occurred.',// i18n.Finance.textNoGNP,
            type: alertType.error, // alertType.info or alertType.warn or alertType.error
            position: 'top',
            css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
            autoFadeOut: true
        };

        if ($('#gnp').val() == null || $('#gnp').val() == "") {
            // alert('GNP value is required.');
            alertOpt.text = i18n.Finance.textNoGNP;
            $.alert(alertOpt);

        } else {
            var yearId = Finance.year;
            var id, areaId, totalPublicExpenditure, totalPublicExpenditureEducation, index=0;
            var data = [];
            var gnp = $('#gnp').val();
            var errorMessage = '';

            $('.table_body .table_row').each(function() {
                id = $(this).attr('record-id');
                areaId = $(this).find('[name=data\\[Finance\\]\\[areaId\\]]').val();
                totalPublicExpenditure = $(this).find('#totalPublicExpenditure').val();
                totalPublicExpenditureEducation = $(this).find('#totalPublicExpenditureEducation').val();

                if((!totalPublicExpenditure.isEmpty() && totalPublicExpenditureEducation.isEmpty()) || (totalPublicExpenditure.isEmpty() && !totalPublicExpenditureEducation.isEmpty())){
                    errorMessage = 'Both "Total Public Expenditure" and "Total Public Expenditure for Education" are required.';
                    return false;
                }else{
                    index++;
                    data.push({
                        id: id,
                        index: index,
                        gross_national_product: gnp,
                        total_public_expenditure: totalPublicExpenditure,
                        total_public_expenditure_education: totalPublicExpenditureEducation,
                        year: yearId,
                        area_id: areaId
                        // area_id: Finance.currentAreaId
                    });

                    if(id==0) {
                        $(this).attr('index', index);
                    }
                }
                
            });

            if(errorMessage.isEmpty()){

                var maskId;
                var url = this.base + this.ajaxUrl;

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: url,
                    data: {data: data},
                    beforeSend: function (jqXHR) {
                        maskId = $.mask({id: maskId, text: 'Saving...'});
                    },
                    success: function (data, textStatus) {
                        var callback = function() {
                            var row, index, totalPublicExpenditureInputInput, totalPublicExpenditureEducationInput;

                            alertOpt.type = alertType.ok;
                            alertOpt.text = i18n.General.textRecordUpdateSuccess;

                            $.alert(alertOpt);

                            $('.table_row').each(function() {
                                row = $(this);
                                index = $(this).attr('index');
                                areaId = row.find('[name=data\\[Finance\\]\\[areaId\\]]');
                                totalPublicExpenditureInput = row.find('#totalPublicExpenditure');
                                totalPublicExpenditureEducationInput = row.find('#totalPublicExpenditureEducation');

                                if(row.attr('record-id') > 0 && totalPublicExpenditureInput.val().toInt() == 0 && totalPublicExpenditureEducationInput.val().toInt() == 0) {
                                    row.attr('record-id', 0);
                                }
                            });

//                            $('.btn_save').addClass('btn_disabled');
                        };
                        $.unmask({id: maskId, callback: callback});
                    }
                });

            }else{
                alertOpt.text = errorMessage;
                $.alert(alertOpt);
            }

        }

        
    }
}
