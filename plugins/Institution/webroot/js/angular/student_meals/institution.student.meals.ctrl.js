angular.module('institution.student.meals.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.meals.svc'])
    .controller('InstitutionStudentMealsCtrl', InstitutionStudentMealsController);

InstitutionStudentMealsController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentMealsSvc'];

function InstitutionStudentMealsController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentMealsSvc) {
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
    
    vm.educationGradeListOptions = [];
    vm.selectedEducationGrade = '';

    vm.attendancePeriodOptions = [];
    vm.selectedAttendancePeriod = '';

    vm.classStudentList = [];
    

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
                setTimeout(function() {
                    vm.setGridData();
                })
                vm.setColumnDef();
            }
        },
        context: {
            scope: $scope,
            mode: vm.action,
            absenceTypes: vm.absenceTypeOptions,
            studentAbsenceReasons: vm.studentAbsenceReasonOptions,
            date: vm.selectedDay,
            schoolClosed: vm.schoolClosed,
            week: vm.selectedWeek,
            period: vm.selectedAttendancePeriod,
            isMarked: vm.isMarked,
            subject_id: vm.selectedSubject,
            education_grade_id: vm.selectedEducationGrade
        },
        // getRowHeight: getRowHeight,
    };

    // ready
    angular.element(document).ready(function () {

        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth()+1;
        var currentdate = currentDate.getDate();
        console.log(currentYear + '-' +currentMonth + '-' + currentdate)
        vm.currentDayMonthYear = currentYear + '-' +currentMonth + '-' + currentdate;
        InstitutionStudentMealsSvc.init(angular.baseUrl, $scope);
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;

        UtilsSvc.isAppendLoader(true);
        if (vm.institutionId != null) {
            //debugger;
            InstitutionStudentMealsSvc.getTranslatedText()
            .then(function(studentAbsenceReasonOptions) {
                vm.studentAbsenceReasonOptions = studentAbsenceReasonOptions;
                vm.gridOptions.context.studentAbsenceReasons = vm.studentAbsenceReasonOptions;
                return InstitutionStudentMealsSvc.getAcademicPeriodOptions(vm.institutionId);
            }, vm.error)
            .then(function(academicPeriodOptions) {
                vm.updateAcademicPeriodList(academicPeriodOptions);
                return InstitutionStudentMealsSvc.getWeekListOptions(vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(weekListOptions) {
                vm.updateWeekList(weekListOptions);
                return InstitutionStudentMealsSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId);
            }, vm.error)
            .then(function(dayListOptions) {
                vm.updateDayList(dayListOptions);
                return InstitutionStudentMealsSvc.getClassOptions(vm.institutionId, vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(classListOptions) {
                vm.updateClassList(classListOptions);                
                return InstitutionStudentMealsSvc.getEducationGradeOptions(vm.institutionId,vm.selectedAcademicPeriod,vm.selectedClass);
            }, vm.error)
            .then(function(isMarked) {
                vm.updateIsMarked(isMarked);
                //return InstitutionStudentMealsSvc.getClassStudent(vm.institutionId,vm.selectedClass,vm.selectedAcademicPeriod,vm.selectedDay,vm.selectedWeekStartDate,vm.selectedWeekEndDate,vm.selectedWeek,vm.subject_id);
                return InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams());
            }, vm.error)
            .then(function(isresponse) {
               vm.classStudentList = isresponse;
            }, vm.error)
            .finally(function() {
                vm.initGrid();
                UtilsSvc.isAppendLoader(false);
            });
        }
    });

    // error
    vm.error = function (error, test) {
        return $q.reject(error);
    }

    // update data
    vm.updateAcademicPeriodList = function(academicPeriodOptions) {
        vm.academicPeriodOptions = academicPeriodOptions;
        if (academicPeriodOptions.length > 0) {
            var selectedAcademicPeriodId = 0;
            for (var i = 0; i < academicPeriodOptions.length; ++i) {
                if (angular.isDefined(academicPeriodOptions[i]['selected'] && academicPeriodOptions[i]['selected'])) {
                    selectedAcademicPeriodId = i;
                    break;
                }
            }
            vm.selectedAcademicPeriod = academicPeriodOptions[selectedAcademicPeriodId].id;
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
        console.log(vm.dayListOptions);
        var hasSelected = false;
        if (dayListOptions.length > 0) {
            for (var i = 0; i < dayListOptions.length; ++i) {
                if (angular.isDefined(dayListOptions[i]['selected']) && dayListOptions[i]['selected']) {
                    hasSelected = true;
                    vm.selectedDay = dayListOptions[i].date;
                    console.log(vm.selectedDay);
                   
                    vm.schoolClosed = (angular.isDefined(dayListOptions[i]['closed']) && dayListOptions[i]['closed']) ? true : false;
                    vm.gridOptions.context.date = vm.selectedDay;
                    vm.gridOptions.context.schoolClosed = vm.schoolClosed;
                    break;
                }
            }

            if (!hasSelected) {
                vm.selectedDay = dayListOptions[0].date;
                vm.schoolClosed = (angular.isDefined(dayListOptions[0]['closed']) && dayListOptions[0]['closed']) ? true : false;
                vm.gridOptions.context.date = vm.selectedDay;
                vm.gridOptions.context.schoolClosed = vm.schoolClosed;
            }
        }
    }

    vm.updateClassList = function(classListOptions) {
        vm.classListOptions = classListOptions;
        if (classListOptions.length > 0) {
            vm.selectedClass = classListOptions[0].id;
            if (classListOptions[0].SecurityRoleFunctions) {
                    vm.superAdmin = 0;
                    vm.permissionView = classListOptions[0].SecurityRoleFunctions._view;
                    vm.permissionEdit = classListOptions[0].SecurityRoleFunctions._edit;
            }
            }
    }
    
    vm.updateEducationGradeList = function(educationGradeListOptions) {
        vm.educationGradeListOptions = educationGradeListOptions;
        if (educationGradeListOptions.length > 0) {
            vm.selectedEducationGrade = educationGradeListOptions[0].id;
            vm.gridOptions.context.education_grade_id = vm.selectedEducationGrade;
        }
    }

    

    vm.subjectListOptions = function(subjectListOptions) {
        vm.subjectListOptions = subjectListOptions;
        if (subjectListOptions.length > 0) {
            vm.selectedSubject = subjectListOptions[0].id;
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

    function getRowHeight(params) {
        return params.data.rowHeight;
    }

    
    vm.setGridData = function() {
        console.log(vm.classStudentList);
        if (angular.isDefined(vm.gridOptions.api)) {
            // vm.gridOptions.api.setRowData(vm.classStudentList);
            vm.setRowDatas(vm.classStudentList);
            vm.countStudentData();
        }
    }

    vm.setRowDatas = function(studentList) {
        console.log(studentList);       
        vm.gridOptions.api.setRowData(studentList);
        
    }

    vm.setColumnDef = function() {
        var columnDefs = [];
        if (vm.selectedDay != -1) {
            columnDefs = InstitutionStudentMealsSvc.getSingleDayColumnDefs(vm.selectedAttendancePeriod, vm.selectedSubject);
        } else {
            columnDefs = InstitutionStudentMealsSvc.getAllDayColumnDefs(vm.dayListOptions, vm.attendancePeriodOptions);
        }

        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setColumnDefs(columnDefs);
            vm.gridOptions.api.sizeColumnsToFit();
        } else {
            vm.gridOptions.columnDefs = columnDefs;
        }
    }

    // dashboard count
    vm.countStudentData = function() {
        var attendanceType = InstitutionStudentMealsSvc.getAttendanceTypeList();
        if (vm.selectedDay != -1) {
            // single day
            vm.totalStudents = vm.classStudentList.length;
            if (vm.isMarked) {
                var presentCount = 0;
                var absenceCount = 0;
                var lateCount = 0;

                if (vm.totalStudents > 0) {
                    angular.forEach(vm.classStudentList, function(obj, key) {
                        if (angular.isDefined(obj['institution_student_absences']) && angular.isDefined(obj['institution_student_absences']['absence_type_code'])) {
                            var code = obj['institution_student_absences']['absence_type_code'];

                            switch (code) {
                                case null:
                                case attendanceType.PRESENT.code:
                                    ++presentCount;
                                     break;
                                case attendanceType.LATE.code:
                                    ++presentCount;
                                    ++lateCount;
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
                vm.lateCount = lateCount;
            } else {
                vm.presentCount = '-';
                vm.absenceCount = '-';
                vm.lateCount = '-';  
            }
        } else {
            // all day
            var allAttendances = '-';
            var allPresentCount = '-';
            var allAbsenceCount = '-';
            var allLateCount = '-';

            if (vm.totalStudents > 0) {
                allAttendances = 0;
                allPresentCount = 0;
                allAbsenceCount = 0;
                allLateCount = 0;

                angular.forEach(vm.classStudentList, function(obj, studentKey) {
                    if (angular.isDefined(obj.week_attendance) && Object.keys(obj.week_attendance).length > 0) {
                        var weekAttendance = obj.week_attendance;
                        angular.forEach(weekAttendance, function(day, dayKey) {
                            if (Object.keys(day).length > 0) {
                                angular.forEach(day, function(period, periodKey) {
                                    switch(period) {
                                        case attendanceType.NOTMARKED.code:
                                            break;
                                        case attendanceType.PRESENT.code:
                                            ++allAttendances;
                                            ++allPresentCount;
                                            break;
                                        case attendanceType.LATE.code:
                                            ++allAttendances;
                                            ++allLateCount;
                                            break;
                                        case attendanceType.UNEXCUSED.code:
                                        case attendanceType.EXCUSED.code:
                                            ++allAttendances;
                                            ++allAbsenceCount;
                                            break;
                                    }
                                });
                            }
                        });
                    }
                });
            }

            vm.allAttendances = allAttendances;
            vm.allPresentCount = allPresentCount;
            vm.allAbsenceCount = allAbsenceCount;
            vm.allLateCount = allLateCount;
        }
    }

    // params
    vm.getClassStudentParams = function() {
        vm.excelExportAUrl = vm.exportexcel
                             +'?institution_id='+ vm.institutionId+
                            '&institution_class_id='+ vm.selectedClass+
                            '&education_grade_id='+ vm.selectedEducationGrade+
                            '&academic_period_id='+ vm.selectedAcademicPeriod+
                            '&day_id='+ vm.selectedDay+
                            '&attendance_period_id='+ vm.selectedAttendancePeriod+
                            '&week_start_day='+ vm.selectedWeekStartDate+
                            '&week_end_day='+ vm.selectedWeekEndDate+
                            '&week_id='+ vm.selectedWeek
        console.log(vm.excelExportAUrl);
        console.log(vm.exportexcel);
        
        return {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            education_grade_id: vm.selectedEducationGrade,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            week_start_day: vm.selectedWeekStartDate,
            week_end_day: vm.selectedWeekEndDate,
            week_id: vm.selectedWeek,
            subject_id: vm.selectedSubject
        };
    }

    vm.getPeriodMarkedParams = function() {
        return {
            institution_id: vm.institutionId,
            academic_period_id: vm.selectedAcademicPeriod,
            institution_class_id: vm.selectedClass,
            education_grade_id: vm.selectedEducationGrade,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            subject_id: vm.selectedSubject
        };
    }

    // changes
    vm.changeAcademicPeriod = function() {
        //debugger;
        //"var test = "/search?fname="+fname"+"&lname="+lname"
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentMealsSvc.getWeekListOptions(vm.selectedAcademicPeriod)
        .then(function(weekListOptions) {
            vm.updateWeekList(weekListOptions);
            return InstitutionStudentMealsSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId);
        }, vm.error)
        .then(function(dayListOptions) {
            vm.updateDayList(dayListOptions);
            return InstitutionStudentMealsSvc.getClassOptions(vm.institutionId, vm.selectedAcademicPeriod);
        }, vm.error)       
        .then(function(classListOptions) {
                vm.updateClassList(classListOptions);                
                return InstitutionStudentMealsSvc.getEducationGradeOptions(vm.institutionId,vm.selectedAcademicPeriod,vm.selectedClass);
            }, vm.error)
        .then(function(isMarked) {
                vm.updateIsMarked(isMarked);
                //return InstitutionStudentMealsSvc.getClassStudent(vm.institutionId,vm.selectedClass,vm.selectedAcademicPeriod,vm.selectedDay,vm.selectedWeekStartDate,vm.selectedWeekEndDate,vm.selectedWeek,vm.subject_id);
                return InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams());
            }, vm.error)
        /*.then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)*/
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
        }, vm.error)
        .finally(function() {
            vm.setGridData();
            vm.setColumnDef();
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeWeek = function() {
        UtilsSvc.isAppendLoader(true);
        var weekObj = vm.weekListOptions.find(obj => obj.id == vm.selectedWeek);
        vm.selectedWeekStartDate = weekObj.start_day;
        vm.selectedWeekEndDate = weekObj.end_day;
        vm.gridOptions.context.week = vm.selectedWeek;
        InstitutionStudentMealsSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId)
       
        .then(function(isMarked) {
                vm.updateIsMarked(isMarked);
                //return InstitutionStudentMealsSvc.getClassStudent(vm.institutionId,vm.selectedClass,vm.selectedAcademicPeriod,vm.selectedDay,vm.selectedWeekStartDate,vm.selectedWeekEndDate,vm.selectedWeek,vm.subject_id);
                return InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams());
            }, vm.error)       
        /*.then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)*/
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
        }, vm.error)
        .finally(function() {
            vm.setGridData();
            vm.setColumnDef();
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeDay = function() {
       
        UtilsSvc.isAppendLoader(true);
        var dayObj = vm.dayListOptions.find(obj => obj.date == vm.selectedDay);
        vm.schoolClosed = (angular.isDefined(dayObj.closed) && dayObj.closed) ? true : false;
        vm.gridOptions.context.schoolClosed = vm.schoolClosed;
        vm.gridOptions.context.date = vm.selectedDay;

        var currentDate = new Date();       
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth()+1;
        var currentdate = currentDate.getDate();
        vm.currentDayMonthYear = currentYear + '-' +currentMonth + '-' + currentdate;
        
        InstitutionStudentMealsSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId)

        .then(function(isMarked) {
                vm.updateIsMarked(isMarked);
                //return InstitutionStudentMealsSvc.getClassStudent(vm.institutionId,vm.selectedClass,vm.selectedAcademicPeriod,vm.selectedDay,vm.selectedWeekStartDate,vm.selectedWeekEndDate,vm.selectedWeek,vm.subject_id);
                return InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams());
            }, vm.error)
        /*.then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)*/
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
            }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
            vm.setGridData();
            vm.setColumnDef();
        });
    }

    vm.changeClass = function() {
        UtilsSvc.isAppendLoader(true);
        if (vm.superAdmin == 0) {
            vm.updateClassRoles(vm.selectedClass);
        }
        InstitutionStudentMealsSvc.getEducationGradeOptions(vm.institutionId,vm.selectedAcademicPeriod,vm.selectedClass)            
        
        .then(function(isMarked) {
                vm.updateIsMarked(isMarked);
                //return InstitutionStudentMealsSvc.getClassStudent(vm.institutionId,vm.selectedClass,vm.selectedAcademicPeriod,vm.selectedDay,vm.selectedWeekStartDate,vm.selectedWeekEndDate,vm.selectedWeek,vm.subject_id);
                return InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams());
            }, vm.error)
        /*.then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)*/
         .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
            }, vm.error)
        
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
            vm.setGridData();
            vm.setColumnDef();
        }); 
    }
    

    // button events
    vm.onEditClick = function() {
        vm.action = 'edit';
        vm.gridOptions.context.mode = vm.action;
        vm.setColumnDef();
        AlertSvc.info($scope, 'Attendances will be automatically saved.');
        InstitutionStudentMealsSvc.savePeriodMarked(vm.getPeriodMarkedParams(), $scope);
    };

    vm.onExcelClick = function() {
        var excelUrlWithQuery = vm.excelUrl + '?' + 
            'institution_id=' + vm.institutionId + '&' + 
            'academic_period_id=' + vm.selectedAcademicPeriod + '&' + 
            'class_id=' + vm.selectedClass;
            
        window.location.href = excelUrlWithQuery;
        return;
    }

    vm.updateClassRoles = function(selectedClass) {
        var selectedClass = selectedClass;
        var classListOptions = vm.classListOptions;
        if (classListOptions.length > 0) {
            angular.forEach(classListOptions, function(value, key) {
                if (value.SecurityRoleFunctions) {
                   if (value.id == selectedClass) {
                        vm.permissionView = value.SecurityRoleFunctions._view;
                        vm.permissionEdit = value.SecurityRoleFunctions._edit;
                   }
                }
            });
            }
    }
}
