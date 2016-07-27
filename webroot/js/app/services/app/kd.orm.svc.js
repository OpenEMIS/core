angular.module('kd.orm.svc', [])
.service('KdOrmSvc', function($q, $http) {
    var query = {
        responseType: 'json',
        _base: '',
        _controller: 'restful',
        _className: '', // model classname
        _id: 0, // model primary key
        _select: [],
        _method: 'GET', // GET/POST/PUT/DELETE
        _contain: [],
        _finder: [],
        _where: {},
        _orWhere: [],
        _group: [],
        _order: [],
        _limit: 0,
        _page: 0,

        className: function(className) {
            this._className = className;
            return this;
        },

        controller: function(controller) {
            this._controller = controller;
            return this;
        },

        reset: function() {
            this._id = 0;
            this._select = [];
            this._contain = [];
            this._finder = [];
            this._where = {};
            this._orWhere = [];
            this._limit = 0;
            this._group = [];
            this._order = [];
            this._page = 0;
            this._method = 'GET';
        },

        select: function(fields) {
            this.reset();
            if (fields != undefined) {
                this._select = fields;
            }
            return this;
        },

        get: function(id) {
            this.reset();
            this._id = id;
            return this;
        },

        contain: function(contain) {
            this._contain = contain;
            return this;
        },

        find: function(finder, params) {
            if (angular.isDefined(params) && angular.isObject(params)) {
                var paramsArray = [];
                angular.forEach(params, function(value, key) {
                    this.push(key + ':' + value);
                }, paramsArray);
                this._finder.push(finder + '[' + paramsArray.join(';') + ']');
            } else {
                this._finder.push(finder);
            }
            return this;
        },

        where: function(where) {
            this._where = where;
            return this;
        },

        orWhere: function(orWhere) {
            if (angular.isObject(orWhere)) {
                var paramsArray = [];
                angular.forEach(orWhere, function(value, key) {
                    this.push(key + ':' + value);
                }, paramsArray);
                this._orWhere.push(paramsArray.join(','));
            }
            return this;
        },

        group: function(group) {
            this._group = group;
            return this;
        },

        order: function(order) {
            this._order = order;
            return this;
        },

        limit: function(limit) {
            this._limit = limit;
            return this;
        },

        page: function(page) {
            this._page = page;
            return this;
        },

        ajax: function(settings) {
            var success = null;
            var error = null;
            var type = 'json';
            var deferred = null;
            var customUrl = null;

            var requireDeferred = settings.defer != undefined && settings.defer == true;

            if (requireDeferred) {
                deferred = $q.defer();
            }

            if (settings.type != undefined) {
                type = settings.type;
            }

            if (settings.url != undefined) {
                customUrl = settings.url;
            }

            var hasSuccessCallback = settings.success != undefined;

            if (hasSuccessCallback && !requireDeferred) {
                success = settings.success;
            } else if (hasSuccessCallback && requireDeferred) {
                success = function(response) {
                    if (angular.isDefined(response.data.error)) {
                        deferred.reject(response.data.error);
                    } else {
                        settings.success(response, deferred);
                    }
                };
            } else if (!hasSuccessCallback && requireDeferred) {
                success = function(response) {
                    if (angular.isDefined(response.data.error)) {
                        deferred.reject(response.data.error);
                    } else {
                        deferred.resolve(response.data);
                    }
                };
            }
            
            if (settings.error != undefined) {
                error = settings.error;
            } else {
                if (requireDeferred) {
                    error = function(error) {
                        deferred.reject(error);
                    };
                }
            }

            if (settings.method == undefined) {
                settings.method = this._method;
            }

            if (settings.headers == undefined) {
                settings.headers = {'Content-Type': 'application/x-www-form-urlencoded'};
            }
            var url = this.toURL(customUrl);
            settings.url = url.replace('@type', type);
            if (success == null && error == null) {
                return $http(settings);
            }

            var httpResponse = $http(settings).then(success, error);
            return requireDeferred ? deferred.promise : httpResponse;
        },

        toURL: function(customUrl) {
            var model = this._className.replace('.', '-');
            var url = [this._base, this._controller, model].join('/');
            var params = [];
            if (this._select.length > 0) {
                params.push('_fields=' + this._select.join(','));
            }
            if (this._contain.length > 0) {
                params.push('_contain=' + this._contain.join(','));
            }
            if (this._finder.length > 0) {
                params.push('_finder=' + this._finder.join(','));
            }
            if (this._group.length > 0) {
                params.push('_group=' + this._group.join(','));
            }
            if (this._order.length > 0) {
                params.push('_order=' + this._order.join(','));
            }
            if (Object.keys(this._where).length > 0) {
                angular.forEach(this._where, function(value, key) {
                    this.push(key + '=' + value);
                }, params);
            }
            if (this._orWhere.length > 0) {
                params.push('_orWhere=' + this._orWhere.join(','));
            }
            params.push('_limit=' + this._limit);
            if (this._page > 0) {
                params.push('_page=' + this._page);
            }

            if (this._id > 0) {
                url += '/' + this._id;
            }

            url += '.@type';

            if (customUrl != null) {
                url = customUrl;
            }

            if (params.length > 0) {
                url += '?' + params.join('&');
            }  
            return url;
        },

        save: function(data) {
            this._method = 'POST';
            var settings = {
                headers: {'Content-Type': 'application/json'},
                data: data
            };
            return this.ajax(settings);
        }
    };

    return {
        base: base,
        init: init
    };

    function base(base) {
        query._base = base;
        return this;
    };

    function init(className) {
        if (angular.isObject(className)) {
            angular.forEach(className, function(model, key) {
                var newObject = angular.merge({}, query);
                newObject.className(model);
                window[key] = newObject;
            });
        } else {
            var newObject = angular.merge({}, query);
            newObject.className(className);
            return newObject;
        }
    };
});
