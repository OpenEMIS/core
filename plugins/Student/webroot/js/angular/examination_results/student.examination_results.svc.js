angular
    .module('student.examination_results.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('StudentExaminationResultsSvc', StudentExaminationResultsSvc);

StudentExaminationResultsSvc.$inject = ['$q', '$filter', 'KdOrmSvc', 'KdSessionSvc'];

function StudentExaminationResultsSvc($q, $filter, KdOrmSvc, KdSessionSvc) {
    var properties = {
        academicPeriods: {},
        examinations: {},
        ExaminationSubjects: {},
        subjects: {},
        examinationGradingTypes: {},
        examinationGradingOptions: {}
    };

    var models = {
        AcademicPeriodsTable: 'AcademicPeriod.AcademicPeriods',
        ExaminationGradingTypesTable: 'Examination.ExaminationGradingTypes',
        ExaminationStudentSubjectResultsTable: 'Examination.ExaminationStudentSubjectResults'
    };

    var service = {
        init: init,
        getExamination: getExamination,
        getExaminationItem: getExaminationItem,
        getSubject: getSubject,
        getExaminationGradingOption: getExaminationGradingOption,
        getExaminationGradingTypes: getExaminationGradingTypes,
        getAcademicPeriods: getAcademicPeriods,
        getStudentExaminationResults: getStudentExaminationResults,
        getColumnDefs: getColumnDefs,
        getRowData: getRowData,
        calculateTotal: calculateTotal,
        translate: translate
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('StudentExaminationResults');
        KdSessionSvc.base(baseUrl);
        KdOrmSvc.init(models);
    };

    function translate(data) {
        KdOrmSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    };

    function getExamination(id) {
        return properties.examinations[id];
    };

    function getExaminationItem(id) {
        return properties.ExaminationSubjects[id];
    };

    function getSubject(id) {
        return properties.subjects[id];
    };

    function getExaminationGradingOption(id) {
        return properties.examinationGradingOptions[id];
    };

    function getExaminationGradingTypes() {
        var success = function(response, deferred) {
            var examinationGradingTypes = response.data.data;

            if (angular.isObject(examinationGradingTypes) && examinationGradingTypes.length > 0) {
                angular.forEach(examinationGradingTypes, function(examinationGradingType, key) {
                    properties.examinationGradingTypes[examinationGradingType.id] = examinationGradingType;
                });
                deferred.resolve(examinationGradingTypes);
            } else {
                deferred.reject('You need to configure Examination Grading Types first');
            }
        };

        return ExaminationGradingTypesTable
            .ajax({success: success, defer: true});
    };

    function getAcademicPeriods() {
        var success = function(response, deferred) {
            var academicPeriods = response.data.data;

            if (angular.isObject(academicPeriods) && academicPeriods.length > 0) {
                angular.forEach(academicPeriods, function(academicPeriod, key) {
                    properties.academicPeriods[academicPeriod.id] = academicPeriod;
                });
                deferred.resolve(academicPeriods);
            } else {
                deferred.reject('No Academic Periods');
            }
        };

        return AcademicPeriodsTable
            .find('Years')
            .ajax({success: success, defer: true});
    };

    function getStudentExaminationResults(academicPeriodId) {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.error)) {
                deferred.reject(response.data.error);
            } else {
                var studentExaminationResults = response.data.data;

                var results = {};
                angular.forEach(studentExaminationResults, function(studentExaminationResult, key) {
                    var academicPeriodId = studentExaminationResult.academic_period_id;
                    var examinationId = studentExaminationResult.examination_id;
                    var examinationItemId = studentExaminationResult.examination_subject_id;
                    var subjectId = studentExaminationResult.education_subject_id;
                    var gradingOptionId = studentExaminationResult.examination_grading_option_id;
                    var marks = studentExaminationResult.marks;
                     if(gradingOptionId!=null){ //POCOR-6761
                        var gradingOption = studentExaminationResult.examination_grading_option;
                        //var gradingOption = studentExaminationResult._matchingData.ExaminationGradingOptions;
                        var gradingType = properties.examinationGradingTypes[gradingOption.examination_grading_type_id];
                        
                    }
                    if (angular.isUndefined(results[academicPeriodId])) {
                        results[academicPeriodId] = {};
                    }

                    if (angular.isUndefined(results[academicPeriodId][examinationId])) {
                        properties.examinations[examinationId] = studentExaminationResult._matchingData.Examinations;
                        results[academicPeriodId][examinationId] = {};
                    }

                    if (angular.isUndefined(results[academicPeriodId][examinationId][examinationItemId])) {
                        properties.ExaminationSubjects[examinationItemId] = studentExaminationResult._matchingData.ExaminationSubjects;
                        if(gradingOptionId!=null){ //POCOR-6761
                            properties.ExaminationSubjects[examinationItemId]['examination_grading_type'] = gradingType;
                        }
                        results[academicPeriodId][examinationId][examinationItemId] = {};
                    }

                    if (angular.isUndefined(properties.subjects[subjectId])) {
                        properties.subjects[subjectId] = studentExaminationResult._matchingData.EducationSubjects;
                    }
                    if(gradingOptionId!=null){ //POCOR-6761
                        if (angular.isUndefined(properties.examinationGradingOptions[gradingOptionId])) {
                            properties.examinationGradingOptions[gradingOptionId] = gradingOption;
                        }
                    }

                    results[academicPeriodId][examinationId][examinationItemId]['education_subject_id'] = subjectId;
                    if(gradingOptionId!=null){ //POCOR-6761
                       
                        results[academicPeriodId][examinationId][examinationItemId]['examination_grading_option_id'] = gradingOptionId;
                    }
                    results[academicPeriodId][examinationId][examinationItemId]['marks'] = studentExaminationResult.marks;
                });

                deferred.resolve(results);
            }
        };

        return ExaminationStudentSubjectResultsTable
            .select()
            .find('Results', {
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    };

    function getColumnDefs() {
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30
        };
        var columnDefs = [];

        columnDefs.push({
            headerName: "Examination Item",
            field: "examination_item",
            filterParams: filterParams,
            filter: "text",
            menuTabs: menuTabs,
        });

        columnDefs.push({
            headerName: "Subject",
            field: "subject",
            filter: "text",
            menuTabs: menuTabs,
            filterParams: filterParams
        });

        columnDefs.push({
            headerName: "Mark",
            field: "mark",
            filter: "number",
            menuTabs: menuTabs,
            filterParams: filterParams,
            cellStyle: function(params) {
                var examinationItemId = params.data['examination_subject_id'];
               // var examinationgrade = params.data['examination_grading_option_id']; //POCOR-6761
                var examinationmarks = params.data['marks']; //POCOR-6761

                var passMark = 0;
                if (angular.isDefined(properties.ExaminationSubjects[examinationItemId]) && angular.isDefined(properties.ExaminationSubjects[examinationItemId]['examination_grading_type'])) {
                    gradingType = properties.ExaminationSubjects[examinationItemId]['examination_grading_type'];
                    passMark = gradingType.pass_mark;
                }

                if (!isNaN(parseFloat(params.value)) && parseFloat(params.value) < passMark) {
                    return {color: '#CC5C5C'};
                } else {
                    return {color: '#333'};
                }
            },
            valueGetter: function(params) {
                var examinationItemId = params.data['examination_subject_id'];
                var examinationmarkk = params.data['marks']; //POCOR-6761

                var resultType = "MARKS";
                //POCOR-6761 start
                   function checkExaminationGradingTypesIdIsNull(){
                        var counter=0;
                        for(var key in properties.examinationGradingOptions)
                        {
                            if(properties.examinationGradingOptions[key].examination_grading_type_id==null)
                            {
                                 counter=counter+1;
                            }
                        } 
                         return counter==0?true:false;
                    }
                    //POCOR-6761 end    
                if (angular.isDefined(properties.ExaminationSubjects[examinationItemId]) && angular.isDefined(properties.ExaminationSubjects[examinationItemId]['examination_grading_type']) && checkExaminationGradingTypesIdIsNull()) {
                    gradingType = properties.ExaminationSubjects[examinationItemId]['examination_grading_type'];
                    resultType = gradingType.result_type;
                    
                }else { //POCOR-6761
                    var resultType="MARK";
                    
                }

                var value = params.data[params.colDef.field];

                if (resultType == 'MARKS') {
                    return $filter('number')(value, 2);
                }else if(resultType=='MARK'){ //POCOR-6761
                    return $filter('number')(value, 2);
                } else {
                    // for GRADES type
                    return value;
                }
            }
        });

        columnDefs.push({
            headerName: "Weight",
            field: 'weight',
            filterParams: filterParams,
            filter: "number",
            menuTabs: menuTabs,
        });

        columnDefs.push({
            headerName: "Total Mark",
            field: "total_mark",
            filter: "number",
            menuTabs: menuTabs,
            filterParams: filterParams,
            valueGetter: function(params) {
                params.data[params.colDef.field] = service.calculateTotal(params.data);

                var value = params.data[params.colDef.field];

                if (!isNaN(parseFloat(value))) {
                    return $filter('number')(value, 2);
                } else {
                    // empty for GRADES type
                    return value;
                }
            }
        });

        var bodyDir = getComputedStyle(document.body).direction;
        if (bodyDir == 'rtl') {
            columnDefs.reverse();
        }

        return {data: columnDefs};
    };

    function getRowData(examinationResults) {
        var deferred = $q.defer();

        var rowData = [];
        angular.forEach(examinationResults, function(resultObj, examinationItemId) {
            var examinationItemDisplayName = properties.ExaminationSubjects[examinationItemId].code_name;
            var itemWeight = properties.ExaminationSubjects[examinationItemId].weight;
            var subjectId = resultObj.education_subject_id;
            var subjectDisplayName = properties.subjects[subjectId].code_name;
            var examinationgradingoptionid = resultObj.examination_grading_option_id; //POCOR-6761
            var data = {
                examination_subject_id: examinationItemId,
                examination_item: examinationItemDisplayName,
                subject_id: subjectId,
                subject: subjectDisplayName,
                weight: itemWeight,
                gradingTypes : examinationgradingoptionid,
            };
            if(examinationgradingoptionid!=null){ //POCOR-6761
                var examinationGradingOption = properties.examinationGradingOptions[resultObj.examination_grading_option_id];
                var gradingType = properties.examinationGradingTypes[examinationGradingOption.examination_grading_type_id];
                var resultType = gradingType.result_type;
            }else { //POCOR-6761
                var resultType ="MARK";
            }

            var result = '';
            switch (resultType) {
                case 'MARKS':
                    result = resultObj.marks;
                    break;
                //POCOR-6761 start
                case 'MARK':
                    result = resultObj.marks;
                    break;
                //POCOR-6761 start
                case 'GRADES':
                    result = examinationGradingOption.code_name;
                    break;
                default:
                    break;
            }

            data['mark'] = result;
            this.push(data);
        }, rowData);

        deferred.resolve(rowData);

        return deferred.promise;
    };

    function calculateTotal(data) {
        var totalMark = '';
        if (!isNaN(parseFloat(data['mark'])) && !isNaN(parseFloat(data['weight']))) {
            totalMark = isNaN(parseFloat(totalMark)) ? 0 : totalMark;
            totalMark += data['mark'] * (data['weight']);
        }

        if (!isNaN(parseFloat(totalMark))) {
            return $filter('number')(totalMark, 2);
        } else {
            return '<i class="fa fa-minus"></i>';
        }
    };
}
