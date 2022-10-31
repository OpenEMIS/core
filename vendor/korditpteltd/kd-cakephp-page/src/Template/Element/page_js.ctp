<script type="text/javascript">
window.onload = function(e) {
    Page.querystringValue = Page.getParamValue('querystring');
}

document.addEventListener("DOMContentLoaded", function() {
    var elements = document.getElementsByTagName('select');
    for (i=0; i<elements.length; i++) {
        if (elements[i].hasAttribute('dependent-on')) {
            element = elements[i];
            dependentOn = element.getAttribute('dependent-on');
            var sources = dependentOn.split(" ");
            (function (s, e) {
                for (var i = 0; i < s.length; i++) {
                    var source = document.getElementById(s[i]);
                    source.addEventListener('change', function() {
                        Page.onChange(s, e);
                    });
                }

            }) (sources, element);
        }
    }
});

var Page = {
    querystringValue: ''
};

Page.updateUrlParamValue = function(key, value) {
    var url = window.location.href;
    var regex = new RegExp("\\b(" + key + "=).*?(&|$)\\b");

    if (url.indexOf("?") == -1) { // no querystring params
        url += "?" + key + "=" + value;
    } else {
        if (url.indexOf(key) == -1) { // key not exists in querystring
            url += "&" + key + "=" + value;
        } else {
            url = url.replace(regex, '$1' + value + '$2');
        }
    }
    return url;
}

Page.removeUrlParam = function(key) {
    var href = window.location.href;
    var regex = new RegExp("\\b(" + key + "=).*?(&|$)\\b");
    url = href.replace(regex, '');
    if (url.substr(url.length - 1) == '?') { // if last character is '?' then remove it
        url = url.substr(0, url.length-1);
    }
    return url;
}

Page.querystring = function(key, value, obj) {
    var querystringValue = this.querystringValue;
    if (querystringValue != null) {
        querystringValue = JSON.parse(querystringValue.hexDecode());
    } else {
        querystringValue = {};
    }
    var retainedDependentKey = ['search'];
    var resetAllKey = false;
    var retainedQueryStringValue = {};

    // Check to see if the object has the reset all flag, if yes then only the search and the value for the object will retain
    if (obj.getAttribute('resetall') == 1) {
        retainedDependentKey.push(key);
        resetAllKey = true;
    }
    // If there is a dependent flag on the object that is change, then only the parent, search and the object will retain
    else if (obj.getAttribute('dependenton') != undefined) {
        var dependentOnString = obj.getAttribute('dependenton');
        var dependentOnArr = dependentOnString.split(" ");
        for (var i = 0 ; i < dependentOnArr.length ; i++) {
            var dependentOn = dependentOnArr[i];
            while (dependentOn != null) {
                retainedDependentKey.push(dependentOn);
                var dependentObj = document.getElementById(dependentOn);
                if (dependentObj != null && dependentObj.getAttribute('dependenton') != undefined) {
                    dependentOn = dependentObj.getAttribute('dependenton');
                } else {
                    dependentOn = null;
                }
            }
        }

    }
    // If not all the query string value will retain
    else {
        retainedQueryStringValue = querystringValue;
    }
    retainedDependentKey.forEach(function (val, k) {
        if (querystringValue[val] != undefined) {
            retainedQueryStringValue[val] = querystringValue[val];
        }
    });

    querystringValue = retainedQueryStringValue;

    if (value == null || value.trim().length == 0) {
        delete querystringValue[key];
    } else {
        querystringValue[key] = value;
    }
    var count = 0;
    for(var prop in querystringValue) {
        if(querystringValue.hasOwnProperty(prop)) ++count;
    }
    if (count > 0) {
        querystringValue = JSON.stringify(querystringValue).hexEncode();
        window.location.href = this.updateUrlParamValue('querystring', querystringValue);
    } else {
        window.location.href = this.removeUrlParam('querystring');
    }
}

Page.getParamValue = function(name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null, ''])[1].replace(/\+/g, '%20')) || null;
}

Page.onChange = function(sources, target) {
    var targetValue = target.value;
    target.innerHTML = '<option value=0>Updating</option>';
    var xhr = new XMLHttpRequest();
    var isMultiple = target.getAttribute('multiple') != null;
    var method = 'onchange/' + target.getAttribute('params');

    // Logic added to fix the bug where the base url is with the id parameter
    var baseUrl = window.location.href;
    baseUrl = baseUrl.split('?')[0];
    var segments = baseUrl.split('/');
    var segmentCount = segments.length;
    var selectedSegment = segments[segmentCount - 2];
    if (selectedSegment[0].toUpperCase() != selectedSegment[0]) {
        method = '../' + method;
    }
    // End of fix

    var params = {};
    for (var i = 0; i < sources.length ; i++) {
        var source = document.getElementById(sources[i]);
        params[source.id] = source.value;
    }

    if (isMultiple) {
        params['multiple'] = 'true';
    }
    // if (isReset) {
    //     params['reset'] = 'true';
    // }
    xhr.open('GET', method + '?querystring=' + JSON.stringify(params).hexEncode());
    xhr.onload = function() {
        if (xhr.status === 200) {
            target.innerHTML = '';
            var response = JSON.parse(xhr.responseText);
            var data = response.data;
            for (var i=0; i<data.length; i++) {
                var option = document.createElement('option');
                option.innerHTML = data[i]['text'];
                option.setAttribute('value', data[i]['value']);
                if (data[i]['value'] == targetValue) {
                    option.setAttribute('selected', true);
                }
                if (data[i].hasOwnProperty('disabled')) {
                    option.setAttribute('disabled', true);
                }
                target.appendChild(option);
            }
        } else {
            console.log('Request failed.  Returned status of ' + xhr.status);
        }
    };
    xhr.send();
}

String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); }
String.prototype.hexEncode = function() {
    var hex, i;
    var result = "";
    for (i=0; i<this.length; i++) {
        hex = this.charCodeAt(i).toString(16);
        result += ("000"+hex).slice(-4);
    }

    return result;
}

String.prototype.hexDecode = function() {
    var i;
    var hexes = this.match(/.{1,4}/g) || [];
    var result = "";
    for(i = 0; i<hexes.length; i++) {
        result += String.fromCharCode(parseInt(hexes[i], 16));
    }

    return result;
}
</script>
