angular
    .module('institutions.staff.ctrl', ['utils.svc', 'alert.svc', 'institutions.staff.svc'])
    .controller('InstitutionsStaffCtrl', InstitutionStaffController);

InstitutionStaffController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'InstitutionsStaffSvc'];

function InstitutionStaffController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, InstitutionsStudentsSvc) {
    // ag-grid vars


    var StaffController = this;

    var pageSize = 10;

    // Variables
    StaffController.externalSearch = false;
    StaffController.hasExternalDataSource;
    StaffController.internalGridOptions = null;
    StaffController.externalGridOptions = null;
    StaffController.rowsThisPage = [];
    StaffController.createNewStudent = false;
    StaffController.genderOptions = {};
    StaffController.academicPeriodOptions = {};
    StaffController.educationGradeOptions = {};
    StaffController.classOptions = {};
    StaffController.step = 'internal_search';
    StaffController.showExternalSearchButton = false;
    StaffController.completeDisabled = false;
    StaffController.institutionId = null;

    // 0 - Non-mandatory, 1 - Mandatory, 2 - Excluded
    StaffController.StudentContacts = 2;
    StaffController.StudentIdentities = 2;
    StaffController.StudentNationalities = 2;
    StaffController.StudentSpecialNeeds = 2;
    StaffController.StudentContactsOptions = [];
    StaffController.StudentIdentitiesOptions = [];
    StaffController.StudentNationalitiesOptions = [];
    StaffController.StudentSpecialNeedsOptions = [];
    StaffController.Student = {};
    StaffController.Student.nationality_id = '';
    StaffController.Student.nationality_name = '';
    StaffController.Student.identity_type_id = '';
    StaffController.Student.identity_type_name = '';
    StaffController.Student.nationality_class = 'input select error';
    StaffController.Student.identity_type_class = 'input select error';
    StaffController.Student.identity_class = 'input string';


    // filter variables
    StaffController.internalFilterOpenemisNo;
    StaffController.internalFilterFirstName;
    StaffController.internalFilterLastName;
    StaffController.internalFilterIdentityNumber;
    StaffController.internalFilterDateOfBirth;

    // Controller functions
    StaffController.initNationality = initNationality;
    StaffController.initIdentityType = initIdentityType;
    StaffController.changeNationality = changeNationality;
    StaffController.changeIdentityType = changeIdentityType;
    StaffController.processStudentRecord = processStudentRecord;
    StaffController.processExternalStudentRecord = processExternalStudentRecord;
    StaffController.createNewInternalDatasource = createNewInternalDatasource;
    StaffController.createNewExternalDatasource = createNewExternalDatasource;
    StaffController.insertStudentData = insertStudentData;
    StaffController.onChangeAcademicPeriod = onChangeAcademicPeriod;
    StaffController.onChangeEducationGrade = onChangeEducationGrade;
    StaffController.getStudentData = getStudentData;
    StaffController.selectStudent = selectStudent;
    StaffController.postForm = postForm;
    StaffController.addStudentUser = addStudentUser;
    StaffController.setStudentName = setStudentName;
    StaffController.appendName = appendName;
    StaffController.changeGender = changeGender;
    StaffController.validateNewUser = validateNewUser;
    StaffController.onExternalSearchClick = onExternalSearchClick;
    StaffController.onAddNewStudentClick = onAddNewStudentClick;
    StaffController.onAddStudentClick = onAddStudentClick;
    StaffController.getUniqueOpenEmisId = getUniqueOpenEmisId;
    StaffController.reloadInternalDatasource = reloadInternalDatasource;
    StaffController.reloadExternalDatasource = reloadExternalDatasource;
    StaffController.clearInternalSearchFilters = clearInternalSearchFilters;
    StaffController.initialLoad = true;
    StaffController.date_of_birth = '';
    $scope.endDate;

    StaffController.selectedStudent;
    StaffController.addStudentButton = false;
    StaffController.selectedStudentData = null;
    StaffController.startDate = '';
    StaffController.endDateFormatted;
    StaffController.defaultIdentityTypeName;
    StaffController.defaultIdentityTypeId;
    StaffController.postResponse;

    angular.element(document).ready(function () {
        InstitutionsStudentsSvc.init(angular.baseUrl);
        InstitutionsStudentsSvc.setInstitutionId(StaffController.institutionId);

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

            StaffController.academicPeriodOptions = {
                availableOptions: periods,
                selectedOption: selectedPeriod[0]
            };

            if (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
                $scope.endDate = InstitutionsStudentsSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.end_date);
                StaffController.onChangeAcademicPeriod();
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
                StaffController[code] = addNewStudentConfig[i].value;
            }
            if (StaffController.StudentContacts != 2) {
                promises[1] = InstitutionsStudentsSvc.getUserContactTypes();
            }
            if (StaffController.StudentNationalities != 2) {
                if (StaffController.StudentNationalities == 1) {
                    StaffController.Student.nationality_class = StaffController.Student.nationality_class + ' required';
                }
                promises[2] = InstitutionsStudentsSvc.getNationalities();
            }
            if (StaffController.StudentIdentities != 2) {
                if (StaffController.StudentIdentities == 1) {
                    StaffController.Student.identity_class = StaffController.Student.identity_class + ' required';
                    StaffController.Student.identity_type_class = StaffController.Student.identity_type_class + ' required';
                }
                promises[3] = InstitutionsStudentsSvc.getIdentityTypes();
            }
            if (StaffController.StudentSpecialNeeds != 2) {
                promises[4] = InstitutionsStudentsSvc.getSpecialNeedTypes();
            }
            var defaultIdentityType = promisesObj[1];
            if (defaultIdentityType.length > 0) {
                StaffController.defaultIdentityTypeName = defaultIdentityType[0].name;
                StaffController.defaultIdentityTypeId = defaultIdentityType[0].id;
                StaffController.Student.identity_type_id = StaffController.defaultIdentityTypeId;
                StaffController.Student.identity_type_name = StaffController.defaultIdentityTypeName;
            } else {
                StaffController.Student.identity_type_id = null;
                StaffController.Student.identity_type_name = 'No default identity set';
            }
            promises[0] = InstitutionsStudentsSvc.getGenders();

            return $q.all(promises);
        }, function(error){
            console.log(error);
            AlertSvc.warning($scope, error);
            UtilsSvc.isAppendLoader(false);
        })
        .then(function(promisesObj) {
            StaffController.genderOptions = promisesObj[0];
            // User Contacts
            if (promisesObj[1] != undefined && promisesObj[1].hasOwnProperty('data')) {
                StaffController.StudentContactsOptions = promisesObj[1]['data'];
            }
            // User Nationalities
            if (promisesObj[2] != undefined && promisesObj[2].hasOwnProperty('data')) {
                StaffController.StudentNationalitiesOptions = promisesObj[2]['data'];
            }
            // User Identities
            if (promisesObj[3] != undefined && promisesObj[3].hasOwnProperty('data')) {
                StaffController.StudentIdentitiesOptions = promisesObj[3]['data'];
            }
            // User Special Needs
            if (promisesObj[4] != undefined && promisesObj[4].hasOwnProperty('data')) {
                StaffController.StudentSpecialNeedsOptions = promisesObj[4]['data'];
            }
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
            AlertSvc.warning($scope, error);
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
        StaffController.Student.nationality_id = '';
        var options = StaffController.StudentNationalitiesOptions;
        for(var i = 0; i < options.length; i++) {
            if (options[i].default == 1) {
                StaffController.Student.nationality_id = options[i].id;
                StaffController.Student.nationality_name = options[i].name;
                StaffController.Student.identity_type_id = options[i].identity_type_id;
                StaffController.Student.identity_type_name = options[i].identity_type.name;
                break;
            }
        }
    }

    function changeNationality() {
        var nationalityId = StaffController.Student.nationality_id;
        var options = StaffController.StudentNationalitiesOptions;
        for(var i = 0; i < options.length; i++) {
            if (options[i].id == nationalityId) {
                StaffController.Student.identity_type_id = options[i].identity_type_id;
                StaffController.Student.nationality_name = options[i].name;
                StaffController.Student.identity_type_name = options[i].identity_type.name;
                break;
            }
        }
    }

    function changeIdentityType() {
        var identityType = StaffController.Student.identity_type_id;
        var options = StaffController.StudentIdentitiesOptions;
        for(var i = 0; i < options.length; i++) {
            if (options[i].id == identityType) {
                StaffController.Student.identity_type_name = options[i].name;
                break;
            }
        }
    }

    function initIdentityType() {
        if (StaffController.Student.nationality_id == '') {
            var options = StaffController.StudentIdentitiesOptions;
            for(var i = 0; i < options.length; i++) {
                if (options[i].default == 1) {
                    StaffController.Student.identity_type_id = options[i].id;
                    StaffController.Student.identity_type_name = options[i].name;
                    break;
                }
            }
        }
    }

    $scope.initGrid = function() {

        StaffController.internalGridOptions = {
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
                        return '<div><input  name="ngSelectionCell" ng-click="InstitutionStaffController.selectStudent('+params.value+')" tabindex="-1" class="no-selection-label" kd-checkbox-radio type="radio" selectedStudent="'+params.value+'"/></div>';
                    }
                },
                {headerName: 'OpenEMIS ID', field: "openemis_no", suppressMenu: true, suppressSorting: true},
                {headerName: 'Name', field: "name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Gender', field: "gender_name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Date of Birth', field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                {headerName: 'Nationality', field: "nationality_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Identity Type", field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Identity Number", field: "identity_number", suppressMenu: true, suppressSorting: true}
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

        StaffController.externalGridOptions = {
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
                        return '<div><input  name="ngSelectionCell" ng-click="InstitutionStaffController.selectStudent('+params.value+')" tabindex="-1" class="no-selection-label" kd-checkbox-radio type="radio" selectedStudent="'+params.value+'"/></div>';
                    }
                },
                {headerName: 'Name', field: "name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Gender', field: "gender_name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Date of Birth', field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                {headerName: 'Nationality', field: "nationality_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Identity Type", field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Identity Number", field: "identity_number", suppressMenu: true, suppressSorting: true}
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
           StaffController.showExternalSearchButton = true;
        }
        InstitutionsStudentsSvc.resetExternalVariable();
        StaffController.createNewInternalDatasource(StaffController.internalGridOptions, withData);
    };

    function reloadExternalDatasource(withData) {
        InstitutionsStudentsSvc.resetExternalVariable();
        StaffController.createNewExternalDatasource(StaffController.externalGridOptions, withData);
    };

    function clearInternalSearchFilters() {
        StaffController.internalFilterOpenemisNo = '';
        StaffController.internalFilterFirstName = '';
        StaffController.internalFilterLastName = '';
        StaffController.internalFilterIdentityNumber = '';
        StaffController.internalFilterDateOfBirth = '';
        StaffController.initialLoad = true;
        StaffController.createNewInternalDatasource(StaffController.internalGridOptions);
    }

    $scope.$watch('endDate', function (newValue) {
        StaffController.endDateFormatted = $filter('date')(newValue, 'dd-MM-yyyy');
    });

    function createNewInternalDatasource(gridObj, withData) {
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                AlertSvc.reset($scope);
                delete StaffController.selectedStudent;
                if (withData) {
                   InstitutionsStudentsSvc.getStudentRecords(
                    {
                        startRow: params.startRow,
                        endRow: params.endRow,
                        conditions: {
                            openemis_no: StaffController.internalFilterOpenemisNo,
                            first_name: StaffController.internalFilterFirstName,
                            last_name: StaffController.internalFilterLastName,
                            identity_number: StaffController.internalFilterIdentityNumber,
                            date_of_birth: StaffController.internalFilterDateOfBirth,
                        }
                    }
                    )
                    .then(function(response) {
                        if (response.conditionsCount == 0) {
                            StaffController.initialLoad = true;
                        } else {
                            StaffController.initialLoad = false;
                        }
                        var studentRecords = response.data;
                        var totalRowCount = response.total;
                        return StaffController.processStudentRecord(studentRecords, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        AlertSvc.warning($scope, error);
                    });
                } else {
                    StaffController.rowsThisPage = [];
                    params.successCallback(StaffController.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function createNewExternalDatasource(gridObj, withData) {
        StaffController.externalDataLoaded = false;
        StaffController.initialLoad = true;
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                AlertSvc.reset($scope);
                delete StaffController.selectedStudent;
                if (withData) {
                    InstitutionsStudentsSvc.getExternalStudentRecords(
                        {
                            startRow: params.startRow,
                            endRow: params.endRow,
                            conditions: {
                                first_name: StaffController.internalFilterFirstName,
                                last_name: StaffController.internalFilterLastName,
                                identity_number: StaffController.internalFilterIdentityNumber,
                                date_of_birth: StaffController.internalFilterDateOfBirth
                            }
                        }
                    )
                    .then(function(response) {
                        var studentRecords = response.data;
                        var totalRowCount = response.total;
                        StaffController.initialLoad = false;
                        return StaffController.processExternalStudentRecord(studentRecords, params, totalRowCount);
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
                        return StaffController.processExternalStudentRecord(studentRecords, params, 0);
                    })
                    .finally(function(res) {
                        InstitutionsStudentsSvc.init(angular.baseUrl);
                    });
                } else {
                    StaffController.rowsThisPage = [];
                    params.successCallback(StaffController.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function processExternalStudentRecord(studentRecords, params, totalRowCount) {
        for(var key in studentRecords) {
            var mapping = InstitutionsStudentsSvc.getExternalSourceMapping();
            studentRecords[key]['institution_name'] = '-';
            studentRecords[key]['academic_period_name'] = '-';
            studentRecords[key]['education_grade_name'] = '-';
            studentRecords[key]['date_of_birth'] = InstitutionsStudentsSvc.formatDate(studentRecords[key][mapping.date_of_birth_mapping]);
            studentRecords[key]['gender_name'] = studentRecords[key][mapping.gender_mapping];
            studentRecords[key]['gender'] = {'name': studentRecords[key][mapping.gender_mapping]};
            studentRecords[key]['identity_type_name'] = studentRecords[key][mapping.identity_type_mapping];
            studentRecords[key]['identity_number'] = studentRecords[key][mapping.identity_number_mapping];
            studentRecords[key]['nationality_name'] = studentRecords[key][mapping.nationality_mapping];
            studentRecords[key]['name'] = '';
            if (studentRecords[key].hasOwnProperty(mapping.first_name_mapping)) {
                studentRecords[key]['name'] = studentRecords[key][mapping.first_name_mapping];
            }
            StaffController.appendName(studentRecords[key], mapping.middle_name_mapping);
            StaffController.appendName(studentRecords[key], mapping.third_name_mapping);
            StaffController.appendName(studentRecords[key], mapping.last_name_mapping);
        }

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = studentRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        StaffController.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return studentRecords;
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
                StaffController.appendName(studentRecords[key], 'middle_name');
                StaffController.appendName(studentRecords[key], 'third_name');
                StaffController.appendName(studentRecords[key], 'last_name');
            }
        }

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = studentRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        StaffController.externalDataLoaded = true;
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
            StaffController.postResponse = postResponse.data;
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
                        StaffController.selectedStudentData = userRecord;
                        StaffController.completeDisabled = true;
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
        StaffController.createNewStudent = true;
        StaffController.completeDisabled = false;
        StaffController.selectedStudentData = {};
        StaffController.selectedStudentData.first_name = '';
        StaffController.selectedStudentData.last_name = '';
        StaffController.selectedStudentData.date_of_birth = '';
        StaffController.initNationality();
        StaffController.initIdentityType();
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
        StaffController.selectedStudent = id;
        StaffController.getStudentData();
    }

    function setStudentName() {
        var studentData = StaffController.selectedStudentData;
        studentData.name = '';

        if (studentData.hasOwnProperty('first_name')) {
            studentData.name = studentData.first_name.trim();
        }
        StaffController.appendName(studentData, 'middle_name', true);
        StaffController.appendName(studentData, 'third_name', true);
        StaffController.appendName(studentData, 'last_name', true);
        StaffController.selectedStudentData = studentData;
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
        var studentData = StaffController.selectedStudentData;
        if (studentData.hasOwnProperty('gender_id')) {
            var genderOptions = StaffController.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == studentData.gender_id) {
                    studentData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StaffController.selectedStudentData = studentData;
        }
    }

    function getStudentData() {
        var log = [];
        angular.forEach(StaffController.rowsThisPage , function(value) {
            if (value.id == StaffController.selectedStudent) {
                StaffController.selectedStudentData = value;
            }
        }, log);
    }

    function onChangeAcademicPeriod() {
        AlertSvc.reset($scope);

        if (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
            $scope.endDate = InstitutionsStudentsSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.end_date);
            StaffController.startDate = InstitutionsStudentsSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date);
        }

        var startDatePicker = angular.element(document.getElementById('Students_start_date'));
        startDatePicker.datepicker("setStartDate", InstitutionsStudentsSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
        startDatePicker.datepicker("setEndDate", InstitutionsStudentsSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.end_date));
        startDatePicker.datepicker("setDate", InstitutionsStudentsSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));

        StaffController.educationGradeOptions = null;
        InstitutionsStudentsSvc.getEducationGrades({
            institutionId: StaffController.institutionId,
            academicPeriodId: StaffController.academicPeriodOptions.selectedOption.id
        })
        .then(function(educationGrades) {
            StaffController.educationGradeOptions = {
                availableOptions: educationGrades,
            };
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    }

    function onChangeEducationGrade() {
        AlertSvc.reset($scope);

        StaffController.classOptions = null;

        InstitutionsStudentsSvc.getClasses({
            institutionId: StaffController.institutionId,
            academicPeriodId: StaffController.academicPeriodOptions.selectedOption.id,
            gradeId: StaffController.educationGradeOptions.selectedOption.education_grade_id
        })
        .then(function(classes) {
            StaffController.classOptions = {
                availableOptions: classes,
            };
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    }

    function postForm() {

        var academicPeriodId = (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption'))? StaffController.academicPeriodOptions.selectedOption.id: '';
        var educationGradeId = (StaffController.educationGradeOptions.hasOwnProperty('selectedOption'))? StaffController.educationGradeOptions.selectedOption.education_grade_id: '';
        var classId = null;
        if (StaffController.classOptions.hasOwnProperty('selectedOption')) {
            classId = StaffController.classOptions.selectedOption.id;
        }
        var startDate = StaffController.startDate;
        var startDateArr = startDate.split("-");
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        for(i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var endDate = $scope.endDate;

        if (!StaffController.createNewStudent) {
            if (StaffController.externalSearch) {
                var studentData = StaffController.selectedStudentData;
                var amendedStudentData = Object.assign({}, studentData);
                amendedStudentData.date_of_birth = InstitutionsStudentsSvc.formatDate(amendedStudentData.date_of_birth);
                StaffController.addStudentUser(amendedStudentData, academicPeriodId, educationGradeId, classId, startDate, endDate);
            } else {
                var studentId = StaffController.selectedStudent;
                StaffController.insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate, {});
            }
        } else {
            console.log('postForm');
            if (StaffController.selectedStudentData != null) {
                console.log('not null');
                var studentData = {};
                var log = [];
                angular.forEach(StaffController.selectedStudentData, function(value, key) {
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
                StaffController.addStudentUser(studentData, academicPeriodId, educationGradeId, classId, startDate, endDate);
            }
        }
    }

    function addStudentUser(studentData, academicPeriodId, educationGradeId, classId, startDate, endDate) {

        var newStudentData = studentData;
        newStudentData['academic_period_id'] = academicPeriodId;
        newStudentData['education_grade_id'] = educationGradeId;
        newStudentData['start_date'] = startDate;
        newStudentData['nationality_id'] = StaffController.Student.nationality_id;
        newStudentData['identity_type_id'] = StaffController.Student.identity_type_id;
        InstitutionsStudentsSvc.addUser(newStudentData)
        .then(function(user){
            if (user[0].error.length === 0) {
                var studentId = user[0].data.id;
                StaffController.insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate, user[1]);
            } else {
                StaffController.postResponse = user[0];
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

        if (angular.isDefined(StaffController.postResponse)){
            delete StaffController.postResponse;
            $scope.$apply();
        }
        // To go to add student page if there is a student selected from the internal search
        // or external search
        if (data.step == 3 && data.direction == 'next') {
            if (StaffController.validateNewUser()) {
                evt.preventDefault();
            };
        }
    });

    function validateNewUser() {
        var remain = false;
        var empty = {'_empty': 'This field cannot be left empty'};
        StaffController.postResponse = {};
        StaffController.postResponse.error = {};
        if (StaffController.selectedStudentData.first_name == '') {
            StaffController.postResponse.error.first_name = empty;
            remain = true;
        }

        if (StaffController.selectedStudentData.last_name == '') {
            StaffController.postResponse.error.last_name = empty;
            remain = true;
        }
        if (StaffController.selectedStudentData.gender_id == '' || StaffController.selectedStudentData.gender_id == null) {
            StaffController.postResponse.error.gender_id = empty;
            remain = true;
        }

        if (StaffController.selectedStudentData.date_of_birth == '') {
            StaffController.postResponse.error.date_of_birth = empty;
            remain = true;
        }

        if (StaffController.StudentNationalities == 1 && (StaffController.Student.nationality_id == '' || StaffController.Student.nationality_id == undefined)) {
            remain = true;
        }

        var arrNumber = [{}];

        // if (StaffController.StudentIdentities == 1 && (StaffController.Student.identity_type_id == '' || StaffController.Student.identity_type_id == undefined)) {
        //     arrNumber[0]['identity_type_id'] = empty;
        //     StaffController.postResponse.error.identities = arrNumber;
        //     remain = true;
        // }
        if (StaffController.StudentIdentities == 1 && (StaffController.selectedStudentData.identity_number == '' || StaffController.selectedStudentData.identity_number == undefined)) {
            arrNumber[0]['number'] = empty;
            StaffController.postResponse.error.identities = arrNumber;
            remain = true;
        }

        var arrNationality = [{}];
        if (StaffController.StudentNationalities == 1 && (StaffController.Student.nationality_id == '' || StaffController.Student.nationality_id == undefined)) {
            arrNationality[0]['nationality_id'] = empty;
            StaffController.postResponse.error.nationalities = arrNationality;
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
            StaffController.selectedStudentData.openemis_no = response;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    angular.element(document.querySelector('#wizard')).on('finished.fu.wizard', function(evt, data) {
        StaffController.postForm();
    });

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function(evt, data) {
        StaffController.addStudentButton = false;
        // Step 1 - Internal search
        if (data.step == 1) {
            StaffController.Student.identity_type_name = StaffController.defaultIdentityTypeName;
            StaffController.Student.identity_type_id = StaffController.defaultIdentityTypeId;
            StaffController.educationGradeOptions.selectedOption = '';
            StaffController.classOptions.selectedOption = '';
            delete StaffController.postResponse;
            StaffController.reloadInternalDatasource(true);
            StaffController.createNewStudent = false;
            StaffController.externalSearch = false;
            StaffController.step = 'internal_search';
        }
        // Step 2 - External search
        else if (data.step == 2) {
            StaffController.Student.identity_type_name = StaffController.externalIdentityType;
            StaffController.Student.identity_type_id = StaffController.defaultIdentityTypeId;
            StaffController.educationGradeOptions.selectedOption = '';
            StaffController.classOptions.selectedOption = '';
            delete StaffController.postResponse;
            StaffController.reloadExternalDatasource(true);
            StaffController.createNewStudent = false;
            StaffController.externalSearch = true;
            StaffController.step = 'external_search';
        }
        // Step 3 - Create user
        else if (data.step == 3) {
            StaffController.externalSearch = false;
            StaffController.createNewStudent = true;
            StaffController.step = 'create_user';
            StaffController.getUniqueOpenEmisId();
            InstitutionsStudentsSvc.resetExternalVariable();
        }
        // Step 4 - Add Student
        else {
            if (StaffController.externalSearch) {
                StaffController.getUniqueOpenEmisId();
            }
            studentData = StaffController.selectedStudentData;
            StaffController.completeDisabled = false;
            if (studentData.hasOwnProperty('institution_students')) {
                if (studentData.institution_students.length > 0) {
                    var schoolName = studentData['institution_students'][0]['institution']['name'];
                    AlertSvc.warning($scope, 'This student is already allocated to ' + schoolName);
                    StaffController.completeDisabled = true;
                }
            }
            StaffController.step = 'add_student';
        }
    });


}
