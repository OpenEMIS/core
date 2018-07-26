//Advanced Search v.1.0.1
(function() {
    'use strict';

    angular.module('advanced.search.ctrl', [])
        .controller('AdvancedSearchCtrl', ['$scope', '$location', function($scope, $location) {

            //Advanced Search
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

            $scope.submitSearch = function() {
                $scope.showAdvSearch = false;
                $scope.searchResultsHeader = true;
                $scope.selectedState = '';
                $scope.disableElement = '';
            }

        }]);
})();
