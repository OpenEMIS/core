angular
    .module('timetable.svc', ['kd.data.svc', 'alert.svc'])
    .service('TimetableSvc', TimetableSvc);

TimetableSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function TimetableSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
    var controllerScope;

    var models = {
        ScheduleTimetableTable: 'Schedule.ScheduleTimetableOverview',
        ScheduleTimeslotsTable: 'Schedule.ScheduleTimeslots',
        AcademicPeriodTable: 'AcademicPeriod.AcademicPeriods'
    };

    var service = {
        init: init,

        getTimetable: getTimetable,
        getTimeslots: getTimeslots,
        getWorkingDayOfWeek: getWorkingDayOfWeek
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
            .contain(['ScheduleIntervals', 'ScheduleTerms', 'InstitutionClasses'])
            .ajax({success: success, defer: true});
    }

    function getTimeslots(scheduleIntervalId) {
        console.log(scheduleIntervalId);

        var success = function(response, deferred) {
            console.log('response', response);
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

};