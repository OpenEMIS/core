//Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('institution.subject.students.ctrl', ['agGrid', 'kd-angular-multi-select', 'utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.subject.students.svc', 'angular.chosen'])
    .controller('InstitutionSubjectStudentsCtrl', InstitutionSubjectStudentsController);

InstitutionSubjectStudentsController.$inject = ['$scope', '$q', '$window', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionSubjectStudentsSvc'];

function InstitutionSubjectStudentsController($scope, $q, $window, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionSubjectStudentsSvc) {

    var Controller = this;

    // Constants
    var suppressMenu = true;
    var suppressSorting = true;
    Controller.dataReady = false;

    // Variables
    Controller.bodyDir = getComputedStyle(document.body).direction;
    Controller.columnTopData = [
        { headerName: "", field: "checkbox", checkboxSelection: true, suppressMenu: suppressMenu, suppressSorting: suppressSorting, minWidth: 50, maxWidth: 50, pinned: 'left' }
    ];
    Controller.rowTopData = [];
    Controller.topKey = 'id';
    Controller.columnBottomData = [
        { headerName: "", field: "checkbox", checkboxSelection: true, suppressMenu: suppressMenu, suppressSorting: suppressSorting, minWidth: 50, maxWidth: 50, pinned: 'left' }
    ];
    Controller.rowBottomData = [];
    Controller.bottomKey = 'id';
    Controller.gridOptionsTop = {
        columnDefs: [],
        rowData: [],
        primaryKey: []
    };
    Controller.gridOptionsBottom = {
        columnDefs: [],
        rowData: [],
        primaryKey: []
    };
    Controller.institutionSubjectId = null;
    Controller.colDef = [
        {headerName: 'OpenEMIS ID', field: 'openemis_no'},
        {headerName: 'Name', field: 'name'},
        {headerName: 'Gender', field: 'gender_name'},
        {headerName: 'Education Grade', field: 'education_grade_name'},
        {headerName: 'Student Status', field: 'student_status_name'}
    ];
    Controller.assignedStudents = {};
    Controller.unassignedStudents = {};
    Controller.educationSubjectName = '';
    Controller.teacherOptions = [];
    Controller.redirectUrl = '';
    Controller.selectedShift = null;
    Controller.selectedTeacher = null;
    Controller.className = '';
    Controller.academicPeriodName = '';
    Controller.institutionSubjects = [];
    Controller.postError = [];
    Controller.pastTeachers = [];

    // Function mapping
    Controller.setTop = setTop;
    Controller.setBottom = setBottom;
    Controller.postForm = postForm;
    Controller.updateQueryStringParameter = updateQueryStringParameter;

    angular.element(document).ready(function () {
        InstitutionSubjectStudentsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        if (Controller.institutionSubjectId != null) {
            InstitutionSubjectStudentsSvc.getInstitutionSubjectDetails(Controller.institutionSubjectId)
            .then(function(response) {
                console.log(response);
                Controller.institutionSubjectName = response.name;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.institutionId = response.institution_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.educationSubjectName = response.education_subject.name;
                Controller.educationSubjectId = response.education_subject_id;
                Controller.institutionClassId = response.class_subjects[0].institution_class_id;
                var teachers = [];
                angular.forEach(response.teachers, function(value, key) {
                    this.push(value.id.toString());
                }, teachers);
                Controller.teachers = teachers;

                var rooms = [];
                angular.forEach(response.rooms, function(value, key) {
                    this.push(value.id.toString());
                }, rooms);
                Controller.rooms = rooms;

                var assignedStudents = [];
                angular.forEach(response.subject_students, function(value, key) {
                    var toPush = {
                        openemis_no: value.user.openemis_no,
                        name: value.user.name,
                        student_status_name: value.student_status.name,
                        gender_name: value.user.gender.name,
                        student_id: value.student_id,
                        encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify(
                            {
                                student_id: value.student_id,
                                institution_class_id: value.institution_class_id,
                                institution_subject_id: value.institution_subject_id,
                                education_grade_id: value.education_grade_id,
                                education_subject_id: value.education_subject_id,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id
                            }
                        ))
                    };
                    this.push(toPush);
                }, assignedStudents);
                Controller.assignedStudents = assignedStudents;

                var promises = [];
                // promises[0] = InstitutionSubjectStudentsSvc.getUnassignedStudent(Controller.institutionSubjectId);
                promises[1] = InstitutionSubjectStudentsSvc.getTeacherOptions(response.institution_id, response.academic_period_id);
                return $q.all(promises);
            }, function(error) {
                console.log(error);
            })
            .then(function (promises) {
                var unassignedStudentsArr = [];
                // angular.forEach(promises[0], function(value, key) {
                //     var toPush = {
                //         openemis_no: value.openemis_no,
                //         name: value.name,
                //         education_grade_name: value.education_grade_name,
                //         student_status_name: value.student_status_name,
                //         gender_name: value.gender_name,
                //         student_id: value.id,
                //         encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify(
                //             {
                //                 student_id: value.id,
                //                 institution_class_id: value.institution_class_id,
                //                 education_grade_id: value.education_grade_id,
                //                 academic_period_id: value.academic_period_id,
                //                 institution_id: value.institution_id,
                //                 student_status_id: value.student_status_id
                //             }
                //         ))
                //     };
                //     this.push(toPush);
                // }, unassignedStudentsArr);
                // Controller.unassignedStudents = unassignedStudentsArr;
                Controller.teacherOptions = promises[1];

                var toTranslate = [];
                angular.forEach(Controller.colDef, function(value, key) {
                    this.push(value.headerName);
                }, toTranslate);
                return InstitutionSubjectStudentsSvc.translate(toTranslate);
            }, function (error) {
                console.log(error);
            })
            .then(function (translatedText) {
                angular.forEach(translatedText, function(value, key) {
                    Controller.colDef[key]['headerName'] = value;
                });
                Controller.setTop(Controller.colDef, Controller.unassignedStudents);
                Controller.setBottom(Controller.colDef, Controller.assignedStudents);
            }, function (error) {
                console.log(error);
            })
            .finally(function(){
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
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
        postData.id = Controller.institutionSubjectId;
        postData.name = Controller.className;
        postData.staff_id = Controller.selectedTeacher;
        postData.institution_shift_id = Controller.selectedShift;
        postData.classStudents = classStudents;
        postData.institution_id = Controller.institutionId;
        postData.academic_period_id = Controller.academicPeriodId;
        postData.subjects = UtilsSvc.urlsafeBase64Encode(JSON.stringify(Controller.institutionSubjects));
        InstitutionSubjectStudentsSvc.saveClass(postData)
        .then(function(response) {
            var error = response.data.error;
            if (error instanceof Array && error.length == 0) {
                Controller.redirectUrl = Controller.updateQueryStringParameter(Controller.redirectUrl, 'alertType', 'success');
                Controller.redirectUrl = Controller.updateQueryStringParameter(Controller.redirectUrl, 'message', 'general.edit.success');
                $window.location.href = Controller.redirectUrl;
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
