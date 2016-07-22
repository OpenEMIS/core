angular
    .module('institutions.students.ctrl', ['utils.svc', 'alert.svc', 'institutions.students.svc'])
    .controller('InstitutionsStudentsCtrl', InstitutionStudentController);

InstitutionStudentController.$inject = ['$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'InstitutionsStudentsSvc'];

function InstitutionStudentController($scope, $window, $filter, UtilsSvc, AlertSvc, InstitutionsStudentsSvc) {
    // ag-grid vars
    $scope.rowsThisPage = null;

    var StudentController = this;

    StudentController.externalSearch = false;

    // Controller functions
    StudentController.processStudentRecord = processStudentRecord;
    StudentController.createNewInternalDatasource = createNewInternalDatasource;
    StudentController.createNewExternalDatasource = createNewExternalDatasource;
    
    $scope.currentStep = 1;
    $scope.pageSize = 10;
    $scope.internalGridOptions = null;
    $scope.externalGridOptions = null;

    $scope.selectedStudent;
    $scope.selectedStudentData;
    $scope.startDate = '';
    $scope.endDate;
    $scope.endDateFormatted;

    $scope.defaultIdentityTypeName;

    $scope.academicPeriodOptions = {};
    $scope.educationGradeOptions = {};
    $scope.classOptions = {};

    // not used
    // $scope.studentStatusOptions;

    // filter variables
    $scope.internalFilterOpenemisNo;
    $scope.internalFilterFirstName;
    $scope.internalFilterLastName;
    $scope.internalFilterIdentityNumber;
    $scope.externalFilterOpenemisNo;
    $scope.externalFilterFirstName;
    $scope.externalFilterLastName;
    $scope.externalFilterIdentityNumber;

    $scope.postResponse;

    // UI control vars
    $scope.initialLoad = true;


    angular.element(document).ready(function () {
        InstitutionsStudentsSvc.init(angular.baseUrl);

        UtilsSvc.isAppendLoader(true);

        InstitutionsStudentsSvc.getAcademicPeriods()
        .then(function(periods) {
            $scope.academicPeriodOptions = {
                availableOptions: periods,
                selectedOption: periods[0]
            };

            if ($scope.academicPeriodOptions.hasOwnProperty('selectedOption')) {
                $scope.endDate = InstitutionsStudentsSvc.formatDate($scope.academicPeriodOptions.selectedOption.end_date);
                $scope.onChangeAcademicPeriod();
            }

            return InstitutionsStudentsSvc.getDefaultIdentityType();
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        .then(function(defaultIdentityType) {
            if (defaultIdentityType.length > 0) {
                $scope.defaultIdentityTypeName = defaultIdentityType[0].name;
            }

            $scope.initGrid();
        }, function(error){
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        ;

    });

    $scope.initGrid = function() {
        $scope.internalGridOptions = {
            columnDefs: [
                {
                    field:'id',
                    headerName:'',
                    suppressMenu: true,
                    suppressSorting: true,
                    width: 30,
                    maxWidth: 30,
                    cellRenderer: function(params) {
                        // console.log();
                        var data = JSON.stringify(params.data);
                        return '<div><input  name="ngSelectionCell" ng-click="selectStudent('+params.value+')" tabindex="-1" type="radio" selectedStudent="'+params.value+'"/></div>';
                    }
                },
                {headerName: "Openemis No", field: "openemis_no", suppressMenu: true, suppressSorting: true},
                {headerName: "First Name", field: "first_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Last Name", field: "last_name", suppressMenu: true, suppressSorting: true},

                {headerName: (angular.isDefined($scope.defaultIdentityTypeName))? $scope.defaultIdentityTypeName: "[default identity type not set]", field: "default_identity_type", suppressMenu: true, suppressSorting: true},
                // {headerName: "Currrent Institution", field: "institution_name", suppressMenu: true, suppressSorting: true},
                // {headerName: "Currrent Academic Period", field: "academic_period_name", suppressMenu: true, suppressSorting: true},
                // {headerName: "Currrent Education Grade", field: "education_grade_name", suppressMenu: true, suppressSorting: true}

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
            angularCompileRows: true,
            onGridReady: function() {
                $scope.reloadInternalDatasource();
                $scope.internalGridOptions.api.sizeColumnsToFit();
            },
        };

        $scope.externalGridOptions = {
            columnDefs: [
                {
                    field:'id',
                    headerName:'',
                    suppressMenu: true,
                    suppressSorting: true,
                    width: 30,
                    maxWidth: 30,
                    cellRenderer: function(params) {
                        // console.log();
                        var data = JSON.stringify(params.data);
                        return '<div><input  name="ngSelectionCell" ng-click="selectStudent('+params.value+')" tabindex="-1" type="radio" selectedStudent="'+params.value+'"/></div>';
                    }
                },
                {headerName: "Openemis No", field: "openemis_no", suppressMenu: true, suppressSorting: true},
                {headerName: "First Name", field: "first_name", suppressMenu: true, suppressSorting: true},
                {headerName: "Last Name", field: "last_name", suppressMenu: true, suppressSorting: true},

                {headerName: (angular.isDefined($scope.defaultIdentityTypeName))? $scope.defaultIdentityTypeName: "[default identity type not set]", field: "default_identity_type", suppressMenu: true, suppressSorting: true},
                // {headerName: "Currrent Institution", field: "institution_name", suppressMenu: true, suppressSorting: true},
                // {headerName: "Currrent Academic Period", field: "academic_period_name", suppressMenu: true, suppressSorting: true},
                // {headerName: "Currrent Education Grade", field: "education_grade_name", suppressMenu: true, suppressSorting: true}

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
            angularCompileRows: true,
            onGridReady: function() {
                $scope.externalGridOptions.api.sizeColumnsToFit();
            },
        };
    };

    $scope.reloadInternalDatasource = function () {
        StudentController.createNewInternalDatasource($scope.internalGridOptions);
    };

    $scope.reloadExternalDatasource = function () {
        StudentController.createNewExternalDatasource($scope.externalGridOptions);
    };

    $scope.clearInternalSearchFilters = function () {
        $scope.internalFilterOpenemisNo = '';
        $scope.internalFilterFirstName = '';
        $scope.internalFilterLastName = '';
        $scope.internalFilterIdentityNumber = '';
        StudentController.createNewInternalDatasource($scope.internalGridOptions);
        $scope.currentStep = 1;
    };

    $scope.clearExternalSearchFilters = function () {
        $scope.externalFilterOpenemisNo = '';
        $scope.externalFilterFirstName = '';
        $scope.externalFilterLastName = '';
        $scope.externalFilterIdentityNumber = '';
        StudentController.createNewExternalDatasource($scope.externalGridOptions);
        $scope.currentStep = 2;
    };

    $scope.$watch('endDate', function (newValue) {
        $scope.endDateFormatted = $filter('date')(newValue, 'dd-MM-yyyy');
    });

    function createNewInternalDatasource(gridObj) {
        var totalCount = 0;
        var dataSource = {
            //rowCount: ???, - not setting the row count, infinite paging will be used
            pageSize: $scope.pageSize, // changing to number, as scope keeps it as a string
            getRows: function (params) {
                AlertSvc.reset($scope);
                delete $scope.selectedStudent;
                InstitutionsStudentsSvc.getStudentRecords(
                    {
                        startRow: params.startRow,
                        endRow: params.endRow,
                        conditions: {
                            openemis_no: $scope.internalFilterOpenemisNo,
                            first_name: $scope.internalFilterFirstName,
                            last_name: $scope.internalFilterLastName,
                            identity_number: $scope.internalFilterIdentityNumber,
                        }
                    }
                )
                .then(function(response) {
                    var studentRecords = response.data;
                    totalCount = response.total;
                    return StudentController.processStudentRecord(studentRecords, params);
                }, function(error) {
                    // No Assessment
                    console.log(error);
                    AlertSvc.warning($scope, error);
                });
            }
        };
        gridObj.api.setDatasource(dataSource);
    }

    function createNewExternalDatasource(gridObj) {
        var totalCount = 0;
        var dataSource = {
            //rowCount: ???, - not setting the row count, infinite paging will be used
            pageSize: $scope.pageSize, // changing to number, as scope keeps it as a string
            getRows: function (params) {
                AlertSvc.reset($scope);
                delete $scope.selectedStudent;
                InstitutionsStudentsSvc.getExternalStudentRecords(
                    {
                        startRow: params.startRow,
                        endRow: params.endRow,
                        conditions: {
                            openemis_no: $scope.externalFilterOpenemisNo,
                            first_name: $scope.externalFilterFirstName,
                            last_name: $scope.externalFilterLastName,
                            identity_number: $scope.externalFilterIdentityNumber,
                        }
                    }
                )
                .then(function(response) {
                    var studentRecords = response.data;
                    totalCount = response.total;
                    return StudentController.processStudentRecord(studentRecords, params);
                }, function(error) {
                    // No Assessment
                    console.log(error);
                    AlertSvc.warning($scope, error);
                });
            }
        };
        gridObj.api.setDatasource(dataSource);
    }

    function processStudentRecord(studentRecords, params)
    {
        for(var key in studentRecords) {
            // default values
            studentRecords[key]['institution_name'] = '-';
            studentRecords[key]['academic_period_name'] = '-';
            studentRecords[key]['education_grade_name'] = '-';
            if ((studentRecords[key].hasOwnProperty('institution_students') && studentRecords[key]['institution_students'].length > 0)) {
                studentRecords[key]['institution_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('institution')))? studentRecords[key].institution_students['0'].institution.name: '-';
                studentRecords[key]['academic_period_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('academic_period')))? studentRecords[key].institution_students['0'].academic_period.name: '-';
                studentRecords[key]['education_grade_name'] = ((studentRecords[key].institution_students['0'].hasOwnProperty('education_grade')))? studentRecords[key].institution_students['0'].education_grade.name: '-';
            }
        }

        $scope.lastRow = -1;
        if (studentRecords.length == 0) {
            if (params.startRow == 0) {
                $scope.lastRow = 0;
            } else {
                $scope.lastRow = params.endRow - ($scope.pageSize % studentRecords.length);
            }
            studentRecords = null;
        } else if (studentRecords.length < $scope.pageSize) {
            $scope.lastRow = params.endRow - ($scope.pageSize % studentRecords.length);
        }
        $scope.rowsThisPage = studentRecords;
        
        params.successCallback($scope.rowsThisPage, $scope.lastRow);
        UtilsSvc.isAppendLoader(false);
        $scope.initialLoad = false;
        return studentRecords;
    }

    $scope.onAddNewStudentClick = function() {
        $window.location.href = 'add'
    };

    $scope.selectStudent = function(id) {
        $scope.selectedStudent = id;
        $scope.getStudentData();
    };

    $scope.getStudentData = function() {
        InstitutionsStudentsSvc.getStudentData($scope.selectedStudent)
            .then(function(studentData) {
                studentData.date_of_birth = InstitutionsStudentsSvc.formatDate(studentData.date_of_birth);
                $scope.selectedStudentData = studentData
                return studentData;
            }, function(error) {
                // No Assessment
                console.log(error);
                AlertSvc.warning($scope, error);
            })
            ;
    };

    $scope.formatDateReverse = function(datetime) {
        datetime = new Date(datetime);

        var yyyy = datetime.getFullYear().toString();
        var mm = (datetime.getMonth()+1).toString(); // getMonth() is zero-based
        var dd  = datetime.getDate().toString();

        return (dd[1]?dd:"0"+dd[0]) + '-' + (mm[1]?mm:"0"+mm[0]) + '-' + yyyy; // padding
    }

    $scope.onChangeAcademicPeriod = function() {
        AlertSvc.reset($scope);

        if ($scope.academicPeriodOptions.hasOwnProperty('selectedOption')) {
            $scope.endDate = InstitutionsStudentsSvc.formatDate($scope.academicPeriodOptions.selectedOption.end_date);
            $scope.startDate = $scope.formatDateReverse($scope.academicPeriodOptions.selectedOption.start_date);
        }

        var startDatePicker = angular.element(document.getElementById('Students_start_date'));
        startDatePicker.datepicker("setStartDate", $scope.formatDateReverse($scope.academicPeriodOptions.selectedOption.start_date));
        startDatePicker.datepicker("setEndDate", $scope.formatDateReverse($scope.academicPeriodOptions.selectedOption.end_date));
        startDatePicker.datepicker("setDate", $scope.formatDateReverse($scope.academicPeriodOptions.selectedOption.start_date));

        $scope.educationGradeOptions = null;
        InstitutionsStudentsSvc.getEducationGrades({
            academicPeriodId: $scope.academicPeriodOptions.selectedOption.id
        })
        .then(function(educationGrades) {
            $scope.educationGradeOptions = {
                availableOptions: educationGrades,
            };
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    };

    $scope.onChangeEducationGrade = function() {
        AlertSvc.reset($scope);

        $scope.classOptions = null;

        InstitutionsStudentsSvc.getClasses({
            academicPeriodId: $scope.academicPeriodOptions.selectedOption.id,
            gradeId: $scope.educationGradeOptions.selectedOption.education_grade_id
        })
        .then(function(classes) {
            $scope.classOptions = {
                availableOptions: classes,
            };
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    };

    $scope.postForm = function() {
        var studentId = null;
        var academicPeriodId = ($scope.academicPeriodOptions.hasOwnProperty('selectedOption'))? $scope.academicPeriodOptions.selectedOption.id: '';
        var educationGradeId = ($scope.educationGradeOptions.hasOwnProperty('selectedOption'))? $scope.educationGradeOptions.selectedOption.education_grade_id: '';
        var classId = null;
        if ($scope.classOptions.hasOwnProperty('selectedOption')) {
            classId = $scope.classOptions.selectedOption.id;
        }
        var startDate = $scope.startDate;
        var startDateArr = startDate.split("-");
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        var endDate = $scope.endDate;

        
    };

    $scope.insertStudentData = function(studentId, academicPeriodId, educationGradeId, classId, startDate, endDate) {
        UtilsSvc.isAppendLoader(true);
        AlertSvc.reset($scope);
        var data = {
            student_id: studentId,
            student_name: studentId,
            academic_period_id: academicPeriodId, 
            education_grade_id: educationGradeId,
            start_date: startDate,
            end_date: endDate
        };

        if (classId != null) {
            data['class'] = classId;
        }

        InstitutionsStudentsSvc.postEnrolledStudent(data).then(function(postResponse) {
            $scope.postResponse = postResponse.data;
            console.log($scope.postResponse);
            UtilsSvc.isAppendLoader(false);
            if (postResponse.data.error.length === 0) {
                AlertSvc.success($scope, 'The record has been added successfully.');
                $window.location.href = 'index'
            } else {
                AlertSvc.error($scope, 'The record is not added due to errors encountered.');
            }
        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    };

    angular.element(document.querySelector('#wizard')).on('actionclicked.fu.wizard', function(evt, data) {
        // evt.preventDefault();
        AlertSvc.reset($scope);

        // To go to add student page if there is a student selected from the internal search
        // or external search
        if ($scope.selectedStudent && (data.step == 1 || data.step == 2) && data.direction == 'next') {
            angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                step: 3
            });
            evt.preventDefault()
        }
        console.log(data);

        if (angular.isDefined($scope.postResponse)){
            delete $scope.postResponse;
            $scope.$apply();
        }

    });

    angular.element(document.querySelector('#wizard')).on('finished.fu.wizard', function(evt, data) {
        $scope.postForm();
    });

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function(evt, data) {
        $scope.currentStep = data.step;
        // External Search
        if (data.step == 2) {
            $scope.reloadExternalDatasource();
        } else if (data.step == 1) {
            $scope.reloadInternalDatasource();
        }
        console.log(data);
    });


}
