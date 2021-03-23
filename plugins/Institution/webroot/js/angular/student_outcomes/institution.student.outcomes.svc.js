angular
    .module('institution.student.outcomes.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStudentOutcomesSvc', InstitutionStudentOutcomesSvc);

InstitutionStudentOutcomesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc'];

function InstitutionStudentOutcomesSvc($http, $q, $filter, KdDataSvc, AlertSvc) {

    var service = {
        init: init,
        translate: translate,
        getClassDetails: getClassDetails,
        getClassStudents: getClassStudents,
        getOutcomeTemplate: getOutcomeTemplate,
        getOutcomeGradingTypes: getOutcomeGradingTypes,
        getStudentOutcomeResults: getStudentOutcomeResults,
        getStudentOutcomeComments: getStudentOutcomeComments,
        getColumnDefs: getColumnDefs,
        getRowData: getRowData,
        renderInput: renderInput,
        saveOutcomeResults: saveOutcomeResults,
        saveOutcomeComments: saveOutcomeComments,
        getSubjectOptions: getSubjectOptions
    };

    var models = {
        InstitutionClasses: 'Institution.InstitutionClasses',
        InstitutionSubjects: 'Institution.InstitutionSubjects',
        StudentStatuses: 'Student.StudentStatuses',
        InstitutionClassStudents: 'Institution.InstitutionClassStudents',
        OutcomeTemplates: 'Outcome.OutcomeTemplates',
        OutcomeCriterias: 'Outcome.OutcomeCriterias',
        OutcomeGradingTypes: 'Outcome.OutcomeGradingTypes',
        InstitutionOutcomeResults: 'Institution.InstitutionOutcomeResults',
        OutcomeSubjectComments: 'Institution.InstitutionOutcomeSubjectComments'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentOutcomes');
        KdDataSvc.init(models);
    };

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
            .find('translateItem')
            .contain(['AcademicPeriods'])
            .ajax({success: success, defer:true});
    }

    function getClassStudents(classId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionClassStudents
            .select()
            .contain(['Users','StudentStatuses'])
            .where({institution_class_id: classId})
            .order(['Users.first_name', 'Users.last_name'])
            .ajax({success: success, defer:true});
    }

    function getSubjectOptions(classId, institutionId, academicPeriodId, gradeId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };

        return InstitutionSubjects
            .find('bySubjectsInClass', {
                institution_class_id: classId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId,
                education_grade_id: gradeId
            })
            .ajax({success: success, defer: true});
    }

    function getOutcomeTemplate(academicPeriodId, outcomeTemplateId) {
        var primaryKey = KdDataSvc.urlsafeB64Encode(JSON.stringify({academic_period_id: academicPeriodId, id: outcomeTemplateId}));
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return OutcomeTemplates
            .get(primaryKey)
            .contain(['Periods', 'EducationGrades.EducationSubjects'])
            .ajax({success: success, defer:true});
    }

    function getOutcomeGradingTypes() {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return OutcomeGradingTypes
            .select()
            .contain(['GradingOptions'])
            .ajax({success: success, defer:true});
    }

    function getStudentOutcomeResults(studentId, templateId, periodId, gradeId, subjectId, institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionOutcomeResults
            .find('studentResults', {
                student_id: studentId,
                outcome_template_id: templateId,
                outcome_period_id: periodId,
                education_grade_id: gradeId,
                education_subject_id: subjectId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer:true});
    }

    function getStudentOutcomeComments(studentId,  templateId, periodId, gradeId, subjectId, institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return OutcomeSubjectComments
            .find('studentComments', {
                student_id: studentId,
                outcome_template_id: templateId,
                outcome_period_id: periodId,
                education_grade_id: gradeId,
                education_subject_id: subjectId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer:true});
    }

    function getColumnDefs(period, subject, student, periodOptions, subjectOptions, studentOptions, studentResults) {
        var menuTabs = [];
        var filterParams = {
            cellHeight: 30
        };

        // dynamic table headers
        var criteriaHeader = 'Outcome Criteria';
        var resultHeader = 'Result';
        if (periodOptions.length > 0 && period != null && subjectOptions.length > 0 && subject != null && studentOptions.length > 0 && student != null) {
            var subjectObj = $filter('filter')(subjectOptions, {'id':subject});
            if (subjectObj.length > 0) {
                criteriaHeader = subjectObj[0].code_name + ' Criteria';
            }
            var studentObj = $filter('filter')(studentOptions, {'student_id':student});
            if (studentObj.length > 0) {
                resultHeader = studentObj[0].user.name_with_id;
            }
        }

        var columnDefs = [];
        columnDefs.push({
            headerName: criteriaHeader,
            field: "outcome_criteria_name",
            filterParams: filterParams,
            menuTabs: menuTabs,
            filter: 'text'
        });

        var columnDef = {
            headerName: resultHeader,
            field: "result",
            filterParams: filterParams,
            menuTabs: menuTabs
        };
        var extra = {
            studentResults: studentResults
        };
        columnDef = this.renderInput(columnDef, extra);
        columnDefs.push(columnDef);

        return {data: columnDefs};
    }

    function getRowData(outcomeTemplateId, subjectId, defaultRow, gradingOptions, studentResults, limit, page) {
        var success = function(response, deferred) {
            var criterias = response.data.data;

            var rowData = [];
            angular.forEach(criterias, function (value, key) {
                var row = angular.copy(defaultRow);
                row['outcome_criteria_id'] = value.id;
                row['outcome_criteria_name'] = value.code_name;

                if (angular.isDefined(gradingOptions[value.outcome_grading_type_id])) {
                    row['grading_options'] = gradingOptions[value.outcome_grading_type_id];
                }

                if (angular.isDefined(studentResults[value.id])) {
                    row['result'] = studentResults[value.id];
                }
                this.push(row);
            }, rowData);

            response.data.data = rowData;
            deferred.resolve(response);
        };

        return OutcomeCriterias
            .select()
            .where({
                outcome_template_id: outcomeTemplateId,
                education_subject_id: subjectId
            })
            .limit(limit)
            .page(page)
            .ajax({success: success, defer:true});
    }

    function renderInput(cols, extra) {
        var vm = this;

        cols = angular.merge(cols, {
            cellClassRules: {
                'oe-cell-error': function(params) {
                    if (angular.isDefined(params.data) && angular.isDefined(params.data.save_error)) {
                        return params.data.save_error[params.colDef.field];
                    }
                }
            },
            cellRenderer: function(params) {
                if (angular.isDefined(params.data)) {
                    var periodEditable = params.data.period_editable;
                    var studentStatus = params.data.student_status;
                    var gradingOptions = {0 : '-- Select --'};
                    if (angular.isDefined(params.data.grading_options)) {
                        angular.forEach(params.data.grading_options, function(obj, key) {
                            gradingOptions[obj.id] = obj.code_name;
                        });
                    }

                    if (periodEditable && studentStatus == "CURRENT") {
                        var oldValue = params.value;

                        var eCell = document.createElement('div');
                        eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");
                        eCell.setAttribute("style","height:100%");

                        var eSelect = document.createElement("select");

                        angular.forEach(gradingOptions, function(value, key) {
                            var eOption = document.createElement("option");
                            eOption.setAttribute("value", key);
                            eOption.innerHTML = value;
                            eSelect.appendChild(eOption);
                        });
                        eSelect.value = params.value;

                        eSelect.addEventListener('blur', function () {
                            var newValue = eSelect.value;

                            if (newValue != oldValue || params.data.save_error[params.colDef.field]) {
                                params.data[params.colDef.field] = newValue;

                                var outcomeCriteriaId = params.data.outcome_criteria_id;
                                if (extra.studentResults[outcomeCriteriaId] == undefined) {
                                    extra.studentResults[outcomeCriteriaId] = 0;
                                }
                                extra.studentResults[outcomeCriteriaId] = newValue;

                                var controller = params.context._controller;
                                vm.saveOutcomeResults(params)
                                .then(function(response) {
                                    params.data.save_error[params.colDef.field] = false;
                                    AlertSvc.info(controller, "Changes will be automatically saved when any value is changed");
                                    params.api.refreshCells({
                                        rowNodes: [params.node],
                                        columns: [params.colDef.field],
                                        force: true
                                    });

                                }, function(error) {
                                    params.data.save_error[params.colDef.field] = true;
                                    console.log(error);
                                    AlertSvc.error(controller, "There was an error when saving the results");
                                    params.api.refreshCells({
                                        rowNodes: [params.node],
                                        columns: [params.colDef.field],
                                        force: true
                                    });
                                });
                            }
                        });

                        eCell.appendChild(eSelect);

                    } else {
                        // don't allow input if period is not editable
                        var cellValue = '';
                        if (angular.isDefined(params.value) && params.value.length != 0 && params.value != 0) {
                            cellValue = gradingOptions[params.value];
                        }

                        var eCell = document.createElement('div');
                        eCell.setAttribute("style","height:100%");
                        var eLabel = document.createTextNode(cellValue);
                        eCell.appendChild(eLabel);
                    }

                    return eCell;
                }
            },
            pinnedRowCellRenderer: function(params) {
                if (angular.isDefined(params.data)) {
                    var periodEditable = params.data.period_editable;
                    var studentStatus = params.data.student_status;

                    if (periodEditable && studentStatus == "CURRENT") {
                        var oldValue = params.value;

                        var eCell = document.createElement('div');
                        var textInput = document.createElement('textarea');

                        textInput.setAttribute("type", "text");
                        textInput.setAttribute("class", "oe-cell-editable");
                        textInput.setAttribute("style","height:100%");
                        textInput.value = params.value;
                        eCell.appendChild(textInput);

                        // allow keyboard shortcuts
                        textInput.addEventListener('keydown', function(event) {
                            event.stopPropagation();
                        });

                        textInput.addEventListener('blur', function() {
                            var newValue = textInput.value;
                            var controller = params.context._controller;
                            if (newValue != oldValue || params.data.save_error[params.colDef.field]) {
                                params.data[params.colDef.field] = newValue;
                                
                                var newVal = newValue;
                                var format = /[ `/'"=%]/;
                                if(format.test(newVal.charAt(0))) {
                                    AlertSvc.warning(controller, 'Special character not allow at first character of text');
                                    return false
                                } else {
                                    AlertSvc.info(controller, 'Changes will be automatically saved when any value is changed');
                                } 
                                
                                vm.saveOutcomeComments(params)
                                .then(function(response) {
                                    params.data.save_error[params.colDef.field] = false;
                                    AlertSvc.info(controller, "Changes will be automatically saved when any value is changed");
                                    params.api.refreshCells({
                                        rowNodes: [params.node],
                                        columns: [params.colDef.field],
                                        force: true
                                    });

                                }, function(error) {
                                    params.data.save_error[params.colDef.field] = true;
                                    console.log(error);
                                    AlertSvc.error(controller, "There was an error when saving the comments");
                                    params.api.refreshCells({
                                        rowNodes: [params.node],
                                        columns: [params.colDef.field],
                                        force: true
                                    });
                                });
                            }
                        });

                    } else {
                        // don't allow input if period is not editable
                        var cellValue = '';
                        if (angular.isDefined(params.value) && params.value.length != 0) {
                            cellValue = params.value;
                        }

                        var eCell = document.createElement('div');
                        var eLabel = document.createTextNode(cellValue);
                        eCell.appendChild(eLabel);
                    }
                    return eCell;
                }
            },
            suppressMenu: true
        });
        return cols;
    }

    function saveOutcomeResults(params) {
        var outcomeGradingOptionId = params.data.result;
        var studentId = params.data.student_id;
        var outcomeTemplateId = params.context.outcome_template_id;
        var outcomePeriodId = params.data.outcome_period_id;
        var educationGradeId = params.context.education_grade_id;
        var educationSubjectId = params.data.education_subject_id;
        var outcomeCriteriaId = params.data.outcome_criteria_id;
        var institutionId = params.context.institution_id;
        var academicPeriodId = params.context.academic_period_id;

        var saveObj = {
            outcome_grading_option_id: parseInt(outcomeGradingOptionId),
            student_id: studentId,
            outcome_template_id: outcomeTemplateId,
            outcome_period_id: outcomePeriodId,
            education_grade_id: educationGradeId,
            education_subject_id: educationSubjectId,
            outcome_criteria_id: outcomeCriteriaId,
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        };
        return InstitutionOutcomeResults.save(saveObj);
    }

    function saveOutcomeComments(params) {
        var comments = params.data.result;
        var studentId = params.data.student_id;
        var outcomeTemplateId = params.context.outcome_template_id;
        var outcomePeriodId = params.data.outcome_period_id;
        var educationGradeId = params.context.education_grade_id;
        var educationSubjectId = params.data.education_subject_id;
        var institutionId = params.context.institution_id;
        var academicPeriodId = params.context.academic_period_id;

        var saveObj = {
            comments: comments,
            student_id: studentId,
            outcome_template_id: outcomeTemplateId,
            outcome_period_id: outcomePeriodId,
            education_grade_id: educationGradeId,
            education_subject_id: educationSubjectId,
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        };
        return OutcomeSubjectComments.save(saveObj);
    }
};