angular.module('timetable.ctrl', ['utils.svc', 'alert.svc', 'timetable.svc'])
    .controller('TimetableCtrl', TimetableController);

TimetableController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'TimetableSvc'];

function TimetableController($scope, $q, $window, $http, UtilsSvc, AlertSvc, TimetableSvc) {
    var vm = this;

    // const
    vm.CURRICULUM_LESSON = 1;
    vm.NON_CURRICULUM_LESSON = 2;
    vm.DELETE_LESSON = -1;

    vm.SPLITTER_OVERVIEW = 'Overview';
    vm.SPLITTER_LESSONS = 'Lessons';

    // config
    vm.action = 'view';
    vm.hideSplitter = 'true';
    vm.splitterContent = vm.SPLITTER_OVERVIEW; // Overview/Lessons
    vm.tableReady = true;

    // options and data
    vm.shiftDefaultId = '';
    vm.staffId = '';
    vm.academicPeriodId = '';
    vm.institutionId = '';
    vm.timetableData = {};
    vm.institutionClassData = {};
    vm.scheduleIntervalData = {};
    vm.scheduleTermData = {};
    vm.scheduleTimeslots = [];
    vm.dayOfWeekList = [];
    vm.educationGradeList = [];
    vm.timetableLessons = [];
    vm.scheduleTimeslotsId = [];

    // for lessons data - display and saving
    vm.lessonList = {};

    vm.lessonType = [];
    //vm.institutionRooms = [];
    vm.institutionSubjects = [];
    vm.institutionClassSubjects = [];
    
    vm.currentLessonList = [];

    // ready
    angular.element(document).ready(function () {        
        console.log('shiftDefaultId', vm.shiftDefaultId);

        TimetableSvc.init(angular.baseUrl, $scope);
        UtilsSvc.isAppendLoader(true);
        if (vm.shiftDefaultId != null) {
            timeTablePageLoad();
        }

    });

    // error
    vm.error = function (error) {
        AlertSvc.error($scope, error);
        console.log('error', error);
        return $q.reject(error);
    };
    
    function timeTablePageLoad(){
        TimetableSvc.getIntervaltable(vm.shiftDefaultId, vm.academicPeriodId, vm.institutionId)
            .then(function(scheduleIntervalData) {
                console.log('scheduleIntervalData', scheduleIntervalData);
                vm.scheduleIntervalData = scheduleIntervalData;                
                return TimetableSvc.getTimeslots(vm.scheduleIntervalData[0].id);
            }, vm.error)
            .then(function(timeslotsData) {
                console.log('getTimeslots', timeslotsData);
                vm.scheduleTimeslots = timeslotsData;
                return TimetableSvc.getWorkingDayOfWeek();
            }, vm.error)
            .then(function(workingDayOfWeek) {
                console.log('getWorkingDayOfWeek', workingDayOfWeek);
                vm.dayOfWeekList = workingDayOfWeek;
                return TimetableSvc.getTimetableLessons(vm.scheduleIntervalData[0].id, vm.staffId);
            }, vm.error)
            .then(function(allLessons) {
                console.log('getTimetableLessons', allLessons);
                vm.timetableLessons = allLessons;
            }, vm.error)
            .finally(function() {
                
                UtilsSvc.isAppendLoader(false);
            });
    } 
  
    vm.getLessonTitle = function(lessonTypeId) {
        for (var lesson in vm.lessonType) {
            if (vm.lessonType[lesson].id == lessonTypeId) {
                return vm.lessonType[lesson].title;
            }
        }
        return '';
    };

    vm.updateTimetableData = function(field, value) {
        vm.timetableData[field] = value;
    };  
    
    vm.toTimeAmPm = function(timeString){
        var timeTokens = timeString.split(':');
        return new Date(1970,0,1, timeTokens[0], timeTokens[1], timeTokens[2]);
    };
}