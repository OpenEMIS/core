angular
    .module('institutions.students.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.students.svc'])
    .controller('InstitutionsStudentsCtrl', InstitutionStudentController);

InstitutionStudentController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStudentsSvc', '$rootScope'];

function InstitutionStudentController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStudentsSvc, $rootScope) {
    // ag-grid vars


    var StudentController = this;
    var test = $scope;

    StudentController.pageSize = 10;

    StudentController.step = 'user_details';
    StudentController.selectedStudentData = {};
    StudentController.internalGridOptions = null;
    StudentController.externalGridOptions = null;
    StudentController.postRespone = null;
    StudentController.translateFields = null;
    StudentController.nationality_class = 'input select error';
    StudentController.identity_type_class = 'input select error';
    StudentController.identity_class = 'input string';
    StudentController.messageClass = '';
    StudentController.message = '';
    StudentController.genderOptions = [];
    StudentController.nationalitiesOptions = [];
    StudentController.identityTypeOptions = [];
    StudentController.academicPeriodOptions = [];
    StudentController.educationGradeOptions = [];
    StudentController.classOptions = [];
    StudentController.selectedGuardianData = {};
    StudentController.isGuardianAdding = false;
    StudentController.guardianStep = 'user_details';
    StudentController.redirectToGuardian = false;
    StudentController.error = {};
    StudentController.institutionId = null;
    StudentController.customFields = [];

    StudentController.datepickerOptions = {
        maxDate: new Date(),
        showWeeks: false
    };

    //controller function
    StudentController.getUniqueOpenEmisId = getUniqueOpenEmisId;
    StudentController.generatePassword = generatePassword;
    StudentController.changeGender = changeGender;
    StudentController.changeNationality = changeNationality;
    StudentController.changeIdentityType = changeIdentityType;
    StudentController.goToFirstStep = goToFirstStep;
    StudentController.goToNextStep = goToNextStep;
    StudentController.goToPrevStep = goToPrevStep;
    StudentController.confirmUser = confirmUser;
    StudentController.getGenders = getGenders;
    StudentController.getNationalities = getNationalities;
    StudentController.getIdentityTypes = getIdentityTypes;
    StudentController.setStudentName = setStudentName;
    StudentController.appendName = appendName;
    StudentController.initGrid = initGrid;
    StudentController.changeAcademicPeriod = changeAcademicPeriod;
    StudentController.changeEducationGrade = changeEducationGrade;
    StudentController.changeClass = changeClass;
    StudentController.cancelProcess = cancelProcess;
    StudentController.getAcademicPeriods = getAcademicPeriods;
    StudentController.getEducationGrades = getEducationGrades;
    StudentController.getClasses = getClasses;
    StudentController.getInternalSearchData = getInternalSearchData;
    StudentController.processInternalGridUserRecord = processInternalGridUserRecord;
    StudentController.getExternalSearchData = getExternalSearchData;
    StudentController.processExternalGridUserRecord = processExternalGridUserRecord;
    StudentController.addGuardian = addGuardian;
    StudentController.goToInternalSearch = goToInternalSearch;
    StudentController.goToExternalSearch = goToExternalSearch;
    StudentController.getRedirectToGuardian = getRedirectToGuardian;
    StudentController.getRelationType = getRelationType;
    StudentController.validateDetails = validateDetails;
    StudentController.saveStudentDetails = saveStudentDetails;
    StudentController.getStudentCustomFields=getStudentCustomFields;
    

    angular.element(document).ready(function () {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.init(angular.baseUrl);
        StudentController.institutionId = Number($window.localStorage.getItem("institution_id"));
        StudentController.translateFields = {
            'openemis_no': 'OpenEMIS ID',
            'name': 'Name',
            'gender_name': 'Gender',
            'date_of_birth': 'Date Of Birth',
            'nationality_name': 'Nationality',
            'identity_type_name': 'Identity Type',
            'identity_number': 'Identity Number',
            'account_type': 'Account Type'
        };
        StudentController.initGrid();
        StudentController.getGenders();
    });

    function getUniqueOpenEmisId() {
        if(!StudentController.isGuardianAdding && StudentController.selectedStudentData.openemis_no){
            StudentController.internalGridOptions = null;
            StudentController.goToInternalSearch();
            return;
        }
        if(StudentController.isGuardianAdding && StudentController.selectedGuardianData.openemis_no){
            StudentController.internalGridOptions = null;
            StudentController.goToInternalSearch();
            return;
        }
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getUniqueOpenEmisId()
            .then(function(response) {
                if(!StudentController.isGuardianAdding){
                    StudentController.selectedStudentData.openemis_no = response;
                    StudentController.selectedStudentData.username = response;
                    StudentController.getInternalSearchData();
                } else {
                    StudentController.selectedGuardianData.openemis_no = response;
                    StudentController.selectedGuardianData.username = response;
                    StudentController.goToInternalSearch();
                }
                
    }, function(error) {
            console.log(error);
            StudentController.getInternalSearchData();
        });
    }

    function getInternalSearchData() {
        var first_name = '';
        var last_name = '';
        var openemis_no = '';
        var date_of_birth = '';
        var identity_number = '';
        if(!StudentController.isGuardianAdding) {
            first_name = StudentController.selectedStudentData.first_name;
            last_name = StudentController.selectedStudentData.last_name;
            date_of_birth = StudentController.selectedStudentData.date_of_birth;
            openemis_no = StudentController.selectedStudentData.openemis_no;
            identity_number = StudentController.selectedStudentData.identity_number;
        } else{
            first_name = StudentController.selectedGuardianData.first_name;
            last_name = StudentController.selectedGuardianData.last_name;
            date_of_birth = StudentController.selectedGuardianData.date_of_birth;
            openemis_no = StudentController.selectedGuardianData.openemis_no;
            identity_number = StudentController.selectedGuardianData.identity_number;
        }
        var dataSource = {
            pageSize: StudentController.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                var param = {
                    page: params.endRow / (params.endRow - params.startRow),
                    limit: params.endRow - params.startRow,
                    first_name: first_name,
                    last_name: last_name,
                    openemis_no: openemis_no,
                    date_of_birth: date_of_birth,
                    identity_number: identity_number,
                }
                InstitutionsStudentsSvc.getInternalSearchData(param)
                .then(function(response) {
                    var gridData = response.data.data;
                    var totalRowCount = response.data.total;
                    return StudentController.processInternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        StudentController.internalGridOptions.api.setDatasource(dataSource);
        StudentController.internalGridOptions.api.sizeColumnsToFit(); 
    }

    function processInternalGridUserRecord(userRecords, params, totalRowCount) {
        console.log(userRecords);

        var lastRow = totalRowCount;
        StudentController.rowsThisPage = userRecords;

        params.successCallback(StudentController.rowsThisPage, lastRow);
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    function getExternalSearchData() {
        var param = {};
        if(!StudentController.isGuardianAdding) {
            param = {
                first_name: StudentController.selectedStudentData.first_name,
                last_name: StudentController.selectedStudentData.last_name,
                date_of_birth: StudentController.selectedStudentData.date_of_birth,
                identity_number: StudentController.selectedStudentData.identity_number,
            }
        } else{
            param = {
                first_name: StudentController.selectedGuardianData.first_name,
                last_name: StudentController.selectedGuardianData.last_name,
                date_of_birth: StudentController.selectedGuardianData.date_of_birth,
                identity_number: StudentController.selectedGuardianData.identity_number,
            }
        }
        var dataSource = {
            pageSize: StudentController.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                InstitutionsStudentsSvc.getExternalSearchData(param)
                .then(function(response) {
                    if(response.data.data){
                        var gridData = response.data.data;
                        var totalRowCount = response.data.total;
                        return StudentController.processExternalGridUserRecord(gridData, params, totalRowCount);
                    }
                    UtilsSvc.isAppendLoader(false);
            }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        StudentController.externalGridOptions.api.setDatasource(dataSource);
        StudentController.externalGridOptions.api.sizeColumnsToFit(); 
    }

    function processExternalGridUserRecord(userRecords, params, totalRowCount) {
        console.log(userRecords);

        var lastRow = totalRowCount;
        StudentController.rowsThisPage = userRecords;

        params.successCallback(StudentController.rowsThisPage, lastRow);
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    function generatePassword() {
            UtilsSvc.isAppendLoader(true);
            InstitutionsStudentsSvc.generatePassword()
        .then(function(response) {
            StudentController.selectedStudentData.password = response;
            StudentController.getAcademicPeriods();
            }, function(error) {
            console.log(error);
            StudentController.getAcademicPeriods();
        });
    }

    function getGenders(){
        InstitutionsStudentsSvc.getGenders().then(function(resp){
            StudentController.genderOptions = resp;
            StudentController.getNationalities();
        }, function(error){
            console.log(error);
            StudentController.getNationalities();
        });
    }

    function getNationalities(){
        InstitutionsStudentsSvc.getNationalities().then(function(resp){
            StudentController.nationalitiesOptions = resp.data;
            StudentController.getIdentityTypes();
        }, function(error){
            console.log(error);
            StudentController.getIdentityTypes();
        });
    }

    function getIdentityTypes(){
        InstitutionsStudentsSvc.getIdentityTypes().then(function(resp){
            StudentController.identityTypeOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getAcademicPeriods() {
        InstitutionsStudentsSvc.getAcademicPeriods().then(function(resp){
            StudentController.academicPeriodOptions = resp.data;
            StudentController.getStudentCustomFields();
        }, function(error){
            console.log(error);
            StudentController.getStudentCustomFields();
        });
    }

    function getEducationGrades() {
        UtilsSvc.isAppendLoader(true);
        var param = {
            academic_periods: StudentController.selectedStudentData.academic_period_id,
            institution_id: StudentController.institutionId
        };
        InstitutionsStudentsSvc.getEducationGrades(param).then(function(resp){
            StudentController.educationGradeOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getClasses() {
        var params = {
            academic_period: StudentController.selectedStudentData.academic_period_id,
            institution_id: StudentController.institutionId,
            grade_id: StudentController.selectedStudentData.education_grade_id
        };
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getClasses(params).then(function(resp){
            StudentController.classOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getStudentCustomFields() {
        InstitutionsStudentsSvc.getStudentCustomFields().then(function(resp){
            StudentController.customFields = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
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
        StudentController.error.gender_id = ''
    }

    function changeNationality() {
        var nationalityId = StudentController.selectedStudentData.nationality_id;
        var nationalityOptions = StudentController.nationalitiesOptions;
        var identityOptions = StudentController.identityTypeOptions;
        for (var i = 0; i < nationalityOptions.length; i++) {
            if (nationalityOptions[i].id == nationalityId) {
                if (nationalityOptions[i].identity_type_id == null) {
                    StudentController.selectedStudentData.identity_type_id = identityOptions['0'].id;
                    StudentController.selectedStudentData.identity_type_name = identityOptions['0'].name;
                } else {
                    StudentController.selectedStudentData.identity_type_id = nationalityOptions[i].identity_type_id;
                    StudentController.selectedStudentData.identity_type_name = nationalityOptions[i].identity_type.name;
                }
                StudentController.selectedStudentData.nationality_name = nationalityOptions[i].name;
                break;
            }
        }
    }

    function changeIdentityType() {
        var identityType = StudentController.selectedStudentData.identity_type_id;
        var identityOptions = StudentController.identityTypeOptions;
        for (var i = 0; i < identityOptions.length; i++) {
            if (identityOptions[i].id == identityType) {
                StudentController.selectedStudentData.identity_type_name = identityOptions[i].name;
                break;
            }
        }
    }

    function changeAcademicPeriod() {
        var academicPeriod = StudentController.selectedStudentData.academic_period_id;
        var academicPeriodOptions = StudentController.academicPeriodOptions;
        for (var i = 0; i < academicPeriodOptions.length; i++) {
            if (academicPeriodOptions[i].id == academicPeriod) {
                StudentController.selectedStudentData.academic_period_name = academicPeriodOptions[i].name;
                break;
            }
        }
        StudentController.error.academic_period_id = '';
        StudentController.getEducationGrades();
    }

    function changeClass() {
        var className = StudentController.selectedStudentData.education_grade_id;
        var classOptions = StudentController.classOptions;
        for (var i = 0; i < classOptions.length; i++) {
            if (classOptions[i].id == className) {
                StudentController.selectedStudentData.education_grade_name = classOptions[i].name;
                break;
            }
        }
    }

    function changeEducationGrade() {
        var educationGrade = StudentController.selectedStudentData.education_grade_id;
        var educationGradeOptions = StudentController.educationGradeOptions;
        for (var i = 0; i < educationGradeOptions.length; i++) {
            if (educationGradeOptions[i].id == educationGrade) {
                StudentController.selectedStudentData.education_grade_name = educationGradeOptions[i].name;
                break;
            }
        }
        StudentController.error.education_grade_id = '';
        StudentController.getClasses();
    }

    function goToInternalSearch(){
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            StudentController.internalGridOptions = {
                columnDefs: [
                    {headerName: StudentController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                onRowSelected: function (_e) {
                    StudentController.selectStudent(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
            setTimeout(function(){
                StudentController.getInternalSearchData();
            }, 1500);
        }, function(error){
            StudentController.internalGridOptions = {
                columnDefs: [
                    {headerName: StudentController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                onRowSelected: function (_e) {
                    StudentController.selectStudent(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
            setTimeout(function(){
                StudentController.getInternalSearchData();
            }, 1500);
        });
    }

    function goToExternalSearch(){
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            StudentController.externalGridOptions = {
                columnDefs: [
                    {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                onRowSelected: function (_e) {
                    StudentController.c(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
            setTimeout(function(){
                StudentController.getExternalSearchData();
            }, 1500);
        }, function(error){
            StudentController.externalGridOptions = {
                columnDefs: [
                    {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                onRowSelected: function (_e) {
                    StudentController.c(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
            setTimeout(function(){
                StudentController.getExternalSearchData();
            }, 1500);
        });
    }

    function goToPrevStep(){
        if(!StudentController.isGuardianAdding){
            switch(StudentController.step){
                case 'internal_search': 
                    StudentController.step = 'user_details';
                    break;
                case 'external_search': 
                    StudentController.step = 'internal_search';
                    StudentController.internalGridOptions = null;
                    StudentController.goToInternalSearch();
                    break;
                case 'confirmation': 
                    StudentController.step = 'external_search';
                    StudentController.externalGridOptions = null;
                    StudentController.goToExternalSearch();
                    break;
                case 'add_student': 
                    StudentController.step = 'confirmation';
                    break;
            }
        } else{
            switch(StudentController.guardianStep){
                case 'internal_search': 
                    StudentController.guardianStep = 'user_details';
                    break;
                case 'external_search': 
                    StudentController.guardianStep = 'internal_search';
                    StudentController.internalGridOptions = null;
                    StudentController.goToInternalSearch();
                    break;
                case 'confirmation': 
                    StudentController.guardianStep = 'external_search';
                    StudentController.externalGridOptions = null;
                    StudentController.goToExternalSearch();
                    break;
            }
        }
    }

    function goToNextStep() {
        if(!StudentController.isGuardianAdding){
            switch(StudentController.step){
                case 'user_details': 
                    StudentController.validateDetails();
                    break;
                case 'internal_search': 
                    StudentController.step = 'external_search';
                    StudentController.externalGridOptions = null;
                    StudentController.goToExternalSearch();
                    break;
                case 'external_search': 
                    StudentController.step = 'confirmation';
                    break;
                case 'confirmation': 
                    StudentController.step = 'add_student';
                    StudentController.selectedStudentData.endDate = '31-12-' + new Date().getFullYear();
                    StudentController.generatePassword();
                    break;
            }
        } else{
            switch(StudentController.guardianStep){
                case 'user_details': 
                    StudentController.guardianStep = 'internal_search';
                    StudentController.getUniqueOpenEmisId();
                    break;
                case 'internal_search': 
                    StudentController.guardianStep = 'external_search';
                    StudentController.externalGridOptions = null;
                    StudentController.goToExternalSearch();
                    break;
                case 'external_search': 
                    StudentController.guardianStep = 'confirmation';
                    break;
            }
        }
    }

    function validateDetails() {
        if(!StudentController.selectedStudentData.first_name){
            StudentController.error.first_name = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.last_name){
            StudentController.error.last_name = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.gender_id){
            StudentController.error.gender_id = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.date_of_birth){
            StudentController.error.date_of_birth = 'This field cannot be left empty';
        }

        if(!StudentController.selectedStudentData.first_name || !StudentController.selectedStudentData.last_name || !StudentController.selectedStudentData.gender_id || !StudentController.selectedStudentData.date_of_birth){
            return;
        }
        StudentController.step = 'internal_search';
        StudentController.getUniqueOpenEmisId();
    }

    function confirmUser() {
        if(!StudentController.selectedStudentData.username){
            StudentController.error.username = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.password){
            StudentController.error.password = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.academic_period_id){
            StudentController.error.academic_period_id = 'This field cannot be left empty';
        }
        // if(!StudentController.selectedStudentData.education_grade_id){
        //     StudentController.error.education_grade_id = 'This field cannot be left empty';
        // }
        if(!StudentController.selectedStudentData.startDate){
            StudentController.error.startDate = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.username || !StudentController.selectedStudentData.password || !StudentController.selectedStudentData.academic_period_id || !StudentController.selectedStudentData.startDate){
            return;
        }
        StudentController.saveStudentDetails();
    }

    function saveStudentDetails() {
        var params = {
            openemis_no: StudentController.selectedStudentData.openemis_no,
            first_name: StudentController.selectedStudentData.first_name,
            middle_name: StudentController.selectedStudentData.middle_name,
            third_name: StudentController.selectedStudentData.third_name,
            last_name: StudentController.selectedStudentData.last_name,
            preferred_name: StudentController.selectedStudentData.preferred_name,
            gender_id: StudentController.selectedStudentData.gender_id,
            date_of_birth: StudentController.selectedStudentData.date_of_birth,
            identity_number: StudentController.selectedStudentData.identity_number,
            nationality_id: StudentController.selectedStudentData.nationality_id,
            username: StudentController.selectedStudentData.username,
            password: StudentController.selectedStudentData.password,
            postal_code: StudentController.selectedStudentData.postalCode,
            address: StudentController.selectedStudentData.address,
            birthplace_area_id: 2,
            address_area_id: 2,
            identity_type_id: StudentController.selectedStudentData.identity_type_id,
            education_grade_id: 59,
            academic_period_id: StudentController.selectedStudentData.academic_period_id,
            start_date: StudentController.selectedStudentData.startDate,
            end_date: StudentController.selectedStudentData.endDate,
            institution_class_id: 524,
            student_status_id: 1,
        };
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.saveStudentDetails(params).then(function(resp){
            StudentController.message = (StudentController.selectedStudentData && StudentController.selectedStudentData.userType ? StudentController.selectedStudentData.userType.name : 'Student') + ' successfully added.';
            StudentController.messageClass = 'alert-success';
            StudentController.step = "summary";
            StudentController.getRedirectToGuardian();
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function goToFirstStep() {
        if(!StudentController.isGuardianAdding){
            StudentController.step = 'user_details';
            StudentController.selectedStudentData = {};
        }
        else{
            StudentController.guardianStep = 'user_details';
            StudentController.selectedGuardianData = {};
        } 
    }

    function cancelProcess() {
        location.href = angular.baseUrl + '/Institution/Institutions/eyJpZCI6NiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6ImtjMTBnNThzMjRsaXVsMTZ2Y2lsMmlvN2tpIn0.ZDJiNzg2MTc0ZWJkNTQ4NmZlZjU0ZDFlOTc1ZTEyNjY3OWQwNzk1MTk4MjVmZTIzMDQ4ZjY2OTRmZWVlZjA3OA/Students/index';
    }

    function addGuardian () {
        StudentController.isGuardianAdding = true;
        StudentController.internalGridOptions = null;
        StudentController.externalGridOptions = null;
        StudentController.getRelationType();
    }

    function getRedirectToGuardian() {
        InstitutionsStudentsSvc.getRedirectToGuardian().then(function(resp){
            StudentController.redirectToGuardian = resp.data;
            StudentController.redirectToGuardian = true;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getRelationType() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getRelationType().then(function(resp){
            StudentController.relationTypeOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    StudentController.selectStudent = function(id) {
        StudentController.getStudentData();
    }

    StudentController.getStudentData = function() {
        var log = [];
        angular.forEach(StudentController.rowsThisPage , function(value) {
            if (value.id == StudentController.selectedStudent) {
                StudentController.selectedStudentData = value;
            }
        }, log);
    }

    function initGrid() {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            StudentController.internalGridOptions = {
                columnDefs: [
                    {headerName: StudentController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                onRowSelected: function (_e) {
                    StudentController.selectStudent(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };

            StudentController.externalGridOptions = {
                columnDefs: [
                    {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                onRowSelected: function (_e) {
                    StudentController.c(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
        }, function(error){
            StudentController.internalGridOptions = {
                columnDefs: [
                    {headerName: StudentController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                onRowSelected: function (_e) {
                    StudentController.selectStudent(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };

            StudentController.externalGridOptions = {
                columnDefs: [
                    {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                onRowSelected: function (_e) {
                    StudentController.selectStudent(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
        });
    };

}