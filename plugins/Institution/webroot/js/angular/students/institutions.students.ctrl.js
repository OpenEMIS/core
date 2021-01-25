angular
    .module('institutions.students.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.students.svc'])
    .controller('InstitutionsStudentsCtrl', InstitutionStudentController);

InstitutionStudentController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStudentsSvc', '$rootScope'];

function InstitutionStudentController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStudentsSvc, $rootScope) {
    // ag-grid vars


    var StudentController = this;
    var test = $scope;

    var pageSize = 10;

    // Variables
    StudentController.externalSearch = false;
    StudentController.hasExternalDataSource;
    StudentController.internalGridOptions = null;
    StudentController.externalGridOptions = null;
    StudentController.rowsThisPage = [];
    StudentController.createNewStudent = false;
    StudentController.genderOptions = {};
    StudentController.translatedTexts = {};
    StudentController.academicPeriodOptions = {};
    StudentController.educationGradeOptions = {};
    StudentController.classOptions = {};
    StudentController.transferReasonOptions = {};
    StudentController.step = 'internal_search';
    StudentController.showExternalSearchButton = false;
    StudentController.existingStudent = false;
    StudentController.studentTransferable = false;
    StudentController.institutionId = null;

    // 0 - Non-mandatory, 1 - Mandatory, 2 - Excluded
    StudentController.StudentContacts = 2;
    StudentController.StudentIdentities = 2;
    StudentController.StudentNationalities = 2;
    StudentController.StudentSpecialNeeds = 2;
    StudentController.StudentContactsOptions = [];
    StudentController.StudentIdentitiesOptions = [];
    StudentController.StudentNationalitiesOptions = [];
    StudentController.StudentSpecialNeedsOptions = [];
    StudentController.Student = {};
    StudentController.Student.nationality_id = '';
    StudentController.Student.nationality_name = '';
    StudentController.Student.identity_type_id = '';
    StudentController.Student.identity_type_name = '';
    StudentController.Student.nationality_class = 'input select error';
    StudentController.Student.identity_type_class = 'input select error';
    StudentController.Student.identity_class = 'input string';


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
    StudentController.changeIdentityType = changeIdentityType;
    StudentController.processStudentRecord = processStudentRecord;
    StudentController.processExternalStudentRecord = processExternalStudentRecord;
    StudentController.createNewInternalDatasource = createNewInternalDatasource;
    StudentController.createNewExternalDatasource = createNewExternalDatasource;
    StudentController.insertStudentData = insertStudentData;
    StudentController.onChangeAcademicPeriod = onChangeAcademicPeriod;
    StudentController.onChangeEducationGrade = onChangeEducationGrade;
    StudentController.getStudentData = getStudentData;
    StudentController.selectStudent = selectStudent;
    StudentController.postForm = postForm;
    StudentController.postTransferForm = postTransferForm;
    StudentController.addStudentUser = addStudentUser;
    StudentController.setStudentName = setStudentName;
    StudentController.appendName = appendName;
    StudentController.changeGender = changeGender;
    StudentController.validateNewUser = validateNewUser;
    StudentController.onExternalSearchClick = onExternalSearchClick;
    StudentController.onAddNewStudentClick = onAddNewStudentClick;
    StudentController.onAddStudentClick = onAddStudentClick;
    StudentController.onAddStudentCompleteClick = onAddStudentCompleteClick;
    StudentController.onTransferStudentClick = onTransferStudentClick;
    StudentController.getUniqueOpenEmisId = getUniqueOpenEmisId;
    StudentController.generatePassword = generatePassword;
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
    StudentController.defaultIdentityTypeId;
    StudentController.postResponse;

    angular.element(document).ready(function() {
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

                return $q.all(promises);
            }, function(error) {
                console.log(error);
                AlertSvc.warning($scope, error);
                UtilsSvc.isAppendLoader(false);
            })
            .then(function(promisesObj) {
                var promises = [];
                var addNewStudentConfig = promisesObj[0].data;
                for (i = 0; i < addNewStudentConfig.length; i++) {
                    var code = addNewStudentConfig[i].code;
                    StudentController[code] = addNewStudentConfig[i].value;
                }
                if (StudentController.StudentContacts != 2) {
                    promises[2] = InstitutionsStudentsSvc.getUserContactTypes();
                }
                if (StudentController.StudentNationalities != 2) {
                    if (StudentController.StudentNationalities == 1) {
                        StudentController.Student.nationality_class = StudentController.Student.nationality_class + ' required';
                    }
                    promises[3] = InstitutionsStudentsSvc.getNationalities();
                }
                if (StudentController.StudentIdentities != 2) {
                    if (StudentController.StudentIdentities == 1) {
                        StudentController.Student.identity_class = StudentController.Student.identity_class + ' required';
                        StudentController.Student.identity_type_class = StudentController.Student.identity_type_class + ' required';
                    }
                    promises[4] = InstitutionsStudentsSvc.getIdentityTypes();
                }
                if (StudentController.StudentSpecialNeeds != 2) {
                    promises[5] = InstitutionsStudentsSvc.getSpecialNeedTypes();
                }
                promises[0] = InstitutionsStudentsSvc.getGenders();
                var translateFields = {
                    'openemis_no': 'OpenEMIS ID',
                    'name': 'Name',
                    'gender_name': 'Gender',
                    'date_of_birth': 'Date Of Birth',
                    'nationality_name': 'Nationality',
                    'identity_type_name': 'Identity Type',
                    'identity_number': 'Identity Number',
                    'account_type': 'Account Type'
                };
                promises[1] = InstitutionsStudentsSvc.translate(translateFields);

                return $q.all(promises);
            }, function(error) {
                console.log(error);
                AlertSvc.warning($scope, error);
                UtilsSvc.isAppendLoader(false);
            })
            .then(function(promisesObj) {
                StudentController.genderOptions = promisesObj[0];
                StudentController.translatedTexts = promisesObj[1];
                // User Contacts
                if (promisesObj[2] != undefined && promisesObj[2].hasOwnProperty('data')) {
                    StudentController.StudentContactsOptions = promisesObj[2]['data'];
                }
                // User Nationalities
                if (promisesObj[3] != undefined && promisesObj[3].hasOwnProperty('data')) {
                    StudentController.StudentNationalitiesOptions = promisesObj[3]['data'];
                }
                // User Identities
                if (promisesObj[4] != undefined && promisesObj[4].hasOwnProperty('data')) {
                    StudentController.StudentIdentitiesOptions = promisesObj[4]['data'];
                }
                // User Special Needs
                if (promisesObj[5] != undefined && promisesObj[5].hasOwnProperty('data')) {
                    StudentController.StudentSpecialNeedsOptions = promisesObj[5]['data'];
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
                    AlertSvc.success($scope, 'The student is added successfully.');
                } else if ($location.search().student_transfer_added) {
                    AlertSvc.success($scope, 'Student transfer request is added successfully.');
                } else if ($location.search().transfer_exists) {
                    AlertSvc.warning($scope, 'There is an existing transfer record for this student.');
                }
            });

    });

    function initNationality() {
        StudentController.Student.nationality_id = '';
        var options = StudentController.StudentNationalitiesOptions;
        for (var i = 0; i < options.length; i++) {
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
        var identityOptions = StudentController.StudentIdentitiesOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == nationalityId) {
                if (options[i].identity_type_id == null) {
                    StudentController.Student.identity_type_id = identityOptions['0'].id;
                    StudentController.Student.identity_type_name = identityOptions['0'].name;
                } else {
                    StudentController.Student.identity_type_id = options[i].identity_type_id;
                    StudentController.Student.identity_type_name = options[i].identity_type.name;
                }
                StudentController.Student.nationality_name = options[i].name;
                break;
            }
        }
    }

    function changeIdentityType() {
        var identityType = StudentController.Student.identity_type_id;
        var options = StudentController.StudentIdentitiesOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == identityType) {
                StudentController.Student.identity_type_name = options[i].name;
                break;
            }
        }
    }

    function initIdentityType() {
        if (StudentController.Student.nationality_id == '') {
            var options = StudentController.StudentIdentitiesOptions;
            for (var i = 0; i < options.length; i++) {
                if (options[i].default == 1) {
                    StudentController.Student.identity_type_id = options[i].id;
                    StudentController.Student.identity_type_name = options[i].name;
                    break;
                }
            }
        }
    }

    $scope.initGrid = function() {
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function(localeText) {
                StudentController.internalGridOptions = {
                    columnDefs: [{
                            headerName: StudentController.translatedTexts.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.gender_name,
                            field: "gender_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.nationality_name,
                            field: "nationality_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.identity_type_name,
                            field: "identity_type_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.account_type,
                            field: "account_type",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    localeText: localeText,
                    enableColResize: true,
                    enableFilter: false,
                    enableServerSideFilter: true,
                    enableServerSideSorting: true,
                    enableSorting: false,
                    headerHeight: 38,
                    rowData: [],
                    rowHeight: 38,
                    rowModelType: 'infinite',
                    // Removed options - Issues in ag-Grid AG-828
                    // suppressCellSelection: true,

                    // Added options
                    suppressContextMenu: true,
                    stopEditingWhenGridLosesFocus: true,
                    ensureDomOrder: true,
                    pagination: true,
                    paginationPageSize: 10,
                    maxBlocksInCache: 1,
                    cacheBlockSize: 10,
                    // angularCompileRows: true,
                    onRowSelected: (_e) => {
                        StudentController.selectStudent(_e.node.data.id);
                        $scope.$apply();
                    }
                };

                StudentController.externalGridOptions = {
                    columnDefs: [{
                            headerName: StudentController.translatedTexts.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.gender_name,
                            field: "gender_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.nationality_name,
                            field: "nationality_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.identity_type_name,
                            field: "identity_type_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    localeText: localeText,
                    enableColResize: false,
                    enableFilter: false,
                    enableServerSideFilter: true,
                    enableServerSideSorting: true,
                    enableSorting: false,
                    headerHeight: 38,
                    rowData: [],
                    rowHeight: 38,
                    rowModelType: 'infinite',
                    // Removed options - Issues in ag-Grid AG-828
                    // suppressCellSelection: true,

                    // Added options
                    suppressContextMenu: true,
                    stopEditingWhenGridLosesFocus: true,
                    ensureDomOrder: true,
                    pagination: true,
                    paginationPageSize: 10,
                    maxBlocksInCache: 1,
                    cacheBlockSize: 10,
                    // angularCompileRows: true,
                    onRowSelected: (_e) => {
                        StudentController.selectStudent(_e.node.data.id);
                        $scope.$apply();
                    }
                };
            }, function(error) {
                StudentController.internalGridOptions = {
                    columnDefs: [{
                            headerName: StudentController.translatedTexts.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.gender_name,
                            field: "gender_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.nationality_name,
                            field: "nationality_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.identity_type_name,
                            field: "identity_type_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    enableColResize: false,
                    enableFilter: false,
                    enableServerSideFilter: true,
                    enableServerSideSorting: true,
                    enableSorting: false,
                    headerHeight: 38,
                    rowData: [],
                    rowHeight: 38,
                    rowModelType: 'infinite',
                    // Removed options - Issues in ag-Grid AG-828
                    // suppressCellSelection: true,

                    // Added options
                    suppressContextMenu: true,
                    stopEditingWhenGridLosesFocus: true,
                    ensureDomOrder: true,
                    pagination: true,
                    paginationPageSize: 10,
                    maxBlocksInCache: 1,
                    cacheBlockSize: 10,
                    // angularCompileRows: true,
                    onRowSelected: (_e) => {
                        StudentController.selectStudent(_e.node.data.id);
                        $scope.$apply();
                    }
                };

                StudentController.externalGridOptions = {
                    columnDefs: [{
                            headerName: StudentController.translatedTexts.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.gender_name,
                            field: "gender_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.nationality_name,
                            field: "nationality_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.identity_type_name,
                            field: "identity_type_name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: StudentController.translatedTexts.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    enableColResize: false,
                    enableFilter: false,
                    enableServerSideFilter: true,
                    enableServerSideSorting: true,
                    enableSorting: false,
                    headerHeight: 38,
                    rowData: [],
                    rowHeight: 38,
                    rowModelType: 'infinite',
                    // Removed options - Issues in ag-Grid AG-828
                    // suppressCellSelection: true,

                    // Added options
                    suppressContextMenu: true,
                    stopEditingWhenGridLosesFocus: true,
                    ensureDomOrder: true,
                    pagination: true,
                    paginationPageSize: 10,
                    maxBlocksInCache: 1,
                    cacheBlockSize: 10,
                    // angularCompileRows: true,
                    onRowSelected: (_e) => {
                        StudentController.selectStudent(_e.node.data.id);
                        $scope.$apply();
                    }
                };
            });
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

    $scope.$watch('endDate', function(newValue) {
        StudentController.endDateFormatted = $filter('date')(newValue, 'dd-MM-yyyy');
    });

    function createNewInternalDatasource(gridObj, withData) {
        var dataSource = {
            pageSize: pageSize,
            getRows: function(params) {
                AlertSvc.reset($scope);
                delete StudentController.selectedStudent;
                if (withData) {
                    InstitutionsStudentsSvc.getStudentRecords({
                            startRow: params.startRow,
                            endRow: params.endRow,
                            conditions: {
                                openemis_no: StudentController.internalFilterOpenemisNo,
                                first_name: StudentController.internalFilterFirstName,
                                last_name: StudentController.internalFilterLastName,
                                identity_number: StudentController.internalFilterIdentityNumber,
                                date_of_birth: StudentController.internalFilterDateOfBirth,
                            }
                        })
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
            getRows: function(params) {
                AlertSvc.reset($scope);
                // delete StudentController.selectedStudent;
                if (withData) {
                    InstitutionsStudentsSvc.getExternalStudentRecords({
                            startRow: params.startRow,
                            endRow: params.endRow,
                            conditions: {
                                first_name: StudentController.internalFilterFirstName,
                                last_name: StudentController.internalFilterLastName,
                                identity_number: StudentController.internalFilterIdentityNumber,
                                date_of_birth: StudentController.internalFilterDateOfBirth
                            }
                        })
                        .then(function(response) {
                            var studentRecords = response.data;
                            var totalRowCount = response.total;
                            StudentController.initialLoad = false;
                            return StudentController.processExternalStudentRecord(studentRecords, params, totalRowCount);
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
                            return StudentController.processExternalStudentRecord(studentRecords, params, 0);
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

    function processExternalStudentRecord(studentRecords, params, totalRowCount) {
        for (var key in studentRecords) {
            var mapping = InstitutionsStudentsSvc.getExternalSourceMapping();
            studentRecords[key]['institution_name'] = '-';
            studentRecords[key]['academic_period_name'] = '-';
            studentRecords[key]['education_grade_name'] = '-';
            studentRecords[key]['date_of_birth'] = InstitutionsStudentsSvc.formatDate(studentRecords[key][mapping.date_of_birth_mapping]);
            studentRecords[key]['gender_name'] = studentRecords[key][mapping.gender_mapping];
            studentRecords[key]['gender'] = {
                'name': studentRecords[key][mapping.gender_mapping]
            };
            studentRecords[key]['identity_type_name'] = studentRecords[key][mapping.identity_type_mapping];
            studentRecords[key]['identity_number'] = studentRecords[key][mapping.identity_number_mapping];
            studentRecords[key]['nationality_name'] = studentRecords[key][mapping.nationality_mapping];
            studentRecords[key]['nationality_name'] = studentRecords[key][mapping.nationality_mapping];
            studentRecords[key]['address'] = studentRecords[key][mapping.address_mapping];
            studentRecords[key]['postal_code'] = studentRecords[key][mapping.postal_mapping];
            studentRecords[key]['name'] = '';
            if (studentRecords[key].hasOwnProperty(mapping.first_name_mapping)) {
                studentRecords[key]['name'] = studentRecords[key][mapping.first_name_mapping];
            }
            StudentController.appendName(studentRecords[key], mapping.middle_name_mapping);
            StudentController.appendName(studentRecords[key], mapping.third_name_mapping);
            StudentController.appendName(studentRecords[key], mapping.last_name_mapping);
        }

        var lastRow = totalRowCount;
        StudentController.rowsThisPage = studentRecords;

        params.successCallback(StudentController.rowsThisPage, lastRow);
        StudentController.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return studentRecords;
    }

    function processStudentRecord(studentRecords, params, totalRowCount) {
        for (var key in studentRecords) {
            studentRecords[key]['institution_name'] = '-';
            studentRecords[key]['academic_period_name'] = '-';
            studentRecords[key]['education_grade_name'] = '-';
            if ((studentRecords[key].hasOwnProperty('institution_students') && studentRecords[key]['institution_students'].length > 0)) {
                studentRecords[key]['institution_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('institution'))) ? studentRecords[key].institution_students['0'].institution.name : '-';
                studentRecords[key]['academic_period_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('academic_period'))) ? studentRecords[key].institution_students['0'].academic_period.name : '-';
                studentRecords[key]['education_grade_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('education_grade'))) ? studentRecords[key].institution_students['0'].education_grade.name : '-';
            }

            studentRecords[key]['date_of_birth'] = InstitutionsStudentsSvc.formatDate(studentRecords[key]['date_of_birth']);
            studentRecords[key]['gender_name'] = studentRecords[key]['gender']['name'];

            if (studentRecords[key]['is_student'] == 1 && studentRecords[key]['is_staff'] == 1) {
                studentRecords[key]['account_type'] = 'Student, Staff';
            } else if (studentRecords[key]['is_student'] == 1 && studentRecords[key]['is_staff'] == 0) {
                studentRecords[key]['account_type'] = 'Student';
            } else if (studentRecords[key]['is_student'] == 0 && studentRecords[key]['is_staff'] == 1) {
                studentRecords[key]['account_type'] = 'Staff';
            }

            if (studentRecords[key]['main_nationality'] != null) {
                studentRecords[key]['nationality_name'] = studentRecords[key]['main_nationality']['name'];
            }
            if (studentRecords[key]['main_identity_type'] != null) {
                studentRecords[key]['identity_type_name'] = studentRecords[key]['main_identity_type']['name'];
            }

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
            academic_period_id: academicPeriodId,
            education_grade_id: educationGradeId,
            start_date: startDate,
            end_date: endDate,
            institution_class_id: classId
        };

        InstitutionsStudentsSvc.postEnrolledStudent(data)
            .then(function(postResponse) {
                StudentController.postResponse = postResponse.data;
                UtilsSvc.isAppendLoader(false);
                if (postResponse.data.error.length === 0) {
                    AlertSvc.success($scope, 'The student is added successfully.');
                    $window.location.href = 'add?student_added=true';
                } else if (userRecord.hasOwnProperty('institution_students') && userRecord.institution_students.length > 0) {
                    userRecord.date_of_birth = InstitutionsStudentsSvc.formatDate(userRecord.date_of_birth);
                    StudentController.selectedStudentData = userRecord;
                    StudentController.existingStudent = true;

                    var schoolId = userRecord['institution_students'][0]['institution_id'];
                    if (StudentController.institutionId != schoolId) {
                        StudentController.studentTransferable = true;
                        var schoolName = userRecord['institution_students'][0]['institution']['code_name'];
                        AlertSvc.warning($scope, 'This student is already allocated to %s', [schoolName]);
                    } else {
                        AlertSvc.warning($scope, 'This student is already allocated to the current institution');
                    }
                } else {
                    AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                }
            }, function(error) {
                console.log(error);
                AlertSvc.warning($scope, error);
            });
    }

    function onAddNewStudentClick() {
        StudentController.createNewStudent = true;
        StudentController.studentTransferable = false;
        StudentController.existingStudent = false;
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

    function onAddStudentCompleteClick() {
        StudentController.postForm();
    }

    function onExternalSearchClick() {
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "externalSearch"
        });
    }

    function onTransferStudentClick() {
        // setup transfer student input fields
        var studentData = StudentController.selectedStudentData;
        var periodEndDate = InstitutionsStudentsSvc.formatDate(studentData['institution_students'][0]['academic_period']['end_date']);

        // only allow transfer start date to be one day after the student's current start date
        var studentStartDate = new Date(studentData['institution_students'][0]['start_date']);
        studentStartDate.setDate(studentStartDate.getDate() + 1);
        var studentStartDateFormatted = $filter('date')(studentStartDate, 'dd-MM-yyyy');

        StudentController.startDate = studentStartDateFormatted;
        $scope.endDate = periodEndDate;

        angular.forEach(StudentController.educationGradeOptions.availableOptions, function(value, key) {
            if (value.education_grade_id == studentData['institution_students'][0]['education_grade_id']) {
                StudentController.educationGradeOptions.selectedOption = StudentController.educationGradeOptions.availableOptions[key];
            }
        });

        var startDatePicker = angular.element(document.getElementById('Students_transfer_start_date'));
        startDatePicker.datepicker("setStartDate", studentStartDateFormatted);
        startDatePicker.datepicker("setEndDate", periodEndDate);
        startDatePicker.datepicker("setDate", studentStartDateFormatted);

        StudentController.classOptions = {};
        StudentController.transferReasonOptions = {};

        InstitutionsStudentsSvc.getClasses({
                institutionId: StudentController.institutionId,
                academicPeriodId: studentData['institution_students'][0]['academic_period_id'],
                gradeId: studentData['institution_students'][0]['education_grade_id'],
            })
            .then(function(classes) {

                StudentController.classOptions = {
                    availableOptions: classes,
                };

                return InstitutionsStudentsSvc.getStudentTransferReasons();
            }, function(error) {
                console.log(error);
            })
            .then(function(response) {
                if (angular.isDefined(response) && response.hasOwnProperty('data')) {
                    StudentController.transferReasonOptions = {
                        availableOptions: response.data
                    };
                }
            }, function(error) {
                console.log(error);
            })
            .finally(function(result) {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: "transferStudent"
                });
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
            for (var i = 0; i < genderOptions.length; i++) {
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
        angular.forEach(StudentController.rowsThisPage, function(value) {
            if (value.id == StudentController.selectedStudent) {
                StudentController.selectedStudentData = value;
            }
        }, log);
    }

    function onChangeAcademicPeriod() {
        AlertSvc.reset($scope);

        if (StudentController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
            $scope.endDate = InstitutionsStudentsSvc.formatDate(StudentController.academicPeriodOptions.selectedOption.end_date);
            StudentController.startDate = InstitutionsStudentsSvc.formatDate(StudentController.academicPeriodOptions.selectedOption.start_date);
        }

        var startDatePicker = angular.element(document.getElementById('Students_start_date'));
        startDatePicker.datepicker("setStartDate", InstitutionsStudentsSvc.formatDate(StudentController.academicPeriodOptions.selectedOption.start_date));
        startDatePicker.datepicker("setEndDate", InstitutionsStudentsSvc.formatDate(StudentController.academicPeriodOptions.selectedOption.end_date));
        startDatePicker.datepicker("setDate", InstitutionsStudentsSvc.formatDate(StudentController.academicPeriodOptions.selectedOption.start_date));

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
        var academicPeriodId = (StudentController.academicPeriodOptions.hasOwnProperty('selectedOption')) ? StudentController.academicPeriodOptions.selectedOption.id : '';
        var educationGradeId = (StudentController.educationGradeOptions.hasOwnProperty('selectedOption')) ? StudentController.educationGradeOptions.selectedOption.education_grade_id : '';
        if (educationGradeId == undefined) {
            educationGradeId = '';
        }
        var classId = null;
        if (StudentController.classOptions.hasOwnProperty('selectedOption')) {
            classId = StudentController.classOptions.selectedOption.id;
        }
        var startDate = StudentController.startDate;
        var startDateArr = startDate.split("-");
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        for (i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var endDate = $scope.endDate;

        if (!StudentController.createNewStudent) {
            if (StudentController.externalSearch) {
                var studentData = StudentController.selectedStudentData;
                var amendedStudentData = Object.assign({}, studentData);
                amendedStudentData.date_of_birth = InstitutionsStudentsSvc.formatDate(amendedStudentData.date_of_birth);
                StudentController.addStudentUser(amendedStudentData, academicPeriodId, educationGradeId, classId, startDate, endDate);
            } else {
                var studentId = StudentController.selectedStudent;
                StudentController.insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate, {});
            }
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
                delete studentData['id'];
                delete studentData['institution_students'];
                delete studentData['is_staff'];
                delete studentData['is_guardian'];
                delete studentData['address'];
                delete studentData['postal_code'];
                delete studentData['address_area_id'];
                delete studentData['birthplace_area_id'];
                delete studentData['date_of_death'];
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

    function postTransferForm() {
        var transferReasonId = (StudentController.transferReasonOptions.hasOwnProperty('selectedOption')) ? StudentController.transferReasonOptions.selectedOption.id : null;
        var classId = (StudentController.classOptions.hasOwnProperty('selectedOption')) ? StudentController.classOptions.selectedOption.id : null;
        var educationGradeId = (StudentController.educationGradeOptions.hasOwnProperty('selectedOption')) ? StudentController.educationGradeOptions.selectedOption.education_grade_id : '';

        if (educationGradeId == undefined) {
            educationGradeId = '';
        }

        var startDate = StudentController.startDate;
        var startDateArr = startDate.split("-");
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        for (i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var endDate = $scope.endDate;

        var data = {
            start_date: startDate,
            end_date: endDate,
            student_id: StudentController.selectedStudent,
            status_id: 0,
            assignee_id: -1,
            institution_id: StudentController.institutionId,
            academic_period_id: StudentController.selectedStudentData.institution_students[0]['academic_period_id'],
            education_grade_id: educationGradeId,
            institution_class_id: classId,
            previous_institution_id: StudentController.selectedStudentData.institution_students[0]['institution_id'],
            previous_academic_period_id: StudentController.selectedStudentData.institution_students[0]['academic_period_id'],
            previous_education_grade_id: StudentController.selectedStudentData.institution_students[0]['education_grade_id'],
            student_transfer_reason_id: transferReasonId,
            comment: StudentController.comment
        };

        InstitutionsStudentsSvc.addStudentTransferRequest(data)
            .then(function(postResponse) {
                StudentController.postResponse = postResponse.data;
                var counter = 0;
                angular.forEach(postResponse.data.error, function(value) {
                    counter++;
                });

                if (counter == 0) {
                    AlertSvc.success($scope, 'Student transfer request is added successfully.');
                    $window.location.href = 'add?student_transfer_added=true';
                } else if (counter == 1 && postResponse.data.error.hasOwnProperty('student_transfer') && postResponse.data.error.student_transfer.hasOwnProperty('ruleTransferRequestExists')) {
                    AlertSvc.warning($scope, 'There is an existing transfer record for this student.');
                    $window.location.href = postResponse.data.error.student_transfer.ruleTransferRequestExists;
                } else {
                    AlertSvc.error($scope, 'There is an error in adding student transfer request.');
                }
            }, function(error) {
                console.log(error);
                AlertSvc.error($scope, 'There is an error in adding student transfer request.');
            });
    }

    function addStudentUser(studentData, academicPeriodId, educationGradeId, classId, startDate, endDate) {
        var newStudentData = studentData;
        newStudentData['academic_period_id'] = academicPeriodId;
        newStudentData['education_grade_id'] = educationGradeId;
        newStudentData['start_date'] = startDate;
        newStudentData['institution_id'] = StudentController.institutionId;
        if (!StudentController.externalSearch) {
            newStudentData['nationality_id'] = StudentController.Student.nationality_id;
            newStudentData['identity_type_id'] = StudentController.Student.identity_type_id;
        }
        InstitutionsStudentsSvc.addUser(newStudentData)
            .then(function(user) {
                if (user[0].error.length === 0) {
                    var studentId = user[0].data.id;
                    StudentController.insertStudentData(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate, user[1]);
                } else {
                    StudentController.postResponse = user[0];
                    console.log(user[0]);
                    AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                }
            }, function(error) {
                console.log(error);
                AlertSvc.warning($scope, error);
            });
    }


    angular.element(document.querySelector('#wizard')).on('actionclicked.fu.wizard', function(evt, data) {
        // evt.preventDefault();
        AlertSvc.reset($scope);

        if (angular.isDefined(StudentController.postResponse)) {
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
        var empty = {
            '_empty': 'This field cannot be left empty'
        };
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

        if (StudentController.selectedStudentData.username == '' || StudentController.selectedStudentData.username == undefined) {
            StudentController.postResponse.error.username = empty;
            remain = true;
        }

        if (StudentController.selectedStudentData.password == '' || StudentController.selectedStudentData.password == undefined) {
            StudentController.postResponse.error.password = empty;
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
                var username = StudentController.selectedStudentData.username;
                //POCOR-5878 starts
                if(username != StudentController.selectedStudentData.openemis_no && (username == '' || typeof username == 'undefined')){
                    StudentController.selectedStudentData.username = StudentController.selectedStudentData.openemis_no;
                    StudentController.selectedStudentData.openemis_no = StudentController.selectedStudentData.openemis_no;
                }else{
                    if(username == StudentController.selectedStudentData.openemis_no){
                        StudentController.selectedStudentData.username = response;
                    }
                    StudentController.selectedStudentData.openemis_no = response;
                }
                //POCOR-5878 ends
                UtilsSvc.isAppendLoader(false);
            }, function(error) {
                console.log(error);
                UtilsSvc.isAppendLoader(false);
            });
    }

    function generatePassword() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.generatePassword()
            .then(function(response) {
                if (StudentController.selectedStudentData.password == '' || typeof StudentController.selectedStudentData.password == 'undefined') {
                    StudentController.selectedStudentData.password = response;
                }
                UtilsSvc.isAppendLoader(false);
            }, function(error) {
                console.log(error);
                UtilsSvc.isAppendLoader(false);
            });
    }

    angular.element(document.querySelector('#wizard')).on('finished.fu.wizard', function(evt, data) {
        // The last complete step is now transfer staff, add transfer staff logic function call here
        StudentController.postTransferForm();
    });

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function(evt, data) {
        StudentController.addStudentButton = false;
        // Step 1 - Internal search
        if (data.step == 1) {
            StudentController.Student.identity_type_name = StudentController.defaultIdentityTypeName;
            StudentController.Student.identity_type_id = StudentController.defaultIdentityTypeId;
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
            StudentController.Student.identity_type_name = StudentController.externalIdentityType;
            StudentController.Student.identity_type_id = StudentController.defaultIdentityTypeId;
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
            StudentController.generatePassword();
            InstitutionsStudentsSvc.resetExternalVariable();
        }
        // Step 4 - Add Student
        else if (data.step == 4) {
            if (StudentController.externalSearch) {
                StudentController.getUniqueOpenEmisId();
            }
            var studentData = StudentController.selectedStudentData;
            StudentController.existingStudent = false;
            StudentController.studentTransferable = false;

            if (studentData.hasOwnProperty('institution_students') && studentData.institution_students.length > 0) {
                StudentController.existingStudent = true;

                var schoolId = studentData['institution_students'][0]['institution_id'];
                if (StudentController.institutionId != schoolId) {
                    StudentController.studentTransferable = true;
                    var schoolName = studentData['institution_students'][0]['institution']['code_name'];
                    AlertSvc.warning($scope, 'This student is already allocated to %s', [schoolName]);
                } else {
                    AlertSvc.warning($scope, 'This student is already allocated to the current institution');
                }
            }
            StudentController.step = 'add_student';
        }
        // Step 5 - Transfer Student
        else {
            AlertSvc.info($scope, 'By clicking save, a transfer workflow will be initiated for this student');
            StudentController.step = 'transfer_student';
        }

        // to ensure that the StudentController.step is updated
        setTimeout(function() {
            $scope.$apply();
        });
    });
}