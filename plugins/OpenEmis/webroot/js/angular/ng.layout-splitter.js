//Layout Splitter v.1.0.3

// Fixing the issue on first load when left navigation is overlaping the view before the angular has been initialize
var sheet = (function() {
    // Create the <style> tag
    var style = document.createElement("style");

    // Add a media (and/or media query) here if you'd like!
    // style.setAttribute("media", "screen")
    // style.setAttribute("media", "only screen and (max-width : 1024px)")

    // WebKit hack :(
    style.appendChild(document.createTextNode(""));

    //addCSSRule(style.sheet, ".left-pane", "background-color: #FF0;", 0);

    // Add the <style> element to the page
    document.head.appendChild(style);

    return style.sheet;
})();

var pos = (typeof localStorage.lastHandlerPos == 'undefined') ? window.innerWidth * 0.1 : window.innerWidth * localStorage.lastHandlerPos;
if (pos > window.innerWidth) {
    pos = window.innerWidth * 0.1;
}
// console.log(window.innerWidth);
sheet.insertRule('.left-pane{width:' + pos + 'px;}', 0);
// End fixing on first load.

// Angular Splitter
angular.module('bgDirectives', [])
    .directive('bgSplitter', function() {
        return {
            restrict: 'E',
            replace: true,
            transclude: true,
            scope: {
                orientation: '@'
            },
            template: '<div class="split-panes {{orientation}}" ng-transclude></div>',
            controller: ['$scope', function($scope) {
                $scope.panes = [];

                this.addPane = function(pane) {
                    if ($scope.panes.length > 1)
                        throw 'splitters can only have two panes';
                    $scope.panes.push(pane);
                    return $scope.panes.length;
                };
            }],
            link: function(scope, element, attrs) {
                var handler = angular.element('<div class="split-handler"></div>');
                var pane1 = scope.panes[0];
                var pane2 = scope.panes[1];
                var vertical = scope.orientation == 'vertical';
                var pane1Min = pane1.minSize || 0;
                var pane2Min = pane2.minSize || 0;
                var bodyDir = getComputedStyle(document.body).direction;
                var affectedDir = (bodyDir == 'ltr') ? 'left' : 'right';
                var drag = false;

                pane1.elem.after(handler);
                var screenWidth = window.innerWidth;

                if (!angular.isUndefined(localStorage.lastHandlerPos) && screenWidth > 1024) {
                    pos = getWidthPixel();

                    handler.css(affectedDir, pos + 'px');
                    pane1.elem.css('width', pos + 'px');
                    pane2.elem.css(affectedDir, pos + 'px');
                } else {
                    if (screenWidth <= 1024) {
                        handler.css(affectedDir, '0px');
                        pane1.elem.css('width', pos + 'px');
                        pane2.elem.css(affectedDir, '0px');
                    }
                }

                enableDrag();

                function enableDrag() {

                    element.bind('mousemove', function(ev) {
                        if (!drag) return;

                        var bounds = element[0].getBoundingClientRect();
                        var pos = 0;
                        var panelObj = reCalPanelPercentSize();

                        pane1Min = panelObj['pane1Min']; //((pane1.minSizeP / 100) * window.innerWidth) || pane1Min;
                        pane2Min = panelObj['pane2Min']; //((pane2.minSizeP / 100) * window.innerWidth) || pane2Min;

                        $.each($('.highchart'), function(key, group) {
                            $(group).highcharts().reflow();
                        });

                        if (vertical) {

                            var height = bounds.bottom - bounds.top;
                            pos = ev.clientY - bounds.top;

                            if (pos < pane1Min) return;
                            if (height - pos < pane2Min) return;

                            handler.css('top', pos + 'px');
                            pane1.elem.css('height', pos + 'px');
                            pane2.elem.css('top', pos + 'px');

                        } else {
                            var width = bounds.right - bounds.left;

                            if (bodyDir == 'ltr') {
                                pos = ev.clientX - bounds.left;
                            } else {
                                pos = bounds.right - ev.clientX;
                            }

                            if (pos < pane1Min) return;

                            if (bodyDir == 'ltr') {
                                if (width - pos < pane2Min) return;
                            } else {
                                if (pos > width - pane2Min) return;
                            }

                            handler.css(affectedDir, pos + 'px');
                            pane1.elem.css('width', pos + 'px');
                            pane2.elem.css(affectedDir, pos + 'px');
                            localStorage.lastHandlerPos = pos / window.innerWidth;
                        }
                    });
                }

                function getWidthPixel() {
                    var pos = (typeof localStorage.lastHandlerPos == 'undefined') ? window.innerWidth * 0.1 : window.innerWidth * localStorage.lastHandlerPos
                    if (pos > window.innerWidth) {
                        pos = window.innerWidth * 0.1;
                    }
                    return pos;
                }

                function disableDrag() {
                    element.unbind('mousemove');
                }

                function reCalPanelPercentSize() {
                    var panelObj = {};
                    if (!angular.isUndefined(pane1.minSizeP)) {
                        panelObj['pane1Min'] = (pane1.minSizeP / 100) * window.innerWidth;
                    }

                    if (!angular.isUndefined(pane2.minSizeP)) {
                        panelObj['pane2Min'] = (pane2.minSizeP / 100) * window.innerWidth;
                    }
                    return panelObj;
                }

                handler.bind('mousedown', function(ev) {
                    ev.preventDefault();
                    drag = true;
                });

                angular.element(document).bind('mouseup', function(ev) {
                    drag = false;
                });

                angular.element(window).bind('resize', function(ev) {
                    var screenWidth = window.innerWidth;
                    if (screenWidth <= 1024) {
                        disableDrag();
                        pane2.elem.css(affectedDir, '0px');
                    } else {
                        enableDrag();
                        handler.css(affectedDir, getWidthPixel() + 'px');
                        pane1.elem.css('width', getWidthPixel() + 'px');
                        pane2.elem.css(affectedDir, getWidthPixel() + 'px');
                    }

                });
            }
        };
    })
    .directive('bgPane', function() {
        return {
            restrict: 'E',
            require: '^bgSplitter',
            replace: true,
            transclude: true,
            scope: {
                minSize: '=',
                minSizeP: '=',
            },
            template: '<div class="split-pane{{index}}" ng-transclude></div>',
            link: function(scope, element, attrs, bgSplitterCtrl) {
                scope.elem = element;
                scope.index = bgSplitterCtrl.addPane(scope);
            }
        };
    });
