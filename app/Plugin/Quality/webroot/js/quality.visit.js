$(function() {});

var QualityVisit = {
    updateURL : function (obj){
        var link = $(obj).closest('form').attr('link');
        
        var url = getRootURL() + link;
        
        
        var selectedDate = $('#dateYear').val()+ '-'+$('#dateMonth').val()+ '-'+$('#dateDay').val();
        var schoolYearId = $('#schoolYearId').val();
        var educationGradeId = $('#educationGradeId').val();
        var institutionSiteClassesId = $('#institutionSiteClassesId').val();
        var institutionSiteTeacherId = $('#institutionSiteTeacherId').val();
        var qualityTypeId = $('#qualityTypeId').val();
        
        url += '/'+ selectedDate;
        
        switch($(obj).attr('id')){
            case 'schoolYearId':
                url += '/'+ schoolYearId;
               break;
           case 'educationGradeId':
                url += '/'+ schoolYearId+'/'+ educationGradeId;
               break;
           case 'institutionSiteClassesId':
                url += '/'+ schoolYearId+'/'+ educationGradeId+'/'+ institutionSiteClassesId;
               break;
           case 'institutionSiteTeacherId':
                url += '/'+ schoolYearId+'/'+ educationGradeId+'/'+ institutionSiteClassesId+'/'+ institutionSiteTeacherId;
               break;
           case 'qualityTypeId':
                url += '/'+ schoolYearId+'/'+ educationGradeId+'/'+ institutionSiteClassesId+'/'+ institutionSiteTeacherId+'/'+ qualityTypeId;
               break;
        }
        
        window.location = url;
    }
};