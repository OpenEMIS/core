angular
    .module('institution.student.attendances.svc', ['kd.data.svc'])
    .service('InstitutionStudentAttendancesSvc', InstitutionStudentAttendancesSvc);

InstitutionStudentAttendancesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionStudentAttendancesSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getDayListOptions: getDayListOptions,
        getClassOptions: getClassOptions,
        getPeriodOptions: getPeriodOptions,
        getClassStudent: getClassStudent
    };

    var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        StudentAttendances: 'Institution.StudentAttendances',
        InstitutionClasses: 'Institution.InstitutionClasses',
        StudentAttendanceMarkTypes: 'Attendance.StudentAttendanceMarkTypes'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentAttendances');
        KdDataSvc.init(models);
    };

    function getAcademicPeriodOptions(institutionId) {
        var success = function(response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                deferred.resolve(periods);
            } else {
                deferred.reject('There was an error when retrieving the academic periods');
            }
        };

        return AcademicPeriods
            .find('PeriodHasClass', {
                institution_id: institutionId
            })
            .ajax({success: success, defer: true});
    }

    function getWeekListOptions(academicPeriodId) {
        var success = function(response, deferred) {
            var academicPeriodObj = response.data.data;
            if (angular.isDefined(academicPeriodObj) && academicPeriodObj.length > 0) {
                var weeks = academicPeriodObj[0].weeks; // find only 1 academic period entity

                if (angular.isDefined(weeks) && weeks.length > 0) {
                    deferred.resolve(weeks);
                } else {
                    deferred.reject('There was an error when retrieving the week list');
                }
            } else {
                deferred.reject('There was an error when retrieving the week list');
            }
        };

        return AcademicPeriods
            .find('WeeksForPeriod', {
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    }

    function getDayListOptions(academicPeriodId, weekId) {
        var success = function(response, deferred) {
            var dayList = response.data.data;
            if (angular.isObject(dayList) && dayList.length > 0) {
                deferred.resolve(dayList);
            } else {
                deferred.reject('There was an error when retrieving the day list');
            }
        };

        return AcademicPeriods
            .find('DaysForPeriodWeek', {
                academic_period_id: academicPeriodId,
                week_id: weekId
            })
            .ajax({success: success, defer: true});
    }

    function getClassOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            var classList = response.data.data;
            if (angular.isObject(classList) && classList.length > 0) {
                deferred.resolve(classList);
            } else {
                deferred.reject('There was an error when retrieving the class list');
            }
        };

        return InstitutionClasses
            .find('ClassesByInstitutionAndAcademicPeriod', {
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
        
        return [];
    }

    function getPeriodOptions(institutionClassId, academicPeriodId) {
        var success = function(response, deferred) {
            var attendancePeriodList = response.data.data;
            if (angular.isObject(attendancePeriodList) && attendancePeriodList.length > 0) {
                deferred.resolve(attendancePeriodList);
            } else {
                deferred.reject('There was an error when retrieving the attendance period list');
            }
        };

        return StudentAttendanceMarkTypes
            .find('PeriodByClass', {
                institution_class_id: institutionClassId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    }

    function getClassStudent(institutionId, institutionClassId, academicPeriodId, day, attendancePeriod) {
        var success = function(response, deferred) {
            var classStudents = response.data.data;

            if (angular.isObject(classStudents) && classStudents.length > 0) {
                deferred.resolve(classStudents);
            } else {
                deferred.reject('There was an error when retrieving the class student list');
            }
        };

        return StudentAttendances
            .find('ClassStudentsWithAbsence', {
                institution_id: institutionId,
                institution_class_id: institutionClassId,
                academic_period_id: academicPeriodId,
                day_id: day,
                attendance_period_id: attendancePeriod
            })
            .ajax({success: success, defer: true});
    }

};