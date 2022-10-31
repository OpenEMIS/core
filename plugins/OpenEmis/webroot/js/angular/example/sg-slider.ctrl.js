//Slider v.1.0.0
(function (){
    'use strict';

angular.module('OE_Styleguide')
    .controller('SgSliderCtrl', ['$scope', function($scope, $window) {

        $scope.sliderData = {
            autoplay: {
                config: {
                    autoplay: true,
                    autoplayStep: 10,
                },
                value: 10,
                id: 'autoplay',
                api: {},
                label: 'with Autoplay',
            },
            horizontal: {
                config: {
                    orientation: 'horizontal',
                    max: 10,
                    min: 0,
                    step: 0.5
                },
                value: 0.5,
                id: 'horizontal',
                api: {},
                label: 'Horizontal',
            },
            range: {
                config: {
                    range: true,
                },
                value: [10, 40],
                id: 'range',
                api: {},
                label: 'with Range',
            },
            nonnumber: {
                config: {
                    type: 'others',
                    options: [{
                        description: 'a really large number 100,000,000',
                        value: 100000000
                    }, {
                        value: 2018,
                    }, {
                        value: 'there is no description for this item'
                    }, {
                        description: 'test text how long testing text length testing text length testing text length testing text length testing text length',
                        value: 'this value is a string'
                    }]
                },
                value: {
                    value: 2018,
                },
                id: 'nonnumber',
                api: {},
                label: 'with Nonnumber',
            }
        }

        $scope.valueUpdate = function(_value, _id) {
            let displayValue = setDisplayFormat(_value, _id);
            console.log('slider: '+_id + ' current value is ' + displayValue);
        };

        $scope.setValue = function () {
            $scope.sliderData.horizontal.api.setSliderThumbValue(10);
        };

        $scope.getCurrentValue = function () {
            console.log($scope.sliderData.horizontal.api.getSliderThumbValue());
        };

        $scope.disableAutoplay = function () {
            $scope.sliderData.autoplay.api.disable(true);
        };

        function setDisplayFormat(_value, _id) {
            if (typeof _value === 'number') {
                return _value;
            } else if (Array.isArray(_value)) {
                return _value[0] + '-' + _value[1];
            } else {
                return _value.value;
            }
        };
        
    }]);
})();