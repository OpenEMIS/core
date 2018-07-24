//Multi Select v.1.0.1
(function() {
    'use strict';

    angular.module('kd-angular-tree-dropdown', [])
        .directive('kdTreeDropdownNg', kdTreeDropdown);
    var tree_dropdown_separator = "--";

    function kdTreeDropdown() {

        var directive = {
            restrict: 'E',
            replace: true,
            transclude: true,
            template: "<div class='tree-dropdown'>\
                        <multi-select-tree \
                        data-input-model='inputModel' \
                        multi-select='isMultiple' \
                        data-output-model='outputModel' \
                        data-callback='selectionCallback(item, selectedItems)'\
                        data-expand-click = 'onItemExpand(item)'\
                        data-expand-parent = 'onClick()'\
                        tree-id='{{elementId}}'\
                        ></multi-select-tree>\
                </div>\
        ",
            controller: kdTreeDropdownCtrl,
            link: kdTreeDropdownLink,
            scope: {
                inputModel: '=?',
                outputModel: '=?',
                textConfig: '=?',
                modelType: '@?',
                onItemExpand: '&',
                expandChild: '&',
                getChildData: '&',
                expandParent: '&'
            }
        };
        return directive;

    }
    kdTreeDropdownCtrl.$inject = ["$scope"];

    function kdTreeDropdownCtrl($scope) {
        $scope.treePlaceholder = "%tree_no_of_item";
        $scope.selectedItem = [];
        $scope.isMultiple = (angular.isDefined($scope.modelType) && $scope.modelType == "single") ? false : true;

        $scope.selectionText = updateSelectionTextConfig($scope);

    }

    function kdTreeDropdownLink(_scope, _element, _attrs) {


        _scope.elementId = getElementId(_element);

        angular.element(document).ready(function() {
            addInputText(_scope);
            angular.element(document.querySelector("#" + _scope.elementId + " .tree-input")).addClass("input-select-wrapper");

        });

        _scope.selectionCallback = function(_item, _selectedItem) {
            if (typeof _item == "undefined") return false;
            //updateSelectionText(_selectedItem, _item, _scope.elementId);
            return true;
        }

        _scope.$watch('outputModel', function(_newSelectedList) {
            if (angular.isUndefined(_scope.selectionText)) return;

            updateSelectionText(generateSelectionText(_newSelectedList, _scope.selectionText, _scope.treePlaceholder), _scope.elementId);
        });

        _scope.$watch('textConfig', function(_newTextConfig) {
            _scope.selectionText = updateSelectionTextConfig(_scope);
            updateSelectionText(generateSelectionText(_scope.outputModel, _scope.selectionText, _scope.treePlaceholder), _scope.elementId);
        }, true);


        /************ Automatically Expand the default selection ****************/

        if (typeof _scope.inputModel !== "undefined") {
            loadExpand(_scope.inputModel);
        }

        _scope.onItemExpand = function(_item) {
            if (_scope.expandChild) {
                //pass in parent data to front end so developer will get the parent to pull child data
                _scope.expandChild({ parentData: _item, getChildData: _scope.getChildData });
            } else {
                console.log("expand-child is not declared");
            }
        }

        _scope.getChildData = function(_parentData, _childData) {
            //check if the child has been selected. if selected, add into outputModel and update selection text.
            for (var i = 0; i < _childData.length; i++) {
                if (typeof _childData[i].selected !== "undefined" && _childData[i].selected && $.inArray(_childData[i], _scope.outputModel) == -1) {
                    _scope.outputModel.push(_childData[i]);
                    updateSelectionText(generateSelectionText(_scope.outputModel, _scope.selectionText, _scope.treePlaceholder), _scope.elementId);
                }
            }
            _parentData.children = _childData;
            loadExpand(_childData);

            _scope.$apply();
        }

        _scope.onClick = function() {
            angular.element(document.querySelector("#" + _scope.elementId + " .tree-selection-text")).addClass("slctLoadingIco");

            _scope.expandParent({ refreshList: _scope.refreshList });
        }

        _scope.refreshList = function(_parentData) {
            angular.element(document.querySelector("#" + _scope.elementId + " .tree-selection-text")).removeClass("slctLoadingIco");

            _scope.inputModel = _parentData;
            loadExpand(_scope.inputModel);
            // to remove in v1.0.1
            // _scope.$apply();
        }

    }

    function loadExpand(_pData) {

        var isExpandNeeded = true;

        for (var i = 0; i < _pData.length; i++) {
            //if children = number > do expand to load
            if (typeof _pData[i].children == "number") {
                if (_pData[i].children > 0) {
                    _pData[i].children = [{ name: "", id: 0, loading: true }];
                }
                isExpandNeeded = false;
            }
        }

        // children is not number, auto expand so the user can see the default selection
        if (isExpandNeeded) {
            processExpandChild(_pData);
        }
    }

    function processExpandChild(_parentData) {

        var output = getFlattenData(_parentData, "");

        for (var i = 0; i < output.length; i++) {

            var data = output[i].split(tree_dropdown_separator);
            var parent = null;

            for (var x = 0; x < data.length; x++) {
                if (data[x] !== "") {
                    if (parent == null) {
                        parent = _parentData[data[x]];
                    } else {
                        parent = parent[data[x]];
                    }
                    parent.isExpanded = true;
                }
            }

        }
    }

    function getFlattenData(_obj, _propString) {

        var result = {};
        var output = [];

        processFlatten(_obj, _propString);

        function processFlatten(_rObj, _rPropString) {
            if (Object(_rObj) !== _rObj) { //number or string
                result[_rPropString] = _rObj; // --0--id : 41
            } else if (Array.isArray(_rObj)) { // true for child data: [{name:child1, id:41}]
                for (var i = 0, len = _rObj.length; i < len; i++) {
                    processFlatten(_rObj[i], _rPropString + tree_dropdown_separator + i); // {id:41, name:parent3, children:[{...}]}, '' + -- + 0
                }
                if (len == 0) { //end of the array
                    result[_rPropString] = [];
                }
            } else { //pass in parent data {id:41, name:parent3, children:[{...}]}
                var isEmpty = true;
                for (var key in _rObj) {
                    isEmpty = false;

                    if (key == "selected" && _rObj[key]) {
                        output.push(_rPropString); // --0
                    }
                    processFlatten(_rObj[key], _rPropString ? _rPropString + tree_dropdown_separator + key : key); // id, '' + -- + 0
                }
                if (isEmpty) //end of the _rObj
                    result[_rPropString] = {};
            }
        }

        return output;
    }


    /*************** End of Expanding *****************/

    function generateSelectionText(_selectedList, _selectionText, _placeholder) {
        var selectionText = _selectionText.noSelection;

        if (angular.isDefined(_selectedList)) {
            if (_selectedList.length == 1) selectionText = _selectedList[0].name;
            else if (_selectedList.length > 1) selectionText = _selectionText.multipleSelection.replace(_placeholder, _selectedList.length);
        }

        return selectionText;
    }

    function updateSelectionText(_selectionText, _elementId) {
        var selectionTextEle = angular.element(document.querySelector("#" + _elementId + " .tree-selection-text"));
        selectionTextEle.text(_selectionText);
    }

    // function updateSelectionText(_selectedItem, _item, _elementId) {
    //     var selectionTextEle = angular.element(document.querySelector(_elementId + " .tree-selection-text"));

    //     if(_selectedItem.length == 0) selectionText = "Please select";
    //     else if(_selectedItem.length == 1) selectionText = _selectedItem[0].name;
    //     else selectionText = _selectedItem.length + " items selected";

    //     selectionTextEle.text(selectionText);
    // }

    function addInputText(_scope) {
        var div = angular.element("<div class='tree-selection-text'></div>");
        div.text(generateSelectionText(_scope.outputModel, _scope.selectionText, _scope.treePlaceholder));
        angular.element(document.querySelector("#" + _scope.elementId + " .tree-control")).append(div);
    }

    function getElementId(_element) {
        if (angular.isDefined(_element[0].id) && _element[0].id != "") return _element[0].id;
        return "";
    }

    // selectionText = {
    //      noSelection: "",
    //      multipleSelection: ""
    // }
    function updateSelectionTextConfig(_scope) {
        var selectionText = {
            noSelection: "-- Select --",
            multipleSelection: _scope.treePlaceholder + " items selected"
        };

        if (angular.isDefined(_scope.textConfig)) {
            var selectionText = Object.assign(selectionText, _scope.textConfig);
        }
        return selectionText;
    }

})();
