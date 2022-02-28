angular.module('directory.directoryaddguardian.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryaddguardian.svc'])
    .controller('DirectoryaddguardianCtrl', DirectoryaddguardianController);

DirectoryaddguardianController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddguardianSvc'];

function DirectoryaddguardianController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddguardianSvc) {
    var scope = $scope;
    scope.step = 'user_details';
    scope.selectedGuardianData = {};
    scope.internalGridOptions = null;
    scope.externalGridOptions = null;
    scope.postRespone = null;
    scope.translateFields = null;
    scope.genderOptions = [];
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
        scope.getRelationType();
    });

    scope.getUniqueOpenEmisId = function() {
        if(scope.selectedGuardianData.openemis_no){
            setTimeout(function(){
                scope.internalGridOptions = null;
                scope.getInternalSearchData();
            }, 1500);
            return;
        }
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.getUniqueOpenEmisId()
        .then(function(response) {
            scope.selectedGuardianData.username = response;
            scope.selectedGuardianData.openemis_no = response;
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
        first_name = scope.selectedGuardianData.first_name;
        last_name = scope.selectedGuardianData.last_name;
        date_of_birth = scope.selectedGuardianData.date_of_birth;
        openemis_no = scope.selectedGuardianData.openemis_no;
        identity_number = scope.selectedGuardianData.identity_number;
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
                    var gridData = response.data.data;
                    var totalRowCount = response.data.total;
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
        var param = {};
        param = {
            first_name: scope.selectedGuardianData.first_name,
            last_name: scope.selectedGuardianData.last_name,
            date_of_birth: scope.selectedGuardianData.date_of_birth,
            identity_number: scope.selectedGuardianData.identity_number,
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
            scope.selectedGuardianData.password = response;
            scope.getContactTypes();
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
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
    
    scope.getRelationType = function(){
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.getRelationType()
        .then(function(response) {
            scope.relationTypeOptions = response.data;
            scope.getGenders();
        }, function(error) {
            console.log(error);
            scope.getGenders();
        });
    }

    scope.setName = function() {
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

    scope.changeNationality =  function() {
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

    scope.changeIdentityType =  function() {
        var identityType = scope.selectedGuardianData.identity_type_id;
        var options = scope.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == identityType) {
                scope.selectedGuardianData.identity_type_name = options[i].name;
                break;
            }
        }
    }

    scope.changeContactType =  function() {
        var contactType = scope.selectedGuardianData.contact_type_id;
        var options = scope.contactTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == contactType) {
                scope.selectedGuardianData.contact_type_name = options[i].name;
                break;
            }
        }
    }

    scope.changeRelationType = function() {
        var relationType = scope.selectedGuardianData.relation_type_id;
        var relationTypeOptions = scope.contactTypeOptions;
        for (var i = 0; i < relationTypeOptions.length; i++) {
            if (relationTypeOptions[i].id == relationType) {
                scope.selectedGuardianData.relation_type_name = relationTypeOptions[i].name;
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
                    scope.selectGuardian(_e.node.data.id);
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
                    scope.selectGuardian(_e.node.data.id);
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
                    scope.selectGuardian(_e.node.data.id);
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
                    scope.selectGuardian(_e.node.data.id);
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

    scope.goToFirstStep = function () {
        scope.step = 'user_details';
        scope.selectedGuardianData = {};
        scope.internalGridOptions = null;
        scope.externalGridOptions = null;
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
                    scope.selectGuardian(_e.node.data.id);
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
                    scope.selectGuardian(_e.node.data.id);
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
                    scope.selectGuardian(_e.node.data.id);
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
                    scope.selectGuardian(_e.node.data.id);
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

    scope.selectGuardian = function(id) {
        scope.selectedGuardian = id;
        scope.getGuardianData();
    }

    scope.getGuardianData = function() {
        var log = [];
        angular.forEach(scope.rowsThisPage , function(value) {
            if (value.id == scope.selectedGuardian) {
                scope.selectedGuardianData = value;
            }
        }, log);
    }

    scope.saveGuardianDetails = function() {
        var params = {
            guardian_relation_id: scope.selectedGuardianData.relation_type_id,
            student_id: 1,
            openemis_no: scope.selectedGuardianData.openemis_no,
            first_name: scope.selectedGuardianData.first_name,
            middle_name: scope.selectedGuardianData.middle_name,
            third_name: scope.selectedGuardianData.third_name,
            last_name: scope.selectedGuardianData.last_name,
            preferred_name: scope.selectedGuardianData.preferred_name,
            gender_id: scope.selectedGuardianData.gender_id,
            date_of_birth: scope.selectedGuardianData.date_of_birth.toLocaleDateString(),
            identity_number: scope.selectedGuardianData.identity_number,
            nationality_id: scope.selectedGuardianData.nationality_id,
            username: scope.selectedGuardianData.username,
            password: scope.selectedGuardianData.password,
            postal_code: scope.selectedGuardianData.postalCode,
            address: scope.selectedGuardianData.address,
            birthplace_area_id: 2,
            address_area_id: 2,
            identity_type_id: scope.selectedGuardianData.identity_type_id,
        };
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.saveGuardianDetails(params)
        .then(function(response) {
            scope.message = (scope.selectedGuardianData && scope.selectedGuardianData.relation_type_name ? scope.selectedGuardianData.relation_type_name : 'Guardian') + ' successfully added.';
            scope.messageClass = 'alert-success';
            scope.step = "summary";
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }
}