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

var CustomTable = {
	colnum : 0,
	rownum : 0,
	init: function(){
		this.attachInsertFunctions();
		this.colnum = $('#XCatList div .field_value ul.quicksand').children().length;
		this.rownum = $('#YCatList div .field_value ul.quicksand').children().length;
		this.changeView();
        this.attachVisibleEvent();
	},
	insertRow : function(){
		var mark = CustomTable.rownum++;
		var firstrow = $('#section > div:first').clone();
		firstrow.find('> div.table_cell:first').find('span');
		firstrow.find('> div.table_cell:first').find('span').html('value');
	
		firstrow.find('> div.table_cell:first').find('span').attr('id','Ylabel_'+CustomTable.rownum);
		firstrow.attr('style','display:none');
		firstrow.attr('colnum',mark);
		firstrow.attr('id','data-grid-row-'+(mark+1));
		
		$('#section > div:last').after(firstrow).promise().done(function(){
			firstrow.show('slow');//add effect
			var div = $('.table_wrapper');
			div.animate({ scrollTop: div.prop("scrollHeight") - div.height() }, 1000);
		});
		
		
	},
	insertCol : function(){
		CustomTable.colnum++;
		//var eff ;
		var headtpl = '<div class="table_cell" ><span id="Xlabel_'+CustomTable.colnum+'">'+i18n.CustomTables.textValue+'</span></div>';
		var coltpl =  '<div class="table_cell"></div>';
		var eff = function(){ $('.table_row, .table_head').each(function(){
			tpl = $(this).attr('class') == 'table_head' ? headtpl : coltpl;
			$(this).find('div:last').after(tpl);
		})
		}
		$.when(eff()).done(function(){
			var leftPos = $('.table_wrapper').scrollLeft();
			$(".table_wrapper").animate({scrollLeft: leftPos+200}, 800);
		});
		
	},
	attachInsertFunctions : function(){
		$('#addRow').click(function(){
			CustomTable.insertRow();
			CustomTable.addOption($('#YCatList'));
		});
		$('#addCol').click(function(){
			CustomTable.insertCol();
			CustomTable.addOption($('#XCatList'));
		});
	},
	moveY : function(obj){

		if($(obj).attr('class') == 'icon_up'){
			$(obj).closest('li');
			var ref = $(obj).closest('li').attr('data-id')
			var prevEl = ($('#data-grid-row-'+ref).prev());
			
			if(prevEl.length === 0) return;
			var div = $('#data-grid-row-'+ref).detach();
			prevEl.before(div);

		}else{
			$(obj).closest('li');
			var ref = $(obj).closest('li').attr('data-id')
			var nextEl = ($('#data-grid-row-'+ref).next());
			
			if(nextEl.length === 0) return;
			
			var div = $('#data-grid-row-'+ref).detach();
			nextEl.after(div);
			
		}
	},
	moveX : function(obj,direction){
		
		if($(obj).attr('class') == 'icon_up'){ //left
			
			var ref = $(obj).closest('li').attr('data-id');
			var el = $('#Xlabel_'+ref).closest('div.table_cell');
			
			var rowIndex  = $(el).parent().children().index(el);
			if(rowIndex == 1) return; //do't allow to swap with first col'
			var prevEl = $(el).prev();
			var div = $(el).detach();
			prevEl.before(div);
			
			
			

		}else{//right
			var ref = $(obj).closest('li').attr('data-id');
			var el = $('#Xlabel_'+ref).closest('div.table_cell');
			
			var rowIndex  = $(el).parent().children().index(el);
			
			if($(el).parent().children().length == (rowIndex +1 )  )return;//do't allow to swap with Edge '
			var prevEl = $(el).next();
			var div = $(el).detach();
			prevEl.after(div);
			
		}
	},
	removeRow : function(obj){
		
		var row = $(obj).closest('div.table_row');
		row.hide('slow',function(){row.remove()});
		
	},
	removeCol : function(obj){
		
		var row = $(obj).closest('div.table_row');
		row.hide('slow',function(){row.remove()});
		
	},
	addOption : function(objList){
                
		if(objList.attr('id') == 'XCatList'){
			label = "X";
			licount = CustomTable.colnum;
		}else{
			label = "Y";
			licount = CustomTable.rownum;
		}
        var censusGridId = objList.last().find('#ref_id').val();

		tpl = '<li class="" data-id="'+licount+'">'+
                            '<input type="hidden" value="'+licount+'" name="data[CensusGrid'+label+'Category]['+licount+'][order]" id="order">'+
                            '<input type="hidden" value="1" name="data[CensusGrid'+label+'Category]['+licount+'][visible]" id="visible">'+
                            //'<input type="hidden" value="4" name="data[CensusGrid'+label+'Category]['+licount+'][id]" id="id">'+
                            '<input type="hidden" value="' + censusGridId + '" name="data[CensusGrid'+label+'Category]['+licount+'][census_grid_id]" id="ref_id">'+
                            '<input type="text" value="" onKeyUp="$(\'#'+label+'label_'+licount+'\').html(this.value);" placeholder="'+i18n.CustomTables.textLowerCapValue+'" name="data[CensusGrid'+label+'Category]['+licount+'][name]" class="default">'+
                            '<span class="icon_visible"></span>'+
                            '<span onclick="CustomTable.reorder(this);CustomTable.move'+label+'(this);" class="icon_up"></span>'+
                            '<span onclick="CustomTable.reorder(this);CustomTable.move'+label+'(this);" class="icon_down"></span>'+
                    '</li>';

        $('#'+objList.attr('id')+' div .field_value ul.quicksand').append(tpl);
        CustomTable.reAttachVisibleEvent();
        CustomTable.renderTableStrip();
		
	},
	changeCustomTableFilter: function(obj){
		window.location = getRootURL()+'Setup/customTables/'+$(obj).val();
	},

	changeView : function() {
        var selectedText = $('#customfieldchoices option:selected').text();
        var selectedValue = $('#customfieldchoices option:selected').val();

        // getting the link
        var link = $('a.table-link');
        var linkText = link.text();
        // id from custom table list
        var id = $('#CensusGridDataId').val();

        siteTypeId = $('#institution_site_type_id').val();

        if (linkText == "Add") {
        	link.removeAttr("href");
        	link.attr('href', getRootURL() + 'Setup/customTablesEdit/' + siteTypeId);
        } else if (linkText == "List") {
        	link.removeAttr("href");
        	link.attr('href', getRootURL() + 'Setup/customTables/' + id);
        	$('#siteTypeid').val(id);
        }
	},

    attachVisibleEvent: function(obj) {
        var optionList, checkboxList;
        var option = '.options .icon_visible';
        var checkbox = '.table_cell .visible_checkbox';
        if(obj!=undefined) {
            optionList = obj.find(option);
            checkboxList = obj.find(checkbox);
        } else {
            optionList = $(option);
            checkboxList = $(checkbox);
        }

        optionList.each(function() {
            var obj = $(this);
            obj.click(function() {
                var category = $(obj).closest('ul').attr('data-category');
                var ref = $(obj).closest('li').attr('data-id');
                var el;
                if(category.toLowerCase() === 'x'){
                    el = $('#Xlabel_'+ref).closest('div.table_cell');
                }else{
                    el = $('#Ylabel_'+ref).closest('div#data-grid-row-'+ref);
                }

                var li = obj.closest('.options > li');

                if(li.hasClass('inactive')) {
                    li.removeClass('inactive');
                    li.find('> #visible').val(1);

                    if(category.toLowerCase() === 'x'){
                        el.closest('.table').find('.table_body').children().each(function(i,o){
                            $(o).children(":nth-child(2)").removeClass('inactive').show();
                        });

                    }
                    el.removeClass('inactive').show();
                } else {
                    li.addClass('inactive');
                    li.find('> #visible').val(0);

                    if(category.toLowerCase() === 'x'){
                        el.closest('.table').find('.table_body').children().each(function(i,o){
                            $(o).children(":nth-child(2)").addClass('inactive').hide();
                        });
                    }
                    el.addClass('inactive').hide();
                }

                CustomTable.renderTableStrip();

            });
        });

        checkboxList.each(function() {
            var obj = $(this);
            obj.click(function() {
                var row = obj.closest('li');
                if(obj.is(':checked')) {
                    row.find('> #visible').val(1);
                } else {
                    row.find('> #visible').val(0);
                }
            });
        });
    },

    detachVisibleEvent: function(obj){
        var optionList, checkboxList;
        var option = '.options .icon_visible';
        var checkbox = '.table_cell .visible_checkbox';
        if(obj!=undefined) {
            optionList = obj.find(option);
            checkboxList = obj.find(checkbox);
        } else {
            optionList = $(option);
            checkboxList = $(checkbox);
        }

        optionList.each(function() {
            var obj = $(this);
            obj.off('click');
        });

        checkboxList.each(function() {
            var obj = $(this);
            obj.off('click');
        });

    },

    reAttachVisibleEvent: function(obj) {
        CustomTable.detachVisibleEvent();
        CustomTable.attachVisibleEvent();
    },

    reorder: function(obj) {

        var sortEvent = jsList.doSort(obj, {callback : function() {
            jsList.init();
            CustomTable.renderTableStrip();
            CustomTable.reAttachVisibleEvent();
        }
        });
    },

    renderTableStrip: function(){
        $('.table .table_body .table_row').removeClass('even');
        $('.table .table_body .table_row:not(.inactive):odd').addClass('even');
    }
}

$(function(){
	CustomTable.init();
})