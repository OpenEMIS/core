//Layout Splitter v.2.0.2
(function() {
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

    var subPanesWidth = 0;
    var appName = APP_CONFIGS.ngApp;//.document.body.getAttribute("ng-app");
    var sessionName = appName + '_handlerID_';
    var matchCase = new RegExp(sessionName);
    var matchBodyCase = new RegExp(sessionName + 'body');
    var matchPanelBodyCase = new RegExp(sessionName + 'div\.panel-body');
    var bodyDir = getComputedStyle(document.body).direction;
    var affectedDir = (bodyDir == 'ltr') ? 'left' : 'right';
// console.log(sessionName);
    var panesObj = {
        'wrapper': 'pane-wrapper',
        'left': 'left-pane',
        'right': 'right-pane'
    };

    var subPanesObj = {
        'wrapper': 'content-splitter',
        'left': 'main-content',
        'right': 'split-content'
    };

    var loadDefaultPanes = {
        layoutPane: true,
        contentPane: true
    };
    initCSS();
    // End fixing on first load.

    // Angular Splitter
    angular.module('bgDirectives', [])
        // .directive('bgSplitter', ["$compile","$q", bgSplitter])
        .directive('bgSplitter', ["$compile", "$q", function($compile, $q) {
            var directive = {
                restrict: 'E',
                replace: true,
                transclude: true,
                scope: {
                    orientation: "@",
                    collapse: "@",
                    // resizeCallback: "=",
                    floatBtn: "@",
                    getElements: "=elements"
                },
                template: '<div class="split-panes {{orientation}}" ng-transclude></div>',
                controller: bgSpiltterCtrl,
                link: function(scope, element, attrs) { bgSplitterLink(scope, element, attrs, $compile, $q) }
            };

            return directive;
        }])
        .directive('bgPane', bgPane);

    function bgPane() {
        var directive = {
            restrict: 'E',
            require: '^bgSplitter',
            replace: true,
            transclude: true,
            scope: {
                // minSize: '@',
                minSizeP: '@',
                // maxSize: '@',
                maxSizeP: '@',
                // Size: "@",
                SizeP: "@",
            },
            template: '<div class="split-pane{{index}}" ng-transclude></div>',
            link: function(scope, element, attrs, bgSplitterCtrl) {
                scope.elem = element;
                scope.index = bgSplitterCtrl.addPane(scope);
            }
        };

        return directive;
    }

    // bgSplitter.$inject = ["$compile","$q"];
    // function bgSplitter($compile, $q) {
    //     var directive = {
    //         restrict: 'E',
    //         replace: true,
    //         transclude: true,
    //         scope: {
    //             orientation: "@",
    //             collapse: "@",
    //             // resizeCallback: "=",
    //             floatBtn: "@",
    //             getElements: "=elements"
    //         },
    //         template: '<div class="split-panes {{orientation}}" ng-transclude></div>',
    //         controller: bgSpiltterCtrl,
    //         link: bgSplitterLink
    //     };

    //     return directive;
    // }

    bgSpiltterCtrl.$inject = ["$scope"];

    function bgSpiltterCtrl($scope) {
        $scope.panes = [];

        this.addPane = function(pane) {
            if ($scope.panes.length > 1)
                throw 'splitters can only have two panes';
            $scope.panes.push(pane);
            return $scope.panes.length;
        };
    }

    function bgSplitterLink(scope, element, attrs, $compile, $q) {
        var animationTime = 550;
        var isMobileView = (window.innerWidth <= 1024) ? true : false;
        var showHideIcon = 'kd-lists';

        var vertical = scope.orientation == 'vertical';
        var defaultConfig = {
            collapse: null
        };
        var panelObj = {};
        var drag = false;
        var paneElems = getChildPanes(element[0].childNodes);
        var elemName = element.parent()[0].localName + '.' + element.parent()[0].className.replace(/ /g, '.').replace(/.ng-scope/g, '');

        var handlerSessionID = sessionName + elemName;
        var handlerMenuID = sessionName + "_menu_status";

        var handler = angular.element('<div class="split-handler"><div class="menu-btn"><i class="fa"></i></div></div>');
        var button = angular.element('<div class="mobile-split-btn"><button class="btn btn-default btn-circle btn-medium btn-float-bottom-right" type="button"><i class="fa"></i></button></div>');

        angular.element(document).ready(function() {
            init();
        });

        function initHandler() {
            var deferred = $q.defer();
            paneElems.left.after(handler);
            deferred.resolve(true);
            return deferred.promise;
        }

        function init() {
            //add handler to the view
            // paneElems.left.after(handler).injector().invoke(function($compile) {
            //     var scope = angular.element(handler).scope();
            //     $compile(handler)(scope);
            // });
            initHandler().then(function() {
                var scope = angular.element(handler).scope();
                $compile(handler)(scope);
            })

            paneElems.splitter = handler;
            panelObj = reCalPanelSize();

            if (!angular.isDefined(attrs.collapse)) {
                defaultConfig.collapse = 'false';
                if (panelObj.isRoot === false) {
                    // panelObj.collapse = (window.innerWidth <= 1024) ? 'true' : panelObj.collapse;

                    if (window.innerWidth <= 1024) {
                        panelObj.collapse = 'true';
                    }
                }

                console.log('initial load = ' + panelObj.collapse);
            }

            // defaultConfig.collapse = paneObj.collapse;
            //reupdate paneElems to get handler;
            paneElems = getChildPanes(element[0].childNodes);

            if (angular.isDefined(scope.getElements)) {
                scope.getElements(paneElems);
            }

            if (panelObj.isRoot === false) {

                addFloatButton();

            } else {
                collapseMainMenu();
            }

            //Add in splitter slide out class on the handler so that it won't appear on load
            showHideSidePane();

            scope.$watch(function() {
                return element.attr('collapse');
            }, function(newValue) {
                if (angular.isDefined(newValue)) {
                    panelObj.collapse = newValue;
                    showHideSidePane(true);
                }
            });

            /* ===============
             * Bind events
             * =============== */

            handler.bind('mousedown touchstart', function(_event) {
                _event.preventDefault();
                drag = true;
            });

            angular.element(document).bind('mouseup touchend', function(_event) {
                drag = false;
            });

            angular.element(window).bind('resize', function(_event) {
                panelObj = reCalPanelSize();
                if (window.innerWidth <= 1024) { //mobile view
                    disableDrag();
                    paneElems.right.css(affectedDir, '0px');

                    if (angular.isDefined(paneElems.floatBtn)) {
                        angular.element(paneElems.floatBtn[0].childNodes[0]).removeClass('kd-close-round').addClass('kd-lists');
                    }
                    if (!isMobileView) {
                        isMobileView = true;
                        if (panelObj.isRoot === false && angular.isUndefined(scope.collapse)) panelObj.collapse = 'true';
                        showHideSidePane(true);
                    }
                } else { //web view
                    // isMobileView = false;
                    if (isMobileView) {
                        isMobileView = false;
                    }

                    enableDrag();
                    setPanes(vertical, paneElems, { pos1: panelObj.current, pos2: panelObj.current });

                    if (panelObj.isRoot === false) {
                        if (panelObj.collapse !== "true") {
                            removeOverlayContent(paneElems.left);
                            addSlideInContent(paneElems.right);
                            addSlideInContent(paneElems.splitter);
                        }
                    } else {
                        var menuBtnElem = paneElems.splitter.find('.menu-btn');
                        var menuIconElem = menuBtnElem.find('.fa');
                        updateBtnMenuStatus(menuIconElem, false);
                    }

                    if (defaultConfig.collapse != null) {
                        panelObj.collapse = defaultConfig.collapse;
                    }
                }
            });

            enableDrag();
        }

        function enableDrag() {
            element.bind('mousemove touchmove', function(_event) {
                if (!drag) return;

                var moveObj = _event;
                var bounds = element[0].getBoundingClientRect();
                var pos = 0;
                panelObj = reCalPanelSize();

                if (angular.isDefined(_event.originalEvent.touches)) { // check is using touch event or mousemove event
                    moveObj = _event.originalEvent.touches[0];
                }

                if (vertical) {
                    var height = bounds.bottom - bounds.top;
                    pos = moveObj.clientY - bounds.top;

                    if (pos < panelObj.pane1.min) return;
                    if (height - pos < panelObj.pane2.min) return;

                    localStorage[handlerSessionID] = pos / height;
                } else {
                    var width = bounds.right - bounds.left;
                    pos = (bodyDir == 'ltr') ? moveObj.clientX - bounds.left : bounds.right - moveObj.clientX;

                    if (angular.isDefined(panelObj.pane1.max) && pos > panelObj.pane1.max) return;
                    if (pos < panelObj.pane1.min) return;
                    if (angular.isDefined(panelObj.pane2.max) && (width - pos > panelObj.pane2.max)) return;
                    if (bodyDir == 'ltr') {
                        if (width - pos < panelObj.pane2.min) return;
                    } else {
                        if (pos > width - panelObj.pane2.min) return;
                    }
                    localStorage[handlerSessionID] = pos / width;
                }
                var selectedWrapper = (element.parent()[0].localName == 'body') ? "pane-wrapper" : "content-splitter";

                scope.$broadcast('onSplitterResize', selectedWrapper);

                setPanes(vertical, paneElems, { pos1: pos, pos2: pos });
            });
        }

        function disableDrag() {
            element.unbind('mousemove touchmove');
        }

        function showHideSidePane(_withAnimation) {
            console.log('after click = ' + panelObj.collapse);
            // var paneElems = getChildPanes(element[0].childNodes); //Get updated dom when angular is ready
            if ((panelObj.isRoot === false) && (window.innerWidth <= 1024)) { // mobile view
                if (panelObj.collapse === "true") {
                    addSlideOutAnimation(paneElems.right);
                } else {
                    addSlideInAnimation(paneElems.right);
                }
            } else if (panelObj.collapse === "true") {
                if (_withAnimation) {
                    addSlideOutAnimation(paneElems.splitter);
                    addSlideOutAnimation(paneElems.right);
                    addOverlayContent(paneElems.left);
                    addTransition(paneElems.left);

                    setTimeout(function() {
                        removeTransition(paneElems.splitter);
                        removeTransition(paneElems.right);
                        removeTransition(paneElems.left);
                    }, animationTime);
                } else {
                    addSlideOutContent(paneElems.splitter);
                    addOverlayContent(paneElems.left);
                    addSlideOutContent(paneElems.right);
                }
            } else {
                if (_withAnimation) {
                    addTransition(paneElems.left);
                    removeOverlayContent(paneElems.left);
                    addSlideInAnimation(paneElems.right);
                    addSlideInAnimation(paneElems.splitter);

                    setTimeout(function() {
                        removeTransition(paneElems.left);
                        removeTransition(paneElems.right);
                        removeTransition(paneElems.splitter);
                    }, animationTime);
                } else {
                    addSlideInContent(paneElems.splitter);
                    removeOverlayContent(paneElems.left);
                    addSlideInContent(paneElems.right);
                }
            }
        }

        //Collapse the Main Menu
        function collapseMainMenu() {
            var menuBtnElem = paneElems.splitter.find('.menu-btn');
            var menuIconElem = menuBtnElem.find('.fa').addClass(getMenuIconClass().current);
            updateBtnMenuStatus(menuIconElem, false);
            menuBtnElem.bind('click', function(_event) {
                if (window.innerWidth >= 1024) {
                    updateBtnMenuStatus(menuIconElem, true);
                }
            });
        }

        function initFloatButton() {
            var deferred = $q.defer();
            paneElems.right.after(button);
            deferred.resolve(true);
            return deferred.promise;
        }

        function addFloatButton() {
            angular.element('.menu-btn').remove();
            if (panelObj.floatBtn === 'true') {
                //add button 
                //
                initFloatButton().then(function() {
                    var btnWrapperElem = angular.element(button);
                    var scope = btnWrapperElem.scope();
                    var iconElem = angular.element(btnWrapperElem[0].childNodes[0].childNodes[0]);
                    var btnElem = angular.element(btnWrapperElem[0].childNodes[0]);

                    iconElem.addClass(showHideIcon);
                    paneElems['floatBtn'] = btnElem;
                    btnElem.bind('click', function(_event) {

                        if (window.innerWidth <= 1024) {
                            iconElem.removeClass(showHideIcon);
                            if (showHideIcon === "kd-lists") {
                                showHideIcon = "kd-close-round";
                                panelObj.collapse = "false";
                            } else {
                                showHideIcon = "kd-lists";
                                panelObj.collapse = "true";
                            }
                            iconElem.addClass(showHideIcon);
                            showHideSidePane();
                        }
                    });

                    $compile(handler)(scope);
                })
                // paneElems.right.after(button).injector().invoke(function($compile) {
                //     var btnWrapperElem = angular.element(button);
                //     var scope = btnWrapperElem.scope();
                //     var iconElem = angular.element(btnWrapperElem[0].childNodes[0].childNodes[0]);
                //     var btnElem = angular.element(btnWrapperElem[0].childNodes[0]);

                //     iconElem.addClass(showHideIcon);
                //     paneElems['floatBtn'] = btnElem;
                //     btnElem.bind('click', function(_event) {

                //         if (window.innerWidth <= 1024) {
                //             iconElem.removeClass(showHideIcon);
                //             if (showHideIcon === "kd-lists") {
                //                 showHideIcon = "kd-close-round";
                //                 panelObj.collapse = "false";
                //             } else {
                //                 showHideIcon = "kd-lists";
                //                 panelObj.collapse = "true";
                //             }
                //             iconElem.addClass(showHideIcon);
                //             showHideSidePane();
                //         }
                //     });

                //     $compile(handler)(scope);
                // });
            }
        }

        function updateBtnMenuStatus(_elem, _updateDirection) { //console.log('run updateBtnMenuStatus')
            //this function needs to update the status and save into the localstorage
            _updateDirection = (angular.isDefined(_updateDirection)) ? _updateDirection : false;
            var curState = getCurrentSplitterStatus();
            var iconClassObj = getMenuIconClass();

            if (curState == "collapse") {
                if (_updateDirection) {
                    _elem.addClass(iconClassObj.opposite).removeClass(iconClassObj.current);
                    addAnimationDelay();
                    showMenu();
                    setCurrentSplitterStatus(false);
                } else {
                    _elem.addClass(iconClassObj.current).removeClass(iconClassObj.opposite);
                    hideMenu();
                }
            } else {

                if (_updateDirection) {
                    addAnimationDelay();
                    hideMenu();
                    _elem.addClass(iconClassObj.opposite).removeClass(iconClassObj.current);
                    setCurrentSplitterStatus(true);
                } else {
                    _elem.addClass(iconClassObj.current).removeClass(iconClassObj.opposite);
                    showMenu();
                }
            }
        }

        function getMenuIconClass() {
            var curState = getCurrentSplitterStatus();
            var curDirection = oppDirection = "";
            var iconClassPrefix = "fa-angle-";

            if (vertical) {
                if (bodyDir == 'ltr') {
                    curDirection = (curState == 'collapse') ? "down" : "up";
                    oppDirection = (curDirection == 'down') ? 'up' : 'down';
                } else {
                    curDirection = (curState == 'collapse') ? "up" : "down";
                    oppDirection = (curDirection == 'up') ? 'down' : 'up';
                }
            } else {
                if (bodyDir == 'ltr') {
                    curDirection = (curState == 'collapse') ? "right" : "left";
                    oppDirection = (curDirection == 'right') ? 'left' : 'right';
                } else {
                    curDirection = (curState == 'collapse') ? "left" : "right";
                    oppDirection = (curDirection == 'left') ? 'right' : 'left';
                }
            }

            return { current: iconClassPrefix + curDirection, opposite: iconClassPrefix + oppDirection };
        }

        function getCurrentSplitterStatus() {
            /* console.log('handlerMenuID = ' + handlerMenuID);
             console.log(localStorage[handlerMenuID]);*/
            return (angular.isDefined(localStorage[handlerMenuID])) ? localStorage[handlerMenuID] : 'uncollapse';
        }

        function setCurrentSplitterStatus(_isCollapse) {
            localStorage[handlerMenuID] = (_isCollapse) ? "collapse" : "uncollapse";
            // console.log('update localStorage = ' + localStorage[handlerMenuID]);
        }

        function hideMenu() {
            paneElems.right.css(affectedDir, '0');
            paneElems.splitter.css(affectedDir, '-1px');
            disableDrag();
        }

        function showMenu() {
            setPanes(vertical, paneElems, { pos1: panelObj.current, pos2: panelObj.current });
            enableDrag();
        }

        function addAnimationDelay() {
            //Adding animation 
            addTransition(paneElems.right);
            addTransition(paneElems.splitter);

            setTimeout(function() {
                removeTransition(paneElems.splitter);
                removeTransition(paneElems.right);
            }, animationTime);
        }

        //End of Collapse Main Menu

        function reCalPanelSize() {
            var bounds = element.parent()[0].getBoundingClientRect();
            var boundSize = (vertical) ? bounds.height : bounds.width;
            var panesObj = {
                'pane1': {},
                'pane2': {},
            };
            for (var i in scope.panes) {
                var pane = scope.panes[i];
                var key = 'pane' + (parseInt(i) + 1);
                var paneObj = panesObj[key];
                // var reverse = (i == 0) ? false : true;
                if (angular.isDefined(pane.minSizeP)) {
                    paneObj['min'] = calRatioInPixel(pane.minSizeP, boundSize, false); //(pane1.minSizeP / 100) * boundSize;
                } else if (angular.isDefined(pane.minSize)) {
                    paneObj['min'] = pane.minSize;
                }

                if (angular.isDefined(pane.maxSizeP)) {
                    paneObj['max'] = calRatioInPixel(pane.maxSizeP, boundSize, false); //(pane1.maxSizeP / 100) * boundSize;
                } else if (angular.isDefined(pane.maxSize)) {
                    paneObj['max'] = pane.maxSize;
                }
            }

            panesObj['collapse'] = angular.isDefined(scope.collapse) ? scope.collapse : false;
            panesObj['floatBtn'] = angular.isDefined(scope.floatBtn) ? scope.floatBtn : 'true';
            panesObj['isRoot'] = elemName.match(/^body/) ? true : false;

            if (typeof localStorage[handlerSessionID] != 'undefined') {
                panesObj['current'] = localStorage[handlerSessionID] * boundSize;
            } else {
                panesObj['current'] = (angular.isDefined(paneObj['min'])) ? paneObj['min'] : (0.1 * boundSize);
            }
            return panesObj;
        }
    }

    function setPanes(_vertical, _elem, _panesPos) {
        if (_vertical) {
            _elem.left.css('height', _panesPos.pos1 + 'px');
            _elem.splitter[0].style.top = _elem.right[0].style.top = _panesPos.pos2 + 'px';
        } else {
            _elem.left.css('width', _panesPos.pos1 + 'px');
            _elem.splitter[0].style[affectedDir] = _elem.right[0].style[affectedDir] = _panesPos.pos2 + 'px';
        }
    }

    function getChildPanes(_childNodes) {
        //, 'className', 'split-pane'
        var type = 'className';
        var matchPane = new RegExp('split-pane');
        var matchSpliter = new RegExp('split-handler');
        var obj = {
            'left': {},
            'right': {},
            'splitter': {}
        };
        var count = 1;
        for (var i in _childNodes) {
            if (_childNodes[i][type] != undefined) {
                if (_childNodes[i][type].match(matchPane)) {
                    var key = (count == 1) ? 'left' : 'right';
                    obj[key] = angular.element(_childNodes[i]);
                    count++;
                } else if (_childNodes[i][type].match(matchSpliter)) {
                    obj.splitter = angular.element(_childNodes[i]);
                }
            }
        }
        return obj;
    }

    function calRatioInPixel(_val, _boundSize, _reverse) {
        var sizePixel = 0;

        if (_val.match(/px$/)) {
            sizePixel = _val.replace('px', "");
        } else {
            // backward compatible (default percent)
            var ratio = _val;
            if (ratio.match(/\%$/)) {
                ratio = ratio.replace('%', "");
            }
            ratio = Number(ratio) / 100;
            if (_reverse) {
                ratio = 1 - ratio;
            }

            sizePixel = ratio * _boundSize;
        }
        return sizePixel;
    }

    function initCSS() {
        //Prepare all nodes (pane 1 and 2)
        var elemNode = document.getElementsByClassName(panesObj.wrapper)[0];
        var layoutPaneObj = getPanesObjFromDOM(elemNode.childNodes, 'localName', 'bg-pane', window.innerWidth);
        var rightPaneWidth = window.innerWidth - layoutPaneObj.current;
        var elemSubNode = document.getElementsByClassName(subPanesObj.wrapper)[0];

        //insert for session
        for (var session in localStorage) {
            if (session.match(matchCase)) {
                var className = session.replace(sessionName, "");
                if (session.match(matchBodyCase)) {
                    // console.log('load main session');
                    // loadDefaultPanes.layoutPane = false;
                    var pos = Math.floor(window.innerWidth * localStorage[session]);
                    var subPanePadding = 15 * 2; // Do check the class in ",has-breadcrumb" class
                    rightPaneWidth = window.innerWidth - pos - subPanePadding;
                    insertCSSRules(className, panesObj, pos);
                } else {
                    // console.log(session);
                    if (session.match(matchPanelBodyCase) && elemSubNode != undefined) {
                        // console.log('load sub main session');
                        loadDefaultPanes.contentPane = false;
                        // console.log('rightPaneWidth = ' + rightPaneWidth);
                        // console.log('localStorage[session] = ' + localStorage[session]);
                        var pos = Math.floor(rightPaneWidth * localStorage[session]);
                        // console.log('pos = ' + pos);
                        insertCSSRules(className, subPanesObj, pos);
                    }
                }
            }
        }

        //insert default
        if (loadDefaultPanes.layoutPane) {
            // console.log('load sub main default layoutPane');
            // console.log(elemNode,panesObj,layoutPaneObj.current);
            insertCSSRules(getParentClassName(elemNode), panesObj, layoutPaneObj.current);
            if (loadDefaultPanes.contentPane && elemSubNode != undefined) {
                // console.log('load sub main default contentPane');
                var mainPaneObj = getPanesObjFromDOM(elemSubNode.childNodes, 'localName', 'bg-pane', rightPaneWidth);
                insertCSSRules(getParentClassName(elemSubNode), subPanesObj, mainPaneObj.current);
            }
        }
    }

    function getParentClassName(_elemNode) {
        return _elemNode.parentElement.localName + '.' + _elemNode.parentElement.className;
    }

    function insertCSSRules(_parentClassName, _panesObj, _pos) {
        var oppDir = (bodyDir == 'ltr') ? 'right' : 'left';
        var dirClass = (bodyDir == 'ltr') ? '' : '.rtl ';
        var prependClassName = dirClass + _parentClassName;

        // var isMobileView = (window.innerWidth <= 1024)? true: false;
        // console.log(prependClassName + ' .' + _panesObj.left + '{width:' + _pos + 'px; ' + affectedDir + ':0px;' + oppDir + ':auto;}');
        // console.log(prependClassName + ' .' + _panesObj.right + '{' + affectedDir + ':' + _pos + 'px;' + oppDir + ':0px;}');
        // console.log(prependClassName + ' .' + _panesObj.left + ' + .split-handler{' + affectedDir + ':' + _pos + 'px;}');
        if (window.innerWidth <= 1024) {
            sheet.insertRule(prependClassName + ' .' + _panesObj.right + '{left: 0px;right:0px;}', sheet.cssRules.length);
            sheet.insertRule(prependClassName + ' .' + _panesObj.left + '{left: 0px;right:0px;}', sheet.cssRules.length);
        } else {
            sheet.insertRule(prependClassName + ' .' + _panesObj.left + '{width:' + _pos + 'px; ' + affectedDir + ':0px;' + oppDir + ':auto; opacity: 1;}', sheet.cssRules.length);
            sheet.insertRule(prependClassName + ' .' + _panesObj.right + '{' + affectedDir + ':' + _pos + 'px;' + oppDir + ':0px;}', sheet.cssRules.length);
            sheet.insertRule(prependClassName + ' .' + _panesObj.left + ' + .split-handler{' + affectedDir + ':' + _pos + 'px;}', sheet.cssRules.length);
        }
        // console.log(sheet);
        // console.log(sheet.cssRules.length);
    }

    function getPanesObjFromDOM(_childNodes, _type, _strMatch, _boundSize) {
        var matchCase = new RegExp(_strMatch);
        var panelObj = {
            'pane1': {},
            'pane2': {},
        };
        var count = 0;
        var sizeFound = false;
        var current = 0;
        for (var i in _childNodes) {
            if (_childNodes[i][_type] != undefined && _childNodes[i][_type].match(matchCase)) {
                var paneKey = (count == 0) ? 'pane1' : 'pane2';
                var reverse = (count == 0) ? false : true;
                if (_childNodes[i].hasAttribute('size-p')) {
                    panelObj[paneKey]['size'] = calRatioInPixel(_childNodes[i].getAttribute('size-p'), _boundSize, reverse);
                    if (!sizeFound) {
                        sizeFound = true;
                        current = panelObj[paneKey]['size'];
                    }
                }
                if (_childNodes[i].hasAttribute('min-size-p')) {
                    panelObj[paneKey]['minSize'] = calRatioInPixel(_childNodes[i].getAttribute('min-size-p'), _boundSize, reverse);
                    if (!sizeFound) {
                        sizeFound = true;
                        current = panelObj[paneKey]['minSize'];
                    }
                }
                count++;
            }
        }

        panelObj['current'] = (sizeFound) ? current : 0.1 * _boundSize;
        return panelObj;
    }

    //Slide in action
    function addSlideInContent(_elem) {
        _elem.addClass('splitter-slide-in').removeClass('splitter-slide-out').removeClass('slide-transition');
    }

    //Slide out action
    function addSlideOutContent(_elem) {
        _elem.addClass('splitter-slide-out').removeClass('splitter-slide-in').removeClass('slide-transition');
    }

    //Slide in action with animation
    function addSlideInAnimation(_elem) {
        _elem.addClass('splitter-slide-in').removeClass('splitter-slide-out').addClass('slide-transition');
    }

    //Slide out action with animation
    function addSlideOutAnimation(_elem) {
        _elem.addClass('splitter-slide-out').removeClass('splitter-slide-in').addClass('slide-transition');
    }

    //Add Transition Only
    function addTransition(_elem) {
        _elem.addClass('slide-transition');
    }

    //Remove Transition Only
    function removeTransition(_elem) {
        _elem.removeClass('slide-transition');
    }

    //Add Overlay Content Only
    function addOverlayContent(_elem) {
        _elem.addClass('overlay-content');
    }

    //Remove Overlay Content Only
    function removeOverlayContent(_elem) {
        _elem.removeClass('overlay-content');
    }
})();