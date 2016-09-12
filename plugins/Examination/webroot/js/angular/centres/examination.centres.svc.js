angular
    .module('examination.centres.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('ExaminationCentresSvc', ExaminationCentresSvc);

ExaminationCentresSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc'];

function ExaminationCentresSvc($http, $q, $filter, KdOrmSvc) {

    var service = {
        init: init,
        getAcademicPeriods: getAcademicPeriods,
        getExamination: getExamination
    };

    var models = {
        ExaminationsTable: 'Examination.Examinations',
        AcademicPeriodsTable: 'AcademicPeriod.AcademicPeriods'
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.init(models);
    };

    function getAcademicPeriods() {
        return AcademicPeriodsTable
            .select()
            .find('visible')
            .find('years')
            .find('visible')
            .find('editable', {isEditable: true})
            .ajax({defer: true})
            ;
    };

    function getExamination(academicPeriodId) {
        return ExaminationsTable
            .select()
            .where({academic_period_id: academicPeriodId})
            .ajax({defer: true})
            ;
    };
};
