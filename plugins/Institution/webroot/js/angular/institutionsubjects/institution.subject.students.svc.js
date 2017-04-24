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
        getRoomsOptions: getRoomsOptions,
        saveClass: saveClass
    };

    var models = {
        InstitutionStaff: 'Institution.Staff',
        InstitutionShifts: 'Institution.InstitutionShifts',
        Users: 'User.Users',
        Rooms: 'Institution.InstitutionRooms',
        InstitutionSubjects: 'Institution.InstitutionSubjects',
        InstitutionClassStudents: 'Institution.InstitutionClassStudents'
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
        return InstitutionStaff.find('subjectStaffOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function getRoomsOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return Rooms.find('subjectRoomOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function saveClass(data) {
        return [];
    }
};
