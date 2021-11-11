angular.module('directory.directoryadd.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryadd.svc'])
    .controller('DirectoryAddCtrl', DirectoryAddController);

DirectoryAddController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddSvc'];

function DirectoryAddController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddSvc) {
    var scope = $scope;

    scope.step = "user_details";
    scope.guardianStep = "user_details";
    scope.selectedUserData = {};
    scope.selectedGuardianData = {};
    scope.internalGridOptions = null;
    scope.externalGridOptions = null;
    scope.postRespone = null;
    scope.translateFields = null;
    scope.genderOptions = [];
    scope.userTypeOptions = [];
    scope.nationality_class = 'input select error';
    scope.identity_type_class = 'input select error';
    scope.identity_class = 'input string';
    scope.messageClass = '';
    scope.message = '';
    scope.nationalitiesOptions = [];
    scope.identityTypeOptions = [];
    scope.contactTypeOptions = [];
    scope.relationTypeOptions = [];
    scope.addressAreaOption = [];
    scope.birthplaceAreaOption = [];
    scope.isGuardianAdding = false;
    scope.pageSize = 10;
    scope.rowsThisPage = [];
    scope.selectedStaff;

    angular.element(document).ready(function () {
        UtilsSvc.isAppendLoader(true);
        console.log(angular.baseUrl);
        DirectoryaddSvc.init(angular.baseUrl);
        scope.translateFields = {
            'openemis_no': 'OpenEMIS ID',
            'name': 'Name',
            'gender_name': 'Gender',
            'date_of_birth': 'Date Of Birth',
            'nationality_name': 'Nationality',
            'identity_type_name': 'Identity Type',
            'identity_number': 'Identity Number',
            'account_type': 'Account Type'
        };
        scope.initGrid();
        scope.getUserTypes();
    });

    scope.getUniqueOpenEmisId = function() {
        if(!scope.isGuardianAdding && scope.selectedUserData.openemis_no)
            return;
        if(scope.isGuardianAdding && scope.selectedGuardianData.openemis_no)
            return;
        UtilsSvc.isAppendLoader(true);
        DirectoryaddSvc.getUniqueOpenEmisId()
            .then(function(response) {
            if(!scope.isGuardianAdding){
                var username = scope.selectedUserData.username;
                if(username != scope.selectedUserData.openemis_no && (username == '' || typeof username == 'undefined')){
                    scope.selectedUserData.username = scope.selectedUserData.openemis_no;
                    scope.selectedUserData.openemis_no = scope.selectedUserData.openemis_no;
                } else{
                    if(username == scope.selectedUserData.openemis_no){
                        scope.selectedUserData.username = response;
                    }
                    scope.selectedUserData.openemis_no = response;
                }
            } else{
                var username = scope.selectedGuardianData.username;
                if(username != scope.selectedGuardianData.openemis_no && (username == '' || typeof username == 'undefined')){
                    scope.selectedGuardianData.username = scope.selectedGuardianData.openemis_no;
                    scope.selectedGuardianData.openemis_no = scope.selectedGuardianData.openemis_no;
                }else{
                    if(username == scope.selectedGuardianData.openemis_no){
                        scope.selectedGuardianData.username = response;
                    }
                    scope.selectedGuardianData.openemis_no = response;
                }
            }
            scope.getInternalSearchData();
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getInternalSearchData = function() {
        var first_name = '';
        var last_name = '';
        if(!scope.isGuardianAdding) {
            first_name = scope.selectedUserData.first_name;
            last_name = scope.selectedUserData.last_name;
        } else{
            first_name = scope.selectedGuardianData.first_name;
            last_name = scope.selectedGuardianData.last_name;
        }
        var dataSource = {
            pageSize: scope.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                DirectoryaddSvc.getInternalSearchData(first_name, last_name)
                .then(function(response) {
                    var gridData = response.data;
                    var totalRowCount = gridData.length;
                    return scope.processUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        scope.internalGridOptions.api.setDatasource(dataSource);
        scope.internalGridOptions.api.sizeColumnsToFit(); 
    }

    scope.processUserRecord = function(userRecords, params, totalRowCount) {
        console.log(userRecords);

        var lastRow = totalRowCount;
        scope.rowsThisPage = userRecords;

        params.successCallback(scope.rowsThisPage, lastRow);
        // scope.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    // scope.setGridData = function() {
    //     if (angular.isDefined(scope.internalGridOptions.api)) {
    //         // vm.gridOptions.api.setRowData(vm.classStudentList);
    //         scope.setRowDatas(scope.gridData);
    //         //  vm.countStudentData();
     
    //     }
    // }

    // scope.setRowDatas = function(userList) {
    //     console.log('studentList controller',userList);
    //     userList.forEach(function (dataItem, index) {
    //         dataItem.rowHeight = 60;
    //     });       
    //     scope.internalGridOptions.api.setRowData(userList);
        
    // }

    scope.generatePassword = function() {
        UtilsSvc.isAppendLoader(true);
        DirectoryaddSvc.generatePassword()
        .then(function(response) {
            if(!scope.isGuardianAdding) {
                if (scope.selectedUserData.password == '' || typeof scope.selectedUserData.password == 'undefined') {
                    scope.selectedUserData.password = response;
                }
            } else {
                if (scope.selectedGuardianData.password == '' || typeof scope.selectedGuardianData.password == 'undefined') {
                    scope.selectedGuardianData.password = response;
                }
            }
            scope.getContactTypes();
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getUserTypes = function() {
        DirectoryaddSvc.getUserTypes()
        .then(function(response) {
            scope.userTypeOptions = response.data;
            scope.getGenders();
        }, function(error) {
            console.log(error);
            scope.getGenders();
        });
    }

    scope.getGenders = function() {
        DirectoryaddSvc.getGenders()
        .then(function(response) {
            scope.genderOptions = response.data;
            scope.getNationalities();
        }, function(error) {
            console.log(error);
            scope.getNationalities();
        });
    }

    scope.getNationalities = function() {
        DirectoryaddSvc.getNationalities()
        .then(function(response) {
            scope.nationalitiesOptions = response.data;
            scope.getIdentityTypes();
        }, function(error) {
            console.log(error);
            scope.getIdentityTypes();
        });
    }

    scope.getIdentityTypes = function() {
        DirectoryaddSvc.getIdentityTypes()
        .then(function(response) {
            scope.identityTypeOptions = response.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getContactTypes = function() {
        DirectoryaddSvc.getContactTypes()
        .then(function(response) {
            scope.contactTypeOptions = response.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.setName = function() {
        if(!scope.isGuardianAdding) {
            var userData = scope.selectedUserData;
            userData.name = '';
            if (userData.hasOwnProperty('first_name')) {
                userData.name = userData.first_name.trim();
            }
            scope.appendName(userData, 'middle_name', true);
            scope.appendName(userData, 'third_name', true);
            scope.appendName(userData, 'last_name', true);
            scope.selectedUserData = userData;
        } else {
            var guardianData = scope.selectedGuardianData;
            guardianData.name = '';
            if (guardianData.hasOwnProperty('first_name')) {
                guardianData.name = userData.first_name.trim();
            }
            scope.appendName(guardianData, 'middle_name', true);
            scope.appendName(guardianData, 'third_name', true);
            scope.appendName(guardianData, 'last_name', true);
            scope.selectedGuardianData = guardianData;
        }
    }

    scope.appendName = function(dataObj, variableName, trim) {
        if (dataObj.hasOwnProperty(variableName)) {
            if (trim === true) {
                dataObj[variableName] = dataObj[variableName].trim();
            }
            if (dataObj[variableName] != null && dataObj[variableName] != '') {
                dataObj.name = dataObj.name + ' ' + dataObj[variableName];
            }
        }
        return dataObj;
    }

    scope.changeGender = function() {
        if(!scope.isGuardianAdding){
            var userData = scope.selectedUserData;
            if (userData.hasOwnProperty('gender_id')) {
                var genderOptions = scope.genderOptions;
                for(var i = 0; i < genderOptions.length; i++) {
                    if (genderOptions[i].id == userData.gender_id) {
                        userData.gender = {
                            name: genderOptions[i].name
                        };
                    }
                }
                scope.selectedUserData = userData;
            }
        } else {
            var guardianData = scope.selectedGuardianData;
            if (guardianData.hasOwnProperty('gender_id')) {
                var genderOptions = scope.genderOptions;
                for(var i = 0; i < genderOptions.length; i++) {
                    if (genderOptions[i].id == guardianData.gender_id) {
                        guardianData.gender = {
                            name: genderOptions[i].name
                        };
                    }
                }
                scope.selectedGuardianData = guardianData;
            }
        }
        
    }

    scope.changeUserType = function() {
        var userData = scope.selectedUserData;
        if (userData.hasOwnProperty('user_type_id')) {
            var userTypeOptions = scope.userTypeOptions;
            for(var i = 0; i < userTypeOptions.length; i++) {
                if (userTypeOptions[i].id == userData.user_type_id) {
                    userData.userType = {
                        name: userTypeOptions[i].name
                    };
                }
            }
            scope.selectedUserData = userData;
        }
    }

    scope.changeNationality =  function() {
        if(!scope.isGuardianAdding) {
            var nationalityId = scope.selectedUserData.nationality_id;
            var options = scope.nationalitiesOptions;
            var identityOptions = scope.identityTypeOptions;
            for (var i = 0; i < options.length; i++) {
                if (options[i].id == nationalityId) {
                    if (options[i].identity_type_id == null) {
                        scope.selectedUserData.identity_type_id = identityOptions['0'].id;
                        scope.selectedUserData.identity_type_name = identityOptions['0'].name;
                    } else {
                        scope.selectedUserData.identity_type_id = options[i].identity_type_id;
                        scope.selectedUserData.identity_type_name = options[i].identity_type.name;
                    }
                    scope.selectedUserData.nationality_name = options[i].name;
                    break;
                }
            }
        } else {
            var nationalityId = scope.selectedGuardianData.nationality_id;
            var options = scope.nationalitiesOptions;
            var identityOptions = scope.identityTypeOptions;
            for (var i = 0; i < options.length; i++) {
                if (options[i].id == nationalityId) {
                    if (options[i].identity_type_id == null) {
                        scope.selectedGuardianData.identity_type_id = identityOptions['0'].id;
                        scope.selectedGuardianData.identity_type_name = identityOptions['0'].name;
                    } else {
                        scope.selectedGuardianData.identity_type_id = options[i].identity_type_id;
                        scope.selectedGuardianData.identity_type_name = options[i].identity_type.name;
                    }
                    scope.selectedGuardianData.nationality_name = options[i].name;
                    break;
                }
            }
        }
    }

    scope.changeIdentityType =  function() {
        if(!scope.isGuardianAdding) {
            var identityType = scope.selectedUserData.identity_type_id;
            var options = scope.identityTypeOptions;
            for (var i = 0; i < options.length; i++) {
                if (options[i].id == identityType) {
                    scope.selectedUserData.identity_type_name = options[i].name;
                    break;
                }
            }
        } else {
            var identityType = scope.selectedGuardianData.identity_type_id;
            var options = scope.identityTypeOptions;
            for (var i = 0; i < options.length; i++) {
                if (options[i].id == identityType) {
                    scope.selectedGuardianData.identity_type_name = options[i].name;
                    break;
                }
            }
        }
    }

    scope.changeContactType =  function() {
        if(!scope.isGuardianAdding) {
            var contactType = scope.selectedUserData.contact_type_id;
            var options = scope.contactTypeOptions;
            for (var i = 0; i < options.length; i++) {
                if (options[i].id == contactType) {
                    scope.selectedUserData.contact_type_name = options[i].name;
                    break;
                }
            }
        } else {
            var contactType = scope.selectedGuardianData.contact_type_id;
            var options = scope.contactTypeOptions;
            for (var i = 0; i < options.length; i++) {
                if (options[i].id == contactType) {
                    scope.selectedGuardianData.contact_type_name = options[i].name;
                    break;
                }
            }
        }
    }

    scope.changeRelationType = function() {}

    scope.goToPrevStep = function(){
        if(!scope.isGuardianAdding) {
            switch(scope.step){
                case 'internal_search': 
                    scope.step = 'user_details';
                    break;
                case 'external_search': 
                    scope.step = 'internal_search';
                    break;
                case 'confirmation': 
                    scope.step = 'external_search';
                    break;
            }
        } else {
            switch(scope.guardianStep){
                case 'internal_search': 
                    scope.guardianStep = 'user_details';
                    break;
                case 'external_search': 
                    scope.guardianStep = 'internal_search';
                    break;
                case 'confirmation': 
                    scope.guardianStep = 'external_search';
                    break;
            }
        }
    }

    scope.goToNextStep = function() {
        if(!scope.isGuardianAdding) {
            switch(scope.step){
                case 'user_details': 
                    scope.step = 'internal_search';
                    scope.getUniqueOpenEmisId();
                    break;
                case 'internal_search': 
                    scope.step = 'external_search';
                    break;
                case 'external_search': 
                    scope.step = 'confirmation';
                    scope.generatePassword();
                    break;
            }
        } else {
            switch(scope.guardianStep){
                case 'user_details': 
                    scope.guardianStep = 'internal_search';
                    scope.getUniqueOpenEmisId();
                    break;
                case 'internal_search': 
                    scope.guardianStep = 'external_search';
                    break;
                case 'external_search': 
                    scope.guardianStep = 'confirmation';
                    scope.generatePassword();
                    break;
            }
        }
        
    }

    scope.confirmUser = function () {
        scope.message = (scope.selectedUserData && scope.selectedUserData.userType ? scope.selectedUserData.userType.name : 'Student') + ' successfully added.';
        scope.messageClass = 'alert-success';
        if(!scope.isGuardianAdding)
            scope.step = "summary";
        else
            scope.guardianStep = "summary";
    }

    scope.goToFirstStep = function () {
        if(!scope.isGuardianAdding){
            scope.step = 'user_details';
            scope.selectedUserData = {};
        }
        else{
            scope.guardianStep = 'user_details';
            scope.selectedGuardianData = {};
        } 
    }

    scope.cancelProcess = function() {
        location.href = angular.baseUrl + '/Directory/Directories/Directories/index';
    }

    scope.addGuardian = function () {
        scope.isGuardianAdding = true;
    }
    
    scope.initGrid = function() {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            scope.internalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                    scope.selectStaff(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.internalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };

            scope.externalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
            scope.internalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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

            scope.externalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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

    scope.selectStaff = function(id) {
        scope.selectedStaff = id;
        scope.getStaffData();
    }

    scope.getStaffData = function() {
        var log = [];
        angular.forEach(scope.rowsThisPage , function(value) {
            if (value.id == scope.selectedStaff) {
                scope.selectedStaffData = value;
            }
        }, log);
    }
    
}