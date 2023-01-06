//Tree Dropdown v.1.0.1
(function (){
    'use strict';

angular.module('OE_Styleguide')
    .controller('SgTreeCtrl', ['$scope', function($scope, $window) {

        $scope.outputFlag = false;

        // $scope.testFunction = function(_value) {
        //     console.log(_value);
        // }

        // $scope.$watch('outputModel', function(_newValue, _oldValue) {
        //     console.log("outputModel Change!", _newValue);
        // })
        // $scope.$watch('outputModel2', function(_newValue, _oldValue) {
        //     console.log("outputModel2 Change!", _newValue);
        // })


        $scope.expandChild = function(parentData, getChildData) {
            var _child; 
            setTimeout(function(){
                if (typeof parentData !== "undefined") {
                    if (parentData.id == 4) {
                        _child = $scope.inputModelText3Child;
                    } else if (parentData.id == 12) {
                        _child = $scope.inputModelText3Child2;
                    }else if (parentData.id == 21) {
                        _child = $scope.inputModelText3Child3;
                    } else if (parentData.id == 7) {
                        _child = $scope.inputModelText4Child;
                    } else if (parentData.id == 14) {
                        _child = $scope.inputModelText4Child2;
                    } else if (parentData.id == 23) {
                        _child = $scope.inputModelText4Child3;
                    } else if (parentData.id == 5) {
                        _child = $scope.inputModelText4Child4;
                    } else if (parentData.id == 111) {
                        _child = $scope.inputModelText_Single2_child;
                    } else {
                        _child = $scope.inputModelText4Child3;
                    }
                    //requires parentData_id in case user click expand on multiple items
                    getChildData(parentData,_child);
                } 
            }, 3000);
        };

        $scope.expandParent = function(refreshList) {
            setTimeout(function(){
                var inputModelText = $scope.inputModelText_using;
                refreshList(inputModelText);
            }, 3000);
        }

        $scope.expandParentSingle = function(refreshList) {
            setTimeout(function(){
                var inputModelText = $scope.inputModelText_Single2;
                refreshList(inputModelText);
            }, 3000);
        };


        $scope.inputModelText_using = [{
                id: 1,
                name: 'testCtrl with long text and see overflow to have problem or not and again to see overflow to have problem again and again and again',
                children: [
                    { id: 11, selected: true, name: 'test11 child with long text also' }
                ]
            },
            {
                id: 2,
                name: 'test2',
                disable: true,
                children: [
                    { id: 21, disable: false, name: 'test21' },
                    {
                        id: 22,
                        disable: true,
                        name: 'test22',
                        children: [
                            { id: 221, selected:true, name: 'test221' }
                        ]
                    }
                ]
            },
            {
                id: 3,
                name: 'test3',
                disable: true,
                children: [
                    { id: 31, name: 'test31' },
                    {
                        id: 32,
                        disable: true,
                        name: 'test32',
                        children: [
                            { id: 321, name: 'test321' },
                            { id: 322, name: 'test322' },
                            {
                                id: 323,
                                disable: true,
                                name: 'test323',
                                children: [
                                    { id: 3231, selected:true, disable: true, name: 'test3231' },
                                    { id: 3232, disable: true, name: 'test3232' },
                                    { id: 3233, name: 'test3233' }
                                ]
                            }
                        ]
                    }
                ]
            },
            {
                id: 4,
                name: 'test4',
                children: [
                    { id: 41, selected:true, disable: true, name: 'test41' },
                    { id: 42, name: 'test42' }
                ]
            },
            { id: 9, name: 'test5' },
        ];

        $scope.inputModelText2 = [{
                id: 1,
                name: 'testCtrl with long text and see overflow to have problem or not and again to see overflow to have problem again and again and again',
                children: [
                    { id: 11, name: 'test11 child with long text also' }
                ]
            },
            {
                id: 2,
                name: 'test2',
                disable: true,
                children: [
                    { id: 21, name: 'test21' },
                    {
                        id: 22,
                        name: 'test22',
                        children: [
                            { id: 221, name: 'test221' }
                        ]
                    }
                ]
            },
            {
                id: 3,
                name: 'test3',
                children: [
                    { id: 31, name: 'test31' },
                    {
                        id: 32,
                        name: 'test32',
                        children: [
                            { id: 321, name: 'test321' },
                            { id: 322, name: 'test322' },
                            {
                                id: 323,
                                name: 'test323',
                                children: [
                                    { id: 3231, name: 'test3231' },
                                    { id: 3232, name: 'test3232' },
                                    { id: 3233, selected: true, name: 'test3233' }
                                ]
                            }
                        ]
                    }
                ]
            },
            {
                id: 4,
                name: 'test4',
                children: [
                    { id: 41, name: 'test41' },
                    { id: 42, name: 'test42' }
                ]
            },
            { id: 9, name: 'test5' }
        ];

        $scope.inputModelText3 = [{
                id: 1,
                name: 'testCtrl with long text and see overflow to have problem or not and again to see overflow to have problem again and again and again'
            , children: 3},
            { id: 2, name: 'children 0', disable: true, children: 0 },
            { id: 4, selected: true, name: 'children = 2', children: 2 },
            { id: 9, name: 'children {count : 0}', children: {count : 0} }
        ];

        $scope.inputModelText3Child = [
            { id: 11, selected: true, name: 'child1' },
            { id: 12, name: 'child2', children: 2 }
        ];

        $scope.inputModelText3Child2 = [
            { id: 21, name: 'child21', children: 2 },
            { id: 22, name: 'child22', selected:true }
        ];

        $scope.inputModelText3Child3 = [
            { id: 31, name: 'child31' },
            { id: 32, name: 'child32' }
        ];

        $scope.inputModelText3Child4 = [
            { id: 41, name: 'child41', selected:true },
            { id: 42, name: 'child42' }
        ];



        $scope.inputModelText4 = [{
                id: 5,
                name: 'parent1'
            , children: 3},
            { id: 6, name: 'parent2', disable: true, children: 0 },
            { id: 7, selected: true, name: 'parent3', children: 2 },
            { id: 8, name: 'parent4', children: {count : 0} }
        ];

        $scope.inputModelText4Child = [
            { id: 13, selected: true, name: 'child3' },
            { id: 14, name: 'child4', children: 2 }
        ];

        $scope.inputModelText4Child2 = [
            { id: 23, name: 'child23', children: 2 },
            { id: 24, name: 'child24', selected:true }
        ];

        $scope.inputModelText4Child3 = [
            { id: 33, name: 'child33' },
            { id: 34, name: 'child34' }
        ];

        $scope.inputModelText4Child4 = [
            { id: 43, name: 'child43', selected:true },
            { id: 44, name: 'child44' }
        ];

        $scope.inputModelText_Single2 = [{
                id: 111,
                name: 'single parent1'
            , children: 3},
            { id: 112, name: 'single parent2', disable: true, children: 0 },
            { id: 113, name: 'single parent3', children: 2 },
            { id: 114, name: 'single parent4', children: {count : 0} }
        ];

        $scope.inputModelText_Single2_child = [
            { id: 1111, name: 'single child1111', selected:true },
            { id: 1112, name: 'single child1112' }
        ];

    }]);


})();