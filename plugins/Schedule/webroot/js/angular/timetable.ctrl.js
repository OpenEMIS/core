angular.module('timetable.ctrl', ['utils.svc', 'alert.svc', 'timetable.svc'])
    .controller('TimetableCtrl', TimetableController);

TimetableController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'TimetableSvc'];

function TimetableController($scope, $q, $window, $http, UtilsSvc, AlertSvc, TimetableSvc) {
    var vm = this;

    const CURRICULUM_LESSON = 1;
    const NON_CURRICULUM_LESSON = 2;

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

    vm.currentSelectedCell = {
        day_of_week: {},
        timeslot: {},
        class: ''
    };

    vm.lessonType = [];
    vm.selectedLessonType = 0;

    /*
        Non-Curriculum Lesson structure
        {
            type: NON_CURRICULUM_LESSON
            name: '',
            institution_room_id: 
        }
    
        Curriculum Lesson structure
        {
            type: CURRICULUM_LESSON
            institution_subject_id: ,
            code_only: bool,
            institution_room_id: 
        }
     */
    vm.currentLessonList = [];

    // ready
    angular.element(document).ready(function () {
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

                return TimetableSvc.getLessonType();
            }, vm.error)
            .then(function (lessonType) {
                console.log('getLessonType', lessonType);
                vm.lessonType = lessonType;
            })
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
                AlertSvc.info($scope, 'Timetable will be automatically saved.');
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
        var selectedClass = vm.getClassName(timeslot, day);

        if (vm.currentSelectedCell.class != selectedClass) {
            vm.onHideSplitter(false, timeslot, day, selectedClass);
            vm.currentLessonList = [];
        }
    }

    vm.onAddLessonType = function() {
        if (vm.selectedLessonType != 0) {
            vm.currentLessonList.push(TimetableSvc.getEmptyLessonObject(vm.selectedLessonType));
        }

        console.log(vm.currentLessonList);

        vm.selectedLessonType = 0;
    }

    vm.onSplitterClose = function() {
        vm.onHideSplitter(true);
    }

    vm.onHideSplitter = function(toggle = false, timeslot = {}, day = {}, selectedClass = '') {
        vm.hideSplitter = toggle.toString();
        vm.currentSelectedCell = {
            day_of_week: day,
            timeslot: timeslot,
            class: selectedClass
        };
    }

    // misc function
    vm.getClassName = function(timeslot, day) {
        return 'lesson-' + timeslot.id + '-' + day.day_of_week;
    }
}