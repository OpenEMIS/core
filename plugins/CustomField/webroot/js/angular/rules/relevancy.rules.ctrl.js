angular
    .module('relevancy.rules.ctrl', [])
    .controller('RelevancyRulesCtrl', RelevancyRulesController);

RelevancyRulesController.$inject = ['$filter', '$q'];



function RelevancyRulesController($filter, $q) {
    
    var vm = this;

    vm.showDropdown = function(dependentQuestionId, showOptions) {
        var optionSelected = parseInt(vm.Dropdown[dependentQuestionId]);
        if (showOptions.indexOf(optionSelected) != -1) {
            return true;
        } else {
            return false;
        }
    }
}