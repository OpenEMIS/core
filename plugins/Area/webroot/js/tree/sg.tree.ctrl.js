angular.module('sg.tree.ctrl', ['kd-angular-tree-dropdown', 'sg.tree.svc'])
    .controller('SgTreeCtrl', SgTreeController);

SgTreeController.$inject = ['$scope', '$window', 'SgTreeSvc'];

function SgTreeController($scope, $window, SgTreeSvc) {

    $scope.outputFlag = false;
    var Controller = this;

    $scope.outputModelText = [];
    Controller.outputValue = null;
    Controller.displayCountry = 0;
    Controller.loaded = false;
    Controller.triggerLoad = triggerLoad;
    Controller.triggerOnChange = false;
    $scope.textConfig = {
        multipleSelection: '%tree_no_of_item items selected'
    };

    /** userId from ng-init can be missing until bind (2nd SgTree on same page) or already a number — avoid JSON.parse(undefined) */
    function resolveUserId(ctrl) {
        var v = ctrl.userId;
        if (v === undefined || v === null || v === '') {
            return 2;
        }
        if (typeof v === 'number') {
            return v;
        }
        try {
            return JSON.parse(String(v));
        } catch (e) {
            var n = parseInt(String(v), 10);
            return isNaN(n) ? 2 : n;
        }
    }

    angular.element(document).ready(function () {
        SgTreeSvc.init(angular.baseUrl);
        var userId = resolveUserId(Controller);
        var authArea = [];
        var counter = 0;
        SgTreeSvc.getRecords(Controller.model ? Controller.model : 'Area.AreaAdministratives', userId, Controller.displayCountry, Controller.outputValue, true)
            .then(function(response) {
                if (angular.isDefined(response[1]) && angular.isDefined(response[1].name)) {
                    $scope.textConfig['noSelection'] = response[1].name;
                }
                return SgTreeSvc.translate($scope.textConfig);
            }, function(error){
                console.log(error)
            })
            .then(function(res) {
                $scope.textConfig = res;
                // console.log('document ready res', res);
            }, function (error) {
                console.log(error);
            });
        // console.log('document ready');
    });

    function triggerLoad(refreshList) {
        // run ajax call to get parentData. Then pass it to refreshList(_response) callback function.
        // eg: assign parentData to _pData.
        if (!Controller.loaded) {
            Controller.loaded = true;
            var userId = resolveUserId(Controller);
            SgTreeSvc.getRecords(Controller.model, userId, Controller.displayCountry, Controller.outputValue)
            .then(function(response) {
                console.log("triggerLoad");
                console.log(response);
                refreshList(response);
                // return SgTreeSvc.translate($scope.textConfig);
            }, function(error){
                console.log(error)
            });
        }
    }

     $scope.$watch('outputModelText', function (newValue, oldValue) {
        if (typeof newValue !== 'undefined' && newValue.length > 0) {
            Controller.outputValue = newValue[0].id;
            if (Controller.triggerOnChange) {
                setTimeout(function() {
                    if (oldValue.length != 0 && Controller.outputValue != null && Controller.outputValue != oldValue[0].id) {
                        $('#reload').val('changeAreaEducation').click();
                        return false;
                    }
                }, 1);
            }
        }
    });

    $scope.$watch('addressAreaOutputModelText', function (newValue, oldValue) {
        if (typeof newValue !== 'undefined' && newValue.length > 0) {
            Controller.outputValue = newValue[0].id;
            if($window.localStorage.getItem('address_area_id')) {
                $window.localStorage.removeItem('address_area_id')
            }
            if($window.localStorage.getItem('address_area')) {
                $window.localStorage.removeItem('address_area')
            }
            $window.localStorage.setItem('address_area_id', Controller.outputValue);
            $window.localStorage.setItem('address_area', JSON.stringify(newValue[0]));
            if (Controller.triggerOnChange) {
                setTimeout(function() {
                    if (oldValue.length != 0 && Controller.outputValue != null && Controller.outputValue != oldValue[0].id) {
                        $('#reload').val('changeAreaEducation').click();
                        return false;
                    }
                }, 1);
            }
        }
    });

    $scope.$watch('birthplaceAreaOutputModelText', function (newValue, oldValue) {
        if (typeof newValue !== 'undefined' && newValue.length > 0) {
            Controller.outputValue = newValue[0].id;
            if($window.localStorage.getItem('birthplace_area_id')) {
                $window.localStorage.removeItem('birthplace_area_id')
            }
            if($window.localStorage.getItem('birthplace_area')) {
                $window.localStorage.removeItem('birthplace_area')
            }
            $window.localStorage.setItem('birthplace_area_id', Controller.outputValue);
            $window.localStorage.setItem('birthplace_area', JSON.stringify(newValue[0]));
            if (Controller.triggerOnChange) {
                setTimeout(function() {
                    if (oldValue.length != 0 && Controller.outputValue != null && Controller.outputValue != oldValue[0].id) {
                        $('#reload').val('changeAreaEducation').click();
                        return false;
                    }
                }, 1);
            }
        }
    });
}
