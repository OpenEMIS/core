//Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('institution.subject.students.ctrl', ['agGrid', 'kd-angular-multi-select', 'utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.subject.students.svc', 'angular.chosen'])
    .controller('InstitutionSubjectStudentsCtrl', InstitutionSubjectStudentsController);

InstitutionSubjectStudentsController.$inject = ['$scope', '$q', '$http', '$window', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionSubjectStudentsSvc'];

function InstitutionSubjectStudentsController($scope, $q, $http, $window, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionSubjectStudentsSvc) {

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
        {headerName: 'Class', field: 'institution_class'},
        {headerName: 'Gender', field: 'gender_name'},
        {headerName: 'Student Status', field: 'student_status_name'}
    ];
    Controller.assignedStudents = [];
    Controller.unassignedStudents = [];
    Controller.originalAssignedStudents = [];
    Controller.educationSubjectName = '';
    Controller.teacherOptions = [];
    Controller.roomOptions = [];
    Controller.alertUrl = '';
    Controller.redirectUrl = '';
    Controller.selectedShift = null;
    Controller.selectedTeacher = null;
    Controller.className = '';
    Controller.academicPeriodName = '';
    Controller.institutionSubjects = [];
    Controller.postError = [];
    Controller.pastTeachers = [];
    Controller.institutionSubjectName = null;
    Controller.academicPeriodId = null;
    Controller.institutionId = null;
    Controller.educationSubjectId = null;
    Controller.educationGradeId = null;
    Controller.institutionClassIds = [];
    Controller.teachers = [];
    Controller.rooms = [];
    Controller.classOptions = [];
    Controller.classes = [];
    Controller.toValidateClasses = false;
    Controller.maxStudentsPerSubject = null;

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
                Controller.institutionSubjectName = response.name;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.institutionId = response.institution_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.educationSubjectName = response.education_subject.name;
                Controller.educationSubjectId = response.education_subject_id;
                Controller.educationGradeId = response.education_grade_id;

                var classIds = [];
                angular.forEach(response.class_subjects, function(value, key) {
                    this.push(value.institution_class_id);
                }, classIds);
                Controller.institutionClassIds = classIds;

                var teachers = [];
                angular.forEach(response.subject_staff, function(value, key) {
                    if (value.end_date == null) {
                        this.push(value.staff_id);
                    } else {
                        Controller.pastTeachers.push({
                            staff_id: value.staff_id,
                            institution_subject_id: value.institution_subject_id,
                            institution_id: value.institution_id,
                            id: value.id,
                            name_with_id: value.user.name_with_id,
                            start_date: value.start_date,
                            end_date: value.end_date
                        });
                    }
                }, teachers);
                Controller.teachers = teachers;

                var rooms = [];
                angular.forEach(response.rooms, function(value, key) {
                    this.push(value.id);
                }, rooms);
                Controller.rooms = rooms;

                var classes = [];
                angular.forEach(response.class_subjects, function(value, key) {
                    this.push(value.institution_class_id);
                }, classes);
                Controller.classes = classes;

                var assignedStudents = [];
                angular.forEach(response.subject_students, function(value, key) {
                    var toPush = {
                        openemis_no: value.user.openemis_no,
                        name: value.user.name,
                        student_status_name: value.student_status.name,
                        gender_name: value.user.gender.name,
                        student_id: value.student_id,
                        institution_class: value.institution_class.name,
                        institution_class_id: value.institution_class_id,
                        encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify(
                            {
                                student_id: value.student_id,
                                institution_class_id: value.institution_class_id,
                                institution_subject_id: value.institution_subject_id,
                                education_grade_id: value.education_grade_id,
                                education_subject_id: value.education_subject_id,
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
                Controller.originalAssignedStudents = assignedStudents.slice();

                var promises = [];
                promises[0] = InstitutionSubjectStudentsSvc.getUnassignedStudent(response.id, Controller.academicPeriodId, Controller.educationGradeId, Controller.institutionClassIds);
                promises[1] = InstitutionSubjectStudentsSvc.getTeacherOptions(response.institution_id, response.academic_period_id);
                promises[2] = InstitutionSubjectStudentsSvc.getRoomsOptions(response.academic_period_id, Controller.institutionSubjectId);
                promises[3] = InstitutionSubjectStudentsSvc.getClassOptions(response.institution_id, response.academic_period_id, Controller.educationGradeId, response.id);
                promises[4] = InstitutionSubjectStudentsSvc.getConfigItemValue('max_students_per_subject');
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
                        student_status_name: value.student_status,
                        gender_name: value.gender,
                        student_id: value.student_id,
                        institution_class: value.institution_class,
                        institution_class_id: value.institution_class_id,
                        encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify(
                            {
                                student_id: value.student_id,
                                institution_class_id: value.institution_class_id,
                                institution_subject_id: Controller.institutionSubjectId,
                                education_grade_id: value.education_grade_id,
                                education_subject_id: Controller.educationSubjectId,
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
                Controller.teacherOptions = promises[1];
                Controller.roomOptions = promises[2];
                Controller.classOptions = promises[3];
                Controller.maxStudentsPerSubject = promises[4];
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
                Controller.toValidateClasses = true;
                UtilsSvc.isAppendLoader(false);
            });
        }

        $scope.$watch('InstitutionSubjectStudentsController.classes', function(_newVal, _oldVal) {
            if (Controller.toValidateClasses) {
                UtilsSvc.isAppendLoader(true);
                validateClassUpdate(_newVal, _oldVal);
            } else if (Controller.dataReady) {
                Controller.toValidateClasses = true;
            }
        })

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
        postData.name = Controller.institutionSubjectName;
        postData.subject_students = [];
        angular.forEach(Controller.assignedStudents, function(value, key) {
            this.push(value.encodedVar);
        }, postData.subject_students)
        postData.institution_id = Controller.institutionId;
        postData.academic_period_id = Controller.academicPeriodId;
        postData.subject_staff = [];
        postData.class_subjects = [];
        postData.rooms = Controller.rooms;
        postData.education_grade_id = Controller.educationGradeId;
        postData.education_subject_id = Controller.educationSubjectId;
        var pastTeachers = JSON.parse(JSON.stringify(Controller.pastTeachers));
        angular.forEach(pastTeachers, function(value, key) {
            if (Controller.teachers.indexOf(value.staff_id) < 0) {
                delete value.name_with_id;
                delete value.start_date;
                delete value.end_date;
                this.push(value);
            }
        }, postData.subject_staff);

        angular.forEach(Controller.classes, function(value, key) {
            this.push({
                institution_class_id: value,
                institution_subject_id: Controller.institutionSubjectId,
                status: 1
            });
        }, postData.class_subjects);

        angular.forEach(Controller.teachers, function(value, key) {
            this.push({
                staff_id: value,
                institution_subject_id: Controller.institutionSubjectId,
                institution_id: Controller.institutionId
            });
        }, postData.subject_staff);
        if(classStudents.length > Controller.maxStudentsPerSubject){
            AlertSvc.error(Controller, 'The number of students per subject has reached the maximum limit of '+Controller.maxStudentsPerSubject+' students.');
        }else{
            InstitutionSubjectStudentsSvc.saveInstitutionSubject(postData)
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

    // updating of classes validation can be done on the frontend
    function validateClassUpdate(newClass, oldClass) {
        var classDiff = getClassesDifferent(newClass, oldClass);
        var validateError = false;

        // Controller.postError = {};
        Controller.postError['classes'] = {};

        // Remove classes to check on the assigned student if contains removed class student
        if (classDiff.type === 'remove') {
            for (var i = 0; i < Controller.assignedStudents.length; ++i) {
                if (Controller.assignedStudents[i]['institution_class_id'] == classDiff.value) {
                    validateError = true;
                    break;
                }
            }

            if (validateError) {
                Controller.postError.class_subjects = {
                    'error': 'Class cannot be removed due to existing student that is assigned to the subject.'
                };
                Controller.classes = classDiff.original;
                Controller.toValidateClasses = false;
            } else {
                let tempUnassignedStudents = [];

                for (var i = 0; i < Controller.unassignedStudents.length; ++i) {
                    if (Controller.unassignedStudents[i]['institution_class_id'] != classDiff.value) {
                        tempUnassignedStudents.push(Controller.unassignedStudents[i]);
                    }
                }

                Controller.unassignedStudents = tempUnassignedStudents;
                if (typeof Controller.gridOptionsTop.api !== 'undefined') {
                    Controller.setTop(Controller.colDef, Controller.unassignedStudents);
                    Controller.gridOptionsTop.api.setRowData(Controller.unassignedStudents);
                }
            }
            UtilsSvc.isAppendLoader(false);
        } else {
            InstitutionSubjectStudentsSvc.getUnassignedStudent(Controller.institutionSubjectId, Controller.academicPeriodId, Controller.educationGradeId, classDiff.value).then(function(response) {
                var unassignedStudentsArr = [];
                angular.forEach(response, function(value, key) {
                    var toPush = {
                        openemis_no: value.openemis_no,
                        name: value.name,
                        student_status_name: value.student_status,
                        gender_name: value.gender,
                        student_id: value.student_id,
                        institution_class: value.institution_class,
                        institution_class_id: value.institution_class_id,
                        encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify(
                            {
                                student_id: value.student_id,
                                institution_class_id: value.institution_class_id,
                                institution_subject_id: Controller.institutionSubjectId,
                                education_grade_id: value.education_grade_id,
                                education_subject_id: Controller.educationSubjectId,
                                academic_period_id: value.academic_period_id,
                                institution_id: value.institution_id,
                                student_status_id: value.student_status_id,
                                gender_id: value.gender_id
                            }
                        ))
                    };
                    this.push(toPush);
                }, unassignedStudentsArr);

                Controller.unassignedStudents = Controller.unassignedStudents.concat(unassignedStudentsArr);

                // if the class is removed but added back again in the same transaction
                // recently unassigned students from that class will be added to the unassigned students list
                var recentUnassignedStudentsArr = [];
                for (var i = 0; i < Controller.originalAssignedStudents.length; ++i) {
                    if (Controller.originalAssignedStudents[i]['institution_class_id'] == classDiff.value) {
                        recentUnassignedStudentsArr.push(Controller.originalAssignedStudents[i]);
                    }
                }
                Controller.unassignedStudents = Controller.unassignedStudents.concat(recentUnassignedStudentsArr);

                if (typeof Controller.gridOptionsTop.api !== 'undefined') {
                    Controller.setTop(Controller.colDef, Controller.unassignedStudents);
                    Controller.gridOptionsTop.api.setRowData(Controller.unassignedStudents);
                }
                UtilsSvc.isAppendLoader(false);
            })
        }
    }

    // get the required data for validation check
    function getClassesDifferent(newClass, oldClass) {
        var classDiff = {
            original: oldClass.slice()
        };

        if (newClass.length > oldClass.length) {
            classDiff['type'] = 'add';
        } else {
            classDiff['type'] = 'remove';
        }

        var diff = {};
        var diffArr = [];

        // Finding the difference in classId
        for (var i = 0; i < newClass.length; ++i) {
            diff[newClass[i]] = newClass[i];
        }

        for (var j = 0; j < oldClass.length; ++j) {
            if (diff.hasOwnProperty(oldClass[j])) {
                delete diff[oldClass[j]];
            } else {
                diff[oldClass[j]] = oldClass[j];
            }
        }

        for (var item in diff) {
            diffArr.push(item);
        }

        classDiff['value'] = diffArr[0];
        return classDiff;
    }
}
