angular.module('utils.svc', [])
.service('UtilsSvc', ['$location',function($location) {
	return {
		requestQuery: function(key) {
            return parseInt($location.search()[key]);
        },

		isAppendLoader: function(_isAppend, _percent) {
	        var hasClass = angular.element('div').hasClass('load-page');
	        if (_isAppend) {
	            if (angular.isDefined(_percent)) {
	                if (_percent == false) {
	                    angular.element('div').remove(".load-page");
	                    hasClass = false;
	                } else {
	                    var hasPercent = angular.element('div').hasClass('loading-percentage');
	                    if (hasClass && hasPercent) {
	                        angular.element(document.getElementById('bodyLoadPercentIndicator')).text(_percent);
	                    } else {
	                        if (hasClass) {
	                            angular.element('div').remove(".load-page");
	                        }
	                        var loaderElement = angular.element('<div class="load-page"><div class="loader-text"><div class="loader lt-ie9"></div><p></p><div id="bodyLoadPercentIndicator" class="loading-percentage">'+_percent+'</div></div></div>');
	                        angular.element(document.body).prepend(loaderElement);
	                    }
	                    return;
	                }
	            }
	            if (!hasClass) {
	                var loaderElement = angular.element('<div class="load-page"><div class="loader-text"><i class="fa kd-openemis"></i><div class="loader lt-ie9"></div><p></p></div></div>');
	                angular.element(document.body).prepend(loaderElement);
	            }
	        } else {
	            angular.element('div').remove(".load-page");
	        }
	    },

	    isAppendSpinner: function(_isAppend, _querySelector) {
	        var spinnerId = _querySelector + '-spinner';
	        var hasClass = angular.element(document.getElementById(spinnerId)).hasClass('spinner-wrapper');
	        if (_isAppend) {
	            if(!hasClass){
	                var spinnerElement = angular.element('<div id="'+ spinnerId +'" ' + 'class="spinner-wrapper"><div class="spinner-text"><div class="spinner lt-ie9"></div></div></div>');
	                angular.element(document.getElementById(_querySelector)).prepend(spinnerElement);
	            }
	        } else{
	            angular.element(document.getElementById(spinnerId)).remove('.spinner-wrapper');
	        }
	    },

	    urlsafeBase64Encode: function(jsonString) {
	    	return encodeURI(btoa(jsonString)).replace(/=/gi, "");
	    }
	}
}]);
