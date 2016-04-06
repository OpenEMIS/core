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
        ResultSvc.isAppendSpinner(true, 'institution-result-table');
        ResultSvc.saveRowData($scope).then(function(_results) {
        }, function(_errors) {
        }).finally(function() {
            ResultSvc.isAppendSpinner(false, 'institution-result-table');
            $scope.$parent.action = 'view';
        });
    };
});
