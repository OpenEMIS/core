angular.module('directory.directoryaddguardian.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryaddguardian.svc', 'kd-angular-tree-dropdown'])
    .controller('DirectoryaddguardianCtrl', DirectoryaddguardianController);

DirectoryaddguardianController.$inject = ['$scope', '$q', '$window', '$http', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddguardianSvc'];

function DirectoryaddguardianController($scope, $q, $window, $http, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddguardianSvc) {
    var scope = $scope;
    scope.step = 'user_details';
    scope.selectedUserData = {};
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
    scope.pageSize = 10;
    scope.rowsThisPage = [];
    scope.selectedGuardian;
    scope.error = {};
    scope.studentOpenEmisId;
    scope.isInternalSearchSelected = false;

    scope.datepickerOptions = {
        minDate: new Date('01/01/1900'),
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
        if($window.localStorage.getItem('address_area')) {
            $window.localStorage.removeItem('address_area')
        }
        if($window.localStorage.getItem('address_area_id')) {
            $window.localStorage.removeItem('address_area_id')
        }
        if($window.localStorage.getItem('birthplace_area')) {
            $window.localStorage.removeItem('birthplace_area')
        }
        if($window.localStorage.getItem('birthplace_area_id')) {
            $window.localStorage.removeItem('birthplace_area_id')
        }
        if($window.localStorage.getItem('studentOpenEmisId')) {
            scope.studentOpenEmisId = $window.localStorage.getItem('studentOpenEmisId');
        }
        scope.initGrid();
        scope.getRelationType();
    });

    $window.savePhoto = function(event) {
        let photo = event.files[0];
        scope.selectedUserData.photo = photo;
        scope.selectedUserData.photo_name = photo.name;
        let fileReader = new FileReader();
        fileReader.readAsDataURL(photo);
        fileReader.onload = () => {
            console.log(fileReader.result);
            scope.selectedUserData.photo_base_64 = fileReader.result;
        }
    }

    scope.getUniqueOpenEmisId = function() {
        if(scope.selectedUserData.openemis_no){
            scope.selectedUserData.username = scope.selectedUserData.openemis_no;
            scope.generatePassword();
            return;
        }
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.getUniqueOpenEmisId()
        .then(function(response) {
            scope.selectedUserData.username = response;
            scope.selectedUserData.openemis_no = response;
            scope.generatePassword();
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getInternalSearchData = function() {
        var first_name = '';
        var last_name = '';
        var openemis_no = null;
        var date_of_birth = '';
        var identity_number = '';
        first_name = scope.selectedUserData.first_name;
        last_name = scope.selectedUserData.last_name;
        date_of_birth = scope.selectedUserData.date_of_birth;
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
                    institution_id: null,
                    user_type_id: 3,
                }
                DirectoryaddguardianSvc.getInternalSearchData(param)
                .then(function(response) {
                    var gridData = response.data.data;
                    if(!gridData)
                        gridData = [];
                    var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
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
                    var gridData = response.data.data;
                    if(!gridData)
                        gridData = [];
                    var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
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
            scope.selectedUserData.password = response;
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
        var guardianData = scope.selectedUserData;
        guardianData.name = '';
        if (guardianData.hasOwnProperty('first_name')) {
            guardianData.name = guardianData.first_name.trim();
        }
        scope.appendName(guardianData, 'middle_name', true);
        scope.appendName(guardianData, 'third_name', true);
        scope.appendName(guardianData, 'last_name', true);
        scope.selectedUserData = guardianData;
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
        var guardianData = scope.selectedUserData;
        if (guardianData.hasOwnProperty('gender_id')) {
            var genderOptions = scope.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == guardianData.gender_id) {
                    guardianData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            scope.selectedUserData = guardianData;
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

    scope.changeRelationType = function() {
        var relationType = scope.selectedUserData.relation_type_id;
        var relationTypeOptions = scope.contactTypeOptions;
        for (var i = 0; i < relationTypeOptions.length; i++) {
            if (relationTypeOptions[i].id == relationType) {
                scope.selectedUserData.relation_type_name = relationTypeOptions[i].name;
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
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
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
                    scope.selectGuardianFromInternalSearch(_e.node.data.id);
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
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
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
                    scope.selectGuardianFromInternalSearch(_e.node.data.id);
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
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
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
                    scope.selectGuardianFromExternalSearch(_e.node.data.id);
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
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
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
                    scope.selectGuardianFromExternalSearch(_e.node.data.id);
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

    scope.validateDetails = function() {
        if(scope.step === 'user_details') {
            if(!scope.selectedUserData.relation_type_id){
                scope.error.relation_type_id = 'This field cannot be left empty';
            }
            if(!scope.selectedUserData.first_name){
                scope.error.first_name = 'This field cannot be left empty';
            }
            if(!scope.selectedUserData.last_name){
                scope.error.last_name = 'This field cannot be left empty';
            }
            if(!scope.selectedUserData.gender_id){
                scope.error.gender_id = 'This field cannot be left empty';
            }
            if(!scope.selectedUserData.date_of_birth) {
                scope.error.date_of_birth = 'This field cannot be left empty';
            } else {
                // let dob = scope.selectedUserData.date_of_birth.toLocaleDateString();
                // let dobArray = dob.split('/');
                scope.selectedUserData.date_of_birth = $filter('date')(scope.selectedUserData.date_of_birth, 'yyyy-MM-dd');
            }
    
            if(!scope.selectedUserData.relation_type_id || !scope.selectedUserData.first_name || !scope.selectedUserData.last_name || !scope.selectedUserData.gender_id || !scope.selectedUserData.date_of_birth){
                return;
            }
            scope.step = 'internal_search';
            scope.internalGridOptions = null;
            scope.goToInternalSearch();
        }
        if(scope.step === 'confirmation') {
            if(!scope.selectedUserData.username){
                scope.error.username = 'This field cannot be left empty';
            }
            if(!scope.selectedUserData.password){
                scope.error.password = 'This field cannot be left empty';
            }
            if(!scope.selectedUserData.username || !scope.selectedUserData.password){
                return;
            }
            scope.saveGuardianDetails();
        }
    }

    scope.goToPrevStep = function(){
        if(scope.isInternalSearchSelected) {
            scope.step = 'internal_search';
            scope.internalGridOptions = null;
            scope.goToInternalSearch();
        } else {
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
    }

    scope.goToNextStep = function() {
        if(scope.isInternalSearchSelected) {
            scope.step = 'confirmation';
            scope.getUniqueOpenEmisId();
        } else {
            switch(scope.step){
                case 'user_details': 
                    scope.validateDetails();
                    break;
                case 'internal_search': 
                    scope.step = 'external_search';
                    scope.externalGridOptions = null;
                    UtilsSvc.isAppendLoader(true);
                    scope.goToExternalSearch();
                    break;
                case 'external_search': 
                    scope.step = 'confirmation';
                    scope.getUniqueOpenEmisId();
                    break;
            }
        }
    }

    scope.cancelProcess = function() {
        $window.history.back();
    }
    
    scope.initGrid = function() {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            scope.internalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
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
                    scope.selectGuardianFromInternalSearch(_e.node.data.id);
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
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
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
                    scope.selectGuardianFromExternalSearch(_e.node.data.id);
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
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
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
                    scope.selectGuardianFromInternalSearch(_e.node.data.id);
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
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
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
                    scope.selectGuardianFromExternalSearch(_e.node.data.id);
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

    scope.selectGuardianFromInternalSearch = function(id) {
        scope.selectedGuardian = id;
        scope.isInternalSearchSelected = true;
        scope.getGuardianData();
    }

    scope.selectGuardianFromExternalSearch = function(id) {
        scope.selectedGuardian = id;
        scope.isInternalSearchSelected = false;
        scope.getGuardianData();
    }

    scope.getGuardianData = function() {
        var log = [];
        angular.forEach(scope.rowsThisPage , function(value) {
            if (value.id == scope.selectedGuardian) {
                scope.selectedUserData = value;
            }
        }, log);
    }

    scope.saveGuardianDetails = function() {
        scope.selectedUserData.addressArea = DirectoryaddguardianSvc.getAddressArea();
        scope.selectedUserData.birthplaceArea = DirectoryaddguardianSvc.getBirthplaceArea();
        var params = {
            guardian_relation_id: scope.selectedUserData.relation_type_id,
            student_openemis_no: scope.studentOpenEmisId,
            openemis_no: scope.selectedUserData.openemis_no,
            first_name: scope.selectedUserData.first_name,
            middle_name: scope.selectedUserData.middle_name,
            third_name: scope.selectedUserData.third_name,
            last_name: scope.selectedUserData.last_name,
            preferred_name: scope.selectedUserData.preferred_name,
            gender_id: scope.selectedUserData.gender_id,
            date_of_birth: scope.selectedUserData.date_of_birth,
            identity_number: scope.selectedUserData.identity_number,
            nationality_id: scope.selectedUserData.nationality_id,
            username: scope.selectedUserData.username,
            password: scope.selectedUserData.password,
            postal_code: scope.selectedUserData.postalCode,
            address: scope.selectedUserData.address,
            birthplace_area_id: DirectoryaddguardianSvc.getBirthplaceAreaId(),
            address_area_id: DirectoryaddguardianSvc.getAddressAreaId(),
            identity_type_id: scope.selectedUserData.identity_type_id,
            photo_name: scope.selectedUserData.photo_name,
            photo_content: scope.selectedUserData.photo_base_64,
            contact_type: scope.selectedUserData.contact_type_id,
            contact_value: scope.selectedUserData.contactValue,
        };
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.saveGuardianDetails(params)
        .then(function(response) {
            scope.message = (scope.selectedUserData && scope.selectedUserData.relation_type_name ? scope.selectedUserData.relation_type_name : 'Guardian') + ' successfully added.';
            scope.messageClass = 'alert-success';
            scope.step = "summary";
            var todayDate = new Date();
            scope.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }
}