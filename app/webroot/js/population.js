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
	population.init();

    $('#year_id').change(function(d, o){

        if($(this).val() !== '' && $(this).val() !== undefined){
            population.year = $(this).val();
            population.fetchData();
            $("#input_year").val(population.year);
        }
    });

    $('input[type="submit"]').click(function(event){
        event.preventDefault();
    });

    $('.btn_cancel').click(function(event){
        $('#viewLink').trigger('click');
    });

});

var population = {
    // properties
    year: 0,
    parentAreaIds: new Array(),
    currentAreaId: 0,
    isEditable: false,
    base: getRootURL() + 'Population/',
    id: '#population',
    deletedRecords: [],
    ajaxUrl: 'populationAjax',
    changeOption: 0,
    // methods
	init: function() {
        this.isEditable = false;
		this.addAreaSwitching();
		this.year = $('#year_id').find(":selected").text();
        if(population.changeOption<1){
            $("#PopulationAreaLevel0").trigger("change");
            this.addAreaSwitching();
        }

        $('.link_add').click(function() {
            if(population.parentAreaIds.length>0){
                population.addRow();
            }else{

                var alertOpt = {
                    // id: 'alert-' + new Date().getTime(),
                    parent: 'body',
                    title: i18n.General.textDismiss,
                    text: i18n.Population.textSelectCountry,//data.msg,
                    type: alertType.warn, // alertType.info or alertType.warn or alertType.error
                    position: 'top',
                    css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
                    autoFadeOut: true
                };

                $.alert(alertOpt);

            }
        });

	},
    show : function(id){
        $('#'+id).css("visibility", "visible");
    },
    hide : function(id){
        $('#'+id).css("visibility", "hidden");
    },
    addAreaSwitching : function(){
        $('select[name*="[area_level_"]').each(function(i, obj){
            $(obj).change(function (d, o){
                population.changeOption = 1;
                var TotalAreaLevel = $('select[name*="[area_level_"]').length;
                var isAreaLevelForInput = $(this).parent().parent().parent().attr('id');
                var currentSelctedOptionValue = parseInt($(this).find(':selected').val());
                var currentSelctedOptionTitle = $(this).find(':selected').html();
                var currentSelect = $(this).attr('name').replace('data[Population][area_level_','');
                currentSelect = currentSelect.replace(']','');
                currentSelect = parseInt(currentSelect);

                if(isAreaLevelForInput !== undefined && isAreaLevelForInput.match(/input/gi)){
                    isAreaLevelForInput = true;
                }else {
                    isAreaLevelForInput = false;
                }

                if(isAreaLevelForInput){
                    for (var i = currentSelect+1; i < TotalAreaLevel; i++) {
                        //disable the select element
                        $('select[name=data\\[Population\\]\\[area_level_'+i+'\\]][class=input_area_level_selector]').attr('disabled','disabled');
                        $('select[name=data\\[Population\\]\\[area_level_'+i+'\\]][class=input_area_level_selector]').parent().parent().find('.label').addClass('disabled');
                        $('select[name=data\\[Population\\]\\[area_level_'+i+'\\]][class=input_area_level_selector]').find('option').remove();
                    }
                }else {
                    for (var i = currentSelect+1; i < TotalAreaLevel; i++) {
                        //disable the select element
                        $('select[name=data\\[Population\\]\\[area_level_'+i+'\\]][class!=input_area_level_selector]').attr('disabled','disabled');
                        $('select[name=data\\[Population\\]\\[area_level_'+i+'\\]][class!=input_area_level_selector]').parent().parent().find('.label').addClass('disabled');
                        $('select[name=data\\[Population\\]\\[area_level_'+i+'\\]][class!=input_area_level_selector]').find('option').remove();
                    }
				}

                if(currentSelctedOptionValue > 0 ){
                    population.parentAreaIds[currentSelect] = currentSelctedOptionValue;//population.currentAreaId;
                }else{
                    population.parentAreaIds.splice(currentSelect, population.parentAreaIds.length - currentSelect);
                }

                population.currentAreaId = currentSelctedOptionValue;

                population.renderLegendText($(this).find('option[value="'+currentSelctedOptionValue+'"]').html());


                if(currentSelctedOptionValue >= 0 && !isAreaLevelForInput && population.parentAreaIds.length > 0 ) {
                    population.fetchData(this);
                }else{

                    $('#mainlist .table .table_body').html('');
                    $('.table_foot .cell_value').html(0);
                }

                if(((currentSelect === 0 && currentSelctedOptionValue > 0) || (currentSelect != 0 && currentSelctedOptionValue > 1))){
                    population.fetchChildren(this);
                }

                if( currentSelect === 0 && currentSelctedOptionValue === 0){
                    $('.table_body').hide();
                    //$('.table_body').show();
                }

            });
        });
    },
    fetchChildren :function (currentobj){
        var selected = $(currentobj).val();
        var maskId;
        var url =  population.base +'viewAreaChildren/'+selected;
        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            beforeSend: function (jqXHR) {
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
                            });
                            nextLabel.removeClass('disabled');
                            nextselect.find('option').remove();
                            nextselect.removeAttr('disabled');
                            nextselect.append(tpl);
                            
                    };
                    $.unmask({ id: maskId,callback: callback(data)});
            }
			/*
            error: function(jqXHR, textStatus, errorThrown) {
                $.unmask({ id: maskId});
                //maskId = $.mask({parent: '.content_wrapper', text:'Login Timeout.<br/>Redirection to login.'});
                if(jqXHR.status === 403){
                    window.location = getRootURL()+'/Population';;
                }
            }*/
        });
    },
    fetchData: function(currentObject){
        //isEditable = typeof isEditable !== 'undefined' ? isEditable : false;
        // init values
        var selectedValue = population.currentAreaId;
        var parentAreaIds = population.parentAreaIds[population.parentAreaIds.length - 1 ];
        // if object exist update with later value
        if(currentObject !== undefined){
            selectedValue = $(currentObject).val();
            parentAreaIds = population.parentAreaIds[population.parentAreaIds.length - 1 ];
        }
        var maskId;
        var url =  population.base +'viewData/'+this.year;

        //if(parseInt(selectedValue) > 0 ){
            url += '/'+selectedValue;
        //}

        if(typeof parentAreaIds !== "undefined" && parseInt(parentAreaIds) !== 0){
            url += '/'+parentAreaIds;
        }

        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            beforeSend: function (jqXHR) {
                maskId = $.mask({parent: '.content_wrapper'});

            },
            success: function (data, textStatus) {
                    var callback = function() {
                            var tpl = '';
                            var tableBody = $('#mainlist .table .table_body');

                            //tableBody.children().remove();
                            if((data !== 'false' && data !== false) && data.length > 0 ){
                                if(population.isEditable === true){
                                    tpl += population.renderRecordToHtmlTableRowForEdit(data);//'<option value="'+i+'">'+data[i]+'</option>';
                                    //$.each(data,function(i,o){
                                            //tpl += population.renderRecordToHtmlTableRowForEdit(data[i], ((i+1)%2 === 0)? true:false);//'<option value="'+i+'">'+data[i]+'</option>';
                                    //});
                                    if(data.length > 0){
                                        $('.btn_save').removeClass('btn_disabled');
                                    }else{
                                        $('.btn_save').addClass('btn_disabled');
                                    }
                                }else{
                                    tpl += population.renderRecordToHtmlTableRow(data);
                                }
                                tableBody.html(tpl);
                                population.computeTotal(tableBody.parent());
                                if(tableBody.is(':visible') === false){
                                    tableBody.show();
                                }
                                
                            }else{
                                tableBody.html('');
                                population.computeTotal(tableBody.parent());
                                tableBody.hide();

                            }
                            
                    };
                    $.unmask({ id: maskId,callback: callback});
            }
			/*
            error: function(jqXHR, textStatus, errorThrown) {
                $.unmask({ id: maskId});
                if(jqXHR.status === 403){
                    window.location = getRootURL()+'/Population';
                }

            }*/
        });

    },

    checkEdited: function() {
        var obj = $(population.id);
        var saveBtn = obj.find('.btn_save');
        var disabledClass = 'btn_disabled';
        var modified = false;
        if(obj.find('.table_row[record-id="0"]').length>0 || population.deletedRecords.length>0) {
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
    
    computeSubtotal: function(obj) {
        var row = $(obj).closest('.table_row');
        var male = row.find('#PopulationMale');
        var female = row.find('#PopulationFemale');
        
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
        population.computeTotal(table);
    },
    
    computeTotal: function(table) {
        var total = 0;
        table.find('.cell_total').each(function() {
            total += $(this).html().toInt();
        });
        table.find('.table_foot .cell_value').html(total);
    },
    
    addRow: function(id) {
        var obj = $('.table_body .table_row');//$('[programme-id="' + id + '"]');
        var rowNum = obj.length;
        var last = '.table_body .table_row:last';
        var lastRowAreaId = $(last).attr('area-id');
        var age = 3;
        var source = (typeof $(last).find('#PopulationSource').val() !== 'undefined')?$(last).find('#PopulationSource').val():'';
        var cell = '<div class="table_cell">';
        var wrapper = '<div class="input_wrapper">';
        var oldestAge = $(last).find('#PopulationAge');

        if(rowNum > 0 && $.isNumeric(oldestAge.val()) && oldestAge.val().toInt() > 0){
            age =  oldestAge.val().toInt() + 1;
        }else{
            age = '';
        }

        if(typeof lastRowAreaId !== 'undefined' && population.currentAreaId > 0){
            lastRowAreaId =  population.currentAreaId;
            
        }else{
            lastRowAreaId = population.parentAreaIds[population.parentAreaIds.length - 1];
        }

        var html = '<div class="table_row' + ((rowNum+1)%2===0 ? ' even' : ' odd') + '" record-id="0" area-id="'+ lastRowAreaId +'">';
        html += cell + wrapper + '<input id="PopulationSource" name="data[Population][source]" type="text" value="'+source+'"/></div></div>';
        html += cell + wrapper + '<input id="PopulationAge" name="data[Population][age]" type="text" defaultValue="' + age + '" value="' + age + '" maxlength="2" /></div></div>';
        html += cell + wrapper + '<input id="PopulationMale" name="data[Population][male]" type="text" defaultValue="0" value="0"  maxlength="10" /></div></div>';
        html += cell + wrapper + '<input id="PopulationFemale" name="data[Population][female]" type="text" defaultValue="0" value="0"  maxlength="10" /></div></div>';
        html += '<div class="table_cell cell_total cell_number">0</div>';
        html += '<div class="table_cell"><span class="icon_delete" title="'+i18n.General.textDelete+'"></span></div>';
        html += '</div>';
        
        $('.table_body').append(html);
        
		if($('.table_body').is(':visible') === false){
			$('.table_body').show();
		}

        var lastRow = $(last);
        lastRow.find('#PopulationMale').select();
        lastRow.find('input[id!="PopulationSource"]').keypress(function(evt) {
            return utility.integerCheck(evt);
        });
        lastRow.find('#PopulationAge').keyup(function() {
            population.checkEdited();
        });
        
        lastRow.find('#PopulationMale').keyup(function() {
            population.computeSubtotal(this);
            population.checkEdited();
        });
        
        lastRow.find('#PopulationFemale').keyup(function() {
            population.computeSubtotal(this);
            population.checkEdited();
        });
        lastRow.find('.icon_delete').click(function() {
            population.removeRow(this);
        });
        
        var saveBtn = $('.btn_save');
        var disabledClass = 'btn_disabled';
        if(saveBtn.hasClass(disabledClass)) {
            saveBtn.removeClass(disabledClass);
        }
    },
    
    removeRow: function(obj) {
        var row = $(obj).closest('.table_row');
        var table = row.closest('.table');
        var id = row.attr('record-id');

        if(id!==0) {
            population.deletedRecords.push(id);
        }
        row.remove();
        population.computeTotal(table);
        population.checkEdited();
        
        var totalRow = table.find('.table_row').length;
        if(totalRow < 1){
            //$('.btn_save').addClass('btn_disabled');
            $('.table_body').hide();
        }
    },

    
    save: function() {
        if($('.btn_save').hasClass('btn_disabled')) {
            return;
        }
        var yearId = population.year;
        var id, age, male, female, area_id, index=0;
        var obj, records, gradeId, categoryId, data = [];
        //$('[programme-id]').each(function() {
            //obj = $(this);
            $('.table_body .table_row').each(function() {
                id = $(this).attr('record-id');
                area_id = $(this).attr('area-id');
                source = $(this).find('#PopulationSource').val();
                age = $(this).find('#PopulationAge').val();
                male = $(this).find('#PopulationMale').val();
                female = $(this).find('#PopulationFemale').val();
//                console.info($.isNumeric( age.toInt()));
//                if(!age.isEmpty() && ($.isNumeric( age.toInt()) && age.toInt() !== 0)) {
                    index++;
                    data.push({
                        id: id,
                        source: source,
                        data_source: 0,
                        age: age,
                        male: male,
                        female: female,
                        year: yearId,
                        area_id: (typeof area_id !== 'undefined')? area_id : population.currentAreaId
                    });
//                }
                
                if(id===0) {
                    $(this).attr('index', index);
                }
            });
        //});
        var maskId;
        var url = this.base + this.ajaxUrl;

        var resultOfValidation = population.validData(data);
        console.info(resultOfValidation);

        if(resultOfValidation.error == true){

            var alertOpt = {
                // id: 'alert-' + new Date().getTime(),
                parent: 'body',
                title: i18n.General.textDismiss,
                text: (resultOfValidation.messages.length > 0)?resultOfValidation.messages.shift(): i18n.Population.textErrorOccurred,
                type: alertType.error, // alertType.info or alertType.warn or alertType.error
                position: 'top',
                css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
                autoFadeOut: true
            };

            $.alert(alertOpt);

        }else{

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: url,
                data: {data: data, deleted: population.deletedRecords},
                beforeSend: function (jqXHR) {
                    maskId = $.mask({id: maskId, parent: '.content_wrapper', text: i18n.General.Saving});
                },
                success: function (data, textStatus) {
                    var callback = function() {
                        var row, index, ageInput, maleInput, femaleInput;

                        var alertOpt = {
                            // id: 'alert-' + new Date().getTime(),
                            parent: 'body',
                            title: i18n.General.textDismiss,
                            text: i18n.General.textRecordUpdateSuccess,//data.msg,
                            type: alertType.ok, // alertType.info or alertType.warn or alertType.error
                            position: 'top',
                            css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
                            autoFadeOut: true
                        };

                        if(data.type === 0){
                            alertOpt.type = alertType.ok;
                        }else if(data.type === -1){
                            alertOpt.type = alertType.error;
                            alertOpt.text = i18n.General.textError;
                        }

                        $.alert(alertOpt);

                        $('.table_row[record-id="0"]').each(function() {
                            row = $(this);
                            index = row.attr('index');
                            sourceInput = row.find('#PopulationSource');
                            ageInput = row.find('#PopulationAge');
                            maleInput = row.find('#PopulationMale');
                            femaleInput = row.find('#PopulationFemale');

                            if(index!==undefined) {
                                row.attr('record-id', data[index]).removeAttr('index');
                            }
                            row.attr('record-id', data.insert.shift());

                            if(row.attr('record-id') > 0 && maleInput.val().toInt() === 0 && femaleInput.val().toInt() === 0) {
                                // row.attr('record-id', 0);
                            }

                            ageInput.attr('defaultValue', ageInput.val().toInt());
                            ageInput.val(ageInput.val().toInt());
                            maleInput.attr('defaultValue', maleInput.val().toInt());
                            maleInput.val(maleInput.val().toInt());
                            femaleInput.attr('defaultValue', femaleInput.val().toInt());
                            femaleInput.val(femaleInput.val().toInt());
                        });

                        population.deletedRecords = [];
                        $('.btn_save').addClass('btn_disabled');
                    };
                    $.unmask({id: maskId, callback: callback});
                }
            });
        }


    },

    validData: function(data) {
        var result = {
            error: true,
            messages: [],
            rows: []
        };

        var error = false;
        var rowsInError = [];
        var errorMessages = [];

        data.forEach(function(element, index, array){
            var errorOnElement = false;

            if(!$.isNumeric(element.age) ){ // Check that the age is a numeric

                if(errorOnElement === false) errorOnElement = true;
                errorMessages.push(i18n.Population.textEmptyAge);

            }else if(element.age.toInt() < 1) { // Check that the age is a numeric

                if(errorOnElement === false) errorOnElement = true;
                errorMessages.push(i18n.Population.textAgeMoreThanZero);

            }

            if(errorOnElement === true) {
                rowsInError.push(index);
            }

            error = error || errorOnElement;

        });
        result.error = error;
        result.messages = errorMessages;
        result.rows = rowsInError;

        return result;
    },

    // Render the legend of the data_section_group with selected options text
    renderLegendText: function (title) {
        var legend = $('#data_section_group').find('legend');
        var selectedOptionId, selectedOptionTitle = '' , legendText = '';
//        console.info(title.search(/^--$/i));
        if(population.parentAreaIds.length > 0  && title.search(/^--/i) >= 0 && title.search(/--$/i) >= 0){
            selectedOptionId = population.parentAreaIds[population.parentAreaIds.length - 1];
            selectedOptionTitle =  population.getLevelName(population.parentAreaIds); //$('#area_section_group').find('option[value="'+selectedOptionId+'"]').html();
        }else if(title.search(/^--/i) < 0 && title.search(/--$/i) < 0){
            selectedOptionTitle = title;
        }

        if(legend.html().search(/:/i) !== -1){
            legendText = legend.html().substring(0, legend.html().search(/:/i));
            if(selectedOptionTitle !== ''){
                legendText += ': '+selectedOptionTitle;
            }

        }else{
            legendText = legend.html();
            if(selectedOptionTitle !== ''){
                legendText += ': '+selectedOptionTitle;
            }
        }
        legend.html(legendText);

    },
    // Rending of htmls for view and edit
    renderRecordToHtmlTableRow: function (data){
        var html = '';

        $.each(data,function(i,o){
            console.log(data[i].data_source);
            html += '<div id="" class="table_row ' + (((i+1)%2 === 0)? 'even':'') + ' '+(data[i].data_source == 0?"":"green_text")+'" >';
            //html += '<div class="table_cell">'+data.area_level.toUpperCase()+'</div>';
            html += '<div class="table_cell">'+data[i].source+'</div>';
            html += '<div class="table_cell cell_number">'+data[i].age+'</div>';
            html += '<div class="table_cell cell_number">'+data[i].male+'</div>';
            html += '<div class="table_cell cell_number">'+data[i].female+'</div>';
            html += '<div class="table_cell cell_number cell_total">'+(parseInt(data[i]['male']) + parseInt(data[i]['female']))+'</div>';
            html += '</div>';
        });


        return html;
    },

    renderRecordToHtmlTableRowForEdit: function (data) {
        var html = '';

        $.each(data,function(i,o){
            html += '<div id="" class="table_row ' + (((i+1)%2 === 0)?  'even':'') + ' '+(data[i].data_source == 0?"":"green_text")+'" record-id="'+data[i].id+'" area-id="'+data[i].area_id+'">';
            //html += '<div class="table_cell">'+data.area_level.toUpperCase()+'</div>';

            html += '<div class="table_cell">';
            html += '                <div class="input_wrapper">';
            html += '                    <input id="PopulationSource" type="text" name="data[Population][source]" value="'+data[i].source+'"/>';
            html += '                </div>';
            html += '            </div>';
            html += '<div class="table_cell">';
            html += '                <div class="input_wrapper">';
            html += '                    <input id="PopulationAge" type="text" name="data[Population][age]" value="'+data[i].age+'" maxlength="2" autocomplete="false" onkeypress="return utility.integerCheck(event)" onkeyup="population.checkEdited()" />';
            html += '                </div>';
            html += '            </div>';
            html += '<div class="table_cell">';
            html += '                <div class="input_wrapper">';
            html += '                    <input id="PopulationMale" type="text" name="data[Population][male]" value="'+data[i].male+'" maxlength="10" onkeypress="return utility.integerCheck(event)" onkeyup="population.computeSubtotal(this); population.checkEdited()"/>';
            html += '                </div>';
            html += '            </div>';
            html += '<div class="table_cell">';
            html += '                <div class="input_wrapper">';
            html += '                    <input id="PopulationFemale" type="text" name="data[Population][female]" value="'+data[i].female+'" maxlength="10" onkeypress="return utility.integerCheck(event)" onkeyup="population.computeSubtotal(this); population.checkEdited()"/>';
            html += '                </div>';
            html += '            </div>';
            html += '<div class="table_cell cell_total cell_number">';
            html +=             parseInt(data[i]['male']) + parseInt(data[i]['female']);
            html += '            </div>';
            html += '<div class="table_cell"><span class="icon_delete" title="'+i18n.General.textDelete+'" onclick="population.removeRow(this)"></span></div>';
            //html += '</div>';
            html += '</div>';
        });

        return html;

    },

    getLevelName: function (parentAreaIds) {
        selectedOptionId = parentAreaIds[parentAreaIds.length - 1];
        selectedOptionTitle = $('#area_section_group').find('option[value="'+selectedOptionId+'"]').html();
        if(selectedOptionTitle !== null){
            return selectedOptionTitle;
        }

        // console.info(parentAreaIds.pop());
    }
};

