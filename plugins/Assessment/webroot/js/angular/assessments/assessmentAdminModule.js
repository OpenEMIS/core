angular.module('assessmentAdminModule', ['kd.common.svc'])
    .controller('assessmentAdminCtrl', function(kdCommonSvc, $scope, $filter) {

        kdCommonSvc.initController($scope);

        $scope.onChangeTargetsCallback = function (target) {
            if (target == 'assessment_items') {
                var attr = {
                    'kdOnChangeElement': true,
                    'kdOnChangeSourceUrl': $scope.baseUrl + '/restful/Assessment-AssessmentGradingTypes.json?_finder=visible,list',
                    'kdOnChangeTarget': 'assessment_grading_type_id'
                }
                kdCommonSvc.changeOptions($scope, '', attr);
            }
        };

        $scope.onClickTargetsCallback = function (target) {
            if (target == 'assessment_periods') {
                var assessmentAcademicPeriod = angular.element('[assessment-academic-period="1"]');
                var id = assessmentAcademicPeriod.val();
                if (id!='') {
                    var detailsUrl = (assessmentAcademicPeriod.attr('assessment-academic-period-details-url')).replace("{%id%}", id);
                    var response = kdCommonSvc.ajax({url:detailsUrl});
                    response  
                        .then(function(data) {

                            var start_date = $scope.showDateValue(data.data.start_date);
                            var end_date = $scope.showDateValue(data.data.end_date);

                            angular.forEach(angular.datepickers, function(element, key) {
                                if ( (element[0].id).toLowerCase().indexOf("-start_date") >= 0 || (element[0].id).toLowerCase().indexOf("-date_enabled") >= 0 ) {
                                    if (element[0].firstElementChild.value=='') {
                                        element.datepicker('setDate', start_date);
                                    }
                                } else if ( (element[0].id).toLowerCase().indexOf("-end_date") >= 0 || (element[0].id).toLowerCase().indexOf("-date_disabled") >= 0 ) {
                                    if (element[0].firstElementChild.value=='') {
                                        element.datepicker('setDate', end_date);
                                    }
                                }
                            });

                        }, function(error) {
                            console.log('Error: ', error);
                        });
                }
            }
        };

        $scope.showDateValue = function (value) {
            var val = typeof value === "object" ? "" : $filter('date')(new Date(value), 'dd-MM-yyyy');
            // should be  element.datepicker('setDate', val); here to "register" the date value to datepicker instance.
            // however couldn't think of a way to pass the element object from the ctp file.
            return val;
        };

        $scope.changeAcademicPeriod = function (id, attr) {

            kdCommonSvc.appendSpinner('table_assessment_periods');
            if (id!='') {
                var detailsUrl = (attr.assessmentAcademicPeriodDetailsUrl).replace("{%id%}", id);
                var response = kdCommonSvc.ajax({url:detailsUrl});
                response  
                    .then(function(data) {

                        var start_date = $scope.showDateValue(data.data.start_date);
                        var end_date = $scope.showDateValue(data.data.end_date);

                        angular.forEach(angular.datepickers, function(element, key) {
                            if ( (element[0].id).toLowerCase().indexOf("-start_date") >= 0 || (element[0].id).toLowerCase().indexOf("-date_enabled") >= 0 ) {
                                element.datepicker('setDate', start_date);
                            } else if ( (element[0].id).toLowerCase().indexOf("-end_date") >= 0 || (element[0].id).toLowerCase().indexOf("-date_disabled") >= 0 ) {
                                element.datepicker('setDate', end_date);
                            }
                        });

                        kdCommonSvc.removeSpinner('table_assessment_periods');

                    }, function(error) {
                        console.log('Error: ', error);
                    });
            } else {

                angular.forEach(angular.datepickers, function(element, key) {
                    element.datepicker('clearDates');
                });

                kdCommonSvc.removeSpinner('table_assessment_periods');
            }

        }

    })
    .directive('assessmentAcademicPeriod', function(kdCommonSvc) {
        return {
            restrict: 'A',
            link: function(scope, elem, attr) {

                elem.on('change', function(event) {
                    scope.changeAcademicPeriod(elem.val(), attr);
                });

            }
        };
    })
    ;
