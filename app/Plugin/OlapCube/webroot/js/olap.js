
$(document).ready(function() {
    objOlapCube.init();
});

var objOlapCube = {

    init: function() {
        $('.Generate').click(function(){
            var table = $('.olap_report');

            //$('#OlapCubeDimensionOlapReportForm').submit(); 
     

             $.ajax({
                type: 'POST',
                dataType: 'json',
                url: getRootURL() + 'OlapCube/olapReport',
                beforeSend: function (jqXHR) {
                    maskId = $.mask({parent: '.content_wrapper', text: i18n.Attachments.textDeletingAttachment});
                },
                success: function (data, textStatus) {
                    var callback = function() {
                        
                    };
                    $.unmask({id: maskId, callback: callback});
                }
            });
        });


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

                        //$('.training_provider option[value="' + defaultVal + '"]').prop('selected', true);
                    }
                }
              
            }
        });
    }

}
