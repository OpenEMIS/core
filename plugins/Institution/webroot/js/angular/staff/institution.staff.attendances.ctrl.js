angular.module('institution.staff.attendances.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.staff.attendances.svc'])
    .controller('InstitutionStaffAttendancesCtrl', InstitutionStaffAttendancesController);

InstitutionStaffAttendancesController.$inject = ['$scope','$timeout' ,'$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStaffAttendancesSvc'];

function InstitutionStaffAttendancesController($scope,$timeout, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStaffAttendancesSvc) {
    var vm = this;
    
    vm.action = 'view';
    vm.excelUrl = '';
    vm.staffId;
    vm.institutionId;
    vm.ownView = 0;
    vm.ownEdit = 0;
    vm.otherView = 0;
    vm.otherEdit = 0;
    vm.permissionStaffId = 0;
    vm.history = false;
    vm.schoolClosed = true;
    vm.academicPeriodOptions = [];
    vm.selectedAcademicPeriod = '';

    vm.weekListOptions = [];
    vm.selectedWeek = '';
    vm.selectedStartDate = '';
    vm.selectedEndDate = '';

    vm.dayListOptions = [];
    vm.selectedDay = '';
    vm.selectedDayDate = '';
    vm.selectedFormattedDayDate = '';

    vm.shiftListOptions = [];
    vm.selectedShift = '';

    vm.selectedShiftStartTime = '';
    vm.selectedShiftEndTime = '';

    // All attendances for a given day
    vm.totalStaff = '';

    // All attendances for a given week
    vm.allAttendances = 0;
    vm.allPresentCount = 0;
    vm.allLeaveCount = 0;
    vm.allLateCount = 0;
    vm.globalLateCount = 0;
    // gridOptions
    vm.gridReady = false;
    vm.gridOptions = {
        context: {
            period: vm.selectedAcademicPeriod,
            action: vm.action,
            scope: $scope,
            history: vm.history,
            schoolClosed: vm.schoolClosed,
        },
        columnDefs: [],
        rowData: [],
        headerHeight: 38,
        rowHeight: 125,
        minColWidth: 200,
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
        InstitutionStaffAttendancesSvc.init(angular.baseUrl);
        vm.action = 'view';
        vm.gridOptions.context.action = vm.action;
        vm.gridOptions.context.history = vm.history;
        UtilsSvc.isAppendLoader(true);
        InstitutionStaffAttendancesSvc.getTranslatedText().
        then(function(isTranslated) {
            return InstitutionStaffAttendancesSvc.getAcademicPeriodOptions();
        }, vm.error)
        .then(function(academicPeriods) {
            vm.setAcademicPeriodList(academicPeriods);
            return InstitutionStaffAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod);
        }, vm.error)
        .then(function(weekList) {
            vm.setWeekList(weekList);
            return InstitutionStaffAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId);
        }, vm.error)
        .then(function(dayListOptions) {
            vm.setDayListOptions(dayListOptions);
            return InstitutionStaffAttendancesSvc.getShiftListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId);
        }, vm.error)
        .then(function(shiftListOptions) {
            vm.setShiftListOptions(shiftListOptions);
            // console.log("---PARAM---");
            // console.log(vm.getAllStaffAttendancesParams());
            return InstitutionStaffAttendancesSvc.getAllStaffAttendances(vm.getAllStaffAttendancesParams());
        }, vm.error)
        .then(function(allStaffAttendances) {
            vm.setAllStaffAttendances(allStaffAttendances);
            vm.initGrid();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    });

    // error
    vm.error = function (error) {
        UtilsSvc.isAppendLoader(false);
        console.error(error);
        vm.initGrid();
        vm.setGridData();
        vm.setColumnDef();
        return $q.reject('There is an error retrieving the data.');
    }

    //onChange
    vm.changeAcademicPeriod = function() {
        UtilsSvc.isAppendLoader(true);
        vm.initGrid();
        InstitutionStaffAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod)
        .then(function(weekListOptions) {
            vm.gridOptions.context.period = vm.selectedAcademicPeriod;
            vm.setWeekList(weekListOptions);
            return InstitutionStaffAttendancesSvc.getDayListOptions(
                vm.selectedAcademicPeriod,
                vm.selectedWeek,
                vm.institutionId);
        }, vm.error)
        .then(function(dayListOptions) {
            vm.setDayListOptions(dayListOptions);
            return InstitutionStaffAttendancesSvc.getAllStaffAttendances(
                vm.getAllStaffAttendancesParams());
        }, vm.error)
        .then(function(allStaffAttendances) {
            vm.setAllStaffAttendances(allStaffAttendances);
            vm.setGridData();
            vm.setColumnDef();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeWeek = function() {
        UtilsSvc.isAppendLoader(true);
        vm.initGrid();
        var weekObj = vm.weekListOptions.find(obj => obj.id == vm.selectedWeek);
        vm.selectedStartDate = weekObj.start_day;
        vm.selectedEndDate = weekObj.end_day;
        InstitutionStaffAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId)
        .then(function(dayListOptions) {
            vm.setDayListOptions(dayListOptions);
            return InstitutionStaffAttendancesSvc.getAllStaffAttendances(vm.getAllStaffAttendancesParams());
        }, vm.error)
        .then(function(allStaffAttendances) {
            vm.setAllStaffAttendances(allStaffAttendances);
            vm.setGridData();
            vm.setColumnDef();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeDay = function() {
        UtilsSvc.isAppendLoader(true);
        vm.initGrid();
        var dayObj = vm.dayListOptions.find(obj => obj.id == vm.selectedDay);
        vm.selectedDayDate = dayObj.date;
        vm.selectedFormattedDayDate = dayObj.name;
        vm.schoolClosed = (angular.isDefined(dayObj.closed) && dayObj.closed) ? true : false;
        vm.gridOptions.context.schoolClosed = vm.schoolClosed;
        vm.gridOptions.context.date = vm.selectedDay;
        InstitutionStaffAttendancesSvc.getAllStaffAttendances(vm.getAllStaffAttendancesParams())
        .then(function(allStaffAttendances) {
            vm.setAllStaffAttendances(allStaffAttendances);
            vm.setColumnDef();
            vm.setGridData();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeShift = function() {
        UtilsSvc.isAppendLoader(true);
        vm.initGrid();
        var shiftObj = vm.shiftListOptions.find(obj => obj.id == vm.selectedShift);
        vm.gridOptions.context.date = vm.selectedShift;
        
        InstitutionStaffAttendancesSvc.getAllStaffAttendances(vm.getAllStaffAttendancesParams())
        .then(function(allStaffAttendances) {
            vm.setAllStaffAttendances(allStaffAttendances);
            vm.setColumnDef();
            vm.setGridData();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    // getters
    vm.getAllStaffAttendancesParams = function() {
        return {
            institution_id: vm.institutionId,
            academic_period_id: vm.selectedAcademicPeriod,
            week_id: vm.selectedWeek,
            week_start_day: vm.selectedStartDate,
            week_end_day: vm.selectedEndDate,
            day_id: vm.selectedDay,
            shift_id: vm.selectedShift,
            start_time: vm.selectedShiftStartTime,
            end_time: vm.selectedShiftEndTime,
            day_date: vm.selectedDayDate,
            own_attendance_view: vm.ownView,
            own_attendance_edit: vm.ownEdit,
            other_attendance_view: vm.otherView,
            other_attendance_edit: vm.otherEdit,
        };
    }

    // grid
    vm.initGrid = function() {
        AggridLocaleSvc.getTranslatedGridLocale().then(
            function(localeText) {
                vm.gridOptions.localeText = localeText;
                vm.gridReady = true;
            },
            function(error) {
                console.error(error);
                vm.gridReady = true;
            }
        );
    }

    //setters
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

    vm.setDayListOptions = function(dayListOptions) {
        // console.log('setDayListOptions');
        // console.log(dayListOptions);
        // console.log(dayListOptions.length);
        vm.dayListOptions = dayListOptions;

        if (dayListOptions.length > 1) {
            angular.forEach(dayListOptions, function(day) {
                if (day.selected == true) {
                   vm.selectedDay = (angular.isDefined(day.closed) && day.closed) ? -1 : day.id;
                   vm.selectedDayDate = day.date;
                   vm.selectedFormattedDayDate = day.name;
                   vm.schoolClosed = (angular.isDefined(day.closed) && day.closed) ? true : false;
                   vm.gridOptions.context.date = vm.selectedDay;
                   vm.gridOptions.context.schoolClosed = vm.schoolClosed;
                }
            });
        }
        if (dayListOptions.length < 1) {
            vm.selectedDay = -1;
            vm.gridOptions.context.date = vm.selectedDay;
        }
    }

    vm.setShiftListOptions = function(shiftListOptions) {
        vm.shiftListOptions = shiftListOptions;
        if (shiftListOptions.length > 0) {
            angular.forEach(shiftListOptions, function(shift) {
                if (shift.id == '-1') {
                   vm.selectedShift = shift.id;
                //    vm.selectedFormattedDayDate = shift.name;
                   vm.gridOptions.context.date = vm.selectedShift;

                }
            });
        }
    }

    vm.setAllStaffAttendances = function(staffList) {
        // UtilsSvc.isAppendLoader(true);
        vm.allPresentCount = 0;
        vm.totalStaff = 0;
        vm.allAttendances = 0;
        vm.allLeaveCount = 0;
        vm.allLateCount = 0;
        vm.count = 0;
        vm.staffList = staffList;
        vm.totalStaff = staffList.length;
        vm.lateCountUpdated = false;
        if (staffList.length > 0) {
            angular.forEach(staffList, function(staff) {
                // for All Days Dashboard
                angular.forEach(staff.attendance, function(attendance) {
                    vm.allAttendances = vm.allAttendances + 1 ;
                    if (attendance.time_in) {
                        vm.allPresentCount = vm.allPresentCount + 1;
                    }
                    if (attendance.leave.length > 0) {
                        vm.allLeaveCount = vm.allLeaveCount + 1;
                    }
                    if (attendance.absence_type_id == 3) {
                        vm.allLateCount = vm.allLateCount + 1;
                        vm.globalLateCount = vm.allLateCount
                       vm.allLateCount = vm.allLateCount + 1;
                    }
                });
                // $scope.$apply();
            });
            
            //console.log(vm.allPresentCount);
            //console.log(vm.allLateCount);
            if (vm.allPresentCount == 0) {
                vm.allPresentCount = '-';
            }
            if (vm.allLeaveCount == 0) {
                vm.allLeaveCount = '-';
            }
            if (vm.allLateCount == 0) {
                vm.allLateCount = '-';
            }
            $timeout(function() {
                vm.allLateCount = vm.globalLateCount; // replace 'NEW VALUE' with the update//POCOR-7255
            })
        }
  }

    vm.setGridData = function() {
        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setRowData(vm.staffList);
        }
    }

    vm.setColumnDef = function() {
        var columnDefs = [];
        if (vm.selectedDay == -1) {
            columnDefs = InstitutionStaffAttendancesSvc.getAllDayColumnDefs(vm.dayListOptions);
        } else {
            columnDefs = InstitutionStaffAttendancesSvc.getColumnDefs(vm.selectedDayDate);
        }

        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setColumnDefs(columnDefs);
            vm.gridOptions.api.sizeColumnsToFit();
        } else {
            vm.gridOptions.columnDefs = columnDefs;
        }
    }

    vm.onEditClick = function() {
        //POCOR-6971[START]
        if(vm.selectedShift == -1){
            AlertSvc.info($scope, 'Please select shift');
            return false;
        }else{
            //POCOR-6971[END]
            vm.action = 'edit';
            vm.gridOptions.context.ownEdit = vm.ownEdit;
            vm.gridOptions.context.otherEdit = vm.otherEdit;
            vm.gridOptions.context.permissionStaffId = vm.permissionStaffId;  
            vm.gridOptions.context.action = vm.action;
            vm.setColumnDef();
            AlertSvc.info($scope, 'Attendance will be saved automatically.');
        }
    };

    vm.onBackClick = function() {
        // vm.setAllStaffAttendances(vm.staffList); //POCOR-7255 comment this line
        $window.localStorage.setItem('back',true)
        vm.action = 'view';
        vm.gridOptions.context.action = vm.action;
        vm.setColumnDef();
        AlertSvc.reset($scope);
        InstitutionStaffAttendancesSvc.getAllStaffAttendances(vm.getAllStaffAttendancesParams())
        .then(function(allStaffAttendances) {
            vm.setAllStaffAttendances(allStaffAttendances);
            // Update the allLateCount variable with the new data
            vm.allLateCount = 0;
            for (var i = 0; i < allStaffAttendances.length; i++) {
                if (allStaffAttendances[i].lateCount) {
                vm.allLateCount += parseInt(allStaffAttendances[i].lateCount);
                }
            }
        });
        //console.log('hello')
    };

    vm.onExcelClick = function() {
        vm.excelSelectedAcademicPeriodUrl = vm.excelUrl + '?academic_period_id=' + vm.selectedAcademicPeriod;
        $window.location.href = vm.excelSelectedAcademicPeriodUrl;
    };
}