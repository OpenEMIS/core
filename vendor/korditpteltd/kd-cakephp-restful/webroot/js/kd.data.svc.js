angular.module('kd.data.svc', [])
.service('KdDataSvc', ['$q', '$http', function($q, $http) {
    var query = {
        responseType: 'json',
        version: 'v2',
        _base: '',
        _controller: 'restful',
        _className: '', // model classname
        _method: 'GET', // GET/POST/PUT/DELETE
        _controllerAction: null,
        _id: null, // model primary key
        _select: [],
        _contain: [],
        _finder: [],
        _where: {},
        _orWhere: [],
        _group: [],
        _order: [],
        _limit: 0,
        _page: 0,
        _search: '',
        _querystring: '',
        _schema: false,

        className: function(className) {
            this._className = className;
            return this;
        },

        controller: function(controller) {
            this._controller = controller;
            return this;
        },

        setVersion: function(version) {
            this.version = version;
            return this;
        },

        reset: function() {
            this._id = null;
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
            this._schema = false;
            this._querystring = '';
            this._search = '';
        },

        schema: function(bool) {
            this._schema = bool;
            return this;
        },

        querystring: function(params) {
            var json = JSON.stringify(params);
            this._querystring = this.urlsafeB64Encode(json);
            return this;
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
            var encodedWhere = {}
            angular.forEach(where, function(value, key) {
                encodedWhere[key.replace(".", "-")] = value;
            });
            this._where = encodedWhere;
            return this;
        },

        /* Eg.
        var wc = KdOrmSvc.wildcard();
        UsersTable
        .orWhere({
            'first_name': wc + 'do' + wc,
            'last_name': 'ste' + wc
        })
        .ajax({defer: true});

        which evaluates to

        // http://host/restful/User-Users.json?_orWhere=first_name:_do_,last_name:ste_
        */
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

        search: function(searchText) {
            if (searchText == '') {
                this._search = searchText;
            } else {
                this._search = this.urlsafeB64Encode(searchText);
            }
            return this;
        },

        ajax: function(settings) {
            var success = null;
            var error = null;
            var type = 'json';
            var deferred = null;

            var requireDeferred = settings.defer != undefined && settings.defer == true;

            if (requireDeferred) {
                deferred = $q.defer();
            }

            if (settings.type != undefined) {
                type = settings.type;
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

            if (settings.authorizationHeader != undefined) {
                settings.headers.authorization = settings.authorizationHeader;
                delete settings.authorizationHeader;
            }

            if (query._controllerAction != null) {
                settings.headers.ControllerAction = query._controllerAction;
            }

            var url = this.toURL();
            settings.url = url.replace('@type', type);

            if (success == null && error == null) {
                return $http(settings);
            }
            this.reset();
            var httpResponse = $http(settings).then(success, error);
            return requireDeferred ? deferred.promise : httpResponse;
        },

        toURL: function() {
            var model = this._className.replace('.', '-');
            var url = [this._base, this._controller, this.version, model].join('/');
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

            if (this._schema) {
                params.push('_schema=' + this._schema);
            }

            if (this._querystring.length > 0) {
                params.push('_querystring=' + this._querystring);
            }

            if (this._search.length > 0) {
                params.push('_search=' + this._search);
            }

            if (this._id != null) {
                url += '/' + this._id;
            }

            url += '.@type';

            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            return url;
        },

        save: function(data) {
            data['action_type'] = 'default'; // flag to distinguish between angular & external API call
            var settings = {
                headers: {'Content-Type': 'application/json'},
                data: data,
                method: 'POST'
            };
            return this.ajax(settings);
        },

        edit: function(data) {
            var settings = {
                headers: {'Content-Type': 'application/json'},
                data: data,
                method: 'PATCH'
            };
            return this.ajax(settings);
        },

        translate: function(data, options) {
            this._className = 'translate';
            var settings = {
                headers: {'Content-Type': 'application/json'},
                data: data,
                method: 'POST'
            };
            if (options !== undefined) {
                if (options.defer !== undefined) {
                    settings.defer = options.defer;
                    settings.success = options.success;
                    settings.error = options.error;
                }
            }
            return this.ajax(settings);
        }
    };

    return {
        base: base,
        controllerAction: controllerAction,
        init: init,
        wildcard: wildcard,
        customAjax: customAjax,
        urlsafeB64Encode: urlsafeB64Encode
    };

    function urlsafeB64Encode(textStr) {
        var encoded = encodeURI(btoa(textStr)).replace(/=/gi, "");
        return encoded;
    }

    function customAjax (url, options, data) {
        if (!angular.isDefined(options)) {
            options = {method: 'GET', headers: {'Content-Type': 'application/json'}};
        }
        if (!angular.isDefined(data)) {
            data = {};
        }
        var deferred = $q.defer();
        var success = function(response) {
            if (angular.isDefined(response.data.error)) {
                deferred.reject(response.data.error);
            } else {
                deferred.resolve(response.data);
            }
        };

        var error = function(error) {
            deferred.reject(error);
        };

        options.url = url;
        options.data = data;
        var httpResponse = $http(options).then(success, error);
        return deferred.promise;
    };

    function controllerAction(action) {
        query._controllerAction = action;
    };

    function base(base) {
        query._base = base;
        return this;
    };

    function wildcard() {
        return '_';
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
}]);
