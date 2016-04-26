angular
    .module('survey.rules.ctrl', ['utils.svc', 'alert.svc', 'survey.rules.svc'])
    .controller('SurveyRulesCtrl', SurveyRulesController);

SurveyRulesController.$inject = ['$scope', '$filter', 'UtilsSvc', 'AlertSvc', 'SurveyRulesSvc'];

function SurveyRulesController($scope, $filter, UtilsSvc, AlertSvc, SurveyRulesSvc) {
    
    var vm = this;
    $scope.action = 'index';
    var filterValue = '';
    
    // Initialisation
    angular.element(document).ready(function() 
    {
        SurveyRulesSvc.init(angular.baseUrl);
        SurveyRulesSvc.getSurveyForm()
        .then(function(response) 
        {
            var formData = response.data;
            var options = [];
            for(i = 0; i < formData.length; i++) 
            {   
                options.push({text: formData[i].name.toString(), value: formData[i].id});
            }
            vm.surveyFormOptions = options;
            vm.filters = vm.surveyFormOptions[0].value;
        }, function(error) 
        {
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        ;
    });

    // Updating of the filter for the survey form
    vm.update = function (selectedItem) 
    {
        filterValue = selectedItem;
        console.log(filterValue);
    }

    // busy waiting to watch the action of the page
    $scope.$watch('action', function(newValue, oldValue) {
        console.log('here');
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
        }
    });

    vm.onAddClick = function() {
        $scope.action = 'add';
    };
}