angular
    .module('institution.class.students.svc', ['kd.orm.svc'])
    .service('InstitutionClassStudentsSvc', InstitutionClassStudentsSvc);

InstitutionClassStudentsSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc'];

function InstitutionClassStudentsSvc($http, $q, $filter, KdOrmSvc) {

    var service = {
        init: init,
        getClassDetails: getClassDetails,
        getUnassignedStudent: getUnassignedStudent,
        translate: translate
    };

    var models = {
        // InstitutionStudents: 'Institution.Students',
        // InstitutionClassStudents: 'Institution.InstitutionClassStudents',
        InstitutionClasses: 'Institution.InstitutionClasses',
        Users: 'User.Users'
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('ClassStudents');
        KdOrmSvc.init(models);
    };

    function translate(data) {
        KdOrmSvc.init({translation: 'translate'});
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
            .contain(['ClassStudents.Users.Genders', 'ClassStudents.StudentStatuses', 'ClassStudents.EducationGrades'])
            .ajax({success: success, defer:true});
    }

    function getUnassignedStudent(classId) {
        return Users.find('InstitutionStudentsNotInClass', {institution_class_id: classId}).ajax({defer: true});
    }
};
