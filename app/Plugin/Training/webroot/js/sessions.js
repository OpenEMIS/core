
$(document).ready(function() {
    objTrainingSessions.init();

    
});

function in_array (needle, haystack, argStrict) {
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


var objTrainingSessions = {

    init: function() {
        var elementLocation = '#searchLocation';
        objTrainingSessions.attachAutoComplete(elementLocation, getRootURL() + $(elementLocation).attr('url'), objTrainingSessions.selectLocationField);
        objTrainingSessions.getDetailsAfterChangeCourse($("#TrainingSessionTrainingCourseId"));

        
    },

    selectProvider: function(obj){
        var provider = $('.training_provider');
        var selProvider = $('.provider');

        $(selProvider).val($(provider).val());
    },

    getDetailsAfterChangeCourse: function(obj){
        var trainingCourseId = $(obj).val();
        var course = $('.training_course');
        var provider = $('.training_provider');

        var selProvider = $('.provider');
        defaultVal = $(selProvider).val();

        if(trainingCourseId === ""){
            provider[0].options.length = 0;
        }else{
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+"Training/getTrainingCoursesById/"+trainingCourseId,
                success: function(data){
                    provider[0].options.length = 0;
                    var o = new Option("--Select--", "");
                     $(o).html("--Select--");
                     provider.append(o);

                    if(data == null){
                        return;
                    }
                    
                    $.each(data, function(i,v){
                        o = new Option(v.TrainingProvider.name, v.TrainingProvider.id);
                        $(o).html(v.TrainingProvider.name);
                        provider.append(o);
                    });

                    $('.training_provider option[value="' + defaultVal + '"]').prop('selected', true);
                }
            });
        }
    },

    clearTrainee: function() {
        var table = $('.trainee');
        table.find('.table_body').empty();
    },

    selectLocationField: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            if(element.get(0).tagName.toUpperCase() === 'INPUT' && element.get(0).id == 'searchLocation') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        return false;
    },

    selectTrainerField: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            console.log(element.get(0));
            if(element.get(0).tagName.toUpperCase() === 'INPUT' && element.get(0).id == 'searchTrainer') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        return false;
    },

   validateTrainee: function() {
          var val = new Array();
          var c = 0;
          $("#trainee_message").remove();
          $('.validate-trainee').each(function(i, obj) {
             if(in_array(obj.value, val)){
                $('.trainee').prepend('<div id="trainee_message" class="error-message custom-file-msg" style="width:230px;margin:0;">Duplicate Trainee</div>');
                return false;
             }else{
                val[c] = obj.value;
             }
             c++;
          });
          
    },


    uploadTrainee: function(obj) {
        if($("#divUpload").hasClass("hide")){
            $('#divUpload').removeClass("hide");
        }else{
            $('#divUpload').addClass("hide");
        }
    },

    processUploadTrainee: function(obj) {
        var table = $('.trainee');
        var index = table.find('.table_row').length + $('.delete-trainee input').length;
        var maskId;
        $("#divUploadMsg").val(''); 
        $('#divUploadMsg').removeClass();
        $("#divUploadMsg").addClass('hide');

        var trainingCourseId = $('.training_course').val();
        $('.training_course').removeClass('form-error');
        $('.training_course').parent().parent().find('.error-message').remove();
        $('.training_course').parent().parent().removeClass('error');
        if(trainingCourseId==""){
            $("html, body").animate({ scrollTop: 0 }, "fast");
            $('.training_course').parent().parent().append('<div class="error-message">Please select a valid Course.</div>');
            $('.training_course').parent().parent().addClass('error');
            $('.training_course').addClass('form-error');
            return false;
        }

        var data = new FormData($('input[name^="upload_file"]'));     
        jQuery.each($('input[name^="upload_file"]')[0].files, function(i, file) {
            data.append(i, file);
        });

        $.ajax({
            url: getRootURL() + 'Training/ajax_upload_trainee/'+index+'/'+trainingCourseId,
            data: data,
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST',
            dataType: 'json',
            beforeSend: function (jqXHR) { maskId = $.mask({parent: table}); },
            success: function(data) {
                var callback = function() {
                    var obj = data;
                    if (obj.layout == undefined || obj.layout == null || obj.layout.length == 0){

                    }else{
                        if (table.find('.table_body tbody').length > 0) { 
                            table.find('.table_body tbody').append(obj.layout);
                        }else{
                            table.find('.table_body').append('<tbody>'+obj.layout+'</tbody>');
                        }
                        objTrainingSessions.validateTrainee();
                    }
                    if(obj.errorFlag){
                         $('#divUploadMsg').addClass('error-message');
                    }else{
                        $('#divUploadMsg').addClass('text-success');
                    }

                    if(obj.message!=""){
                        $('#divUploadMsg').html(obj.message);
                        $("#divUploadMsg").removeClass('hide');
                    }
                }
                $.unmask({id: maskId, callback: callback});
            },
             error: function (data) {
                console.log("error");
            } 
        });

    },


     addTrainee: function(obj) {
        var table = $('.trainee');
        var index = table.find('.table_row').length + $('.delete-trainee input').length;
        var maskId;
        var params = {index: index};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body').append(data);
                var element = '#searchTrainee' + index;
                var url = getRootURL() + table.attr('url') + '/' + index + '/' + $('.training_course').val();
                objTrainingSessions.attachAutoComplete(element, url, objTrainingSessions.selectTrainee);
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

    selectTrainee: function(event, ui) {
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
        objTrainingSessions.validateTrainee();
        return false;
    },
    
    deleteTrainee: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');

        if(id != undefined) {
            var div = $('.delete-trainee');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
        objTrainingSessions.validateTrainee();
    },

    addTrainer: function(obj) {
        var table = $('.trainer');
        var index = table.find('.table_row').length + $('.delete-trainer input').length;
        var trainer_type = $('.trainer_type :selected').text(); 
        var maskId;
        var params = {index: index, trainer_type: trainer_type};
        var success = function(data, status) {
            var callback = function() {
                table.find('.table_body tbody').append(data);
                var element = '#searchTrainer' + index;
                var url = getRootURL() + table.attr('url') + '/' + index;
                if($('.trainer_type').val()=='1'){
                    objTrainingSessions.attachAutoComplete(element, url, objTrainingSessions.selectTrainer);
                }else{
                    $("#searchTrainer"+index).on("keydown", function(event){
                         objTrainingSessions.autoFill(index);
                    });
                }
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
    
    autoFill:function(index){
        $('.trainer-id-'+index).val('0');
        $('.trainer-table-'+index).val('');
        $('.trainer-validate-'+index).val('_'+$('#searchTrainer'+index).val());
        objTrainingSessions.validateTrainer();
    },
    
    selectTrainer: function(event, ui) {
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
        objTrainingSessions.validateTrainer();
        return false;
    },
    
    deleteTrainer: function(obj) {
        var row = $(obj).closest('.table_row');
        var id = row.attr('row-id');

        if(id != undefined) {
            var div = $('.delete-trainer');
            var index = div.find('input').length;
            var name = div.attr('name').replace('{index}', index);
            var controlId = $('.control-id');
            var input = row.find(controlId).attr({name: name});
            div.append(input);
        }
        row.remove();
        objTrainingSessions.validateTrainer();
    },

    validateTrainer: function() {
          var val = new Array();
          var c = 0;
          $("#trainer_message").remove();
          $('.validate-trainer').each(function(i, obj) {
             if(in_array(obj.value, val)){
                $('.trainer').prepend('<div id="trainer_message" class="error-message custom-file-msg" style="width:230px;margin:0;">Duplicate Trainer</div>');
                return false;
             }else{
                val[c] = obj.value;
             }
             c++;
          });
          
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
    },

    save: function(obj) {

       if(objTrainingSessions.errorFlag()){ 
            if( $('#TrainingSessionSessionAddForm').length )  {
                return true;
            }else{
                return true;
            }
        }else{ 
            return false; 
        }
    }

}
