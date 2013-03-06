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
    currentAreaId: 0,
    isEditable: false,
    baseURL: getRootURL() + 'Areas/',
    id: '#area',
    deletedRecords: [],
    ajaxUrl: 'areaAjax',
    // methods
	init: function() {
        this.isEditable = false;
		this.addAreaSwitching();

        $('.link_add').click(function() {
            if( $("#area_section_group .input").length < 1 ){
                var alertOpt = {
                    // id: 'alert-' + new Date().getTime(),
                    parent: 'body',
                    title: i18n.General.textDismiss,
                    text: '<div style=\"text-align:center;\">' + i18n.Areas.initAlertOptText +'</div>',
                    type: alertType.warn, // alertType.info or alertType.warn or alertType.error
                    position: 'top',
                    css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
                    autoFadeOut: true
                };

                $.alert(alertOpt);
                
            }else{
                areas.addRow();
                
            }

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
        $('select[name*="[area_level_"]').each(function(i, obj){
            $(obj).change(function (d, o){
                var TotalAreaLevel = $('select[name*="[area_level_"]').length;
                var isAreaLevelForInput = $(this).parent().parent().parent().attr('id');
                var currentSelctedOptionValue = parseInt($(this).find(':selected').val());
                var currentSelctedOptionTitle = $(this).find(':selected').html();
                var currentSelect = $(this).attr('name').replace('data[Area][area_level_','');

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
                        $('select[name=data\\[Area\\]\\[area_level_'+i+'\\]][class=input_area_level_selector]').attr('disabled','disabled');
                        $('select[name=data\\[Area\\]\\[area_level_'+i+'\\]][class=input_area_level_selector]').parent().parent().find('.label').addClass('disabled');

                        $('select[name=data\\[Area\\]\\[area_level_'+i+'\\]][class=input_area_level_selector]').find('option').remove();
                    }
				}else {
                    for (var i = currentSelect+1; i < TotalAreaLevel; i++) {
                        //disable the select element
                        $('select[name=data\\[Area\\]\\[area_level_'+i+'\\]][class!=input_area_level_selector]').attr('disabled','disabled');
                        $('select[name=data\\[Area\\]\\[area_level_'+i+'\\]][class!=input_area_level_selector]').parent().parent().find('.label').addClass('disabled');
                        $('select[name=data\\[Area\\]\\[area_level_'+i+'\\]][class!=input_area_level_selector]').find('option').remove();
                    }
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
                    areas.fetchChildren(this);
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
    },
    fetchChildren :function (currentobj){
        
        var selected = $(currentobj).val();
        var maskId;
        var url =  areas.baseURL +'viewAreaChildren/'+selected;
        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
            beforeSend: function (jqXHR) {
                    // maskId = $.mask({parent: '.content_wrapper'});
                    maskId = $.mask({parent: '#area_section_group', text: i18n.General.textLoadAreas});
            },
            success: function (data, textStatus) {
                
                    var callback = function(data) {
                            tpl = '';
                            var nextselect = $(currentobj).parent().parent().next().find('select');
                            var nextLabel = nextselect.parent().parent().find('.label');
                            //data[1] += nextLabel.text().toUpperCase(); // Add "ALL <text>" option in the select element
                            $.each(data,function(i,o){
                                tpl += '<option value="'+i+'">'+o+'</option>';
                            });
                            nextLabel.removeClass('disabled');
                            nextselect.find('option').remove();
                            nextselect.removeAttr('disabled');
                            nextselect.append(tpl);
                            
                    };
                    $.unmask({ id: maskId,callback: callback(data)});
            }

        });
    },
    fetchData: function(currentObject){
        // init values
        var selectedValue = areas.currentAreaId;
        var parentAreaIds = areas.parentAreaIds[areas.parentAreaIds.length - 1 ];
        var saveBtn = $('.btn_save');
        // if object exist update with later value
        if(currentObject !== undefined){
            selectedValue = $(currentObject).val();
            parentAreaIds = areas.parentAreaIds[areas.parentAreaIds.length - 1 ];
        }

        var maskId;
        var url =  areas.baseURL +'viewData/';

        if(parseInt(selectedValue) > 0 ){
            url += selectedValue;
        }else if(typeof parentAreaIds !== "undefined" && parseInt(parentAreaIds) !== 0){
            url += parentAreaIds;
        }

        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: url,
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

                            
                    };
                    $.unmask({ id: maskId,callback: callback});
            }
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
            data.push({
                id: id,
                area_level_id: area_level_id,
                code: code,
                name: name,
                order: order,
                visible: (visible === 1? visible: 0),
                parent_id: (areas.parentAreaIds.length < 1)? -1: areas.parentAreaIds[areas.parentAreaIds.length-1]
                // area_id: (typeof area_id !== 'undefined')? area_id : areaarea.currentAreaId
            });
            
            if(id===0) {
                $(this).attr('index', index);
            }
        });
        
        var maskId;
        var url = this.baseURL + this.ajaxUrl;
        
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

        $.each(data,function(index,element){
            i = (element.indexCount !== undefined && element.indexCount !== 0 )? element.indexCount : index;
            
            html += '<li data-id="' + (i+1) + '" ' + ((element.isNew !== undefined && element.isNew)? 'class="new_row" ':'') + '>';
            html += '<input type="hidden" name="data[Area]['+ i +'][order]" id="order" value="'+(i+1)/*element.order*/+'" />';

            html += '<input type="hidden" name="data[Area]['+ i +'][id]" id="id" value="'+element.id/*element.order*/+'" />';

            html += '<div class="cell cell_visible">';
            html += '        <input type="hidden" name="data[Area]['+i+'][visible]" id="PostVisible_" value="0" />';
            html += '        <input type="checkbox" name="data[Area]['+i+'][visible]" value="1" id="PostVisible" '+((parseInt(element.visible) === 1)? 'checked="checked"':'') +'/>';

            html += '</div>';
            
            html += '<div class="cell cell_level">';
            html += '   <select name="data[Area]['+i+'][area_level_id]" style="width:100px;">';

            $.each(areas.area_levels, function(i,o){
                html += '<option value="'+o.id+'" ';
                if(parseInt(element.area_level_id) === parseInt(o.id)){
                    html += 'selected="selected"';
                }
                html +='>'+o.name+'</option>';
                // html += '<option value="1" selected="selected">Papua New Guinea</option>';

            });
            html += '   </select>';

            html += '</div>';

            html += '<div class="cell cell_code">';
            html += '   <div class="input_wrapper">';
            html += '       <input name="data[Area]['+i+'][code]" value="'+element.code+'" type="text" id="AreaCode">';
            html += '   </div>';
            html += '</div>';
            
            html += '<div class="cell cell_name">';
            html += '   <div class="input_wrapper">';
            html += '        <input name="data[Area]['+i+'][name]" value="'+element.name+'" type="text" id="AreaNAme">';
            html += '   </div>';
            html += '</div>';
            
            html += '<div class="cell cell_order">';
            html += '       <span class="icon_up" onclick="areas.reorder(this)"></span>';
            html += '       <span class="icon_down" onclick="areas.reorder(this)"></span>';

                if(element.isNew !== undefined && element.isNew){
                    html += '                   <span class="icon_cross" onclick="areas.remove(this)"></span>';
                }
            html += '</div>';

        });

        return html;

    },

    renderRecordToHtmlTableRowForEditAreaLevels: function (data) {
        var html = '', i ;

        $.each(data,function(index, element){
            i = (element.indexCount !== undefined && element.indexCount !== 0 )? element.indexCount : index;

            html += '<li ';
            html += 'data-id="' + (i+1) + '" ';
            html += 'class="' + ((element.isNew !== undefined && element.isNew)? ' new_row': '' )+ '" ';
            html += '>';

            html += '<input type="hidden" name="data[AreaLevel]['+ i +'][level]" id="order" value="'+(i + 1)+'"/>';
            html += '<input type="hidden" name="data[AreaLevel]['+ i +'][id]" id="id" value="'+element.id+'"/>';
            
            html += '<div class="cell cell_name_area_level">';
            html += '   <div class="input_wrapper">';
            html += '        <input name="data[AreaLevel]['+i+'][name]" value="'+element.name+'" type="text" id="AreaNAme"/>';
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


