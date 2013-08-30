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
	areas.init();

    $('input[type="submit"][id!="area_level_submit"]').click(function(event){
        event.preventDefault();
    });

});

var areas = {
    // properties
    parentAreaIds: [],
    area_levels: [],
    currentAreaId: 1,
    addParent: 0,
    isEditable: false,
    baseURL: getRootURL() + 'Areas/',
	extraParam : '',
    id: '#area',
    deletedRecords: [],
    ajaxUrl: 'areaAjax',
    // methods
	init: function() {
        this.isEditable = false;
		this.addAreaSwitching();

        $('.link_add').click(function() {
            areas.addRow();
        });

        $('.link_add_area_level').click(function(event) {
            event.preventDefault();
            areas.addRowforAreaLevel();
        });

	},
    hide: function(id){
        $('#'+id).hide();
    },
    show: function(id){
        $('#'+id).show();
    },
    addAreaSwitching : function(){
        var saveBtn = $('.btn_save');
        var myAreaArr = ["area_level","area_education_level"];
        for (var i = 0; i < myAreaArr.length; i++) {
            $('select[name*="['+myAreaArr[i]+'_"]').each(function(i, obj){
                $(obj).change(function (d, o){
                    var TotalAreaLevel = $('select[name*="['+myAreaArr[i]+'_"]').length;
                    var isAreaLevelForInput = $(this).parent().parent().parent().attr('id');
                    var currentSelctedOptionValue = parseInt($(this).find(':selected').val());
                    var currentSelctedOptionTitle = $(this).find(':selected').html();
                    var Model = $(this).closest('form').attr('model');

                    var currentSelect = $(this).attr('name').replace('data['+Model+']['+myAreaArr[i]+'_','');

                    currentSelect = currentSelect.replace(']','');
                    currentSelect = parseInt(currentSelect);

                    if(isAreaLevelForInput !== undefined && isAreaLevelForInput.match(/input/gi)){
                        isAreaLevelForInput = true;
                    }else {
                        isAreaLevelForInput = false;
                    }

                    if(currentSelctedOptionValue > 0 ){
                        areas.parentAreaIds[currentSelect] = currentSelctedOptionValue;//areas.currentAreaId;
                    }else{
                        areas.parentAreaIds.splice(currentSelect, areas.parentAreaIds.length - currentSelect);
                    }

                    areas.currentAreaId = currentSelctedOptionValue;

                    areas.renderLegendText($(this).find('option[value="'+currentSelctedOptionValue+'"]').html()); //render legend Label

                    if(currentSelctedOptionValue >= 0 && !isAreaLevelForInput && areas.parentAreaIds.length > 0 ) {
                        areas.fetchData(this);
                    }else if(!isAreaLevelForInput){
                        areas.fetchData();
                        $('.table_body').html('');
                        $('.table_foot .cell_value').html(0);
                    }else{
                        $('.table_view').html('');
                        $('.table_foot .cell_value').html(0);
                    }

                    if(((currentSelect === 0 && currentSelctedOptionValue > 0) || (currentSelect !== 0 && currentSelctedOptionValue > 1))){
                        jsForm.getAreaChildren(this);
                    }else{
                        var myselect = $(this).parent().parent().find('select');
                        var myLabel = myselect.parent().parent().find('.label');
                        myLabel.show();
                        var nextSelect = myselect.parent().parent().next().find('select');
                        //var nextRow = myselect.parent().parent().next('.row');

                        do{
                            nextSelect.parent().parent().hide();
                            nextSelect.find('option').remove();
                            nextSelect = nextSelect.parent().parent().next().find('select');
                        }while(nextSelect.length>0)
                        myLabel.html(i18n.Areas.AreaLevelText);
                    }

                    if( currentSelect === 0 && currentSelctedOptionValue === 0){
                        $('.table_view').hide();
                        if(!saveBtn.hasClass('btn_disabled')){
                            saveBtn.addClass('btn_disabled');
                        }
                        //$('.table_body').show();
                    }
                });
            });
        }
    },
    fetchData: function(currentObject){
        // init values
        var selectedValue = areas.currentAreaId;
        var hasOptions = 0;
        var edutype = "education";
        $('#area_section_group').each(function(index) {
            var nextrow = $(this).find('.row');
            var myselect = nextrow.find('select');
            do{
                lastOptionChosen = 0;
                if(nextrow.is(":visible")){
                    if(myselect.val()>0){
                        selectedValue = myselect.val();
                    }
                    hasOptions = 1;
                }
                myselect = myselect.parent().parent().next().find('select');
                nextrow = myselect.parent().parent();
            }while(myselect.length>0)
        });
        var parentAreaIds = areas.parentAreaIds[areas.parentAreaIds.length - 1 ];
        var saveBtn = $('.btn_save');
        // if object exist update with later value
        if(currentObject !== undefined){
            //selectedValue = $(currentObject).val();
            parentAreaIds = areas.parentAreaIds[areas.parentAreaIds.length - 1 ];
        }
        var maskId;
        var url =  areas.baseURL +'viewData/' + selectedValue;
		var Model = ($('form').attr('model'));
		areas.extraParam = Model;
        if(areas.extraParam=="Area"){
           edutype = "area";
        }
        $.when(
            $.ajax({
                type: "GET",
                url: getRootURL() +'/Areas/checkLowestLevel/'+selectedValue+'/'+edutype,
                success: function (data) {
                    if(data=='true'){
                        $('#addarea').hide();
                    }else{
                        $('#addarea').show();
                    }
                }
            })
        ).then(function() {
            $.ajax({
                type: 'GET',
                dataType: 'json',
                url: url+'/'+areas.extraParam,
                beforeSend: function (jqXHR) {
                    maskId = $.mask({parent: '#data_section_group'});
                },
                success: function (data, textStatus) {
                    var callback = function() {
                        var tpl = '';
                        var tableBody = $('.table .table_body');
                        if((data !== 'false' && data !== false) /*&& data.length > 0 */){
                            if(areas.isEditable === true){
                                if('area_levels' in data){
                                    areas.area_levels =  data.area_levels;
                                }
                                tableBody = $('.table_view');
                                // tableBody = $('#mainlist .table_view');
                                tpl += areas.renderRecordToHtmlTableRowForEdit(data.data);//'<option value="'+i+'">'+data[i]+'</option>';
                                // console.info(tpl);
                                //$.each(data,function(i,o){
                                //tpl += areas.renderRecordToHtmlTableRowForEdit(data[i], ((i+1)%2 === 0)? true:false);//'<option value="'+i+'">'+data[i]+'</option>';
                                //});
                                if(data.length > 0){
                                    $('.btn_save').removeClass('btn_disabled');
                                }else{
                                    $('.btn_save').addClass('btn_disabled');
                                }
                            }else{
                                tpl += areas.renderRecordToHtmlTableRow(data.data);
                            }
                            tableBody.html(tpl);
                            if(tableBody.is(':visible') === false){
                                tableBody.show();
                            }
                            if(saveBtn.hasClass('btn_disabled')){
                                saveBtn.removeClass('btn_disabled');
                            }
                            jsList.init('.table_view');
                        }else{
                            tableBody.html('');
                            tableBody.hide();
                            if(!saveBtn.hasClass('btn_disabled')){
                                saveBtn.addClass('btn_disabled');
                            }
                        }
                        if(hasOptions==0){
                            areas.addParent = 1;
                            areas.addRow();
                        }

                    };
                    $.unmask({ id: maskId,callback: callback});
                }
            });
        });
    },

    checkEdited: function() {
        var obj = $(areas.id);
        var saveBtn = obj.find('.btn_save');
        var disabledClass = 'btn_disabled';
        var modified = false;
        if(obj.find('.table_row[record-id="0"]').length>0 || areas.deletedRecords.length>0) {
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
    
    reorder: function(obj) {
        jsList.doSort(obj, {callback : function() {
                jsList.init();
            }
        });
    },
    
    addRow: function(id) {
        var ul = $('.table_view');
        var li = ul.find('li');//$('.table_view li');//$('[programme-id="' + id + '"]');
        var newRowObject = {
            indexCount: 0,
            id: 0,
            code: '',
            name: '',
            area_id: 0,
            visible: 1,
            level_name: '',
            isNew: true
        };
        var rowNum = li.length;
        var last = '.table_view li:last';

        newRowObject.indexCount = (rowNum === 0)? 0: rowNum;//parseInt($(last).attr('data-id')) + 1;
        newRowObject.code = '';
        newRowObject.name = '';
        newRowObject.id = 0;
        newRowObject.order = parseInt($(last).find('#order').val()) + 1;

        if(typeof newRowObject.area_id !== 'undefined' && areas.currentAreaId > 0){
            newRowObject.area_id =  areas.currentAreaId;
            
        }else{
            newRowObject.area_id = areas.parentAreaIds[areas.parentAreaIds.length - 1];
        }

        var renderedHtmlRow = areas.renderRecordToHtmlTableRowForEdit(new Array(newRowObject));
        // console.info(renderedHtmlRow);
        if(rowNum > 0 ){
            li.parent().append(renderedHtmlRow);
        }else{
            ul.html(renderedHtmlRow);
        }

        jsList.init(li.parent());
        
        if($('.table_view').is(':visible') === false){
            $('.table_view').show();
        }

        var lastRow = $(last);
        
        var saveBtn = $('.btn_save');
        var disabledClass = 'btn_disabled';
        if(saveBtn.hasClass(disabledClass)) {
            saveBtn.removeClass(disabledClass);
        }
    },
    
    addRowforAreaLevel: function(id) {
        var ul = $('.table_view');
        var li = $('.table_view li');//$('[programme-id="' + id + '"]');
        var newRowObject = {
            indexCount: 0,
            id: 0,
            name: '',
            order: 0,
            isNew: true
        };
        var rowNum = li.length;

        var last = '.table_view li:last';
        newRowObject.indexCount = (rowNum === 0)? 0: rowNum;//$(last).attr('data-id').toInt();
        newRowObject.name = '';
        newRowObject.id = 0;
        newRowObject.order = parseInt($(last).find('#order').val()) + 1;

        var renderedHtmlRow = areas.renderRecordToHtmlTableRowForEditAreaLevels(new Array(newRowObject));
        ul.append(renderedHtmlRow);

        jsList.init('.table_view');
        
        var saveBtn = $('.btn_save');
        var disabledClass = 'btn_disabled';
        if(saveBtn.hasClass(disabledClass)) {
            saveBtn.removeClass(disabledClass);
        }
    },
    
    remove: function(obj) {

        var li = $(obj).closest('li');
        var ul = li.closest('.table_view');
        var id = parseInt(li.find('#id').val());

        li.remove();
        areas.checkEdited();
        ul.children().each(function(i,o){
            var current_li = $(o);
            current_li.find('input').each(function(innerI, innerO){
                var replaceNameAttr = $(innerO).attr('name').replace(/\[[0-9]+\]/, '['+i+']');
                $(innerO).attr('name', replaceNameAttr);
            });
            current_li.attr('data-id', i+1);
            current_li.find('#order').attr('value', i+1);
        });
        
        jsList.init(ul);

        var totalRow = ul.find('li').length;
        if(totalRow < 1){
            //$('.btn_save').addClass('btn_disabled');
            //ul.hide();
        }
    },

    
    save: function() {
		var Model = ($('form').attr('model'));
        if($('.btn_save').hasClass('btn_disabled')) {
            return;
        }
        var id, visible, index=0;
        var data = [];
        $('.table_view').children().each(function(i,o) {

            id = parseInt($(this).find('#id').val());
            area_level_id = parseInt($(this).find('.cell_level select option:selected').val());
            code = $(this).find('.cell_code .input_wrapper input').val();
            var name = $(this).find('.cell_name .input_wrapper input').val();
            var order = parseInt($(this).find('#order').val());
            visible = parseInt($(this).find('#PostVisible:checked').val());
            

            index++;
			
			dataval = {
                id: id,
                code: code,
                name: name,
                order: order,
                visible: (visible === 1? visible: 0),
                parent_id: (areas.parentAreaIds.length < 1)? -1: areas.parentAreaIds[areas.parentAreaIds.length-1]
                // area_id: (typeof area_id !== 'undefined')? area_id : areaarea.currentAreaId
            }
			
			if(Model=='AreaEducation'){
				dataval.area_education_level_id = area_level_id;
			}else{
				dataval.area_level_id = area_level_id;
			}
			
            data.push(dataval);
            
            if(id===0) {
                $(this).attr('index', index);
            }
        });
        
        var maskId;
		
        var url = this.baseURL + this.ajaxUrl + '/' +Model;
        
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: url,
            data: {data: data, deleted: areas.deletedRecords},
            beforeSend: function (jqXHR) {
                maskId = $.mask({id: maskId, parent: '.content_wrapper', text: i18n.General.textSaving});
            },
            success: function (data, textStatus) {

                var callback = function() {
                    var alertOpt = {
                        // id: 'alert-' + new Date().getTime(),
                        parent: 'body',
                        title: i18n.Areas.titleDismiss,
                        text: data.msg,
                        type: alertType.ok, // alertType.info or alertType.warn or alertType.error
                        position: 'top',
                        css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
                        autoFadeOut: true
                    };

                    if(data.type === 0){
                        alertOpt.type = alertType.ok;

                    }else if(data.type === -1){
                        alertOpt.type = alertType.error;
                    }

                    $.alert(alertOpt);

                    var newRows = $('.table_view > li.new_row');
                    $.each(data['data']['new'], function(i,object){
                        var row = newRows.find('#order[value="'+i+'"]').parent();
                        row.find('#id').attr('value', object);
                        row.find('#order[value="'+i+'"]').parent().find('.cell_order .icon_cross').remove();
                        row.find('#order[value="'+i+'"]').parent().removeClass("new_row");
                    });
                };
                $.unmask({id: maskId, callback: callback});
            }
        });

    },
    // Render the legend of the data_section_group with selected options text
    renderLegendText: function (title) {
        var parentId = new Array();
        var $cnt = 0;
        $('#area_section_group').each(function(index) {
            var nextrow = $(this).find('.row');
            var myselect = nextrow.find('select');
            do{
                if(nextrow.is(":visible")){
                    if(myselect.val()>0){
                        parentId[$cnt] = myselect.val();
                        $cnt += 1;
                    }
                }
                myselect = myselect.parent().parent().next().find('select');
                nextrow = myselect.parent().parent();
            }while(myselect.length>0)
        });
        areas.parentAreaIds = parentId;
    // renderLegendText: function (obj, selectedOptionId) {
        var legend = $('#data_section_group').find('legend');
        var selectedOptionId, selectedOptionTitle = '' , legendText = '';
        var searchWord = "Boundaries";

        if(areas.parentAreaIds.length > 0  && title.search(/--Select--/i) >= 0){
            selectedOptionId = areas.parentAreaIds[areas.parentAreaIds.length - 1];
            selectedOptionTitle = areas.getLevelName(areas.parentAreaIds); //$('#area_section_group').find('option[value="'+selectedOptionId+'"]').html();
        }else if(title.search(/--Select--/i) < 0){
            selectedOptionTitle = title;
            
        }


        if(legend.html().search(searchWord) !== -1){
            legendText = legend.html().substring(0, legend.html().search(searchWord)+searchWord.length);
            if(selectedOptionTitle !== undefined && selectedOptionTitle !== ''){
                legendText += ' of '+selectedOptionTitle;
            }

        }else{
            legendText = legend.html();
            if(selectedOptionTitle !== undefined && selectedOptionTitle !== ''){
                legendText += ' of '+selectedOptionTitle;
            }
        }

        legend.html(legendText);

    },
    // Rending of htmls for view and edit
    renderRecordToHtmlTableRow: function (data){
        var html = '';

        $.each(data,function(i,o){
            html += '<div class="table_row ' + (((i+1)%2 === 0)? 'even':'odd') + '">';
            html += '<div class="table_cell cell_visible">';
            if(parseInt(data[i].visible) == 1){
                html += '<span class="green">&#10003;</span>';
            }else{
                html += '<span class="red">&#10008;</span>';
            }
            html += '</div>';
            html += '<div class="table_cell">'+data[i].level_name+'</div>';
            html += '<div class="table_cell">'+data[i].code+'</div>';
            html += '<div class="table_cell">'+data[i].name+'</div>';
            html += '</div>';
        });


        return html;
    },

    renderRecordToHtmlTableRowForEdit: function (data) {
        var html = '', i ;
		var  Model= $('form').attr('model');
        $.each(data,function(index,element){
            i = (element.indexCount !== undefined && element.indexCount !== 0 )? element.indexCount : index;
            
            html += '<li data-id="' + (i+1) + '" ' + ((element.isNew !== undefined && element.isNew)? 'class="new_row" ':'') + '>';
            html += '<input type="hidden" name="data['+Model+']['+ i +'][order]" id="order" value="'+(i+1)/*element.order*/+'" />';

            html += '<input type="hidden" name="data['+Model+']['+ i +'][id]" id="id" value="'+element.id/*element.order*/+'" />';
                if(areas.addParent ==1){
                    html += '<div class="cell cell_visible" style="width: 120px;">';
                }else{
                    html += '<div class="cell cell_visible">';
                }
            html += '        <input type="hidden" name="data['+Model+']['+i+'][visible]" id="PostVisible_" value="0" />';
            html += '        <input type="checkbox" name="data['+Model+']['+i+'][visible]" value="1" id="PostVisible" '+((parseInt(element.visible) === 1)? 'checked="checked"':'') +'/>';

            html += '</div>';
            
            html += '<div class="cell cell_level">';
            html += '   <select name="data['+Model+']['+i+']['+((Model=='AreaEducation')?'area_education_level_id':'area_level_id')+']" style="width:100px;">';

            $.each(areas.area_levels, function(i,o){
                if(element.lowest_id > o.id || element.lowest_id==null){
                    html += '<option value="'+o.id+'" ';
                    if(parseInt(element.area_level_id) === parseInt(o.id)){
                        html += 'selected="selected"';
                    }
                    html +='>'+o.name+'</option>';
                    // html += '<option value="1" selected="selected">Papua New Guinea</option>';
                }
            });
            html += '   </select>';

            html += '</div>';

            html += '<div class="cell cell_code">';
            html += '   <div class="input_wrapper">';
            html += '       <input name="data['+Model+']['+i+'][code]" value="'+element.code+'" type="text" id="AreaCode">';
            html += '   </div>';
            html += '</div>';
            
            html += '<div class="cell cell_name">';
            html += '   <div class="input_wrapper">';
            html += '        <input name="data['+Model+']['+i+'][name]" value="'+element.name+'" type="text" id="AreaNAme">';
            html += '   </div>';
            html += '</div>';
                if(areas.addParent ==0){
            html += '<div class="cell cell_order">';
            html += '       <span class="icon_up" onclick="areas.reorder(this)"></span>';
            html += '       <span class="icon_down" onclick="areas.reorder(this)"></span>';
                }

                if(element.isNew !== undefined && element.isNew && areas.addParent ==0){
                    html += '                   <span class="icon_cross" onclick="areas.remove(this)"></span>';
                }
            html += '</div>';

        });

        return html;

    },

    renderRecordToHtmlTableRowForEditAreaLevels: function (data) {
		
		var  Model= $('form').attr('model');
        var html = '', i ;

        $.each(data,function(index, element){
            i = (element.indexCount !== undefined && element.indexCount !== 0 )? element.indexCount : index;

            html += '<li ';
            html += 'data-id="' + (i+1) + '" ';
            html += 'class="' + ((element.isNew !== undefined && element.isNew)? ' new_row': '' )+ '" ';
            html += '>';

            html += '<input type="hidden" name="data['+Model+']['+ i +'][level]" id="order" value="'+(i + 1)+'"/>';
            html += '<input type="hidden" name="data['+Model+']['+ i +'][id]" id="id" value="'+element.id+'"/>';
            
            html += '<div class="cell cell_name_area_level">';
            html += '   <div class="input_wrapper">';
            html += '        <input name="data['+Model+']['+i+'][name]" value="'+element.name+'" type="text" id="AreaNAme"/>';
            html += '   </div>';
            html += '</div>';
            
            html += '<div class="cell cell_order_area_level">';
            // html += '       <span class="icon_up" onclick="areas.reorder(this)"></span>';
            // html += '       <span class="icon_down" onclick="areas.reorder(this)"></span>';

            if(element.isNew !== undefined && element.isNew){
                html += '                   <span class="icon_cross" onclick="areas.remove(this)"></span>';
            }
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
    }
};


