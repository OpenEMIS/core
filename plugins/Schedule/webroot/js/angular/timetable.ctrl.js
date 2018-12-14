angular.module('timetable.ctrl', ['utils.svc', 'alert.svc', 'timetable.svc'])
    .controller('TimetableCtrl', TimetableController);

TimetableController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'TimetableSvc'];

function TimetableController($scope, $q, $window, $http, UtilsSvc, AlertSvc, TimetableSvc) {
    var vm = this;

    vm.action = 'view';
    vm.hideSplitter = 'true';
    vm.splitterContent = 'Overview'; // Overview/Lessons

    vm.tableReady = false;

    vm.timetableId = '';
    vm.timetableData = {};
    vm.institutionClassData = {};
    vm.scheduleIntervalData = {};
    vm.scheduleTermData = {};
    vm.scheduleTimeslots = [];

    vm.dayOfWeekList = [];

    // ready
    angular.element(document).ready(function () {
        // AlertSvc.info($scope, 'Timetable will be automatically saved.');
        console.log('action', vm.action);
        console.log('timetableId', vm.timetableId);

        TimetableSvc.init(angular.baseUrl, $scope);

        UtilsSvc.isAppendLoader(true);
        if (vm.timetableId != null) {
            TimetableSvc.getTimetable(vm.timetableId)
            .then(function(timetableData) {
                console.log('getTimetable', timetableData);
                vm.timetableData = timetableData;
                vm.institutionClassData = timetableData.institution_class;
                vm.scheduleIntervalData = timetableData.schedule_interval;
                vm.scheduleTermData = timetableData.schedule_term;

                return TimetableSvc.getTimeslots(vm.timetableData.institution_schedule_interval_id);
            }, vm.error)
            .then(function(timeslotsData) {
                console.log('getTimeslots', timeslotsData);
                vm.scheduleTimeslots = timeslotsData;

                return TimetableSvc.getWorkingDayOfWeek();
            }, vm.error)
            .then(function(workingDayOfWeek) {
                console.log('getWorkingDayOfWeek', workingDayOfWeek);
                vm.dayOfWeekList = workingDayOfWeek;
                vm.tableReady = true;
            }, vm.error)
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
            })
        }

    });

    // error
    vm.error = function (error) {
        AlertSvc.error($scope, error);
        return $q.reject(error);
    }

    // button events
    vm.onInfoClicked = function() {
        vm.splitterContent = 'Overview';
        vm.hideSplitter = 'false';
    }

    vm.onTimeslotCellClicked = function(timeslot, day) {
        vm.splitterContent = 'Lessons';
        vm.hideSplitter = 'false';

        console.log('cell clicked!', timeslot, day);
    }

    vm.onSplitterClose = function() {
        vm.hideSplitter = 'true';
    }
}