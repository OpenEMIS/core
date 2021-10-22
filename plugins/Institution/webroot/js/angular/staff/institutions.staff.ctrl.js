angular
    .module('institutions.staff.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.staff.svc', 'angular.chosen'])
    .controller('InstitutionsStaffCtrl', InstitutionStaffController);

InstitutionStaffController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStaffSvc'];

function InstitutionStaffController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStaffSvc) {
    // ag-grid vars


    var StaffController = this;

    var pageSize = 10;
    StaffController.step = 'user_details';
    StaffController.selectedStaffData = {};
    StaffController.internalGridOptions = null;
    StaffController.externalGridOptions = null;
    StaffController.postRespone = null;
    StaffController.translateFields = null;
    StaffController.nationality_class = 'input select error';
    StaffController.identity_type_class = 'input select error';
    StaffController.identity_class = 'input string';
    StaffController.messageClass = '';
    StaffController.message = '';
    StaffController.genderOptions = [];
    StaffController.nationalitiesOptions = [];
    StaffController.identityTypeOptions = [];

    //controller function
    StaffController.getUniqueOpenEmisId = getUniqueOpenEmisId;
    StaffController.generatePassword = generatePassword;
    StaffController.changeGender = changeGender;
    StaffController.changeNationality = changeNationality;
    StaffController.changeIdentityType = changeIdentityType;
    StaffController.goToFirstStep = goToFirstStep;
    StaffController.goToNextStep = goToNextStep;
    StaffController.goToPrevStep = goToPrevStep;
    StaffController.confirmUser = confirmUser;
    StaffController.getGenders = getGenders;
    StaffController.getNationalities = getNationalities;
    StaffController.getIdentityTypes = getIdentityTypes;
    StaffController.setStaffName = setStaffName;
    StaffController.appendName = appendName;
    StaffController.initGrid = initGrid;
    

    angular.element(document).ready(function () {
        UtilsSvc.isAppendLoader(true);
        StaffController.initGrid();
        InstitutionsStaffSvc.init(angular.baseUrl);
        StaffController.translateFields = {
            'openemis_no': 'OpenEMIS ID',
            'name': 'Name',
            'gender_name': 'Gender',
            'date_of_birth': 'Date Of Birth',
            'nationality_name': 'Nationality',
            'identity_type_name': 'Identity Type',
            'identity_number': 'Identity Number',
            'account_type': 'Account Type'
        };
        StaffController.getGenders();
    });

    function getUniqueOpenEmisId() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.getUniqueOpenEmisId()
            .then(function(response) {
                var username = StaffController.selectedUserData.username;
                //POCOR-5878 starts
                if(username != StaffController.selectedUserData.openemis_no && (username == '' || typeof username == 'undefined')){
                    StaffController.selectedUserData.username = StaffController.selectedUserData.openemis_no;
                    StaffController.selectedUserData.openemis_no = StaffController.selectedUserData.openemis_no;
                }else{
                    if(username == StaffController.selectedUserData.openemis_no){
                        StaffController.selectedUserData.username = response;
                    }
                    StaffController.selectedUserData.openemis_no = response;
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
        InstitutionsStaffSvc.generatePassword()
        .then(function(response) {
            if (StaffController.selectedUserData.password == '' || typeof StaffController.selectedUserData.password == 'undefined') {
                StaffController.selectedUserData.password = response;
            }
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getGenders(){
        InstitutionsStaffSvc.getGenders().then(function(resp){
            StaffController.genderOptions = resp;
            StaffController.getNationalities();
        }, function(error){
            console.log(error);
            StaffController.getNationalities();
        });
    }

    function getNationalities(){
        InstitutionsStaffSvc.getNationalities().then(function(resp){
            StaffController.nationalitiesOptions = resp.data;
            StaffController.getIdentityTypes();
        }, function(error){
            console.log(error);
            StaffController.getIdentityTypes();
        });
    }

    function getIdentityTypes(){
        InstitutionsStaffSvc.getIdentityTypes().then(function(resp){
            StaffController.identityTypeOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function setStaffName() {
        var staffData = StudentController.selectedStaffData;
        staffData.name = '';

        if (staffData.hasOwnProperty('first_name')) {
            staffData.name = staffData.first_name.trim();
        }
        StaffController.appendName(staffData, 'middle_name', true);
        StaffController.appendName(staffData, 'third_name', true);
        StaffController.appendName(staffData, 'last_name', true);
        StaffController.selectedStaffData = staffData;
    }

    function appendName(staffObj, variableName, trim) {
        if (staffObj.hasOwnProperty(variableName)) {
            if (trim === true) {
                staffObj[variableName] = staffObj[variableName].trim();
            }
            if (staffObj[variableName] != null && staffObj[variableName] != '') {
                staffObj.name = staffObj.name + ' ' + staffObj[variableName];
            }
        }
        return staffObj;
    }

    function changeGender() {
        var userData = StaffController.selectedUserData;
        if (userData.hasOwnProperty('gender_id')) {
            var genderOptions = StaffController.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == userData.gender_id) {
                    userData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StaffController.selectedUserData = userData;
        }
    }

    function changeNationality() {
        var nationalityId = StaffController.selectedStaffData.nationality_id;
        var options = StaffController.nationalitiesOptions;
        var identityOptions = StaffController.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == nationalityId) {
                if (options[i].identity_type_id == null) {
                    StaffController.selectedStaffData.identity_type_id = identityOptions['0'].id;
                    StaffController.selectedStaffData.identity_type_name = identityOptions['0'].name;
                } else {
                    StaffController.selectedStaffData.identity_type_id = options[i].identity_type_id;
                    StaffController.selectedStaffData.identity_type_name = options[i].identity_type.name;
                }
                StaffController.selectedStaffData.nationality_name = options[i].name;
                break;
            }
        }
    }

    function changeIdentityType() {
        var identityType = StaffController.selectedStaffData.identity_type_id;
        var options = StaffController.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == identityType) {
                StaffController.selectedStaffData.identity_type_name = options[i].name;
                break;
            }
        }
    }

    function goToPrevStep(){
        switch(StaffController.step){
            case 'internal_search': 
                StaffController.step = 'user_details';
                break;
            case 'external_search': 
                StaffController.step = 'internal_search';
                break;
            case 'confirmation': 
                StaffController.step = 'external_search';
                break;
        }
    }

    function goToNextStep() {
        switch(StaffController.step){
            case 'user_details': 
                StaffController.step = 'internal_search';
                // StaffController.getUniqueOpenEmisId();
                // StaffController.generatePassword();
                break;
            case 'internal_search': 
                StaffController.step = 'external_search';
                break;
            case 'external_search': 
                StaffController.step = 'confirmation';
                break;
        }
    }

    function confirmUser() {
        StaffController.message = (StaffController.selectedUserData && StaffController.selectedUserData.userType ? StaffController.selectedUserData.userType.name : 'Student') + ' successfully added.';
        StaffController.messageClass = 'alert-success';
        StaffController.step = "summary";
    }

    function goToFirstStep() {
        StaffController.step = 'user_details';
    }

    // StaffController.selectStaff = function(id) {
    //     StaffController.selectedUser = id;
    //     StaffController.getStaffData();
    // }

    // StaffController.getStaffData = function() {
    //     var log = [];
    //     angular.forEach(StaffController.rowsThisPage , function(value) {
    //         if (value.id == StaffController.selectedUser) {
    //             StaffController.selectedUserData = value;
    //         }
    //     }, log);
    // }

    function initGrid() {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            StaffController.internalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
            };

            StaffController.externalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
            };
        }, function(error){
            StaffController.internalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
            };

            StaffController.externalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
            };
        });
    };
    
}
