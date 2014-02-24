
$(document).ready(function() {
    objTrainingCourses.init();
});

function in_array (needle, haystack, argStrict) {
      // From: http://phpjs.org/functions
      // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
      // +   improved by: vlado houba
      // +   input by: Billy
      // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
      // *     example 1: in_array('van', ['Kevin', 'van', 'Zonneveld']);
      // *     returns 1: true
      // *     example 2: in_array('vlado', {0: 'Kevin', vlado: 'van', 1: 'Zonneveld'});
      // *     returns 2: false
      // *     example 3: in_array(1, ['1', '2', '3']);
      // *     returns 3: true
      // *     example 3: in_array(1, ['1', '2', '3'], false);
      // *     returns 3: true
      // *     example 4: in_array(1, ['1', '2', '3'], true);
      // *     returns 4: false
      var key = '',
        strict = !! argStrict;

      if (strict) {
        for (key in haystack) {
          if (haystack[key] === needle) {
            return true;
          }
        }
      } else {
        for (key in haystack) {
          if (haystack[key] == needle) {
            return true;
          }
        }
      }

      return false;
}


var objTrainingCourses = {
    init: function() {
        $(".icon_plus").unbind( "click" );
        $('.icon_plus').click(objTrainingCourses.addRow);
    },

    validateFileSize: function(obj) {
      //this.files[0].size gets the size of your file.
      var fileSize = obj.files[0].size;
      var fileAttr = $(obj).attr('index');
      if(fileSize/1024 > 2050){
        $('.table_row ' + fileAttr).parent().append('<div id="fileinput_message_' + fileAttr + '" class="error-message custom-file-msg">Invalid File Size</div>');
      }else{
        $("#fileinput_message_" + fileAttr).remove();
    
      }
    },


    addRow: function() {
        var table = $('.file_upload');
        var size = table.find('.table_row').length;

        var maskId;
        var controller = $('#controller').text();

        var url = getRootURL() + controller + '/attachmentsCourseAdd';
    
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
                    $('.file_upload .table_body').append(data);
                };
                $.unmask({id: maskId, callback: callback});
            }
        });
    },

    deleteFile: function(id) {
        var dlgId = 'deleteDlg';
        var btn = {
            value: i18n.General.textDelete,
            callback: function() {
                var maskId;
                var controller = $('#controller').text();
                var url = getRootURL() + controller + '/attachmentsCourseDelete/';
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
                                    $('[file-id=' + id + ']').fadeOut(600, function() {
                                        $(this).remove();
                                        attachments.renderTable();
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


    validateTargetPopulation: function() {
          var val = new Array();
          var c = 0;
          $("#target_population_message").remove();
          $('.validate-target-population').each(function(i, obj) {
             if(in_array(obj.value, val)){
                $('.target_population').prepend('<div id="target_population_message" class="error-message custom-file-msg" style="width:230px;margin:0;">Duplicate Target Population</div>');
                return false;
             }else{
                val[c] = obj.value;
             }
             c++;
          });
          
    },

    addTargetPopulation: function(obj) {
        var table = $('.target_population');
        var index = table.find('.table_row').length + $('.delete-target-population input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body').append(data);
                var element = '#searchTargetPopulation' + index;
                var url = getRootURL() + table.attr('url') + '/' + index;
                objTrainingCourses.attachAutoComplete(element, url, objTrainingCourses.selectTargetPopulation);
            };
            $.unmask({id: maskId, callback: callback});
        };
        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: getRootURL() + $(obj).attr('url'),
            data: params,
            beforeSend: function (jqXHR) { maskId = $.mask({parent: ".row_target_population"}); },
            success: success
        });
    },

    selectTargetPopulation: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            if(element.get(0).tagName.toUpperCase() === 'INPUT') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        objTrainingCourses.validateTargetPopulation();
        return false;
    },
    
    deleteTargetPopulation: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');

        if(id != undefined) {
            var div = $('.delete-target-population');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
        objTrainingCourses.validateTargetPopulation();
    },

   validatePrerequisite: function() {
          var val = new Array();
          var c = 0;
          $("#prerequisite_message").remove();
          $('.validate-prerequisite').each(function(i, obj) {
             if(in_array(obj.value, val)){
                $('.prerequisite').prepend('<div id="prerequisite_message" class="error-message custom-file-msg" style="width:230px;margin:0;">Duplicate Prerequisite</div>');
                return false;
             }else{
                val[c] = obj.value;
             }
             c++;
          });
          
    },

    addPrerequisite: function(obj) {
        var table = $('.prerequisite');
        var index = table.find('.table_row').length + $('.delete-prerequisite input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body').append(data);
                var element = '#searchPrerequisite' + index;
                var url = getRootURL() + table.attr('url') + '/' + index;
                objTrainingCourses.attachAutoComplete(element, url, objTrainingCourses.selectPrerequisite);
            };
            $.unmask({id: maskId, callback: callback});
        };
        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: getRootURL() + $(obj).attr('url'),
            data: params,
            beforeSend: function (jqXHR) { maskId = $.mask({parent: ".row_prerequisite"}); },
            success: success
        });
    },

    selectPrerequisite: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            if(element.get(0).tagName.toUpperCase() === 'INPUT') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        objTrainingCourses.validatePrerequisite();
        return false;
    },
    
    deletePrerequisite: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');
        if(id != undefined) {
            var div = $('.delete-prerequisite');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
        objTrainingCourses.validatePrerequisite();
    },

    attachAutoComplete: function(element, url, callback) {
        $(element).autocomplete({
            source: url,
            minLength: 2,
            select: callback
        });
    },

    errorFlag: function() {
        var errorMsg = $('.custom-file-msg').length;
        if(errorMsg==0){
            return true;
        }else{
            return false;
        }
    }

}