$(function() {
});

var QualityVisit = {
    updateURL: function(obj) {
        var link = $(obj).closest('form').attr('link');

        var url = getRootURL() + link;


        var selectedDate = $('#dateYear').val() + '-' + $('#dateMonth').val() + '-' + $('#dateDay').val();
        var schoolYearId = $('#schoolYearId').val();
        var educationGradeId = $('#educationGradeId').val();
        var institutionSiteClassesId = $('#institutionSiteClassesId').val();
        var institutionSiteTeacherId = $('#institutionSiteTeacherId').val();
        var qualityTypeId = $('#qualityTypeId').val();

        url += '/' + selectedDate;

        switch ($(obj).attr('id')) {
            case 'schoolYearId':
                url += '/' + schoolYearId;
                break;
            case 'educationGradeId':
                url += '/' + schoolYearId + '/' + educationGradeId;
                break;
            case 'institutionSiteClassesId':
                url += '/' + schoolYearId + '/' + educationGradeId + '/' + institutionSiteClassesId;
                break;
            case 'institutionSiteTeacherId':
                url += '/' + schoolYearId + '/' + educationGradeId + '/' + institutionSiteClassesId + '/' + institutionSiteTeacherId;
                break;
            case 'qualityTypeId':
                url += '/' + schoolYearId + '/' + educationGradeId + '/' + institutionSiteClassesId + '/' + institutionSiteTeacherId + '/' + qualityTypeId;
                break;
        }

        window.location = url;
    },
    addExtraAttachment: function() {
//        /alert('alert');
        $.ajax({
            type: "POST",
            url: getRootURL() + 'Quality/qualityVisitAjaxAddAttachment',
           // data: {id: id, last_id: $('#last_id').val()},
            success: function(data) {
                $('#attachmensWrapper').append(data);
            }
        });
    },
    removeAttachment : function (obj){
      //  $(obj).parent().parent().hide();
      
  //    alert($(obj).attr('id'));
      
      
        $.ajax({
            type: "POST",
            url: getRootURL() + 'Quality/qualityVisitAjaxRemoveAttachment/'+$(obj).attr('id'),
           // data: {id: id, last_id: $('#last_id').val()},
            success: function(data) {
                $(obj).parent().parent().remove();
            }
        });
    }
};