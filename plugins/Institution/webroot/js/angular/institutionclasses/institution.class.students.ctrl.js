//Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('institution.class.students.ctrl', ['agGrid', 'kd-angular-multi-select', 'utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.class.students.svc', 'angular.chosen'])
    .controller('InstitutionClassStudentsCtrl', InstitutionClassStudentsController);

InstitutionClassStudentsController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionClassStudentsSvc'];

function InstitutionClassStudentsController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionClassStudentsSvc) {

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
    Controller.classId = null;
    Controller.colDef = [
        {headerName: 'OpenEMIS ID', field: 'openemis_no'},
        {headerName: 'Name', field: 'name'},
        {headerName: 'Gender', field: 'gender_name'},
        {headerName: 'Education Grade', field: 'education_grade_name'},
        {headerName: 'Student Status', field: 'student_status_name'},
        {headerName: 'Special Needs', field: 'special_needs'}
    ];
    Controller.assignedStudents = {};
    Controller.unassignedStudents = {};
    Controller.shiftOptions = [];
    Controller.mainTeacherOptions = [];
    Controller.teacherOptions = [];
    Controller.secondaryTeacherOptions = [];
    Controller.alertUrl = '';
    Controller.redirectUrl = '';
    Controller.selectedShift = null;
    Controller.selectedTeacher = null;
    Controller.selectedSecondaryTeacher = [];
    Controller.className = '';
    Controller.academicPeriodName = '';
    Controller.postError = [];
    Controller.maxStudentsPerClass = null;
    Controller.classCapacity = null;

    // Function mapping
    Controller.setTop = setTop;
    Controller.setBottom = setBottom;
    Controller.postForm = postForm;
    Controller.updateQueryStringParameter = updateQueryStringParameter;
    Controller.changeStaff = changeStaff;

    angular.element(document).ready(function () {
        InstitutionClassStudentsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        if (Controller.classId != null) {
            InstitutionClassStudentsSvc.getClassDetails(Controller.classId)
            .then(function(response) {
                Controller.selectedTeacher = response.staff_id;

                var secondaryTeachers = [];
                angular.forEach(response.classes_secondary_staff, function(value, key) {
                    this.push(value.secondary_staff_id);
                }, secondaryTeachers);
                Controller.selectedSecondaryTeacher = secondaryTeachers;

                Controller.selectedShift = response.institution_shift_id;
                Controller.className = response.name;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.institutionId = response.institution_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.classCapacity = response.capacity;

                var assignedStudents = [];
                angular.forEach(response.class_students, function(value, key) {
                    var toPush = {
                        openemis_no: value.user.openemis_no,
                        name: value.user.name,
                        education_grade_name: value.education_grade.name,
                        student_status_name: value.student_status.name,
                        gender_name: value.user.gender.name,
                        student_id: value.student_id,
                        special_needs: (value.user.has_special_needs) ? "<i class='fa fa-check'></i>" : "<i class='fa fa-times'></i>",
                        encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify(
                            {
                                student_id: value.student_id,
                                institution_class_id: value.institution_class_id,
                                education_grade_id: value.education_grade_id,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id,
                                gender_id: value.user.gender.id
                            }
                        ))
                    };
                    this.push(toPush);
                }, assignedStudents);
                Controller.assignedStudents = assignedStudents;

                var promises = [];
                promises[0] = InstitutionClassStudentsSvc.getUnassignedStudent(Controller.classId);
                promises[1] = InstitutionClassStudentsSvc.getInstitutionShifts(response.institution_id, response.academic_period_id);
                promises[2] = InstitutionClassStudentsSvc.getTeacherOptions(response.institution_id, response.academic_period_id);
                promises[3] = InstitutionClassStudentsSvc.getConfigItemValue('max_students_per_class');
                return $q.all(promises);
            }, function(error) {
                console.log(error);
            })
            .then(function (promises) {
                var unassignedStudentsArr = [];
                angular.forEach(promises[0], function(value, key) {
                    var toPush = {
                        openemis_no: value.openemis_no,
                        name: value.name,
                        education_grade_name: value.education_grade_name,
                        student_status_name: value.student_status_name,
                        gender_name: value.gender_name,
                        student_id: value.id,
                        special_needs: (value.has_special_needs) ? "<i class='fa fa-check'></i>" : "<i class='fa fa-times'></i>",
                        encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify(
                            {
                                student_id: value.id,
                                institution_class_id: value.institution_class_id,
                                education_grade_id: value.education_grade_id,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id,
                                gender_id: value.gender_id
                            }
                        ))
                    };
                    this.push(toPush);
                }, unassignedStudentsArr);
                Controller.unassignedStudents = unassignedStudentsArr;
                Controller.shiftOptions = promises[1];
                Controller.mainTeacherOptions = promises[2];
                Controller.maxStudentsPerClass = parseInt(promises[3]);
  
                Controller.teacherOptions = Controller.changeStaff(Controller.selectedSecondaryTeacher);
                Controller.secondaryTeacherOptions = Controller.changeStaff(Controller.selectedTeacher);

                var toTranslate = [];
                angular.forEach(Controller.colDef, function(value, key) {
                    this.push(value.headerName);
                }, toTranslate);
                return InstitutionClassStudentsSvc.translate(toTranslate);
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
        postData.capacity = parseInt(Controller.classCapacity);

        // postData.secondary_staff_id = Controller.selectedSecondaryTeacher;
        postData.classes_secondary_staff = [];
        angular.forEach(Controller.selectedSecondaryTeacher, function(value, key) {
            this.push({
                secondary_staff_id: value,
                institution_class_id: Controller.classId
            });
        }, postData.classes_secondary_staff);

        if(postData.capacity > Controller.maxStudentsPerClass) {
            Controller.postError.capacity = {
                'error': 'The capacity per class has exceeded the maximum capacity limit of '+Controller.maxStudentsPerClass+' students.'
            };
        } else if(classStudents.length > postData.capacity) {
            AlertSvc.error(Controller, 'The number of students has reached the capacity limit of '+postData.capacity+' students.');
        } else {
            InstitutionClassStudentsSvc.saveClass(postData)
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
