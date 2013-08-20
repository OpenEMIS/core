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



var data = {
        validate:{
            messages:[],
            name: false,
            upload: false
        }
    },
    CustomReport = {
        allowedType: 'text/xml',
        maxFilesize: 3145728,
        alertOpt: {
            // id: 'alert-' + new Date().getTime(),
            parent: 'body',
            title: i18n.General.textDismiss,
            text: '<div style=\"text-align:center;\"></div>',
            type: alertType.error, // alertType.info or alertType.warn or alertType.error
            position: 'top',
            css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
            autoFadeOut: true
        },
        init: function(maxFilesize) {
            if(parseInt(maxFilesize) !== NaN){
                CustomReport.maxFilesize = maxFilesize;
            }
            $("#add").click(function(){
                var editElement = $('#edit_report');
                $(this).hide();

                if(editElement.is(':visible')){
                    editElement.hide();
                }
                $("#add_reports").show();
            });

            $(".edit").click(function(){
                var row = $(this).closest('.table_row'),
                    name = "",
                    desc = "",
                    addElement = $('#add_reports'),
                    editElement = $("#edit_report");

                if(addElement.is(':visible')){
                    addElement.find('.cancel').trigger('click');
                }

                name = row.find('.col_name').html().trim();
                desc = row.find('.col_desc').html().trim();
                id = CustomReport.getIdFromString(row.attr('id'));

                editElement.find('#name').attr('value', name);
                editElement.find('#description').attr('value', desc);
                editElement.find('#reportId').attr('value', id);
                editElement.show();
            });

            $(".cancel").click(function(){
                var form = $(this).closest('form');

                if(form.attr('id') !== 'EditReportCustomForm'){
                    CustomReport.validate.reset(form);
                    form.find('#add_reports').hide();
                    $("#add").show();
                }else{
                    CustomReport.validate.reset(form);
                    form.find('#edit_report').hide();
                }
                return false;
            });

            $('.disable').click(function(){
                if(window.confirm('Do you want to disable this report?')){
                    //$(this).closest('.table_row').hide('slow').remove();
                    var reportElement = $(this).closest('.table_row');
                    if(reportElement.attr('enabled') === '1'){
                        reportElement.attr('enabled', '0');
                        $(this).html('Enable');
                    }else{
                        reportElement.attr('enabled', '1');
                        $(this).html('Disable');
                    }
                }
            });

            $('button#update').click(CustomReport.validate.validateEdit);

            $('input[type="file"]').bind('change', CustomReport.validate.rules.file);
            $('input[type="text"]#name').bind('blur', CustomReport.validate.rules.name);
            $("#save").click(CustomReport.validate.validateSave);

        },
        validate: {
            isNameRequired:true, // required.
            isUploadRequired:true, //required.
            isValid: function(element, mode){ // if the validate variables are valid
                data.validate.messages = [];
                if(mode.toString().toLowerCase() === 'edit') {
                    data.validate.upload = false;
                    data.validate.name = false;
                }else{
                    data.validate.upload = true;
                    data.validate.name = false;

                }

                if(mode !== undefined && mode.toLowerCase() === 'add'){
                    element.find('input[type="file"]').trigger('change', [true]);
                }else{
                    element.find('input[type="file"]').trigger('change',[false]);
                }
                element.find('input[type="text"]#name').trigger('blur');

                var valid = true;
                /*for(prop in this){
                 if(typeof this[prop] === 'boolean'){
                 valid = valid && this[prop];
                 }
                 }*/

                for(prop in data.validate){
                    if(typeof data.validate[prop] === 'boolean'){
                        valid = valid && data.validate[prop];
                    }
                }
                return valid;
            },
            reset: function(element) { // reset all the fields and validate variables
                this.isNameValid = false; // required
                this.isNameValid = false; // required
                fileElement = element.find('input[type="file"]');
                fileElement.replaceWith(fileElement.clone().bind('change', CustomReport.validate.validateFile));
                element.find('#name').prop('value','');
                element.find('#description').prop('value','');
                data.validate.messages = [];
                data.validate.upload = false;
                data.validate.name = false;
            },
            getMessage: function(){
                return data.validate.messages.pop();
            },
            rules: {
                name: function(event, required){
                    if(this.value.length === 0){
                        data.validate.name = false;
                        data.validate.messages.push('Name cannot be empty.');
                    }else{
                        data.validate.name = true;
                    }
                },
                file: function(event, required){
                    var allowedType = CustomReport.allowedType;
                    var fileList = this.files;
                    if(!jQuery.browser.msie){
                        //console.info(fileList[0]);
                        if(fileList !== undefined && fileList.length > 0){
                            if(allowedType.toLowerCase() !== fileList[0].type.toLowerCase()){
                                data.validate.messages.push('Only XML file are allow.');
                                data.validate.upload = false;
                            }else if(fileList[0].size > 3145728){
                                data.validate.messages.push('Filesize is too large.');
                                data.validate.upload = false;
                            }else{
                                data.file = this.files[0];
                                data.validate.upload = true;
                            }
                        }else if(required == true){
                            data.validate.messages.push('Please select a file.');
                            data.validate.upload = false;
                        }else {
                            data.validate.upload = true;
                        }
                    }
                    // return false;
                }

            },
            validateSave: function(){
                if(!CustomReport.validate.isValid($(this).closest('#add_reports'), 'add')){
                    CustomReport.displayMessage(CustomReport.validate.getMessage());
                }else{
                    CustomReport.closest('form').submit();
                }

                return false;
            },
            validateEdit: function(){
                console.info(CustomReport.validate.isValid($(this).closest('#edit_report'), 'edit'));
                if(!CustomReport.validate.isValid($(this).closest('#edit_report'), 'edit')){
                    CustomReport.displayMessage(CustomReport.validate.getMessage());
                }else{
                    CustomReport.closest('form').submit();
                }
                // CustomReport.validate.isValid();
                return false;
            }
        },
        displayMessage: function(msg, type){
            var opt = CustomReport.alertOpt;
            opt.text = msg;
            if(type !== undefined) opt.type = type;
            $.alert(opt);
            opt.type = alertType.error;
            // alert(msg);
        },
        getIdFromString: function(elementId) {
            var id = '', words;
            if(elementId.length > 0){
                words = elementId.split('_');
                id = words[words.length - 1];
                if(isNaN(parseInt(id))){
                    id = '';
                }
            }

            return id;
        }
    }
