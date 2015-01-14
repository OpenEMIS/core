$(function() {
});

var QualityRubric = {
    updateURL: function(obj) {
        var link = $(obj).closest('form').attr('link');

        var url = getRootURL() + link;

        //   alert('b4 = '+url);
        //var selectedDate = $('#dateYear').val()+ '-'+$('#dateMonth').val()+ '-'+$('#dateDay').val();
        var academicPeriodId = $('#academicPeriodId').val();
        var rubricsTemplateId = $('#rubricsTemplateId').val();
        var institutionSiteClassGradeId = $('#institutionSiteClassGradeId').val();
        var institutionSiteClassId = $('#institutionSiteClassId').val();
        var institutionSitestaffId = $('#institutionSitestaffId').val();
        //var qualityTypeId = $('#qualityTypeId').val();

        //url += '/'+ selectedDate;

        switch ($(obj).attr('id')) {
            case 'academicPeriodId':
                url += '/' + academicPeriodId;
                break;
            case 'institutionSiteClassGradeId':
                url += '/' + academicPeriodId + '/' + institutionSiteClassGradeId;
                break;
            case 'institutionSiteClassId':
                url += '/' + academicPeriodId + '/' + institutionSiteClassGradeId + '/' + institutionSiteClassId;
                break;
            case 'rubricsTemplateId':
                url += '/' + academicPeriodId + '/' + institutionSiteClassGradeId + '/' + institutionSiteClassId + '/' + rubricsTemplateId;
                break;
            case 'institutionSitestaffId':
                url += '/' + academicPeriodId + '/' + institutionSiteClassGradeId + '/' + institutionSiteClassId + '/' + rubricsTemplateId + '/' + institutionSitestaffId;
                break;

        }

        //    alert('after = '+url);

        window.location = url;
    },
    selectRubricAnswer: function(obj, id, color) {
        var parent = $(obj).parent();

        parent.children().removeClass('selected').css('background-color', 'transparent');
        parent.find('.answer').val(id);
        $(obj).addClass('selected').css('background-color', '#' + color);

    },
    overRubricAnswer: function(obj, color) {

        $(obj).css('background-color', '#' + color);
        // alert('selected');
    },
    outRubricAnswer: function(obj) {
        if ($(obj).hasClass('selected') == false) {
            $(obj).css('background-color', 'transparent');
        }
        // alert('selected');
    }
};