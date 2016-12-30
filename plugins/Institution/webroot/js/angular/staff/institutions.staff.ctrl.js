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
    StaffController.institutionPositionId = {};
    StaffController.step = 'internal_search';
    StaffController.showExternalSearchButton = false;
    StaffController.completeDisabled = false;
    StaffController.institutionId = null;

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
    StaffController.onAddStaffClick = onAddStaffClick;
    StaffController.getUniqueOpenEmisId = getUniqueOpenEmisId;
    StaffController.reloadInternalDatasource = reloadInternalDatasource;
    StaffController.reloadExternalDatasource = reloadExternalDatasource;
    StaffController.clearInternalSearchFilters = clearInternalSearchFilters;
    StaffController.onChangePositionType = onChangePositionType;
    StaffController.onChangeFTE = onChangeFTE;
    StaffController.initialLoad = true;
    StaffController.date_of_birth = '';

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

            StaffController.institutionPositionId = {
                availableOptions: [],
                selectedOption: ''
            };

            if (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
                StaffController.onChangeAcademicPeriod();
            }
            promises.push(InstitutionsStaffSvc.getAddNewStaffConfig());
            return $q.all(promises);
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
            UtilsSvc.isAppendLoader(false);
        })
        .then(function(promisesObj) {
            var promises = [];
            var addNewStaffConfig = promisesObj[0].data;
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
        var startDate = StaffController.startDate;
        var endDate = StaffController.endDate;
        InstitutionsStaffSvc.getPositionList(academicPeriodId, fte, startDate, endDate)
        .then(function(response) {
            StaffController.institutionPositionId.availableOptions = response;
            console.log(StaffController.institutionPositionId.availableOptions);
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
        StaffController.createNewInternalDatasource(StaffController.internalGridOptions, withData);
    };

    function reloadExternalDatasource(withData) {
        InstitutionsStaffSvc.resetExternalVariable();
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
                delete StaffController.selectedStaff;
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
                        var studentRecords = response.data;
                        var totalRowCount = response.total;
                        return StaffController.processStaffRecord(studentRecords, params, totalRowCount);
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
                delete StaffController.selectedStaff;
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
                        var studentRecords = response.data;
                        var totalRowCount = response.total;
                        StaffController.initialLoad = false;
                        return StaffController.processExternalStaffRecord(studentRecords, params, totalRowCount);
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
                        var studentRecords = [];
                        InstitutionsStaffSvc.init(angular.baseUrl);
                        return StaffController.processExternalStaffRecord(studentRecords, params, 0);
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

    function processExternalStaffRecord(studentRecords, params, totalRowCount) {
        for(var key in studentRecords) {
            var mapping = InstitutionsStaffSvc.getExternalSourceMapping();
            studentRecords[key]['institution_name'] = '-';
            studentRecords[key]['academic_period_name'] = '-';
            studentRecords[key]['education_grade_name'] = '-';
            studentRecords[key]['date_of_birth'] = InstitutionsStaffSvc.formatDate(studentRecords[key][mapping.date_of_birth_mapping]);
            studentRecords[key]['gender_name'] = studentRecords[key][mapping.gender_mapping];
            studentRecords[key]['gender'] = {'name': studentRecords[key][mapping.gender_mapping]};
            studentRecords[key]['identity_type_name'] = studentRecords[key][mapping.identity_type_mapping];
            studentRecords[key]['identity_number'] = studentRecords[key][mapping.identity_number_mapping];
            studentRecords[key]['nationality_name'] = studentRecords[key][mapping.nationality_mapping];
            studentRecords[key]['name'] = '';
            if (studentRecords[key].hasOwnProperty(mapping.first_name_mapping)) {
                studentRecords[key]['name'] = studentRecords[key][mapping.first_name_mapping];
            }
            StaffController.appendName(studentRecords[key], mapping.middle_name_mapping);
            StaffController.appendName(studentRecords[key], mapping.third_name_mapping);
            StaffController.appendName(studentRecords[key], mapping.last_name_mapping);
        }

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = studentRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        StaffController.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return studentRecords;
    }

    function processStaffRecord(studentRecords, params, totalRowCount) {
        for(var key in studentRecords) {
            studentRecords[key]['institution_name'] = '-';
            studentRecords[key]['academic_period_name'] = '-';
            studentRecords[key]['education_grade_name'] = '-';
            if ((studentRecords[key].hasOwnProperty('institution_students') && studentRecords[key]['institution_students'].length > 0)) {
                studentRecords[key]['institution_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('institution')))? studentRecords[key].institution_students['0'].institution.name: '-';
                studentRecords[key]['academic_period_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('academic_period')))? studentRecords[key].institution_students['0'].academic_period.name: '-';
                studentRecords[key]['education_grade_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('education_grade')))? studentRecords[key].institution_students['0'].education_grade.name: '-';
            }

            studentRecords[key]['date_of_birth'] = InstitutionsStaffSvc.formatDate(studentRecords[key]['date_of_birth']);
            studentRecords[key]['gender_name'] = studentRecords[key]['gender']['name'];

            if (!studentRecords[key].hasOwnProperty('name')) {
                studentRecords[key]['name'] = '';
                if (studentRecords[key].hasOwnProperty('first_name')) {
                    studentRecords[key]['name'] = studentRecords[key]['first_name'];
                }
                StaffController.appendName(studentRecords[key], 'middle_name');
                StaffController.appendName(studentRecords[key], 'third_name');
                StaffController.appendName(studentRecords[key], 'last_name');
            }
        }

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = studentRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        StaffController.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return studentRecords;
    }

    function insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, userRecord) {
        UtilsSvc.isAppendLoader(true);
        AlertSvc.reset($scope);
        var data = {
            staff_id: staffId,
            staff_name: staffId,
            institution_position_id: institutionPositionId,
            academic_period_id: academicPeriodId,
            position_type: positionType,
            staff_type_id: staffTypeId,
            FTE: fte,
            start_date: startDate,
            end_date: endDate
        };

        InstitutionsStaffSvc.postAssignedStaff(data)
        .then(function(postResponse) {
            StaffController.postResponse = postResponse.data;
            UtilsSvc.isAppendLoader(false);
            if (postResponse.data.error.length === 0) {
                AlertSvc.success($scope, 'The staff is added successfully.');
                $window.location.href = 'add?staff_added=true';
            } else {
                if (userRecord.hasOwnProperty('institution_students')) {
                    if (userRecord.institution_students.length > 0) {
                        var schoolName = userRecord['institution_students'][0]['institution']['name'];
                        AlertSvc.warning($scope, 'Staff is already added in ' + schoolName);
                        userRecord.date_of_birth = InstitutionsStaffSvc.formatDate(userRecord.date_of_birth);
                        StaffController.selectedStaffData = userRecord;
                        StaffController.completeDisabled = true;
                    } else {
                        AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                    }
                } else {
                    AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                }
            }
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
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
        var studentData = StaffController.selectedStaffData;
        studentData.name = '';

        if (studentData.hasOwnProperty('first_name')) {
            studentData.name = studentData.first_name.trim();
        }
        StaffController.appendName(studentData, 'middle_name', true);
        StaffController.appendName(studentData, 'third_name', true);
        StaffController.appendName(studentData, 'last_name', true);
        StaffController.selectedStaffData = studentData;
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
        var studentData = StaffController.selectedStaffData;
        if (studentData.hasOwnProperty('gender_id')) {
            var genderOptions = StaffController.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == studentData.gender_id) {
                    studentData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StaffController.selectedStaffData = studentData;
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
            console.log(StaffController.startDate);
        }

        var startDatePicker = angular.element(document.getElementById('Staff_start_date'));
        startDatePicker.datepicker("setStartDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
        startDatePicker.datepicker("setEndDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.end_date));
        startDatePicker.datepicker("setDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
        var endDatePicker = angular.element(document.getElementById('Staff_end_date'));
        endDatePicker.datepicker("setStartDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
    }

    function postForm() {

        var academicPeriodId = (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption'))? StaffController.academicPeriodOptions.selectedOption.id: '';
        var positionType = StaffController.positionType;
        var fte = (StaffController.fteOptions.hasOwnProperty('selectedOption'))? StaffController.fteOptions.selectedOption.id: '';
        var staffTypeId = (StaffController.staffTypeIdOptions.hasOwnProperty('selectedOption'))? StaffController.staffTypeIdOptions.selectedOption.id: '';
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
                var studentData = StaffController.selectedStaffData;
                var amendedStaffData = Object.assign({}, studentData);
                amendedStaffData.date_of_birth = InstitutionsStaffSvc.formatDate(amendedStaffData.date_of_birth);
                StaffController.addStaffUser(amendedStaffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate);
            } else {
                var staffId = StaffController.selectedStaff;
                StaffController.insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, {});
            }
        } else {
            console.log('postForm');
            if (StaffController.selectedStaffData != null) {
                console.log('not null');
                var studentData = {};
                var log = [];
                angular.forEach(StaffController.selectedStaffData, function(value, key) {
                  studentData[key] = value;
                }, log);
                if (studentData.hasOwnProperty('date_of_birth')) {
                    var dateOfBirth = studentData.date_of_birth;
                    var dateOfBirthArr = dateOfBirth.split("-");
                    dateOfBirth = dateOfBirthArr[2] + '-' + dateOfBirthArr[1] + '-' + dateOfBirthArr[0];
                    studentData.date_of_birth = dateOfBirth;
                }
                delete studentData['id'];
                delete studentData['institution_students'];
                delete studentData['is_staff'];
                delete studentData['is_guardian'];
                delete studentData['address'];
                delete studentData['postal_code'];
                delete studentData['address_area_id'];
                delete studentData['birthplace_area_id'];
                delete studentData['date_of_death'];
                delete studentData['password'];
                studentData['super_admin'] = 0;
                studentData['status'] = 1;
                delete studentData['last_login'];
                delete studentData['photo_name'];
                delete studentData['photo_content'];
                delete studentData['modified'];
                delete studentData['modified_user_id'];
                delete studentData['created'];
                delete studentData['created_user_id'];
                StaffController.addStaffUser(studentData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate);
            }
        }
    }

    function addStaffUser(studentData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate) {

        var newStaffData = studentData;
        newStaffData['academic_period_id'] = academicPeriodId;
        newStaffData['start_date'] = startDate;
        newStaffData['nationality_id'] = StaffController.Staff.nationality_id;
        newStaffData['identity_type_id'] = StaffController.Staff.identity_type_id;
        InstitutionsStaffSvc.addUser(newStaffData)
        .then(function(user){
            if (user[0].error.length === 0) {
                var staffId = user[0].data.id;
                StaffController.insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, user[1]);
            } else {
                StaffController.postResponse = user[0];
                console.log(user[0]);
                AlertSvc.error($scope, 'The record is not added due to errors encountered.');
            }
        }, function(error){
            console.log(error);
            AlertSvc.warning($scope, error);
        });
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

        // if (StaffController.StaffIdentities == 1 && (StaffController.Staff.identity_type_id == '' || StaffController.Staff.identity_type_id == undefined)) {
        //     arrNumber[0]['identity_type_id'] = empty;
        //     StaffController.postResponse.error.identities = arrNumber;
        //     remain = true;
        // }
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
        StaffController.postForm();
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
        else {
            if (StaffController.externalSearch) {
                StaffController.getUniqueOpenEmisId();
            }
            studentData = StaffController.selectedStaffData;
            StaffController.completeDisabled = false;
            if (studentData.hasOwnProperty('institution_students')) {
                if (studentData.institution_students.length > 0) {
                    var schoolName = studentData['institution_students'][0]['institution']['name'];
                    AlertSvc.warning($scope, 'This student is already allocated to ' + schoolName);
                    StaffController.completeDisabled = true;
                }
            }
            StaffController.step = 'add_student';
        }
    });


}
