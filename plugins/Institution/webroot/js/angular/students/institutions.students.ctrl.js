angular
    .module('institutions.students.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.students.svc'])
    .controller('InstitutionsStudentsCtrl', InstitutionStudentController);

InstitutionStudentController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStudentsSvc', '$rootScope'];

function InstitutionStudentController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStudentsSvc, $rootScope) {
    // ag-grid vars


    var StudentController = this;
    var test = $scope;

    var pageSize = 10;

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
    

    angular.element(document).ready(function () {
        UtilsSvc.isAppendLoader(true);
        StudentController.initGrid();
        InstitutionsStudentsSvc.init(angular.baseUrl);
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
        StudentController.getGenders();
    });

    function getUniqueOpenEmisId() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getUniqueOpenEmisId()
            .then(function(response) {
                StudentController.selectedStudentData.openemis_no = response;
                StudentController.selectedStudentData.username = response;
                UtilsSvc.isAppendLoader(false);
    }, function(error) {
            console.log(error);
            StudentController.generatePassword();
        });
    }

    function generatePassword() {
            UtilsSvc.isAppendLoader(true);
            InstitutionsStudentsSvc.generatePassword()
        .then(function(response) {
            StudentController.selectedStudentData.password = response;
            StudentController.getAcademicPeriods();
            }, function(error) {
            console.log(error);
            StudentController.getGenders();
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
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getEducationGrades() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getEducationGrades(StudentController.selectedStudentData.academic_period_id).then(function(resp){
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
            institution_id: 6,
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
                if (genderOptions[i].id == userData.gender_id) {
                    studentData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StudentController.selectedStudentData = studentData;
        }
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
        StudentController.getClasses();
    }

    function goToPrevStep(){
        switch(StudentController.step){
            case 'internal_search': 
                StudentController.step = 'user_details';
                break;
            case 'external_search': 
                StudentController.step = 'internal_search';
                break;
            case 'confirmation': 
                StudentController.step = 'external_search';
                break;
            case 'add_student': 
                StudentController.step = 'confirmation';
                break;
        }
    }

    function goToNextStep() {
        switch(StudentController.step){
            case 'user_details': 
                StudentController.step = 'internal_search';
                StudentController.getUniqueOpenEmisId();
                break;
            case 'internal_search': 
                StudentController.step = 'external_search';
                break;
            case 'external_search': 
                StudentController.step = 'confirmation';
                break;
            case 'confirmation': 
                StudentController.step = 'add_student';
                StudentController.generatePassword();
                break;
        }
    }

    function confirmUser() {
        StudentController.message = (StudentController.selectedStudentData && StudentController.selectedStudentData.userType ? StudentController.selectedStudentData.userType.name : 'Student') + ' successfully added.';
        StudentController.messageClass = 'alert-success';
        StudentController.step = "summary";
    }

    function goToFirstStep() {
        StudentController.step = 'user_details';
        StudentController.selectedStudentData = {};
    }

    function cancelProcess() {
        location.href = angular.baseUrl + '/Directory/Directories/Directories/index';
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
                    StudentController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
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
                    StudentController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
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
                    StudentController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
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
                    StudentController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
            };
        });
    };

}