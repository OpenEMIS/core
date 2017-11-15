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
    Controller.bodyDir = getComputedStyle(document.body).direction;
    Controller.colDef = [
        {headerName: 'OpenEMIS ID', field: 'openemis_no'},
        {headerName: 'Student Name', field: 'name'},
        {headerName: 'Student Status', field: 'student_status_name'}
    ];
    Controller.alertUrl = '';
    Controller.redirectUrl = '';
    Controller.classId = null;
    Controller.className = '';
    Controller.competencyTemplateId = null;
    Controller.academicPeriodId = null;
    Controller.academicPeriodName = '';
    Controller.postError = [];
    // format of competency result will be competencyperiodId.competencyItemId.studentId.criteriaId
    Controller.competencyItemResults = {};
    Controller.competencyTemplateName = '';
    Controller.criteriaOptions = [];
    Controller.gridOptions = {};
    Controller.criteriaGradeOptions = {};
    Controller.studentResults = {};
    Controller.studentComments = {};

    // Function mapping
    Controller.initGrid = initGrid;
    Controller.changeCriteria = changeCriteria;
    Controller.changeComments = changeComments;
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
                Controller.institutionId = response.institution_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.studentList = response.class_students;
                var promises = [];
                return InstitutionStudentCompetenciesSvc.getCompetencyTemplate(Controller.academicPeriodId, Controller.competencyTemplateId);
            }, function(error) {
                console.log(error);
            })
            .then(function (competencyTemplate) {
                Controller.competencyTemplateId = competencyTemplate.id;
                Controller.competencyTemplateName = competencyTemplate.code_name;
                Controller.criterias = competencyTemplate.criterias;
                angular.forEach(Controller.criterias, function(value, key) {
                    Controller.criteriaGradeOptions[value.id] = {};
                    Controller.criteriaGradeOptions[value.id]['name'] = value.name;
                    Controller.criteriaGradeOptions[value.id]['code'] = value.code;
                    Controller.criteriaGradeOptions[value.id]['grading_type'] = value.grading_type;
                    Controller.criteriaGradeOptions[value.id]['competency_item_id'] = value.competency_item_id;
                    Controller.criteriaGradeOptions[value.id]['id'] = value.id;
                });
                Controller.periodOptions = competencyTemplate.periods;
                if (Controller.periodOptions.length > 0) {
                    Controller.itemOptions = Controller.periodOptions[0].competency_items;
                    Controller.selectedPeriod = Controller.periodOptions[0].id;
                    Controller.selectedPeriodStatus = Controller.periodOptions[0].editable;
                    if (Controller.itemOptions.length > 0) {
                        Controller.selectedItem = Controller.periodOptions[0].competency_items[0].id;
                    } else {
                        AlertSvc.warning(Controller, "Please setup competency items for the selected period");
                    }
                } else {
                    AlertSvc.warning(Controller, "Please setup competency periods for the selected template");
                }
                return InstitutionStudentCompetenciesSvc.getStudentCompetencyResults(
                    Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
                console.log(error);
            })
            .then(function (competencyResults) {
                Controller.changeCriteria(competencyResults);
                return InstitutionStudentCompetenciesSvc.getStudentCompetencyComments(
                    Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
            })
            .then(function (competencyItemComments) {
                Controller.changeComments(competencyItemComments);
                return Controller.initGrid();
            }, function (error) {
            })
            .finally(function(){
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });
        }

    });

    function resetColumnDefs(criteria, period, selectedPeriodStatus, item) {
        var response = InstitutionStudentCompetenciesSvc.getColumnDefs(criteria, item, Controller.bodyDir);

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
                    var rowData = [];
                    var competencyCriteriaIds = [];
                    angular.forEach(criteria, function (value, key) {
                        if (value.competency_item_id == Controller.selectedItem) {
                            var variableName = 'competency_criteria_id_' + value.id;
                            var newObj = {
                                id: value.id,
                                name: variableName,
                                value: 0
                            };
                            this.push(newObj);
                        }
                    }, competencyCriteriaIds);
                    angular.forEach(Controller.studentList, function(student, key) {
                        var copyCriteriaIds = angular.copy(competencyCriteriaIds);
                        if (angular.isDefined(Controller.studentResults[student.student_id])) {
                            angular.forEach(copyCriteriaIds, function (value, key) {
                                if (angular.isDefined(Controller.studentResults[student.student_id][value.id])) {
                                    copyCriteriaIds[key].value = Controller.studentResults[student.student_id][value.id];
                                }
                            });
                        }
                        var row = {
                            openemis_id: student.user.openemis_no,
                            student_id: student.student_id,
                            name: student.user.name,
                            competency_item_id: item,
                            competency_period_id: period,
                            period_editable: selectedPeriodStatus,
                            student_status_name: student.student_status.name,
                            student_status_code: student.student_status.code,
                            comments: '',
                            save_error: {
                                comments: false
                            }
                        };
                        var studentComments = Controller.studentComments;
                        if (angular.isDefined(studentComments[student.student_id]) && studentComments[student.student_id]['comments'] != null) {
                            row['comments'] = studentComments[student.student_id]['comments'];
                        }

                        angular.forEach(copyCriteriaIds, function(value, key) {
                            row[value.name] = value.value;
                            row['save_error'][value.name] = false;
                        });
                        rowData.push(row);
                    });
                    Controller.gridOptions.api.setRowData(rowData);

                    if (angular.isDefined(item)) {
                        if (response.data.length > 4) {
                            AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                        } else {
                            AlertSvc.warning(Controller, "Please setup competency criterias for the selected item");
                        }
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

    function changeCriteria(competencyResults) {
        var studentResults = {};
        angular.forEach(competencyResults, function (value, key) {
            // Format for the student criteria result will be student_id.criteria_id.grading_option_id
            if (studentResults[value.student_id] == undefined) {
                studentResults[value.student_id] = {}
            }
            studentResults[value.student_id][value.competency_criteria_id] = value.competency_grading_option_id;
        });
        Controller.studentResults = studentResults;
    }

    function changeComments(competencyItemComments) {
        var studentComments = {};
        angular.forEach(competencyItemComments, function (value, key) {
            if (studentComments[value.student_id] == undefined) {
                studentComments[value.student_id] = {}
            }
            studentComments[value.student_id]['comments'] = value.comments;
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
                    Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.institutionId, Controller.academicPeriodId)
        .then(function (results) {
            Controller.changeCriteria(results);
            return InstitutionStudentCompetenciesSvc.getStudentCompetencyComments(
                Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.institutionId, Controller.academicPeriodId);
        }, function (error) {
        })
        .then(function (comments) {
            Controller.changeComments(comments);
            Controller.resetColumnDefs(Controller.criteriaGradeOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem);
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
                    competency_template_id: Controller.competencyTemplateId
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
                suppressCellSelection: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                localeText: localeText,
                onCellValueChanged: function(params) {
                    if (params.newValue != params.oldValue || params.data.save_error[params.colDef.field]) {
                        InstitutionStudentCompetenciesSvc.saveCompetencyComments(params)
                        .then(function(response) {
                            params.data.save_error[params.colDef.field] = false;
                            AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                            params.api.refreshCells([params.node], [params.colDef.field]);

                        }, function(error) {
                            params.data.save_error[params.colDef.field] = true;
                            console.log(error);
                            AlertSvc.error(Controller, "There was an error when saving the comments");
                            params.api.refreshCells([params.node], [params.colDef.field]);
                        });
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.criteriaGradeOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem);
                }
            };
        }, function(error){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institution_id,
                    academic_period_id: Controller.academicPeriodId,
                    competency_template_id: Controller.competencyTemplateId
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                minColWidth: 200,
                enableColResize: false,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressCellSelection: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                onCellValueChanged: function(params) {
                    if (params.newValue != params.oldValue || params.data.save_error[params.colDef.field]) {
                        InstitutionStudentCompetenciesSvc.saveCompetencyComments(params)
                        .then(function(response) {
                            params.data.save_error[params.colDef.field] = false;
                            AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                            params.api.refreshCells([params.node], [params.colDef.field]);

                        }, function(error) {
                            params.data.save_error[params.colDef.field] = true;
                            console.log(error);
                            AlertSvc.error(Controller, "There was an error when saving the comments");
                            params.api.refreshCells([params.node], [params.colDef.field]);
                        });
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.criteriaGradeOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem);
                }
            };
        });
    }
}