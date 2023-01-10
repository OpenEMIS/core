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
    
    function timeTablePageLoad(){       
        StudentTimetableSvc.getTimetable(vm.timetableId)
            .then(function(timetableData) {
                //console.log('getTimetable', timetableData);
                vm.timetableData = timetableData;
                vm.institutionClassData = timetableData.institution_class;
                vm.scheduleIntervalData = timetableData.schedule_interval;
                vm.scheduleTermData = timetableData.schedule_term;
                vm.resetOverviewData();
                return StudentTimetableSvc.getTimeslots(vm.timetableData.institution_schedule_interval_id);
            }, vm.error)
            .then(function(timeslotsData) {
                ////console.log('getTimeslots', timeslotsData);
                vm.scheduleTimeslots = timeslotsData;

                return StudentTimetableSvc.getWorkingDayOfWeek();
            }, vm.error)
            .then(function(workingDayOfWeek) {
                //console.log('getWorkingDayOfWeek', workingDayOfWeek);
                vm.dayOfWeekList = workingDayOfWeek;

                return StudentTimetableSvc.getTimetableLessons(vm.timetableData.id);
            }, vm.error)
            .then(function(allLessons) {
                ////console.log('getTimetableLessons', allLessons);
                vm.timetableLessons = allLessons;
                return TimetableSvc.getScheduleTimetableCustomizesTable(vm.institutionId, vm.academicPeriodId);               
            }, vm.error)
            .then(function(customizeColors) {
                //console.log('customizeColors', customizeColors);
                angular.forEach(customizeColors, function(value, key){
                    vm.timetableCustomizeColors[value.customize_key] = value.customize_value;
                });
                //console.log('timetableCustomizeColors', vm.timetableCustomizeColors);
                return StudentTimetableSvc.getEducationGrade(vm.timetableData.institution_class_id);
            }, vm.error)
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
            }, vm.error)
            
            .then(function(institutionRooms) {
                //console.log('getInstitutionRooms', institutionRooms);
                vm.institutionRooms = institutionRooms;

                return StudentTimetableSvc.getTimetableStatus();
            }, vm.error)
            .then(function(timetableStatus) {
                //console.log('getTimetableStatus', timetableStatus);
                vm.timetableStatus = timetableStatus;
                ////console.log('timetableDataDetails:', vm.timetableData);               
                return StudentTimetableSvc.getInstitutionClassSubjects(vm.timetableData.institution_id, vm.timetableData.institution_class_id, vm.timetableData.academic_period_id);
            }, vm.error)
            .then(function(institutionClassSubjects) {
                //console.log('institutionClassSubjects:', institutionClassSubjects);
                vm.institutionClassSubjects = institutionClassSubjects;
            }, vm.error)
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
	
	vm.ExportTimetable = function () {
        $("#tblTimetable").table2excel({
            filename: "StudentTimetable.xls"
        });
    };
}