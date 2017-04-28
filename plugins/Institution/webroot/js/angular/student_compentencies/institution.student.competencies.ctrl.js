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

    // Function mapping
    Controller.postForm = postForm;
    Controller.updateQueryStringParameter = updateQueryStringParameter;

    angular.element(document).ready(function () {
        InstitutionStudentCompetenciesSvc.init(angular.baseUrl);
        // UtilsSvc.isAppendLoader(true);
        if (Controller.classId != null) {
            InstitutionStudentCompetenciesSvc.getClassDetails(Controller.classId)
            .then(function(response) {
                Controller.className = response.name;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.institutionId = response.institution_id;
                Controller.academicPeriodName = response.academic_period.name;
                var promises = [];
                promises[0] = InstitutionStudentCompetenciesSvc.getCompetencyTemplate(Controller.academicPeriodId, Controller.competencyTemplateId);
            }, function(error) {
                console.log(error);
            })
            .then(function (promises) {
                var studentCompetencyResult = {};
                angular.forEach(promises[0], function(value, key) {
                    var toPush = {
                        openemis_no: value.openemis_no,
                        name: value.name,
                        education_grade_name: value.education_grade_name,
                        student_status_name: value.student_status_name,
                        gender_name: value.gender_name,
                        student_id: value.id,
                        encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify(
                            {
                                student_id: value.id,
                                institution_class_id: value.institution_class_id,
                                education_grade_id: value.education_grade_id,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id
                            }
                        ))
                    };
                    this.push(toPush);
                }, unassignedStudentsArr);
                Controller.unassignedStudents = unassignedStudentsArr;
                Controller.shiftOptions = promises[1];
                Controller.teacherOptions = promises[2];


                var toTranslate = [];
                angular.forEach(Controller.colDef, function(value, key) {
                    this.push(value.headerName);
                }, toTranslate);
                return InstitutionStudentCompetenciesSvc.translate(toTranslate);
            }, function (error) {
                console.log(error);
            })
            // .then(function (translatedText) {
            //     angular.forEach(translatedText, function(value, key) {
            //         Controller.colDef[key]['headerName'] = value;
            //     });
            //     Controller.setTop(Controller.colDef, Controller.unassignedStudents);
            //     Controller.setBottom(Controller.colDef, Controller.assignedStudents);
            // }, function (error) {
            //     console.log(error);
            // })
            .finally(function(){
                Controller.dataReady = true;
                // UtilsSvc.isAppendLoader(false);
            });
        }

    });

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