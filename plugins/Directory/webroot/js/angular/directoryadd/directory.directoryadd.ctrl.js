angular.module('directory.directoryadd.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryadd.svc'])
    .controller('DirectoryAddCtrl', DirectoryAddController);

DirectoryAddController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddguardianSvc'];

function DirectoryAddController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddguardianSvc) {
    var scope = $scope;

    scope.step = "user_details";
    scope.selectedUserData = {};
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
    scope.addressAreaOption = [];
    scope.birthplaceAreaOption = [];
    scope.isGuardianAdding = false;
    scope.pageSize = 10;
    scope.rowsThisPage = [];
    scope.selectedUser;

    scope.datepickerOptions = {
        maxDate: new Date(),
        showWeeks: false
    };

    angular.element(document).ready(function () {
        UtilsSvc.isAppendLoader(true);
        console.log(angular.baseUrl);
        DirectoryaddguardianSvc.init(angular.baseUrl);
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
        if(scope.selectedUserData.openemis_no){
            setTimeout(function(){
                scope.internalGridOptions = null;
                scope.getInternalSearchData();
            }, 1500);
            return;
        }
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.getUniqueOpenEmisId()
            .then(function(response) {
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
            scope.getInternalSearchData();
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getInternalSearchData = function() {
        var first_name = '';
        var last_name = '';
        var openemis_no = '';
        var date_of_birth = '';
        var identity_number = '';
        first_name = scope.selectedUserData.first_name;
        last_name = scope.selectedUserData.last_name;
        date_of_birth = scope.selectedUserData.date_of_birth;
        openemis_no = scope.selectedUserData.openemis_no;
        identity_number = scope.selectedUserData.identity_number;
        var dataSource = {
            pageSize: scope.pageSize,
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
                DirectoryaddguardianSvc.getInternalSearchData(param)
                .then(function(response) {
                    var gridData = response.data;
                    var totalRowCount = gridData.length;
                    return scope.processInternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        scope.internalGridOptions.api.setDatasource(dataSource);
        scope.internalGridOptions.api.sizeColumnsToFit(); 
    }

    scope.processInternalGridUserRecord = function(userRecords, params, totalRowCount) {
        console.log(userRecords);

        var lastRow = totalRowCount;
        scope.rowsThisPage = userRecords;

        params.successCallback(scope.rowsThisPage, lastRow);
        // scope.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    scope.getExternalSearchData = function() {
        var param = {
            first_name: scope.selectedUserData.first_name,
            last_name: scope.selectedUserData.last_name,
            date_of_birth: scope.selectedUserData.date_of_birth,
            identity_number: scope.selectedUserData.identity_number,
        }
        var dataSource = {
            pageSize: scope.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                DirectoryaddguardianSvc.getExternalSearchData(param)
                .then(function(response) {
                    var gridData = response.data;
                    var totalRowCount = gridData.length;
                    return scope.processExternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        scope.externalGridOptions.api.setDatasource(dataSource);
        scope.externalGridOptions.api.sizeColumnsToFit(); 
    }

    scope.processExternalGridUserRecord = function(userRecords, params, totalRowCount) {
        console.log(userRecords);

        var lastRow = totalRowCount;
        scope.rowsThisPage = userRecords;

        params.successCallback(scope.rowsThisPage, lastRow);
        // scope.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    scope.generatePassword = function() {
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.generatePassword()
        .then(function(response) {
            if (scope.selectedUserData.password == '' || typeof scope.selectedUserData.password == 'undefined') {
                scope.selectedUserData.password = response;
            }
            scope.getContactTypes();
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getUserTypes = function() {
        DirectoryaddguardianSvc.getUserTypes()
        .then(function(response) {
            scope.userTypeOptions = response.data;
            scope.getGenders();
        }, function(error) {
            console.log(error);
            scope.getGenders();
        });
    }

    scope.getGenders = function() {
        DirectoryaddguardianSvc.getGenders()
        .then(function(response) {
            scope.genderOptions = response.data;
            scope.getNationalities();
        }, function(error) {
            console.log(error);
            scope.getNationalities();
        });
    }

    scope.getNationalities = function() {
        DirectoryaddguardianSvc.getNationalities()
        .then(function(response) {
            scope.nationalitiesOptions = response.data;
            scope.getIdentityTypes();
        }, function(error) {
            console.log(error);
            scope.getIdentityTypes();
        });
    }

    scope.getIdentityTypes = function() {
        DirectoryaddguardianSvc.getIdentityTypes()
        .then(function(response) {
            scope.identityTypeOptions = response.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getContactTypes = function() {
        DirectoryaddguardianSvc.getContactTypes()
        .then(function(response) {
            scope.contactTypeOptions = response.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getRedirectToGuardian = function(){
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.getRedirectToGuardian()
        .then(function(resp) {
            scope.redirectToGuardian = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.setName = function() {
        var userData = scope.selectedUserData;
        userData.name = '';
        if (userData.hasOwnProperty('first_name')) {
            userData.name = userData.first_name.trim();
        }
        scope.appendName(userData, 'middle_name', true);
        scope.appendName(userData, 'third_name', true);
        scope.appendName(userData, 'last_name', true);
        scope.selectedUserData = userData;
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
    }

    scope.changeIdentityType =  function() {
        var identityType = scope.selectedUserData.identity_type_id;
        var options = scope.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == identityType) {
                scope.selectedUserData.identity_type_name = options[i].name;
                break;
            }
        }
    }

    scope.changeContactType =  function() {
        var contactType = scope.selectedUserData.contact_type_id;
        var options = scope.contactTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == contactType) {
                scope.selectedUserData.contact_type_name = options[i].name;
                break;
            }
        }
    }

    scope.goToInternalSearch = function(){
        UtilsSvc.isAppendLoader(true);
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
                    scope.selectUser(_e.node.data.id);
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
            setTimeout(function(){
                scope.getInternalSearchData();
            }, 1500);
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
                    scope.selectUser(_e.node.data.id);
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
            setTimeout(function(){
                scope.getInternalSearchData();
            }, 1500);
        });
    }

    scope.goToExternalSearch = function(){
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
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
                    scope.selectUser(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.externalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };
            setTimeout(function(){
                scope.getExternalSearchData();
            }, 1500);
        }, function(error){
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
                    scope.selectUser(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.externalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };setTimeout(function(){
                scope.getExternalSearchData();
            }, 1500);
        });
    }

    scope.goToPrevStep = function(){
        switch(scope.step){
            case 'internal_search': 
                scope.step = 'user_details';
                break;
            case 'external_search': 
                scope.step = 'internal_search';
                scope.internalGridOptions = null;
                scope.goToInternalSearch();
                break;
            case 'confirmation': 
                scope.step = 'external_search';
                scope.externalGridOptions = null;
                scope.goToExternalSearch();
                break;
        }
    }

    scope.goToNextStep = function() {
        switch(scope.step){
            case 'user_details': 
                scope.step = 'internal_search';
                scope.getUniqueOpenEmisId();
                break;
            case 'internal_search': 
                scope.step = 'external_search';
                UtilsSvc.isAppendLoader(true);
                setTimeout(function(){
                    scope.getExternalSearchData();
                }, 1500);
                break;
            case 'external_search': 
                scope.step = 'confirmation';
                scope.generatePassword();
                break;
        }
    }

    scope.confirmUser = function () {
        scope.message = (scope.selectedUserData && scope.selectedUserData.userType ? scope.selectedUserData.userType.name : 'Student') + ' successfully added.';
        scope.messageClass = 'alert-success';
        scope.step = "summary";
        scope.getRedirectToGuardian();
    }

    scope.goToFirstStep = function () {
        scope.step = 'user_details';
        scope.selectedUserData = {};
    }

    scope.cancelProcess = function() {
        location.href = angular.baseUrl + '/Directory/Directories/Directories/index';
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
                    scope.selectUser(_e.node.data.id);
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
                    scope.selectUser(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.externalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
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
                    scope.selectUser(_e.node.data.id);
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
                    scope.selectUser(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.externalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };
        });
    };

    scope.selectUser = function(id) {
        scope.selectedUser = id;
        scope.getUserData();
    }

    scope.getUserData = function() {
        var log = [];
        angular.forEach(scope.rowsThisPage , function(value) {
            if (value.id == scope.selectedUser) {
                scope.selectedUserData = value;
            }
        }, log);
    }

    scope.addGuardian=function(){
        $window.location.href = angular.baseUrl + '/Directory/Directories/Addguardian';
    }
    
}