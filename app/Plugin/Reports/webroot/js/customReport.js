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

//                if(editElement.is(':visible')){
//                    editElement.hide();
//                }
                $("#add_reports").show();

            $('input[type="file"]').change(CustomReport.validate.rules.file);

            $('input[type="text"]#name').blur(CustomReport.validate.rules.name);

        },
        selectFile: function(obj) {
            var parent = $(obj).closest('.file_input');
            parent.find('input[type="file"]').click();
        },

        updateFile: function(obj) {
            var parent = $(obj).closest('.file_input');
            parent.find('.file input[type="text"]').val($(obj).val());
        },
        deleteFile: function(id) {
            var dlgId = 'deleteDlg';
            var btn = {
                value: i18n.General.textDelete,
                callback: function() {
                    var maskId;
                    var controller = $('#controller').text();
                    var url = getRootURL() + controller + '/CustomDelete/';
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: url,
                        data: {id: id},
                        beforeSend: function (jqXHR) {
                            maskId = $.mask({parent: '.content_wrapper', text: i18n.Attachments.textDeletingAttachment});
                        },
                        success: function (data, textStatus) {
                            var callback = function() {
                                var closeEvent = function() {
                                    var successHandler = function() {
                                        $('div[row-id=' + id + ']').fadeOut(600, function() {
                                            $(this).remove();
                                            CustomReport.renderTable();
                                        });
                                    };
                                    jsAjax.result({data: data, callback: successHandler});
                                };
                                $.closeDialog({id: dlgId, onClose: closeEvent});
                            };

                            $.unmask({id: maskId, callback: callback});
                        }
                    });
                }
            };

            var dlgOpt = {
                id: dlgId,
                title: i18n.Attachments.titleDeleteAttachment,
                content: i18n.Attachments.contentDeleteAttachment,
                buttons: [btn]
            };

            $.dialog(dlgOpt);
        },
        addRow: function() {
            var size = $('.table_row').length;
            var maskId;
            var controller = $('#controller').text();
            var url = getRootURL() + controller + '/CustomAdd';

            $.ajax({
                type: 'GET',
                dataType: 'text',
                url: url,
                data: {size: size},
                beforeSend: function (jqXHR) {
                    maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textAddingRow});
                },
                success: function (data, textStatus) {
                    var callback = function() {
//                        $('.file_upload .table_body').append(data);
                        $('.table_body').append(data);
                    };
                    $.unmask({id: maskId, callback: callback});
                }
            });
        },
        deleteRow: function(obj) {
            $(obj).closest('.table_row').remove();
            CustomReport.renderTable();
        },
        renderTable: function() {
            $('.table_row.even').removeClass('even');
            $('.table_row:odd').addClass('even');
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
            validateSave: function(e){
                if(!CustomReport.validate.isValid($(this).closest('form'), 'add')){
                    e.preventDefault();
                    CustomReport.displayMessage(CustomReport.validate.getMessage());
                    return false;
                }

                return true;
            },
            validateEdit: function(e){
                if(!CustomReport.validate.isValid($(this).closest('form'), 'edit')){
                    e.preventDefault();
                    CustomReport.displayMessage(CustomReport.validate.getMessage());
                    return false;
                }

                return true;
            }
        },
        displayMessage: function(msg, type){
            var opt = CustomReport.alertOpt;
            opt.text = msg;
            if(type !== undefined) opt.type = type;
            $.alert(opt);
            opt.type = alertType.error;
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
