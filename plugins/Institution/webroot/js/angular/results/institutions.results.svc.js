angular.module('institutions.results.svc', [])
.service('InstitutionsResultsSvc', function($http, $q, $filter, KdOrmSvc) {
    var models = {
        AssessmentsTable: 'Assessment.Assessments',
        AssessmentItemsTable: 'Assessment.AssessmentItems',
        AssessmentPeriodsTable: 'Assessment.AssessmentPeriods',
        AssessmentItemResultsTable: 'Assessment.AssessmentItemResults',
        InstitutionSubjectStudentsTable: 'Institution.InstitutionSubjectStudents'
    };

    return {
        init: function(baseUrl) {
            KdOrmSvc.base(baseUrl);
            angular.forEach(models, function(model, key) {
                window[key] = KdOrmSvc.init(model);
            });
        },

        getAssessment: function(assessmentId) {
            var deferred = $q.defer();

            var success = function(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    deferred.resolve(response.data.data);
                }
            };

            var error = function(error) {
                deferred.reject(error);
            };

            AssessmentsTable.get(assessmentId).ajax({success: success, error: error});

            return deferred.promise;
        },

        getSubjects: function(assessmentId) {
            var deferred = $q.defer();

            var success = function(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var items = response.data.data;

                    if (angular.isObject(items) && items.length > 0) {
                        var educationSubject = null;

                        var subjects = [];
                        angular.forEach(items, function(item, key) {
                            educationSubject = item.education_subject;
                            educationSubject.pass_mark = item.grading_type.pass_mark;
                            educationSubject.max = item.grading_type.max;
                            educationSubject.result_type = item.grading_type.result_type;

                            this.push(educationSubject);
                        }, subjects);

                        deferred.resolve(subjects);
                    } else {
                        deferred.reject('You need to configure Assessment Items first');
                    }
                }
            };
            var error = function(error) {
                deferred.reject(error);
            };

            AssessmentItemsTable
            .select()
            .contain(['EducationSubjects', 'GradingTypes'])
            .where({assessment_id: assessmentId})
            .ajax({success: success, error: error})
            ;

            return deferred.promise;
        },

        getPeriods: function(assessmentId) {
            var deferred = $q.defer();

            var success = function(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var periods = response.data.data;

                    if (angular.isObject(periods) && periods.length > 0) {
                        deferred.resolve(periods);
                    } else {
                        deferred.reject('You need to configure Assessment Periods first');
                    }
                }
            };

            var error = function(error) {
                deferred.reject(error);
            };

            AssessmentPeriodsTable
            .select()
            .where({assessment_id: assessmentId})
            .ajax({success: success, error: error});

            return deferred.promise;
        },

        getColumnDefs: function(action, subject, periods) {
            var filterParams = {
                cellHeight: 30
            };
            var columnDefs = [];

            columnDefs.push({
                headerName: "OpenEMIS ID",
                field: "openemis_id",
                filterParams: filterParams
            });
            columnDefs.push({
                headerName: "Name",
                field: "name",
                sort: 'asc',
                filterParams: filterParams
            });
            columnDefs.push({
                headerName: "student id",
                field: "student_id",
                hide: true,
                filterParams: filterParams
            });

            angular.forEach(periods, function(period, key) {
                var headerName = period.name + " <span class='divider'></span> " + period.weight;
                var periodField = 'period_' + period.id;
                var weightField = 'weight_' + period.id;

                var columnDef = {
                    headerName: headerName,
                    field: periodField,
                    filter: 'number'
                };

                if (action == 'edit' && period.editable) {
                    columnDef.headerName += " <i class='fa fa-pencil-square-o fa-lg header-icon'></i>";
                    columnDef.cellClass = 'ag-cell-highlight';
                    columnDef.cellRenderer = function(params) {
                        var inputElement = document.createElement("input");

                        inputElement.setAttribute('class', 'ag-cell-edit-input oe-cell-editable');
                        inputElement.setAttribute('type', 'number');
                        inputElement.setAttribute('ng-pattern', '/^[0-9]+(\.[0-9]{1,2})?$/');
                        inputElement.setAttribute('step', '0.01');
                        inputElement.setAttribute('ng-model', 'data.' + params.colDef.field);
                        inputElement.setAttribute('oe-student', parseInt(params.data.student_id));
                        inputElement.setAttribute('oe-period', period.id);
                        inputElement.setAttribute('oe-original', parseFloat(params.value));

                        return inputElement;
                    };
                } else {
                    columnDef.cellStyle = function(params) {
                        if (parseFloat(params.value) < parseFloat(subject.pass_mark)) {
                            return {color: '#CC5C5C'};
                        } else {
                            return {color: '#333'};
                        }
                    };
                    columnDef.valueGetter = function(params) {
                        var value = params.data[params.colDef.field];

                        if (!isNaN(parseFloat(value))) {
                            return $filter('number')(value, 2);
                        } else {
                            return '';
                        }
                    };
                }

                this.push(columnDef);

                columnDefs.push({
                    headerName: "weight of " + period.id,
                    field: weightField,
                    hide: true
                });
            }, columnDefs);

            var columnDef = {
                headerName: "Total Mark",
                field: "total_mark",
                filter: "number",
                valueGetter: function(params) {
                    var totalMark = 0;

                    for (var key in params.data) {
                        if (/period_/.test(key) && angular.isNumber(params.data[key])) {
                            var index = key.replace(/period_(\d+)/, '$1');
                            totalMark += params.data[key] * (params.data['weight_'+index] / params.data.total_weight);
                        }
                    }

                    if (totalMark > 0) {
                        return $filter('number')(totalMark, 2);
                    } else {
                        return '';
                    }
                },
                filterParams: filterParams
            };

            if (action == 'edit') {
                columnDef.hide = true;
            }

            columnDefs.push(columnDef);

            columnDefs.push({
                headerName: "total weight",
                field: "total_weight",
                hide: true
            });

            return columnDefs;
        },

        getRowData: function(periods, institutionId, classId, assessmentId, academicPeriodId, educationSubjectId) {
            var deferred = $q.defer();

            var success = function(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var subjectStudents = response.data.data;

                    var totalWeight = 0;
                    var periodObj = {};
                    angular.forEach(periods, function(period, key) {
                        totalWeight += parseFloat(period.weight);
                        periodObj[period.id] = period;
                    }, periodObj);

                    if (angular.isObject(subjectStudents) && subjectStudents.length > 0) {
                        var studentId = null;
                        var currentStudentId = null;
                        var studentResults = {};
                        var rowData = [];

                        angular.forEach(subjectStudents, function(subjectStudent, key) {
                            currentStudentId = parseInt(subjectStudent.student_id);

                            if (studentId != currentStudentId) {
                                if (studentId != null) {
                                    this.push(studentResults);   
                                }
                                
                                studentResults = {
                                    openemis_id: subjectStudent.openemis_no,
                                    name: subjectStudent.name,
                                    student_id: currentStudentId,
                                    total_mark: null,
                                    total_weight: totalWeight
                                };

                                angular.forEach(periods, function(period, key) {
                                    studentResults['period_' + parseInt(period.id)] = '';
                                    studentResults['weight_' + parseInt(period.id)] = parseFloat(periodObj[parseInt(period.id)]['weight']);
                                });

                                studentId = currentStudentId;
                            }

                            var marks = parseFloat(subjectStudent.marks);
                            if (!isNaN(marks)) {
                                studentResults['period_' + parseInt(subjectStudent.assessment_period_id)] = marks;
                            }
                        }, rowData);

                        if (studentResults.hasOwnProperty('student_id')) {
                            rowData.push(studentResults);
                        }

                        deferred.resolve(rowData);
                    } else {
                        deferred.reject('No Students');
                    }
                }
            };

            var error = function(error) {
                deferred.reject(error);
            };

            InstitutionSubjectStudentsTable
            .select()
            .contain(['Users'])
            .find('Results', {
                institution_id: institutionId,
                class_id: classId,
                assessment_id: assessmentId,
                academic_period_id: academicPeriodId,
                subject_id: educationSubjectId
            })
            .where({institution_class_id: classId})
            .ajax({success: success, error: error})
            ;

            return deferred.promise;
        },

        saveRowData: function(assessmentId, educationSubjectId, institutionId, academicPeriodId) {
            var promises = [];

            angular.forEach(angular.element('.oe-cell-editable'), function(obj, key) {
                var oldValue = obj.attributes['oe-original'].value;
                var newValue = (obj.value.length > 0) ? parseFloat(obj.value) : null;

                if (!isNaN(newValue) && (isNaN(oldValue) && newValue != null) || (!isNaN(oldValue) && oldValue != newValue)) {
                    var data = {
                        "marks" : newValue,
                        "assessment_id" : assessmentId,
                        "education_subject_id" : educationSubjectId,
                        "institution_id" : institutionId,
                        "academic_period_id" : academicPeriodId,
                        "student_id" : parseInt(obj.attributes['oe-student'].value),
                        "assessment_period_id" : parseInt(obj.attributes['oe-period'].value)
                    };

                    promises.push(AssessmentItemResultsTable.save(data));
                }
            });

            return $q.all(promises);
        },

        saveTotal: function(data, classId, institutionId, academicPeriodId, educationSubjectId) {
            var totalMark = 0;

            for (var key in data) {
                if (/period_/.test(key) && angular.isNumber(data[key])) {
                    var index = key.replace(/period_(\d+)/, '$1');
                    totalMark += data[key] * (data['weight_'+index] / data.total_weight);
                }
            }

            totalMark = totalMark ? $filter('number')(totalMark, 2) : null;

            var data = {
                "total_mark" : totalMark,
                "student_id" : data.student_id,
                "institution_class_id" : classId,
                "institution_id" : institutionId,
                "academic_period_id" : academicPeriodId,
                "education_subject_id" : educationSubjectId
            };

            InstitutionSubjectStudentsTable.save(data);
        }
    }
});
