angular.module('institution.student.attendances.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.attendances.svc'])
    .controller('InstitutionStudentAttendancesCtrl', InstitutionStudentAttendancesController);

InstitutionStudentAttendancesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentAttendancesSvc'];

function InstitutionStudentAttendancesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentAttendancesSvc) {
    var vm = this;

    vm.action = 'view';
    vm.institutionId;
    vm.schoolClosed = true;

    vm.absenceTypeOptions = [];
    vm.studentAbsenceReasonOptions = [];
    vm.isMarked = false;

    // Dashboards
    vm.totalStudents = '-';
    vm.presentCount = '-';
    vm.absenceCount = '-'

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
    vm.selectedClass = '';

    vm.attendancePeriodOptions = [];
    vm.selectedAttendancePeriod = '';

    // gridOptions
    vm.gridReady = false;
    vm.classStudentList = [];
    vm.gridOptions = {
        columnDefs: [],
        rowData: [],
        headerHeight: 38,
        rowHeight: 135,
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
            absenceType: vm.absenceTypeOptions,
            studentAbsenceReason: vm.studentAbsenceReasonOptions,
            date: vm.selectedDay,
            schoolClosed: vm.schoolClosed,
            week: vm.selectedWeek,
            period: vm.selectedAttendancePeriod,
            absenceType: vm.absenceType,
            isMarked: vm.isMarked,
            // saveError: {},
            // originalData: {}
        },
    };

    // ready
    angular.element(document).ready(function () {
        InstitutionStudentAttendancesSvc.init(angular.baseUrl);
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;

        UtilsSvc.isAppendLoader(true);
        if (vm.institutionId != null) {
            InstitutionStudentAttendancesSvc.getAbsenceTypeOptions()
            .then(function(absenceType) {
                vm.absenceType = absenceType;
                vm.gridOptions.context.absenceType = vm.absenceType;
                return InstitutionStudentAttendancesSvc.getStudentAbsenceReasonOptions();
            }, vm.error)
            .then(function(studentAbsenceReasonOptions) {
                vm.studentAbsenceReasonOptions = studentAbsenceReasonOptions;
                vm.gridOptions.context.studentAbsenceReason = vm.studentAbsenceReasonOptions;
                return InstitutionStudentAttendancesSvc.getAcademicPeriodOptions(vm.institutionId);
            }, vm.error)
            .then(function(academicPeriodOptions) {
                // console.log('Controller - academicPeriodOptions', academicPeriodOptions);
                vm.updateAcademicPeriodList(academicPeriodOptions);
                return InstitutionStudentAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(weekListOptions) {
                // console.log('Controller - weekListOptions', weekListOptions);
                vm.updateWeekList(weekListOptions);
                return InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek);
            }, vm.error)
            .then(function(dayListOptions) {
                console.log('Controller - dayListOptions', dayListOptions);
                vm.updateDayList(dayListOptions);
                return InstitutionStudentAttendancesSvc.getClassOptions(vm.institutionId, vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(classListOptions) {
                // console.log('Controller - classListOptions', classListOptions);
                vm.updateClassList(classListOptions);
                return InstitutionStudentAttendancesSvc.getPeriodOptions(vm.selectedClass, vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(attendancePeriodOptions) {
                // console.log('Controller - attendancePeriodOptions', attendancePeriodOptions);
                vm.updateAttendancePeriodList(attendancePeriodOptions);
                return InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams());
            }, vm.error)
            .then(function(isMarked) {
                // console.log('Controller - isMarked', isMarked);
                vm.updateIsMarked(isMarked);
                return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
            }, vm.error)
            .then(function(classStudents) {
                vm.updateClassStudentList(classStudents);
                vm.initGrid();
            }, vm.error)
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
            });
        }
    });

    // error
    vm.error = function (error) {
        console.log(error);   
    }

    // update data
    vm.updateAcademicPeriodList = function(academicPeriodOptions) {
        vm.academicPeriodOptions = academicPeriodOptions;
        if (academicPeriodOptions.length > 0) {
            vm.selectedAcademicPeriod = academicPeriodOptions[0].id;
        }
    }

    vm.updateWeekList = function(weekListOptions) {
        vm.weekListOptions = weekListOptions;
        if (weekListOptions.length > 0) {
            for (var i = 0; i < weekListOptions.length; ++i) {
                if (angular.isDefined(weekListOptions[i]['selected']) && weekListOptions[i]['selected']) {
                    vm.selectedWeek = weekListOptions[i].id;
                    vm.selectedWeekStartDate = weekListOptions[i].start_day;
                    vm.selectedWeekEndDate = weekListOptions[i].end_day;
                    vm.week = vm.selectedWeek;
                    vm.gridOptions.context.week = vm.selectedWeek;
                    break;
                }
            }
        }
    }

    vm.updateDayList = function(dayListOptions) {
        vm.dayListOptions = dayListOptions;
        if (dayListOptions.length > 0) {
            for (var i = 0; i < dayListOptions.length; ++i) {
                if (angular.isDefined(dayListOptions[i]['selected']) && dayListOptions[i]['selected']) {
                    vm.selectedDay = dayListOptions[i].date;
                    vm.schoolClosed = (angular.isDefined(dayListOptions[i]['closed']) && dayListOptions[i]['closed']) ? true : false;
                    vm.gridOptions.context.date = vm.selectedDay;
                    vm.gridOptions.context.schoolClosed = vm.schoolClosed;
                    break;
                }
            }
        }
    }

    vm.updateClassList = function(classListOptions) {
        vm.classListOptions = classListOptions;
        if (classListOptions.length > 0) {
            vm.selectedClass = classListOptions[0].id;
        }
    }

    vm.updateAttendancePeriodList = function(attendancePeriodOptions) {
        vm.attendancePeriodOptions = attendancePeriodOptions;
        if (attendancePeriodOptions.length > 0) {
            vm.selectedAttendancePeriod = attendancePeriodOptions[0].id;
            vm.gridOptions.context.period = vm.selectedAttendancePeriod;
        }
    }

    vm.updateClassStudentList = function(classStudents) {
        vm.classStudents = [];
        vm.classStudentList = classStudents;
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

    vm.setGridData = function() {
        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setRowData(vm.classStudentList);
            vm.countStudentData();
        }
    }

    vm.setColumnDef = function() {
        var columnDefs = [];
        if (vm.selectedDay != -1) {
            columnDefs = InstitutionStudentAttendancesSvc.getSingleDayColumnDefs(vm.selectedAttendancePeriod);
        } else {
            // vm.gridOptions.headerHeight = vm.groupColumnHeight;
            columnDefs = InstitutionStudentAttendancesSvc.getAllDayColumnDefs(vm.dayListOptions, vm.attendancePeriodOptions);
        }

        if (angular.isDefined(vm.gridOptions.api)) {
            // console.log('vm.classStudentList', vm.classStudentList);
            vm.gridOptions.api.setColumnDefs(columnDefs);
            vm.gridOptions.api.sizeColumnsToFit();
        } else {
            vm.gridOptions.columnDefs = columnDefs;
        }
    }

    // dashboard count
    vm.countStudentData = function() {
        vm.totalStudents = vm.classStudentList.length;

        if (vm.isMarked) {
            var presentCount = 0;
            var absenceCount = 0;
            var attendanceType = InstitutionStudentAttendancesSvc.getAttendanceTypeList();

            if (vm.totalStudents > 0) {
                angular.forEach(vm.classStudentList, function(obj, key) {
                    if (angular.isDefined(obj['institution_student_absences']) && angular.isDefined(obj['institution_student_absences']['absence_type_code'])) {
                        var code = obj['institution_student_absences']['absence_type_code'];

                        switch (code) {
                            case null:
                            case attendanceType.PRESENT.code:
                            case attendanceType.LATE.code:
                                ++presentCount;
                                break;
                            case attendanceType.UNEXCUSED.code:
                            case attendanceType.EXCUSED.code:
                                ++absenceCount;
                                break;
                        }
                    } 
                });
            }

            vm.presentCount = presentCount;
            vm.absenceCount = absenceCount;
        } else {
            vm.presentCount = '-';
            vm.absenceCount = '-';
        }
    }

    // params
    vm.getClassStudentParams = function() {
        return {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            week_start_day: vm.selectedWeekStartDate,
            week_end_day: vm.selectedWeekEndDate,
            week_id: vm.selectedWeek
        };
    }

    vm.getIsMarkedParams = function() {
        return {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod
        };
    }

    // changes
    vm.changeAcademicPeriod = function() {
        console.log('Change - academic period', vm.selectedAcademicPeriod);
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod)
        .then(function(weekListOptions) {
            vm.updateWeekList(weekListOptions);
            return InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek);
        }, vm.error)
        .then(function(dayListOptions) {
            vm.updateDayList(dayListOptions);
            return InstitutionStudentAttendancesSvc.getClassOptions(vm.institutionId, vm.selectedAcademicPeriod);
        }, vm.error)
        .then(function(classListOptions) {
            vm.updateClassList(classListOptions);
            return InstitutionStudentAttendancesSvc.getPeriodOptions(vm.selectedClass, vm.selectedAcademicPeriod);
        }, vm.error)
        .then(function(attendancePeriodOptions) {
            vm.updateAttendancePeriodList(attendancePeriodOptions);
            return InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams());
        }, vm.error)
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            // console.log('classStudents', classStudents);
            vm.updateClassStudentList(classStudents);
            vm.setGridData();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeWeek = function() {
        console.log('Change - week', vm.selectedDay);
        UtilsSvc.isAppendLoader(true);
        var weekObj = vm.weekListOptions.find(obj => obj.id == vm.selectedWeek);
        vm.selectedWeekStartDate = weekObj.start_day;
        vm.selectedWeekEndDate = weekObj.end_day;
        vm.gridOptions.context.week = vm.selectedWeek;
        InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek)
        .then(function(dayListOptions) {
            vm.updateDayList(dayListOptions);
            return InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams());
        }, vm.error)
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
            vm.setGridData();
            vm.setColumnDef();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeDay = function() {
        console.log('Change - day', vm.selectedDay);
        UtilsSvc.isAppendLoader(true);
        var dayObj = vm.dayListOptions.find(obj => obj.date == vm.selectedDay);
        vm.schoolClosed = (angular.isDefined(dayObj.closed) && dayObj.closed) ? true : false;
        vm.gridOptions.context.schoolClosed = vm.schoolClosed;
        vm.gridOptions.context.date = vm.selectedDay;
        InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams())
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
            vm.setGridData();
            vm.setColumnDef();
            }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeClass = function() {
        console.log('Change - class', vm.selectedClass);
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getPeriodOptions(vm.selectedClass, vm.selectedAcademicPeriod)
        .then(function(attendancePeriodOptions) {
            vm.updateAttendancePeriodList(attendancePeriodOptions);
            return InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams());
        }, vm.error)
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
            vm.setGridData();
            vm.setColumnDef();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        }); 
    }

    vm.changeAttendancePeriod = function() {
        console.log('Change - attendance', vm.selectedClass);
        vm.gridOptions.context.period = vm.selectedAttendancePeriod;
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams())
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
            vm.setGridData();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    // button events
    vm.onEditClick = function() {
        vm.action = 'edit';
        vm.gridOptions.context.mode = vm.action;
        vm.setColumnDef();
        AlertSvc.info($scope, 'Attendances will be automatically saved.');
    };

    vm.onBackClick = function() {
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams())
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            vm.setColumnDef();
            vm.countStudentData();
            AlertSvc.reset($scope);
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    };
}