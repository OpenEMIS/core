//Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular
    .module("institutionadd.associations.ctrl", [
        "agGrid",
        "kd-angular-multi-select",
        "utils.svc",
        "alert.svc",
        "aggrid.locale.svc",
        "institutionadd.associations.svc",
        "angular.chosen",
    ])
    .controller(
        "InstitutionAssociationsCtrl",
        InstitutionAssociationsController
    );

InstitutionAssociationsController.$inject = [
    "$scope",
    "$q",
    "$window",
    "$http",
    "UtilsSvc",
    "AlertSvc",
    "AggridLocaleSvc",
    "InstitutionAssociationsSvc",
];

function InstitutionAssociationsController(
    $scope,
    $q,
    $window,
    $http,
    UtilsSvc,
    AlertSvc,
    AggridLocaleSvc,
    InstitutionAssociationsSvc
) {
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
        pinned: "left",
    }, ];
    Controller.rowTopData = [];
    Controller.topKey = "id";
    Controller.columnBottomData = [{
        headerName: "",
        field: "checkbox",
        checkboxSelection: true,
        suppressMenu: suppressMenu,
        suppressSorting: suppressSorting,
        minWidth: 50,
        maxWidth: 50,
        pinned: "left",
    }, ];
    Controller.rowBottomData = [];
    Controller.bottomKey = "id";
    Controller.gridOptionsTop = {
        columnDefs: [],
        rowData: [],
        primaryKey: [],
    };
    Controller.gridOptionsBottom = {
        columnDefs: [],
        rowData: [],
        primaryKey: [],
    };
    Controller.classId = null;
    Controller.colDef = [{
            headerName: "OpenEMIS ID",
            field: "openemis_no",
        },
        {
            headerName: "Name",
            field: "name",
        },
        {
            headerName: "Gender",
            field: "gender_name",
        },
        {
            headerName: "Education Grade",
            field: "education_grade_name",
        },
        {
            headerName: "Student Status",
            field: "student_status_name",
        },
    ];
    Controller.academicPeriodOptions = {};
    Controller.institutionId = null;
    Controller.academicPeriodId = (Controller.academicPeriodOptions.hasOwnProperty('selectedOption')) ? Controller.academicPeriodOptions.selectedOption.id : 30
    Controller.assignedStudents = {};
    Controller.unassignedStudents = {};
    Controller.mainTeacherOptions = [];
    Controller.teacherOptions = [];
    Controller.secondaryTeacherOptions = [];
    Controller.alertUrl = "";
    Controller.redirectUrl = "";
    Controller.selectedTeacher = null;
    Controller.selectedSecondaryTeacher = [];
    Controller.associationName = "";
    Controller.academicPeriodName = "";
    Controller.postError = [];
    Controller.maxStudentsPerClass = null;

    Controller.onChangeAcademicPeriod = onChangeAcademicPeriod;

    // Function mapping
    Controller.setTop = setTop;
    Controller.setBottom = setBottom;
    Controller.postForm = postForm;
    Controller.updateQueryStringParameter = updateQueryStringParameter;
    Controller.changeStaff = changeStaff;

    angular.element(document).ready(function() {
        console.log(Controller.academicPeriodOptions.selectedOption)
        InstitutionAssociationsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        ///if (Controller.classId == '' && Controller.classId == undefined) {
        InstitutionAssociationsSvc.getAcademicPeriodOptions(
                Controller.institutionId
            )
            .then(
                function(periods) {
                    console.log(periods);
                    // Acadmic Periods Option
                    var selectedPeriod = [];
                    var secondaryTeachers = [];
                    angular.forEach(
                        periods,
                        function(value) {
                            if (value.current == 1) {
                                this.push(value);
                            }
                        },
                        selectedPeriod
                    );
                    if (selectedPeriod.length == 0) {
                        selectedPeriod = periods;
                    }
                    Controller.academicPeriodOptions = {
                        availableOptions: periods,
                        selectedOption: selectedPeriod[0],
                    };

                    Controller.selectedSecondaryTeacher = secondaryTeachers;
                    // assigned Students
                    var assignedStudents = [];
                    angular.forEach(
                        [],
                        function(value, key) {
                            var toPush = {
                                openemis_no: value.user.openemis_no,
                                name: value.user.name,
                                education_grade_name: value.education_grade.name,
                                student_status_name: value.student_status.name,
                                gender_name: value.user.gender.name,
                                security_user_id: value.security_user_id,
                                encodedVar: UtilsSvc.urlsafeBase64Encode(
                                    JSON.stringify({
                                        security_user_id: value.security_user_id,
                                        education_grade_id: value.education_grade_id,
                                        academic_period_id: value.academic_period_id,
                                        student_status_id: value.student_status_id,
                                        gender_id: value.user.gender.id,
                                    })
                                ),
                            };
                            this.push(toPush);
                        },
                        assignedStudents
                    );
                    Controller.assignedStudents = assignedStudents;
                    var promises = [];
                    promises[0] = InstitutionAssociationsSvc.getTeacherOptions(
                        Controller.institutionId,
                        Controller.academicPeriodId
                    );
                    promises[1] = InstitutionAssociationsSvc.getUnassignedStudent(
                        Controller.institutionId,
                        Controller.academicPeriodId
                    );
                    return $q.all(promises);
                },
                function(error) {
                    console.log(error);
                }
            )
            .then(
                function(promises) {
                    // Unassigned Students
                    var unassignedStudentsArr = [];
                    angular.forEach(
                        promises[1],
                        function(value, key) {
                            console.log(value);
                            var toPush = {
                                openemis_no: value.openemis_no,
                                name: value.name,
                                education_grade_name: value.education_grade_name,
                                student_status_name: value.student_status_name,
                                gender_name: value.gender_name,
                                security_user_id: value.security_user_id,
                                encodedVar: UtilsSvc.urlsafeBase64Encode(
                                    JSON.stringify({
                                        security_user_id: value.security_user_id,
                                        education_grade_id: value.education_grade_id,
                                        academic_period_id: value.academic_period_id,
                                        student_status_id: value.student_status_id,
                                        gender_id: value.gender_id,
                                    })
                                ),
                            };
                            this.push(toPush);
                        },
                        unassignedStudentsArr
                    );
                    Controller.unassignedStudents = unassignedStudentsArr;
                    Controller.mainTeacherOptions = promises[0];
                    Controller.teacherOptions = Controller.changeStaff(
                        Controller.selectedSecondaryTeacher
                    );
                    Controller.secondaryTeacherOptions = Controller.changeStaff(
                        Controller.selectedTeacher
                    );
                    console.log(Controller.mainTeacherOptions);

                    var toTranslate = [];
                    angular.forEach(
                        Controller.colDef,
                        function(value, key) {
                            this.push(value.headerName);
                        },
                        toTranslate
                    );
                    return InstitutionAssociationsSvc.translate(toTranslate);
                },
                function(error) {
                    console.log(error);
                }
            )
            .then(
                function(translatedText) {
                    angular.forEach(translatedText, function(value, key) {
                        Controller.colDef[key]["headerName"] = value;
                    });
                    Controller.setTop(
                        Controller.colDef,
                        Controller.unassignedStudents
                    );
                    Controller.setBottom(
                        Controller.colDef,
                        Controller.assignedStudents
                    );
                },
                function(error) {
                    console.log(error);
                }
            )
            .finally(function() {
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });
        //}
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

    function setTop(header, content, key = "name") {
        for (var i = 0; i < header.length; i++) {
            header[i].suppressMenu = suppressMenu;
            header[i].filter = "text";
            header[i].width = 200;
            header[i].minWidth = 200;
            Controller.columnTopData.push(header[i]);
        }
        if (Controller.bodyDir != "ltr") {
            Controller.columnTopData.reverse();
        }
        for (var i = 0; i < content.length; i++) {
            if (content[i].checkbox == undefined) {
                content[i].checkbox = "";
            }
        }
        Controller.rowTopData = content;
        Controller.topKey = key;
        Controller.gridOptionsTop.columnDefs = Controller.columnTopData;
        Controller.gridOptionsTop.rowData = Controller.rowTopData;
        Controller.gridOptionsTop.primaryKey = Controller.topKey;
    }

    function setBottom(header, content, key = "name") {
        for (var i = 0; i < header.length; i++) {
            header[i].suppressMenu = suppressMenu;
            header[i].filter = "text";
            header[i].width = 200;
            header[i].minWidth = 200;
            Controller.columnBottomData.push(header[i]);
        }
        if (Controller.bodyDir != "ltr") {
            Controller.columnBottomData.reverse();
        }

        for (var i = 0; i < content.length; i++) {
            if (content[i].checkbox == undefined) {
                content[i].checkbox = "";
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
        angular.forEach(
            Controller.gridOptionsBottom.rowData,
            function(value, key) {
                this.push(value.encodedVar);
            },
            associationStudents
        );
        var postData = {};
        postData.name = Controller.associationName;
        postData.associationStudents = associationStudents;
        // postData.institution_id = postData.institution_id;
        // postData.academic_period_id = postData.academic_period_id;
        postData.institution_id = Controller.institutionId;
        postData.academic_period_id = (Controller.academicPeriodOptions.hasOwnProperty('selectedOption')) ? Controller.academicPeriodOptions.selectedOption.id : '';;
        postData.association_staff = [];
        angular.forEach(
            Controller.selectedSecondaryTeacher,
            function(value, key) {
                this.push({
                    security_user_id: value,
                    institution_association_id: Controller.classId,
                });
            },
            postData.association_staff
        );

        InstitutionAssociationsSvc.saveAssociation(postData).then(
            function(response) {
                var error = response.data.error;
                console.log(error);
                if (error instanceof Array && error.length == 0) {
                    Controller.alertUrl = Controller.updateQueryStringParameter(
                        Controller.alertUrl,
                        "alertType",
                        "success"
                    );
                    Controller.alertUrl = Controller.updateQueryStringParameter(
                        Controller.alertUrl,
                        "message",
                        "general.add.success"
                    );
                    //Controller.redirectUrl = Controller.updateQueryStringParameter(Controller.redirectUrl, 'module', Controller.moduleKey);
                    $http.get(Controller.alertUrl).then(
                        function(response) {
                            $window.location.href =
                                "index?association_added=true";
                        },
                        function(error) {
                            console.log(error);
                        }
                    );
                } else {
                    AlertSvc.error(
                        Controller,
                        "The record is not updated due to errors encountered."
                    );
                    angular.forEach(error, function(value, key) {
                        Controller.postError[key] = value;
                    });
                }
            },
            function(error) {
                console.log(error);
            }
        );
    }

    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf("?") !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, "$1" + key + "=" + value + "$2");
        } else {
            return uri + separator + key + "=" + value;
        }
    }

    function onChangeAcademicPeriod() {
        (Controller.academicPeriodOptions.hasOwnProperty('selectedOption')) ? Controller.academicPeriodOptions.selectedOption.id: ''
        Controller.academicPeriodId = Controller.academicPeriodOptions.selectedOption.id;
        // console.log(Controller.academicPeriodOptions.selectedOption.id);
        // AlertSvc.reset($scope);
    }
}