angular.module('institution.student.competencies.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.competencies.svc'])
    .controller('InstitutionStudentCompetenciesCtrl', InstitutionStudentCompetenciesController);

InstitutionStudentCompetenciesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentCompetenciesSvc'];

function InstitutionStudentCompetenciesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentCompetenciesSvc) {

    var Controller = this;

    // Constants
    var suppressMenu = true;
    var suppressSorting = true;
    Controller.dataReady = false;

    // Variables
    Controller.gridOptions = {};
    Controller.classId = null;
    Controller.className = '';
    Controller.competencyTemplateId = null;
    Controller.competencyTemplateName = '';
    Controller.academicPeriodId = null;
    Controller.academicPeriodName = '';
    Controller.institutionId = null;
    Controller.criteriaGradeOptions = {};
    Controller.studentResults = {};
    Controller.studentComments = {};
    // filters
    Controller.periodOptions = [];
    Controller.selectedPeriod = null;
    Controller.selectedPeriodStatus = null;
    Controller.itemOptions = [];
    Controller.selectedItem = null;
    Controller.studentOptions = [];
    Controller.selectedStudent = null;

    // Function mapping
    Controller.initGrid = initGrid;
    Controller.formatResults = formatResults;
    Controller.formatComments = formatComments;
    Controller.resetColumnDefs = resetColumnDefs;
    Controller.changeCompetencyOptions = changeCompetencyOptions;

    angular.element(document).ready(function () {
        InstitutionStudentCompetenciesSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        if (Controller.classId != null) {
            InstitutionStudentCompetenciesSvc.getClassDetails(Controller.classId)
            .then(function(response) {
                Controller.className = response.name;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.institutionId = response.institution_id;
                return InstitutionStudentCompetenciesSvc.getStudentStatusId("CURRENT");
            }, function(error) {
                console.log(error);
            })
            .then(function (response) {
                var enrolledStatusId = response[0].id;
                return InstitutionStudentCompetenciesSvc.getClassStudents(Controller.classId, enrolledStatusId);
            }, function(error) {
                console.log(error);
            })
            .then(function (classStudents) {
                Controller.studentOptions = classStudents;
                if (Controller.studentOptions.length > 0) {
                    Controller.selectedStudent = Controller.studentOptions[0].student_id;
                } else {
                    AlertSvc.warning(Controller, "Please setup students for this class");
                }
                return InstitutionStudentCompetenciesSvc.getCompetencyTemplate(Controller.academicPeriodId, Controller.competencyTemplateId);
            }, function(error) {
                console.log(error);
            })
            .then(function (competencyTemplate) {
                Controller.competencyTemplateId = competencyTemplate.id;
                Controller.competencyTemplateName = competencyTemplate.code_name;
                var criterias = competencyTemplate.criterias;
                angular.forEach(criterias, function(value, key) {
                    Controller.criteriaGradeOptions[value.id] = {};
                    Controller.criteriaGradeOptions[value.id]['name'] = value.name;
                    Controller.criteriaGradeOptions[value.id]['code'] = value.code;
                    Controller.criteriaGradeOptions[value.id]['grading_type'] = value.grading_type;
                    Controller.criteriaGradeOptions[value.id]['competency_item_id'] = value.competency_item_id;
                    Controller.criteriaGradeOptions[value.id]['id'] = value.id;
                });
                Controller.periodOptions = competencyTemplate.periods;
                if (Controller.periodOptions.length > 0) {
                    Controller.selectedPeriod = Controller.periodOptions[0].id;
                    Controller.selectedPeriodStatus = Controller.periodOptions[0].editable;
                    Controller.itemOptions = Controller.periodOptions[0].competency_items;
                    if (Controller.itemOptions.length > 0) {
                        Controller.selectedItem = Controller.periodOptions[0].competency_items[0].id;
                    } else {
                        AlertSvc.warning(Controller, "Please setup competency items for the selected period");
                    }
                } else {
                    AlertSvc.warning(Controller, "Please setup competency periods for the selected template");
                }
                return InstitutionStudentCompetenciesSvc.getStudentCompetencyResults(
                    Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
                console.log(error);
            })
            .then(function (competencyResults) {
                Controller.formatResults(competencyResults);
                return InstitutionStudentCompetenciesSvc.getStudentCompetencyComments(
                    Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
            })
            .then(function (competencyItemComments) {
                Controller.formatComments(competencyItemComments);
                return Controller.initGrid();
            }, function (error) {
            })
            .finally(function(){
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });
        }

    });

    function resetColumnDefs(criteriaGradeOptions, period, selectedPeriodStatus, item, student) {
        var response = InstitutionStudentCompetenciesSvc.getColumnDefs(item, student, Controller.itemOptions, Controller.studentOptions);

        if (angular.isDefined(response.error)) {
            // No Grading Options
            AlertSvc.warning($scope, response.error);
            return false;
        } else {
            if (Controller.gridOptions != null) {
                var textToTranslate = [];
                angular.forEach(response.data, function(value, key) {
                    textToTranslate.push(value.headerName);
                });
                InstitutionStudentCompetenciesSvc.translate(textToTranslate)
                .then(function(res){
                    angular.forEach(res, function(value, key) {
                        response.data[key]['headerName'] = value;
                    });
                    Controller.gridOptions.api.setColumnDefs(response.data);

                    if (item != null && student != null) {
                        // competency criterias
                        var rowData = [];
                        angular.forEach(criteriaGradeOptions, function (value, key) {
                            if (value.competency_item_id == item) {
                                var criteriaName = value.name;
                                if (value.code != null && value.code.length > 0) {
                                    criteriaName = value.code + " <span class='divider'></span> " + value.name;
                                }
                                var row = {
                                    student_id: student,
                                    period_editable: selectedPeriodStatus,
                                    competency_period_id: period,
                                    competency_item_id: item,
                                    competency_criteria_name: criteriaName,
                                    competency_criteria_id: value.id,
                                    grading_options: value.grading_type.grading_options,
                                    result: 0,
                                    save_error: {
                                        result: false
                                    }
                                };

                                if (angular.isDefined(Controller.studentResults[value.id])) {
                                    row['result'] = Controller.studentResults[value.id];
                                }
                                this.push(row);
                            }
                        }, rowData);
                        Controller.gridOptions.api.setRowData(rowData);

                        if (rowData.length > 0) {
                            AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                        } else {
                            AlertSvc.warning(Controller, "Please setup competency criterias for the selected item");
                            Controller.gridOptions.api.hideOverlay();
                            var emptyRow = [{
                                period_editable: false,
                                competency_criteria_name: 'No Competency Criterias',
                                result: '',
                                save_error: {
                                    result: false
                                }
                            }];
                            Controller.gridOptions.api.setRowData(emptyRow);
                        }

                        // item comments (pinned row at bottom)
                        var comments = '';
                        if (angular.isDefined(Controller.studentComments['comments'])) {
                            comments = Controller.studentComments['comments'];
                        }
                        var pinnedRowData = [{
                            student_id: student,
                            period_editable: selectedPeriodStatus,
                            competency_period_id: period,
                            competency_item_id: item,
                            competency_criteria_name: 'Comments',
                            result: comments,
                            save_error: {
                                result: false
                            }
                        }];
                        Controller.gridOptions.api.setPinnedBottomRowData(pinnedRowData);
                    }

                    Controller.gridOptions.api.sizeColumnsToFit();
                }, function(error){
                    console.log(error);
                });
                return true;
            } else {
                return true;
            }
        }
    }

    function formatResults(competencyResults) {
        var studentResults = {};
        angular.forEach(competencyResults, function (value, key) {
            // Format for the student criteria result will be criteria_id.grading_option_id
            if (studentResults[value.competency_criteria_id] == undefined) {
                studentResults[value.competency_criteria_id] = {};
            }
            studentResults[value.competency_criteria_id] = value.competency_grading_option_id;
        });
        Controller.studentResults = studentResults;
    }

    function formatComments(competencyItemComments) {
        var studentComments = {};
        angular.forEach(competencyItemComments, function (value, key) {
            studentComments['comments'] = value.comments;
        });
        Controller.studentComments = studentComments;
    }

    function changeCompetencyOptions(periodChange) {
        if (periodChange) {
            angular.forEach(Controller.periodOptions, function(value, key) {
                if (value.id == Controller.selectedPeriod) {
                    Controller.itemOptions = value.competency_items;
                    Controller.selectedItem = value.competency_items[0].id;
                    Controller.selectedPeriodStatus = value.editable;
                }
            });
        }
        InstitutionStudentCompetenciesSvc.getStudentCompetencyResults(
            Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId)
        .then(function (results) {
            Controller.formatResults(results);
            return InstitutionStudentCompetenciesSvc.getStudentCompetencyComments(
                Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId);
        }, function (error) {
        })
        .then(function (comments) {
            Controller.formatComments(comments);
            Controller.resetColumnDefs(Controller.criteriaGradeOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent);
        }, function (error) {
        });
    }

    function initGrid() {
        return AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institutionId,
                    academic_period_id: Controller.academicPeriodId,
                    competency_template_id: Controller.competencyTemplateId,
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                // Removed options - Issues in ag-Grid AG-828
                // suppressCellSelection: true,

                // Added options
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                localeText: localeText,
                domLayout: 'autoHeight',
                onGridSizeChanged: function(e) {
                    this.api.sizeColumnsToFit();
                },
                getRowStyle: function(params) {
                    if (params.node.rowPinned) {
                        return {'font-weight': 'bold'}
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.criteriaGradeOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent);
                }
            };
        }, function(error){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institutionId,
                    academic_period_id: Controller.academicPeriodId,
                    competency_template_id: Controller.competencyTemplateId,
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                // Removed options - Issues in ag-Grid AG-828
                // suppressCellSelection: true,

                // Added options
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                localeText: localeText,
                domLayout: 'autoHeight',
                onGridSizeChanged: function(e) {
                    this.api.sizeColumnsToFit();
                },
                getRowStyle: function(params) {
                    if (params.node.rowPinned) {
                        return {'font-weight': 'bold'}
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.criteriaGradeOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent);
                }
            };
        });
    }
}