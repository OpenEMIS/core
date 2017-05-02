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

    // Function mapping
    Controller.postForm = postForm;
    Controller.updateQueryStringParameter = updateQueryStringParameter;
    Controller.initGrid = initGrid;

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
                var promises = [];
                return InstitutionStudentCompetenciesSvc.getCompetencyTemplate(Controller.academicPeriodId, Controller.competencyTemplateId);
            }, function(error) {
                console.log(error);
            })
            .then(function (competencyTemplate) {
                Controller.competencyTemplateId = competencyTemplate.id;
                Controller.competencyTemplateName = competencyTemplate.name;
                Controller.criteriaOptions = competencyTemplate.criterias;
                Controller.itemOptions = competencyTemplate.items;
                Controller.periodOptions = competencyTemplate.periods;
                if (Controller.periodOptions.length > 0) {
                    Controller.selectedPeriod = Controller.periodOptions[0].id;
                }
                if (Controller.itemOptions.length > 0) {
                    Controller.selectedItem = Controller.itemOptions[0].id;
                }

            }, function (error) {
                console.log(error);
            })
            .then(function (competencyResults) {

            }, function (error) {

            })
            .finally(function(){
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });
        }

    });

    function intGrid() {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            Controller.gridOptions = {
                context: {
                    institution_id: $scope.institution_id,
                    class_id: $scope.class_id,
                    assessment_id: $scope.assessment_id,
                    academic_period_id: $scope.academic_period_id,
                    education_grade_id: $scope.education_grade_id,
                    education_subject_id: 0
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
                localeText: localeText,
                onCellValueChanged: function(params) {
                    if (params.newValue != params.oldValue) {
                        var index = params.colDef.field.replace(/period_(\d+)/, '$1');

                        if (angular.isUndefined($scope.results[params.data.student_id])) {
                            $scope.results[params.data.student_id] = {};
                        }

                        if (angular.isUndefined($scope.results[params.data.student_id][index])) {
                            $scope.results[params.data.student_id][index] = {marks: ''};
                        }

                        $scope.results[params.data.student_id][index]['marks'] = params.newValue;

                        params.data.total_mark = InstitutionsResultsSvc.calculateTotal(params.data);
                        // marked as dirty
                        params.data.is_dirty = true;

                        var subject = $scope.subject;
                        var gradingTypes = $scope.gradingTypes;
                        var extra = {
                            subject: subject,
                            gradingTypes: gradingTypes
                        };
                        InstitutionsResultsSvc.saveSingleRecordData(params, extra)
                        .then(function(response) {
                        }, function(error) {
                            console.log(error);
                        });
                        // Important: to refresh the grid after data is modified
                        $scope.gridOptions.api.refreshView();
                    }
                },
                onGridReady: function() {
                    $scope.onChangeSubject(subject);
                }
            };
        }, function(error){
            Controller.gridOptions = {
                context: {
                    institution_id: $scope.institution_id,
                    class_id: $scope.class_id,
                    assessment_id: $scope.assessment_id,
                    academic_period_id: $scope.academic_period_id,
                    education_grade_id: $scope.education_grade_id,
                    education_subject_id: 0
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
                    if (params.newValue != params.oldValue) {
                        var index = params.colDef.field.replace(/period_(\d+)/, '$1');

                        if (angular.isUndefined($scope.results[params.data.student_id])) {
                            $scope.results[params.data.student_id] = {};
                        }

                        if (angular.isUndefined($scope.results[params.data.student_id][index])) {
                            $scope.results[params.data.student_id][index] = {marks: ''};
                        }

                        $scope.results[params.data.student_id][index]['marks'] = params.newValue;

                        params.data.total_mark = InstitutionsResultsSvc.calculateTotal(params.data);
                        // marked as dirty
                        params.data.is_dirty = true;

                        var subject = $scope.subject;
                        var gradingTypes = $scope.gradingTypes;
                        var extra = {
                            subject: subject,
                            gradingTypes: gradingTypes
                        };
                        InstitutionsResultsSvc.saveSingleRecordData(params, extra)
                        .then(function(response) {
                        }, function(error) {
                            console.log(error);
                        });
                        // Important: to refresh the grid after data is modified
                        $scope.gridOptions.api.refreshView();
                    }
                },
                onGridReady: function() {
                    $scope.onChangeSubject(subject);
                }
            };
        });
    }

    function setTop(header, content, key = 'name') {
        for(var i = 0; i < header.length; i++) {
            header[i].suppressMenu = suppressMenu;
            header[i].filter = 'text';
            header[i].width = 200;
            header[i].minWidth = 200;
            Controller.columnTopData.push(header[i]);
        }
        if (Controller.bodyDir != 'ltr') {
            Controller.columnTopData.reverse();
        }
        for(var i = 0; i < content.length; i++) {
            if (content[i].checkbox == undefined) {
                content[i].checkbox = '';
            }
        }
        Controller.rowTopData = content;
        Controller.topKey = key;
        Controller.gridOptionsTop.columnDefs = Controller.columnTopData;
        Controller.gridOptionsTop.rowData = Controller.rowTopData;
        Controller.gridOptionsTop.primaryKey = Controller.topKey;
    }

    function setBottom(header, content, key = 'name') {
        for(var i = 0; i < header.length; i++) {
            header[i].suppressMenu = suppressMenu;
            header[i].filter = 'text';
            header[i].width = 200;
            header[i].minWidth = 200;
            Controller.columnBottomData.push(header[i]);
        }
        if (Controller.bodyDir != 'ltr') {
            Controller.columnBottomData.reverse();
        }

        for(var i = 0; i < content.length; i++) {
            if (content[i].checkbox == undefined) {
                content[i].checkbox = '';
            }
        }
        Controller.rowBottomData = content;
        Controller.bottomKey = key;
        Controller.gridOptionsBottom.columnDefs = Controller.columnBottomData;
        Controller.gridOptionsBottom.rowData = Controller.rowBottomData;
        Controller.gridOptionsBottom.primaryKey = Controller.bottomKey;
    }

    function postForm() {
        Controller.postError = [];
        var classStudents = [];
        angular.forEach(Controller.gridOptionsBottom.rowData, function (value, key) {
            this.push(value.encodedVar);
        }, classStudents);
        var postData = {};
        postData.id = Controller.classId;
        postData.name = Controller.className;
        postData.staff_id = Controller.selectedTeacher;
        postData.institution_shift_id = Controller.selectedShift;
        postData.classStudents = classStudents;
        postData.institution_id = Controller.institutionId;
        postData.academic_period_id = Controller.academicPeriodId;
        postData.subjects = UtilsSvc.urlsafeBase64Encode(JSON.stringify(Controller.institutionSubjects));
        InstitutionStudentCompetenciesSvc.saveClass(postData)
        .then(function(response) {
            var error = response.data.error;
            if (error instanceof Array && error.length == 0) {
                Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'alertType', 'success');
                Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'message', 'general.edit.success');
                $http.get(Controller.alertUrl)
                .then(function(response) {
                    $window.location.href = Controller.redirectUrl;
                }, function (error) {
                    console.log(error);
                });
            } else {
                AlertSvc.error(Controller, 'The record is not updated due to errors encountered.');
                angular.forEach(error, function(value, key) {
                    Controller.postError[key] = value;
                })
            }
        }, function(error){
            console.log(error);
        });

    }

    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return uri + separator + key + "=" + value;
        }
    }
}