angular.module('kd.common.svc', [])
.service('kdCommonSvc', ['$http', '$q', function ($http, $q) {
 
    this.initController = function(scope) {
        var removeRow = function(kdId, index) {
            scope.onClickTargets.handlers.addRow[kdId].splice(index, 1);
        };
        scope['onChangeTargets'] = {};
        scope['onClickTargets'] = {
            'handlers': {
                'addRow': {},
                'removeRow': removeRow,
                'alert': {}
            },
        };
        scope.baseUrl = angular.baseUrl;
        scope.selectedOption = function(elementId, optionId) {
            var element = angular.element('#'+elementId);
            return (element.attr('kd-selected-value')==optionId) ? true : false;
        };
        scope.showValue = function (value) {
            return typeof value === "object" ? "" : value;
        };
    }

    var self = this;
    this.changeOptions = function(scope, id, attr) {
        var spinnerParent = attr.kdOnChangeSpinnerParent || 0;
        self.appendSpinner(spinnerParent);

        var target = attr.kdOnChangeTarget;
        var dataType = attr.kdOnChangeElement;
        var targetUrl = attr.kdOnChangeSourceUrl + id;
        var response = self.ajax({url:targetUrl});
        response  
            .then(function(data) {

                targetOptions = [];
                if (dataType=='data') {
                    targetOptions = data.data;
                } else {
                    for (var id in data.data) {
                        targetOptions.push({"id":id, "name":data.data[id]});
                    }
                }
                scope.onChangeTargets[target] = targetOptions;
                if (typeof scope.onChangeTargetsCallback === 'function') {
                    scope.onChangeTargetsCallback(target);
                }

                self.removeSpinner(spinnerParent);
            
            }, function(error) {
                console.log('Error: ', error);
            });
    };

    this.addRow = function(scope, elem, attr) {
        var spinnerParent = attr.kdOnClickSpinnerParent || 0;
        self.appendSpinner(spinnerParent);

        var target = attr.kdOnClickTarget;
        var targetUrl = attr.kdOnClickSourceUrl;
        var response = self.ajax({url:targetUrl});
        response  
            .then(function(data) {
                if (typeof scope.onClickTargets.handlers.addRow[target] !== 'undefined' && scope.onClickTargets.handlers.addRow[target] !== null) {
                    scope.onClickTargets.handlers.addRow[target].push(data.data);
                } else {
                    scope.onClickTargets.handlers.addRow[target] = [data.data];
                }
                if (typeof scope.onClickTargetsCallback === 'function') {
                    scope.onClickTargetsCallback(target, 'addRow');
                }

                self.removeSpinner(spinnerParent);
            
            }, function(error) {
                console.log('Failure...', error);
            });
    };

    this.htmlEntities = function(str) {
        return String(str).replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /**
     * convert object to URI component for AngularJS POST requests
     * @source http://stackoverflow.com/questions/19254029/angularjs-http-post-does-not-send-data
     */
    this.serialize = function (obj) {
        var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

        for(name in obj) {
            value = obj[name];

            if(value instanceof Array) {
                for(i=0; i<value.length; ++i) {
                    subValue = value[i];
                    fullSubName = name + '[' + i + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += serialize(innerObj) + '&';
                }
            }
            else if(value instanceof Object) {
                for(subName in value) {
                    subValue = value[subName];
                    fullSubName = name + '[' + subName + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += serialize(innerObj) + '&';
                }
            }
            else if(value !== undefined && value !== null)
                query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
        }

        return query.length ? query.substr(0, query.length - 1) : query;
    };

    this.ajax = function (params, success, error) {
        $http.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
        var deferred = $q.defer();
        var defaultParams = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        };
        var mergedParams = angular.merge(defaultParams, params);
        $http(mergedParams).then(
            function successCallback(_response) {
                deferred.resolve(_response.data);
            }, function errorCallback(_error) {
                deferred.reject(_error);
            }, function progressCallback(_response) {
            }
        );
        return deferred.promise;
    }

    this.appendSpinner = function(_querySelector) {
        _querySelector = _querySelector || 'content-main-form';
        var spinnerId = _querySelector + '-spinner';
        var hasClass = angular.element(document.getElementById(spinnerId)).hasClass('spinner-wrapper');
        if (!hasClass) {
            var spinnerElement = angular.element('<div id="'+ spinnerId +'" ' + 'class="spinner-wrapper"><div class="spinner-text"><div class="spinner lt-ie9"></div></div></div>');
            angular.element(document.getElementById(_querySelector)).prepend(spinnerElement);
        }
    }

    this.removeSpinner = function(_querySelector) {
        _querySelector = _querySelector || 'content-main-form';
        var spinnerId = _querySelector + '-spinner';
        angular.element(document.getElementById(spinnerId)).remove('.spinner-wrapper');
    }

}]);
