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
            return AssessmentsTable.get(assessmentId).ajax({defer: true});
        },

        getSubjects: function(assessmentId) {
            var success = function(response, deferred) {
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
            };

            return AssessmentItemsTable
            .select()
            .contain(['EducationSubjects', 'GradingTypes'])
            .where({assessment_id: assessmentId})
            .ajax({success: success, defer: true})
            ;
        },

        getPeriods: function(assessmentId) {
            var success = function(response, deferred) {
                var periods = response.data.data;

                if (angular.isObject(periods) && periods.length > 0) {
                    deferred.resolve(periods);
                } else {
                    deferred.reject('You need to configure Assessment Periods first');
                }   
            };

            return AssessmentPeriodsTable
            .select()
            .where({assessment_id: assessmentId})
            .ajax({success: success, defer: true});
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
                    filter: 'number',
                    valueGetter: function(params) {
                        var value = params.data[params.colDef.field];

                        if (!isNaN(parseFloat(value))) {
                            return $filter('number')(value, 2);
                        } else {
                            return '';
                        }
                    },
                    filterParams: filterParams
                };

                if (action == 'edit' && period.editable) {
                    columnDef.headerName = headerName + " <i class='fa fa-pencil-square-o fa-lg header-icon'></i>";
                    columnDef.editable = true;
                    columnDef.cellClass = 'oe-cell-highlight';
                }

                this.push(columnDef);

                columnDefs.push({
                    headerName: "weight of " + period.id,
                    field: weightField,
                    hide: true
                });
            }, columnDefs);

            columnDefs.push({
                headerName: "Total Mark",
                field: "total_mark",
                filter: "number",
                valueGetter: function(params) {
                    var value = params.data[params.colDef.field];

                    if (!isNaN(parseFloat(value))) {
                        return $filter('number')(value, 2);
                    } else {
                        return '';
                    }
                },
                filterParams: filterParams
            });

            columnDefs.push({
                headerName: "total weight",
                field: "total_weight",
                hide: true
            });

            columnDefs.push({
                headerName: "is modified",
                field: "is_dirty",
                hide: true
            });

            return columnDefs;
        },

        getRowData: function(periods, institutionId, classId, assessmentId, academicPeriodId, educationSubjectId) {
            var success = function(response, deferred) {
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
                                    total_mark: subjectStudent.total_mark,
                                    total_weight: totalWeight,
                                    is_dirty: false
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

            return InstitutionSubjectStudentsTable
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
            .ajax({success: success, defer: true})
            ;
        },

        calculateTotal: function(data) {
            var totalMark = 0;

            for (var key in data) {
                if (/period_/.test(key)) {
                    var index = key.replace(/period_(\d+)/, '$1');
                    totalMark += data[key] * (data['weight_'+index] / data.total_weight);
                }
            }

            if (totalMark > 0) {
                return $filter('number')(totalMark, 2);
            } else {
                return '';
            }
        },

        saveRowData: function(results, assessmentId, educationSubjectId, institutionId, academicPeriodId) {
            var promises = [];

            angular.forEach(results, function(result, studentId) {
                angular.forEach(result, function(obj, assessmentPeriodId) {
                    var marks = !isNaN(parseFloat(obj.marks)) ? $filter('number')(obj.marks, 2) : null;

                    var data = {
                        "marks" : marks,
                        "assessment_id" : assessmentId,
                        "education_subject_id" : educationSubjectId,
                        "institution_id" : institutionId,
                        "academic_period_id" : academicPeriodId,
                        "student_id" : parseInt(studentId),
                        "assessment_period_id" : parseInt(assessmentPeriodId)
                    };

                    promises.push(AssessmentItemResultsTable.save(data));
                });
            });

            return $q.all(promises);
        },

        saveTotal: function(row, studentId, classId, institutionId, academicPeriodId, educationSubjectId) {
            var totalMark = this.calculateTotal(row);
            totalMark = !isNaN(parseFloat(totalMark)) ? $filter('number')(totalMark, 2) : null;

            var data = {
                "total_mark" : totalMark,
                "student_id" : studentId,
                "institution_class_id" : classId,
                "institution_id" : institutionId,
                "academic_period_id" : academicPeriodId,
                "education_subject_id" : educationSubjectId
            };

            InstitutionSubjectStudentsTable.save(data);
        }
    }
});
