//Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('institution.class.students.ctrl', ['agGrid', 'kd-angular-multi-select', 'utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.class.students.svc'])
    .controller('InstitutionClassStudentsCtrl', InstitutionClassStudentsController);

SgMultiSelectCtrl.$inject = ['$scope', '$q', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionClassStudentsSvc'];

function InstitutionClassStudentsController($scope, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionClassStudentsSvc) {

    var Controller = this;

    // Constants
    var suppressMenu = true;
    var suppressSorting = true;

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
    Controller.classId = null;
    Controller.assignedStudents = {};

    // Function mapping
    Controller.setTop = setTop;
    Controller.setBottom = setBottom;

    angular.element(document).ready(function () {
        InstitutionClassStudentsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        if (Controller.classId != null) {
            InstitutionClassStudentsSvc.getClassDetails(Controller.classId)
            .then(function(response) {
                var assignedStudents = [];
                angular.forEach(response.class_students, function(value, key) {
                    // 'name_with_id' => $result->name_with_id,
                    // 'id' => $result->id,
                    // 'education_grade_id' => $result->education_grade_id,
                    // 'education_grade_name' => __($result->education_grade_name),
                    // 'student_status_id' => $result->student_status_id,
                    // 'student_status_name' => __($result->student_status_name),
                    // 'academic_period_id' => $result->academic_period_id,
                    // 'gender_name' => __($result->gender_name),
                    // 'institution_id' => $institutionId,
                    // 'institution_class_id' => $institutionClassId
                    var toPush = {
                        name_with_id: value.user.name_with_id,
                        education_grade_name: value.education_grade.name,
                        student_status_name: value.student_status.name,
                        gender_name: value.user.gender.name,
                        encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify(
                            {
                                student_id: value.student_id,
                                institution_class_id: value.institution_class_id,
                                education_grade_id: value.education_grade_id,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id
                            }
                        ))
                    };
                    this.push(toPush);
                }, assignedStudents);
                Controller.assignedStudents = assignedStudents;

                return InstitutionClassStudentsSvc.getUnassignedStudent(Controller.classId);
            }, function(error) {
                console.log(error);
            })
            .then(function (unassignedStudents) {
                console.log(unassignedStudents);
            }, function (error) {

            });
        }


        UtilsSvc.isAppendLoader(false);
    });

    function setTop(header, content, key = 'id') {
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

    function setBottom(header, content, key = 'id') {
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

        Controller.gridOptionsTop.columnDefs = Controller.columnBottomData;
        Controller.gridOptionsTop.rowData = Controller.rowBottomData;
        Controller.gridOptionsTop.primaryKey = Controller.bottomKey;
    }
}
