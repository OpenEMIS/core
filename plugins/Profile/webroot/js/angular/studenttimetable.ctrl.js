angular.module('studenttimetable.ctrl', ['utils.svc', 'alert.svc', 'studenttimetable.svc'])
    .controller('StudentTimetableCtrl', TimetableController);

TimetableController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'StudentTimetableSvc'];

function TimetableController($scope, $q, $window, $http, UtilsSvc, AlertSvc, StudentTimetableSvc) {
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
    vm.studentId = '';
    vm.timetableId= '';
    vm.academicPeriodId = '';
    vm.institutionId = '';
    vm.timetableData = {};
    vm.overviewData = {};
    vm.institutionClassData = {};
    vm.scheduleIntervalData = {};
    vm.scheduleTermData = {};
    vm.scheduleTimeslots = [];
    vm.dayOfWeekList = [];
    vm.educationGradeList = [];
    vm.timetableLessons = [];
    vm.timetableCustomizeColors = [];
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
        console.log('@StudentTimetableCtrl::init angular.baseUrl=', angular.baseUrl, 'vm.timetableId=', vm.timetableId); //[TEMP-LOG]
        StudentTimetableSvc.init(angular.baseUrl, $scope);
        UtilsSvc.isAppendLoader(true);
        timeTablePageLoad();
    });

    // error
    vm.error = function (error) {
        AlertSvc.error($scope, error);
        //console.log('error', error);
        return $q.reject(error);
    };

    //[TEMP-LOG] labeled error handler: logs which chain step rejected and captures call stack
    function errAt(step) { //[TEMP-LOG]
        return function(error) { //[TEMP-LOG]
            console.error('@StudentTimetableCtrl::chain error at step=[' + step + '] timetableId=' + vm.timetableId + ' error=', error); //[TEMP-LOG]
            return vm.error(error); //[TEMP-LOG]
        }; //[TEMP-LOG]
    } //[TEMP-LOG]

    function timeTablePageLoad(){
        console.log('@StudentTimetableCtrl::timeTablePageLoad', { timetableId: vm.timetableId, institutionId: vm.institutionId, academicPeriodId: vm.academicPeriodId, studentId: vm.studentId, tableId: vm.tableId }); //[TEMP-LOG]
        StudentTimetableSvc.getTimetable(vm.timetableId)
            .then(function(timetableData) {
                console.log('@StudentTimetableCtrl::getTimetable result', timetableData); //[TEMP-LOG]
                //console.log('getTimetable', timetableData);
                try { //[TEMP-LOG]
                vm.timetableData = timetableData;
                vm.institutionClassData = timetableData.institution_class;
                vm.scheduleIntervalData = timetableData.schedule_interval;
                vm.scheduleTermData = timetableData.schedule_term;
                console.log('@StudentTimetableCtrl::before resetOverviewData timetableId=', vm.timetableId, 'academic_period=', timetableData.academic_period, 'schedule_term=', timetableData.schedule_term, 'institution_class=', timetableData.institution_class, 'schedule_interval=', timetableData.schedule_interval); //[TEMP-LOG]
                vm.resetOverviewData();
                console.log('@StudentTimetableCtrl::after resetOverviewData ok, calling getTimeslots intervalId=', vm.timetableData.institution_schedule_interval_id); //[TEMP-LOG]
                return StudentTimetableSvc.getTimeslots(vm.timetableData.institution_schedule_interval_id);
                } catch(e) { console.error('@StudentTimetableCtrl::getTimetable callback THREW', e, e && e.message, e && e.stack); throw e; } //[TEMP-LOG]
            }, errAt('getTimetable')) //[TEMP-LOG]
            .then(function(timeslotsData) {
                console.log('@StudentTimetableCtrl::getTimeslots result', timeslotsData); //[TEMP-LOG]
                ////console.log('getTimeslots', timeslotsData);
                //POCOR-9594: start - sort timeslots by order before time calculation
                vm.scheduleTimeslots = timeslotsData.slice().sort(function(a, b) { return a.order - b.order; });
                //POCOR-9594: end
                return null; // resolve immediately; time calculation happens in next then
            }, errAt('getTimeslots')) //[TEMP-LOG]
            .then(function() {
                //POCOR-9594: start - calculate start_time/end_time for each timeslot using shift start time
                var shiftInterval = vm.timetableData.schedule_interval;
                var shiftStartTime = (shiftInterval && shiftInterval.shift && shiftInterval.shift.shift_option)
                    ? shiftInterval.shift.shift_option.start_time
                    : '07:00:00';
                var timeParts = (shiftStartTime || '07:00:00').split(':');
                var currentMinutes = parseInt(timeParts[0], 10) * 60 + parseInt(timeParts[1], 10);
                var pad = function(n) { return n < 10 ? '0' + n : '' + n; };
                vm.scheduleTimeslots = vm.scheduleTimeslots.map(function(slot) {
                    var startH = Math.floor(currentMinutes / 60);
                    var startM = currentMinutes % 60;
                    currentMinutes += (slot.interval || 0);
                    var endH = Math.floor(currentMinutes / 60);
                    var endM = currentMinutes % 60;
                    return angular.extend({}, slot, {
                        start_time: pad(startH) + ':' + pad(startM) + ':00',
                        end_time: pad(endH) + ':' + pad(endM) + ':00'
                    });
                });
                //POCOR-9594: end
                return StudentTimetableSvc.getWorkingDayOfWeek();
            }, errAt('timeslotCalc')) //[TEMP-LOG]
            .then(function(workingDayOfWeek) {
                console.log('@StudentTimetableCtrl::getWorkingDayOfWeek result', workingDayOfWeek); //[TEMP-LOG]
                //console.log('getWorkingDayOfWeek', workingDayOfWeek);
                vm.dayOfWeekList = workingDayOfWeek;

                return StudentTimetableSvc.getTimetableLessons(vm.timetableData.id);
            }, errAt('getWorkingDayOfWeek')) //[TEMP-LOG]
            .then(function(allLessons) {
                console.log('@StudentTimetableCtrl::getTimetableLessons result count=', allLessons ? allLessons.length : 'null', allLessons); //[TEMP-LOG]
                ////console.log('getTimetableLessons', allLessons);
                vm.timetableLessons = allLessons;
                return StudentTimetableSvc.getScheduleTimetableCustomizesTable(vm.timetableData.id); //POCOR-9594: fix TimetableSvc (was undefined) and fix args
            }, errAt('getTimetableLessons')) //[TEMP-LOG]
            .then(function(customizeColors) {
                //console.log('customizeColors', customizeColors);
                angular.forEach(customizeColors, function(value, key){
                    vm.timetableCustomizeColors[value.customize_key] = value.customize_value;
                });
                //console.log('timetableCustomizeColors', vm.timetableCustomizeColors);
                return StudentTimetableSvc.getEducationGrade(vm.timetableData.institution_class_id);
            }, errAt('getScheduleTimetableCustomizes')) //[TEMP-LOG]
            .then(function(educationGrades) {
                //console.log('getEducationGrade', educationGrades);
                vm.educationGradeList = educationGrades;
                vm.overviewData.education_grade_name = '';

                for (var i = 0; i < educationGrades.length; i++) {
                    vm.overviewData.education_grade_name += educationGrades[i].grade_name;
                    if (i != educationGrades.length - 1) {
                        vm.overviewData.education_grade_name += ', ';
                    }
                }

                vm.tableReady = true;
                return StudentTimetableSvc.getLessonType();
            })
            .then(function(lessonType) {
                //console.log('getLessonType', lessonType);
                vm.lessonType = lessonType;

                return StudentTimetableSvc.getInstitutionRooms(vm.timetableData.institution_id);
            }, errAt('getEducationGrade')) //[TEMP-LOG]

            .then(function(institutionRooms) {
                //console.log('getInstitutionRooms', institutionRooms);
                vm.institutionRooms = institutionRooms;

                return StudentTimetableSvc.getTimetableStatus();
            }, errAt('getInstitutionRooms')) //[TEMP-LOG]
            .then(function(timetableStatus) {
                //console.log('getTimetableStatus', timetableStatus);
                vm.timetableStatus = timetableStatus;
                ////console.log('timetableDataDetails:', vm.timetableData);
                return StudentTimetableSvc.getInstitutionClassSubjects(vm.timetableData.institution_id, vm.timetableData.institution_class_id, vm.timetableData.academic_period_id);
            }, errAt('getTimetableStatus')) //[TEMP-LOG]
            .then(function(institutionClassSubjects) {
                //console.log('institutionClassSubjects:', institutionClassSubjects);
                vm.institutionClassSubjects = institutionClassSubjects;
            }, errAt('getInstitutionClassSubjects')) //[TEMP-LOG]
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
                AlertSvc.info($scope, 'Timetable will be automatically saved.');
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


    vm.resetOverviewData = function() {

            vm.overviewData.id = vm.timetableData.id;
            vm.overviewData.name = vm.timetableData.name;
            vm.overviewData.status = vm.timetableData.status;
            vm.overviewData.academic_period_id = vm.timetableData.academic_period_id;
            vm.overviewData.institution_class_id = vm.timetableData.institution_class_id;
            vm.overviewData.institution_id = vm.timetableData.institution_id,
            vm.overviewData.institution_schedule_interval_id = vm.timetableData.institution_schedule_interval_id;
            vm.overviewData.institution_schedule_term_id = vm.timetableData.institution_schedule_term_id;

            // for display usage
            vm.overviewData.academic_period_name = vm.timetableData.academic_period.name;
            vm.overviewData.term_name = vm.timetableData.schedule_term.name;
            vm.overviewData.class_name = vm.timetableData.institution_class.name;
            vm.overviewData.interval_name = vm.timetableData.schedule_interval.name;

    };

    vm.ExportTimetable = function () { //POCOR-9594: use per-instance tableId; fix extension .xls → .xlsx
        var tableId = vm.tableId || 'tblTimetable';
        $('#' + tableId).table2excel({
            filename: 'StudentTimetable.xlsx'
        });
    };
}
