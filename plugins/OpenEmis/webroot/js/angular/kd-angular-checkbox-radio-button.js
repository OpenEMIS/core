//Checkbox and Radio Button v.1.0.1
(function() {
    'use strict';

    angular.module('kd-angular-checkbox-radio', [])
        .directive('kdCheckboxRadio', kdCheckboxRadio);


    function kdCheckboxRadio() {
        var directive = {
            restrict: 'A',
            link: kdCheckboxRadioLink,
        };
        return directive;
    }

    function kdCheckboxRadioLink(_scope, _element, _attrs) {
        var wrapper = angular.element("<div class='selection-wrapper'></div>");
        var label = angular.element("<label></label>");

        if (_attrs.kdCheckboxRadio != "") label.append(_attrs.kdCheckboxRadio);
        _element.after(wrapper);

        wrapper.append(_element).append(label);
    }

})();
