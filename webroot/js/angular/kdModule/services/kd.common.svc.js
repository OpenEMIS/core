angular.module('kd.common.svc', [])
.service('kdCommonSvc', ['$http', '$q', function ($http, $q) {
 
    this.baseUrl = '/';
    this.ctrl = 'chak';
    // this.storage.options = {
    //     element1: {}
    // };

    this.init = function(scope, ctrlFunctions) {
        // var defaultFunctions = ['changeOptions', 'func1', 'func2'];
        // ctrlFunctions = ctrlFunctions || defaultFunctions;
        // for (func in ctrlFunctions) {
            scope.changeOptions = this.changeOptions(scope);  
        // }
    }

    this.changeOptions = function(id, attr) {
        // caCommonSvc.changeOptions($scope);
        // var dataType = attr.caOnChangeElement;
        // var target = attr.caOnChangeTarget;
        // var targetUrl = attr.caOnChangeSourceUrl + id;
        // var response = caCommonSvc.ajax({url:targetUrl});
        // response  
        //     .then(function(data) {

        //         targetOptions = [];
        //         if (dataType=='data') {
        //             targetOptions = data.data;
        //         } else {
        //             for (var id in data.data) {
        //                 targetOptions.push({"id":id, "name":data.data[id]});
        //             }
        //         }
        //         $scope.onChangeTargets[target] = targetOptions;
                
        //     }, function(error) {
        //         console.log('Failure...', error);
        //     });
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

    this.ajax = function (params) {
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

}]);



// CA_Controller.js
// .inject(CA_Svc);
// .init() { CA_Svc.init(this); }
// // .add() { CA_Svc.add(); }
// // .edit() { CA_Svc.edit(); }


// CA_Svc.js
// .add() {}
// .edit() {}
// .onchange() {}
// .init(ControllerObject) {
//     ControllerObject.add = function() {
//         CA_Svc.add();
//     }
// }



// Custom_Controller.js
// .inject(CA_Svc)
// .init() { CA_Svc.init(this); }
