angular
    .module('institution.student.competencies.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStudentCompetenciesSvc', InstitutionStudentCompetenciesSvc);

InstitutionStudentCompetenciesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc'];

function InstitutionStudentCompetenciesSvc($http, $q, $filter, KdDataSvc, AlertSvc) {

    var service = {
        init: init,
        getClassDetails: getClassDetails,
        getClassStudents: getClassStudents,
        getCompetencyTemplate: getCompetencyTemplate,
        getCompetencyGradingTypes: getCompetencyGradingTypes,
        translate: translate,
        saveCompetencyResults: saveCompetencyResults,
        saveCompetencyComments: saveCompetencyComments,
        getStudentCompetencyResults: getStudentCompetencyResults,
        getStudentCompetencyComments: getStudentCompetencyComments,
        getColumnDefs: getColumnDefs,
        renderInput: renderInput,
        renderCommentColumns: renderCommentColumns
    };

    var models = {
        InstitutionClasses: 'Institution.InstitutionClasses',
        StudentStatuses: 'Student.StudentStatuses',
        InstitutionClassStudents: 'Institution.InstitutionClassStudents',
        CompetencyTemplates: 'Competency.CompetencyTemplates',
        CompetencyGradingTypes: 'Competency.CompetencyGradingTypes',
        InstitutionCompetencyResults: 'Institution.InstitutionCompetencyResults',
        CompetencyItemComments: 'Institution.InstitutionCompetencyItemComments'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentCompetencies');
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
            .contain(['AcademicPeriods', 'ClassStudents.Users', 'ClassStudents.StudentStatuses'])
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

    function getStudentCompetencyResults(templateId, periodId, itemId, studentId, institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionCompetencyResults
            .find('studentResults', {
                competency_template_id: templateId,
                competency_period_id: periodId,
                competency_item_id: itemId,
                student_id: studentId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer:true});
    }

    function getStudentCompetencyComments(templateId, periodId, itemId, studentId, institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return CompetencyItemComments
            .find('studentComments', {
                competency_template_id: templateId,
                competency_period_id: periodId,
                competency_item_id: itemId,
                student_id: studentId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer:true});
    }

    function getCompetencyTemplate(academicPeriodId, competencyTemplateId) {
        var primaryKey = KdDataSvc.urlsafeB64Encode(JSON.stringify({academic_period_id: academicPeriodId, id: competencyTemplateId}));
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return CompetencyTemplates
            .get(primaryKey)
            .contain(['Periods.CompetencyItems', 'Criterias'])
            .ajax({success: success, defer:true});
    }

    function getCompetencyGradingTypes() {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return CompetencyGradingTypes
            .select()
            .contain(['GradingOptions'])
            .ajax({success: success, defer:true});
    }

    function getColumnDefs(item, student, itemOptions, studentOptions) {
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30
        };

        // dynamic table headers
        var criteriaHeader = 'Competency Criteria';
        var resultHeader = 'Result';
        if (itemOptions.length > 0 && item != null && studentOptions.length > 0 && student != null) {
            var itemObj = $filter('filter')(itemOptions, {'id':item});
            if (itemObj.length > 0) {
                criteriaHeader = itemObj[0].name + ' Criteria';
            }
            var studentObj = $filter('filter')(studentOptions, {'student_id':student});
            if (studentObj.length > 0) {
                resultHeader = studentObj[0].user.name_with_id;
            }
        }

        var columnDefs = [];
        columnDefs.push({
            headerName: criteriaHeader,
            field: "competency_criteria_name",
            filterParams: filterParams,
            menuTabs: menuTabs,
            filter: 'text'
        });
        columnDefs.push({
            headerName: "competency criteria id",
            field: "competency_criteria_id",
            hide: true
        });

        var columnDef = {
            headerName: resultHeader,
            field: "result",
            filterParams: filterParams,
            menuTabs: menuTabs
        };
        var extra = {};
        columnDef = this.renderInput(columnDef, extra);
        columnDefs.push(columnDef);

        var resultCommentsColumn = {
            headerName: 'Comments',
            field: 'comments'
        }
        resultCommentsColumn = this.renderCommentColumns(resultCommentsColumn);
        columnDefs.push(resultCommentsColumn);

        return {data: columnDefs};
    }

    function renderCommentColumns(commentsColumn) {
        var vm = this;

        commentsColumn = angular.merge(commentsColumn, {
            cellClassRules: {
                'oe-cell-error': function(params) {
                    return params.data.save_error[params.colDef.field];
                }
            },
            cellRenderer: function (params) {
                if (angular.isDefined(params.data)) {
                    var periodEditable = params.data.period_editable;
                    var studentStatus = params.data.student_status;

                    if (periodEditable && studentStatus == "CURRENT") {
                    var oldValue = params.value;

                    var eCell = document.createElement('div');

                    var commentInput = document.createElement('textarea');

                    commentInput.setAttribute("type", "text");
                    commentInput.setAttribute("class", "oe-cell-editable");
                    commentInput.setAttribute("style","height:100%");

                    commentInput.value = '';
                    if (angular.isDefined(params.value)) {
                        commentInput.value = params.value;
                    }

                    eCell.appendChild(commentInput);

                    // allow keyboard shortcuts
                    commentInput.addEventListener('keydown', function(event) {
                        event.stopPropagation();
                    });

                    commentInput.addEventListener('blur', function() {
                        var newValue = commentInput.value;
                        var controller = params.context._controller;
                        if (newValue != oldValue || params.data.save_error[params.colDef.field]) {

                            var newVal = newValue;
                            var format = /[ `/'"=%]/;
                            if(format.test(newVal.charAt(0))) {
                                AlertSvc.warning(controller, 'Special character not allow at first character of text');
                                return false
                            } else {
                                AlertSvc.info(controller, 'Changes will be automatically saved when any value is changed');
                            } 

                            params.data[params.colDef.field] = newValue;

                            vm.saveCompetencyResults(params)
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
            pinnedRowCellRenderer: function(params) {
                var periodEditable = params.data.period_editable;
                var studentStatus = params.data.student_status;

                if (periodEditable && studentStatus == "CURRENT") {
                    var oldValue = params.value;

                    var eCell = document.createElement('div');
                    var textInput = document.createElement('textarea');
                    textInput.setAttribute("type", "text");
                    textInput.setAttribute("class", "oe-cell-editable");
                    textInput.setAttribute("style", "height:100%");

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

                            var newVal = newValue;
                            var format = /[ `/'"=%]/;
                            if(format.test(newVal.charAt(0))) {
                                AlertSvc.warning(controller, 'Special character not allow at first character of text');
                                return false
                            } else {
                                AlertSvc.info(controller, 'Changes will be automatically saved when any value is changed');
                            } 

                            params.data[params.colDef.field] = newValue;

                            
                            vm.saveCompetencyComments(params)
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
            },
            suppressMenu: true
        });

        return commentsColumn;
    }

    function renderInput(cols, extra) {
        var vm = this;

        cols = angular.merge(cols, {
            cellClassRules: {
                'oe-cell-error': function(params) {
                    return params.data.save_error[params.colDef.field];
                }
            },
            pinnedRowCellRenderer: function(params) {
                return params.value;
            },
            cellRenderer: function(params) {
                var periodEditable = params.data.period_editable;
                var studentStatus = params.data.student_status;

                var gradingOptions = {
                    0 : {
                        id: '',
                        code: '',
                        name: '-- Select --'
                    }
                };
                if (angular.isDefined(params.data.grading_options)) {
                    angular.forEach(params.data.grading_options, function(obj, key) {
                        gradingOptions[obj.id] = obj;
                    });
                }

                if (periodEditable && studentStatus == "CURRENT") {
                    var oldValue = params.value;

                    var eCell = document.createElement('div');
                    eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");
                    eCell.setAttribute("style","height:100%")
                    var eSelect = document.createElement("select");

                    angular.forEach(gradingOptions, function(obj, key) {
                        var eOption = document.createElement("option");
                        var labelText = obj.name;
                        if (obj.code.length > 0) {
                            labelText = obj.code + ' - ' + labelText;
                        }
                        eOption.setAttribute("value", obj.id);
                        eOption.innerHTML = labelText;
                        eSelect.appendChild(eOption);
                    });
                    eSelect.value = params.value;

                    eSelect.addEventListener('blur', function () {
                        var newValue = eSelect.value;

                        if (newValue != oldValue || params.data.save_error[params.colDef.field]) {
                            params.data[params.colDef.field] = newValue;

                            var controller = params.context._controller;
                            vm.saveCompetencyResults(params)
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
                        cellValue = gradingOptions[params.value]['name'];
                        if (gradingOptions[params.value]['code'].length > 0) {
                            cellValue = gradingOptions[params.value]['code'] + ' - ' + cellValue;
                        }
                    }

                    var eCell = document.createElement('div');
                    var eLabel = document.createTextNode(cellValue);
                    eCell.appendChild(eLabel);
                }

                return eCell;
            },
            suppressMenu: true
        });
        return cols;
    }

    function saveCompetencyResults(params) {
        var competencyTemplateId = params.context.competency_template_id;
        var competencyItemId = params.data.competency_item_id;
        var competencyCriteriaId = params.data.competency_criteria_id;
        var competencyPeriodId = params.data.competency_period_id;
        var institutionId = params.context.institution_id;
        var academicPeriodId = params.context.academic_period_id;
        var competencyGradingOptionId = params.data.result;
        var studentId = params.data.student_id;
        var comments = params.data.comments;

        var saveObj = {
            competency_grading_option_id: parseInt(competencyGradingOptionId),
            student_id: studentId,
            competency_template_id: competencyTemplateId,
            competency_item_id: competencyItemId,
            competency_criteria_id: competencyCriteriaId,
            competency_period_id: competencyPeriodId,
            institution_id: institutionId,
            academic_period_id: academicPeriodId,
            comments: comments
        };
        return InstitutionCompetencyResults.save(saveObj);
    }

    function saveCompetencyComments(params) {
        var competencyTemplateId = params.context.competency_template_id;
        var competencyItemId = params.data.competency_item_id;
        var competencyPeriodId = params.data.competency_period_id;
        var institutionId = params.context.institution_id;
        var academicPeriodId = params.context.academic_period_id;
        var itemComments = params.data.comments;
        var studentId = params.data.student_id;

        var saveObj = {
            comments: itemComments,
            student_id: studentId,
            competency_template_id: competencyTemplateId,
            competency_item_id: competencyItemId,
            competency_period_id: competencyPeriodId,
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        };
        return CompetencyItemComments.save(saveObj);
    }
};