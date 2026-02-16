angular
    .module('institution.class.students.svc', ['kd.data.svc'])
    .service('InstitutionClassStudentsSvc', InstitutionClassStudentsSvc);

InstitutionClassStudentsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionClassStudentsSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getClassDetails: getClassDetails,
        getUnassignedStudent: getUnassignedStudent,
        translate: translate,
        getInstitutionShifts: getInstitutionShifts,
        getInstitutionUnits: getInstitutionUnits,
        getInstitutionCourses: getInstitutionCourses,
        getClassCustomFields: getClassCustomFields,
        createCustomFieldsArray: createCustomFieldsArray,
        getTeacherOptions: getTeacherOptions,
        saveClass: saveClass,
        getConfigItemValue: getConfigItemValue
    };

    var models = {
        InstitutionStaff: 'Institution.Staff',
        InstitutionClasses: 'Institution.InstitutionClasses',
        InstitutionShifts: 'Institution.InstitutionShifts',
        InstitutionUnits: 'Institution.Unit',
        InstitutionCourses: 'Institution.Course',
        Users: 'User.Users',
        ConfigItemsTable: 'Configuration.ConfigItems'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('ClassStudents');
        KdDataSvc.init(models);
    };

    function getClassCustomFields(userId){
        var params = {
            class_id: userId,
        };
        var deferred = $q.defer();
        let url = angular.baseUrl + '/Institutions/classCustomFields';
        $http.post(url, {params: params})
            .then(function(response){
                deferred.resolve(response);
            }, function(error) {
                deferred.reject(error);
            });
        return deferred.promise;
    }
    function createCustomFieldsArray(scope) {
        // console.log('createCustomFieldsArray SVC')
        if (scope.customFields === "null") return;

        function mapBySection(item) {
            return item.section;
        }

        function filterBySection(item, section) {
            return section === item.section;
        }
        // console.log(scope.customFields);
        if(scope.customFields && Array.isArray(scope.customFields)) {
            var selectedCustomField = scope.customFields;


            var filteredSections = Array.from(new Set(scope.customFields.map((item) => mapBySection(item))));
            filteredSections.forEach((section)=>{
                let filteredArray = selectedCustomField.filter((item) => filterBySection(item, section));
                scope.customFieldsArray.push({sectionName: section , data: filteredArray});
            });
            console.log(filteredSections);
            scope.customFieldsArray.forEach((customField) => {
                customField.data.forEach((fieldData) => {
                    fieldData.answer = '';
                    fieldData.errorMessage = '';
                    if(fieldData.field_type === 'TEXT' || fieldData.field_type === 'TEXTAREA' || fieldData.field_type === 'NOTE') {
                        fieldData.answer = fieldData.values ? fieldData.values : '';
                    }
                    if(fieldData.field_type === 'DROPDOWN') {
                        fieldData.selectedOptionId = '';
                        fieldData.answer = fieldData.values && fieldData.values.length > 0 && fieldData.values[0].dropdown_val ? fieldData.values[0].dropdown_val : '';
                        fieldData.option.forEach((option) => {
                            if(option.option_id === fieldData.answer) {
                                fieldData.selectedOption = option.option_name;
                            }
                        })
                    }
                    if(fieldData.field_type === 'DATE') {
                        fieldData.isDatepickerOpen = false;
                        let params = fieldData.params !== '' ? JSON.parse(fieldData.params) : null;
                        fieldData.params = params;
                        fieldData.datePickerOptions = {
                            minDate: fieldData.params && fieldData.params.start_date ? new Date(fieldData.params.start_date): new Date(),
                            maxDate: new Date('01/01/2100'),
                            showWeeks: false
                        };
                        fieldData.answer = new Date(fieldData.values);
                    }
                    if(fieldData.field_type === 'TIME') {
                        fieldData.hourStep = 1;
                        fieldData.minuteStep = 5;
                        fieldData.isMeridian = true;
                        let params = fieldData.params !== '' ? JSON.parse(fieldData.params) : null;
                        fieldData.params = params;
                        if(fieldData.params && fieldData.params.start_time) {
                            var startTimeArray = fieldData.params.start_time.split(" ");
                            var startTimes = startTimeArray[0].split(":");
                            if(startTimes[0] === 12) {
                                var startTimeHour = startTimeArray[1] === 'PM' ? Number(startTimes[0]) : Number(startTimes[0]) - 12;
                            } else {
                                var startTimeHour = startTimeArray[1] === 'AM' ? Number(startTimes[0]) : Number(startTimes[0]) + 12;
                            }
                        }
                        if(fieldData.params && fieldData.params.end_time) {
                            var endTimeArray = fieldData.params.end_time.split(" ");
                            var endTimes = endTimeArray[0].split(":");
                            if(startTimes[0] === 12) {
                                var endTimeHour = endTimeArray[1] === 'PM' ? Number(endTimes[0]) : Number(endTimes[0]) - 12;
                            } else {
                                var endTimeHour = endTimeArray[1] === 'AM' ? Number(endTimes[0]) : Number(endTimes[0]) + 12;
                            }
                        }
                        if(fieldData.values !== '') {
                            let timeValuesArray = fieldData.values.split(':');
                            fieldData.answer = new Date(new Date(new Date().setHours(timeValuesArray[0])).setMinutes(timeValuesArray[1]));
                        } else {
                            fieldData.answer = new Date();
                        }
                    }
                    if(fieldData.field_type === 'CHECKBOX') {
                        fieldData.answer = [];
                        fieldData.option.forEach((option) => {
                            option.selected = false;
                        });
                        if(fieldData.values && fieldData.values.length > 0) {
                            fieldData.values.forEach((value) => {
                                fieldData.answer.push(value.checkbox_val);
                                fieldData.option.forEach((option)=> {
                                    if(option.option_id === value.checkbox_val) {
                                        option.selected = true;
                                    }
                                })
                            });
                        }
                    }
                    if(fieldData.field_type === 'DECIMAL' || fieldData.field_type === 'NUMBER') {
                        let params = fieldData.params !== '' ? JSON.parse(fieldData.params) : null;
                        fieldData.params = params;
                        fieldData.answer = Number(fieldData.values);
                    }
                });
            });
        }
        console.log(scope.customFieldsArray);
        return scope.customFieldsArray;
    }
    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    }

    function getClassDetails(classId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionClasses
            .get(classId)
            .find('classDetails')
            .ajax({success: success, defer:true});
    }

    function getUnassignedStudent(classId) {
        var success = function(response, deferred) {
            console.log("getUnassignedStudent")
            console.log(response)
            deferred.resolve(response.data.data);
        };
        return Users.find('InstitutionStudentsNotInClass', {institution_class_id: classId}).ajax({success: success, defer: true});
    }

    function getInstitutionShifts(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionShifts.find('shiftOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function getInstitutionUnits(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            // console.log("response here");
            // console.log(response);
            deferred.resolve(response.data.data);
        };
        return InstitutionUnits.find('unitOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function getInstitutionCourses(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionCourses.find('courseOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function getTeacherOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionStaff.find('classStaffOptions', {institution_id: institutionId, academic_period_id: academicPeriodId}).ajax({success: success, defer: true});
    }

    function getConfigItemValue(code) {
        var success = function(response, deferred) {
            var results = response.data.data;
            if (angular.isObject(results) && results.length > 0) {
                var configItemValue = (results[0].value.length > 0) ? results[0].value : results[0].default_value;
                deferred.resolve(configItemValue);
            } else {
                deferred.reject('There is no ' + code + ' configured');
            }
        };

        return ConfigItemsTable
            .where({code: code})
            .ajax({success: success, defer: true});
    };

    function saveClass(data) {
        InstitutionClasses.reset();
        return InstitutionClasses.edit(data);
    }
};
