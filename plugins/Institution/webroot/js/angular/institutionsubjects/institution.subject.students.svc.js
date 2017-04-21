angular
    .module('institution.subject.students.svc', ['kd.orm.svc'])
    .service('InstitutionSubjectStudentsSvc', InstitutionSubjectStudentsSvc);

InstitutionSubjectStudentsSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc'];

function InstitutionSubjectStudentsSvc($http, $q, $filter, KdOrmSvc) {

    var service = {
        init: init,
        getInstitutionSubjectDetails: getInstitutionSubjectDetails,
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
        Users: 'User.Users',

        InstitutionSubjects: 'Institution.InstitutionSubjects'
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('SubjectStudents');
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

    function getInstitutionSubjectDetails(institutionSubjectId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionSubjects
            .get(institutionSubjectId)
            .contain(['Teachers', 'Rooms', 'EducationSubjects', 'AcademicPeriods', 'SubjectStudents.Users.Genders', 'SubjectStudents.StudentStatuses', 'ClassSubjects'])
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
