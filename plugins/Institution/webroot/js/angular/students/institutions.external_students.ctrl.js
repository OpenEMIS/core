angular
    .module('institutions.external_students.ctrl', ['utils.svc', 'alert.svc', 'institutions.external_students.svc'])
    .controller('InstitutionsStudentsCtrl', InstitutionStudentController);

InstitutionStudentController.$inject = ['$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'InstitutionsExternalStudentsSvc'];

function InstitutionStudentController($scope, $window, $filter, UtilsSvc, AlertSvc, InstitutionsStudentsSvc) {
    // ag-grid vars
    $scope.rowsThisPage = null;

    var StudentController = this;
    
    $scope.pageSize = 10;
    $scope.gridOptions = null;

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
    $scope.filterOpenemisNo;
    $scope.filterFirstName;
    $scope.filterLastName;
    $scope.filterIdentityNumber;

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
        $scope.gridOptions = {
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
                $scope.reloadDatasource();
                $scope.gridOptions.api.sizeColumnsToFit();
            },
        };
    };

    $scope.reloadDatasource = function () {
        $scope.createNewDatasource();
    }

    $scope.clearFilters = function () {
        $scope.filterOpenemisNo = '';
        $scope.filterFirstName = '';
        $scope.filterLastName = '';
        $scope.filterIdentityNumber = '';
        $scope.createNewDatasource();
    }

    $scope.$watch('endDate', function (newValue) {
        $scope.endDateFormatted = $filter('date')(newValue, 'dd-MM-yyyy');
    });

    $scope.createNewDatasource = function(studentRecords) {
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
                            openemis_no: $scope.filterOpenemisNo,
                            first_name: $scope.filterFirstName,
                            last_name: $scope.filterLastName,
                            identity_number: $scope.filterIdentityNumber,
                        }
                    }
                )
                    .then(function(response) {
                        var studentRecords = response.data;


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
                    }, function(error) {
                        // No Assessment
                        console.log(error);
                        AlertSvc.warning($scope, error);
                    })
                    ;
            }
        };

        $scope.gridOptions.api.setDatasource(dataSource);
    };

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
                if (!studentData.hasOwnProperty('name')) {
                    studentData.name = studentData.first_name;
                    if (studentData.middle_name != null) {
                        studentData.name = studentData.name + ' ' + studentData.middle_name;
                    }
                    if (studentData.third_name != null) {
                        studentData.name = studentData.name + ' ' + studentData.third_name;
                    }
                    if (studentData.last_name != null) {
                        studentData.name = studentData.name + ' ' + studentData.last_name;
                    }
                }
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

        // console.log($scope.selectedStudent);
        InstitutionsStudentsSvc.getStudentData($scope.selectedStudent)
        .then(function(studentData){
            InstitutionsStudentsSvc.addUser(studentData)
            .then(function(response) {
                console.log(response);
                var userId = response.data.id;
                UtilsSvc.isAppendLoader(true);
                AlertSvc.reset($scope);
                var data = {student_id: userId};
                // student_name validation
                data['student_name'] = userId;
                data['academic_period_id'] = ($scope.academicPeriodOptions.hasOwnProperty('selectedOption'))? $scope.academicPeriodOptions.selectedOption.id: '';
                data['education_grade_id'] = ($scope.educationGradeOptions.hasOwnProperty('selectedOption'))? $scope.educationGradeOptions.selectedOption.education_grade_id: '';

                if ($scope.classOptions.hasOwnProperty('selectedOption')) {
                    data['class'] = $scope.classOptions.selectedOption.id;
                }

                data['start_date'] = $scope.startDate;
                data['end_date'] = $scope.endDate;

                

                InstitutionsStudentsSvc.postEnrolledStudent(data).then(function(postResponse) {
                    $scope.postResponse = postResponse.data;
                    UtilsSvc.isAppendLoader(false);
                    if (postResponse.data.error.length === 0) {
                        AlertSvc.success($scope, 'The record has been added successfully.');
                        $window.location.href = 'index'
                    } else {
                        console.log(postResponse.data.error);
                        AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                    }
                }, function(error) {
                    console.log(error);
                    AlertSvc.warning($scope, error);
                });
                
            }, function(error) {
                deferred.reject(error);
                console.log(error);
            });
            // .then(function(stuData){
            //     console.log(stuData);
            // });
            
        });
        
    };

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function(evt, data) {

    });

    angular.element(document.querySelector('#wizard')).on('finished.fu.wizard', function(evt, data) {
        // evt.preventDefault();
        // console.log('angular.element COMPLETE');
        $scope.postForm();
    });

    angular.element(document.querySelector('#wizard')).on('actionclicked.fu.wizard', function(evt, data) {
        // evt.preventDefault();
        // console.log('angular.element ACTIONCLICKED');
        AlertSvc.reset($scope);
        // console.log(angular.copy($scope.postResponse));

        if (angular.isDefined($scope.postResponse)){
            delete $scope.postResponse;
            $scope.$apply();
        }

    });
}
