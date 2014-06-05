$(function() {
});

var QualityVisit = {
    updateURL: function(obj) {
        var link = $(obj).closest('form').attr('link');

        var url = getRootURL() + link;
        var selectedDate = $('#date #date').val();// + '-' + $('#dateMonth').val() + '-' + $('#dateDay').val();
        var schoolYearId = $('#schoolYearId').val();
        var educationGradeId = $('#educationGradeId').val();
        var institutionSiteClassId = $('#institutionSiteClassId').val();
        var institutionSitestaffId = $('#staffId').val();
        var qualityTypeId = $('#qualityTypeId').val();

        url += '/' + selectedDate;

        switch ($(obj).attr('id')) {
            case 'schoolYearId':
                url += '/' + schoolYearId;
                break;
            case 'educationGradeId':
                url += '/' + schoolYearId + '/' + educationGradeId;
                break;
            case 'institutionSiteClassId':
                url += '/' + schoolYearId + '/' + educationGradeId + '/' + institutionSiteClassId;
                break;
            case 'staffId':
                url += '/' + schoolYearId + '/' + educationGradeId + '/' + institutionSiteClassId + '/' + institutionSitestaffId;
                break;
            case 'qualityTypeId':
                url += '/' + schoolYearId + '/' + educationGradeId + '/' + institutionSiteClassId + '/' + institutionSitestaffId + '/' + qualityTypeId;
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