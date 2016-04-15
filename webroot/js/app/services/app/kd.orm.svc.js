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
    	_order: [],

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
        	var paramsArray = [];
        	if (params != undefined) {
        		angular.forEach(params, function(value, key) {
					this.push(key + ':' + value);
				}, paramsArray);
				this._finder.push(finder + '[' + paramsArray.join(';') + ']');
        	}
        	return this;
        },

        where: function(where) {
        	this._where = where;
        	return this;
        },

        order: function(order) {
        	this._order = order;
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
                        deferred.resolve(response.data.data);
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
			var url = this.toURL();
			settings.url = url.replace('@type', type);

			if (success == null && error == null) {
				return $http(settings);
			}

            var httpResponse = $http(settings).then(success, error);
            return requireDeferred ? deferred.promise : httpResponse;
        },

        toURL: function() {
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
        	if (Object.keys(this._where).length > 0) {
        		angular.forEach(this._where, function(value, key) {
					this.push(key + '=' + value);
				}, params);
        	}

        	if (this._id > 0) {
        		url += '/' + this._id;
        	}

        	url += '.@type';

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
    	base: function(base) {
    		query._base = base;
    		return this;
    	},

    	init: function(className) {
    		var newObject = angular.merge({}, query);
    		newObject.className(className);
    		return newObject;
    	}
    }
});
