var maskId ;
var olapReport = {
    id : 0,
    part : 0,
    year: '0000',
    totalRecords : 0,
    limitPerRun : 0,
    progressTpl : '',
    observations: {},
    totalObservations : 0,
    variables: new Array(),
    init : function(){
        $.ajaxSetup({
            cache: false
        });

        $('div.date-selector select').change(function(e){
           olapReport.year = $(this).val();
        });

        $('button.btn.btn-add-single').click(function(e){
            var selectedFields = $('.selected-fields select');

            $('.allow-fields select option:selected').each(function(i,o){
                var model = $(o).data('model');
                var value = $(o).val();

                if(selectedFields.find('option[value="'+value+'"][data-model="'+model+'"]').length < 1){
                    var text = $(o).html();
                    text += " [" + $(o).parent().attr('label') + "]";
                    var html = '<option value="'+value+'" data-model="'+model+'">'+text+'</option>';
                    selectedFields.append(html);
                }
            });
        });

        $('button.btn.btn-remove-single').click(function(e){
            $('.selected-fields select option:selected').each(function(i,o){
                $(o).remove();
            });
        });

        $('button.btn.btn-add-all').click(function(e){
            var selectedFields = $('.selected-fields select');

            $('.allow-fields select option').each(function(i,o){
                var model = $(o).data('model');
                var value = $(o).val();

                if(selectedFields.find('option[value="'+value+'"][data-model="'+model+'"]').length < 1){
                    var text = $(o).html();
                    text += " [" + $(o).parent().attr('label') + "]";
                    var html = '<option value="'+value+'" data-model="'+model+'">'+text+'</option>';
                    $('.selected-fields select').append(html);
                }
            });
        });

        $('button.btn.btn-remove-all').click(function(e){
            $('.selected-fields select option').remove();
        });

        $('button#generate').click(function(e){

            var selectedFields = $('div.selected-fields select');
            if(selectedFields.children().length > 0 && parseInt(olapReport.year) > 0){
                olapReport.queryForObservations(selectedFields);

                $.dialog({
                    content: '<div id="progresswrapper" style="background:url(http://jimpunk.net/Loading/wp-content/uploads/loading130.gif); background-repeat:no-repeat; margin:auto;width:66px !important;height:66px;"><div id="progressbar" style=" height: 66px; line-height: 66px; text-align: center; width: 66px;">0%</div></div>',
                    title: 'Generating OLAP Report...',//$(this).parent().siblings('.col_name').html(),
                    showCloseBtn:false
                })

            }

//            olapReport.genReport(0);
        });
        $('div.date-selector select option').first().attr('selected', 'selected').trigger('change');

    },
    progressComplete:function(){
        $("#progressbar").html('0%');

        window.location = getRootURL()+'Reports/download/';
        $.closeDialog();


    },

    genReport:function(batch, observationId, year){
//        console.info("genReport");
//        console.info(olapReport.observations);
//        for(var observationId in olapReport.observations){
//            console.info("Observation "+observationId+ ": "+olapReport.observations[observationId]);
//        }
        var data = {
            batch: batch,
            observationId: 0,
            year: olapReport.year
        };

//        for(var observationId in olapReport.observations){

            data = {
                batch: batch,
                observationId: olapReport.observations.shift(),
                year: olapReport.year,
                last: (olapReport.observations.length<1)? true:false,
                variables: olapReport.variables
            };
        // check if the batch is a numeric value.
        $.ajax({
            type: 'POST',
            dataType: "json",
            url: getRootURL()+"Reports/genOlapReport/",
            data: data,
            beforeSend: function (jqXHR) {

            },
            success: function (data, textStatus) {
                olapReport.totalRecords++;
                if (olapReport.totalRecords >=  olapReport.totalObservations) {
                    percentage = 100;
//                    Report.progressComplete();
                }else{
                    var percentage = Math.floor(100 * parseInt(olapReport.totalRecords) / parseInt(olapReport.totalObservations));
                    olapReport.genReport(olapReport.totalRecords);
//                    Report.part = data.batch;
//                    Report.genReport(Report.part);
                }
                //$("#uploadprogressbar").progressBar(percentage);
                $("#progressbar").html(percentage+'%');
                if(percentage >= 100){
                    olapReport.progressComplete();
                }

            },
            error: function(data, textStatus) {

                $.closeDialog();
                var alertOpt = {
                    // id: 'alert-' + new Date().getTime(),
                    parent: 'body',
                    title: i18n.General.textDismiss,
                    text: '<div style=\"text-align:center;\">' + textStatus +'</div>',
                    type: alertType.error, // alertType.info or alertType.warn or alertType.error
                    position: 'top',
                    css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
                    autoFadeOut: true
                };

                $.alert(alertOpt);

//                alert(textStatus);
            }
        });
//        }
        return batch;
    },

    queryForObservations:function(selectedFields) {
        var data = {
            'schoolYear': 0000,
            'variables' : {}
        };
        data.schoolYear = $('div.date-selector select option:selected').val();
        data.variables = new Array();
        if(olapReport.variables.length > 0) {
            olapReport.variables = new Array();
        }
        selectedFields.children().each(function(i,o){
//            if(data.variables[$(o).data('model') ] === undefined){
//                data.variables[$(o).data('model') ] = new Array();
//            }
//            data.variables[$(o).data('model')].push($(o).data('model')+"."+$(o).val());
            olapReport.variables.push($(o).data('model')+"."+$(o).val());

        });
        data.variables = olapReport.variables;

        var totalFieldsSubmitting = 0;
        for(var prop in data.variables){
            totalFieldsSubmitting += data.variables[prop].length;
        }

        if(totalFieldsSubmitting > 0){

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: getRootURL()+"Reports/olapGetObservations/",
                data: data,
                success: function(data, textStatus) {
                    olapReport.observations = data['observations'];
                    olapReport.totalObservations = parseInt(data['total']);
                    count = 0;
                    batch = 0;
//                    olapReport.queryForTotalRecords();
                    olapReport.totalRecords = 0;
//                    for(var observationId in olapReport.observations){
//                        if(count < 4000){
                            olapReport.genReport(0);
//                            olapReport.genReport(batch, observationId, olapReport.year);
//                            count++;
//                        }
//                    }
//                    console.info(data);
                },
                error: function(data, textStatus){
                    console.group('error');
                    console.info(textStatus);
                    console.info(data);
                    console.groupEnd();
                }

            });
        }

    },
    queryForTotalRecords: function() {

        for(var observationId in olapReport.observations){
            var data = {
                'observationId':observationId,
                'schoolYear': olapReport.year
            }
            $.ajax({
                type: 'GET',
                dataType: 'json',
                url: getRootURL()+"Reports/olapGetNumberOfRecordsPerObservation/"+observationId+"/"+olapReport.year,
//                data: data,
                success: function(data, textStatus) {
                    olapReport.totalRecords += parseInt(data['total']);
                },
                error: function(data, textStatus){
                    console.group('error');
                    console.info(textStatus);
                    console.info(data);
                    console.groupEnd();
                }

            });

        }


    },
    orderOptionsUp: function(config){
        var selector = (config !== undefined && config.selector !== undefined)?config.selector:'.selected-fields select option';
        selector += ":selected";
        // move selected option up
        $(selector).each(function(i,o){
            var prevOption = $(o).prev();

            if(prevOption.length < 1){
                return false;
            }

            prevOption.detach();
            $(o).after(prevOption);
        });

    },
    orderOptionsDown: function(config){
        var selector = (config !== undefined && config.selector !== undefined)?config.selector:'.selected-fields select option';
        selector += ":selected";
        // move selected option down
        $($(selector).get().reverse()).each(function(i,o){
            var nextOption = $(o).next();

            if(nextOption.length < 1){
                return false;
            }
            nextOption.detach();
            $(o).before(nextOption);
        });

    }

}