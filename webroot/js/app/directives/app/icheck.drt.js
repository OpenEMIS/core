angular.module('ICheck', [])
    .directive('icheck', ['$timeout', '$parse', function($timeout, $parse) {
        return {
            require: 'ngModel',
            link: function($scope, element, $attrs, ngModel) {
                return $timeout(function() {
                    var value;
                    value = $attrs['value'];

                    if ($attrs['name'] != null) {
                        element.addClass($attrs['name']);
                    } else {
                        // console.warn('["Name"] atrribute is missing.');
                        // console.warn(element);
                    }

                    // $scope.$watch($attrs['ngModel'], function(_newValue) {
                    //     element.iCheck('update');
                    // });

                    // $scope.$watch($attrs['ngDisabled'], function(_newValue) {
                    //     element.iCheck(_newValue ? 'disable' : 'enable');
                    //     element.iCheck('update');
                    // }); 

                    $scope.$watchGroup([$attrs['ngModel'], $attrs['ngDisabled']], function(_newValue, oldValues) {
                        // newValues array contains the current values of the watch expressions
                        // with the indexes matching those of the watchExpression array
                        // i.e.
                        // newValues[0] -> $scope.foo 
                        // and 
                        // newValues[1] -> $scope.bar 

                        // if(_newValue){
                        element.iCheck(_newValue[1] ? 'disable' : 'enable');
                        element.iCheck('update');
                    });

                    return element.iCheck({
                        checkboxClass: 'icheckbox_minimal-grey',
                        radioClass: 'iradio_minimal-grey'

                    }).on('ifChanged', function(event) {
                        if (element.attr('type') === 'checkbox' && $attrs['ngModel']) {
                            if (element.attr('value') === 'all') {
                                if (event.target.checked) {
                                    angular.element('.' + element.attr('name')).iCheck('check');
                                } else {
                                    angular.element('.' + element.attr('name')).iCheck('uncheck');
                                }
                                angular.element('.' + element.attr('name')).iCheck('update');
                            }

                            $scope.$apply(function() {
                                return ngModel.$setViewValue(event.target.checked);
                            })
                        }
                    }).on('ifClicked', function(event) {
                        if (element.attr('type') === 'radio' && $attrs['ngModel']) {
                            return $scope.$apply(function() {
                                //set up for radio buttons to be de-selectable
                                /*if (ngModel.$viewValue != value)*/
                                return ngModel.$setViewValue(value);
                                /*else
                                    ngModel.$setViewValue(null);
                                ngModel.$render();
                                return*/
                            });
                        }
                    });
                });
            }
        };
    }])
