angular.module('institutions.results.svc', ['kd.orm.svc', 'kd.session.svc'])
.service('InstitutionsResultsSvc', function($http, $q, $filter, KdOrmSvc, KdSessionSvc) {
    const resultTypes = {MARKS: 'MARKS', GRADES: 'GRADES'};

    var models = {
        AssessmentsTable: 'Assessment.Assessments',
        AssessmentItemsTable: 'Assessment.AssessmentItems',
        AssessmentPeriodsTable: 'Assessment.AssessmentPeriods',
        AssessmentItemResultsTable: 'Assessment.AssessmentItemResults',
        InstitutionSubjectStudentsTable: 'Institution.InstitutionSubjectStudents',
        SecurityGroupUsersTable: 'Security.SecurityGroupUsers'
    };

    return {
        init: function(baseUrl) {
            KdOrmSvc.base(baseUrl);
            KdSessionSvc.base(baseUrl);
            angular.forEach(models, function(model, key) {
                window[key] = KdOrmSvc.init(model);
            });
        },

        getAssessment: function(assessmentId) {
            return AssessmentsTable.get(assessmentId).ajax({defer: true});
        },

        getSubjects: function(assessmentId, classId) 
        {
            // To add session and access control check
            var session = '';
            var allSubjects = 1;
            var mySubjects = 1;
            var superAdmin = 1;

            // Get list of institution roles
            var institutionId = 0;

            KdSessionSvc.read('Auth.User.super_admin').then(function(value) {
                return value;
            }).then (function(isSuperAdmin){
                if (!isSuperAdmin)
                {
                    KdSessionSvc.read('Auth.User.id').then(function(userId) {
                        KdSessionSvc.read('Institution.Institutions.id').then(function(institutionId) {
                            var roles = SecurityGroupUsersTable
                                .select()
                                // .find('RoleByInstitution', {security_user_id: userId, institution_id: institutionId})
                                .ajax({defer: true});
                            console.log(roles);
                        });

                        console.log(institutionId);
                        console.log(userId);
                        // if (!allSubjects) 
                        // {
                        //     if (!mySubjects) 
                        //     {
                        //         // If there is no mysubject permission
                        //         return AssessmentItemsTable.ajax({success: fail, defer: true});
                        //     } else 
                        //     {
                        //         assessmentSubjects = assessmentSubjects
                        //             .find('staffSubjects', {class_id: classId});
                        //     }
                        // }
                    });
                    
                }
            });

            var fail = function(response, deferred) {
                deferred.reject('You do not have access to subjects');
            };

            var assessmentSubjects = AssessmentItemsTable
                .select()
                .contain(['EducationSubjects', 'GradingTypes.GradingOptions'])
                .where({assessment_id: assessmentId});
            
            if (!superAdmin)
            {
                if (!allSubjects) 
                {
                    if (!mySubjects) 
                    {
                        // If there is no mysubject permission
                        return AssessmentItemsTable.ajax({success: fail, defer: true});
                    } else 
                    {
                        assessmentSubjects = assessmentSubjects
                            .find('staffSubjects', {class_id: classId});
                    }
                }
            }

            var success = function(response, deferred) {
                var items = response.data.data;

                if (angular.isObject(items) && items.length > 0) 
                {
                    var educationSubject = null;

                    var subjects = [];
                    angular.forEach(items, function(item, key) 
                    {
                        educationSubject = item.education_subject;
                        educationSubject.grading_type = item.grading_type;

                        this.push(educationSubject);
                    }, subjects);

                    deferred.resolve(subjects);
                } else 
                {
                    deferred.reject('You need to configure Assessment Items first');
                }
            };
            
            return assessmentSubjects.ajax({success: success, defer: true});
        },

        getPeriods: function(assessmentId) 
        {
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
            var extra = {};
            var resultType = subject.grading_type.result_type;
            var isMarksType = (resultType == resultTypes.MARKS);
            var isGradesType = (resultType == resultTypes.GRADES);

            if (subject.grading_type.grading_options.length == 0) {
                // return error if No Grading Options
                return {error: 'You need to configure Grading Options first'};
            }

            if (isMarksType) {
                extra = {
                    minMark: 0,
                    passMark: subject.grading_type.pass_mark,
                    maxMark: subject.grading_type.max
                };
            } else if (isGradesType) {
                var gradingOptions = {
                    0 : {
                        id: 0,
                        code: '',
                        name: '-- Select --'    
                    }
                };

                angular.forEach(subject.grading_type.grading_options, function(obj, key) {
                    gradingOptions[obj.id] = obj;
                });

                extra = {
                    gradingOptions: gradingOptions
                };
            }

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

            var ResultsSvc = this;
            angular.forEach(periods, function(period, key) {
                var allowEdit = (action == 'edit' && period.editable);
                var headerLabel = period.name + " <span class='divider'></span> " + period.weight + "%";
                var headerName = allowEdit ? headerLabel + " <i class='fa fa-pencil-square-o fa-lg header-icon'></i>" : headerLabel;

                var periodField = 'period_' + period.id;
                var weightField = 'weight_' + period.id;

                var columnDef = {
                    headerName: headerName,
                    field: periodField,
                    filterParams: filterParams
                };

                if (isMarksType) {
                    columnDef = ResultsSvc.renderMarks(allowEdit, columnDef, extra);
                } else if (isGradesType) {
                    extra['period'] = period;
                    columnDef = ResultsSvc.renderGrades(allowEdit, columnDef, extra);
                }

                this.push(columnDef);

                columnDefs.push({
                    headerName: "weight of " + period.id,
                    field: weightField,
                    hide: true
                });
            }, columnDefs);

            if (isMarksType) {
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
            }

            return {data: columnDefs};
        },

        renderMarks: function(allowEdit, cols, extra) {
            var minMark = extra.minMark;
            var passMark = extra.passMark;
            var maxMark = extra.maxMark;

            cols = angular.merge(cols, {
                filter: 'number',
                cellStyle: function(params) {
                    if (!isNaN(parseFloat(params.value)) && parseFloat(params.value) < passMark) {
                        return {color: '#CC5C5C'};
                    } else {
                        return {color: '#333'};
                    }
                },
                valueGetter: function(params) {
                    var value = params.data[params.colDef.field];

                    if (!isNaN(parseFloat(value))) {
                        return $filter('number')(value, 2);
                    } else {
                        return '';
                    }
                }
            });

            if (allowEdit) {
                cols = angular.merge(cols, {
                    editable: true,
                    cellClass: 'oe-cell-highlight',
                    newValueHandler: function(params) {
                        var valueAsFloat = parseFloat(params.newValue);

                        if (params.newValue.length > 0 && (isNaN(valueAsFloat) || (valueAsFloat < minMark || valueAsFloat > maxMark))) {
                            params.data[params.colDef.field] = '';
                        } else {
                            params.data[params.colDef.field] = params.newValue;
                        }
                    }
                });
            }

            return cols;
        },

        renderGrades: function(allowEdit, cols, extra) {
            var gradingOptions = extra.gradingOptions;
            var period = extra.period;

            if (allowEdit) {
                cols = angular.merge(cols, {
                    cellClass: 'oe-cell-highlight',
                    cellRenderer: function(params) {
                        if (params.value.length == 0) {
                            params.value = 0;
                        }

                        var eCell = document.createElement('div');
                        eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");
                        eCell.setAttribute("oe-student", params.data.student_id);
                        eCell.setAttribute("oe-period", period.id);
                        eCell.setAttribute("oe-oldValue", params.value);

                        var eSelect = document.createElement("select");

                        angular.forEach(gradingOptions, function(obj, key) {
                            var eOption = document.createElement("option");
                            var labelText = obj.name;
                            if (obj.code.length > 0) {
                                labelText = obj.code + ' - ' + labelText;
                            }
                            eOption.setAttribute("value", key);
                            eOption.innerHTML = labelText;
                            eSelect.appendChild(eOption);
                        });

                        eSelect.value = params.value;

                        eSelect.addEventListener('change', function () {
                            var newValue = eSelect.value;
                            params.data[params.colDef.field] = newValue;
                            eCell.setAttribute("oe-newValue", eSelect.value);
                        });

                        eCell.appendChild(eSelect);

                        return eCell;
                    },
                    suppressMenu: true
                });
            } else {
                cols = angular.merge(cols, {
                    cellRenderer: function(params) {
                        var cellValue = '';
                        if (params.value.length != 0 && params.value != 0) {
                            cellValue = gradingOptions[params.value]['name'];
                            if (gradingOptions[params.value]['code'].length > 0) {
                                cellValue = gradingOptions[params.value]['code'] + ' - ' + cellValue;
                            }
                        }
                        // var cellValue = (params.value.length != 0 && params.value != 0) ? gradingOptions[params.value]['name'] : '';

                        var eCell = document.createElement('div');
                        var eLabel = document.createTextNode(cellValue);
                        eCell.appendChild(eLabel);

                        return eCell;
                    },
                    suppressMenu: true
                });
            }

            return cols;
        },

        getRowData: function(resultType, periods, institutionId, classId, assessmentId, academicPeriodId, educationSubjectId) {
            var success = function(response, deferred) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var subjectStudents = response.data.data;

                    var isMarksType = (resultType == resultTypes.MARKS);
                    var isGradesType = (resultType == resultTypes.GRADES);

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
                                    student_id: currentStudentId
                                };

                                if (isMarksType) {
                                    studentResults = angular.merge(studentResults, {
                                        total_mark: subjectStudent.total_mark,
                                        total_weight: totalWeight,
                                        is_dirty: false
                                    });
                                }

                                angular.forEach(periods, function(period, key) {
                                    studentResults['period_' + parseInt(period.id)] = '';
                                    studentResults['weight_' + parseInt(period.id)] = parseFloat(periodObj[parseInt(period.id)]['weight']);
                                });

                                studentId = currentStudentId;
                            }

                            if (isMarksType) {
                                var marks = parseFloat(subjectStudent.marks);
                                if (!isNaN(marks)) {
                                    studentResults['period_' + parseInt(subjectStudent.assessment_period_id)] = marks;
                                }
                            } else if (isGradesType) {
                                if (subjectStudent.grading_option_id != null) {
                                    studentResults['period_' + parseInt(subjectStudent.assessment_period_id)] = subjectStudent.grading_option_id;
                                }
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

        getResultTypes: function() {
            return resultTypes;
        },

        getGrading: function(subject, marks) {
            var gradingOptions = subject.grading_type.grading_options;
            var gradingResults = {
                id: null,
                code: '',
                name: ''
            };

            angular.forEach(gradingOptions, function(gradingOption, key) {
                if (marks >= gradingOption.min && marks <= gradingOption.max) {
                    this.id = gradingOption.id;
                    this.code = gradingOption.code;
                    this.name = gradingOption.name;
                }
            }, gradingResults);

            return gradingResults;
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

        saveRowData: function(subject, results, assessmentId, educationSubjectId, institutionId, academicPeriodId) {
            var promises = [];
            var resultType = subject.grading_type.result_type;

            angular.forEach(results, function(result, studentId) {
                angular.forEach(result, function(obj, assessmentPeriodId) {
                    var marks = null;
                    var gradingOptionId = null;

                    if (resultType == resultTypes.MARKS) {
                        if (!isNaN(parseFloat(obj.marks))) {
                            marks = $filter('number')(obj.marks, 2);
                            var gradingObj = this.getGrading(subject, marks);
                            gradingOptionId = gradingObj.id;
                        }
                    } else if (resultType == resultTypes.GRADES) {
                        if (obj.gradingOptionId != 0) {
                            gradingOptionId = obj.gradingOptionId;
                        }
                    }

                    var data = {
                        "marks" : marks,
                        "assessment_grading_option_id" : gradingOptionId,
                        "assessment_id" : assessmentId,
                        "education_subject_id" : educationSubjectId,
                        "institution_id" : institutionId,
                        "academic_period_id" : academicPeriodId,
                        "student_id" : parseInt(studentId),
                        "assessment_period_id" : parseInt(assessmentPeriodId)
                    };

                    promises.push(AssessmentItemResultsTable.save(data));
                }, this);
            }, this);

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
