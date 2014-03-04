$(function() {});

var QualityRubric = {
    updateURL : function (obj){
        var link = $(obj).closest('form').attr('link');
        
        var url = getRootURL() + link;
        
     //   alert('b4 = '+url);
        //var selectedDate = $('#dateYear').val()+ '-'+$('#dateMonth').val()+ '-'+$('#dateDay').val();
        var schoolYearId = $('#schoolYearId').val();
        var rubricsTemplateId = $('#rubricsTemplateId').val();
        var institutionSiteClassGradeId = $('#institutionSiteClassGradeId').val();
        var institutionSiteClassId = $('#institutionSiteClassId').val();
        var institutionSiteTeacherId = $('#institutionSiteTeacherId').val();
        //var qualityTypeId = $('#qualityTypeId').val();
        
        //url += '/'+ selectedDate;
       
        switch($(obj).attr('id')){
           case 'schoolYearId':
                url += '/'+ schoolYearId;
               break;
           case 'rubricsTemplateId':
                url += '/'+ schoolYearId+'/'+ rubricsTemplateId;
               break;
           case 'institutionSiteClassGradeId':
                url += '/'+ schoolYearId+'/'+ rubricsTemplateId+'/'+ institutionSiteClassGradeId;
               break;
           case 'institutionSiteClassId':
                url += '/'+ schoolYearId+'/'+ rubricsTemplateId+'/'+ institutionSiteClassGradeId+'/'+ institutionSiteClassId;
               break;
           case 'institutionSiteTeacherId':
                url += '/'+ schoolYearId+'/'+ rubricsTemplateId+'/'+ institutionSiteClassGradeId+'/'+ institutionSiteClassId+'/'+ institutionSiteTeacherId;
               break;
           
        }
        
    //    alert('after = '+url);
        
        window.location = url;
    },
    selectRubricAnswer : function (obj, id, color){
        var parent = $(obj).parent();
       
       parent.children().removeClass('selected').css('background-color', 'transparent');
       parent.find('.answer').val(id);
       $(obj).addClass('selected').css('background-color', '#'+color);
  
    },
    overRubricAnswer : function (obj, color){
        
        $(obj).css('background-color', '#'+color);
       // alert('selected');
    },
    outRubricAnswer : function (obj){
        if($(obj).hasClass('selected') == false){
            $(obj).css('background-color', 'transparent');
        }
       // alert('selected');
    }
};