//Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('institution.associations.ctrl', ['agGrid', 'kd-angular-multi-select', 'utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.associations.svc', 'angular.chosen'])
    .controller('InstitutionAssociationsCtrl', InstitutionAssociationsController);

InstitutionAssociationsController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionAssociationsSvc'];

function InstitutionAssociationsController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionAssociationsSvc) {

    var Controller = this;

    // Constants
    var suppressMenu = true;

    var suppressSorting = true;
    Controller.dataReady = false;

    // Variables
    Controller.bodyDir = getComputedStyle(document.body).direction;
    Controller.columnTopData = [{
        headerName: "",
        field: "checkbox",
        checkboxSelection: true,
        suppressMenu: suppressMenu,
        suppressSorting: suppressSorting,
        minWidth: 50,
        maxWidth: 50,
        pinned: 'left'
    }];
    Controller.rowTopData = [];
    Controller.topKey = 'id';
    Controller.columnBottomData = [{
        headerName: "",
        field: "checkbox",
        checkboxSelection: true,
        suppressMenu: suppressMenu,
        suppressSorting: suppressSorting,
        minWidth: 50,
        maxWidth: 50,
        pinned: 'left'
    }];
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
    Controller.classId = null;
    Controller.colDef = [{
            headerName: 'OpenEMIS ID',
            field: 'openemis_no'
        },
        {
            headerName: 'Name',
            field: 'name'
        },
        {
            headerName: 'Gender',
            field: 'gender_name'
        },
        {
            headerName: 'Education Grade',
            field: 'education_grade_name'
        },
        {
            headerName: 'Student Status',
            field: 'student_status_name'
        }
    ];
    Controller.institutionId = null;
    Controller.academicPeriodId = null;
    Controller.assignedStudents = {};
    Controller.unassignedStudents = {};
    Controller.mainTeacherOptions = [];
    Controller.teacherOptions = [];
    Controller.secondaryTeacherOptions = [];
    Controller.alertUrl = '';
    Controller.redirectUrl = '';
    Controller.selectedTeacher = null;
    Controller.selectedSecondaryTeacher = [];
    Controller.associationName = '';
    Controller.academicPeriodName = '';
    Controller.postError = [];
    Controller.maxStudentsPerClass = null;

    // Function mapping
    Controller.setTop = setTop;
    Controller.setBottom = setBottom;
    Controller.postForm = postForm;
    Controller.updateQueryStringParameter = updateQueryStringParameter;
    Controller.changeStaff = changeStaff;

    angular.element(document).ready(function() {
        InstitutionAssociationsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        //Edit Section
        if (Controller.classId != null || Controller.classId != undefined || Controller.classId == '') {
            InstitutionAssociationsSvc.getAssociationDetails(Controller.classId)
                .then(function(response) {
                    var secondaryTeachers = [];
                    angular.forEach(response.association_staff, function(value, key) {
                        this.push(value.security_user_id);
                    }, secondaryTeachers);
                    Controller.selectedSecondaryTeacher = secondaryTeachers;

                    Controller.associationName = response.name;
                    Controller.academicPeriodId = response.academic_period_id;
                    Controller.institutionId = response.institution_id;
                    Controller.academicPeriodName = response.academic_period.name;

                    var assignedStudents = [];
                    angular.forEach(response.association_student, function(value, key) {
                        var toPush = {
                            openemis_no: value.user.openemis_no,
                            name: value.user.name,
                            education_grade_name: value.education_grade.name,
                            student_status_name: value.student_status.name,
                            gender_name: value.user.gender.name,
                            security_user_id: value.security_user_id,
                            encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify({
                                security_user_id: value.security_user_id,
                                institution_association_id: value.institution_association_id,
                                education_grade_id: value.education_grade_id,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id,
                                gender_id: value.user.gender.id
                            }))
                        };
                        this.push(toPush);
                    }, assignedStudents);
                    Controller.assignedStudents = assignedStudents;

                    var promises = [];
                    promises[0] = InstitutionAssociationsSvc.getUnassignedStudent(Controller.classId, response.institution_id, response.academic_period_id);
                    promises[2] = InstitutionAssociationsSvc.getTeacherOptions(response.institution_id, response.academic_period_id);
                    promises[3] = InstitutionAssociationsSvc.getConfigItemValue('max_students_per_class');
                    return $q.all(promises);
                }, function(error) {
                    console.log(error);
                })
                .then(function(promises) {
                    var unassignedStudentsArr = [];
                    angular.forEach(promises[0], function(value, key) {
                        var toPush = {
                            openemis_no: value.openemis_no,
                            name: value.name,
                            education_grade_name: value.education_grade_name,
                            student_status_name: value.student_status_name,
                            gender_name: value.gender_name,
                            security_user_id: value.security_user_id,
                            encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify({
                                security_user_id: value.security_user_id,
                                institution_association_id: value.institution_association_id,
                                education_grade_id: value.education_grade_id,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id,
                                gender_id: value.gender_id
                            }))
                        };
                        this.push(toPush);
                    }, unassignedStudentsArr);
                    Controller.unassignedStudents = unassignedStudentsArr;
                    Controller.mainTeacherOptions = promises[2];
                    Controller.maxStudentsPerClass = parseInt(promises[3]);

                    Controller.teacherOptions = Controller.changeStaff(Controller.selectedSecondaryTeacher);
                    Controller.secondaryTeacherOptions = Controller.changeStaff(Controller.selectedTeacher);

                    var toTranslate = [];
                    angular.forEach(Controller.colDef, function(value, key) {
                        this.push(value.headerName);
                    }, toTranslate);
                    return InstitutionAssociationsSvc.translate(toTranslate);
                }, function(error) {
                    console.log(error);
                })
                .then(function(translatedText) {
                    angular.forEach(translatedText, function(value, key) {
                        Controller.colDef[key]['headerName'] = value;
                    });
                    Controller.setTop(Controller.colDef, Controller.unassignedStudents);
                    Controller.setBottom(Controller.colDef, Controller.assignedStudents);
                }, function(error) {
                    console.log(error);
                })
                .finally(function() {
                    Controller.dataReady = true;
                    UtilsSvc.isAppendLoader(false);
                });
        }


    });

    function changeStaff(key) {
        var newOptions = [];
        for (var i = 0; i < Controller.mainTeacherOptions.length; i++) {
            if (key instanceof Array) {
                if (!key.includes(Controller.mainTeacherOptions[i].id)) {
                    newOptions.push(Controller.mainTeacherOptions[i]);
                }
            } else {
                if (Controller.mainTeacherOptions[i].id != key) {
                    newOptions.push(Controller.mainTeacherOptions[i]);
                }
            }

        }
        return newOptions;
    }

    function setTop(header, content, key = 'name') {
        for (var i = 0; i < header.length; i++) {
            header[i].suppressMenu = suppressMenu;
            header[i].filter = 'text';
            header[i].width = 200;
            header[i].minWidth = 200;
            Controller.columnTopData.push(header[i]);
        }
        if (Controller.bodyDir != 'ltr') {
            Controller.columnTopData.reverse();
        }
        for (var i = 0; i < content.length; i++) {
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
        for (var i = 0; i < header.length; i++) {
            header[i].suppressMenu = suppressMenu;
            header[i].filter = 'text';
            header[i].width = 200;
            header[i].minWidth = 200;
            Controller.columnBottomData.push(header[i]);
        }
        if (Controller.bodyDir != 'ltr') {
            Controller.columnBottomData.reverse();
        }

        for (var i = 0; i < content.length; i++) {
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
        var associationStudents = [];
        console.log(Controller.gridOptionsBottom.rowData)
        angular.forEach(Controller.gridOptionsBottom.rowData, function(value, key) {
            this.push(value.encodedVar);
        }, associationStudents);
        var postData = {};
        postData.id = Controller.classId;
        postData.name = Controller.associationName;
        postData.associationStudents = associationStudents;
        postData.institution_id = Controller.institutionId;
        postData.academic_period_id = Controller.academicPeriodId;
        postData.association_staff = [];
        angular.forEach(Controller.selectedSecondaryTeacher, function(value, key) {
            console.log(value)
            this.push({
                security_user_id: value,
                institution_association_id: Controller.classId
            });
        }, postData.association_staff);

        InstitutionAssociationsSvc.updateAssociation(postData)
            .then(function(response) {
                var error = response.data.error;
                if (error instanceof Array && error.length == 0) {
                    Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'alertType', 'success');
                    Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'message', 'general.edit.success');
                    $http.get(Controller.alertUrl)
                        .then(function(response) {
                            $window.location.href = Controller.redirectUrl;
                        }, function(error) {
                            console.log(error);
                        });
                } else {
                    AlertSvc.error(Controller, 'The record is not updated due to errors encountered.');
                    angular.forEach(error, function(value, key) {
                        Controller.postError[key] = value;
                    })
                }
            }, function(error) {
                console.log(error);
            });

    }

    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        } else {
            return uri + separator + key + "=" + value;
        }
    }

}