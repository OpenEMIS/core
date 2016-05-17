angular
    .module('relevancy.rules.ctrl', ['utils.svc', 'alert.svc', 'relevancy.rules.svc'])
    .controller('RelevancyRulesCtrl', RelevancyRulesController);

RelevancyRulesController.$inject = ['$scope', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'RelevancyRulesSvc'];



function RelevancyRulesController($scope, $filter, $q, UtilsSvc, AlertSvc, RelevancyRulesSvc) {
    
    var vm = this;
    var filterValue = '';
    // var surveyFormId = UtilsSvc.requestQuery('survey_form_id');
    
    // Initialisation
    angular.element(document).ready(function() 
    {
    });

    vm.printSomething = function () {
        
    }

    vm.showDropdown = function(dependentQuestionId, showOptions) {
        console.log(showOptions);
        console.log(dependentQuestionId);
    }
}