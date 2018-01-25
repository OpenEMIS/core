angular
    .module('institution.subject.students.svc', ['kd.data.svc'])
    .service('InstitutionSubjectStudentsSvc', InstitutionSubjectStudentsSvc);

InstitutionSubjectStudentsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionSubjectStudentsSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getInstitutionSubjectDetails: getInstitutionSubjectDetails,
        getUnassignedStudent: getUnassignedStudent,
        translate: translate,
        getTeacherOptions: getTeacherOptions,
        getRoomsOptions: getRoomsOptions,
        getClassOptions: getClassOptions,
        saveInstitutionSubject: saveInstitutionSubject
    };

    var models = {
        InstitutionStaff: 'Institution.Staff',
        Users: 'User.Users',
        Rooms: 'Institution.InstitutionRooms',
        InstitutionSubjects: 'Institution.InstitutionSubjects',
        InstitutionClassStudents: 'Institution.InstitutionClassStudents',
        InstitutionClasses: 'Institution.InstitutionClasses'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('SubjectStudents');
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

    function getInstitutionSubjectDetails(institutionSubjectId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionSubjects
            .get(institutionSubjectId)
            .find('subjectDetails')
            .ajax({success: success, defer:true});
    }

    function getUnassignedStudent(institutionSubjectId, academicPeriodId, educationGradeId, institutionClassIds) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };

        var encode = function(textStr) {
            let encoded = encodeURI(btoa(textStr)).replace(/=/gi, "");
            return encoded;
        }

        return InstitutionClassStudents.find('unassignedSubjectStudents', {
                institution_subject_id: institutionSubjectId,
                academic_period_id: academicPeriodId,
                education_grade_id: educationGradeId,
                // POCOR-4371 to encode the array of ids as comma separated values in restfulv2component is not support, will throw error
                institution_class_ids: encode(institutionClassIds)
            }).ajax({success: success, defer: true});
    }

    function getTeacherOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionStaff.find('subjectStaffOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function getRoomsOptions(academicPeriodId, institutionSubjectId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return Rooms.find('subjectRoomOptions', {academic_period_id: academicPeriodId, institution_subject_id: institutionSubjectId}).ajax({success: success, defer: true});
    }

    function getClassOptions(institutionId, academicPeriodId, educationGradeId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };

        return InstitutionClasses.find('classOptions', {
            institution_id: institutionId,
            academic_period_id: academicPeriodId,
            grade_id: educationGradeId
        }).ajax({success: success, defer: true});
    }

    function saveInstitutionSubject(data) {
        return InstitutionSubjects.edit(data);
    }
};
