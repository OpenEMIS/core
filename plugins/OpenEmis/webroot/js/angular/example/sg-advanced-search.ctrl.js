//Advanced Search v.1.0.0

angular.module('OE_Styleguide')
    // .controller('SgAdvancedSearchCtrl', function($scope) {
    .controller('SgAdvancedSearchCtrl', ['$scope', '$location', function($scope, $location) {

        $scope.searchResultsHeader = false;
        $scope.selectedState = '';
        $scope.disableElement = '';

        //Show or Hide the Advanced Search Component
        $scope.toggleAdvancedSearch = function() {
            $scope.showAdvSearch = !$scope.showAdvSearch;

            if ($scope.showAdvSearch) {
                $scope.selectedState = 'btn-toggled';
                $scope.disableElement = 'disable-element';
            } else {
                $scope.selectedState = '';
                $scope.disableElement = '';
            }
        }

        //Remove the Advanced Search Component
        $scope.removeAdvSearch = function() {
            $scope.showAdvSearch = false;
            $scope.selectedState = '';
            $scope.disableElement = '';
        }

        //Reset the values on the Advanced Search
        $scope.resetFields = function() {
            var searchForm = angular.element('#search-form');
            var resetCheckbox = angular.element('input[type="checkbox"]');  

            $scope.inputModelText = angular.copy(JSON.parse(JSON.stringify($scope.originalModel)));
            searchForm.find('input:text, select').val('');
            resetCheckbox.removeAttr('checked');
        }

        $scope.submitSearch = function() {
            $scope.showAdvSearch = false;
            $scope.searchResultsHeader = true;
            $scope.selectedState = '';
            $scope.disableElement = '';
        }

        $scope.clearSearch = function() {
            window.location.reload();
        }

        $scope.outputFlag = false;
        $scope.originalModel = 
        [
            {id: 1, disabled: true, name: 'Option 1 with super long text again for testing ellipsis again and again', children: [
                {id: 11, disabled: true, name: 'Option 11 children with super long text'}
            ]},
            {id: 2, disabled: true, name: 'Option 2', children: [
                {id: 21, disabled: true, name: 'Option 21'},
                {id: 22, disabled: true, name: 'Option 22', children: [
                    {id: 221, selected: true, name: 'Option 221'}
                ]}
            ]},
            {id: 3, name: 'Option 3', children: [
                {id: 31, name: 'Option 31'},
                {id: 32, name: 'Option 32', children: [
                    {id: 321, name: 'Option 321'},
                    {id: 322, name: 'Option 322'},
                    {id: 323, name: 'Option 323', children: [
                        {id: 3231, name: 'Option 3231'},
                        {id: 3232, name: 'Option 3232'},
                        {id: 3233, name: 'Option 3233'}
                    ]}
                ]}
            ]},
            {id: 4, name: 'Option 4', children: [
                {id: 41, name: 'Option 41'},
                {id: 42, name: 'Option 42'}
            ]},
            {id: 5, name: 'Option 5'}
        ];
        $scope.inputModelText = angular.copy(JSON.parse(JSON.stringify($scope.originalModel)));

    }]);
