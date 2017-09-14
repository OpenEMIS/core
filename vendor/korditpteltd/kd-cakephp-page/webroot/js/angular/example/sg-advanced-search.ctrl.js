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

    }]);
