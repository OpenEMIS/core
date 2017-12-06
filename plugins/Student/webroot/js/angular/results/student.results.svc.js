angular
    .module('student.results.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('StudentResultsSvc', StudentResultsSvc);

StudentResultsSvc.$inject = ['$q', '$filter', 'KdOrmSvc', 'KdSessionSvc'];

function StudentResultsSvc($q, $filter, KdOrmSvc, KdSessionSvc) {
    var properties = {
        academicPeriods: {},
        assessments: {},
        subjects: {},
        assessmentGradingTypes: {},
        assessmentPeriods: {},
        assessmentGradingOptions: {}
    };

    var models = {
        AcademicPeriodsTable: 'AcademicPeriod.AcademicPeriods',
        AssessmentGradingTypesTable: 'Assessment.AssessmentGradingTypes',
        AssessmentPeriodsTable: 'Assessment.AssessmentPeriods',
        AssessmentItemResultsTable: 'Assessment.AssessmentItemResults'
    };

    var service = {
        init: init,
        getAssessment: getAssessment,
        getSubject: getSubject,
        getAssessmentPeriod: getAssessmentPeriod,
        getAssessmentGradingOption: getAssessmentGradingOption,
        getAssessmentGradingTypes: getAssessmentGradingTypes,
        getAcademicPeriods: getAcademicPeriods,
        getStudentResults: getStudentResults,
        getAssessmentPeriods: getAssessmentPeriods,
        getColumnDefs: getColumnDefs,
        getRowData: getRowData,
        calculateTotal: calculateTotal,
        translate: translate
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('Results');
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

    function getAssessment(id) {
        return properties.assessments[id];
    };

    function getSubject(id) {
        return properties.subjects[id];
    };

    function getAssessmentPeriod(id) {
        return properties.assessmentPeriods[id];
    };

    function getAssessmentGradingOption(id) {
        return properties.assessmentGradingOptions[id];
    };

    function getAssessmentGradingTypes() {
        var success = function(response, deferred) {
            var assessmentGradingTypes = response.data.data;

            if (angular.isObject(assessmentGradingTypes) && assessmentGradingTypes.length > 0) {
                angular.forEach(assessmentGradingTypes, function(assessmentGradingType, key) {
                    properties.assessmentGradingTypes[assessmentGradingType.id] = assessmentGradingType;
                });
                deferred.resolve(assessmentGradingTypes);
            } else {
                deferred.reject('You need to configure Assessment Grading Types first');
            }
        };

        return AssessmentGradingTypesTable
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

    function getStudentResults(academicPeriodId) {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.error)) {
                deferred.reject(response.data.error);
            } else {
                var studentResults = response.data.data;

                var results = {};
                angular.forEach(studentResults, function(studentResult, key) {
                    var academicPeriodId = studentResult.academic_period_id;
                    var assessmentId = studentResult.assessment_id;
                    var subjectId = studentResult.education_subject_id;
                    var assessmentPeriodId = studentResult.assessment_period_id;

                    if (angular.isUndefined(results[academicPeriodId])) {
                        results[academicPeriodId] = {};
                    }

                    if (angular.isUndefined(results[academicPeriodId][assessmentId])) {
                        properties.assessments[assessmentId] = studentResult._matchingData.Assessments;
                        results[academicPeriodId][assessmentId] = {};
                    }

                    if (angular.isUndefined(results[academicPeriodId][assessmentId][subjectId])) {
                        properties.subjects[subjectId] = studentResult._matchingData.EducationSubjects;
                        results[academicPeriodId][assessmentId][subjectId] = {};
                    }

                    if (angular.isUndefined(results[academicPeriodId][assessmentId][subjectId][assessmentPeriodId])) {
                        properties.assessmentPeriods[assessmentPeriodId] = studentResult._matchingData.AssessmentPeriods;
                        results[academicPeriodId][assessmentId][subjectId][assessmentPeriodId] = {};
                    }

                    properties.assessmentGradingOptions[studentResult.assessment_grading_option_id] = studentResult._matchingData.AssessmentGradingOptions;
                    results[academicPeriodId][assessmentId][subjectId][assessmentPeriodId]['assessment_grading_option_id'] = studentResult.assessment_grading_option_id;
                    results[academicPeriodId][assessmentId][subjectId][assessmentPeriodId]['marks'] = studentResult.marks;
                });

                deferred.resolve(results);
            }
        };

        return AssessmentItemResultsTable
            .select()
            .find('Results', {
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true})
            ;
    };

    function getAssessmentPeriods(assessmentId) {
        var success = function(response, deferred) {
            var assessmentPeriods = response.data.data;

            if (angular.isObject(assessmentPeriods) && assessmentPeriods.length > 0) {
                deferred.resolve(assessmentPeriods);
            } else {
                deferred.reject('You need to configure Assessment Periods first');
            }
        };

        return AssessmentPeriodsTable
            .select()
            .where({assessment_id: assessmentId})
            .ajax({success: success, defer: true});
    };

    function getColumnDefs(assessmentPeriods) {
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30
        };
        var columnDefs = [];
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }

        columnDefs.push({
            headerName: "Subject",
            field: "subject",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });

        angular.forEach(assessmentPeriods, function(assessmentPeriod, key) {
            var assessmentPeriodField = 'period_' + assessmentPeriod.id;
            var weightField = 'weight_' + assessmentPeriod.id;
            var columnDef = {
                headerName: assessmentPeriod.academic_term + " - " + assessmentPeriod.name + " <span class='divider'></span> " + assessmentPeriod.weight,
                field: assessmentPeriodField,
                filter: "number",
                menuTabs: menuTabs,
                filterParams: filterParams,
                cellStyle: function(params) {
                    var subjectId = params.data['subject_id'];

                    var resultType = '';

                    var passMark = 0;
                    if (angular.isDefined(properties.subjects[subjectId]) && angular.isDefined(properties.subjects[subjectId][assessmentPeriod.id]) && angular.isDefined(properties.subjects[subjectId][assessmentPeriod.id]['assessment_grading_type'])) {
                        gradingType = properties.subjects[subjectId][assessmentPeriod.id]['assessment_grading_type'];
                        passMark = gradingType.pass_mark;
                        resultType = gradingType.result_type;
                    }

                    if (resultType == 'DURATION') {
                        var duration = String(params.value).split(" : ");
                        var minInSeconds = parseInt(duration[0]) * 60;
                        var seconds = parseInt(duration[1]);
                        var totalSeconds = minInSeconds + seconds;

                        if (!isNaN(parseFloat(params.value)) && totalSeconds > passMark) {
                            return {color: '#CC5C5C', direction: 'ltr'};
                        } else {
                            return {color: '#333', direction: 'ltr'};
                        }
                    } else {
                        if (!isNaN(parseFloat(params.value)) && parseFloat(params.value) < passMark) {
                            return {color: '#CC5C5C'};
                        } else {
                            return {color: '#333'};
                        }
                    }
                },
                valueGetter: function(params) {
                    var subjectId = params.data['subject_id'];

                    var resultType = "MARKS";
                    if (angular.isDefined(properties.subjects[subjectId]) && angular.isDefined(properties.subjects[subjectId][assessmentPeriod.id]) && angular.isDefined(properties.subjects[subjectId][assessmentPeriod.id]['assessment_grading_type'])) {
                        gradingType = properties.subjects[subjectId][assessmentPeriod.id]['assessment_grading_type'];
                        resultType = gradingType.result_type;
                    }

                    var value = params.data[params.colDef.field];

                    if (resultType == 'MARKS') {
                        return $filter('number')(value, 2);

                    } else if (resultType == 'DURATION') {
                        if (!isNaN(parseFloat(value))) {
                            var durationAsFloat = $filter('number')(value, 2);
                            var duration = String(durationAsFloat).replace(".", " : ");
                            return duration;
                        } else {
                            return '';
                        }

                    } else {
                        // for GRADES type
                        return value;
                    }
                }
            };

            this.push(columnDef);

            columnDefs.push({
                headerName: "weight of " + assessmentPeriod.id,
                field: weightField,
                menuTabs: menuTabs,
                filter: 'text',
                hide: true
            });
        }, columnDefs);

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

        return {data: columnDefs};
    };

    function getRowData(assessmentResults) {
        var deferred = $q.defer();

        var rowData = [];
        angular.forEach(assessmentResults, function(subjectObj, subjectId) {
            var subjectName = properties.subjects[subjectId].name;
            var data = {
                subject_id: subjectId,
                subject: subjectName
            };

            angular.forEach(assessmentResults[subjectId], function(resultObj, assessmentPeriodId) {
                var assessmentGradingOption = properties.assessmentGradingOptions[resultObj.assessment_grading_option_id];
                var gradingType = properties.assessmentGradingTypes[assessmentGradingOption.assessment_grading_type_id];
                var resultType = gradingType.result_type;

                if (angular.isUndefined(properties.subjects[subjectId][assessmentPeriodId])) {
                    properties.subjects[subjectId][assessmentPeriodId] = {assessment_grading_type: gradingType};
                }

                var result = '';
                var weight = '';
                switch (resultType) {
                    case 'MARKS':
                        result = resultObj.marks;
                        weight = parseFloat(properties.assessmentPeriods[assessmentPeriodId]['weight']);
                        break;
                    case 'GRADES':
                        result = assessmentGradingOption.code_name;
                        weight = '';
                        break;
                    case 'DURATION':
                        result = resultObj.marks;
                        weight = '';
                        break;
                    default:
                        break;
                }

                this['period_'+assessmentPeriodId] = result;
                this['weight_'+assessmentPeriodId] = weight;
            }, data);

            this.push(data);
        }, rowData);

        deferred.resolve(rowData);

        return deferred.promise;
    };

    function calculateTotal(data) {
        var totalMark = '';
        for (var key in data) {
            if (/period_/.test(key)) {
                var index = key.replace(/period_(\d+)/, '$1');
                // add checking to skip adding to Total Mark if is GRADES type
                if (!isNaN(parseFloat(data[key])) && !isNaN(parseFloat(data['weight_'+index]))) {
                    totalMark = isNaN(parseFloat(totalMark)) ? 0 : totalMark;
                    totalMark += data[key] * (data['weight_'+index]);
                }
            }
        }

        if (!isNaN(parseFloat(totalMark))) {
            return $filter('number')(totalMark, 2);
        } else {
            return '';
        }
    };
}
