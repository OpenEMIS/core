angular.module('multi-select-tree').run(['$templateCache', function($templateCache) {
  'use strict';

  $templateCache.put('src/multi-select-tree.tpl.html',
    "<div class=\"tree-control\">\n" +
    "\n" +
    "    <div class=\"tree-input\" ng-click=\"onControlClicked($event)\">\n" +
    "    <span ng-if=\"selectedItems.length == 0\" class=\"selected-items\">\n" +
    "      <span ng-bind=\"defaultLabel\"></span>\n" +
    "    </span>\n" +
    "    <span ng-if=\"selectedItems.length > 0\" class=\"selected-items\">\n" +
    "      <span ng-repeat=\"selectedItem in selectedItems\" class=\"selected-item\">{{selectedItem.name}} <span class=\"selected-item-close\"\n" +
    "                                                                                  ng-click=\"deselectItem(selectedItem, $event)\"></span></span>\n" +
    "        <span class=\"caret\"></span>\n" +
    "    </span>\n" +
    "        <!-- <input type=\"text\" class=\"blend-in\" /> -->\n" +
    "    </div>\n" +
    "    <div class=\"tree-view\" ng-show=\"showTree\">\n" +
    "        <ul class=\"tree-container\">\n" +
    "            <tree-item class=\"top-level\" ng-repeat=\"item in inputModel\" item=\"item\" ng-show=\"!item.isFiltered\"\n" +
    "                       use-callback=\"useCallback\" can-select-item=\"canSelectItem\"\n" +
    "                       multi-select=\"multiSelect\" item-selected=\"itemSelected(item)\"\n" +
    "                       on-active-item=\"onActiveItem(item)\" select-only-leafs=\"selectOnlyLeafs\" is-radio=\"isRadio\" tree-id=\"{{treeId}}\"></tree-item>\n" +
    "        </ul>\n" +
    "    </div>\n" +
    "</div>\n"
  );


  $templateCache.put('src/tree-item.tpl.html',
    "<li>\n" +
    "    <div class=\"item-container\" ng-class=\"{active: item.isActive, selected: item.selected, disableNode: item.disabled}\"\n" +
    "         ng-click=\"clickSelectItem(item, $event)\" ng-mouseover=\"onMouseOver(item, $event)\">\n" +
    "        <div ng-if=\"showExpand(item)\" class=\"expand fa fa-fw\" ng-class=\"{'expand-opened': item.isExpanded, 'fa-caret-down': item.isExpanded, 'fa-caret-right': !item.isExpanded}\"\n" +
    "              ng-click=\"onExpandClicked(item, $event)\"></div>\n" +
    "\n" +
    "        <div class=\"item-details\"> " +
    "           <input class=\"tree-checkbox\" ng-disabled=\"item.disabled\" type=\"checkbox\" ng-if=\"showCheckbox()\"\n ng-checked=\"item.selected\"/> " +
    "           <input class=\"tree-checkbox\" ng-disabled=\"item.disabled\" type=\"radio\" name=\"{{treeId}}\" ng-if=\"!showCheckbox()\"\n ng-checked=\"item.selected\"/> " +
    "           <label>{{item.name}}\n</label> " +
    "        </div>\n" +
    "    </div>\n" +
    "    <ul ng-repeat=\"child in item.children\" ng-if=\"item.isExpanded\">\n" +
    "        <tree-item class='children' item=\"child\" item-selected=\"subItemSelected(item)\" use-callback=\"useCallback\"\n" +
    "                   can-select-item=\"canSelectItem\" multi-select=\"multiSelect\"\n" +
    "                   on-active-item=\"activeSubItem(item, $event)\"></tree-item>\n" +
    "    </ul>\n" +
    "</li>\n"
  );

}]);
