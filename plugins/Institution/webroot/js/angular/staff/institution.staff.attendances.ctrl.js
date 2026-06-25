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
    vm.allAbsentCount = 0; //POCOR-8135
    // vm.allLateCount = 0;
    // vm.globalLateCount = 0;
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
            //POCOR-9700: warm the time_format cache BEFORE the grid renders so AM/PM appears on first paint.
            // Without this, the lazy fetch in createTimeElement resolves after the first render and
            // the AM/PM only shows after a shift change triggers a grid refresh.
            return InstitutionStaffAttendancesSvc.getTimeFormatIs12h();
        }, vm.error)
        .then(function() {
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
        AlertSvc.reset($scope);
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
        AlertSvc.reset($scope);
        UtilsSvc.isAppendLoader(true);
        vm.initGrid();
        var shiftObj = vm.shiftListOptions.find(obj => obj.id == vm.selectedShift);
        vm.gridOptions.context.date = vm.selectedShift;
        //POCOR-9700: expose shift bounds so the cell editor can soft-warn on out-of-window times
        vm.gridOptions.context.shiftStartTime = vm.selectedShiftStartTime;
        vm.gridOptions.context.shiftEndTime = vm.selectedShiftEndTime;

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

        // if (dayListOptions.length > 1) {
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
        // }
        if (dayListOptions.length < 1) {
            vm.selectedDay = -1;
            vm.gridOptions.context.date = vm.selectedDay;
        }
    }

    vm.setShiftListOptions = function(shiftListOptions) {
        vm.shiftListOptions = shiftListOptions;
        //POCOR-9700: when there is exactly one real shift, auto-select it so users do not have to.
        // When there are multiple shifts, leave the "-- All --" placeholder selected; the Edit button
        // is gated on a real shift (see ng-disabled in the template) so users see the disabled state
        // instead of an after-click "Please select shift" warning.
        var realShifts = (shiftListOptions || []).filter(function(s) { return s && s.id != -1 && s.id !== '-1'; });
        if (realShifts.length === 1) {
            var only = realShifts[0];
            vm.selectedShift = only.id;
            vm.selectedShiftStartTime = only.start_time || '';
            vm.selectedShiftEndTime = only.end_time || '';
            vm.gridOptions.context.date = vm.selectedShift;
            vm.gridOptions.context.shiftStartTime = vm.selectedShiftStartTime;
            vm.gridOptions.context.shiftEndTime = vm.selectedShiftEndTime;
            vm.changeShift();
        } else if (shiftListOptions.length > 0) {
            angular.forEach(shiftListOptions, function(shift) {
                if (shift.id == '-1') {
                    vm.selectedShift = shift.id;
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
        vm.allAbsentCount = 0; //POCOR-8135
        // vm.allLateCount = 0;
        vm.count = 0;
        vm.staffList = staffList;
        vm.totalStaff = staffList.length;
        // vm.lateCountUpdated = false;
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
                    //POCOR-8135: start - count absent (no time_in and no leave record)
                    if (!attendance.time_in && attendance.leave.length === 0) {
                        vm.allAbsentCount = vm.allAbsentCount + 1;
                    }
                    //POCOR-8135: end
                    // if (attendance.absence_type_id == 3) {
                    //     vm.allLateCount++; //POCOR-8118
                    // }
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
            if (vm.allAbsentCount == 0) { //POCOR-8135
                vm.allAbsentCount = '-';
            }
            if (vm.allLateCount == 0) {
                vm.allLateCount = '-';
            }
            // vm.globalLateCount = vm.allLateCount; //POCOR-8118
            // $timeout(function() {
            //     vm.allLateCount = vm.globalLateCount; // replace 'NEW VALUE' with the update//POCOR-7255
            // })
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
        var selectedDate = new Date(vm.selectedDayDate);
        selectedDate.setHours(0, 0, 0, 0);

        InstitutionStaffAttendancesSvc.getConfigItemValue('time_zone').then(function (timeZone) {
            var now = new Date();

            // 2. Get "Now" in the configured timezone
            var formatter = new Intl.DateTimeFormat('en-CA', {
                timeZone: timeZone,
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });

            var parts = formatter.formatToParts(now);
            var d = parts.reduce((acc, part) => {
                if (part.type !== 'literal') acc[part.type] = part.value;
                return acc;
            }, {});

            var institutionToday = new Date(d.year, d.month - 1, d.day);

            // --- CONSOLE LOGS FOR TESTING ---
            // console.log("--- Timezone Validation Debug ---");
            // console.log("Configured Timezone:", timeZone);
            // console.log("Local Browser Time:", now.toString());
            // console.log("Institution Current Time:", `${d.year}-${d.month}-${d.day} ${d.hour}:${d.minute}:${d.second}`);
            // console.log("Selected Date for Edit:", selectedDate.toDateString());
            // console.log("Institution 'Today' Limit:", institutionToday.toDateString());
            // console.log("Is Future Date?:", selectedDate > institutionToday);
            // console.log("---------------------------------");

            if (selectedDate > institutionToday) {
                // Check if $scope exists before calling alert to prevent the 'class' error
                if ($scope) {
                    console.warn('Future dates cannot be edited');
                    AlertSvc.warning($scope, 'Future dates cannot be edited');
                } else {
                    console.warn('AlertSvc failed: $scope is undefined');
                }
                return false;
            }

            if (vm.selectedShift == -1) {
                AlertSvc.warning($scope, 'Please select shift');
                return false;
            }

            // Logic for successful edit
            vm.action = 'edit';
            vm.gridOptions.context.ownEdit = vm.ownEdit;
            vm.gridOptions.context.otherEdit = vm.otherEdit;
            vm.gridOptions.context.permissionStaffId = vm.permissionStaffId;
            vm.gridOptions.context.action = vm.action;
            vm.setColumnDef();
            AlertSvc.info($scope, 'Attendance will be saved automatically.');


        }).catch(function (err) {
            console.error("Timezone config missing or error:", err);
        });
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
            // vm.allLateCount = 0;
            // for (var i = 0; i < allStaffAttendances.length; i++) {
            //     if (allStaffAttendances[i].lateCount) {
            //     vm.allLateCount += parseInt(allStaffAttendances[i].lateCount);
            //     }
            // }
        });
        //console.log('hello')
    };

    vm.onExcelClick = function() {
        vm.excelSelectedAcademicPeriodUrl = vm.excelUrl + '?academic_period_id=' + vm.selectedAcademicPeriod+'&institution_id='+vm.institutionId;
        $window.location.href = vm.excelSelectedAcademicPeriodUrl;
    };
}
