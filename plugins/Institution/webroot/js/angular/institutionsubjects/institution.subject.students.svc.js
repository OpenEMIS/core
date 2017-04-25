angular
    .module('institution.subject.students.svc', ['kd.orm.v2.svc'])
    .service('InstitutionSubjectStudentsSvc', InstitutionSubjectStudentsSvc);

InstitutionSubjectStudentsSvc.$inject = ['$http', '$q', '$filter', 'KdOrmV2Svc'];

function InstitutionSubjectStudentsSvc($http, $q, $filter, KdOrmV2Svc) {

    var service = {
        init: init,
        getInstitutionSubjectDetails: getInstitutionSubjectDetails,
        getUnassignedStudent: getUnassignedStudent,
        translate: translate,
        getTeacherOptions: getTeacherOptions,
        getRoomsOptions: getRoomsOptions,
        saveInstitutionSubject: saveInstitutionSubject
    };

    var models = {
        InstitutionStaff: 'Institution.Staff',
        Users: 'User.Users',
        Rooms: 'Institution.InstitutionRooms',
        InstitutionSubjects: 'Institution.InstitutionSubjects',
        InstitutionClassStudents: 'Institution.InstitutionClassStudents'
    };

    return service;

    function init(baseUrl) {
        KdOrmV2Svc.base(baseUrl);
        KdOrmV2Svc.controllerAction('SubjectStudents');
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

    function getInstitutionSubjectDetails(institutionSubjectId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionSubjects
            .get(institutionSubjectId)
            .contain(['SubjectStaff.Users', 'Rooms', 'EducationSubjects', 'AcademicPeriods', 'SubjectStudents.Users.Genders', 'SubjectStudents.StudentStatuses', 'ClassSubjects'])
            .ajax({success: success, defer:true});
    }

    function getUnassignedStudent(institutionSubjectId, academicPeriodId, educationGradeId, institutionClassIds) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionClassStudents.find('unassignedSubjectStudents', {
                institution_subject_id: institutionSubjectId,
                academic_period_id: academicPeriodId,
                education_grade_id: educationGradeId,
                institution_class_ids: institutionClassIds
            }).ajax({success: success, defer: true});
    }

    function getTeacherOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionStaff.find('subjectStaffOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function getRoomsOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return Rooms.find('subjectRoomOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function saveInstitutionSubject(data) {
        return InstitutionSubjects.edit(data);
    }
};
