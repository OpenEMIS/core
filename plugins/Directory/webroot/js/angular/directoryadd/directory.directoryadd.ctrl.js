angular.module('directory.directoryadd.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryadd.svc', 'kd-angular-tree-dropdown'])
    .controller('DirectoryAddCtrl', DirectoryAddController);

DirectoryAddController.$inject = ['$scope', '$q', '$window', '$http', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddSvc'];

function DirectoryAddController($scope, $q, $window, $http, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddSvc) {
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
    scope.dobDatepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    scope.error = {};
    scope.customFields = [];
    scope.customFieldsArray = [];
    var todayDate = new Date();
    scope.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
    scope.redirectToGuardian = false;
    scope.isInternalSearchSelected = false;

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
            $window.localStorage.removeItem('studentOpenEmisId');
        }
        scope.initGrid();
        scope.getUserTypes();
    });

    scope.getUniqueOpenEmisId = function() {
        if(scope.selectedUserData.openemis_no){
            scope.generatePassword();
            return;
        }
        UtilsSvc.isAppendLoader(true);
        DirectoryaddSvc.getUniqueOpenEmisId()
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
                    user_type_id: scope.selectedUserData.user_type_id,
                };
                DirectoryaddSvc.getInternalSearchData(param)
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
                DirectoryaddSvc.getExternalSearchData(param)
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
        DirectoryaddSvc.generatePassword()
        .then(function(response) {
            if (scope.selectedUserData.password == '' || typeof scope.selectedUserData.password == 'undefined') {
                scope.selectedUserData.password = response;
            }
            scope.getContactTypes();
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
            if(scope.selectedUserData.userType.name === 'Students') {
                scope.getStudentCustomFields();
            } else {
                UtilsSvc.isAppendLoader(false);
            }
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getRedirectToGuardian = function(){
        UtilsSvc.isAppendLoader(true);
        DirectoryaddSvc.getRedirectToGuardian()
        .then(function(resp) {
            scope.redirectToGuardian = resp.data[0].redirecttoguardian_status;
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
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
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
                    scope.selectUserFromInternalSearch(_e.node.data.id);
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
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
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
                    scope.selectUserFromInternalSearch(_e.node.data.id);
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
                    scope.selectUserFromExternalSearch(_e.node.data.id);
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
                    scope.selectUserFromExternalSearch(_e.node.data.id);
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
            if(!scope.selectedUserData.user_type_id){
                scope.error.user_type_id = 'This field cannot be left empty';
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
                scope.selectedUserData.date_of_birth = $filter('date')(scope.selectedUserData.date_of_birth, 'yyyy-MM-dd');
            }
    
            if(!scope.selectedUserData.user_type_id || !scope.selectedUserData.first_name || !scope.selectedUserData.last_name || !scope.selectedUserData.gender_id || !scope.selectedUserData.date_of_birth){
                return;
            }
            scope.step = 'internal_search';
            scope.internalGridOptions = null;
            scope.goToInternalSearch();
        }
        if(scope.step === 'confirmation') {
            let isCustomFieldNotValidated = false;
            if(!scope.selectedUserData.username){
                scope.error.username = 'This field cannot be left empty';
            }
            if(!scope.selectedUserData.password){
                scope.error.password = 'This field cannot be left empty';
            }
            scope.customFieldsArray.forEach((customField) => {
                customField.data.forEach((field) => {
                    if(field.is_mandatory === 1) {
                        if(field.field_type === 'TEXT' || field.field_type === 'TEXTAREA' || field.field_type === 'NOTE' || field.field_type === 'DROPDOWN' || field.field_type === 'NUMBER' || field.field_type === 'DECIMAL' || field.field_type === 'DATE' || field.field_type === 'TIME') {
                            if(!field.answer) {
                                field.errorMessage = 'This field is required.';
                                isCustomFieldNotValidated = true;
                            }
                        } else if(field.field_type === 'CHECKBOX') {
                            if(field.answer.length === 0) {
                                field.errorMessage = 'This field is required.';
                                isCustomFieldNotValidated = true;
                            }
                        }
                    }
                })
            });
            if(!scope.selectedUserData.username || !scope.selectedUserData.password || isCustomFieldNotValidated){
                return;
            }
            scope.saveDetails();
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
                    scope.internalGridOptions = null;
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

    scope.confirmUser = function () {
        scope.message = (scope.selectedUserData && scope.selectedUserData.userType ? scope.selectedUserData.userType.name : 'Student') + ' successfully added.';
        scope.messageClass = 'alert-success';
        scope.step = "summary";
        var todayDate = new Date();
        scope.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
        if(scope.selectedUserData.userType.name === 'Students')
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
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
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
                    scope.selectUserFromInternalSearch(_e.node.data.id);
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
                    scope.selectUserFromExternalSearch(_e.node.data.id);
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
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
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
                    scope.selectUserFromInternalSearch(_e.node.data.id);
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
                    scope.selectUserFromExternalSearch(_e.node.data.id);
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

    scope.selectUserFromInternalSearch = function(id) {
        scope.selectedUser = id;
        scope.isInternalSearchSelected = true;
        scope.getUserData();
    }

    scope.selectUserFromExternalSearch = function(id) {
        scope.selectedUser = id;
        scope.isInternalSearchSelected = false;
        scope.getUserData();
    }

    scope.setUserData = function(selectedData) {
        scope.selectedUserData.openemis_no = selectedData.openemis_no;
        scope.selectedUserData.first_name = selectedData.first_name;
        scope.selectedUserData.middle_name = selectedData.middle_name;
        scope.selectedUserData.third_name = selectedData.third_name;
        scope.selectedUserData.last_name = selectedData.last_name;
        scope.selectedUserData.preferred_name = selectedData.preferred_name;
        scope.selectedUserData.date_of_birth = selectedData.date_of_birth;
        scope.selectedUserData.email = selectedData.email;
        scope.selectedUserData.gender_id = selectedData.gender_id;
        scope.selectedUserData.gender = {name: selectedData.gender};
        scope.selectedUserData.nationality_id = selectedData.nationality_id;
        scope.selectedUserData.nationality_name = selectedData.nationality;
        scope.selectedUserData.identity_type_id = selectedData.identity_type_id;
        scope.selectedUserData.identity_type_name = selectedData.identity_type;
        scope.selectedUserData.identity_number = selectedData.identity_number;
        scope.selectedUserData.username = selectedData.username;
        scope.selectedUserData.password = selectedData.password;
        scope.selectedUserData.address = selectedData.address;
        scope.selectedUserData.postalCode = selectedData.postal_code;
        scope.selectedUserData.address_area_id = selectedData.address_area_id;
        scope.selectedUserData.birthplace_area_id = selectedData.birthplace_area_id;
        scope.selectedUserData.addressArea = {name: selectedData.area_name};
        scope.selectedUserData.birthplaceArea = {name: selectedData.birth_area_name};
    }

    scope.getUserData = function() {
        var log = [];
        angular.forEach(scope.rowsThisPage , function(value) {
            if (value.id == scope.selectedUser) {
                scope.setUserData(value);
            }
        }, log);
    }

    scope.addGuardian = function(){
        if($window.localStorage.getItem('studentOpenEmisId')) {
            $window.localStorage.removeItem('studentOpenEmisId');
        }
        $window.localStorage.setItem('studentOpenEmisId', scope.selectedUserData.openemis_no);
        $window.location.href = angular.baseUrl + '/Directory/Directories/Addguardian';
    }
    
    scope.getStudentCustomFields = function() {
        DirectoryaddSvc.getStudentCustomFields().then(function(resp){
            scope.customFields = resp.data;
            scope.customFieldsArray = [];
            scope.createCustomFieldsArray();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.createCustomFieldsArray = function() {
        if(scope.customFields && scope.customFields.length > 0) {
            var selectedCustomField = scope.customFields;
            var filteredSections = Array.from(new Set(scope.customFields.map((item)=> scope.mapBySection(item))));
            filteredSections.forEach((section)=>{
                let filteredArray = selectedCustomField.filter((item) => scope.filterBySection(item, section));
                scope.customFieldsArray.push({sectionName: section , data: filteredArray});
            });
            scope.customFieldsArray.forEach((customField) => {
                customField.data.forEach((fieldData) => {
                    fieldData.answer = '';
                    fieldData.errorMessage = '';
                    if(fieldData.field_type === 'DROPDOWN') {
                        fieldData.selectedOptionId = '';
                    }
                    if(fieldData.field_type === 'DATE') {
                        fieldData.isDatepickerOpen = false;
                        let params = JSON.parse(fieldData.params);
                        fieldData.params = params;
                        fieldData.datePickerOptions = {
                            minDate: fieldData.params && fieldData.params.start_date ? new Date(fieldData.params.start_date): new Date(),
                            maxDate: new Date('01/01/2100'),
                            showWeeks: false
                        };
                    }
                    if(fieldData.field_type === 'TIME') {
                        fieldData.hourStep = 1;
                        fieldData.minuteStep = 5;
                        fieldData.isMeridian = true;
                        let params = JSON.parse(fieldData.params);
                        fieldData.params = params;
                        if(fieldData.params && fieldData.params.start_time) {
                            var startTimeArray = fieldData.params.start_time.split(" ");
                            var startTimes = startTimeArray[0].split(":");
                            if(startTimes[0] === 12) {
                                var startTimeHour = startTimeArray[1] === 'PM' ? Number(startTimes[0]) : Number(startTimes[0]) - 12;
                            } else {
                                var startTimeHour = startTimeArray[1] === 'AM' ? Number(startTimes[0]) : Number(startTimes[0]) + 12;
                            } 
                        }
                        if(fieldData.params && fieldData.params.end_time) {
                            var endTimeArray = fieldData.params.end_time.split(" ");
                            var endTimes = endTimeArray[0].split(":");
                            if(startTimes[0] === 12) {
                                var endTimeHour = endTimeArray[1] === 'PM' ? Number(endTimes[0]) : Number(endTimes[0]) - 12;
                            } else {
                                var endTimeHour = endTimeArray[1] === 'AM' ? Number(endTimes[0]) : Number(endTimes[0]) + 12;
                            }
                        }
                        fieldData.answer = fieldData.params && fieldData.params.start_time ? new Date(new Date(new Date().setHours(startTimeHour)).setMinutes(startTimes[1])): new Date();
                        fieldData.min = params && params.start_time ? new Date(new Date(new Date().setHours(startTimeHour)).setMinutes(startTimes[1])): new Date();
                        fieldData.max = fieldData.params && fieldData.params.end_time ? new Date(new Date(new Date().setHours(endTimeHour)).setMinutes(endTimes[1])): new Date();
                    }
                    if(fieldData.field_type === 'CHECKBOX') {
                        fieldData.answer = [];
                        fieldData.option.forEach((option) => {
                            option.selected = false;
                        })
                    }
                    if(fieldData.field_type === 'DECIMAL') {
                        let params = JSON.parse(fieldData.params);
                        fieldData.params = params;
                    }
                });
            });
        }
        
    }

    scope.mapBySection = function(item) {
        return item.section;
    }

    scope.filterBySection = function(item, section) {
        return section === item.section;
    }

    scope.changeOption = function(field, optionId){
        field.option.forEach((option) => {
            if(option.option_id === optionId){
                field.selectedOption = option.option_name;
            }
        })
    }

    scope.changed = function(answer){
        console.log(answer);
    }

    scope.selectOption = function (field) {
        field.answer = [];
        field.option.forEach((option) => {
            if(option.selected) {
                field.answer.push(option.option_id);
            }
        })
    }

    scope.onDecimalNumberChange = function(field) {
        let timer;
        if(timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(()=>{
            field.answer = parseFloat(field.answer.toFixed(field.params.precision));
        }, 3000);
    }

    scope.saveDetails = function() {
        scope.selectedUserData.addressArea = DirectoryaddSvc.getAddressArea();
        scope.selectedUserData.birthplaceArea = DirectoryaddSvc.getBirthplaceArea();
        let param = {
            user_type: scope.selectedUserData.user_type_id,
            openemis_no: scope.selectedUserData.openemis_no,
            first_name: scope.selectedUserData.first_name,
            middle_name: scope.selectedUserData.middle_name,
            third_name: scope.selectedUserData.third_name,
            last_name: scope.selectedUserData.last_name,
            preferred_name: scope.selectedUserData.preferred_name,
            gender_id: scope.selectedUserData.gender_id,
            date_of_birth: scope.selectedUserData.date_of_birth,
            identity_number: scope.selectedUserData.identity_number,
            identity_type_id: scope.selectedUserData.identity_type_id,
            nationality_id: scope.selectedUserData.nationality_id,
            username: scope.selectedUserData.username,
            password: scope.selectedUserData.password,
            postal_code: scope.selectedUserData.postalCode,
            address: scope.selectedUserData.address,
            birthplace_area_id: DirectoryaddSvc.getBirthplaceAreaId(),
            address_area_id: DirectoryaddSvc.getAddressAreaId(),
            contact_type: scope.selectedUserData.contact_type_id,
            contact_value: scope.selectedUserData.contactValue,
            photo_name: scope.selectedUserData.photo_name,
            photo_content: scope.selectedUserData.photo_base_64,
            custom: [],
        };
        if(scope.selectedUserData.userType.name === 'Students') {
            scope.customFieldsArray.forEach((customField)=> {
                customField.data.forEach((field)=> {
                    if(field.field_type !== 'CHECKBOX') {
                        let fieldData = {
                            custom_field_id: field.student_custom_field_id,
                            text_value:"",
                            number_value:null,
                            decimal_value:"",
                            textarea_value:"",
                            time_value:"",
                            date_value:"",
                            file:"",
                        };
                        if(field.field_type === 'TEXT' || field.field_type === 'NOTE' || field.field_type === 'TEXTAREA') {
                            fieldData.text_value = field.answer;
                        }
                        if(field.field_type === 'NUMBER') {
                            fieldData.number_value = field.answer;
                        }
                        if(field.field_type === 'DECIMAL') {
                            fieldData.decimal_value = String(field.answer);
                        }
                        if(field.field_type === 'DROPDOWN') {
                            fieldData.number_value = Number(field.answer);
                        }
                        if(field.field_type === 'TIME') {
                            let time = field.answer.toLocaleTimeString();
                            let timeArray = time.split(':');
                            fieldData.time_value = `${timeArray[0]}:${timeArray[1]}`;
                        }
                        if(field.field_type === 'DATE') {
                            fieldData.date_value = $filter('date')(field.anser, 'yyyy-MM-dd');
                        }
                        param.custom.push(fieldData);
                    } else {
                        field.answer.forEach((id )=> {
                            let fieldData = {
                                custom_field_id: field.student_custom_field_id,
                                text_value:"",
                                number_value: Number(id),
                                decimal_value:"",
                                textarea_value:"",
                                time_value:"",
                                date_value:"",
                                file:"",
                            };
                            param.custom.push(fieldData);
                        });
                    }
                })
            });
        }
        UtilsSvc.isAppendLoader(true);
        DirectoryaddSvc.saveDirectoryData(param).then(function(resp){
            scope.confirmUser();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }
}
