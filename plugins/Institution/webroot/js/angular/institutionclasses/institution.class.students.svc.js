angular
    .module('institution.class.students.svc', ['kd.orm.v2.svc'])
    .service('InstitutionClassStudentsSvc', InstitutionClassStudentsSvc);

InstitutionClassStudentsSvc.$inject = ['$http', '$q', '$filter', 'KdOrmV2Svc'];

function InstitutionClassStudentsSvc($http, $q, $filter, KdOrmV2Svc) {

    var service = {
        init: init,
        getClassDetails: getClassDetails,
        getUnassignedStudent: getUnassignedStudent,
        translate: translate,
        getInstitutionShifts: getInstitutionShifts,
        getTeacherOptions: getTeacherOptions,
        saveClass: saveClass
    };

    var models = {
        InstitutionStaff: 'Institution.Staff',
        InstitutionClasses: 'Institution.InstitutionClasses',
        InstitutionShifts: 'Institution.InstitutionShifts',
        Users: 'User.Users'
    };

    return service;

    function init(baseUrl) {
        KdOrmV2Svc.base(baseUrl);
        KdOrmV2Svc.controllerAction('ClassStudents');
        KdOrmV2Svc.init(models);
    };

    function translate(data) {
        KdOrmV2Svc.init({translation: 'translate'});
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
            .contain(['ClassStudents.Users.Genders', 'ClassStudents.StudentStatuses', 'ClassStudents.EducationGrades', 'AcademicPeriods', 'InstitutionSubjects'])
            .ajax({success: success, defer:true});
    }

    function getUnassignedStudent(classId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return Users.find('InstitutionStudentsNotInClass', {institution_class_id: classId}).ajax({success: success, defer: true});
    }

    function getInstitutionShifts(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionShifts.find('shiftOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function getTeacherOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionStaff.find('classStaffOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function saveClass(data) {
        InstitutionClasses.reset();
        return InstitutionClasses.edit(data);
    }
};
