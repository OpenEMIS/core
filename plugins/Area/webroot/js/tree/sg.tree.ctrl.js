angular.module('sg.tree.ctrl', ['kd-angular-tree-dropdown', 'sg.tree.svc'])
    .controller('SgTreeCtrl', SgTreeController);

SgTreeController.$inject = ['$scope', '$window', 'SgTreeSvc'];

function SgTreeController($scope, $window, SgTreeSvc) {

    $scope.outputFlag = false;
    var Controller = this;

    Controller.inputModelText = [
        {id: 1, name: 'testCtrl with long text and see overflow to have problem or not and again', children: [
            {id: 11, name: 'test11 child with long text also to see overflow to have problem again and again and again'}
        ]},
        {id: 2, name: 'test2', children: [
            {id: 21, name: 'test21'},
            {id: 22, name: 'test22', children: [
                {id: 221, name: 'test221'}
            ]}
        ]},
        {id: 3, name: 'test3', children: [
            {id: 31, name: 'test31'},
            {id: 32, name: 'test32', children: [
                {id: 321, name: 'test321'},
                {id: 322, name: 'test322'},
                {id: 323, name: 'test323', children: [
                    {id: 3231, name: 'test3231'},
                    {id: 3232, name: 'test3232'},
                    {id: 3233, name: 'test3233'}
                ]}
            ]}
        ]},
        {id: 4, name: 'test4', children: [
            {id: 41, name: 'test41'},
            {id: 42, name: 'test42'}
        ]},
        {id: 5, name: 'test5'}
    ];
    $scope.outputModelText = [];
    Controller.outputValue = null;

    angular.element(document).ready(function () {
        SgTreeSvc.init(angular.baseUrl);
        if (Controller.outputValue != null) {
            // $scope.outputModelText.push(Controller.outputValue);
        }
        SgTreeSvc.getRecords(model)
        .then(function(response) {

        }, function(error){

        });


    });

    $scope.$watch('outputModelText', function (newValue) {
        if (typeof newValue !== 'undefined' && newValue.length > 0) {
            Controller.outputValue = newValue[0].id;
        }
    });
}