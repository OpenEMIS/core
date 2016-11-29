angular.module('institutions.results.svc', ['kd.orm.svc', 'kd.session.svc', 'kd.access.svc'])
.service('InstitutionsResultsSvc', function($http, $q, $filter, KdOrmSvc, KdSessionSvc, KdAccessSvc) {
    const resultTypes = {MARKS: 'MARKS', GRADES: 'GRADES', DURATION: 'DURATION'};

    var models = {
        AssessmentsTable: 'Assessment.Assessments',
        AssessmentItemsTable: 'Assessment.AssessmentItems',
        AssessmentItemsGradingTypesTable: 'Assessment.AssessmentItemsGradingTypes',
        AssessmentPeriodsTable: 'Assessment.AssessmentPeriods',
        AssessmentItemResultsTable: 'Assessment.AssessmentItemResults',
        InstitutionSubjectStudentsTable: 'Institution.InstitutionSubjectStudents',
        SecurityGroupUsersTable: 'Security.SecurityGroupUsers'
    };

    return {
        init: function(baseUrl) {
            KdOrmSvc.base(baseUrl);
            KdOrmSvc.controllerAction('Results');
            KdSessionSvc.base(baseUrl);
            angular.forEach(models, function(model, key) {
                window[key] = KdOrmSvc.init(model);
            });
        },

        getAssessment: function(assessmentId) {
            return AssessmentsTable.get(assessmentId).ajax({defer: true});
        },

        getPermissions: function() {
            var promises = [];

            promises.push(KdSessionSvc.read('Auth.User.super_admin'));
            promises.push(KdSessionSvc.read('Auth.User.id'));
            promises.push(KdSessionSvc.read('Institution.Institutions.id'));

            return $q.all(promises);
        },

        getSubjects: function(assessmentId, classId)
        {
            var deferred = $q.defer();
            var isSuperAdmin = 0;
            var securityUserId = 0;
            var roles = [];
            var allSubjectRoles = [];
            var subjectRoles = [];
            var subjects = [];

            this.getPermissions()
            .then(function(response) {
                isSuperAdmin = response[0];
                securityUserId = response[1];
                var institutionId = response[2];
                // allSubjectRoles = response[3];
                // subjectRoles = response[4];

                return SecurityGroupUsersTable
                    .select(['security_role_id'])
                    .find('RoleByInstitution', {security_user_id: securityUserId, institution_id: institutionId})
                    .ajax({defer: true});

            }, function(error) {
                console.log('error:');
                console.log(error);
                deferred.reject(error);
            })
            .then(function(response) {
                var securityRoles = response.data;
                for (i = 0; i < securityRoles.length; i++) {
                    roles[i] = securityRoles[i].security_role_id;
                }
                var promises = [];

                promises.push(KdAccessSvc.checkPermission('Institutions.AllSubjects.view', roles));
                promises.push(KdAccessSvc.checkPermission('Institutions.Subjects.view', roles));

                return $q.all(promises);
            }, function(error) {

            })
            .then(function(response) {
                var allSubjectsPermission = response[0];
                var mySubjectsPermission = response[1];

                var assessmentSubjects = AssessmentItemsTable
                    .select()
                    .contain(['EducationSubjects'])
                    .where({assessment_id: assessmentId});

                // For no subjects
                var fail = function(response, deferred) {
                    deferred.reject('You do not have access to subjects');
                };

                // For returning of results
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

                if (isSuperAdmin)
                {
                    // Super admin will return all subjects
                    assessmentSubjects = assessmentSubjects.ajax({success: success, defer: true});
                } else
                {
                    // Non super admin logic

                    // Check if has all subjects permission
                    if (!allSubjectsPermission)
                    {
                        // If no all subjects permission, check if user has my subjects permisson
                        if (mySubjectsPermission)
                        {
                            // User has my subjects permission, display subjects relevant to user
                            assessmentSubjects = assessmentSubjects
                                .find('staffSubjects', {class_id: classId, staff_id: securityUserId})
                                .ajax({success: success, defer: true});
                        } else
                        {
                            // Display nothing
                            assessmentSubjects = AssessmentItemsTable.ajax({success: fail, defer: true});
                        }
                    } else {
                        // Display all subjects
                        assessmentSubjects = assessmentSubjects.ajax({success: success, defer: true});
                    }

                }

                return assessmentSubjects;
            }, function(error) {
                console.log('error:');
                console.log(error);
                deferred.reject(error);
            })
            // 3rd
            .then(function(response) {
                deferred.resolve(response);
            }, function(error) {
                console.log('error:');
                console.log(error);
                deferred.reject(error);
            });

            return deferred.promise;
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

        getGradingTypes: function(assessmentId, subjectId)
        {
            var success = function(response, deferred) {
                var gradingTypes = response.data.data;

                if (angular.isObject(gradingTypes) && gradingTypes.length > 0) {
                    var indexedGradingTypes = {};
                    angular.forEach(gradingTypes, function(obj, key) {
                        indexedGradingTypes[obj.assessment_period_id] = obj;
                    });

                    deferred.resolve(indexedGradingTypes);
                } else {
                    deferred.reject('You need to configure Assessment Items Grading Types first');
                }
            };

            return AssessmentItemsGradingTypesTable
            .select()
            .contain(['EducationSubjects', 'AssessmentGradingTypes.GradingOptions'])
            .where({assessment_id: assessmentId, education_subject_id: subjectId})
            .ajax({success: success, defer: true});
        },

        getColumnDefs: function(action, subject, periods, gradingTypes, _results) {
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
                var isMarksType = true; // default is MARKS type
                var isGradesType = false;

                // get grading type by subject and assessment period
                if (angular.isDefined(gradingTypes[period.id])) {
                    subject.grading_type = gradingTypes[period.id].assessment_grading_type;

                    if (subject.grading_type.grading_options.length == 0) {
                        // return error if No Grading Options
                        return {error: 'You need to configure Grading Options first'};
                    }

                    var resultType = subject.grading_type.result_type;
                    var maxMark = subject.grading_type.max;
                    var isMarksType = (resultType == resultTypes.MARKS);
                    var isGradesType = (resultType == resultTypes.GRADES);
                    var isDurationType = (resultType == resultTypes.DURATION);
                }

                var allowEdit = (action == 'edit' && period.editable);
                var headerLabel = period.name + " <span class='divider'></span> " + maxMark;
                var headerName = allowEdit ? headerLabel + " <i class='fa fa-pencil-square-o fa-lg header-icon'></i>" : headerLabel;

                var periodField = 'period_' + period.id;
                var weightField = 'weight_' + period.id;

                var columnDef = {
                    headerName: headerName,
                    field: periodField,
                    filterParams: filterParams
                };

                var extra = {};
                if (isMarksType) {
                    if (subject.grading_type != null) {
                        extra = {
                            minMark: 0,
                            passMark: subject.grading_type.pass_mark,
                            maxMark: subject.grading_type.max
                        };
                    }

                    columnDef = ResultsSvc.renderMarks(allowEdit, columnDef, extra, _results);
                } else if (isGradesType) {
                    if (subject.grading_type != null) {
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

                    extra['period'] = period;
                    columnDef = ResultsSvc.renderGrades(allowEdit, columnDef, extra, _results);
                } else if (isDurationType) {
                    if (subject.grading_type != null) {
                        extra = {
                            minMark: 0,
                            passMark: subject.grading_type.pass_mark,
                            maxMark: subject.grading_type.max
                        };
                    }

                    extra['period'] = period;
                    columnDef = ResultsSvc.renderDuration(allowEdit, columnDef, extra, _results);
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
                headerName: "is modified",
                field: "is_dirty",
                hide: true
            });

            return {data: columnDefs};
        },

        renderMarks: function(allowEdit, cols, extra, _results) {
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

        renderGrades: function(allowEdit, cols, extra, _results) {
            var gradingOptions = extra.gradingOptions;
            var period = extra.period;

            if (allowEdit) {
                cols = angular.merge(cols, {
                    cellClass: 'oe-cell-highlight',
                    cellRenderer: function(params) {
                        if (params.value.length == 0) {
                            params.value = 0;
                        }

                        var oldValue = params.value;
                        var studentId = params.data.student_id;
                        var periodId = period.id;

                        var eCell = document.createElement('div');
                        eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");

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

                            if (angular.isUndefined(_results[studentId])) {
                                _results[studentId] = {};
                            }

                            if (angular.isUndefined(_results[studentId][periodId])) {
                                _results[studentId][periodId] = {gradingOptionId: ''};
                            }

                            _results[studentId][periodId]['gradingOptionId'] = newValue;
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

        renderDuration: function(allowEdit, cols, extra, _results) {
            var minMark = extra.minMark;
            var passMark = extra.passMark;
            var maxMark = extra.maxMark;
            var periodId = extra.period.id;

            cols = angular.merge(cols, {
                // filter: 'text',
                cellStyle: function(params) {
                    var value = params.data[params.colDef.field];
                    var valueInSeconds = value * 60;

                    if (!isNaN(parseFloat(value)) && parseInt(valueInSeconds) > passMark) {
                        return {color: '#CC5C5C'};
                    } else {
                        return {color: '#333'};
                    }
                },
                valueGetter: function(params) {
                    var value = params.data[params.colDef.field];

                    if (!isNaN(parseFloat(value))) {
                        var duration = $filter('number')(value, 2);
                        var formatDuration = String(duration).replace(".", " : ");
                        return formatDuration;
                    } else {
                        return '';
                    }
                }
            });

            var ResultsSvc = this;
            if (allowEdit) {
                cols = angular.merge(cols, {
                    cellClass: 'oe-cell-highlight',
                    cellRenderer: function(params) {
                        var studentId = params.data.student_id;

                        var eCell = document.createElement('div');
                        var minuteInput = document.createElement('input');
                        minuteInput.setAttribute("id", "mins");
                        minuteInput.setAttribute("type", "number");
                        minuteInput.setAttribute("min", "0");
                        minuteInput.setAttribute("max", "9999");
                        minuteInput.setAttribute("style", "width: 50%; border: none; background-color: inherit; text-align: center;");

                        var text = document.createElement('span');
                        var colon = document.createTextNode(" : ");
                        text.setAttribute("style", "font-weight:strong");
                        text.appendChild(colon);

                        var secondInput = document.createElement('input');
                        secondInput.setAttribute("id", "secs");
                        secondInput.setAttribute("type", "number");
                        secondInput.setAttribute("min", "0");
                        secondInput.setAttribute("max", "59");
                        secondInput.setAttribute("style", "width: 50%; border: none; background-color: inherit; text-align: center;");

                        eCell.appendChild(minuteInput);
                        eCell.appendChild(text);
                        eCell.appendChild(secondInput);

                        var oldValue = params.data[params.colDef.field];
                        if (oldValue) {
                            console.log(typeof(oldValue));
                            var duration = String(oldValue).split(".");
                            minuteInput.value = duration[0];
                            secondInput.value = duration[1];
                        }

                        eCell.addEventListener('change', function() {
                            var minuteFloat = parseFloat(minuteInput.value);
                            var secondFloat = parseFloat(secondInput.value);

                            if (minuteInput.value.length > 0 && (isNaN(minuteFloat) || (minuteFloat < 0 || minuteFloat > 9999))) {
                                minuteInput.value = '';
                                secondInput.value = '';
                            }

                            if (secondInput.value.length > 0 && (isNaN(secondFloat) || (secondFloat < 0 || secondFloat > 59 || secondInput.value.length > 2))) {
                                minuteInput.value = '';
                                secondInput.value = '';
                            } else if (secondInput.value.length == 1 && secondFloat < 10 ) {
                                // for padding
                                secondInput.value = '0' + secondInput.value;
                            }

                            if (angular.isUndefined(_results[studentId])) {
                                _results[studentId] = {};
                            }

                            if (angular.isUndefined(_results[studentId][periodId])) {
                                _results[studentId][periodId] = {duration: ''};
                            }

                            var duration = minuteInput.value + '.' + secondInput.value;

                            params.data[params.colDef.field] = duration;
                            _results[studentId][periodId]['duration'] = duration;
                        });

                        return eCell;
                    },
                    suppressMenu: true
                });
            }
            return cols;
        },

        getRowData: function(gradingTypes, periods, institutionId, classId, assessmentId, academicPeriodId, educationSubjectId) {
            var success = function(response, deferred) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var subjectStudents = response.data.data;

                    var periodObj = {};
                    angular.forEach(periods, function(period, key) {
                        periodObj[period.id] = period;
                    }, periodObj);

                    if (angular.isObject(subjectStudents) && subjectStudents.length > 0) {
                        var studentId = null;
                        var currentStudentId = null;
                        var studentResults = {};
                        var rowData = [];
                        var assessmentPeriodId = null;

                        var isMarksType = true; // default to MARKS
                        var isGradesType = false;
                        var isDurationType = false;
                        var resultType = null;

                        angular.forEach(subjectStudents, function(subjectStudent, key) {
                            currentStudentId = parseInt(subjectStudent.student_id);
                            assessmentPeriodId = subjectStudent.AssessmentItemResults.assessment_period_id;
                            if (assessmentPeriodId != null && angular.isDefined(gradingTypes[assessmentPeriodId])) {
                                resultType = gradingTypes[assessmentPeriodId].assessment_grading_type.result_type;
                            }

                            isMarksType = (resultType == resultTypes.MARKS);
                            isGradesType = (resultType == resultTypes.GRADES);
                            isDurationType = (resultType == resultTypes.DURATION);

                            if (studentId != currentStudentId) {
                                if (studentId != null) {
                                    this.push(studentResults);
                                }

                                studentResults = {
                                    openemis_id: subjectStudent._matchingData.Users.openemis_no,
                                    name: subjectStudent._matchingData.Users.name,
                                    student_id: currentStudentId,
                                    total_mark: subjectStudent.total_mark,
                                    is_dirty: false
                                };

                                var periodWeight = 0;
                                angular.forEach(periods, function(period, key) {
                                    var resultTypeByPeriod = gradingTypes[period.id].assessment_grading_type.result_type;

                                    // if is GRADES type, set weight to empty so that will not be included when calculate total marks.
                                    if (resultTypeByPeriod == resultTypes.MARKS) {
                                        periodWeight = parseFloat(periodObj[parseInt(period.id)]['weight']);
                                    } else if (resultTypeByPeriod == resultTypes.GRADES) {
                                        periodWeight = '';
                                    }

                                    studentResults['period_' + parseInt(period.id)] = '';
                                    studentResults['weight_' + parseInt(period.id)] = periodWeight;
                                });

                                studentId = currentStudentId;
                            }

                            if (isMarksType) {
                                var marks = parseFloat(subjectStudent.AssessmentItemResults.marks);
                                if (!isNaN(marks)) {
                                    studentResults['period_' + parseInt(assessmentPeriodId)] = marks;
                                }
                            } else if (isGradesType) {
                                if (subjectStudent.AssessmentItemResults.assessment_grading_option_id != null) {
                                    studentResults['period_' + parseInt(assessmentPeriodId)] = subjectStudent.AssessmentItemResults.assessment_grading_option_id;
                                }
                            } else if (isDurationType) {
                                var duration = parseFloat(subjectStudent.AssessmentItemResults.marks);
                                if (!isNaN(duration)) {
                                    studentResults['period_' + parseInt(assessmentPeriodId)] = subjectStudent.AssessmentItemResults.marks;
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
            .find('Results', {
                institution_id: institutionId,
                class_id: classId,
                assessment_id: assessmentId,
                academic_period_id: academicPeriodId,
                subject_id: educationSubjectId
            })
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
        },

        saveRowData: function(subject, gradingTypes, results, assessmentId, educationSubjectId, institutionId, academicPeriodId) {
            var promises = [];

            angular.forEach(results, function(result, studentId) {
                angular.forEach(result, function(obj, assessmentPeriodId) {
                    subject.grading_type = gradingTypes[assessmentPeriodId].assessment_grading_type;
                    var resultType = subject.grading_type.result_type;

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
                    } else if (resultType == resultTypes.DURATION) {
                        if (!isNaN(parseFloat(obj.duration))) {
                            marks = $filter('number')(obj.duration, 2);
                            durationInSeconds = marks * 60;
                            var gradingObj = this.getGrading(subject, durationInSeconds);
                            gradingOptionId = gradingObj.id;
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
