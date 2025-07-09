//Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('institution.departments.ctrl', ['agGrid', 'kd-angular-multi-select', 'utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.departments.svc', 'angular.chosen'])
    .controller('InstitutionDepartmentsCtrl', InstitutionDepartmentsController);

InstitutionDepartmentsController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionDepartmentsSvc'];

function InstitutionDepartmentsController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionDepartmentsSvc) {

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
            headerName: 'Staff Status',
            field: 'staff_status_name'
        }
    ];
    Controller.institutionId = null;
    Controller.assignedStaff = {};
    Controller.unassignedStaff = {};
    Controller.teacherOptions = [];
    Controller.alertUrl = '';
    Controller.redirectUrl = '';
    Controller.selectedTeacher = null;
    Controller.departmentName = '';
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
        InstitutionDepartmentsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        //Edit Section
        if (Controller.classId != null || Controller.classId != undefined || Controller.classId == '') {
            InstitutionDepartmentsSvc.getDepartmentDetails(Controller.classId)
                .then(function(response) {
                    var secondaryTeachers = [];
                    angular.forEach(response.department_staff, function(value, key) {
                        this.push(value.security_user_id);
                    }, secondaryTeachers);
                    Controller.selectedSecondaryTeacher = secondaryTeachers;

                    Controller.departmentName = response.name;
                    Controller.academicPeriodId = response.academic_period_id;
                    Controller.institutionId = response.institution_id;
                    Controller.academicPeriodName = response.academic_period.name;

                    var assignedStaff = [];
                    angular.forEach(response.department_student, function(value, key) {
                        var toPush = {
                            openemis_no: value.user.openemis_no,
                            name: value.user.name,
                            education_grade_name: value.education_grade.name,
                            student_status_name: value.student_status.name,
                            gender_name: value.user.gender.name,
                            security_user_id: value.security_user_id,
                            encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify({
                                security_user_id: value.security_user_id,
                                institution_department_id: value.institution_department_id,
                                education_grade_id: value.education_grade_id,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id,
                                gender_id: value.user.gender.id
                            }))
                        };
                        this.push(toPush);
                    }, assignedStaff);
                    Controller.assignedStaff = assignedStaff;

                    var promises = [];
                    promises[0] = InstitutionDepartmentsSvc.getUnassignedStudent(Controller.classId, response.institution_id, response.academic_period_id);
                    promises[2] = InstitutionDepartmentsSvc.getTeacherOptions(response.institution_id, response.academic_period_id);
                    promises[3] = InstitutionDepartmentsSvc.getConfigItemValue('max_students_per_class');
                    return $q.all(promises);
                }, function(error) {
                    console.log(error);
                })
                .then(function(promises) {
                    var unassignedStaffArr = [];
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
                                institution_department_id: value.institution_department_id,
                                education_grade_id: value.education_grade_id,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id,
                                gender_id: value.gender_id
                            }))
                        };
                        this.push(toPush);
                    }, unassignedStaffArr);
                    Controller.unassignedStaff = unassignedStaffArr;
                    Controller.mainTeacherOptions = promises[2];
                    Controller.maxStudentsPerClass = parseInt(promises[3]);

                    Controller.teacherOptions = Controller.changeStaff(Controller.selectedSecondaryTeacher);
                    Controller.secondaryTeacherOptions = Controller.changeStaff(Controller.selectedTeacher);

                    var toTranslate = [];
                    angular.forEach(Controller.colDef, function(value, key) {
                        this.push(value.headerName);
                    }, toTranslate);
                    return InstitutionDepartmentsSvc.translate(toTranslate);
                }, function(error) {
                    console.log(error);
                })
                .then(function(translatedText) {
                    angular.forEach(translatedText, function(value, key) {
                        Controller.colDef[key]['headerName'] = value;
                    });
                    Controller.setTop(Controller.colDef, Controller.unassignedStaff);
                    Controller.setBottom(Controller.colDef, Controller.assignedStaff);
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
        var departmentStudents = [];
        console.log(Controller.gridOptionsBottom.rowData)
        angular.forEach(Controller.gridOptionsBottom.rowData, function(value, key) {
            this.push(value.encodedVar);
        }, departmentStudents);
        var postData = {};
        postData.id = Controller.classId;
        postData.name = Controller.departmentName;
        if(postData.name === ''){ // POCOR-7994
            AlertSvc.error(
                Controller,
                'The record is not saved due to errors encountered.'
            );
            Controller.postError.name = ['Name Is Required'];
            return;
        }
        postData.departmentStudents = departmentStudents;
        postData.institution_id = Controller.institutionId;
        postData.academic_period_id = Controller.academicPeriodId;
        postData.department_staff = [];
        angular.forEach(Controller.selectedSecondaryTeacher, function(value, key) {
            console.log(value)
            this.push({
                security_user_id: value,
                institution_department_id: Controller.classId
            });
        }, postData.department_staff);

        InstitutionDepartmentsSvc.updateDepartment(postData)
            .then(function(response) {
                var error = response.data.error;
                if (error instanceof Array && error.length == 0) {
                    Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'alertType', 'success');
                    Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'message', 'general.edit.success');
                    var queryString1 = localStorage.getItem('queryString1');
                    var queryString2 = localStorage.getItem('queryString2');
                    $http.get(Controller.alertUrl)
                        .then(function(response) {
                            //$window.location.href = Controller.redirectUrl;
                            var successUrl = angular.baseUrl + '/Institution/Institutions/Departments/view/' + queryString1 + '/' + queryString2;
                            $window.location.href = successUrl;
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
