angular
    .module('institutions.students.ctrl', ['utils.svc', 'alert.svc', 'institutions.students.svc'])
    .controller('InstitutionsStudentsCtrl', InstitutionStudentController);

InstitutionStudentController.$inject = ['$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'InstitutionsStudentsSvc'];

function InstitutionStudentController($scope, $window, $filter, UtilsSvc, AlertSvc, InstitutionsStudentsSvc) {
    // ag-grid vars
    

    var StudentController = this;

    var pageSize = 10;

    // Variables
    StudentController.externalSearch = false;
    StudentController.hasExternalDataSource;
    StudentController.internalGridOptions = null;
    StudentController.externalGridOptions = null;
    StudentController.rowsThisPage = 0;
    StudentController.createNewStudent = false;
    StudentController.genderOptions = {};
    StudentController.academicPeriodOptions = {};
    StudentController.educationGradeOptions = {};
    StudentController.classOptions = {};
    StudentController.step = 'internal_search';

    // filter variables
    StudentController.internalFilterOpenemisNo;
    StudentController.internalFilterFirstName;
    StudentController.internalFilterLastName;
    StudentController.internalFilterIdentityNumber;
    StudentController.externalFilterOpenemisNo;
    StudentController.externalFilterFirstName;
    StudentController.externalFilterLastName;
    StudentController.externalFilterIdentityNumber;

    // Controller functions
    StudentController.processStudentRecord = processStudentRecord;
    StudentController.createNewInternalDatasource = createNewInternalDatasource;
    StudentController.createNewExternalDatasource = createNewExternalDatasource;
    StudentController.insertStudentData = insertStudentData;
    StudentController.onChangeAcademicPeriod = onChangeAcademicPeriod;
    StudentController.onChangeEducationGrade = onChangeEducationGrade;
    StudentController.getStudentData = getStudentData;
    StudentController.selectStudent = selectStudent;
    StudentController.postForm = postForm;
    StudentController.addStudentUser = addStudentUser;
    StudentController.setStudentName = setStudentName;
    StudentController.appendName = appendName;
    StudentController.changeGender = changeGender;
    StudentController.validateNewUser = validateNewUser;

    StudentController.selectedStudent;
    StudentController.selectedStudentData = null;
    StudentController.startDate = '';
    $scope.endDate;
    StudentController.endDateFormatted;

    StudentController.defaultIdentityTypeName;

    StudentController.postResponse;

    // UI control vars
    StudentController.initialLoad = true;


    angular.element(document).ready(function () {
        InstitutionsStudentsSvc.init(angular.baseUrl);

        UtilsSvc.isAppendLoader(true);

        InstitutionsStudentsSvc.getAcademicPeriods()
        .then(function(periods) {
            StudentController.academicPeriodOptions = {
                availableOptions: periods,
                selectedOption: periods[0]
            };

            if (StudentController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
                $scope.endDate = InstitutionsStudentsSvc.formatDate(StudentController.academicPeriodOptions.selectedOption.end_date);
                StudentController.onChangeAcademicPeriod();
            }

            return InstitutionsStudentsSvc.getDefaultIdentityType();
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        .then(function(defaultIdentityType) {
            if (defaultIdentityType.length > 0) {
                StudentController.defaultIdentityTypeName = defaultIdentityType[0].name;
            }
            $scope.initGrid();
            return InstitutionsStudentsSvc.getGenders();
        }, function(error){
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        .then(function(genders) {
            StudentController.genderOptions = genders;
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });

    });

    $scope.initGrid = function() {

        StudentController.internalGridOptions = {
            columnDefs: [
                {
                    field:'id',
                    headerName:'',
                    suppressMenu: true,
                    suppressSorting: true,
                    width: 40,
                    maxWidth: 40,
                    cellRenderer: function(params) {
                        var data = JSON.stringify(params.data);
                        return '<div><input  name="ngSelectionCell" ng-click="InstitutionStudentController.selectStudent('+params.value+')" tabindex="-1" class="no-selection-label" kd-checkbox-radio type="radio" selectedStudent="'+params.value+'"/></div>';
                    }
                },
                {headerName: "Openemis No", field: "openemis_no", suppressMenu: true, suppressSorting: true},
                {headerName: "First Name", field: "first_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Last Name", field: "last_name", suppressMenu: true, suppressSorting: true},
                {headerName: (angular.isDefined(StudentController.defaultIdentityTypeName))? StudentController.defaultIdentityTypeName: "[default identity type not set]", field: "identity_number", suppressMenu: true, suppressSorting: true},
            ],
            enableColResize: false,
            enableFilter: true,
            enableServerSideFilter: true,
            enableServerSideSorting: true,
            enableSorting: true,
            headerHeight: 38,
            rowData: [],
            rowHeight: 38,
            rowModelType: 'pagination',
            onGridReady: function() {
                $scope.reloadInternalDatasource(false);
                UtilsSvc.isAppendLoader(false);
            },
            angularCompileRows: true
        };
        
        StudentController.externalGridOptions = {
            columnDefs: [
                {
                    field:'id',
                    headerName:'',
                    suppressMenu: true,
                    suppressSorting: true,
                    width: 40,
                    maxWidth: 40,
                    cellRenderer: function(params) {
                        var data = JSON.stringify(params.data);
                        return '<div><input  name="ngSelectionCell" ng-click="InstitutionStudentController.selectStudent('+params.value+')" tabindex="-1" class="no-selection-label" kd-checkbox-radio type="radio" selectedStudent="'+params.value+'"/></div>';
                    }
                },
                {headerName: "Openemis No", field: "openemis_no", suppressMenu: true, suppressSorting: true},
                {headerName: "First Name", field: "first_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Last Name", field: "last_name", suppressMenu: true, suppressSorting: true},
                {headerName: (angular.isDefined(StudentController.defaultIdentityTypeName))? StudentController.defaultIdentityTypeName: "[default identity type not set]", field: "identity_number", suppressMenu: true, suppressSorting: true},
            ],
            enableColResize: false,
            enableFilter: true,
            enableServerSideFilter: true,
            enableServerSideSorting: true,
            enableSorting: true,
            headerHeight: 38,
            rowData: [],
            rowHeight: 38,
            rowModelType: 'pagination',
            angularCompileRows: true
        };
    };

    $scope.reloadInternalDatasource = function (withData) {
        InstitutionsStudentsSvc.resetExternalVariable();
        StudentController.createNewInternalDatasource(StudentController.internalGridOptions, withData);
    };

    $scope.reloadExternalDatasource = function (withData) {
        InstitutionsStudentsSvc.resetExternalVariable();
        StudentController.createNewExternalDatasource(StudentController.externalGridOptions, withData);
    };

    $scope.clearInternalSearchFilters = function () {
        StudentController.internalFilterOpenemisNo = '';
        StudentController.internalFilterFirstName = '';
        StudentController.internalFilterLastName = '';
        StudentController.internalFilterIdentityNumber = '';
        StudentController.createNewInternalDatasource(StudentController.internalGridOptions);
    };

    $scope.clearExternalSearchFilters = function () {
        StudentController.externalFilterOpenemisNo = '';
        StudentController.externalFilterFirstName = '';
        StudentController.externalFilterLastName = '';
        StudentController.externalFilterIdentityNumber = '';
        StudentController.createNewExternalDatasource(StudentController.externalGridOptions);
    };

    $scope.$watch('endDate', function (newValue) {
        StudentController.endDateFormatted = $filter('date')(newValue, 'dd-MM-yyyy');
    });

    function createNewInternalDatasource(gridObj, withData) {
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                AlertSvc.reset($scope);
                delete StudentController.selectedStudent;
                if (withData) {
                   InstitutionsStudentsSvc.getStudentRecords(
                    {
                        startRow: params.startRow,
                        endRow: params.endRow,
                        conditions: {
                            openemis_no: StudentController.internalFilterOpenemisNo,
                            first_name: StudentController.internalFilterFirstName,
                            last_name: StudentController.internalFilterLastName,
                            identity_number: StudentController.internalFilterIdentityNumber,
                        }
                    }
                    )
                    .then(function(response) {
                        var studentRecords = response.data;
                        var totalRowCount = response.total;
                        return StudentController.processStudentRecord(studentRecords, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        AlertSvc.warning($scope, error);
                    }); 
                } else {
                    StudentController.rowsThisPage = [];
                    params.successCallback(StudentController.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function createNewExternalDatasource(gridObj, withData) {
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                AlertSvc.reset($scope);
                delete StudentController.selectedStudent;
                if (withData) {
                    InstitutionsStudentsSvc.getExternalStudentRecords(
                        {
                            startRow: params.startRow,
                            endRow: params.endRow,
                            conditions: {
                                openemis_no: StudentController.externalFilterOpenemisNo,
                                first_name: StudentController.externalFilterFirstName,
                                last_name: StudentController.externalFilterLastName,
                                identity_number: StudentController.externalFilterIdentityNumber,
                            }
                        }
                    )
                    .then(function(response) {
                        var studentRecords = response.data;
                        var totalRowCount = response.total;
                        return StudentController.processStudentRecord(studentRecords, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        var status = error.status;
                        if (status == '401') {
                            var message = 'You have not been authorised to fetch from external data source.';
                            AlertSvc.warning($scope, message);
                        } else {
                            AlertSvc.warning($scope, error);
                        }
                        var studentRecords = [];
                        return StudentController.processStudentRecord(studentRecords, params, 0);
                    });
                } else {
                    StudentController.rowsThisPage = [];
                    params.successCallback(StudentController.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function processStudentRecord(studentRecords, params, totalRowCount) {
        for(var key in studentRecords) {
            studentRecords[key]['institution_name'] = '-';
            studentRecords[key]['academic_period_name'] = '-';
            studentRecords[key]['education_grade_name'] = '-';
            if ((studentRecords[key].hasOwnProperty('institution_students') && studentRecords[key]['institution_students'].length > 0)) {
                studentRecords[key]['institution_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('institution')))? studentRecords[key].institution_students['0'].institution.name: '-';
                studentRecords[key]['academic_period_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('academic_period')))? studentRecords[key].institution_students['0'].academic_period.name: '-';
                studentRecords[key]['education_grade_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('education_grade')))? studentRecords[key].institution_students['0'].education_grade.name: '-';
            }
        }

        var lastRow = totalRowCount;
        StudentController.rowsThisPage = studentRecords;
        
        params.successCallback(StudentController.rowsThisPage, lastRow);
        UtilsSvc.isAppendLoader(false);
        StudentController.initialLoad = false;
        return studentRecords;
    }

    function insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate) {
        UtilsSvc.isAppendLoader(true);
        AlertSvc.reset($scope);
        var data = {
            student_id: studentId,
            student_name: studentId,
            academic_period_id: academicPeriodId, 
            education_grade_id: educationGradeId,
            start_date: startDate,
            end_date: endDate
        };

        if (classId != null) {
            data['class'] = classId;
        }

        InstitutionsStudentsSvc.postEnrolledStudent(data)
        .then(function(postResponse) {
            StudentController.postResponse = postResponse.data;
            UtilsSvc.isAppendLoader(false);
            if (postResponse.data.error.length === 0) {
                AlertSvc.success($scope, 'The record has been added successfully.');
                $window.location.href = 'index'
            } else {
                AlertSvc.error($scope, 'The record is not added due to errors encountered.');
            }
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    }

    $scope.onAddNewStudentClick = function() {
        StudentController.createNewStudent = true;

        if (StudentController.hasExternalDataSource) {
            angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                step: "createUser"
            });
        } else {
            angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                step: 2
            });
        }
    };

    function selectStudent(id) {
        StudentController.selectedStudent = id;
        StudentController.getStudentData();
    }

    function setStudentName() {
        var studentData = StudentController.selectedStudentData;
        studentData.name = '';

        if (studentData.hasOwnProperty('first_name')) {
            studentData.name = studentData.first_name.trim();
        }
        StudentController.appendName('middle_name');
        StudentController.appendName('third_name');
        StudentController.appendName('last_name');
        StudentController.selectedStudentData = studentData;
    }

    function appendName(variableName) {
        var studentData = StudentController.selectedStudentData;
        if (studentData.hasOwnProperty(variableName)) {
            studentData[variableName] = studentData[variableName].trim();
            if (studentData[variableName] != null && studentData[variableName] != '') {
                studentData.name = studentData.name + ' ' + studentData[variableName];
            }
        }
        StudentController.selectedStudentData = studentData;
    }

    function changeGender() {
        var studentData = StudentController.selectedStudentData;
        if (studentData.hasOwnProperty('gender_id')) {
            var genderOptions = StudentController.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == studentData.gender_id) {
                    studentData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StudentController.selectedStudentData = studentData;
        }
    }

    function getStudentData() {
        InstitutionsStudentsSvc.getStudentData(StudentController.selectedStudent)
            .then(function(studentData) {
                studentData.date_of_birth = InstitutionsStudentsSvc.formatDate(studentData.date_of_birth);
                if (!studentData.hasOwnProperty('name')) {
                    studentData.name = studentData.first_name;
                    if (studentData.middle_name != null && studentData.middle_name != '') {
                        studentData.name = studentData.name + ' ' + studentData.middle_name;
                    }
                    if (studentData.third_name != null && studentData.third_name != '') {
                        studentData.name = studentData.name + ' ' + studentData.third_name;
                    }
                    if (studentData.last_name != null && studentData.last_name != '') {
                        studentData.name = studentData.name + ' ' + studentData.last_name;
                    }
                }
                StudentController.selectedStudentData = studentData;
                return studentData;
            }, function(error) {
                console.log(error);
                AlertSvc.warning($scope, error);
            })
            ;
    }

    $scope.formatDateReverse = function(datetime) {
        datetime = new Date(datetime);

        var yyyy = datetime.getFullYear().toString();
        var mm = (datetime.getMonth()+1).toString(); // getMonth() is zero-based
        var dd  = datetime.getDate().toString();

        return (dd[1]?dd:"0"+dd[0]) + '-' + (mm[1]?mm:"0"+mm[0]) + '-' + yyyy; // padding
    }



    function onChangeAcademicPeriod() {
        AlertSvc.reset($scope);

        if (StudentController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
            $scope.endDate = InstitutionsStudentsSvc.formatDate(StudentController.academicPeriodOptions.selectedOption.end_date);
            StudentController.startDate = $scope.formatDateReverse(StudentController.academicPeriodOptions.selectedOption.start_date);
        }

        var startDatePicker = angular.element(document.getElementById('Students_start_date'));
        startDatePicker.datepicker("setStartDate", $scope.formatDateReverse(StudentController.academicPeriodOptions.selectedOption.start_date));
        startDatePicker.datepicker("setEndDate", $scope.formatDateReverse(StudentController.academicPeriodOptions.selectedOption.end_date));
        startDatePicker.datepicker("setDate", $scope.formatDateReverse(StudentController.academicPeriodOptions.selectedOption.start_date));

        StudentController.educationGradeOptions = null;
        InstitutionsStudentsSvc.getEducationGrades({
            academicPeriodId: StudentController.academicPeriodOptions.selectedOption.id
        })
        .then(function(educationGrades) {
            StudentController.educationGradeOptions = {
                availableOptions: educationGrades,
            };
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    }

    function onChangeEducationGrade() {
        AlertSvc.reset($scope);

        StudentController.classOptions = null;

        InstitutionsStudentsSvc.getClasses({
            academicPeriodId: StudentController.academicPeriodOptions.selectedOption.id,
            gradeId: StudentController.educationGradeOptions.selectedOption.education_grade_id
        })
        .then(function(classes) {
            StudentController.classOptions = {
                availableOptions: classes,
            };
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    }

    function postForm() {
        var academicPeriodId = (StudentController.academicPeriodOptions.hasOwnProperty('selectedOption'))? StudentController.academicPeriodOptions.selectedOption.id: '';
        var educationGradeId = (StudentController.educationGradeOptions.hasOwnProperty('selectedOption'))? StudentController.educationGradeOptions.selectedOption.education_grade_id: '';
        var classId = null;
        if (StudentController.classOptions.hasOwnProperty('selectedOption')) {
            classId = StudentController.classOptions.selectedOption.id;
        }
        var startDate = StudentController.startDate;
        var startDateArr = startDate.split("-");
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        var endDate = $scope.endDate;

        if (!StudentController.createNewStudent) {
            InstitutionsStudentsSvc.getStudentData(StudentController.selectedStudent)
            .then(function(studentData){
                if (StudentController.externalSearch) {
                    StudentController.addStudentUser(studentData, academicPeriodId, educationGradeId, classId, startDate, endDate);
                } else {
                    var studentId = StudentController.selectedStudent;
                    StudentController.insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate);
                }
            }, function(error){

            });
        } else {
            if (StudentController.selectedStudentData != null) {
                var studentData = {};
                var log = [];
                angular.forEach(StudentController.selectedStudentData, function(value, key) {
                  studentData[key] = value;
                }, log);
                if (studentData.hasOwnProperty('date_of_birth')) {
                    var dateOfBirth = studentData.date_of_birth;
                    var dateOfBirthArr = dateOfBirth.split("-");
                    dateOfBirth = dateOfBirthArr[2] + '-' + dateOfBirthArr[1] + '-' + dateOfBirthArr[0];
                    studentData.date_of_birth = dateOfBirth; 
                }
                
                InstitutionsStudentsSvc.getOpenEmisId()
                .then(function(openemisNo){
                    studentData.openemis_no = openemisNo.data[0].openemis;
                    StudentController.addStudentUser(studentData, academicPeriodId, educationGradeId, classId, startDate, endDate);
                }, function(error){

                });
                
            }
        }
    }

    function addStudentUser(studentData, academicPeriodId, educationGradeId, classId, startDate, endDate) {

        var newStudentData = studentData;
        newStudentData['academic_period_id'] = academicPeriodId;
        newStudentData['education_grade_id'] = educationGradeId;
        InstitutionsStudentsSvc.addUser(newStudentData)
        .then(function(user){
            console.log(user);
            if (user.error.length === 0) {
                var studentId = user.data.id;
                StudentController.insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate);
            } else {
                StudentController.postResponse = user;
                AlertSvc.error($scope, 'The record is not added due to errors encountered.');
            }
        }, function(error){
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    }


    angular.element(document.querySelector('#wizard')).on('actionclicked.fu.wizard', function(evt, data) {
        // evt.preventDefault();
        AlertSvc.reset($scope);

        if (angular.isDefined(StudentController.postResponse)){
            delete StudentController.postResponse;
            $scope.$apply();
        }
        // To go to add student page if there is a student selected from the internal search
        // or external search
        if (StudentController.selectedStudent && (data.step == 1 || data.step == 2) && data.direction == 'next') {

            if (StudentController.hasExternalDataSource) {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: 'addStudent'
                });
                evt.preventDefault();
            } else {
                if (data.step == 1) {
                    angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                        step: 3
                    });
                    evt.preventDefault(); 
                } else {
                    StudentController.validateNewUser();
                }
                
            }
            
        } else if (
            (
                (data.step == 3 && StudentController.hasExternalDataSource)
            ) && data.direction == 'next') {
                StudentController.validateNewUser();
        }
    });

    function validateNewUser() {
        var remain = false;
        StudentController.postResponse = {};
        StudentController.postResponse.error = {};
        if (StudentController.selectedStudentData.first_name == '') {
            StudentController.postResponse.error.first_name = {'_empty': 'This field cannot be left empty'};
            remain = true;
        }

        if (StudentController.selectedStudentData.last_name == '') {
            StudentController.postResponse.error.last_name = {'_empty': 'This field cannot be left empty'};
            remain = true;
        }
        if (StudentController.selectedStudentData.gender_id == '' || StudentController.selectedStudentData.gender_id == null) {
            StudentController.postResponse.error.gender_id = {'_empty': 'This field cannot be left empty'};
            remain = true;
        }

        if (StudentController.selectedStudentData.date_of_birth == '') {
            StudentController.postResponse.error.date_of_birth = {'_empty': 'This field cannot be left empty'};
            remain = true;
        }

        if (remain) {
            AlertSvc.error($scope, 'Please review the errors in the form.');
            $scope.$apply();
            if (StudentController.hasExternalDataSource) {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: 'createUser'
                });  
            } else {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: 2
                });
            }
        }
    }

    angular.element(document.querySelector('#wizard')).on('finished.fu.wizard', function(evt, data) {
        StudentController.postForm();
    });

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function(evt, data) {
        // External Search
        if (data.step == 2  && StudentController.hasExternalDataSource) {
            $scope.reloadExternalDatasource(false);
            StudentController.createNewStudent = false;
            StudentController.externalSearch = true;
            StudentController.step = 'external_search';
        } else if (data.step == 1) {
            $scope.reloadInternalDatasource(false);
            StudentController.createNewStudent = false;
            StudentController.externalSearch = false;
            StudentController.step = 'internal_search';
        } else if (data.step == 3 && StudentController.hasExternalDataSource) {
            StudentController.externalSearch = false;
            StudentController.step = 'create_user';
            InstitutionsStudentsSvc.resetExternalVariable();
        } else if (data.step == 2 && !StudentController.hasExternalDataSource) {
            StudentController.externalSearch = false;
            InstitutionsStudentsSvc.resetExternalVariable();
            StudentController.selectedStudent = true;
            StudentController.step = 'create_user';
        } else {
            StudentController.step = 'add_student';
        }
    });


}
