angular.module('institution.result.toolbar.controller', ['institution.result.service'])
.controller('ToolbarCtrl', function($scope, ResultSvc) {
    angular.element(document).ready(function () {
        // default action is view
        $scope.$parent.action = 'view';
    });

    $scope.onEditClick = function() {
        $scope.$parent.action = 'edit';
    };

    $scope.onBackClick = function() {
        $scope.$parent.action = 'view';
    };

    $scope.onSaveClick = function() {
        $scope.$parent.action = 'view';
        // ResultSvc.saveRowData($scope);
    };
});
