angular.module('institution.assessments.archive.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.assessments.archive.svc'])
    .controller('InstitutionAssessmentsArchiveCtrl', InstitutionAssessmentsArchiveController);

InstitutionAssessmentsArchiveController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionAssessmentsArchiveSvc'];

function InstitutionAssessmentsArchiveController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionAssessmentsArchiveSvc) {
    var vm = this;
    vm.action = 'view';
    vm.excelUrl = '';

    vm.institutionId;
    vm.schoolClosed = true;

    vm.absenceTypeOptions = [];
    vm.studentAbsenceReasonOptions = [];
    vm.isMarked = false;

    // Dashboards
    vm.totalStudents = '-';
    vm.presentCount = '-';
    vm.absenceCount = '-';
    vm.lateCount = '-';

    vm.allAttendances = '-';
    vm.allPresentCount = '-';
    vm.allAbsenceCount = '-';
    vm.allLateCount = '-';
    vm.exportexcel = '';
    vm.excelExportAUrl = '';

    // Options
    vm.academicPeriodOptions = [];
    vm.selectedAcademicPeriod = '';

    vm.weekListOptions = [];
    vm.selectedWeek = '';
    vm.selectedWeekStartDate = '';
    vm.selectedWeekEndDate = '';

    vm.dayListOptions = [];
    vm.selectedDay = '';

    vm.classListOptions = [];
    vm.subjectListOptions = [];
    vm.selectedClass = '';

    vm.attendancePeriodOptions = [];
    vm.selectedAttendancePeriod = '';

    vm.classStudentList = [];
    vm.isMarkableSubjectAttendance = false;

    vm.superAdmin = 1;
    vm.permissionView = 1;
    vm.permissionEdit = 1;

    // gridOptions
    vm.gridReady = false;
    vm.gridOptions = {
        columnDefs: [],
        rowData: [],
        headerHeight: 38,
        rowHeight: 80,
        minColWidth: 100,
        enableColResize: true,
        enableSorting: true,
        unSortIcon: true,
        enableFilter: true,
        suppressMenuHide: true,
        suppressMovableColumns: true,
        suppressContextMenu: true,
        stopEditingWhenGridLosesFocus: true,
        ensureDomOrder: true,
        suppressCellSelection: true,
        onGridSizeChanged: function() {
            this.api.sizeColumnsToFit();
        },
        onGridReady: function() {
            if (angular.isDefined(vm.gridOptions.api)) {
                vm.setGridData();
                vm.setColumnDef();
            }
        },
        context: {
            scope: $scope,
            mode: vm.action,
            // absenceTypes: vm.absenceTypeOptions,
            // studentAbsenceReasons: vm.studentAbsenceReasonOptions,
            // date: vm.selectedDay,
            // schoolClosed: vm.schoolClosed,
            // week: vm.selectedWeek,
            // period: vm.selectedAttendancePeriod,
            // isMarked: vm.isMarked,
            // subject_id: vm.selectedSubject
        },
        // getRowHeight: getRowHeight,
    };

    // ready
    angular.element(document).ready(function () {
        InstitutionAssessmentsArchiveSvc.init(angular.baseUrl, $scope);
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;

        UtilsSvc.isAppendLoader(true);
        if (vm.institutionId != null) {
            InstitutionAssessmentsArchiveSvc.getTranslatedText().
            then(function(isMarked) {
                vm.updateIsMarked(isMarked);
                return InstitutionAssessmentsArchiveSvc.getClassStudent(vm.getClassStudentParams());
            }, vm.error)
            .then(function(classStudents) {
                vm.updateClassStudentList(classStudents);
            }, vm.error)
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
                vm.initGrid();
                vm.setGridData();
                vm.setColumnDef();
            });
        }
    });

    // error
    vm.error = function (error, test) {
        return $q.reject(error);
    }

    vm.updateClassStudentList = function(classStudents) {
        console.log("classStudentsOne")
        console.log(classStudents)
        vm.classStudents = [];
        vm.classStudentList = [...classStudents.data];
    }

    vm.updateIsMarked = function(isMarked) {
        vm.isMarked = isMarked;
        vm.gridOptions.context.isMarked = isMarked;
    };

    // grid
    vm.initGrid = function() {
        AggridLocaleSvc.getTranslatedGridLocale().then(
            function(localeText) {
                vm.gridOptions.localeText = localeText;
                vm.gridReady = true;
            },
            function(error) {
                vm.gridReady = true;
            }
        );
    }

    function getRowHeight(params) {
        return params.data.rowHeight;
    }

    
    vm.setGridData = function() {
        if (angular.isDefined(vm.gridOptions.api)) {
            vm.setRowDatas(vm.classStudentList);
        }
    }

    vm.setRowDatas = function(studentList) {
        vm.gridOptions.api.setRowData(studentList);
        // vm.gridOptions.rowData = studentList        
    }

    vm.setColumnDef = function() {
        var columnDefs = [];
        if (vm.selectedDay != -1) {
            columnDefs = InstitutionAssessmentsArchiveSvc.getDummyData();
        } else {
            columnDefs = InstitutionAssessmentsArchiveSvc.getDummyData();
        }

        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setColumnDefs(columnDefs);
            vm.gridOptions.api.sizeColumnsToFit();
        } else {
            vm.gridOptions.columnDefs = columnDefs;
        }

    }

    // params
    vm.getClassStudentParams = function() {

        vm.excelExportAUrl = vm.exportexcel
                             +'?institution_id='+ vm.institutionId+
                            '&institution_class_id='+ vm.selectedClass+
                            '&academic_period_id='+ vm.selectedAcademicPeriod+
                            '&day_id='+ vm.selectedDay+
                            '&attendance_period_id='+ vm.selectedAttendancePeriod+
                            '&week_start_day='+ vm.selectedWeekStartDate+
                            '&week_end_day='+ vm.selectedWeekEndDate+
                            '&week_id='+ vm.selectedWeek
        
        return {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            week_start_day: vm.selectedWeekStartDate,
            week_end_day: vm.selectedWeekEndDate,
            week_id: vm.selectedWeek,
            subject_id: vm.selectedSubject
        };
    }

    // button events
    vm.onEditClick = function() {
        vm.action = 'edit';
        vm.gridOptions.context.mode = vm.action;
        vm.setColumnDef();
        AlertSvc.info($scope, 'Attendances will be automatically saved.');
        InstitutionAssessmentsArchiveSvc.savePeriodMarked(vm.getPeriodMarkedParams(), $scope);
    };

    vm.onBackClick = function() {
        vm.action = 'index';
        vm.gridOptions.context.mode = vm.action;
        UtilsSvc.isAppendLoader(true);
        InstitutionAssessmentsArchiveSvc.getIsMarked(vm.getIsMarkedParams())
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            vm.setColumnDef();
            AlertSvc.reset($scope);
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    };

}
