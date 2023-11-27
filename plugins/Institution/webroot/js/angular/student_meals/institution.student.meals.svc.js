angular
    .module('institution.student.meals.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStudentMealsSvc', InstitutionStudentMealsSvc);

InstitutionStudentMealsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function InstitutionStudentMealsSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {

    const mealType = {
        'Received': {
            id: 1,
            code: 'Received',
            icon: 'fa fa-check',
            color: '#77B576'
        },
        'NotReceived': {
            id: 2,
            code: 'NotReceived',
            icon: 'fa fa-times',
            color: '#CC5C5C'
        },
        'None': {
            id: 3,
            code: 'None',
            icon: 'fa fa-minus',
            color: '#999999'
        }
    };

    const ALL_DAY_VALUE = -1;

    var translateText = {
        'original': {
            'OpenEmisId': 'OpenEMIS ID',
            'Name': 'Name',
            'MealReceived': 'Meal Received',
            'BenefitType': 'Benefit Type',
            'Monday': 'Monday',
            'Tuesday': 'Tuesday',
            'Wednesday': 'Wednesday',
            'Thursday': 'Thursday',
            'Friday': 'Friday',
            'Saturday': 'Saturday',
            'Sunday': 'Sunday'
        },
        'translated': {}
    };

    var controllerScope;

    var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        StudentAttendances: 'Institution.StudentAttendances',
        StudentMeals: 'Institution.StudentMeals',
        InstitutionClasses: 'Institution.InstitutionClasses',
        StudentMealDetails: 'Institution.InstitutionMealStudents',
        StudentAttendanceMarkTypes: 'Attendance.StudentAttendanceMarkTypes',
        StudentAttendanceMarkedRecords: 'Meal.StudentAttendanceMarkedRecords',
        MealBenefit: 'Meal.MealBenefit',
        MealProgrammes: 'Meal.MealProgrammes',
        MealInstitutionProgrammes: 'Meal.MealInstitutionProgrammes',
        StudentMealMarkedRecords: 'Meal.StudentMealMarkedRecords',
        MealReceived: 'Meal.MealReceived'
    };

    var service = {
        init: init,
        translate: translate,
        getMealTypeList: getMealTypeList,

        getTranslatedText: getTranslatedText,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getDayListOptions: getDayListOptions,
        getClassOptions: getClassOptions,
        getMealProgramOptions: getMealProgramOptions,
        getStudents: getStudents,
        saveStudents: saveStudents,

        getSingleDayColumnDefs: getSingleDayColumnDefs,
        getAllDayColumnDefs: getAllDayColumnDefs,

        saveStudentMeal: saveStudentMeal,
        markDayMeal: markDayMeal,

        getBenefitOptions: getBenefitOptions,
        getMealReceivedOptions: getMealReceviedOptions,

    };

    return service;

    function init(baseUrl, scope) {
        controllerScope = scope;
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentMeals');
        KdDataSvc.init(models);
    }

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function (response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success: success, defer: true});
    }

    function getMealTypeList() {
        return mealType;
    }

    // data service
    function getTranslatedText() {
        var success = function (response, deferred) {
            var translatedObj = response.data;
            if (angular.isDefined(translatedObj)) {
                translateText = translatedObj;
            }
            deferred.resolve(angular.isDefined(translatedObj));
        };

        KdDataSvc.init({translation: 'translate'});
        return translation.translate(translateText.original, {
            success: success,
            defer: true
        });
    }

    function getAcademicPeriodOptions(institutionId) {

        var success = function (response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                deferred.resolve(periods);
            } else {
                deferred.reject('The list of academic periods with classes is empty');
            }
        };

        return AcademicPeriods
            .find('periodHasClass', {
                institution_id: institutionId
            })
            .ajax({success: success, defer: true});
    }

    function getWeekListOptions(academicPeriodId) {
        var success = function (response, deferred) {
            var academicPeriodObj = response.data.data;
            if (angular.isDefined(academicPeriodObj) && academicPeriodObj.length > 0) {
                var weeks = academicPeriodObj[0].weeks; // find only 1 academic period entity
                if (angular.isDefined(weeks) && weeks.length > 0) {
                    deferred.resolve(weeks);
                } else {
                    deferred.reject('The week list is empty');
                }
            } else {
                deferred.reject('There was an error when retrieving the week list');
            }
        };

        return AcademicPeriods
            .find('weeksForPeriodMeal', {
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    }

    function getDayListOptions(options) {
        var success = function (response, deferred) {
            var dayList = response.data.data;
            if (angular.isObject(dayList) && dayList.length > 0) {
                deferred.resolve(dayList);
            } else {
                deferred.reject('There was an error when retrieving the day list');
            }
        };

        return AcademicPeriods
            .find('daysForPeriodWeekMeal', options)
            .ajax({success: success, defer: true});
    }

    function getClassOptions(institutionId, academicPeriodId) {
        var success = function (response, deferred) {
            var classList = response.data.data;
            if (angular.isObject(classList)) {
                if (classList.length > 0) {
                    deferred.resolve(classList);
                } else {
                    AlertSvc.warning(controllerScope, 'You do not have any classes');
                    deferred.reject('You do not have any classess');
                }
            } else {
                deferred.reject('There was an error when retrieving the class list');
            }
        };

        return InstitutionClasses
            .find('classesByInstitutionAndAcademicPeriod', {
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});

        return [];
    }

    function getMealProgramOptions(options) {

        var success = function (response, deferred) {

            var mealPrograms = response.data.data;
            if (mealPrograms) {
                deferred.resolve(mealPrograms);
            } else {
                deferred.reject('Empty Meal Program List');
            }
        };

        return MealInstitutionProgrammes
            .find('mealInstitutionPrograms', options)
            .ajax({success: success, defer: true});
    }

    function getMealReceviedOptions() {

        var success = function (response, deferred) {

            var mealPrograms = response.data.data;
            if (mealPrograms) {
                deferred.resolve(mealPrograms);
            } else {
                deferred.reject('Empty Institution Meal Programs');
            }
        };

        return MealReceived
            .select(['id', 'name'])
            .ajax({success: success, defer: true});
    }

    function getBenefitOptions() {
        var success = function (response, deferred) {
            // console.log('getBenefitOptions', response);
            var mealBenefitType = response.data.data;
            if (angular.isObject(mealBenefitType) && mealBenefitType.length > 0) {
                deferred.resolve(mealBenefitType);
            } else {
                deferred.reject('There was an error when retrieving benefit type options');
            }
        };

        return MealBenefit
            .select(['id', 'name', 'default'])
            .order(['order'])
            .ajax({success: success, defer: true});
    }

    function getStudents(options) {
        if (options.institution_class_id == ''
            || options.academic_period_id == ''
            || options.meal_program_id == '') {
            return $q.reject('Please select necessary options');
        }

        var success = function (response, deferred) {
            var classStudents = response.data.data;
            // console.log('getClassStudent');
            // console.log(options);
            // console.log(classStudents);
            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                deferred.reject('There was an error when retrieving the class student list');
            }
        };

        return StudentMeals
            .find('classStudentsWithMeal', options)
            .ajax({success: success, defer: true});
    }

    function saveStudents(options) {
        if (options.institution_class_id == ''
            || options.academic_period_id == ''
            || options.meal_program_id == '') {
            return $q.reject('Please select necessary options');
        }

        var success = function (response, deferred) {
            var classStudents = response.data.data;
            // console.log('getClassStudent');
            // console.log(response);
            // console.log(classStudents);
            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                // console.log(response);
                deferred.reject('There was an error when saving the class student list');
            }
        };

        return StudentMeals
            .find('classStudentsWithMealSave', options)
            .ajax({success: success, defer: true});
    }

    // save error
    function clearError(data, skipKey) {
        if (angular.isUndefined(data.save_error)) {
            data.save_error = {};
        }

        angular.forEach(data.save_error, function (error, key) {
            if (key != skipKey) {
                data.save_error[key] = false;
            }
        })
    }

    function hasError(data, key) {
        return (angular.isDefined(data.save_error) && angular.isDefined(data.save_error[key]) && data.save_error[key]);
    }

    // save
    function saveStudentMeal(options) {
        if (options.id == '') {
            return $q.reject('Please select necessary options');
        }

        var success = function (response, deferred) {
            var classStudents = response.data.data;
            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                // console.log(response);
                deferred.reject('There was an error when saving the class student list');
            }
        };

        return StudentMeals
            .find('classStudentWithMealSave', options)
            .ajax({success: success, defer: true});
    }

    function markDayMeal(params, scope) {
        params['meal_programmes_id'] = params['meal_program_id'];
        params['date'] = params['day'];
        UtilsSvc.isAppendSpinner(true);
        StudentMealMarkedRecords.save(params)
            .then(
                function (response) {
                    UtilsSvc.isAppendSpinner(false);
                    AlertSvc.info(scope, 'Daily Meal Mark Is Saved');
                    return response;
                },
                function (error) {
                    UtilsSvc.isAppendSpinner(false);
                    console.error(error);
                    AlertSvc.error(scope, 'There was an error when marking the record');
                    return error;
                }
            )
            .finally(function () {
                UtilsSvc.isAppendSpinner(false);
                return true;
            });

    }

    // column definitions
    function getAllDayColumnDefs(dayList, attendancePeriodList) {
        var columnDefs = [];
        var menuTabs = ["filterMenuTab"];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: 'keep'
        };
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }
        columnDefs.push({
            headerName: translateText.translated.OpenEmisId,
            field: "user.openemis_no",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.Name,
            field: "user.name",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });

        angular.forEach(dayList, function (dayObj, dayKey) {
            if (dayObj.id != -1) {
                var childrenColDef = [];
                angular.forEach(attendancePeriodList, function (periodObj, periodKey) {
                    childrenColDef.push({
                        headerName: periodObj.id,
                        field: 'week_meals.' + dayObj.day + '.' + periodObj.id,
                        suppressSorting: true,
                        suppressResize: true,
                        menuTabs: [],
                        minWidth: 30,
                        headerClass: 'children-period',
                        cellClass: 'children-cell',
                        cellRenderer: function (params) {
                            if (angular.isDefined(params.value)) {
                                var code = params.value;
                                return getViewAllDayAttendanceElement(code);
                            }
                        }
                    });
                });

                var dayText = dayObj.name;

                var colDef = {
                    headerName: dayText,
                    children: childrenColDef
                };

                columnDefs.push(colDef);
            }
        });

        return columnDefs;
    }

    function getSingleDayColumnDefs() {
        var columnDefs = [];
        var menuTabs = ["filterMenuTab"];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: 'keep'
        };
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }

        const cellDefOpenemisNo = {
            headerName: translateText.translated.OpenEmisId,
            field: 'user.openemis_no',
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: 'text'
        };

        const cellDefUserName = {
            headerName: translateText.translated.Name,
            field: 'user.name',
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: 'text'
        };

        columnDefs.push(cellDefOpenemisNo);

        columnDefs.push(cellDefUserName);

        function cellRendMealReceived() {
            return function (params) {

                if (angular.isDefined(params.value)) {
                    var context = params.context;
                    var mode = params.context.mode;
                    var data = params.data;

                    if (mode === 'view') {
                        return getViewMealElement(data);
                    } else if (mode === 'edit') {
                        var api = params.api;
                        return getEditMealElement(data, api, context);
                    }
                }
            };
        }

        const cellDefMealReceived = {
            headerName: translateText.translated.MealReceived,
            field: "meal_received_id",
            suppressSorting: true,
            menuTabs: [],
            cellRenderer: cellRendMealReceived()
        };

        columnDefs.push(cellDefMealReceived);

        columnDefs.push({
            headerName: translateText.translated.BenefitType,
            field: "meal_benefit_id",
            menuTabs: [],
            suppressSorting: true,
            cellRenderer: function (params) {
                // alert('renderMe!');
                if (angular.isDefined(params.value)) {
                    var data = params.data;
                    var context = params.context;
                    var mealBenefitTypeOptions = context.mealBenefitTypeOptions;
                    var mealReceivedOptions = context.mealReceivedOptions;
                    var mode = context.mode;

                    if (angular.isDefined(params.data)) {
                        var mealReceivedId = (params.data.meal_received_id == null) ? 0 : params.data.meal_received_id;
                        // console.log("studentMealTypeId", studentMealTypeId)
                        var mealReceivedOption = mealReceivedOptions.find(obj => obj.id == mealReceivedId);
                        if (mode == 'view') {
                            if (mealReceivedId != 1) {
                                return '<i style="color: #999999;" class="fa fa-minus"></i>';
                            } else if ((mealReceivedId == 1
                                && params.data.meal_benefit_id == null)) {
                                var html = '100%';
                            }
                            //END: POCOR-6609
                            else {
                                var html = params.data.meal_benefit_name;
                            }

                            return html;
                        } else if (mode == 'edit') {
                            var api = params.api;
                            if (mealReceivedOption != undefined) {
                                switch (mealReceivedOption.name) {
                                    case 'None':
                                        return '<i style="color: #999999;" class="fa fa-minus"></i>';
                                    case 'Not Received':
                                        return '<i style="color: #999999;" class="fa fa-minus"></i>';
                                    case 'Received':
                                        var eCell = document.createElement('div');
                                        eCell.setAttribute("class", "reason-wrapper");
                                        // console.log('mealBenefitTypeOptions', mealBenefitTypeOptions);
                                        var eSelect = getEditMealBenefiteElement(data, mealBenefitTypeOptions, context, api);
                                        // var eTextarea = getEditCommentElement(data, context, api);
                                        eCell.appendChild(eSelect);
                                        // eCell.appendChild(eTextarea);
                                        return eCell;
                                    default:
                                        break;
                                }
                            } else {
                                return '<i style="color: #999999;" class="fa fa-minus"></i>';
                            }


                        }
                    }
                }
            }
        });

        return columnDefs;
    }

    // cell renderer elements
    function getEditMealElement(data, api, context) {
        var mealReceivedOptions = context.mealReceivedOptions;
        // console.log('onedit', data.institution_student_meal);
        var dataKey = 'meal_received_id';
        var scope = context.scope;
        var eCell = document.createElement('div');
        eCell.setAttribute("class", "oe-select-wrapper input-select-wrapper");
        eCell.setAttribute("id", dataKey);

        if (data[dataKey] == null) {
            //set default value
            data[dataKey] = data.default_meal_receive_id; //POCOR-7662
        }

        var eSelect = document.createElement("select");
        angular.forEach(mealReceivedOptions, function (receiveOption, key) {
            // console.log('onedit', receiveOption);
            // console.log('onedit', data.institution_student_meal[dataKey]);
            var eOption = document.createElement("option");
            var labelText = receiveOption.name;
            eOption.setAttribute("value", receiveOption.id);
            eOption.innerHTML = labelText;
            eSelect.appendChild(eOption);

            if (hasError(data, dataKey)) {
                eSelect.setAttribute("class", "error");
            }

            if (data[dataKey] === receiveOption.id) {
                // Select the option that matches the value in data[dataKey]
                eOption.selected = true;
            }
        });


        eSelect.value = data[dataKey];
        eSelect.addEventListener('change', function () {
            var oldValue = +data[dataKey];
            var newValue = +eSelect.value;
            // console.log(data);
            // console.log(eSelect.value);
            if (newValue != oldValue) {
                UtilsSvc.isAppendSpinner(true);
                const options = {
                    institution_meal_student_id: data['institution_meal_student_id'],
                    meal_received_id: newValue,
                    day_id: context.date,
                    meal_program_id: data['meal_program_id']
                };

                saveStudentMeal(options)
                    .then(
                        function () {
                            context.scope.$ctrl.changeMealProgram();
                            AlertSvc.info(scope, 'Meal is automatically saved.');
                        },
                        function (error) {
                            console.error(error);
                            eSelect.value = oldValue;
                            AlertSvc.error(scope, error);
                        }
                    )
                    .finally(function () {
                        UtilsSvc.isAppendSpinner(false);
                    });
            }
        });
        eCell.appendChild(eSelect);
        return eCell;
    }

    function getEditMealBenefiteElement(data, mealBenefitTypeOptions, context, api) {
        var dataKey = 'meal_benefit_id';
        var scope = context.scope;
        var eSelectWrapper = document.createElement('div');
        eSelectWrapper.setAttribute("class", "oe-select-wrapper input-select-wrapper");
        eSelectWrapper.setAttribute("id", dataKey);
        eSelectWrapper.setAttribute("style", "display: block");

        var eSelect = document.createElement("select");
        if (hasError(data, dataKey)) {
            eSelect.setAttribute("class", "error");
        }
        var mealBenifitIdByDefault;
        for (let i = 0; i < mealBenefitTypeOptions.length; i++) {
            // console.log(mealBenefitTypeOptions[i]);
            if (mealBenefitTypeOptions[i].default == 1) {
                mealBenifitIdByDefault = mealBenefitTypeOptions[i].id;
                break;
            }
        }

        if (data[dataKey] == null) {
            data[dataKey] = mealBenifitIdByDefault;
            // data.institution_student_meal[dataKey] = mealBenefitTypeOptions[0].id;
        }

        angular.forEach(mealBenefitTypeOptions, function (obj, key) {
            var eOption = document.createElement("option");
            var labelText = obj.name;
            eOption.setAttribute("value", obj.id);
            eOption.innerHTML = labelText;
            eSelect.appendChild(eOption);
        });

        eSelect.value = data[dataKey];
        eSelect.addEventListener('change', function () {
            var oldValue = +data[dataKey];
            var newValue = +eSelect.value;
            // console.log(data);
            // console.log(eSelect.value);
            if (newValue != oldValue) {
                UtilsSvc.isAppendSpinner(true);
                const options = {
                    institution_meal_student_id: data['institution_meal_student_id'],
                    only_change_benefit: true,
                    day_id: context.date,
                    meal_program_id: data['meal_program_id'],
                    meal_benefit_id: newValue
                };

                saveStudentMeal(options)
                    .then(
                        function () {
                            context.scope.$ctrl.changeMealProgram();
                            AlertSvc.info(scope, 'Meal is automatically saved.');
                        },
                        function (error) {
                            console.error(error);
                            eSelect.value = oldValue;
                            AlertSvc.error(scope, error);
                        }
                    )
                    .finally(function () {
                        UtilsSvc.isAppendSpinner(false);
                    });
            }

        })

        eSelectWrapper.appendChild(eSelect);
        return eSelectWrapper;
    }

    function getViewMealElement(data) {
        if (angular.isDefined(data)) {
            var html = '';
            const id = +data.meal_received_id;

            switch (id) {
                case mealType.Received.id:
                    html = '<i style="color: ' + mealType.Received.color + ';">Received</i>';
                    break;
                case mealType.NotReceived.id:
                    html = '<i style="color: ' + mealType.NotReceived.color + ';">Not Received</i>';
                    break;
                case mealType.None.id:
                    html = '<i style="color: ' + mealType.None.color + ';" class="' + mealType.None.icon + '"></i>';
                    break;
                default:
                    html = '<i style="color: ' + mealType.None.color + ';" class="' + mealType.None.icon + '"></i>';
                    break;
            }
            return html;

            //     if(data.institution_student_meal.meal_received_id == 1) {
            //     html = data.institution_student_meal.meal_received
            // }else if(data.institution_student_meal.meal_received_id == 2) {
            //     html = data.institution_student_meal.meal_received
            // }else if(data.institution_student_meal.meal_received_id == null
            //     || data.institution_student_meal.meal_received_id == 3) {
            //     // html = data.institution_student_meal.meal_received
            //     html='<i style="color: #999999;" class="fa fa-minus"></i>';
            // }
            //
            // return html;
        }
    }

    function getViewAllDayAttendanceElement(code) {
        // console.log("code", code);
        var html = '';
        // console.log(mealType)
        switch (code) {
            case mealType.Received.code:
                html = '<i style="color: ' + mealType.Received.color + ';">Received</i>';
                break;
            case mealType.NotReceived.code:
                html = '<i style="color: ' + mealType.NotReceived.color + ';">Not Received</i>';
                break;
            case mealType.None.code:
                html = '<i style="color: ' + mealType.None.color + ';" class="' + attendanceType.NOTMARKED.icon + '"></i>';
                break;

            default:
                html = '<i style="color: ' + mealType.None.color + ';" class="' + attendanceType.NOTMARKED.icon + '"></i>';
                break;
        }
        return html;
    }

};