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
    StaffController.changeAcademicPeriod = changeAcademicPeriod;
    StaffController.changeEducationGrade = changeEducationGrade;
    StaffController.changeClass = changeClass;
    

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
        StudentController.getUniqueOpenEmisId();
    });

    function getUniqueOpenEmisId() {
        InstitutionsStudentsSvc.getUniqueOpenEmisId()
            .then(function(response) {
                // var username = StudentController.selectedStudentData.username;
                // //POCOR-5878 starts
                // if(username != StudentController.selectedStudentData.openemis_no && (username == '' || typeof username == 'undefined')){
                //     StudentController.selectedStudentData.username = StudentController.selectedStudentData.openemis_no;
                //     StudentController.selectedStudentData.openemis_no = StudentController.selectedStudentData.openemis_no;
                // }else{
                //     if(username == StudentController.selectedStudentData.openemis_no){
                //         StudentController.selectedStudentData.username = response;
                //     }
                //     StudentController.selectedStudentData.openemis_no = response;
                // }
                StudentController.selectedStudentData.openemis_no = response;
                StudentController.generatePassword();
        }, function(error) {
            console.log(error);
            StudentController.generatePassword();
        });
    }

    function generatePassword() {
        InstitutionsStudentsSvc.generatePassword()
        .then(function(response) {
            if (StudentController.selectedStudentData.password == '' || typeof StudentController.selectedStudentData.password == 'undefined') {
                StudentController.selectedStudentData.password = response;
            }
            StudentController.getGenders();
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
        var options = StudentController.nationalitiesOptions;
        var identityOptions = StudentController.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == nationalityId) {
                if (options[i].identity_type_id == null) {
                    StudentController.selectedStudentData.identity_type_id = identityOptions['0'].id;
                    StudentController.selectedStudentData.identity_type_name = identityOptions['0'].name;
                } else {
                    StudentController.selectedStudentData.identity_type_id = options[i].identity_type_id;
                    StudentController.selectedStudentData.identity_type_name = options[i].identity_type.name;
                }
                StudentController.selectedStudentData.nationality_name = options[i].name;
                break;
            }
        }
    }

    function changeIdentityType() {
        var identityType = StudentController.selectedStudentData.identity_type_id;
        var options = StudentController.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == identityType) {
                StudentController.selectedStudentData.identity_type_name = options[i].name;
                break;
            }
        }
    }

    function changeAcademicPeriod() {}

    function changeClass() {}

    function changeEducationGrade() {}

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
            case 'add_staff': 
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
                StudentController.step = 'add_staff';
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

    scope.cancelProcess = function() {
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