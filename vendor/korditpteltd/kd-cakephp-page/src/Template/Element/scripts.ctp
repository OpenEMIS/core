<?php
//Main Library
echo $this->Html->script('Page.lib/css_browser_selector');
echo $this->Html->script('Page.lib/respond');
echo $this->Html->script('Page.lib/jquery/jquery.min');
echo $this->Html->script('Page.lib/jquery/jquery-ui.min');
echo $this->Html->script('Page.lib/angular/angular.min');
echo $this->Html->script('Page.lib/angular/angular-route.min');
echo $this->Html->script('Page.lib/angular/angular-animate.min');
// echo $this->Html->script('Page.angular/ng.layout-splitter');
echo $this->Html->script('Page.angular/kd-angular-elem-sizes');
echo $this->Html->script('Page.angular/kd-angular-checkbox-radio-button');
echo $this->Html->script('Page.angular/kd-angular-multi-select/kd-angular-multi-select');
echo $this->Html->script('Page.angular/kd-angular-treedropdown');
echo $this->Html->script('Page.lib/holder');
echo $this->Html->script('Page.lib/angular/ui-bootstrap-tpls.min');
echo $this->Html->script('Page.angular/kd-angular-advanced-search.ctrl');

//Only when needed this have to be added in ScriptBottom
echo $this->Html->script('Page.jquery/jq.mobile-menu');
echo $this->Html->script('Page.jquery/jq.loader');
echo $this->Html->script('Page.jquery/jq.chosen');
echo $this->Html->script('Page.jquery/jq.checkable');
echo $this->Html->script('Page.jquery/jq.datetime-picker');
echo $this->Html->script('Page.jquery/jq.table');
echo $this->Html->script('Page.jquery/jq.tabs');
echo $this->Html->script('Page.jquery/jq.tooltip');
echo $this->Html->script('Page.jquery/jq.header');

//External Plugins
echo $this->Html->script('Page.../plugins/bootstrap/js/bootstrap.min');
echo $this->Html->script('Page.../plugins/fuelux/js/fuelux');
echo $this->Html->script('Page.../plugins/icheck/icheck');
echo $this->Html->script('Page.../plugins/scrolltabs/js/jquery.mousewheel');
echo $this->Html->script('Page.../plugins/scrolltabs/js/jquery.scrolltabs');
echo $this->Html->script('Page.../plugins/slider/js/bootstrap-slider');
echo $this->Html->script('Page.../plugins/ng-scrolltabs/js/angular-ui-tab-scroll');
echo $this->Html->script('Page.../plugins/ng-agGrid/js/ag-grid');
// echo $this->Html->script('Page.../plugins/ag-grid-enterprise/dist/ag-grid-enterprise.min');

//Tree Dropdown
echo $this->Html->script('Page.../plugins/multi-select-tree/dist/angular-multi-select-tree-0.1.0');
echo $this->Html->script('Page.../plugins/multi-select-tree/dist/angular-multi-select-tree-0.1.0.tpl');
