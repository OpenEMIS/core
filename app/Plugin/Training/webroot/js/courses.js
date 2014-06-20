
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
        //$(".icon_plus").unbind( "click");
        //$('.icon_plus').click(jsForm.insertNewInputFile, validateFileSize);
        //$('#TrainingCourseFiles').bind("onchange", objTrainingCourses.validateFileSize(this));
        //$(".icon_plus").removeAttr('onclick');
        //$(".icon_plus").attr('onclick', 'jsForm.insertNewInputFile;objTrainingCourses.getFileInput(this);');
    },


    validateFileSize: function(obj) {
      //this.files[0].size gets the size of your file.
      var fileSize = objfiles[0].size;
      //console.log(fileSize);
      //files[0].size
      var fileAttr = $(obj).attr('index');
      if(fileSize/1024 > 2050){
        $('.table_row ' + fileAttr).parent().append('<div id="fileinput_message_' + fileAttr + '" class="error-message custom-file-msg">Invalid File Size</div>');
      }else{
        $("#fileinput_message_" + fileAttr).remove();
    
      }
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
                table.find('.table_body tbody').append(data);
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

   validateCoursePrerequisite: function() {
          var val = new Array();
          var c = 0;
          $("#course_prerequisite_message").remove();
          $('.validate-course-prerequisite').each(function(i, obj) {
             if(in_array(obj.value, val)){
                $('.course_prerequisite').prepend('<div id="course_prerequisite_message" class="error-message custom-file-msg" style="width:230px;margin:0;">Duplicate Course Prerequisite</div>');
                return false;
             }else{
                val[c] = obj.value;
             }
             c++;
          });
          
    },

    addCoursePrerequisite: function(obj) {
        var table = $('.course_prerequisite');
        var index = table.find('.table_row').length + $('.delete-course_prerequisite input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body tbody').append(data);
                var element = '#searchCoursePrerequisite' + index;
                var url = getRootURL() + table.attr('url') + '/' + index;
                objTrainingCourses.attachAutoComplete(element, url, objTrainingCourses.selectCoursePrerequisite);
            };
            $.unmask({id: maskId, callback: callback});
        };
        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: getRootURL() + $(obj).attr('url'),
            data: params,
            beforeSend: function (jqXHR) { maskId = $.mask({parent: ".row_course_prerequisite"}); },
            success: success
        });
    },

    selectCoursePrerequisite: function(event, ui) {
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
        objTrainingCourses.validateCoursePrerequisite();
        return false;
    },
    
    deleteCoursePrerequisite: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');
        if(id != undefined) {
            var div = $('.delete-course_prerequisite');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
        objTrainingCourses.validateCoursePrerequisite();
    },



    addProvider: function(obj) {
        var table = $('.provider');
        var index = table.find('.table_row').length + $('.delete-provider input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body tbody').append(data);
                 objTrainingCourses.validateProvider();
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

    deleteProvider: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');

        if(id != undefined) {
            var div = $('.delete-provider');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
        objTrainingCourses.validateProvider();
    },

     validateProvider: function() {
          var val = new Array();
          var c = 0;
          $("#provider_message").remove();
          $('.validate-provider').each(function(i, obj) {
             if(in_array(obj.value, val)){
                $('.provider').prepend('<div id="provider_message" class="error-message custom-file-msg" style="width:230px;margin:0;">Duplicate Provider</div>');
                return false;
             }else{
                val[c] = obj.value;
             }
             c++;
          });
          
    },

    validateResultType: function() {
          var val = new Array();
          var c = 0;
          $("#result_type_message").remove();
          $('.validate-result-type').each(function(i, obj) {
             if(in_array(obj.value, val)){
                $('.result_type').prepend('<div id="result_type_message" class="error-message custom-file-msg" style="width:230px;margin:0;">Duplicate Result Type</div>');
                return false;
             }else{
                val[c] = obj.value;
             }
             c++;
          });
          
    },

    addResultType: function(obj) {
        var table = $('.result_type');
        var index = table.find('.table_row').length + $('.delete-result-type input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body tbody').append(data);
                objTrainingCourses.validateResultType();
            };
            $.unmask({id: maskId, callback: callback});
        };
        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: getRootURL() + $(obj).attr('url'),
            data: params,
            beforeSend: function (jqXHR) { maskId = $.mask({parent: table}); },
            success: success
        });
    },
    
    deleteResultType: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');

        if(id != undefined) {
            var div = $('.delete-result-type');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
        objTrainingCourses.validateResultType();
    },

   validateSpecialisation: function() {
          var val = new Array();
          var c = 0;
          $("#specialisation_message").remove();
          $('.validate-specialisation').each(function(i, obj) {
             if(in_array(obj.value, val)){
                $('.specialisation').prepend('<div id="specialisation_message" class="error-message custom-file-msg" style="width:230px;margin:0;">Duplicate Specialisation</div>');
                return false;
             }else{
                val[c] = obj.value;
             }
             c++;
          });
          
    },

    addSpecialisation: function(obj) {
        var table = $('.specialisation');
        var index = table.find('.table_row').length + $('.delete-specialisation input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body tbody').append(data);
                objTrainingCourses.validateSpecialisation();
            };
            $.unmask({id: maskId, callback: callback});
        };
        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: getRootURL() + $(obj).attr('url'),
            data: params,
            beforeSend: function (jqXHR) { maskId = $.mask({parent: table}); },
            success: success
        });
    },
    
    deleteSpecialisation: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');

        if(id != undefined) {
            var div = $('.delete-specialisation');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
        objTrainingCourses.validateSpecialisation();
    },

    validateExperience: function() {
          var val = new Array();
          var c = 0;
          $("#experience_message").remove();
          $('.validate-experience').each(function(i, obj) {
             if(in_array(obj.value, val)){
                $('.experience').prepend('<div id="experience_message" class="error-message custom-file-msg" style="width:230px;margin:0;">Duplicate Experience</div>');
                return false;
             }else{
                val[c] = obj.value;
             }
             c++;
          });
          
    },

    addExperience: function(obj) {
        var table = $('.experience');
        var index = table.find('.table_row').length + $('.delete-experience input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body tbody').append(data);
                 $(".months-selection-"+index).on("change", function(event){
                       objTrainingCourses.autoFill(index);
                });
            };
            $('.add_experience').addClass('hide');
            $.unmask({id: maskId, callback: callback});
        };
        $.ajax({
            type: 'GET',
            dataType: 'text',
            url: getRootURL() + $(obj).attr('url'),
            data: params,
            beforeSend: function (jqXHR) { maskId = $.mask({parent: table}); },
            success: success
        });
    },

    autoFill:function(index){
       var month = parseInt($('#searchExperienceMonth'+index).val());
       var year = parseInt($('#searchExperienceYear'+index).val());
       var totalMonth = (year*12) + month;

       $('.experience-validate-'+index).val(totalMonth);
       objTrainingCourses.validateExperience();
    },
    
    deleteExperience: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');

        if(id != undefined) {
            var div = $('.delete-experience');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
            $('.add_experience').removeClass('hide');
        }
        row.remove();
        objTrainingCourses.validateExperience();
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