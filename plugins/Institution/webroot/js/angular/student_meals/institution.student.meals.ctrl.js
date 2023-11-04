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
    // vm.presentCount = '-';
    // vm.absenceCount = '-';
    // vm.lateCount = '-';

    vm.allPresentCount = '-';
    vm.exportexcel = '';
    vm.excelExportAUrl = '';

    vm.allMeals = '-';

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

    vm.classStudentList = [];


    vm.mealProgramOptions = [];
    vm.selectedMealProgram = '';

    vm.mealBenefitTypeOptions = [];

    vm.mealTypes = [];


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
        onGridSizeChanged: function () {
            this.api.sizeColumnsToFit();
        },
        onGridReady: function () {
            if (angular.isDefined(vm.gridOptions.api)) {
                setTimeout(function () {
                    vm.setGridData();
                })
                vm.setColumnDef();
            }
        },
        context: {
            scope: $scope,
            mode: vm.action,
            mealBenefitTypeOptions: vm.mealBenefitTypeOptions,
            mealTypes: vm.mealTypes,
            date: vm.selectedDay,
            schoolClosed: vm.schoolClosed,
            week: vm.selectedWeek,
            subject_id: vm.selectedSubject,
            education_grade_id: vm.selectedEducationGrade,
            meal_program_id: vm.selectedMealProgram
        },
        getRowHeight: getRowHeight,
    };


    function handleError(error) {
        removeLoader();
        console.error(error);
        AlertSvc.warning($scope, error);
        return false;
    }

    function removeLoader() {
        UtilsSvc.isAppendLoader(false);
    }

    function appendLoader() {
        AlertSvc.reset($scope);
        UtilsSvc.isAppendLoader(true);
    }

    function setDayMonthYear() {
        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth() + 1;
        var currentdate = currentDate.getDate();
        // console.log(currentYear + '-' +currentMonth + '-' + currentdate)
        vm.currentDayMonthYear = currentYear + '-' + currentMonth + '-' + currentdate;
    }

    function getTranslatedText() {
        var promise;
        promise = InstitutionStudentMealsSvc.getTranslatedText();
        return promise.then(function (result) {
            return result;
        });
    }


    function getAcademicPeriodOptions() {
        var promise;
        promise = InstitutionStudentMealsSvc.getAcademicPeriodOptions(vm.institutionId);
        return promise.then(function (result) {
            return result;
        });
    }

    function setAcademicPeriodOptions(result) {
        vm.academicPeriodOptions = result;
        // console.log(vm.academicPeriodOptions);
        if (result.length > 0) {

            for (var i = 0; i < result.length; ++i) {
                vm.selectedAcademicPeriod = result[0].id;
                if (angular.isDefined(result[i]['selected'] && result[i]['selected'])) {
                    vm.selectedAcademicPeriod = result[i].id;
                    break;
                }
            }

        }

    }

    function getWeekListOptions() {
        var promise;
        promise = InstitutionStudentMealsSvc.getWeekListOptions(vm.selectedAcademicPeriod);
        return promise.then(function (result) {
            return result;
        });
    }

    function setWeekListOptions(result) {
        vm.weekListOptions = result;
        if (result.length > 0) {
            for (var i = 0; i < result.length; ++i) {
                if (angular.isDefined(result[i]['selected']) && result[i]['selected']) {
                    vm.selectedWeek = result[i].id;
                    vm.selectedWeekStartDate = result[i].start_day;
                    vm.selectedWeekEndDate = result[i].end_day;
                    vm.week = vm.selectedWeek;
                    vm.gridOptions.context.week = vm.selectedWeek;
                    break;
                }
            }
        }

    }

    function getDayListOptions() {
        var promise;
        var options = {
            institution_id: vm.institutionId,
            academic_period_id: vm.selectedAcademicPeriod,
            week_id: vm.selectedWeek,
            week_start_day: vm.selectedWeekStartDate,
            week_end_day: vm.selectedWeekEndDate,
        };
        promise = InstitutionStudentMealsSvc.getDayListOptions(options);
        return promise.then(function (result) {
            return result;
        });
    }

    function setDayListOptions(dayListOptions) {
        vm.dayListOptions = dayListOptions;
        var hasSelected = false;
        var daySelected = 0;
        if (dayListOptions.length > 0) {
            for (var i = 0; i < dayListOptions.length; ++i) {
                if (angular.isDefined(dayListOptions[i]['selected']) && dayListOptions[i]['selected']) {
                    hasSelected = true;
                    daySelected = i;
                    break;
                }
            }

            vm.selectedDay = dayListOptions[daySelected].date;
            vm.schoolClosed = (angular.isDefined(dayListOptions[daySelected]['closed']) && dayListOptions[daySelected]['closed']) ? true : false;
            vm.gridOptions.context.date = vm.selectedDay;
            vm.gridOptions.context.schoolClosed = vm.schoolClosed;
        }

        if (dayListOptions.length < 1) {
            vm.selectedDay = -1;
            vm.gridOptions.context.date = vm.selectedDay;
        }
    }

    function getClassOptions() {
        var promise;
        promise = InstitutionStudentMealsSvc.getClassOptions(vm.institutionId,
            vm.selectedAcademicPeriod);
        return promise.then(function (result) {
            return result;
        });
    }

    function setClassOptions(classListOptions) {
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

    function getMealProgramOptions() {
        var promise;
        var options = {
            institution_id: vm.institutionId,
            academic_period_id: vm.selectedAcademicPeriod};
        promise = InstitutionStudentMealsSvc.getMealProgramOptions(options);
        return promise.then(function (result) {
            return result;
        });
    }

    function setMealProgramOptions(mealProgram) {
        if (mealProgram.length !== 0) {
            vm.mealProgramOptions = mealProgram;
            vm.selectedMealProgram = vm.mealProgramOptions[0].id;
        } else {
            vm.mealProgramOptions = mealProgram;
            vm.selectedMealProgram = vm.mealProgramOptions;
        }
    }

    function getClassStudents() {
        var promise;
        var options = {
            institution_id: vm.institutionId,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            week_id: vm.selectedWeek,
            week_start_day: vm.selectedWeekStartDate,
            week_end_day: vm.selectedWeekEndDate,
            institution_class_id: vm.selectedClass,
            meal_program_id: vm.selectedMealProgram,
        };
        promise = InstitutionStudentMealsSvc.getClassStudent(options);

        return promise.then(function (result) {
            return result;
        });
    }

    function setClassStudents(classStudents) {
        vm.classStudents = [];
        vm.classStudentList = classStudents;
        // console.log(classStudents);
    }

    function setExcelExportUrl() {
        vm.excelExportAUrl = vm.exportexcel +
            '?institution_id=' + vm.institutionId +
            '&institution_class_id=' + vm.selectedClass +
            '&education_grade_id=' + vm.selectedEducationGrade +
            '&academic_period_id=' + vm.selectedAcademicPeriod +
            '&day_id=' + vm.selectedDay +
            '&attendance_period_id=' + vm.selectedAttendancePeriod +
            '&week_start_day=' + vm.selectedWeekStartDate +
            '&week_end_day=' + vm.selectedWeekEndDate +
            '&subject_id=' + vm.selectedSubject +
            '&week_id=' + vm.selectedWeek;
    }

    // ready
    angular.element(document).ready(function () {

        setDayMonthYear();
        InstitutionStudentMealsSvc.init(angular.baseUrl, $scope);
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;

        appendLoader();
        if (vm.institutionId === null) {
            vm.initGrid();
            removeLoader();
            return;
        }
        // debugger;
        getTranslatedText()
            .then(getAcademicPeriodOptions)
            .then(setAcademicPeriodOptions)
            .then(getWeekListOptions)
            .then(setWeekListOptions)
            .then(getDayListOptions)
            .then(setDayListOptions)
            .then(getClassOptions)
            .then(setClassOptions)
            .then(getMealProgramOptions)
            .then(setMealProgramOptions)
            .then(getClassStudents)
            .then(setClassStudents)
            .then(function () {
                vm.initGrid();
                removeLoader();
            }).catch(handleError);
    });

    // error
    vm.error = function (error, test) {
        return $q.reject(error);
    }

    // update data
    // vm.updateWeekList = function (weekListOptions) {
    //     vm.weekListOptions = weekListOptions;
    //     if (weekListOptions.length > 0) {
    //         for (var i = 0; i < weekListOptions.length; ++i) {
    //             if (angular.isDefined(weekListOptions[i]['selected']) && weekListOptions[i]['selected']) {
    //                 vm.selectedWeek = weekListOptions[i].id;
    //                 vm.selectedWeekStartDate = weekListOptions[i].start_day;
    //                 vm.selectedWeekEndDate = weekListOptions[i].end_day;
    //                 vm.week = vm.selectedWeek;
    //                 vm.gridOptions.context.week = vm.selectedWeek;
    //                 break;
    //             }
    //         }
    //     }
    // }
    //
    // vm.updateDayList = function (dayListOptions) {
    //
    //     vm.dayListOptions = dayListOptions;
    //     // console.log(vm.dayListOptions);
    //     var hasSelected = false;
    //     // vm.dayListOptions.splice(0, 1); // uncomment when All day needed in dropdown
    //     if (dayListOptions.length > 0) {
    //         for (var i = 0; i < dayListOptions.length; ++i) {
    //             if (angular.isDefined(dayListOptions[i]['selected']) && dayListOptions[i]['selected']) {
    //                 hasSelected = true;
    //                 vm.selectedDay = dayListOptions[i].date;
    //                 //START:POCOR:6609
    //                 setTimeout(() => {
    //                     // localStorage.removeItem('academic_period_id');
    //                     // localStorage.removeItem('dataSource');
    //                     // localStorage.setItem('academic_period_id', vm.selectedAcademicPeriod);
    //                     // localStorage.removeItem('academic_period_id');
    //                     localStorage.setItem('academic_period_id', vm.selectedAcademicPeriod);
    //                     // let date = new Date(vm.selectedDay);
    //                     // localStorage.setItem('current_day_number', date.getDay());
    //                 }, 1000)
    //                 //END:POCOR:6609
    //
    //                 vm.schoolClosed = (angular.isDefined(dayListOptions[i]['closed']) && dayListOptions[i]['closed']) ? true : false;
    //                 vm.gridOptions.context.date = vm.selectedDay;
    //                 vm.gridOptions.context.schoolClosed = vm.schoolClosed;
    //                 break;
    //             }
    //         }
    //
    //         if (!hasSelected) {
    //             vm.selectedDay = dayListOptions[0].date;
    //             vm.schoolClosed = (angular.isDefined(dayListOptions[0]['closed']) && dayListOptions[0]['closed']) ? true : false;
    //             vm.gridOptions.context.date = vm.selectedDay;
    //             vm.gridOptions.context.schoolClosed = vm.schoolClosed;
    //         }
    //     }
    // }
    //
    // vm.updateClassList = function (classListOptions) {
    //     vm.classListOptions = classListOptions;
    //     if (classListOptions.length > 0) {
    //         vm.selectedClass = classListOptions[0].id;
    //         if (classListOptions[0].SecurityRoleFunctions) {
    //             vm.superAdmin = 0;
    //             vm.permissionView = classListOptions[0].SecurityRoleFunctions._view;
    //             vm.permissionEdit = classListOptions[0].SecurityRoleFunctions._edit;
    //         }
    //     }
    // }
    //
    // vm.updateMealPrograme = function (mealProgram) {
    //     //START:POCOR:6609
    //     if (mealProgram.length !== 0) {
    //         vm.mealProgramOptions = mealProgram;
    //         vm.selectedMealProgram = vm.mealProgramOptions[0].id;
    //     } else {
    //         vm.mealProgramOptions = mealProgram;
    //         vm.selectedMealProgram = vm.mealProgramOptions;
    //     }
    //     //END:POCOR:6609
    // }
    //
    // vm.updateEducationGradeList = function (educationGradeListOptions) {
    //     vm.educationGradeListOptions = educationGradeListOptions;
    //     if (educationGradeListOptions.length > 0) {
    //         vm.selectedEducationGrade = educationGradeListOptions[0].id;
    //         vm.gridOptions.context.education_grade_id = vm.selectedEducationGrade;
    //     }
    // }
    //
    // vm.updateSubjectList = function (subjectListOptions, isMarkableSubjectAttendance) {
    //     vm.subjectListOptions = subjectListOptions;
    //     if (vm.isMarkableSubjectAttendance == true) {
    //         if (subjectListOptions.length > 0) {
    //             vm.selectedSubject = subjectListOptions[0].id;
    //             vm.gridOptions.context.subject_id = vm.selectedSubject;
    //         }
    //     } else {
    //         vm.selectedSubject = 0;
    //         vm.gridOptions.context.subject_id = vm.selectedSubject;
    //     }
    // }
    //
    //
    // vm.subjectListOptions = function (subjectListOptions) {
    //     vm.subjectListOptions = subjectListOptions;
    //     if (subjectListOptions.length > 0) {
    //         vm.selectedSubject = subjectListOptions[0].id;
    //     }
    // }
    //
    // vm.updateAttendancePeriodList = function (attendancePeriodOptions) {
    //     vm.attendancePeriodOptions = attendancePeriodOptions;
    //     if (attendancePeriodOptions.length > 0) {
    //         vm.selectedAttendancePeriod = attendancePeriodOptions[0].id;
    //         vm.gridOptions.context.period = vm.selectedAttendancePeriod;
    //     }
    // }
    //
    // vm.updateClassStudentList = function (classStudents) {
    //     vm.classStudents = [];
    //     vm.classStudentList = classStudents;
    //
    // }
    //
    // vm.updateIsMarked = function (isMarked) {
    //     vm.isMarked = isMarked;
    //     vm.gridOptions.context.isMarked = isMarked;
    // };

    // grid
    vm.initGrid = function () {
        AggridLocaleSvc.getTranslatedGridLocale().then(
            function (localeText) {
                vm.gridOptions.localeText = localeText;
                vm.gridReady = true;
            },
            function (error) {
                vm.gridReady = true;
            }
        );
    }

    function getRowHeight(params) {
        return params.data.rowHeight;
    }


    vm.setGridData = function () {
        // console.log(vm.classStudentList);
        if (angular.isDefined(vm.gridOptions.api)) {
            // vm.gridOptions.api.setRowData(vm.classStudentList);
            vm.setRowDatas(vm.classStudentList);
            vm.countStudentData();

        }
    }

    vm.setRowDatas = function (studentList) {
        // console.log('studentList controller',studentList);
        studentList.forEach(function (dataItem, index) {
            if (dataItem.hasOwnProperty('institution_student_meal')) {
                if (dataItem.institution_student_meal.meal_received_id == 2 || dataItem.institution_student_meal.meal_received_id == 3) {
                    dataItem.rowHeight = 60;
                } else {
                    dataItem.rowHeight = 60;
                }
            } else {
                dataItem.rowHeight = 80;
            }
        });
        vm.gridOptions.api.setRowData(studentList);

    }

    vm.setColumnDef = function () {
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
    vm.countStudentData = function () {
        var mealType = InstitutionStudentMealsSvc.getMealTypeList();
        if (vm.selectedDay != -1) {
            // single day
            vm.totalStudents = vm.classStudentList.length;
            if (vm.isMarked) {
                var presentCount = 0;
                var absenceCount = 0;
                var lateCount = 0;

                if (vm.totalStudents > 0) {
                    angular.forEach(vm.classStudentList, function (obj, key) {
                        if (angular.isDefined(obj['institution_student_meal']) && angular.isDefined(obj['institution_student_meal']['meal_received'])) {
                            var code = obj['institution_student_meal']['meal_received'];
                            // console.log('mealType',mealType);
                            // console.log('code',code);
                            switch (code) {
                                case null:
                                case mealType.Free.code:
                                    // ++presentCount;
                                    break;
                                case mealType.Paid.code:
                                    ++presentCount;
                                    // ++lateCount;
                                    break;

                                case mealType.None.code:
                                    // ++absenceCount;
                                    break;
                            }
                        }
                    });
                }

                vm.presentCount = presentCount;
                vm.absenceCount = absenceCount;
                vm.lateCount = lateCount;
            } else {
                //START: POCOR-6936
                var presentCount = 0;
                if (vm.totalStudents > 0) {
                    angular.forEach(vm.classStudentList, function (obj, key) {
                        // console.log(obj);
                        if (angular.isDefined(obj['institution_student_meal']) && angular.isDefined(obj['institution_student_meal']['meal_received'])) {
                            var code = obj['institution_student_meal']['meal_received'];
                            // console.log(code);
                            if (code == 'Received') {
                                ++presentCount;
                            }

                        }
                    });
                }
                //END: POCOR-6936
                vm.presentCount = presentCount;
                vm.absenceCount = '-';
                vm.lateCount = '-';
            }
        } else {
            // all day
            var allAttendances = '-';
            var allPresentCount = '-';
            var allAbsenceCount = '-';
            var allLateCount = '-';
            var allMeals = '-';
            var allFreeMealCount = '-';
            var allPaidMealCount = '-';

            if (vm.totalStudents > 0) {
                allAttendances = 0;
                allPresentCount = 0;
                allAbsenceCount = 0;
                allLateCount = 0;

                allMeals = 0;
                allFreeMealCount = 0;
                allPaidMealCount = 0;

                angular.forEach(vm.classStudentList, function (obj, studentKey) {
                    if (angular.isDefined(obj.week_meals) && Object.keys(obj.week_meals).length > 0) {
                        var weekAttendance = obj.week_meals;
                        angular.forEach(weekAttendance, function (day, dayKey) {
                            if (Object.keys(day).length > 0) {
                                angular.forEach(day, function (period, periodKey) {
                                    // console.log('period', period);
                                    switch (period) {
                                        case mealType.Paid.code:
                                            ++allPaidMealCount;
                                            break;
                                        case mealType.Free.code:
                                            ++allFreeMealCount;
                                            break;

                                        case mealType.None.code:
                                            break;
                                    }
                                });
                            }
                        });
                    }
                });
            }

            vm.allMeals = allFreeMealCount + allPaidMealCount;
            vm.allFreeMealCount = allFreeMealCount;
            vm.allPaidMealCount = allPaidMealCount;

        }
    }

    // params
    vm.getClassStudentParams = function () {
        vm.excelExportAUrl = vm.exportexcel
            + '?institution_id=' + vm.institutionId +
            '&institution_class_id=' + vm.selectedClass +
            // '&education_grade_id='+ vm.selectedEducationGrade+
            '&academic_period_id=' + vm.selectedAcademicPeriod +
            '&day_id=' + vm.selectedDay +
            // '&attendance_period_id='+ vm.selectedAttendancePeriod+
            '&week_start_day=' + vm.selectedWeekStartDate +
            '&week_end_day=' + vm.selectedWeekEndDate +
            '&week_id=' + vm.selectedWeek
        // console.log(vm.excelExportAUrl);
        // console.log(vm.exportexcel);

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
            subject_id: vm.selectedSubject,
            meal_program_id: vm.selectedMealProgram
        };
    }

    vm.getIsMarkedParams = function () {
        return {
            // institution_id: vm.institutionId,
            // institution_class_id: vm.selectedClass,
            // education_grade_id: vm.selectedEducationGrade,
            // academic_period_id: vm.selectedAcademicPeriod,
            // day_id: vm.selectedDay,
            // attendance_period_id: vm.selectedAttendancePeriod,
            // subject_id: vm.selectedSubject
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            meal_programm_id: vm.selectedMealProgram,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
        };
    }

    vm.getPeriodMarkedParams = function () {
        return {
            institution_id: vm.institutionId,
            academic_period_id: vm.selectedAcademicPeriod,
            institution_class_id: vm.selectedClass,
            meal_programm_id: vm.selectedMealProgramme,
            day: vm.selectedDay,
        };
    }

    // changes
    vm.changeAcademicPeriod = function () {
        appendLoader();
        getWeekListOptions()
            .then(setWeekListOptions)
            .then(getDayListOptions)
            .then(setDayListOptions)
            .then(getClassOptions)
            .then(setClassOptions)
            .then(getMealProgramOptions)
            .then(setMealProgramOptions)
            .then(getClassStudents)
            .then(setClassStudents)
            .then(function () {
                vm.initGrid();
                removeLoader();
            }).catch(handleError);
    };

    vm.changeWeek = function () {
        appendLoader();
        var weekObj = vm.weekListOptions.find(obj => obj.id == vm.selectedWeek);
        vm.selectedWeekStartDate = weekObj.start_day;
        vm.selectedWeekEndDate = weekObj.end_day;
        vm.gridOptions.context.week = vm.selectedWeek;
        getDayListOptions()
        .then(setDayListOptions)
            .then(getClassOptions)
            .then(setClassOptions)
            .then(getMealProgramOptions)
            .then(setMealProgramOptions)
            .then(getClassStudents)
            .then(setClassStudents)
            .then(function () {
                vm.initGrid();
                removeLoader();
            }).catch(handleError);
    }

    vm.changeDay = function () {
        appendLoader();
        var dayObj = vm.dayListOptions.find(obj => obj.date == vm.selectedDay);
        vm.schoolClosed = (angular.isDefined(dayObj.closed) && dayObj.closed) ? true : false;
        vm.gridOptions.context.schoolClosed = vm.schoolClosed;
        vm.gridOptions.context.date = vm.selectedDay;

        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth() + 1;
        var currentdate = currentDate.getDate();
        vm.currentDayMonthYear = currentYear + '-' + currentMonth + '-' + currentdate;
            getClassOptions()
            .then(setClassOptions)
            .then(getMealProgramOptions)
            .then(setMealProgramOptions)
            .then(getClassStudents)
            .then(setClassStudents)
            .then(function () {
                vm.initGrid();
                removeLoader();
            }).catch(handleError);
    }

    vm.changeClass = function () {
        appendLoader();
        if (vm.superAdmin == 0) {
            vm.updateClassRoles(vm.selectedClass);
        }
            getMealProgramOptions()
            .then(setMealProgramOptions)
            .then(getClassStudents)
            .then(setClassStudents)
            .then(function () {
                vm.initGrid();
                removeLoader();
            }).catch(handleError);
    }

    vm.changeMealPrograme = function () {
        appendLoader();
        getClassStudents()
            .then(setClassStudents)
            .then(function () {
                vm.initGrid();
                removeLoader();
            }).catch(handleError);
    }


    // button events
    vm.onEditClick = function () {
        // console.log('vm',vm.mealBenefitTypeOptions);
        vm.action = 'edit';
        vm.gridOptions.context.mode = vm.action;
        vm.gridOptions.context.meal_programme_id = vm.selectedMealProgramme;
        vm.setColumnDef();
        AlertSvc.info($scope, 'Meal will be automatically saved.');
        InstitutionStudentMealsSvc.savePeriodMarked(vm.getPeriodMarkedParams(), $scope, vm.mealBenefitTypeOptions);
    };

    vm.onBackClick = function () {
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;
        UtilsSvc.isAppendLoader(true);
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentMealsSvc.getClassStudent(vm.getClassStudentParams())
            .then(function (classStudents) {
                vm.updateClassStudentList(classStudents);
            }, vm.error)

            .finally(function () {
                UtilsSvc.isAppendLoader(false);
                vm.setGridData();
                vm.setColumnDef();
            });
    };

    vm.onExcelClick = function () {
        var excelUrlWithQuery = vm.excelUrl + '?' +
            'institution_id=' + vm.institutionId + '&' +
            'academic_period_id=' + vm.selectedAcademicPeriod + '&' +
            'class_id=' + vm.selectedClass;

        window.location.href = excelUrlWithQuery;
        return;
    }

    vm.updateClassRoles = function (selectedClass) {
        var selectedClass = selectedClass;
        var classListOptions = vm.classListOptions;
        if (classListOptions.length > 0) {
            angular.forEach(classListOptions, function (value, key) {
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
