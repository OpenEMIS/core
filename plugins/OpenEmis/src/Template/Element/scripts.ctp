<?php
//Main Library
echo $this->Html->script('OpenEmis.lib/css_browser_selector');
echo $this->Html->script('OpenEmis.lib/respond');
echo $this->Html->script('OpenEmis.lib/jquery/jquery.min');
echo $this->Html->script('OpenEmis.lib/jquery/jquery-ui.min');
echo $this->Html->script('OpenEmis.lib/angular/angular.min');
echo $this->Html->script('OpenEmis.angular/ng.layout-splitter');
echo $this->Html->script('OpenEmis.lib/holder');

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

//External Plugins
echo $this->Html->script('OpenEmis.../plugins/bootstrap/js/bootstrap.min');
echo $this->Html->script('OpenEmis.../plugins/fuelux/js/fuelux');
echo $this->Html->script('OpenEmis.../plugins/icheck/icheck');
echo $this->Html->script('OpenEmis.../plugins/scrolltabs/js/jquery.mousewheel');
echo $this->Html->script('OpenEmis.../plugins/scrolltabs/js/jquery.scrolltabs');
echo $this->Html->script('OpenEmis.../plugins/slider/js/bootstrap-slider');