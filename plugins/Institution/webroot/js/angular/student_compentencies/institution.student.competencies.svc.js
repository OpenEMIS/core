angular
    .module('institution.student.competencies.svc', ['kd.data.svc'])
    .service('InstitutionStudentCompetenciesSvc', InstitutionStudentCompetenciesSvc);

InstitutionStudentCompetenciesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionStudentCompetenciesSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getClassDetails: getClassDetails,
        getCompetencyTemplate: getCompetencyTemplate,
        translate: translate,
        saveCompetencyResults: saveCompetencyResults
    };

    var models = {
        InstitutionClasses: 'Institution.InstitutionClasses',
        CompetencyTemplates: 'Competency.CompetencyTemplates'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentCompetencies');
        KdDataSvc.init(models);
    };

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    }

    function getClassDetails(classId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionClasses
            .get(classId)
            .contain(['AcademicPeriods'])
            .ajax({success: success, defer:true});
    }

    function getCompetencyTemplate(academicPeriodId, competencyTemplateId) {
        var primaryKey = KdDataSvc.urlsafeB64Encode(JSON.stringify({academic_period_id: academicPeriodId, id: competencyTemplateId}));
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return CompetencyTemplates
            .get(primaryKey)
            .contain(['Periods', 'Criterias', 'StudentCompetencyResults'])
            .ajax({success: success, defer:true});
    }

    function saveCompetencyResults(data) {
        InstitutionClasses.reset();
        return InstitutionClasses.edit(data);
    }
};