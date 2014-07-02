
$(document).ready(function() {
    objOlapCube.init();
});

var objOlapCube = {

    init: function() {
        
        $('#OlapCubeDimensionOlapReportForm').submit(function() {
            maskId = $.mask({parent: '#olap_report', text: i18n.Olap.textGeneratingCube});
            return true;
        });
        $('.criteria').change(function() {
            maskId = $.mask({parent: '#olap_report', text: i18n.Olap.textLoadingCube});
            return true;
        });

    },

    checkValidate: function(obj){
        $("#cube_message").remove();
        if($('.rowCube').val()==$('.columnCube').val()){
            $('#OlapCubeDimensionOlapReportForm').prepend('<div id="cube_message" class="form-group"><div class="error-message custom-file-msg col-md-12" style="max-width:90%;margin:5px;">'+i18n.Olap.textValidateCube+'</div></div>');

            return false;
        }
        return true;
    },

   getDetailsAfterChange: function(obj){
        var val = $(obj).val();
        var cube = $('.cube').val();
        alert(getRootURL()+"OlapCube/getDimension/"+val+'/'+cube);

        var controls = new Array();
        controls[0] = "row";
        controls[1] = "column";
        controls[2] = "criteria";
        $.ajax({ 
            type: "get",
            dataType: "json",
            url: getRootURL()+"OlapCube/getDimension/"+val+'/'+cube,
            success: function(data){
                for(var i=0;i<controls.length;i++){
                    if(controls[i]!=obj.className){
                        var fieldVal = $('.'+controls[i]);
                        fieldVal[0].options.length = 0;

                        if(i==2){
                            var o = new Option("--Select--", "");
                            $(o).html("--Select--");
                            fieldVal.append(o);
                         }
                        if(data == null){
                            return;
                        }
                        
                        $.each(data, function(i,v){
                            o = new Option(v.OlapCubeDimension.dimension, v.OlapCubeDimension.id);
                            $(o).html(v.OlapCubeDimension.dimension);
                            fieldVal.append(o);
                        });
                    }
                }
              
            }
        });
    },


}
