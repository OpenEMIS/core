<?php
//Main Library
echo $this->Html->script('OpenEmis.lib/css_browser_selector');
echo $this->Html->script('OpenEmis.lib/respond');
echo $this->Html->script('OpenEmis.lib/jquery/jquery.min');
echo $this->Html->script('OpenEmis.lib/jquery/jquery-ui.min');
echo $this->Html->script('OpenEmis.lib/angular/angular.min');
// echo $this->Html->script('OpenEmis.lib/angular/angular-route.min');
echo $this->Html->script('OpenEmis.lib/angular/angular-animate.min');
// echo $this->Html->script('OpenEmis.angular/ng.layout-splitter');
echo $this->Html->script('OpenEmis.angular/kd-angular-elem-sizes');
echo $this->Html->script('OpenEmis.angular/kd-angular-checkbox-radio-button');
echo $this->Html->script('OpenEmis.angular/kd-angular-multi-select/kd-angular-multi-select');
echo $this->Html->script('OpenEmis.angular/kd-angular-treedropdown');
echo $this->Html->script('OpenEmis.angular/kd-angular-ag-grid');
echo $this->Html->script('OpenEmis.lib/holder');
echo $this->Html->script('OpenEmis.lib/angular/ui-bootstrap-tpls.min');
echo $this->Html->script('OpenEmis.angular/kd-angular-advanced-search.ctrl');

//Only when needed this have to be added in ScriptBottom
echo $this->Html->script('OpenEmis.jquery/jq.mobile-menu');
echo $this->Html->script('OpenEmis.jquery/jq.loader');
echo $this->Html->script('OpenEmis.jquery/jq.chosen');
echo $this->Html->script('OpenEmis.jquery/jq.checkable');
echo $this->Html->script('OpenEmis.jquery/jq.datetime-picker');
echo $this->Html->script('OpenEmis.jquery/jq.table');
echo $this->Html->script('OpenEmis.jquery/jq.tabs');
echo $this->Html->script('OpenEmis.jquery/jq.tooltip');
echo $this->Html->script('OpenEmis.jquery/jq.header');
// echo $this->Html->script('OpenEmis.jquery/jq.multiple-image-uploader');
// echo $this->Html->script('OpenEmis.jquery/jq.gallery');

//External Plugins
echo $this->Html->script('OpenEmis.../plugins/bootstrap/js/bootstrap.min');
echo $this->Html->script('OpenEmis.../plugins/fuelux/js/fuelux');
echo $this->Html->script('OpenEmis.../plugins/scrolltabs/js/jquery.mousewheel');
echo $this->Html->script('OpenEmis.../plugins/scrolltabs/js/jquery.scrolltabs');
echo $this->Html->script('OpenEmis.../plugins/slider/js/bootstrap-slider');
echo $this->Html->script('OpenEmis.../plugins/ng-scrolltabs/js/angular-ui-tab-scroll');
// echo $this->Html->script('OpenEmis.../plugins/ng-agGrid/js/ag-grid');
echo $this->Html->script('OpenEmis.../plugins/ag-grid-enterprise/dist/ag-grid-enterprise.min');

//Tree Dropdown
echo $this->Html->script('OpenEmis.../plugins/multi-select-tree/dist/angular-multi-select-tree-0.1.0');
echo $this->Html->script('OpenEmis.../plugins/multi-select-tree/dist/angular-multi-select-tree-0.1.0.tpl');

echo $this->Html->script('ControllerAction.../plugins/datepicker/locales/bootstrap-datepicker.'.$dateLanguage.'.min', ['block' => true]);