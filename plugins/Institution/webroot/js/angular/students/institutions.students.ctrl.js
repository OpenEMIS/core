angular
    .module('institutions.students.ctrl', ['utils.svc', 'alert.svc', 'institutions.students.svc'])
    .controller('InstitutionsStudentsCtrl', InstitutionStudentController);

InstitutionStudentController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'InstitutionsStudentsSvc'];

function InstitutionStudentController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, InstitutionsStudentsSvc) {
    // ag-grid vars


    var StudentController = this;

    var pageSize = 10;

    // Variables
    StudentController.externalSearch = false;
    StudentController.hasExternalDataSource;
    StudentController.internalGridOptions = null;
    StudentController.externalGridOptions = null;
    StudentController.rowsThisPage = [];
    StudentController.createNewStudent = false;
    StudentController.genderOptions = {};
    StudentController.academicPeriodOptions = {};
    StudentController.educationGradeOptions = {};
    StudentController.classOptions = {};
    StudentController.step = 'internal_search';
    StudentController.showExternalSearchButton = false;
    StudentController.completeDisabled = false;
    StudentController.institutionId = null;

    // 0 - Non-mandatory, 1 - Mandatory, 2 - Excluded
    StudentController.StudentContacts = 2;
    StudentController.StudentIdentities = 2;
    StudentController.StudentNationalities = 2;
    StudentController.StudentSpecialNeeds = 2;
    StudentController.StudentContactsOptions = null;
    StudentController.StudentIdentitiesOptions = null;
    StudentController.StudentNationalitiesOptions = null;
    StudentController.StudentSpecialNeedsOptions = null;
    StudentController.Student = {};
    StudentController.Student.nationality_id = '';
    StudentController.Student.nationality_name = '';
    StudentController.Student.identity_type_id = '';
    StudentController.Student.identity_type_name = '';


    // filter variables
    StudentController.internalFilterOpenemisNo;
    StudentController.internalFilterFirstName;
    StudentController.internalFilterLastName;
    StudentController.internalFilterIdentityNumber;
    StudentController.internalFilterDateOfBirth;

    // Controller functions
    StudentController.initNationality = initNationality;
    StudentController.initIdentityType = initIdentityType;
    StudentController.changeNationality = changeNationality;
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
    StudentController.onExternalSearchClick = onExternalSearchClick;
    StudentController.onAddNewStudentClick = onAddNewStudentClick;
    StudentController.onAddStudentClick = onAddStudentClick;
    StudentController.getUniqueOpenEmisId = getUniqueOpenEmisId;
    StudentController.reloadInternalDatasource = reloadInternalDatasource;
    StudentController.reloadExternalDatasource = reloadExternalDatasource;
    StudentController.clearInternalSearchFilters = clearInternalSearchFilters;
    StudentController.initialLoad = true;
    StudentController.date_of_birth = '';
    $scope.endDate;

    StudentController.selectedStudent;
    StudentController.addStudentButton = false;
    StudentController.selectedStudentData = null;
    StudentController.startDate = '';
    StudentController.endDateFormatted;
    StudentController.defaultIdentityTypeName;
    StudentController.postResponse;

    angular.element(document).ready(function () {
        InstitutionsStudentsSvc.init(angular.baseUrl);
        InstitutionsStudentsSvc.setInstitutionId(StudentController.institutionId);

        UtilsSvc.isAppendLoader(true);

        InstitutionsStudentsSvc.getAcademicPeriods()
        .then(function(periods) {
            var promises = [];
            var selectedPeriod = [];
            angular.forEach(periods, function(value) {
                if (value.current == 1) {
                   this.push(value);
                }
            }, selectedPeriod);
            if (selectedPeriod.length == 0) {
                selectedPeriod = periods;
            }

            StudentController.academicPeriodOptions = {
                availableOptions: periods,
                selectedOption: selectedPeriod[0]
            };

            if (StudentController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
                $scope.endDate = InstitutionsStudentsSvc.formatDate(StudentController.academicPeriodOptions.selectedOption.end_date);
                StudentController.onChangeAcademicPeriod();
            }
            promises.push(InstitutionsStudentsSvc.getAddNewStudentConfig());
            promises.push(InstitutionsStudentsSvc.getDefaultIdentityType());

            return $q.all(promises);
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
            UtilsSvc.isAppendLoader(false);
        })
        .then(function(promisesObj) {
            var promises = [];
            var addNewStudentConfig = promisesObj[0].data;
            for(i=0; i < addNewStudentConfig.length; i++) {
                var code = addNewStudentConfig[i].code;
                StudentController[code] = addNewStudentConfig[i].value;
            }
            if (StudentController.StudentContacts != 2) {
                promises[1] = InstitutionsStudentsSvc.getUserContactTypes();
            }
            if (StudentController.StudentNationalities != 2) {
                promises[2] = InstitutionsStudentsSvc.getNationalities();
            }
            if (StudentController.StudentIdentities != 2) {
                promises[3] = InstitutionsStudentsSvc.getIdentityTypes();
            }
            var defaultIdentityType = promisesObj[1];
            if (defaultIdentityType.length > 0) {
                StudentController.defaultIdentityTypeName = defaultIdentityType[0].name;
                StudentController.Student.identity_type_name = StudentController.defaultIdentityTypeName;
            }
            promises[0] = InstitutionsStudentsSvc.getGenders();

            return $q.all(promises);
        }, function(error){
            console.log(error);
            AlertSvc.warning($scope, error);
            UtilsSvc.isAppendLoader(false);
        })
        .then(function(promisesObj) {
            StudentController.genderOptions = promisesObj[0];
            // User Contacts
            if (promisesObj[1] != undefined && promisesObj[1].hasOwnProperty('data')) {
                StudentController.StudentContactsOptions = promisesObj[1]['data'];
            }
            // User Nationalities
            if (promisesObj[2] != undefined && promisesObj[2].hasOwnProperty('data')) {
                StudentController.StudentNationalitiesOptions = promisesObj[2]['data'];
            }
            // User Identities
            if (promisesObj[3] != undefined && promisesObj[3].hasOwnProperty('data')) {
                StudentController.StudentIdentitiesOptions = promisesObj[3]['data'];
            }
            // User Special Needs
            if (promisesObj[4] != undefined && promisesObj[4].hasOwnProperty('data')) {
                StudentController.StudentSpecialNeedsOptions = promisesObj[4]['data'];
            }
            var deferred = $q.defer();
            if (StudentController.hasExternalDataSource) {
                InstitutionsStudentsSvc.getExternalDefaultIdentityType()
                .then(function(externalIdentityType) {
                    if (externalIdentityType.length > 0) {
                        deferred.resolve(externalIdentityType[0].name);
                    } else {
                        deferred.reject('No External Identity Type');
                    }
                }, function(error) {
                    StudentController.hasExternalDataSource = false;
                    InstitutionsStudentsSvc.init(angular.baseUrl);
                    deferred.reject(error);
                });
                return deferred.promise;
            } else {
                return StudentController.genderOptions;
            }
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
            AlertSvc.warning($scope, error);
        })
        .then(function(arr) {
            if (StudentController.hasExternalDataSource) {
                externalIdentityType = arr;
                StudentController.defaultExternalIdentityTypeName = externalIdentityType;
                InstitutionsStudentsSvc.init(angular.baseUrl);
                return true;
            } else {
                return true;
            }
        }, function(error) {
            StudentController.hasExternalDataSource = false;
            InstitutionsStudentsSvc.init(angular.baseUrl);
            console.log('Error connecting to external source');
            AlertSvc.warning($scope, 'Error connecting to external source');
        })
        .finally(function(result) {
            $scope.initGrid();
            UtilsSvc.isAppendLoader(false);
            if ($location.search().student_added) {
                AlertSvc.success($scope, 'The student is added to the Pending Admission list successfully.');
            }
        });

    });

    function initNationality() {
        StudentController.Student.nationality_id = '';
        var options = StudentController.StudentNationalitiesOptions;
        for(var i = 0; i < options.length; i++) {
            if (options[i].default == 1) {
                StudentController.Student.nationality_id = options[i].id;
                StudentController.Student.nationality_name = options[i].name;
                StudentController.Student.identity_type_id = options[i].identity_type_id;
                StudentController.Student.identity_type_name = options[i].identity_type.name;
                break;
            }
        }
    }

    function changeNationality() {
        var nationalityId = StudentController.Student.nationality_id;
        var options = StudentController.StudentNationalitiesOptions;
        for(var i = 0; i < options.length; i++) {
            if (options[i].id == nationalityId) {
                StudentController.Student.identity_type_id = options[i].identity_type_id;
                StudentController.Student.nationality_name = options[i].name;
                StudentController.Student.identity_type_name = options[i].identity_type.name;
                break;
            }
        }
    }

    function initIdentityType() {
        if (StudentController.Student.nationality_id == '') {
            var options = StudentController.StudentIdentitiesOptions;
            for(var i = 0; i < options.length; i++) {
                if (options[i].default == 1) {
                    console.log(options[i].id);
                    StudentController.Student.identity_type_id = options[i].id;
                    StudentController.Student.identity_type_name = options[i].name;
                    break;
                }
            }
        }
    }

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
                {headerName: 'OpenEMIS ID', field: "openemis_no", suppressMenu: true, suppressSorting: true},
                {headerName: 'Name', field: "name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Gender', field: "gender_name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Date of Birth', field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                {headerName: (angular.isDefined(StudentController.defaultIdentityTypeName))? StudentController.defaultIdentityTypeName : "[default identity type not set]", field: "identity_number", suppressMenu: true, suppressSorting: true},
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
                {headerName: 'OpenEMIS ID', field: "openemis_no", suppressMenu: true, suppressSorting: true},
                {headerName: 'Name', field: "name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Gender', field: "gender_name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Date of Birth', field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                {headerName: (angular.isDefined(StudentController.defaultExternalIdentityTypeName))? StudentController.defaultExternalIdentityTypeName: "[default identity type not set]", field: "identity_number", suppressMenu: true, suppressSorting: true},
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

    function reloadInternalDatasource(withData) {
        if (withData !== false) {
           StudentController.showExternalSearchButton = true;
        }
        InstitutionsStudentsSvc.resetExternalVariable();
        StudentController.createNewInternalDatasource(StudentController.internalGridOptions, withData);
    };

    function reloadExternalDatasource(withData) {
        InstitutionsStudentsSvc.resetExternalVariable();
        StudentController.createNewExternalDatasource(StudentController.externalGridOptions, withData);
    };

    function clearInternalSearchFilters() {
        StudentController.internalFilterOpenemisNo = '';
        StudentController.internalFilterFirstName = '';
        StudentController.internalFilterLastName = '';
        StudentController.internalFilterIdentityNumber = '';
        StudentController.internalFilterDateOfBirth = '';
        StudentController.initialLoad = true;
        StudentController.createNewInternalDatasource(StudentController.internalGridOptions);
    }

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
                            date_of_birth: StudentController.internalFilterDateOfBirth,
                        }
                    }
                    )
                    .then(function(response) {
                        if (response.conditionsCount == 0) {
                            StudentController.initialLoad = true;
                        } else {
                            StudentController.initialLoad = false;
                        }
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
        StudentController.externalDataLoaded = false;
        StudentController.initialLoad = true;
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
                                openemis_no: StudentController.internalFilterOpenemisNo,
                                first_name: StudentController.internalFilterFirstName,
                                last_name: StudentController.internalFilterLastName,
                                identity_number: StudentController.internalFilterIdentityNumber,
                                date_of_birth: StudentController.internalFilterDateOfBirth
                            }
                        }
                    )
                    .then(function(response) {
                        var studentRecords = response.data;
                        var totalRowCount = response.total;
                        StudentController.initialLoad = false;
                        return StudentController.processStudentRecord(studentRecords, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        var status = error.status;
                        if (status == '401') {
                            var message = 'You have not been authorised to fetch from external data source.';
                            AlertSvc.warning($scope, message);
                        } else {
                            var message = 'External search failed, please contact your administrator to verify the external search attributes';
                            AlertSvc.warning($scope, message);
                        }
                        var studentRecords = [];
                        InstitutionsStudentsSvc.init(angular.baseUrl);
                        return StudentController.processStudentRecord(studentRecords, params, 0);
                    })
                    .finally(function(res) {
                        InstitutionsStudentsSvc.init(angular.baseUrl);
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

            studentRecords[key]['date_of_birth'] = InstitutionsStudentsSvc.formatDate(studentRecords[key]['date_of_birth']);
            studentRecords[key]['gender_name'] = studentRecords[key]['gender']['name'];

            if (!studentRecords[key].hasOwnProperty('name')) {
                studentRecords[key]['name'] = '';
                if (studentRecords[key].hasOwnProperty('first_name')) {
                    studentRecords[key]['name'] = studentRecords[key]['first_name'];
                }
                StudentController.appendName(studentRecords[key], 'middle_name');
                StudentController.appendName(studentRecords[key], 'third_name');
                StudentController.appendName(studentRecords[key], 'last_name');
            }
        }

        var lastRow = totalRowCount;
        StudentController.rowsThisPage = studentRecords;

        params.successCallback(StudentController.rowsThisPage, lastRow);
        StudentController.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return studentRecords;
    }

    function insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate, userRecord) {
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
                AlertSvc.success($scope, 'The student is added to the Pending Admission list successfully.');
                $window.location.href = 'add?student_added=true';
            } else {
                if (userRecord.hasOwnProperty('institution_students')) {
                    if (userRecord.institution_students.length > 0) {
                        var schoolName = userRecord['institution_students'][0]['institution']['name'];
                        AlertSvc.warning($scope, 'Student is already enrolled in ' + schoolName);
                        userRecord.date_of_birth = InstitutionsStudentsSvc.formatDate(userRecord.date_of_birth);
                        StudentController.selectedStudentData = userRecord;
                        StudentController.completeDisabled = true;
                    } else {
                        AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                    }
                } else {
                    AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                }
            }
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    }

    function onAddNewStudentClick() {
        StudentController.createNewStudent = true;
        StudentController.completeDisabled = false;
        StudentController.selectedStudentData = {};
        StudentController.selectedStudentData.first_name = '';
        StudentController.selectedStudentData.last_name = '';
        StudentController.selectedStudentData.date_of_birth = '';
        StudentController.initNationality();
        StudentController.initIdentityType();
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "createUser"
        });
    }

    function onAddStudentClick() {
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "addStudent"
        });
    }

    function onExternalSearchClick() {
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "externalSearch"
        });
    }

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
        StudentController.appendName(studentData, 'middle_name', true);
        StudentController.appendName(studentData, 'third_name', true);
        StudentController.appendName(studentData, 'last_name', true);
        StudentController.selectedStudentData = studentData;
    }

    function appendName(studentObj, variableName, trim) {
        if (studentObj.hasOwnProperty(variableName)) {
            if (trim === true) {
                studentObj[variableName] = studentObj[variableName].trim();
            }
            if (studentObj[variableName] != null && studentObj[variableName] != '') {
                studentObj.name = studentObj.name + ' ' + studentObj[variableName];
            }
        }
        return studentObj;
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
        var log = [];
        angular.forEach(StudentController.rowsThisPage , function(value) {
            if (value.id == StudentController.selectedStudent) {
                StudentController.selectedStudentData = value;
            }
        }, log);
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
            institutionId: StudentController.institutionId,
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
            institutionId: StudentController.institutionId,
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
        for(i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var endDate = $scope.endDate;

        if (!StudentController.createNewStudent) {
            InstitutionsStudentsSvc.getStudentData(StudentController.selectedStudent)
            .then(function(studentData){

                if (StudentController.externalSearch) {
                    InstitutionsStudentsSvc.init(angular.baseUrl);
                    StudentController.addStudentUser(studentData, academicPeriodId, educationGradeId, classId, startDate, endDate);
                } else {
                    var studentId = StudentController.selectedStudent;
                    StudentController.insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate, {});
                }
            }, function(error){
                console.log(error);
            });
        } else {
            console.log('postForm');
            if (StudentController.selectedStudentData != null) {
                console.log('not null');
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
                delete studentData['id'];
                delete studentData['institution_students'];
                delete studentData['is_staff'];
                delete studentData['is_guardian'];
                delete studentData['address'];
                delete studentData['postal_code'];
                delete studentData['address_area_id'];
                delete studentData['birthplace_area_id'];
                delete studentData['date_of_death'];
                delete studentData['password'];
                studentData['super_admin'] = 0;
                studentData['status'] = 1;
                delete studentData['last_login'];
                delete studentData['photo_name'];
                delete studentData['photo_content'];
                delete studentData['modified'];
                delete studentData['modified_user_id'];
                delete studentData['created'];
                delete studentData['created_user_id'];
                StudentController.addStudentUser(studentData, academicPeriodId, educationGradeId, classId, startDate, endDate);
            }
        }
    }

    function addStudentUser(studentData, academicPeriodId, educationGradeId, classId, startDate, endDate) {

        var newStudentData = studentData;
        newStudentData['academic_period_id'] = academicPeriodId;
        newStudentData['education_grade_id'] = educationGradeId;
        newStudentData['start_date'] = startDate;
        newStudentData['nationality_id'] = StudentController.Student.nationality_id;
        newStudentData['identity_type_id'] = StudentController.Student.identity_type_id;
        InstitutionsStudentsSvc.addUser(newStudentData)
        .then(function(user){
            if (user[0].error.length === 0) {
                var studentId = user[0].data.id;
                StudentController.insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate, user[1]);
            } else {
                StudentController.postResponse = user[0];
                console.log(user[0]);
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
        if (data.step == 3 && data.direction == 'next') {
            if (StudentController.validateNewUser()) {
                evt.preventDefault();
            };
        }
    });

    function validateNewUser() {
        var remain = false;
        var empty = {'_empty': 'This field cannot be left empty'};
        StudentController.postResponse = {};
        StudentController.postResponse.error = {};
        if (StudentController.selectedStudentData.first_name == '') {
            StudentController.postResponse.error.first_name = empty;
            remain = true;
        }

        if (StudentController.selectedStudentData.last_name == '') {
            StudentController.postResponse.error.last_name = empty;
            remain = true;
        }
        if (StudentController.selectedStudentData.gender_id == '' || StudentController.selectedStudentData.gender_id == null) {
            StudentController.postResponse.error.gender_id = empty;
            remain = true;
        }

        if (StudentController.selectedStudentData.date_of_birth == '') {
            StudentController.postResponse.error.date_of_birth = empty;
            remain = true;
        }

        if (StudentController.StudentNationalities == 1 && (StudentController.Student.nationality_id == '' || StudentController.Student.nationality_id == undefined)) {
            remain = true;
        }

        var arrNumber = [{}];

        // if (StudentController.StudentIdentities == 1 && (StudentController.Student.identity_type_id == '' || StudentController.Student.identity_type_id == undefined)) {
        //     arrNumber[0]['identity_type_id'] = empty;
        //     StudentController.postResponse.error.identities = arrNumber;
        //     remain = true;
        // }
        if (StudentController.StudentIdentities == 1 && (StudentController.selectedStudentData.identity_number == '' || StudentController.selectedStudentData.identity_number == undefined)) {
            arrNumber[0]['number'] = empty;
            StudentController.postResponse.error.identities = arrNumber;
            remain = true;
        }

        var arrNationality = [{}];
        if (StudentController.StudentNationalities == 1 && (StudentController.Student.nationality_id == '' || StudentController.Student.nationality_id == undefined)) {
            arrNationality[0]['nationality_id'] = empty;
            StudentController.postResponse.error.nationalities = arrNationality;
            remain = true;
        }

        if (remain) {
            AlertSvc.error($scope, 'Please review the errors in the form.');
            $scope.$apply();
            angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                step: 'createUser'
            });
        }
        return remain;
    }

    function getUniqueOpenEmisId() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getUniqueOpenEmisId()
        .then(function(response) {
            StudentController.selectedStudentData.openemis_no = response;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    angular.element(document.querySelector('#wizard')).on('finished.fu.wizard', function(evt, data) {
        StudentController.postForm();
    });

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function(evt, data) {
        StudentController.addStudentButton = false;
        // Step 1 - Internal search
        if (data.step == 1) {
            StudentController.Student.identity_type_name = StudentController.defaultIdentityTypeName;
            StudentController.educationGradeOptions.selectedOption = '';
            StudentController.classOptions.selectedOption = '';
            delete StudentController.postResponse;
            StudentController.reloadInternalDatasource(true);
            StudentController.createNewStudent = false;
            StudentController.externalSearch = false;
            StudentController.step = 'internal_search';
        }
        // Step 2 - External search
        else if (data.step == 2) {
            StudentController.Student.identity_type_name = StudentController.defaultIdentityTypeName;
            StudentController.educationGradeOptions.selectedOption = '';
            StudentController.classOptions.selectedOption = '';
            delete StudentController.postResponse;
            StudentController.reloadExternalDatasource(true);
            StudentController.createNewStudent = false;
            StudentController.externalSearch = true;
            StudentController.step = 'external_search';
        }
        // Step 3 - Create user
        else if (data.step == 3) {
            StudentController.externalSearch = false;
            StudentController.createNewStudent = true;
            StudentController.step = 'create_user';
            StudentController.getUniqueOpenEmisId();
            InstitutionsStudentsSvc.resetExternalVariable();
        }
        // Step 4 - Add Student
        else {
            studentData = StudentController.selectedStudentData;
            StudentController.completeDisabled = false;
            if (studentData.hasOwnProperty('institution_students')) {
                if (studentData.institution_students.length > 0) {
                    var schoolName = studentData['institution_students'][0]['institution']['name'];
                    AlertSvc.warning($scope, 'This student is already allocated to ' + schoolName);
                    StudentController.completeDisabled = true;
                }
            }
            StudentController.step = 'add_student';
        }
    });


}
