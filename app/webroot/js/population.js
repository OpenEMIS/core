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
		
		if($('#population').hasClass('edit')){
			population.fetchDataByArea(currentAreaId, 'edit');
		}else{
			population.fetchDataByArea(currentAreaId, '');
		}
		
		$('a.withLatestAreaId').each(function(){
			var newHref = $(this).attr('href').replace(/(\/\d{4}\/)\d*/, '$1'+currentAreaId);
			$(this).attr('href', newHref);
		});
		
		$('a.btn_cancel').each(function(){
			var newCancelHref = $(this).attr('href').replace(/(\/\d{4}\/)\d*/, '$1'+currentAreaId);
			$(this).attr('href', newCancelHref);
		});
		
		$('form#PopulationEditForm').each(function(){
			var newAction = $(this).attr('action').replace(/(\/\d{4}\/)\d*/, '$1'+currentAreaId);
			$(this).attr('action', newAction);
		});
	});
	
	$('select#populationYear').on('change', function(){
		if($('#population').hasClass('edit')){
			location.href = population.base + 'edit/' + $(this).val() + '/' + currentAreaId;
		}else{
			location.href = population.base + 'index/' + $(this).val() + '/' + currentAreaId;
		}
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
        $('.link_add').click(function() {
			var validSelects = $('#areapicker.areapicker select').filter(function(){
				return parseInt($(this).val()) > 0;
			}).length;
			
			if(validSelects > 0){
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
	
	fetchDataByArea: function(areaId, mode) {
		var year = $('select#populationYear').val();
		
		var url;
		if(mode === 'edit'){
			url = population.base + 'loadForm/' + year + '/' + areaId;
		}else{
			url = population.base + 'loadData/' + year + '/' + areaId;
		}

		$.ajax({
			type: 'GET',
			dataType: 'html',
			url: url,
			success: function(data, textStatus) {
				var tableBody = $('#data_section_group table').find('tbody');
				var tableHead = $('#data_section_group table').find('thead');

				if (data.length > 0) {
					if(tableBody.length > 0){
						tableBody.remove();
					}
					tableHead.after(data);
					population.computeTotal(tableHead.parent());
				} else {
					if(tableBody.length > 0){
						tableBody.remove();
					}
					population.computeTotal(tableHead.parent());
				}
			}
		});
	},
    
    computeSubtotal: function(obj) {

        var row = $(obj).closest('tr');
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
		
		row.find('.cell-total').html(male.val().toInt() + female.val().toInt());
        var table = $(obj).closest('table');
        population.computeTotal(table);
    },
    
    computeTotal: function(table) {
        var total = 0;
        table.find('.cell-total').each(function() {
            total += $(this).html().toInt();
        });
        table.find('tfoot .cell-value').html(total);
    },
    
    addRow: function(id) {
		var tbody = $('form#PopulationEditForm tbody');
		var newRowIndex = tbody.find('tr').length;
		var url = population.base + 'addFormRow/' + newRowIndex;

		$.ajax({
			type: 'GET',
			dataType: 'html',
			url: url,
			success: function(data, textStatus) {
				var tableBody = $('#data_section_group table').find('tbody');
				var tableHead = $('#data_section_group table').find('thead');

				if (data.length > 0) {
					if(tableBody.length > 0){
						tableBody.append(data);
					}else{
						tableHead.after(data);
					}
				}
			}
		});
	},
    
    removeRow: function(obj) {
        var row = $(obj).closest('tr');
        var table = row.closest('table');
        var id = row.attr('record-id');
		
		var fieldDeletedIds = $('input#idsToBeDeleted');

        if(id !== '0') {
            fieldDeletedIds.val(fieldDeletedIds.val() + id + ',');
        }
        row.remove();
        population.computeTotal(table);
        
        var totalRow = table.find('tbody tr').length;
        if(totalRow < 1){
            table.find('tbody').remove();
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

    getLevelName: function (parentAreaIds) {
        selectedOptionId = parentAreaIds[parentAreaIds.length - 1];
        selectedOptionTitle = $('#area_section_group').find('option[value="'+selectedOptionId+'"]').html();
        if(selectedOptionTitle !== null){
            return selectedOptionTitle;
        }

        // console.info(parentAreaIds.pop());
    }
};

