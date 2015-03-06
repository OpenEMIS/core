$(function() {
});

var QualityVisit = {
    updateURL: function(obj) {
        var link = $(obj).closest('form').attr('link');

        var url = getRootURL() + link;
        var selectedDate = $('#date #date').val();// + '-' + $('#dateMonth').val() + '-' + $('#dateDay').val();
        var academicPeriodId = $('#academicPeriodId').val();
        var educationGradeId = $('#educationGradeId').val();
        var institutionSiteSectionId = $('#institutionSiteSectionId').val();
        var institutionSitestaffId = $('#staffId').val();
        var qualityTypeId = $('#qualityTypeId').val();

        url += '/' + selectedDate;

        switch ($(obj).attr('id')) {
            case 'academicPeriodId':
                url += '/' + academicPeriodId;
                break;
            case 'educationGradeId':
                url += '/' + academicPeriodId + '/' + educationGradeId;
                break;
            case 'institutionSiteSectionId':
                url += '/' + academicPeriodId + '/' + educationGradeId + '/' + institutionSiteSectionId;
                break;
            case 'staffId':
                url += '/' + academicPeriodId + '/' + educationGradeId + '/' + institutionSiteSectionId + '/' + institutionSitestaffId;
                break;
            case 'qualityTypeId':
                url += '/' + academicPeriodId + '/' + educationGradeId + '/' + institutionSiteSectionId + '/' + institutionSitestaffId + '/' + qualityTypeId;
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