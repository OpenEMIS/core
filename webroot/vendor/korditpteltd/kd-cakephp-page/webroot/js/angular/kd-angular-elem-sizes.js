//Element Height and Width Sizes v.1.0.0

angular.module('kd-elem-sizes', [])
    .directive('kdElemSizes', kdElemSizes);

function kdElemSizes() {
    var directive = {
        restrict: 'A',
        transclude: true,
        link: kdElemSizesLink
    };
    return directive;
}

function kdElemSizesLink(_scope, _element, _attrs) {
    var offsetHeight = 0;

    angular.element(document).ready(function() {
        if (_element.hasClass('has-tabs')) {
            offsetHeight = 33;
        }

        if (_attrs.kdElemSizes.match(/^fitToElementClass/) || _attrs.kdElemSizes.match(/^fitToElementId/)) {
            var fitOptions = {
                element: _element,
                selectedElementObj: _attrs.kdElemSizes.split(":"),
                offsetHeight: offsetHeight,
                type: (_attrs.kdElemSizes.match(/^fitToElementClass/)) ? "class" : "id",
            }

            fitToScreen(fitOptions);

            angular.element(window).bind('resize', function(_event) {
                fitToScreen(fitOptions);
            });
        } else {
            if (angular.isDefined(_attrs.kdHeight)) {
                _element.css('height', _attrs.kdHeight);
            }

            if (angular.isDefined(_attrs.kdWidth)) {
                _element.css('width', _attrs.kdWidth);
            }
        }
    });
}

function fitToScreen(_options) {
    var type = (_options.type == 'class') ? "." : "#";
    var newHeight = 0;
    if (_options.selectedElementObj.length >= 2) {
        var parentElem = angular.element(document.querySelector(type + _options.selectedElementObj[1]));
        newHeight = parentElem[0].offsetHeight - _options.offsetHeight;
    } else {
        newHeight = _options.element[0].parentElement.offsetHeight - _options.offsetHeight;

    }
    _options.element.css('height', newHeight).css('width', '100%');
}
