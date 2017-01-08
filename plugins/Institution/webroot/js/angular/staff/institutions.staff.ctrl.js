angular
    .module('institutions.staff.ctrl', ['utils.svc', 'alert.svc', 'institutions.staff.svc'])
    .controller('InstitutionsStaffCtrl', InstitutionStaffController);

InstitutionStaffController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'InstitutionsStaffSvc'];

function InstitutionStaffController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, InstitutionsStaffSvc) {
    // ag-grid vars


    var StaffController = this;

    var pageSize = 10;

    // Variables
    StaffController.externalSearch = false;
    StaffController.hasExternalDataSource;
    StaffController.internalGridOptions = null;
    StaffController.externalGridOptions = null;
    StaffController.rowsThisPage = [];
    StaffController.createNewStaff = false;
    StaffController.genderOptions = {};
    StaffController.academicPeriodOptions = {};
    StaffController.institutionPositionOptions = {};
    StaffController.staffTypeOptions = {};
    StaffController.staffTypeId = {};
    StaffController.step = 'internal_search';
    StaffController.showExternalSearchButton = false;
    StaffController.completeDisabled = false;
    StaffController.institutionId = null;
    StaffController.institutionName = '';
    StaffController.addStaffError = false;
    StaffController.transferStaffError = false;

    // 0 - Non-mandatory, 1 - Mandatory, 2 - Excluded
    StaffController.StaffContacts = 2;
    StaffController.StaffIdentities = 2;
    StaffController.StaffNationalities = 2;
    StaffController.StaffSpecialNeeds = 2;
    StaffController.StaffContactsOptions = [];
    StaffController.StaffIdentitiesOptions = [];
    StaffController.StaffNationalitiesOptions = [];
    StaffController.StaffSpecialNeedsOptions = [];
    StaffController.Staff = {};
    StaffController.Staff.nationality_id = '';
    StaffController.Staff.nationality_name = '';
    StaffController.Staff.identity_type_id = '';
    StaffController.Staff.identity_type_name = '';
    StaffController.Staff.nationality_class = 'input select error';
    StaffController.Staff.identity_type_class = 'input select error';
    StaffController.Staff.identity_class = 'input string';


    // filter variables
    StaffController.internalFilterOpenemisNo;
    StaffController.internalFilterFirstName;
    StaffController.internalFilterLastName;
    StaffController.internalFilterIdentityNumber;
    StaffController.internalFilterDateOfBirth;

    // Controller functions
    StaffController.initNationality = initNationality;
    StaffController.initIdentityType = initIdentityType;
    StaffController.changeNationality = changeNationality;
    StaffController.changeIdentityType = changeIdentityType;
    StaffController.processStaffRecord = processStaffRecord;
    StaffController.processExternalStaffRecord = processExternalStaffRecord;
    StaffController.createNewInternalDatasource = createNewInternalDatasource;
    StaffController.createNewExternalDatasource = createNewExternalDatasource;
    StaffController.insertStaffData = insertStaffData;
    StaffController.onChangeAcademicPeriod = onChangeAcademicPeriod;
    StaffController.getStaffData = getStaffData;
    StaffController.selectStaff = selectStaff;
    StaffController.postForm = postForm;
    StaffController.addStaffUser = addStaffUser;
    StaffController.setStaffName = setStaffName;
    StaffController.appendName = appendName;
    StaffController.changeGender = changeGender;
    StaffController.validateNewUser = validateNewUser;
    StaffController.onExternalSearchClick = onExternalSearchClick;
    StaffController.onAddNewStaffClick = onAddNewStaffClick;
    StaffController.onAddStaffCompleteClick = onAddStaffCompleteClick;
    StaffController.onAddStaffClick = onAddStaffClick;
    StaffController.getUniqueOpenEmisId = getUniqueOpenEmisId;
    StaffController.reloadInternalDatasource = reloadInternalDatasource;
    StaffController.reloadExternalDatasource = reloadExternalDatasource;
    StaffController.clearInternalSearchFilters = clearInternalSearchFilters;
    StaffController.onChangePositionType = onChangePositionType;
    StaffController.onChangeFTE = onChangeFTE;
    StaffController.postTransferForm = postTransferForm;
    StaffController.initialLoad = true;
    StaffController.date_of_birth = '';

    StaffController.displayedFTE = '';
    StaffController.selectedStaff;
    StaffController.positionType = '';
    StaffController.addStaffButton = false;
    StaffController.selectedStaffData = null;
    StaffController.startDate = '';
    StaffController.endDate = '';
    StaffController.defaultIdentityTypeName;
    StaffController.defaultIdentityTypeId;
    StaffController.postResponse;

    angular.element(document).ready(function () {
        InstitutionsStaffSvc.init(angular.baseUrl);
        InstitutionsStaffSvc.setInstitutionId(StaffController.institutionId);

        UtilsSvc.isAppendLoader(true);

        InstitutionsStaffSvc.getAcademicPeriods()
        .then(function(periods) {
            var promises = [];
            var selectedPeriod = [];
            angular.forEach(periods, function(value) {
                if (value.current == 1) {
                   this.push(value);
                }
            }, selectedPeriod);
            if (selectedPeriod.length == 0) {
                selectedPeriod = periods;
            }

            StaffController.academicPeriodOptions = {
                availableOptions: periods,
                selectedOption: selectedPeriod[0]
            };

            StaffController.institutionPositionOptions = {
                availableOptions: [],
                selectedOption: ''
            };

            if (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
                StaffController.onChangeAcademicPeriod();
            }
            promises.push(InstitutionsStaffSvc.getAddNewStaffConfig());
            promises.push(InstitutionsStaffSvc.getStaffTypes());
            promises.push(InstitutionsStaffSvc.getInstitution(StaffController.institutionId));
            return $q.all(promises);
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
            UtilsSvc.isAppendLoader(false);
        })
        .then(function(promisesObj) {
            var promises = [];
            var addNewStaffConfig = promisesObj[0].data;
            var staffTypes = promisesObj[1].data;
            var institutionName = promisesObj[2].data[0]['code_name'];
            StaffController.institutionName = institutionName;
            StaffController.staffTypeOptions = staffTypes;

            for(i=0; i < addNewStaffConfig.length; i++) {
                var code = addNewStaffConfig[i].code;
                StaffController[code] = addNewStaffConfig[i].value;
            }
            if (StaffController.StaffContacts != 2) {
                promises[1] = InstitutionsStaffSvc.getUserContactTypes();
            }
            if (StaffController.StaffNationalities != 2) {
                if (StaffController.StaffNationalities == 1) {
                    StaffController.Staff.nationality_class = StaffController.Staff.nationality_class + ' required';
                }
                promises[2] = InstitutionsStaffSvc.getNationalities();
            }
            if (StaffController.StaffIdentities != 2) {
                if (StaffController.StaffIdentities == 1) {
                    StaffController.Staff.identity_class = StaffController.Staff.identity_class + ' required';
                    StaffController.Staff.identity_type_class = StaffController.Staff.identity_type_class + ' required';
                }
                promises[3] = InstitutionsStaffSvc.getIdentityTypes();
            }
            if (StaffController.StaffSpecialNeeds != 2) {
                promises[4] = InstitutionsStaffSvc.getSpecialNeedTypes();
            }
            promises[0] = InstitutionsStaffSvc.getGenders();

            return $q.all(promises);
        }, function(error){
            console.log(error);
            AlertSvc.warning($scope, error);
            UtilsSvc.isAppendLoader(false);
        })
        .then(function(promisesObj) {
            StaffController.genderOptions = promisesObj[0];
            // User Contacts
            if (promisesObj[1] != undefined && promisesObj[1].hasOwnProperty('data')) {
                StaffController.StaffContactsOptions = promisesObj[1]['data'];
            }
            // User Nationalities
            if (promisesObj[2] != undefined && promisesObj[2].hasOwnProperty('data')) {
                StaffController.StaffNationalitiesOptions = promisesObj[2]['data'];
            }
            // User Identities
            if (promisesObj[3] != undefined && promisesObj[3].hasOwnProperty('data')) {
                StaffController.StaffIdentitiesOptions = promisesObj[3]['data'];
            }
            // User Special Needs
            if (promisesObj[4] != undefined && promisesObj[4].hasOwnProperty('data')) {
                StaffController.StaffSpecialNeedsOptions = promisesObj[4]['data'];
            }
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
            AlertSvc.warning($scope, error);
        })
        .finally(function(result) {
            $scope.initGrid();
            UtilsSvc.isAppendLoader(false);
            if ($location.search().staff_added) {
                AlertSvc.success($scope, 'The staff is added successfully.');
            } else if ($location.search().staff_transfer_added) {
                AlertSvc.success($scope, 'Staff transfer request is added successfully.');
            }
        });
    });

    function initNationality() {
        StaffController.Staff.nationality_id = '';
        var options = StaffController.StaffNationalitiesOptions;
        for(var i = 0; i < options.length; i++) {
            if (options[i].default == 1) {
                StaffController.Staff.nationality_id = options[i].id;
                StaffController.Staff.nationality_name = options[i].name;
                StaffController.Staff.identity_type_id = options[i].identity_type_id;
                StaffController.Staff.identity_type_name = options[i].identity_type.name;
                break;
            }
        }
    }

    function changeNationality() {
        var nationalityId = StaffController.Staff.nationality_id;
        var options = StaffController.StaffNationalitiesOptions;
        for(var i = 0; i < options.length; i++) {
            if (options[i].id == nationalityId) {
                StaffController.Staff.identity_type_id = options[i].identity_type_id;
                StaffController.Staff.nationality_name = options[i].name;
                StaffController.Staff.identity_type_name = options[i].identity_type.name;
                break;
            }
        }
    }

    function changeIdentityType() {
        var identityType = StaffController.Staff.identity_type_id;
        var options = StaffController.StaffIdentitiesOptions;
        for(var i = 0; i < options.length; i++) {
            if (options[i].id == identityType) {
                StaffController.Staff.identity_type_name = options[i].name;
                break;
            }
        }
    }

    function onChangePositionType() {
        if (StaffController.positionType == '') {
            StaffController.fte = '';
            StaffController.onChangeFTE();
        } else if (StaffController.positionType == 'Full-Time') {
            StaffController.fte = 1;
            StaffController.onChangeFTE();
        } else if (StaffController.positionType == 'Part-Time') {
            StaffController.fte = '';
            StaffController.onChangeFTE();
        }
    }

    function onChangeFTE() {
        var academicPeriodId = StaffController.academicPeriodOptions.selectedOption.id;
        var fte = StaffController.fte;
        if (fte == '') {
            fte = 0;
        }
        StaffController.displayedFTE = (fte*100) + '%';
        var startDate = StaffController.startDate;
        var endDate = StaffController.endDate;
        InstitutionsStaffSvc.getPositionList(fte, startDate, endDate)
        .then(function(response) {
            StaffController.institutionPositionOptions.availableOptions = response;
        }, function(errors) {

        });
    }

    function initIdentityType() {
        if (StaffController.Staff.nationality_id == '') {
            var options = StaffController.StaffIdentitiesOptions;
            for(var i = 0; i < options.length; i++) {
                if (options[i].default == 1) {
                    StaffController.Staff.identity_type_id = options[i].id;
                    StaffController.Staff.identity_type_name = options[i].name;
                    break;
                }
            }
        }
    }

    $scope.initGrid = function() {

        StaffController.internalGridOptions = {
            columnDefs: [
                {
                    field:'id',
                    headerName:'',
                    suppressMenu: true,
                    suppressSorting: true,
                    width: 40,
                    maxWidth: 40,
                    cellRenderer: function(params) {
                        var data = JSON.stringify(params.data);
                        return '<div><input  name="ngSelectionCell" ng-click="InstitutionStaffController.selectStaff('+params.value+')" tabindex="-1" class="no-selection-label" kd-checkbox-radio type="radio" selectedStaff="'+params.value+'"/></div>';
                    }
                },
                {headerName: 'OpenEMIS ID', field: "openemis_no", suppressMenu: true, suppressSorting: true},
                {headerName: 'Name', field: "name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Gender', field: "gender_name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Date of Birth', field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                {headerName: 'Nationality', field: "nationality_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Identity Type", field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Identity Number", field: "identity_number", suppressMenu: true, suppressSorting: true}
            ],
            enableColResize: false,
            enableFilter: true,
            enableServerSideFilter: true,
            enableServerSideSorting: true,
            enableSorting: true,
            headerHeight: 38,
            rowData: [],
            rowHeight: 38,
            rowModelType: 'pagination',
            angularCompileRows: true
        };

        StaffController.externalGridOptions = {
            columnDefs: [
                {
                    field:'id',
                    headerName:'',
                    suppressMenu: true,
                    suppressSorting: true,
                    width: 40,
                    maxWidth: 40,
                    cellRenderer: function(params) {
                        var data = JSON.stringify(params.data);
                        return '<div><input  name="ngSelectionCell" ng-click="InstitutionStaffController.selectStaff('+params.value+')" tabindex="-1" class="no-selection-label" kd-checkbox-radio type="radio" selectedStaff="'+params.value+'"/></div>';
                    }
                },
                {headerName: 'Name', field: "name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Gender', field: "gender_name", suppressMenu: true, suppressSorting: true},
                {headerName: 'Date of Birth', field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                {headerName: 'Nationality', field: "nationality_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Identity Type", field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Identity Number", field: "identity_number", suppressMenu: true, suppressSorting: true}
            ],
            enableColResize: false,
            enableFilter: true,
            enableServerSideFilter: true,
            enableServerSideSorting: true,
            enableSorting: true,
            headerHeight: 38,
            rowData: [],
            rowHeight: 38,
            rowModelType: 'pagination',
            angularCompileRows: true
        };
    };

    function reloadInternalDatasource(withData) {
        if (withData !== false) {
           StaffController.showExternalSearchButton = true;
        }
        InstitutionsStaffSvc.resetExternalVariable();
        delete StaffController.selectedStaff;
        StaffController.staffTypeId = '';
        StaffController.positionType = '';
        StaffController.endDate = '';
        StaffController.onChangeAcademicPeriod();
        StaffController.onChangePositionType();
        StaffController.createNewInternalDatasource(StaffController.internalGridOptions, withData);
    };

    function reloadExternalDatasource(withData) {
        InstitutionsStaffSvc.resetExternalVariable();
        delete StaffController.selectedStaff;
        StaffController.staffTypeId = '';
        StaffController.positionType = '';
        StaffController.endDate = '';
        StaffController.onChangeAcademicPeriod();
        StaffController.onChangePositionType();
        StaffController.createNewExternalDatasource(StaffController.externalGridOptions, withData);
    };

    function clearInternalSearchFilters() {
        StaffController.internalFilterOpenemisNo = '';
        StaffController.internalFilterFirstName = '';
        StaffController.internalFilterLastName = '';
        StaffController.internalFilterIdentityNumber = '';
        StaffController.internalFilterDateOfBirth = '';
        StaffController.initialLoad = true;
        StaffController.createNewInternalDatasource(StaffController.internalGridOptions);
    }

    function createNewInternalDatasource(gridObj, withData) {
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                AlertSvc.reset($scope);
                if (withData) {
                   InstitutionsStaffSvc.getStaffRecords(
                    {
                        startRow: params.startRow,
                        endRow: params.endRow,
                        conditions: {
                            openemis_no: StaffController.internalFilterOpenemisNo,
                            first_name: StaffController.internalFilterFirstName,
                            last_name: StaffController.internalFilterLastName,
                            identity_number: StaffController.internalFilterIdentityNumber,
                            date_of_birth: StaffController.internalFilterDateOfBirth,
                        }
                    }
                    )
                    .then(function(response) {
                        if (response.conditionsCount == 0) {
                            StaffController.initialLoad = true;
                        } else {
                            StaffController.initialLoad = false;
                        }
                        var staffRecords = response.data;
                        var totalRowCount = response.total;
                        return StaffController.processStaffRecord(staffRecords, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        AlertSvc.warning($scope, error);
                    });
                } else {
                    StaffController.rowsThisPage = [];
                    params.successCallback(StaffController.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function createNewExternalDatasource(gridObj, withData) {
        StaffController.externalDataLoaded = false;
        StaffController.initialLoad = true;
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                AlertSvc.reset($scope);
                if (withData) {
                    InstitutionsStaffSvc.getExternalStaffRecords(
                        {
                            startRow: params.startRow,
                            endRow: params.endRow,
                            conditions: {
                                first_name: StaffController.internalFilterFirstName,
                                last_name: StaffController.internalFilterLastName,
                                identity_number: StaffController.internalFilterIdentityNumber,
                                date_of_birth: StaffController.internalFilterDateOfBirth
                            }
                        }
                    )
                    .then(function(response) {
                        var staffRecords = response.data;
                        var totalRowCount = response.total;
                        StaffController.initialLoad = false;
                        return StaffController.processExternalStaffRecord(staffRecords, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        var status = error.status;
                        if (status == '401') {
                            var message = 'You have not been authorised to fetch from external data source.';
                            AlertSvc.warning($scope, message);
                        } else {
                            var message = 'External search failed, please contact your administrator to verify the external search attributes';
                            AlertSvc.warning($scope, message);
                        }
                        var staffRecords = [];
                        InstitutionsStaffSvc.init(angular.baseUrl);
                        return StaffController.processExternalStaffRecord(staffRecords, params, 0);
                    })
                    .finally(function(res) {
                        InstitutionsStaffSvc.init(angular.baseUrl);
                    });
                } else {
                    StaffController.rowsThisPage = [];
                    params.successCallback(StaffController.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function processExternalStaffRecord(staffRecords, params, totalRowCount) {
        for(var key in staffRecords) {
            var mapping = InstitutionsStaffSvc.getExternalSourceMapping();
            staffRecords[key]['institution_name'] = '-';
            staffRecords[key]['academic_period_name'] = '-';
            staffRecords[key]['education_grade_name'] = '-';
            staffRecords[key]['date_of_birth'] = InstitutionsStaffSvc.formatDate(staffRecords[key][mapping.date_of_birth_mapping]);
            staffRecords[key]['gender_name'] = staffRecords[key][mapping.gender_mapping];
            staffRecords[key]['gender'] = {'name': staffRecords[key][mapping.gender_mapping]};
            staffRecords[key]['identity_type_name'] = staffRecords[key][mapping.identity_type_mapping];
            staffRecords[key]['identity_number'] = staffRecords[key][mapping.identity_number_mapping];
            staffRecords[key]['nationality_name'] = staffRecords[key][mapping.nationality_mapping];
            staffRecords[key]['address'] = staffRecords[key][mapping.address_mapping];
            staffRecords[key]['postal_code'] = staffRecords[key][mapping.postal_mapping];
            staffRecords[key]['name'] = '';
            if (staffRecords[key].hasOwnProperty(mapping.first_name_mapping)) {
                staffRecords[key]['name'] = staffRecords[key][mapping.first_name_mapping];
            }
            StaffController.appendName(staffRecords[key], mapping.middle_name_mapping);
            StaffController.appendName(staffRecords[key], mapping.third_name_mapping);
            StaffController.appendName(staffRecords[key], mapping.last_name_mapping);
        }

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = staffRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        StaffController.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return staffRecords;
    }

    function processStaffRecord(staffRecords, params, totalRowCount) {
        for(var key in staffRecords) {
            staffRecords[key]['institution_name'] = '-';
            staffRecords[key]['academic_period_name'] = '-';
            staffRecords[key]['education_grade_name'] = '-';
            if ((staffRecords[key].hasOwnProperty('institution_students') && staffRecords[key]['institution_students'].length > 0)) {
                staffRecords[key]['institution_name'] = ((staffRecords[key].institution_students['0'].hasOwnProperty('institution')))? staffRecords[key].institution_students['0'].institution.name: '-';
                staffRecords[key]['academic_period_name'] = ((staffRecords[key].institution_students['0'].hasOwnProperty('academic_period')))? staffRecords[key].institution_students['0'].academic_period.name: '-';
                staffRecords[key]['education_grade_name'] = ((staffRecords[key].institution_students['0'].hasOwnProperty('education_grade')))? staffRecords[key].institution_students['0'].education_grade.name: '-';
            }

            staffRecords[key]['date_of_birth'] = InstitutionsStaffSvc.formatDate(staffRecords[key]['date_of_birth']);
            staffRecords[key]['gender_name'] = staffRecords[key]['gender']['name'];

            if (!staffRecords[key].hasOwnProperty('name')) {
                staffRecords[key]['name'] = '';
                if (staffRecords[key].hasOwnProperty('first_name')) {
                    staffRecords[key]['name'] = staffRecords[key]['first_name'];
                }
                StaffController.appendName(staffRecords[key], 'middle_name');
                StaffController.appendName(staffRecords[key], 'third_name');
                StaffController.appendName(staffRecords[key], 'last_name');
            }
        }

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = staffRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        StaffController.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return staffRecords;
    }

    function insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, userRecord) {
        UtilsSvc.isAppendLoader(true);
        AlertSvc.reset($scope);
        var data = {
            staff_id: staffId,
            staff_name: staffId,
            institution_position_id: institutionPositionId,
            staff_assignment: true,
            academic_period_id: academicPeriodId,
            position_type: positionType,
            staff_type_id: staffTypeId,
            FTE: fte,
            start_date: startDate,
            end_date: endDate
        };
        var deferred = $q.defer();
        InstitutionsStaffSvc.postAssignedStaff(data)
        .then(function(postResponse) {
            StaffController.postResponse = postResponse.data;
            UtilsSvc.isAppendLoader(false);
            StaffController.addStaffError = false;
            StaffController.transferStaffError = false;
            var log = [];
            var counter = 0;
            angular.forEach(postResponse.data.error , function(value) {
                counter++;
            }, log);
            if (counter == 0) {
                AlertSvc.success($scope, 'The staff is added successfully.');
                $window.location.href = 'add?staff_added=true';
                deferred.resolve(StaffController.postResponse);
            }
            else if (counter == 1 && postResponse.data.error.hasOwnProperty('staff_assignment') && postResponse.data.error.staff_assignment.hasOwnProperty('ruleTransferRequestExists')) {
                AlertSvc.warning($scope, 'There is an existing transfer in request.');
                $window.location.href = postResponse.data.error.staff_assignment.ruleTransferRequestExists;
                deferred.resolve(StaffController.postResponse);
            } else if (counter == 1 && postResponse.data.error.hasOwnProperty('staff_assignment') && postResponse.data.error.staff_assignment.hasOwnProperty('ruleCheckStaffAssignment')) {
                InstitutionsStaffSvc.getStaffData(staffId, startDate, endDate)
                .then(function(response) {
                    StaffController.selectedStaffData['institution_staff'] = response.institution_staff;
                    var idName = StaffController.selectedStaffData.openemis_no + ' - ' + StaffController.selectedStaffData.name;
                    var institutionName = StaffController.selectedStaffData['institution_staff'][0]['institution']['code_name'];
                    StaffController.transferStaffError = true;
                    AlertSvc.info($scope, idName + ' is currently assigned to '+ institutionName +'. By clicking save, a transfer request will be sent to the institution for approval');
                    deferred.resolve(StaffController.postResponse);
                }, function(error) {
                    StaffController.transferStaffError = true;
                    AlertSvc.warning($scope, 'Staff is currently assigned to another Institution.');
                    deferred.resolve(StaffController.postResponse);
                });

            } else {
                StaffController.addStaffError = true;
                AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                deferred.resolve(StaffController.postResponse);
            }

        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function onAddNewStaffClick() {
        StaffController.createNewStaff = true;
        StaffController.completeDisabled = false;
        StaffController.selectedStaffData = {};
        StaffController.selectedStaffData.first_name = '';
        StaffController.selectedStaffData.last_name = '';
        StaffController.selectedStaffData.date_of_birth = '';
        StaffController.initNationality();
        StaffController.initIdentityType();
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "createUser"
        });
    }

    function onAddStaffClick() {
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "addStaff"
        });
    }

    function onAddStaffCompleteClick() {
        StaffController.postForm().then(function(response) {
            if (StaffController.addStaffError) {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: "addStaff"
                });
            } else if (StaffController.transferStaffError) {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: "transferStaff"
                });
            }
        }, function(error) {
            console.log(errors);
            // error handling here
        });
    }

    function onExternalSearchClick() {
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "externalSearch"
        });
    }

    function selectStaff(id) {
        StaffController.selectedStaff = id;
        StaffController.getStaffData();
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
        var staffData = StaffController.selectedStaffData;
        if (staffData.hasOwnProperty('gender_id')) {
            var genderOptions = StaffController.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == staffData.gender_id) {
                    staffData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StaffController.selectedStaffData = staffData;
        }
    }

    function getStaffData() {
        var log = [];
        angular.forEach(StaffController.rowsThisPage , function(value) {
            if (value.id == StaffController.selectedStaff) {
                StaffController.selectedStaffData = value;
            }
        }, log);
    }

    function onChangeAcademicPeriod() {
        AlertSvc.reset($scope);

        if (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
            StaffController.startDate = InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date);
        }

        var startDatePicker = angular.element(document.getElementById('Staff_start_date'));
        startDatePicker.datepicker("setStartDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
        startDatePicker.datepicker("setEndDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.end_date));
        startDatePicker.datepicker("setDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
        var endDatePicker = angular.element(document.getElementById('Staff_end_date'));
        endDatePicker.datepicker("setStartDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
        StaffController.onChangeFTE();
    }

    function postTransferForm() {
        var startDate = StaffController.startDate;
        var startDateArr = startDate.split("-");
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        for(i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var positionType = StaffController.positionType;
        var institutionPositionId = (StaffController.institutionPositionOptions.hasOwnProperty('selectedOption') && StaffController.institutionPositionOptions.selectedOption != null) ? StaffController.institutionPositionOptions.selectedOption.value: '';
        institutionPositionId = (institutionPositionId == undefined) ? '' : institutionPositionId;
        var fte = StaffController.fte;
        var staffTypeId = (StaffController.staffTypeId != null && StaffController.staffTypeId.hasOwnProperty('id')) ? StaffController.staffTypeId.id : '';
        var data = {
            staff_id: StaffController.selectedStaff,
            start_date: startDate,
            end_date: StaffController.endDate,
            staff_type_id: staffTypeId,
            FTE: fte,
            institution_position_id: institutionPositionId,
            status: 0,
            institution_id: StaffController.institutionId,
            previous_institution_id: StaffController.selectedStaffData.institution_staff[0]['institution']['id'],
            type: 2,
            comment: StaffController.comment,
            update: 0
        };

        InstitutionsStaffSvc.addStaffTransferRequest(data)
        .then(function(response) {
            var data = response.data;
            if (data.error.length == 0) {
                AlertSvc.success($scope, 'Staff transfer request is added successfully.');
                $window.location.href = 'add?staff_transfer_added=true';
            } else if (data.error.hasOwnProperty('update') && data.error.update.hasOwnProperty('ruleTransferRequestExists')) {
                AlertSvc.warning($scope, 'There is an existing transfer in request.');
                $window.location.href = data.error.update.ruleTransferRequestExists;
            } else {
                console.log(response);
                AlertSvc.error($scope, 'There is an error in adding staff transfer request.');
            }
        }, function(error) {
            console.log(error);
            AlertSvc.error($scope, 'There is an error in adding staff transfer request.');
        })
    }

    function postForm() {
        var deferred = $q.defer();
        var academicPeriodId = (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption'))? StaffController.academicPeriodOptions.selectedOption.id: '';
        var positionType = StaffController.positionType;
        var institutionPositionId = (StaffController.institutionPositionOptions.hasOwnProperty('selectedOption') && StaffController.institutionPositionOptions.selectedOption != null) ? StaffController.institutionPositionOptions.selectedOption.value: '';
        institutionPositionId = (institutionPositionId == undefined) ? '' : institutionPositionId;
        var fte = StaffController.fte;
        var staffTypeId = (StaffController.staffTypeId != null && StaffController.staffTypeId.hasOwnProperty('id')) ? StaffController.staffTypeId.id : '';
        var startDate = StaffController.startDate;
        var startDateArr = startDate.split("-");
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        for(i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var endDate = StaffController.endDate;

        if (!StaffController.createNewStaff) {
            if (StaffController.externalSearch) {
                var staffData = StaffController.selectedStaffData;
                var amendedStaffData = Object.assign({}, staffData);
                amendedStaffData.date_of_birth = InstitutionsStaffSvc.formatDate(amendedStaffData.date_of_birth);
                return StaffController.addStaffUser(amendedStaffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate);
            } else {
                var staffId = StaffController.selectedStaff;
                return StaffController.insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, {});
            }
        } else {
            if (StaffController.selectedStaffData != null) {
                var staffData = {};
                var log = [];
                angular.forEach(StaffController.selectedStaffData, function(value, key) {
                  staffData[key] = value;
                }, log);
                if (staffData.hasOwnProperty('date_of_birth')) {
                    var dateOfBirth = staffData.date_of_birth;
                    var dateOfBirthArr = dateOfBirth.split("-");
                    dateOfBirth = dateOfBirthArr[2] + '-' + dateOfBirthArr[1] + '-' + dateOfBirthArr[0];
                    staffData.date_of_birth = dateOfBirth;
                }
                delete staffData['id'];
                delete staffData['institution_staff'];
                delete staffData['is_staff'];
                delete staffData['is_guardian'];
                delete staffData['address'];
                delete staffData['postal_code'];
                delete staffData['address_area_id'];
                delete staffData['birthplace_area_id'];
                delete staffData['date_of_death'];
                staffData['super_admin'] = 0;
                staffData['status'] = 1;
                delete staffData['last_login'];
                delete staffData['photo_name'];
                delete staffData['photo_content'];
                delete staffData['modified'];
                delete staffData['modified_user_id'];
                delete staffData['created'];
                delete staffData['created_user_id'];
                if (staffData['username'] == '') {
                    staffData['username'] = null;
                    staffData['password'] = null;
                } else {
                    staffData['password'] = (staffData['password'] == '') ? null : staffData['password'];
                }
                return StaffController.addStaffUser(staffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate);
            }
        }
    }

    function addStaffUser(staffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate) {
        var deferred = $q.defer();
        var newStaffData = staffData;
        newStaffData['academic_period_id'] = academicPeriodId;
        newStaffData['start_date'] = startDate;
        newStaffData['nationality_id'] = StaffController.Staff.nationality_id;
        newStaffData['identity_type_id'] = StaffController.Staff.identity_type_id;
        InstitutionsStaffSvc.addUser(newStaffData)
        .then(function(user){
            if (user[0].error.length === 0) {
                var staffId = user[0].data.id;
                deferred.resolve(StaffController.insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, user[1]));
            } else {
                StaffController.postResponse = user[0];
                AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                deferred.resolve(StaffController.postResponse);
            }
        }, function(error){
            console.log(error);
            deferred.reject(error);
            AlertSvc.warning($scope, error);
        });

        return deferred.promise;
    }


    angular.element(document.querySelector('#wizard')).on('actionclicked.fu.wizard', function(evt, data) {
        // evt.preventDefault();
        AlertSvc.reset($scope);

        if (angular.isDefined(StaffController.postResponse)){
            delete StaffController.postResponse;
            $scope.$apply();
        }
        // To go to add student page if there is a student selected from the internal search
        // or external search
        if (data.step == 3 && data.direction == 'next') {
            if (StaffController.validateNewUser()) {
                evt.preventDefault();
            };
        }
    });

    function validateNewUser() {
        var remain = false;
        var empty = {'_empty': 'This field cannot be left empty'};
        StaffController.postResponse = {};
        StaffController.postResponse.error = {};
        if (StaffController.selectedStaffData.first_name == '') {
            StaffController.postResponse.error.first_name = empty;
            remain = true;
        }

        if (StaffController.selectedStaffData.last_name == '') {
            StaffController.postResponse.error.last_name = empty;
            remain = true;
        }
        if (StaffController.selectedStaffData.gender_id == '' || StaffController.selectedStaffData.gender_id == null) {
            StaffController.postResponse.error.gender_id = empty;
            remain = true;
        }

        if (StaffController.selectedStaffData.date_of_birth == '') {
            StaffController.postResponse.error.date_of_birth = empty;
            remain = true;
        }

        if (StaffController.StaffNationalities == 1 && (StaffController.Staff.nationality_id == '' || StaffController.Staff.nationality_id == undefined)) {
            remain = true;
        }

        var arrNumber = [{}];

        if (StaffController.StaffIdentities == 1 && (StaffController.selectedStaffData.identity_number == '' || StaffController.selectedStaffData.identity_number == undefined)) {
            arrNumber[0]['number'] = empty;
            StaffController.postResponse.error.identities = arrNumber;
            remain = true;
        }

        var arrNationality = [{}];
        if (StaffController.StaffNationalities == 1 && (StaffController.Staff.nationality_id == '' || StaffController.Staff.nationality_id == undefined)) {
            arrNationality[0]['nationality_id'] = empty;
            StaffController.postResponse.error.nationalities = arrNationality;
            remain = true;
        }

        if (remain) {
            AlertSvc.error($scope, 'Please review the errors in the form.');
            $scope.$apply();
            angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                step: 'createUser'
            });
        }
        return remain;
    }

    function getUniqueOpenEmisId() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.getUniqueOpenEmisId()
        .then(function(response) {
            StaffController.selectedStaffData.openemis_no = response;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    angular.element(document.querySelector('#wizard')).on('finished.fu.wizard', function(evt, data) {
        // The last complete step is now transfer staff, add transfer staff logic function call here
        StaffController.postTransferForm();
    });

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function(evt, data) {
        StaffController.addStaffButton = false;
        // Step 1 - Internal search
        if (data.step == 1) {
            StaffController.Staff.identity_type_name = StaffController.defaultIdentityTypeName;
            StaffController.Staff.identity_type_id = StaffController.defaultIdentityTypeId;
            delete StaffController.postResponse;
            StaffController.reloadInternalDatasource(true);
            StaffController.createNewStaff = false;
            StaffController.externalSearch = false;
            StaffController.step = 'internal_search';
        }
        // Step 2 - External search
        else if (data.step == 2) {
            StaffController.Staff.identity_type_name = StaffController.externalIdentityType;
            StaffController.Staff.identity_type_id = StaffController.defaultIdentityTypeId;
            delete StaffController.postResponse;
            StaffController.reloadExternalDatasource(true);
            StaffController.createNewStaff = false;
            StaffController.externalSearch = true;
            StaffController.step = 'external_search';
        }
        // Step 3 - Create user
        else if (data.step == 3) {
            StaffController.externalSearch = false;
            StaffController.createNewStaff = true;
            StaffController.step = 'create_user';
            StaffController.getUniqueOpenEmisId();
            InstitutionsStaffSvc.resetExternalVariable();
        }
        // Step 4 - Add Staff
        else if (data.step == 4) {
            if (StaffController.externalSearch) {
                StaffController.getUniqueOpenEmisId();
            }
            // Work around for alert reset
            StaffController.createNewInternalDatasource(StaffController.internalGridOptions, true);
            StaffController.step = 'add_staff';
        }
        // Step 5 - Transfer Staff
        else if (data.step == 5) {
            StaffController.step = 'transfer_staff';
        }
    });


}
