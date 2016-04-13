angular.module('assessmentAdminModule', ['kd.common.svc'])
    .controller('assessmentAdminCtrl', function(kdCommonSvc, $scope) {

        kdCommonSvc.initController($scope);

        $scope.onChangeTargetsCallback = function (target) {
            if (target == 'assessment_items') {
                var attr = {
                    'kdOnChangeElement': true,
                    'kdOnChangeSourceUrl': $scope.baseUrl + '/restful/assessment-assessmentgradingtypes.json?_finder=visible,list',
                    'kdOnChangeTarget': 'assessment_grading_type_id'
                }
                kdCommonSvc.changeOptions($scope, '', attr);
            }
        };

        // var form = angular.element("form[action='" + $scope.baseUrl + "/Assessments/Assessments/add']");
        // var buttons = form.find('button[type="submit"]');
        // pr(buttons.attr('type'));
        // buttons.attr('type', 'reset');
        // pr(buttons.attr('type'));
        // button[0].attr
    })
    ;
