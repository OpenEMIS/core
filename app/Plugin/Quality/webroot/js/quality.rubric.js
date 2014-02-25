$(function() {});

var QualityRubric = {
    updateURL : function (obj){
        var link = $(obj).closest('form').attr('link');
        
        var url = getRootURL() + link;
        
        
        //var selectedDate = $('#dateYear').val()+ '-'+$('#dateMonth').val()+ '-'+$('#dateDay').val();
        var schoolYearId = $('#schoolYearId').val();
        var rubricsTemplateId = $('#rubricsTemplateId').val();
        var institutionSiteClassesId = $('#institutionSiteClassesId').val();
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
           case 'institutionSiteClassesId':
                url += '/'+ schoolYearId+'/'+ rubricsTemplateId+'/'+ institutionSiteClassesId;
               break;
           case 'institutionSiteTeacherId':
                url += '/'+ schoolYearId+'/'+ rubricsTemplateId+'/'+ institutionSiteClassesId+'/'+ institutionSiteTeacherId;
               break;
           
        }
        
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