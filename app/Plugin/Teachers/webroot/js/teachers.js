$(document).ready(function() {
    objTeacher.init();

    $('#resetDefault').click(function(e){
        e.preventDefault();
        console.info('click');
        var $teacherPhotoContent = $('#TeacherPhotoContent');
        var $teacherResetImage= $('#TeacherResetImage')
        if ($teacherPhotoContent.attr('disabled')){
            $teacherPhotoContent.removeAttr('disabled');
            $teacherResetImage.attr('value', '0');
        }else {
            $teacherPhotoContent.attr('disabled', 'disabled');
            $teacherResetImage.attr('value', '1');
        }
    });
});

var objTeacher = {
    init :function(){
        $('#institutions .icon_plus').click(objTeacher.addRow);
        this.incrementYearEvent();
    },

    addRow: function() {
        var size = $('.table_body .table_row').length;
        var maskId;
        var url = getRootURL() + 'Teachers/institutionsAdd';
        
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
                    objTeacher.incrementYearEvent();
                    
//                    $('.datepicker_year').change(function() {
//                        var parent = $(this).parent().parent();
//                        var start_month = parent.find('.datepicker_month');
//                        var start_year = parent.find('.datepicker_year');
//
//                        var end_date = new Date(parseInt(start_year.val())+1, start_month.val()-1, 1);
//                        // update datepicker's value
//                        jsForm.updateDatepickerValue(parent.parent().find('.cell_end_date'), end_date);
//                    });
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
                                    url: getRootURL()+"Teachers/institutionsDelete/"+id
                            });

                            $.closeDialog({id:'deletedlg',onClose:function(){
                                    //location.reload();

                            }});
                            $("#institution_row_"+id).fadeOut(300, function() { $(this).remove(); });
                      }}],
            'closeBtnCaption': i18n.General.textNo
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