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
    scope.isExternalSearchSelected = false;
    scope.isIdentityUserExist = false;
    scope.isExternalSearchEnable = false;
    scope.externalSearchSourceName = '';
    scope.disableFields = {
        username: true,
        password: true
    }
    scope.isSearchResultEmpty = false;
    scope.datepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    scope.addressAreaId = null;
    scope.birthplaceAreaId = null;

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
        if ($window.localStorage.getItem('address_area')) {
            $window.localStorage.removeItem('address_area')
        }
        if ($window.localStorage.getItem('address_area_id')) {
            $window.localStorage.removeItem('address_area_id')
        }
        if ($window.localStorage.getItem('birthplace_area')) {
            $window.localStorage.removeItem('birthplace_area')
        }
        if ($window.localStorage.getItem('birthplace_area_id')) {
            $window.localStorage.removeItem('birthplace_area_id')
        }
        if ($window.localStorage.getItem('studentOpenEmisId')) {
            scope.studentOpenEmisId = $window.localStorage.getItem('studentOpenEmisId');
        }
        scope.initGrid();
        scope.getRelationType();
        try {
            //POCOR-7231::Start
            if (window.location.href.indexOf("Institution") > -1) {
                const queryString2 = getParameterByName('queryString2');
                const queryData1 = JSON.parse(window.atob(queryString2))
                if (Object.keys(queryData1)) {
                    const {institution_id, openemis_no} = queryData1;
                    scope.selectedUserData.institution_id = institution_id;
                    scope.studentOpenEmisId = openemis_no;
                    $window.localStorage.setItem('studentOpenEmisId', openemis_no)
                }
            } else {
                const queryString = window.location.href.split('?')[1].split('=')[1].replace(/%3D/g, '')
                const queryData = JSON.parse(window.atob(queryString))
                if (Object.keys(queryData)) {
                    const {institution_id, openemis_no} = queryData;
                    scope.selectedUserData.institution_id = institution_id;
                    scope.studentOpenEmisId = openemis_no;
                    $window.localStorage.setItem('studentOpenEmisId', openemis_no)
                }
            }
            //POCOR-7231::End

        } catch (err) {
            console.warn(err)
        }

    });

    function getParameterByName(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    $window.savePhoto = function (event) {
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

    scope.getUniqueOpenEmisId = function () {
        if ((scope.isExternalSearchSelected || scope.isInternalSearchSelected) && scope.selectedUserData.openemis_no && !isNaN(Number(scope.selectedUserData.openemis_no.toString()))) {
            scope.selectedUserData.username = angular.copy(scope.selectedUserData.openemis_no);
            scope.generatePassword();
            return;
        }
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.getUniqueOpenEmisId()
            .then(function (response) {
                scope.selectedUserData.openemis_no = response;
                scope.selectedUserData.username = angular.copy(scope.selectedUserData.openemis_no);
                scope.generatePassword();
            }, function (error) {
                console.log(error);
                UtilsSvc.isAppendLoader(false);
            });
    }

    scope.getInternalSearchData = function () {
        var first_name = '';
        var last_name = '';
        var openemis_no = null;
        var date_of_birth = '';
        var identity_number = '';
        var nationality_id = '';
        var nationality_name = '';
        var identity_type_name = '';
        var identity_type_id = '';
        first_name = scope.selectedUserData.first_name;
        last_name = scope.selectedUserData.last_name;
        date_of_birth = scope.selectedUserData.date_of_birth;
        identity_number = scope.selectedUserData.identity_number;
        openemis_no = scope.selectedUserData.openemis_no;
        nationality_id = scope.selectedUserData.nationality_id;
        nationality_name = scope.selectedUserData.nationality_name;
        identity_type_name = scope.selectedUserData.identity_type_name;
        identity_type_id = scope.selectedUserData.identity_type_id;

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
                    nationality_id: nationality_id,
                    nationality_name: nationality_name,
                    identity_type_name: identity_type_name,
                    identity_type_id: identity_type_id
                }
                DirectoryaddguardianSvc.getInternalSearchData(param)
                    .then(function (response) {
                        var gridData = response.data.data;
                        if (!gridData)
                            gridData = [];
                        var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                        scope.isSearchResultEmpty = gridData.length === 0;
                        return scope.processInternalGridUserRecord(gridData, params, totalRowCount);
                    }, function (error) {
                        console.log(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };
        scope.internalGridOptions.api.setDatasource(dataSource);
        scope.internalGridOptions.api.sizeColumnsToFit();
    }

    scope.processInternalGridUserRecord = function (userRecords, params, totalRowCount) {
        console.log(userRecords);
        if (userRecords.length === 0) {
            params.failCallback([], totalRowCount);
            UtilsSvc.isAppendLoader(false);
            return;
        }

        var lastRow = totalRowCount;
        scope.rowsThisPage = userRecords;

        params.successCallback(scope.rowsThisPage, lastRow);
        // scope.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    scope.getExternalSearchData = function () {
        var param = {};
        param = {
            first_name: scope.selectedUserData.first_name,
            last_name: scope.selectedUserData.last_name,
            date_of_birth: scope.selectedUserData.date_of_birth,
            identity_number: scope.selectedUserData.identity_number,
            openemis_no: scope.selectedUserData.openemis_no
        }
        var dataSource = {
            pageSize: scope.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                DirectoryaddguardianSvc.getExternalSearchData(param)
                    .then(function (response) {
                        var gridData = response.data.data;
                        if (!gridData)
                            gridData = [];
                        gridData.forEach((data) => {
                            data.gender = data['gender.name'];
                            data.nationality = data['main_nationality.name'];
                            data.identity_type = data['main_identity_type.name'];
                            data.gender_id = data['gender.id'];
                            data.nationality_id = data['main_nationality.id'];
                            data.identity_type_id = data['main_identity_type.id'];
                        });
                        var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                        scope.isSearchResultEmpty = gridData.length === 0;
                        return scope.processExternalGridUserRecord(gridData, params, totalRowCount);
                    }, function (error) {
                        console.log(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };
        scope.externalGridOptions.api.setDatasource(dataSource);
        scope.externalGridOptions.api.sizeColumnsToFit();
    }

    scope.processExternalGridUserRecord = function (userRecords, params, totalRowCount) {
        console.log(userRecords);
        if (userRecords.length === 0) {
            params.failCallback([], totalRowCount);
            UtilsSvc.isAppendLoader(false);
            return;
        }

        var lastRow = totalRowCount;
        scope.rowsThisPage = userRecords;

        params.successCallback(scope.rowsThisPage, lastRow);
        // scope.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    scope.generatePassword = function () {
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.generatePassword()
            .then(function (response) {
                scope.selectedUserData.password = response;
                scope.getContactTypes();
                UtilsSvc.isAppendLoader(false);
            }, function (error) {
                console.log(error);
                UtilsSvc.isAppendLoader(false);
            });
    }

    scope.getGenders = function () {
        DirectoryaddguardianSvc.getGenders()
            .then(function (response) {
                scope.genderOptions = response.data;
                scope.getNationalities();
            }, function (error) {
                console.log(error);
                scope.getNationalities();
            });
    }

    scope.getNationalities = function () {
        DirectoryaddguardianSvc.getNationalities()
            .then(function (response) {
                scope.nationalitiesOptions = response.data;
                scope.getIdentityTypes();
            }, function (error) {
                console.log(error);
                scope.getIdentityTypes();
            });
    }

    scope.getIdentityTypes = function () {
        DirectoryaddguardianSvc.getIdentityTypes()
            .then(function (response) {
                scope.identityTypeOptions = response.data;
                scope.checkConfigForExternalSearch()
                UtilsSvc.isAppendLoader(false);
            }, function (error) {
                console.log(error);
                scope.checkConfigForExternalSearch()
                UtilsSvc.isAppendLoader(false);
            });
    }

    scope.getContactTypes = function () {
        DirectoryaddguardianSvc.getContactTypes()
            .then(function (response) {
                scope.contactTypeOptions = response.data;
                UtilsSvc.isAppendLoader(false);
            }, function (error) {
                console.log(error);
                UtilsSvc.isAppendLoader(false);
            });
    }

    scope.getRelationType = function () {
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.getRelationType()
            .then(function (response) {
                scope.relationTypeOptions = response.data;
                scope.getGenders();
            }, function (error) {
                console.log(error);
                scope.getGenders();
            });
    }

    scope.setName = function () {
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

    scope.appendName = function (dataObj, variableName, trim) {
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

    scope.changeGender = function () {
        var guardianData = scope.selectedUserData;
        if (guardianData.hasOwnProperty('gender_id')) {
            var genderOptions = scope.genderOptions;
            for (var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == guardianData.gender_id) {
                    guardianData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            scope.selectedUserData = guardianData;
        }

    }

    scope.changeNationality = function () {
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

    scope.changeIdentityType = function () {
        var identityType = scope.selectedUserData.identity_type_id;
        if (identityType == null) {
            scope.selectedUserData.identity_type_id = '';
            scope.selectedUserData.identity_number = '';
            scope.selectedUserData.identity_type_name = '';
        }
        var options = scope.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == identityType) {
                scope.selectedUserData.identity_type_name = options[i].name;
                break;
            }
        }
    }

    scope.changeContactType = function () {
        var contactType = scope.selectedUserData.contact_type_id;
        var options = scope.contactTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == contactType) {
                scope.selectedUserData.contact_type_name = options[i].name;
                break;
            }
        }
    }

    scope.changeRelationType = function () {
        var relationType = scope.selectedUserData.relation_type_id;
        var relationTypeOptions = scope.contactTypeOptions;
        for (var i = 0; i < relationTypeOptions.length; i++) {
            if (relationTypeOptions[i].id == relationType) {
                scope.selectedUserData.relation_type_name = relationTypeOptions[i].name;
                break;
            }
        }
    }

    scope.goToInternalSearch = function () {
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function (localeText) {
                scope.internalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.account_type,
                            field: "account_type",
                            suppressMenu: true,
                            suppressSorting: true
                        }
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
                        scope.isInternalSearchSelected = true;
                        scope.isExternalSearchSelected = false;
                        scope.selectGuardianFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.internalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };
                setTimeout(function () {
                    scope.getInternalSearchData();
                }, 1500);
            }, function (error) {
                scope.internalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.account_type,
                            field: "account_type",
                            suppressMenu: true,
                            suppressSorting: true
                        }
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
                        scope.isInternalSearchSelected = true;
                        scope.isExternalSearchSelected = false;
                        scope.selectGuardianFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.internalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };
                setTimeout(function () {
                    scope.getInternalSearchData();
                }, 1500);
            });
    }

    scope.goToExternalSearch = function () {
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function (localeText) {
                scope.externalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
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
                        scope.isInternalSearchSelected = false;
                        scope.isExternalSearchSelected = true;
                        scope.selectGuardianFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.externalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };
                setTimeout(function () {
                    // scope.getExternalSearchData();
                    if (scope.externalSearchSourceName === 'Jordan CSPD') {
                        scope.getCSPDSearchData();
                    } else {
                        scope.getExternalSearchData();
                    }
                }, 1500);
            }, function (error) {
                scope.externalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
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
                        scope.isInternalSearchSelected = false;
                        scope.isExternalSearchSelected = true;
                        scope.selectGuardianFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.externalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };
                setTimeout(function () {
                    // scope.getExternalSearchData();
                    if (scope.externalSearchSourceName === 'Jordan CSPD') {
                        scope.getCSPDSearchData();
                    } else {
                        scope.getExternalSearchData();
                    }
                }, 1500);
            });
    }

    scope.validateDetails = async function () {
        scope.error = {}
        if (!scope.selectedUserData.relation_type_id) {
            scope.error.relation_type_id = 'This field cannot be left empty';
            return;
        }

        if (scope.step === 'user_details') {
            const [blockName, hasError] = checkUserDetailValidationBlocksHasError();
            scope.error.first_name = '';
            scope.error.last_name = '';
            scope.error.gender_id = '';
            scope.error.date_of_birth = '';
            scope.error.nationality_id = '';
            scope.error.identity_type_id = '';
            scope.error.identity_number = '';

            if (blockName === 'Identity' && hasError) {
                if (!scope.selectedUserData.nationality_id) {
                    scope.error.nationality_id = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.identity_type_id) {
                    scope.error.identity_type_id = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.identity_number) {
                    scope.error.identity_number = 'This field cannot be left empty';
                }
            } else if (blockName === 'General_Info' && hasError) {
                if (!scope.selectedUserData.first_name) {
                    scope.error.first_name = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.last_name) {
                    scope.error.last_name = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.gender_id) {
                    scope.error.gender_id = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.date_of_birth) {
                    scope.error.date_of_birth = 'This field cannot be left empty';
                } else {
                    // let dob = scope.selectedUserData.date_of_birth.toLocaleDateString();
                    // let dobArray = dob.split('/');
                    scope.selectedUserData.date_of_birth = $filter('date')(scope.selectedUserData.date_of_birth, 'yyyy-MM-dd');
                }
            }
            if (hasError) {
                return;
            }
            scope.step = 'internal_search';
            scope.internalGridOptions = null;
            scope.goToInternalSearch();
            await checkUserAlreadyExistByIdentity();
        }
        if (scope.step === 'confirmation') {
            console.log('confirmation');
            const result = await scope.checkUserExistByIdentityFromConfiguration();
            if (result) {return};
        }

        if (scope.step === 'confirmation') {
            if (!scope.selectedUserData.username) {
                scope.error.username = 'This field cannot be left empty';
            }
            if (!scope.selectedUserData.password) {
                scope.error.password = 'This field cannot be left empty';
            }
            if (!scope.selectedUserData.username || !scope.selectedUserData.password) {
                return;
            }
            scope.saveGuardianDetails();
        }
    }

    scope.goToPrevStep = function () {
        if (scope.isInternalSearchSelected) {
            scope.isInternalSearchSelected = false;
            scope.step = 'user_details';
            scope.internalGridOptions = null;
            // scope.goToInternalSearch();
        } else {
            switch (scope.step) {
                case 'internal_search': {
                    scope.step = 'user_details';
                    if (scope.isSearchResultEmpty) {
                        scope.selectedUserData.openemis_no = "";
                    }
                    break;
                }
                case 'external_search':
                    scope.step = 'internal_search';
                    scope.internalGridOptions = null;
                    scope.goToInternalSearch();
                    break;
                case 'confirmation': {
                    if (scope.isExternalSearchEnable) {
                        scope.step = 'external_search';
                        scope.externalGridOptions = null;
                        scope.goToExternalSearch();
                    } else {
                        scope.step = 'internal_search';
                        scope.internalGridOptions = null;
                        scope.goToInternalSearch();
                    }
                    return;
                }
            }
        }
    }

    scope.goToNextStep = async function () {
        if (scope.step === 'confirmation') {
            const result = await scope.checkUserExistByIdentityFromConfiguration();
            if (result) return;
        }
        if (scope.isInternalSearchSelected) {
            scope.step = 'confirmation';
            scope.getUniqueOpenEmisId();
        } else {
            switch (scope.step) {
                case 'user_details':
                    scope.validateDetails();
                    break;
                case 'internal_search': {
                    if (scope.isExternalSearchEnable) {
                        scope.step = 'external_search';
                        scope.externalGridOptions = null;
                        UtilsSvc.isAppendLoader(true);
                        scope.goToExternalSearch();
                    } else {
                        scope.step = 'confirmation';
                        scope.getUniqueOpenEmisId();
                    }
                    return;
                }
                case 'external_search':
                    scope.step = 'confirmation';
                    scope.getUniqueOpenEmisId();
                    break;
            }
        }
    }

    scope.cancelProcess = function () {
        $window.history.back();
    }

    scope.initGrid = function () {
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function (localeText) {
                scope.internalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.account_type,
                            field: "account_type",
                            suppressMenu: true,
                            suppressSorting: true
                        }
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
                        scope.isInternalSearchSelected = true;
                        scope.isExternalSearchSelected = false;
                        scope.selectGuardianFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.internalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };

                scope.externalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
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
                        scope.isInternalSearchSelected = false;
                        scope.isExternalSearchSelected = true;
                        scope.selectGuardianFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.externalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };
            }, function (error) {
                scope.internalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.account_type,
                            field: "account_type",
                            suppressMenu: true,
                            suppressSorting: true
                        }
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
                        scope.isInternalSearchSelected = true;
                        scope.isExternalSearchSelected = false;
                        scope.selectGuardianFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.internalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };

                scope.externalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
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
                        scope.isInternalSearchSelected = false;
                        scope.isExternalSearchSelected = true;
                        scope.selectGuardianFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.externalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };
            });
    };

    scope.selectGuardianFromInternalSearch = function (id) {
        scope.selectedGuardian = id;
        scope.isInternalSearchSelected = true;
        scope.getGuardianData();

        if (scope.isIdentityUserExist) {
            scope.messageClass = '';
            scope.message = '';
            scope.isIdentityUserExist = false;
        }
        scope.disableFields = {
            username: true,
            password: true
        }
    }

    scope.selectGuardianFromExternalSearch = function (id) {
        scope.selectedGuardian = id;
        scope.isInternalSearchSelected = false;
        scope.getGuardianData();
        scope.disableFields = {
            username: false,
            password: false
        }
    }

    scope.getGuardianData = function () {
        var log = [];
        angular.forEach(scope.rowsThisPage, function (value) {
            if (value.id == scope.selectedGuardian) {
                if (scope.isInternalSearchSelected)
                    scope.setUserData(value);
                else
                    scope.setExternalUserData(value);
            }
        }, log);
    }

    scope.setUserData = function (selectedData) {
        scope.selectedUserData.addressArea = {
            id: selectedData.address_area_id,
            name: selectedData.area_name,
            code: selectedData.area_code
        };
        scope.selectedUserData.birthplaceArea = {
            id: selectedData.birthplace_area_id,
            name: selectedData.birth_area_name,
            code: selectedData.birth_area_code
        };
        scope.selectedUserData.user_id = selectedData.id;
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
        scope.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
        scope.selectedUserData.password = selectedData.password;
        scope.selectedUserData.address = selectedData.address;
        scope.selectedUserData.postalCode = selectedData.postal_code;
        scope.selectedUserData.address_area_id = selectedData.address_area_id;
        scope.selectedUserData.birthplace_area_id = selectedData.birthplace_area_id;
        scope.selectedUserData.addressArea = {name: selectedData.area_name};
        scope.selectedUserData.birthplaceArea = {name: selectedData.birth_area_name};
        if ($window.localStorage.getItem('birthplace_area_id')) {
            $window.localStorage.removeItem('birthplace_area_id')
        }
        if ($window.localStorage.getItem('address_area_id')) {
            $window.localStorage.removeItem('address_area_id')
        }
        $window.localStorage.setItem('birthplace_area_id', selectedData.birthplace_area_id);
        $window.localStorage.setItem('address_area_id', selectedData.address_area_id);
        if ($window.localStorage.getItem('birthplace_area')) {
            $window.localStorage.removeItem('birthplace_area')
        }
        if ($window.localStorage.getItem('address_area')) {
            $window.localStorage.removeItem('address_area')
        }
        $window.localStorage.setItem('birthplace_area', JSON.stringify({
            id: selectedData.birthplace_area_id,
            name: selectedData.birth_area_name
        }));
        $window.localStorage.setItem('address_area', JSON.stringify({
            id: selectedData.address_area_id,
            name: selectedData.area_name
        }));
        scope.addressAreaId = selectedData.address_area_id;
        scope.birthplaceAreaId = selectedData.birthplace_area_id;
        if (selectedData.address_area_id > 0) {
            document.getElementById('addressArea_textbox').style.visibility = 'visible';
            document.getElementById('addressArea_dropdown').style.visibility = 'hidden';
        } else {
            document.getElementById('addressArea_textbox').style.display = 'none';
            document.getElementById('addressArea_dropdown').style.visibility = 'visible';
        }

        if (selectedData.birthplace_area_id > 0) {
            document.getElementById('birthplaceArea_textbox').style.visibility = 'visible';
            document.getElementById('birthplaceArea_dropdown').style.visibility = 'hidden';
        } else {
            document.getElementById('birthplaceArea_textbox').style.display = 'none';
            document.getElementById('birthplaceArea_dropdown').style.visibility = 'visible';
        }
    }

    scope.setExternalUserData = function (selectedData) {
        /* TODO */
        if (scope.externalSearchSourceName = 'Jordan CSPD') {
            DirectoryaddguardianSvc.getUniqueOpenEmisId().then((response) => {
                const selectedObjectWithOpenemisNo = Object.assign({}, selectedData, {'openemis_no': response})
                selectedData = selectedObjectWithOpenemisNo;
                scope.selectedUserData.addressArea = {
                    id: selectedData.address_area_id,
                    name: selectedData.area_name,
                    code: selectedData.area_code
                };
                scope.selectedUserData.birthplaceArea = {
                    id: selectedData.birthplace_area_id,
                    name: selectedData.birth_area_name,
                    code: selectedData.birth_area_code
                };
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
                scope.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
                scope.selectedUserData.password = selectedData.password;
                scope.selectedUserData.address = selectedData.address;
                scope.selectedUserData.postalCode = selectedData.postal_code;
                if (selectedData.address_area_id > 0) {
                    document.getElementById('addressArea_textbox').style.visibility = 'visible';
                    document.getElementById('addressArea_dropdown').style.visibility = 'hidden';
                } else {
                    document.getElementById('addressArea_textbox').style.display = 'none';
                    document.getElementById('addressArea_dropdown').style.visibility = 'visible';
                }

                if (selectedData.birthplace_area_id > 0) {
                    document.getElementById('birthplaceArea_textbox').style.visibility = 'visible';
                    document.getElementById('birthplaceArea_dropdown').style.visibility = 'hidden';
                } else {
                    document.getElementById('birthplaceArea_textbox').style.display = 'none';
                    document.getElementById('birthplaceArea_dropdown').style.visibility = 'visible';
                }
            })
        } else {
            scope.selectedUserData.addressArea = {
                id: selectedData.address_area_id,
                name: selectedData.area_name,
                code: selectedData.area_code
            };
            scope.selectedUserData.birthplaceArea = {
                id: selectedData.birthplace_area_id,
                name: selectedData.birth_area_name,
                code: selectedData.birth_area_code
            };
            scope.selectedUserData.user_id = selectedData.id;
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
            scope.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
            scope.selectedUserData.password = selectedData.password;
            scope.selectedUserData.address = selectedData.address;
            scope.selectedUserData.postalCode = selectedData.postal_code;
            if (selectedData.address_area_id > 0) {
                document.getElementById('addressArea_textbox').style.visibility = 'visible';
                document.getElementById('addressArea_dropdown').style.visibility = 'hidden';
            } else {
                document.getElementById('addressArea_textbox').style.display = 'none';
                document.getElementById('addressArea_dropdown').style.visibility = 'visible';
            }

            if (selectedData.birthplace_area_id > 0) {
                document.getElementById('birthplaceArea_textbox').style.visibility = 'visible';
                document.getElementById('birthplaceArea_dropdown').style.visibility = 'hidden';
            } else {
                document.getElementById('birthplaceArea_textbox').style.display = 'none';
                document.getElementById('birthplaceArea_dropdown').style.visibility = 'visible';
            }
        }
    }

    scope.saveGuardianDetails = function () {
        console.log("Start");
        console.log(scope);
        console.log("End");
        const addressAreaRef = DirectoryaddguardianSvc.getAddressArea()
        addressAreaRef && (scope.selectedUserData.addressArea = addressAreaRef);
        const birthplaceAreaRef = DirectoryaddguardianSvc.getBirthplaceArea();
        birthplaceAreaRef && (scope.selectedUserData.birthplaceArea = birthplaceAreaRef);
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
            nationality_name: scope.selectedUserData.nationality_name,
            username: scope.selectedUserData.username,
            password: scope.selectedUserData.password,
            postal_code: scope.selectedUserData.postalCode,
            address: scope.selectedUserData.address,
            birthplace_area_id: DirectoryaddguardianSvc.getBirthplaceAreaId(),
            address_area_id: DirectoryaddguardianSvc.getAddressAreaId(),
            identity_type_id: scope.selectedUserData.identity_type_id,
            identity_type_name: scope.selectedUserData.identity_type_name,
            photo_name: scope.selectedUserData.photo_name,
            photo_content: scope.selectedUserData.photo_base_64,
            contact_type: scope.selectedUserData.contact_type_id,
            contact_value: scope.selectedUserData.contactValue,
        };
        UtilsSvc.isAppendLoader(true);
        DirectoryaddguardianSvc.saveGuardianDetails(params)
            .then(function (response) {
                scope.message = (scope.selectedUserData && scope.selectedUserData.relation_type_name ? scope.selectedUserData.relation_type_name : 'Guardian') + ' successfully added.';
                scope.messageClass = 'alert-success';
                scope.step = "summary";
                var todayDate = new Date();
                scope.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
                UtilsSvc.isAppendLoader(false);
            }, function (error) {
                console.log(error);
                UtilsSvc.isAppendLoader(false);
            });
    }


    async function checkUserAlreadyExistByIdentity() {
        const userData = scope.selectedUserData;
        const userSvc = DirectoryaddguardianSvc;
        const result = await userSvc.checkUserAlreadyExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id': userData.nationality_id,
            'first_name': userData.first_name,
            'last_name': userData.last_name,
            'gender_id': userData.gender_id,
            'date_of_birth': userData.date_of_birth,
            'user_id': userData.user_id
        });

        if (result.data.user_exist === 1) {
            scope.messageClass = 'alert_warn';
            scope.message = result.data.message;
            scope.isIdentityUserExist = true;
        } else {
            scope.messageClass = '';
            scope.message = '';
            scope.isIdentityUserExist = false;
        }
        /*  return result.data.user_exist === 1; */
    }

    scope.addGuardian = function addGuardian() {
        //POCOR-7231::Start
        let str1 = document.URL;
        ;
        const Arr = str1.split("/");
        var len = Arr.length - 1;
        if (window.location.href.indexOf("Institution") > -1) {
            $window.location.href = angular.baseUrl + '/Institution/Institutions/' + Arr[len];
        } else {
            const queryString = getParameterByName('queryString');
            $window.location.href = angular.baseUrl + '/Directory/Directories/Addguardian?queryString=' + queryString;
        }
        //POCOR-7231::End
    }

    /**
     * @desc 1)Identity Number is mandatory OR
     * @desc 2)OpenEMIS ID is mandatory OR
     * @desc 3)First Name, Last Name, Date of Birth and Gender are mandatory
     * @returns [ error block name | true or false]
     */
    function checkUserDetailValidationBlocksHasError() {
        const {first_name, last_name, gender_id, date_of_birth, identity_type_id, identity_number, nationality_id, openemis_no} = scope.selectedUserData;
        const isGeneralInfodHasError = (!first_name || !last_name || !gender_id || !date_of_birth)
        const isOpenEmisNoHasError = openemis_no !== "" && openemis_no !== undefined;
        const isIdentityHasError = identity_number?.length > 1 && (nationality_id === undefined || nationality_id === "" || nationality_id === null || identity_type_id === "" || identity_type_id === undefined || identity_type_id === null)
        const isSkipableForIdentity = identity_number?.length > 1 && nationality_id > 0 && identity_type_id > 0;

        if (isIdentityHasError) {
            return ['Identity', true]
        }
        if (isSkipableForIdentity) {
            return ['Identity', false]
        }

        if (isOpenEmisNoHasError) {
            return ["OpenEMIS_ID", false];
        }

        if (isGeneralInfodHasError) {
            return ["General_Info", true];
        }

        return ["", false];
    }

    scope.checkConfigForExternalSearch = function checkConfigForExternalSearch() {
        DirectoryaddguardianSvc.checkConfigForExternalSearch().then(function (resp) {
            scope.isExternalSearchEnable = resp.showExternalSearch;
            scope.externalSearchSourceName = resp.value;
            UtilsSvc.isAppendLoader(false);
        }, function (error) {
            scope.isExternalSearchEnable = false;
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }
    scope.isNextButtonShouldDisable = function isNextButtonShouldDisable() {
        const {step, selectedUserData, isIdentityUserExist} = scope;
        const {first_name, last_name, date_of_birth, gender_id} = selectedUserData;

        if (isIdentityUserExist && step === "internal_search") {
            return true;
        }

        if (step === "external_search" && (!first_name || !last_name || !date_of_birth || !gender_id)) {
            return true;
        }
        return false;
    }

    scope.getCSPDSearchData = function getCSPDSearchData() {
        var param = {
            identity_number: scope.selectedUserData.identity_number,
        };
        var dataSource = {
            pageSize: scope.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                DirectoryaddguardianSvc.getCspdData(param)
                    .then(function (response) {
                        var gridData = [response.data.data];
                        if (!gridData) gridData = [];
                        gridData.forEach((data) => {
                            data.name = `${data['first_name']} ${data['middle_name']} ${data['last_name']}`;
                            data.gender = data['gender_name'];
                            data.nationality = data['nationality_name'];
                            data.identity_type = data['identity_type_name'];
                            data.gender_id = data['gender_id'];
                            data.nationality_id = data['nationality_id'];
                            data.identity_type_id = data['identity_type_id'];
                        });
                        var totalRowCount = gridData.length === 0 ? 1 : gridData.length;
                        scope.isSearchResultEmpty = gridData.length === 0;
                        return scope.processExternalGridUserRecord(gridData, params, totalRowCount);
                    }, function (error) {
                        console.log(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };
        scope.externalGridOptions.api.setDatasource(dataSource);
        scope.externalGridOptions.api.sizeColumnsToFit();
    }
    scope.checkUserExistByIdentityFromConfiguration = async function checkUserExistByIdentityFromConfiguration() {
        userData = scope.selectedUserData;
        const { identity_type_id, identity_number } = userData;
        // console.log(scope.selectedUserData);
        // scope.error.nationality_id = "";
        scope.error.identity_type_id = ""
        scope.error.identity_number = "";


        /* if (!nationality_id)
        {
            scope.error.nationality_id =
                "This field cannot be left empty";

                return false;
        } */
        if (!identity_type_id)
        {
            scope.error.identity_type_id =
                "This field cannot be left empty";
            return false;
        }
        if (!identity_number)
        {
            scope.error.identity_number =
                "This field cannot be left empty";

            return false;
        }

        const userSvc = DirectoryaddguardianSvc;
        const userCtrl = scope;

        const result = await userSvc.checkUserAlreadyExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id':userData.nationality_id,
            'first_name': userData.first_name,
            'last_name': userData.last_name,
            'gender_id': userData.gender_id,
            'date_of_birth': userData.date_of_birth,
            'user_id': userData.user_id,
        });

        if (result.data.user_exist === 1)
        {
            // console.log(result.data);
            scope.messageClass = 'alert_warn';
            scope.message = result.data.message;
            scope.isIdentityUserExist = true;
            scope.error.identity_number = result.data.message;
            $window.scrollTo({bottom:0});
        } else
        {
            scope.messageClass = '';
            scope.message = '';
            scope.isIdentityUserExist = false;
            scope.error.identity_number ==""
        }
        return result.data.user_exist === 1;
    }

}