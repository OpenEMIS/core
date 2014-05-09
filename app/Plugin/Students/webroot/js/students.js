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
    objStudent.init();

    $('#resetDefault').click(function(e){
		alert('click');
        e.preventDefault();
//        console.info('click');
        var $photoContent = $('#StudentPhotoContent');
        var $resetImage= $('#StudentResetImage')
        if ($photoContent.attr('disabled')){
            $photoContent.removeAttr('disabled');
            $resetImage.attr('value', '0');
        }else {
            $photoContent.attr('disabled', 'disabled');
            $resetImage.attr('value', '1');
        }
    });
});

var objStudent = {
    init :function(){
        $('#institutions .icon_plus').click(objStudent.addRow);
        this.incrementYearEvent();
    },

    addRow: function() {
		var size = $('.table_body .table_row').length;
		var maskId;
		var url = getRootURL() + 'Students/institutionsAdd';
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {order: size},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					$('.table_body').append(data);
					jsForm.initDatepicker($('.table_row:last'));

					// // update datepicker's value
					// jsForm.updateDatepickerValue('.cell_end_date', new Date());
					
//					$('.cell_start_date .datepicker select.datepicker_year').change(function() {
////					$('.datepicker_year').change(function() {
//						var parent = $(this).parent().parent();
//						var start_month = parent.find('.datepicker_month');
//						var start_year = parent.find('.datepicker_year');
//
//						var end_date = new Date(parseInt(start_year.val())+1, start_month.val()-1, 1);
//						// update datepicker's value
//						jsForm.updateDatepickerValue(parent.parent().find('.cell_end_date'), end_date);
//					});
                    objStudent.incrementYearEvent();
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},

	removeRow: function(obj) {
        var row = $(obj).closest('.table_row');
        var table = row.closest('.table');
        var id = row.attr('data-id');
        row.remove();
    },

	getUniqueID : function (){
        $.ajax({ 
            type: "get",
            url: getRootURL()+"Students/getUniqueID",
            success: function(data){
				if(data!='Fail'){
					document.getElementById('StudentIdentificationNo').value = data;
				}else{
					var element = $("input:[id*=Gen]").parent().parent().find(".error-message");
					if(element.length > 0){
						element.html('Unable to Generate with custom format.');
					}else{
						$("input:[id*=Gen]").parent().parent().append("<div class='error-message'>Unable to Generate with custom format.</div>");
					}
				}
            }
        });
    },
	
    validateAdd : function(){
        var bool = true,
            table = $('.table'),
            table_rows = table.find('.table_row'),
            errorMessage = [],
            alertOpt = {
                parent: ".content_wrapper",
                type: alertType.error,
                text: 'Error has occurred.',
                position: 'top',
                css: {},
                autoFadeOut: true
            };

        table_rows.each(function(i, o){
            var startMonth = $(o).find(".cell_start_date .datepicker select.datepicker_month option:selected").val(),
                startYear = $(o).find(".cell_start_date .datepicker select.datepicker_year option:selected").val(),
                endMonth = $(o).find(".cell_end_date .datepicker select.datepicker_month option:selected").val(),
                endYear = $(o).find(".cell_end_date .datepicker select.datepicker_year option:selected").val();

            if(startMonth < 1 || startYear < 1 ) {
                errorMessage.push("Start date is required.");
            }

            if(endMonth < 1 || endYear < 1) {
                errorMessage.push("End date is required.");
            }

            if((endYear == startYear && endMonth <= startMonth) || endYear < startYear) {
                errorMessage.push("Please select the correct time period.");
            }

        });

        if(errorMessage.length > 0 ) {
            bool = !bool;
        }

        if(bool){
            $.mask({text: i18n.General.textSaving});
        }else{
            alertOpt.text = errorMessage.shift();
            $.alert(alertOpt);
        }

        return bool;
    },

	confirmDeletedlg : function(id){
        $.dialog({
            'id' :'deletedlg',
            'title': i18n.General.textDeleteConfirmation,
            'content': i18n.General.textDeleteConfirmationMessage,
            'buttons':[
                    {'value':i18n.General.textYes,'callback':function(){ 
                            
                            $.ajax({
                                    type: "post",
                                    dataType: "json",
                                    url: getRootURL()+"Students/institutionsDelete/"+id
                            });

                            $.closeDialog({id:'deletedlg',onClose:function(){
                                    //location.reload();

                            }});
                            $("#institution_row_"+id).fadeOut(300, function() { $(this).remove(); });
                      }}],
            'closeBtnCaption':i18n.General.textNo
        })
    },

    incrementYearEvent: function() {
        $('.cell_start_date .datepicker select.datepicker_year').change(function() {
            var parent = $(this).parent().parent();
            var start_month = parent.find('.datepicker_month');
            var start_year = parent.find('.datepicker_year');

            var end_date = new Date(parseInt(start_year.val())+1, start_month.val()-1, 1);
            // update datepicker's value
            jsForm.updateDatepickerValue(parent.parent().find('.cell_end_date'), end_date);
        });

    }
}
