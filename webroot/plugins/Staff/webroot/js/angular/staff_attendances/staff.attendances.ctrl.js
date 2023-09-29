angular.module('staff.attendances.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'staff.attendances.svc'])
    .controller('StaffAttendancesCtrl', StaffAttendancesController);

StaffAttendancesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'StaffAttendancesSvc'];

function StaffAttendancesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, StaffAttendancesSvc) {
    var vm = this;

    vm.action = 'view';
    vm.staffId;
    vm.institutionId = '';

    vm.academicPeriodOptions = [];
    vm.selectedAcademicPeriod = '';

    vm.weekListOptions = [];
    vm.selectedWeek = '';
    vm.selectedStartDate = '';
    vm.selectedEndDate = '';

    // gridOptions
    vm.gridReady = false;
    vm.gridOptions = {
        context: {
            period: vm.selectedAcademicPeriod,
            action: vm.action,
            scope: $scope,
        },
        columnDefs: [],
        rowData: [],
        headerHeight: 38,
        rowHeight: 50,
        minColWidth: 200,
        enableColResize: true,
        enableSorting: false,
        unSortIcon: true,
        enableFilter: true,
        suppressMenuHide: true,
        suppressMovableColumns: true,
        suppressContextMenu: true,
        stopEditingWhenGridLosesFocus: true,
        ensureDomOrder: true,
        suppressCellSelection: true,
        gridAutoHeight: true,
        onGridSizeChanged: function() {
            this.api.sizeColumnsToFit();
        },
        onGridReady: function() {
            if (angular.isDefined(vm.gridOptions.api)) {
                vm.setGridData();
                vm.setColumnDef();
            }
        },
    };

    angular.element(document).ready(function () {
        StaffAttendancesSvc.init(angular.baseUrl);
        vm.action = 'view';
        vm.gridOptions.context.action = vm.action;

        UtilsSvc.isAppendLoader(true);
        StaffAttendancesSvc.getTranslatedText().
        then(function(isTranslated) {
            return StaffAttendancesSvc.getAcademicPeriodOptions();
        }, vm.error)
        .then(function(academicPeriods) {
            vm.setAcademicPeriodList(academicPeriods);
            return StaffAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod);
        }, vm.error)
        .then(function(weekList) {
            vm.setWeekList(weekList);
            return StaffAttendancesSvc.getStaffAttendances(vm.getStaffAttendancesParams());
        }, vm.error)
        .then(function(staffAttendances) {
            vm.setStaffAttendances(staffAttendances);
            vm.setGridData();
            vm.setColumnDef();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    });

    // error
    vm.error = function (error) {
        console.log(error);
    }

    vm.setAcademicPeriodList = function(academicPeriods) {
        vm.academicPeriodOptions = academicPeriods;
        if (academicPeriods.length > 0) {
            angular.forEach(academicPeriods, function(academicPeriod) {
                if (academicPeriod.current == 1) {
                   vm.selectedAcademicPeriod = academicPeriod.id;
                   vm.gridOptions.context.period = vm.selectedAcademicPeriod;
                }
            });
        }
    }

    vm.setWeekList = function(weekList) {
        vm.weekListOptions = weekList;
        if (weekList.length > 0) {
            angular.forEach(weekList, function(week) {
                if (week.selected == true) {
                   vm.selectedWeek = week.id;
                   vm.selectedStartDate = week.start_day;
                   vm.selectedEndDate = week.end_day;
                }
            });
        }
    }

    //onChange
    vm.changeAcademicPeriod = function() {
        UtilsSvc.isAppendLoader(true);
        StaffAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod)
        .then(function(weekListOptions) {
            vm.gridOptions.context.period = vm.selectedAcademicPeriod;
            vm.setWeekList(weekListOptions);
            return StaffAttendancesSvc.getStaffAttendances(vm.getStaffAttendancesParams());
        }, vm.error)
        .then(function(staffAttendances) {
            console.log(staffAttendances);
            vm.setStaffAttendances(staffAttendances);
            vm.setGridData();
            vm.setColumnDef();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeWeek = function() {
        UtilsSvc.isAppendLoader(true);
        var weekObj = vm.weekListOptions.find(obj => obj.id == vm.selectedWeek);
        vm.selectedStartDate = weekObj.start_day;
        vm.selectedEndDate = weekObj.end_day;
        StaffAttendancesSvc.getStaffAttendances(vm.getStaffAttendancesParams())
        .then(function(staffAttendances) {
            vm.setStaffAttendances(staffAttendances);
            vm.setGridData();
            vm.setColumnDef();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    // getters
    vm.getStaffAttendancesParams = function() {
        return {
            staff_id: vm.staffId,
            institution_id: vm.institutionId,
            academic_period_id: vm.selectedAcademicPeriod,
            week_id: vm.selectedWeek,
            week_start_day: vm.selectedStartDate,
            week_end_day: vm.selectedEndDate,
        };
    }

    //setters
    vm.setStaffAttendances = function(staff) {
        vm.staff = [];
        vm.staffList = staff;
    }

    vm.setGridData = function() {
        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setRowData(vm.staffList);
        }
    }

    vm.setColumnDef = function() {
        var columnDefs = [];
        columnDefs = StaffAttendancesSvc.getColumnDefs(vm.staffList);
        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setColumnDefs(columnDefs);
            vm.gridOptions.api.sizeColumnsToFit();
        } else {
            vm.gridOptions.columnDefs = columnDefs;
        }
    }

    vm.onEditClick = function() {
        vm.action = 'edit';
        vm.gridOptions.context.action = vm.action;
        vm.setColumnDef();
        AlertSvc.info($scope, 'Attendance will be saved automatically.');
    };

    vm.onBackClick = function() {
        vm.action = 'view';
        vm.gridOptions.context.action = vm.action;
        vm.setColumnDef();
        AlertSvc.reset($scope);
    };

}