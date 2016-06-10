angular
    .module('relevancy.rules.ctrl', [])
    .controller('RelevancyRulesCtrl', RelevancyRulesController);

RelevancyRulesController.$inject = ['$filter', '$q'];

function RelevancyRulesController($filter, $q) {    
    var vm = this;
    vm.showDropdown = showDropdown;

    function showDropdown(dependentQuestionId, showOptions) {
        var optionSelected = parseInt(vm.Dropdown[dependentQuestionId]);
        var showOption = false;
        if (showOptions.indexOf(optionSelected) != -1) {
            showOption = true;
        }
        return showOption;
    }
}