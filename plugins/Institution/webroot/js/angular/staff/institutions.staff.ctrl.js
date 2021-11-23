angular
    .module('institutions.staff.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.staff.svc', 'angular.chosen'])
    .controller('InstitutionsStaffCtrl', InstitutionStaffController);

InstitutionStaffController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStaffSvc'];

function InstitutionStaffController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStaffSvc) {
    // ag-grid vars


    var StaffController = this;

    StaffController.pageSize = 10;
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
    StaffController.positionTypeOptions = [];
    StaffController.institutionPositionOptions = {
        availableOptions: [],
        selectedOption: ''
    };
    StaffController.staffTypeOptions = [];
    StaffController.shiftsOptions = [];
    StaffController.fteOptions = [];
    StaffController.shiftsId = [];
    StaffController.rowsThisPage= [];

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
    StaffController.getPostionTypes = getPostionTypes;
    StaffController.getPositions = getPositions;
    StaffController.getStaffTypes = getStaffTypes;
    StaffController.getShifts = getShifts;
    StaffController.getFtes = getFtes;
    StaffController.changePositionType = changePositionType;
    StaffController.changePosition = changePosition;
    StaffController.changeStaffType = changeStaffType;
    StaffController.cancelProcess = cancelProcess;
    StaffController.changeFte = changeFte;
    StaffController.getInternalSearchData = getInternalSearchData;
    StaffController.getExternalSearchData = getExternalSearchData;
    StaffController.processInternalGridUserRecord = processInternalGridUserRecord;
    StaffController.processExternalGridUserRecord = processExternalGridUserRecord;

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
                StaffController.selectedStaffData.openemis_no = response;
                StaffController.selectedStaffData.username = response;
                StaffController.getInternalSearchData();
    }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getInternalSearchData() {
        first_name = StaffController.selectedStaffData.first_name;
        last_name = StaffController.selectedStaffData.last_name;
        var dataSource = {
            pageSize: StaffController.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                InstitutionsStaffSvc.getInternalSearchData(first_name, last_name)
                .then(function(response) {
                    var gridData = response.data;
                    var totalRowCount = gridData.length;
                    return StaffController.processInternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        StaffController.internalGridOptions.api.setDatasource(dataSource);
        StaffController.internalGridOptions.api.sizeColumnsToFit(); 
    }

    function processInternalGridUserRecord(userRecords, params, totalRowCount) {
        console.log(userRecords);

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = userRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        // scope.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    function getExternalSearchData() {
        first_name = StaffController.selectedStaffData.first_name;
        last_name = StaffController.selectedStaffData.last_name;
        var dataSource = {
            pageSize: StaffController.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                InstitutionsStaffSvc.getExternalSearchData(first_name, last_name)
                .then(function(response) {
                    var gridData = response.data;
                    var totalRowCount = gridData.length;
                    return StaffController.processExternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        StaffController.externalGridOptions.api.setDatasource(dataSource);
        StaffController.externalGridOptions.api.sizeColumnsToFit(); 
    }

    function processExternalGridUserRecord(userRecords, params, totalRowCount) {
        console.log(userRecords);

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = userRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        // scope.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    function generatePassword() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.generatePassword()
        .then(function(response) {
            if (StaffController.selectedStaffData.password == '' || typeof StaffController.selectedStaffData.password == 'undefined') {
                StaffController.selectedStaffData.password = response;
            }
            StaffController.getPostionTypes();
        }, function(error) {
            console.log(error);
            StaffController.getPostionTypes();
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

    function getPostionTypes(){
        InstitutionsStaffSvc.getPositionTypes().then(function(resp){
            StaffController.positionTypeOptions = resp.data;
            StaffController.getStaffTypes();
        }, function(error){
            console.log(error);
            StaffController.getStaffTypes();
        });
    }

    function getFtes(){
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.getFtes().then(function(resp){
            StaffController.fteOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getPositions(){
        UtilsSvc.isAppendLoader(true);
        var params = {
            institutionId: 6,
            fte: StaffController.selectedStaffData.position_type_id === 'Full-Time' ? 1 : StaffController.selectedStaffData.fte_id,
            startDate: StaffController.selectedStaffData.startDate,
            endDate: StaffController.selectedStaffData.endDate,
        }
        InstitutionsStaffSvc.getPositions(params).then(function(resp){
            StaffController.institutionPositionOptions.availableOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getStaffTypes(){
        InstitutionsStaffSvc.getStaffTypes().then(function(resp){
            StaffController.staffTypeOptions = resp.data;
            StaffController.getShifts();
        }, function(error){
            console.log(error);
            StaffController.getShifts();
        });
    }

    function getShifts(){
        InstitutionsStaffSvc.getShifts().then(function(resp){
            StaffController.shiftsOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function setStaffName() {
        var staffData = StaffController.selectedStaffData;
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
        var userData = StaffController.selectedStaffData;
        if (userData.hasOwnProperty('gender_id')) {
            var genderOptions = StaffController.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == userData.gender_id) {
                    userData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StaffController.selectedStaffData = userData;
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
        var identityTypeOptions = StaffController.identityTypeOptions;
        for (var i = 0; i < identityTypeOptions.length; i++) {
            if (identityTypeOptions[i].id == identityType) {
                StaffController.selectedStaffData.identity_type_name = identityTypeOptions[i].name;
                break;
            }
        }
    }

    function changePositionType() {
        var positionType = StaffController.selectedStaffData.position_type_id;
        var positionTypeOptions = StaffController.positionTypeOptions;
        for (var i = 0; i < positionTypeOptions.length; i++) {
            if (positionTypeOptions[i].id == positionType) {
                StaffController.selectedStaffData.position_type_name = positionTypeOptions[i].name;
                break;
            }
        }
        if(positionType === 'Full-Time'){
            StaffController.selectedStaffData.fte_id = 1;
            StaffController.getPositions();
        }
        else{
            StaffController.getFtes();
        }
    }

    function changePosition() {
        var position = StaffController.selectedStaffData.position_id;
        var positionOptions = StaffController.institutionPositionOptions;
        for (var i = 0; i < positionOptions.length; i++) {
            if (positionOptions[i].id == position) {
                StaffController.selectedStaffData.position_name = positionOptions[i].name;
                break;
            }
        }
    }

    function changeStaffType() {
        var staffType = StaffController.selectedStaffData.staff_type_id;
        var staffTypeOptions = StaffController.staffTypeOptions;
        for (var i = 0; i < staffTypeOptions.length; i++) {
            if (staffTypeOptions[i].id == staffType) {
                StaffController.selectedStaffData.staff_type_name = staffTypeOptions[i].name;
                break;
            }
        }
        StaffController.getPositions();
    }

    function changeFte() {
        var fte = StaffController.selectedStaffData.fte_id;
        var fteOptions = StaffController.fteOptions;
        for (var i = 0; i < fteOptions.length; i++) {
            if (fteOptions[i].id == fte) {
                StaffController.selectedStaffData.fte_name = fteOptions[i].name;
                break;
            }
        }
        StaffController.getPositions();
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
            case 'add_staff': 
                StaffController.step = 'confirmation';
                break;
        }
    }

    function goToNextStep() {
        switch(StaffController.step){
            case 'user_details': 
                StaffController.step = 'internal_search';
                StaffController.getUniqueOpenEmisId();
                break;
            case 'internal_search': 
                StaffController.step = 'external_search';
                UtilsSvc.isAppendLoader(false);
                setTimeout(function(){
                    StaffController.getExternalSearchData();
                }, 1500);
                break;
            case 'external_search': 
                StaffController.step = 'confirmation';
                break;
            case 'confirmation': 
                StaffController.step = 'add_staff';
                StaffController.generatePassword();
                break;
        }
    }

    function confirmUser() {
        StaffController.message = (StaffController.selectedStaffData && StaffController.selectedStaffData.userType ? StaffController.selectedStaffData.userType.name : 'Student') + ' successfully added.';
        StaffController.messageClass = 'alert-success';
        StaffController.step = "summary";
    }

    function goToFirstStep() {
        StaffController.step = 'user_details';
        StaffController.selectedStaffData = {};
    }

    function cancelProcess() {
        location.href = angular.baseUrl + '/Directory/Directories/Directories/index';
    }

    StaffController.selectStaff = function(id) {
        StaffController.selectedUser = id;
        StaffController.getStaffData();
    }

    StaffController.getStaffData = function() {
        var log = [];
        angular.forEach(StaffController.rowsThisPage , function(value) {
            if (value.id == StaffController.selectedUser) {
                StaffController.selectedStaffData = value;
            }
        }, log);
    }

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
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
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
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
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
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
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
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
        });
    };
    
}
