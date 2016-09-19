angular
    .module('examinations.results.svc', ['kd.orm.svc'])
    .service('ExaminationsResultsSvc', ExaminationsResultsSvc);

ExaminationsResultsSvc.$inject = ['$q', 'KdOrmSvc'];

function ExaminationsResultsSvc($q, KdOrmSvc) {
    var models = {
        AcademicPeriodsTable: 'AcademicPeriod.AcademicPeriods',
        ExaminationsTable: 'Examination.Examinations',
        ExaminationItemsTable: 'Examination.ExaminationItems',
        ExaminationCentresTable: 'Examination.ExaminationCentres'
    };    

    var service = {
        init: init,
        getAcademicPeriods: getAcademicPeriods,
        getExaminations: getExaminations,
        getSubjects: getSubjects,
        getExaminationCentres: getExaminationCentres
    };

    return service;

    function init(baseUrl) {
        console.log('ExaminationsResultsSvc > init()');
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.init(models);
    };

    function getAcademicPeriods() {
        return AcademicPeriodsTable
            .select()
            .find('years')
            .find('visible')
            .find('editable', {isEditable: true})
            .ajax({defer: true});
    };

    function getExaminations(academicPeriodId) {
        return ExaminationsTable
            .select()
            .where({academic_period_id: academicPeriodId})
            .ajax({defer: true});
    };

    function getSubjects(examinationId) {
        var success = function(response, deferred) {
            var examinationSubjects = response.data.data;

            if (angular.isObject(examinationSubjects) && examinationSubjects.length > 0) {
                var subjects = [];
                angular.forEach(examinationSubjects, function(examinationSubject, key) 
                {
                    educationSubject = examinationSubject.education_subject;
                    educationSubject.grading_type = examinationSubject.examination_grading_type;

                    this.push(educationSubject);
                }, subjects);

                deferred.resolve(subjects);
            } else {
                deferred.reject('You need to configure Examination Items first');
            }   
        };

        return ExaminationItemsTable
            .select()
            .contain(['EducationSubjects', 'ExaminationGradingTypes'])
            .where({examination_id: examinationId})
            .ajax({success: success, defer: true});
    };

    function getExaminationCentres(academicPeriodId, examinationId) {
        return ExaminationCentresTable
            .select()
            .where({
                academic_period_id: academicPeriodId,
                examination_id: examinationId
            })
            .ajax({defer: true});
    };
}
