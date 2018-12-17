angular
    .module('timetable.svc', ['kd.data.svc', 'alert.svc'])
    .service('TimetableSvc', TimetableSvc);

TimetableSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function TimetableSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
    var controllerScope;

    const CURRICULUM_LESSON = 1;
    const NON_CURRICULUM_LESSON = 2;

    var models = {
        ScheduleTimetableTable: 'Schedule.ScheduleTimetables',
        ScheduleTimeslotsTable: 'Schedule.ScheduleTimeslots',
        ScheduleLessonsTable: 'Schedule.ScheduleLessons',
        AcademicPeriodTable: 'AcademicPeriod.AcademicPeriods',
        InstitutionClassGradesTable: 'Institution.InstitutionClassGrades'
    };

    var service = {
        init: init,

        getTimetable: getTimetable,
        getTimeslots: getTimeslots,
        getWorkingDayOfWeek: getWorkingDayOfWeek,
        getLessonType: getLessonType,
        getTimetableStatus: getTimetableStatus,
        getEducationGrade: getEducationGrade,

        getEmptyLessonObject: getEmptyLessonObject,

        saveOverviewData: saveOverviewData 
    };

    return service;

    function init(baseUrl, scope) {
        controllerScope = scope;
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('ScheduleTimetable');
        KdDataSvc.init(models);
    }

    function getTimetable(timetableId) {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleTimetableTable
            .get(timetableId)
            .contain(['AcademicPeriods', 'ScheduleIntervals', 'ScheduleTerms', 'InstitutionClasses'])
            .ajax({success: success, defer: true});
    }

    function getTimeslots(scheduleIntervalId) {
        console.log(scheduleIntervalId);

        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleTimeslotsTable
            .where({institution_schedule_interval_id: scheduleIntervalId})
            .order(['order'])
            .ajax({success: success, defer: true});
    }

    function getWorkingDayOfWeek() {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return AcademicPeriodTable
            .find('workingDayOfWeek')
            .ajax({success: success, defer: true});
    }

    function getLessonType() {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleLessonsTable
            .find('lessonType')
            .ajax({success: success, defer: true});
    }

    function getTimetableStatus() {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleTimetableTable
            .find('timetableStatus')
            .ajax({success: success, defer: true});
    }

    function getEducationGrade(institutionClassId) {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return InstitutionClassGradesTable
            .where({institution_class_id: institutionClassId})
            .ajax({success: success, defer: true});
    }

    function getEmptyLessonObject(lessonType) {
        var lessonObject = {};

        if (lessonType == NON_CURRICULUM_LESSON) {
            lessonObject = {
                type: NON_CURRICULUM_LESSON,
                name: '',
                institution_room_id: -1
            };
        } else { // CURRICULUM_LESSON
            lessonObject = {
                type: CURRICULUM_LESSON,
                institution_subject_id: -1,
                code_only: false,
                institution_room_id: -1
            };
        }

        return lessonObject;
    }

    function saveOverviewData(timetableData) {
        console.log('timetableData', timetableData);
        var saveData = {
            id: timetableData.id,
            name: timetableData.name,
            status: timetableData.status,
            academic_period_id: timetableData.academic_period_id,
            institution_class_id: timetableData.institution_class_id,
            institution_id: timetableData.institution_id,
            institution_schedule_interval_id: timetableData.institution_schedule_interval_id,
            institution_schedule_term_id: timetableData.institution_schedule_term_id
        };

        return ScheduleTimetableTable.edit(saveData);
    }
};