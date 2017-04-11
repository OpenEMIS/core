angular
    .module('examination.centres.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('ExaminationCentresSvc', ExaminationCentresSvc);

ExaminationCentresSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc'];

function ExaminationCentresSvc($http, $q, $filter, KdOrmSvc) {

    var service = {
        init: init,
        getAcademicPeriods: getAcademicPeriods,
        getExamination: getExamination,
        getInstitutions: getInstitutions,
        getSubjects: getSubjects,
        getSpecialNeedTypes: getSpecialNeedTypes
    };

    var models = {
        ExaminationsTable: 'Examination.Examinations',
        AcademicPeriodsTable: 'AcademicPeriod.AcademicPeriods',
        InstitutionsTable: 'Institution.Institutions',
        ExaminationItemsTable: 'Examination.ExaminationItems',
        SpecialNeedTypesTable: 'FieldOption.SpecialNeedTypes'
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

    function getInstitutions(params) {
        var examinationId = params.conditions.examination_id;
        return InstitutionsTable
            .select()
            .find('NotExamCentres', {examination_id: examinationId.toString()})
            .limit(10)
            .ajax({defer: true});
    }

    function getSubjects(examinationId) {
         return ExaminationItemsTable
            .select()
            .find('subjects', {examination_id: examinationId.toString()})
            .ajax({defer: true});
    }

    function getSpecialNeedTypes() {
         return SpecialNeedTypesTable
            .select()
            .find('visibleNeedTypes')
            .ajax({defer: true});
    }
};
