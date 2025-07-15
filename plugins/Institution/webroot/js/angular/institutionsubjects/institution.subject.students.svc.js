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
        saveInstitutionSubject: saveInstitutionSubject,
        getConfigItemValue: getConfigItemValue
    };

    var models = {
        InstitutionStaff: 'Institution.Staff',
        Users: 'User.Users',
        Rooms: 'Institution.InstitutionRooms',
        InstitutionSubjects: 'Institution.InstitutionSubjects',
        InstitutionClassStudents: 'Institution.InstitutionClassStudents',
        InstitutionClasses: 'Institution.InstitutionClasses',
        ConfigItemsTable: 'Configuration.ConfigItems'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('SubjectStudents');
        KdDataSvc.init(models);
    };

    function translate(data) {
        KdDataSvc.init({ translation: 'translate' });

        var success = function(response, deferred) {
            deferred.resolve(response.data.translated);
        };

        var error = function(response, deferred) {
            // couldn’t translate, so just return the original payload
            deferred.resolve(data);
        };

        return translation.translate(data, {
            success: success,
            error:   error,
            defer:   true
        });
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

    function getRoomsOptions(institutionSubjectId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return Rooms.find('subjectRoomOptions', {institution_subject_id: institutionSubjectId})
            .ajax({success: success, defer: true});
    }

    function getClassOptions(institutionId, academicPeriodId, educationGradeId, institutionSubjectId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };

        return InstitutionClasses.find('subjectClassOptions', {
            institution_id: institutionId,
            academic_period_id: academicPeriodId,
            grade_id: educationGradeId,
            institution_subject_id: institutionSubjectId
        }).ajax({success: success, defer: true});
    }

    function getConfigItemValue(code) {
        var success = function(response, deferred) {
            var results = response.data.data;
            if (angular.isObject(results) && results.length > 0) {
                var value = results[0].value;
                var defaultValue = results[0].default_value;

                // Use default if value is empty, null, or undefined
                var configItemValue = (value !== undefined && value !== null && value !== "") ? value : defaultValue;

                // Optionally cast to number
                deferred.resolve(Number(configItemValue));
            } else {
                deferred.reject('There is no ' + code + ' configured');
            }
        };

        return ConfigItemsTable
            .where({ code: code })
            .ajax({
                success: success,
                defer: true
            });
    }


    function saveInstitutionSubject(data) {
        return InstitutionSubjects.edit(data);
    }
};
