<?php
echo $this->element('OpenEmis.scripts');

/*echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');*/

echo $this->element('ControllerAction.scripts');
echo $this->Html->script('doughnutchart/Chart.min');
echo $this->Html->script('doughnutchart/Chart.Doughnut');

// Slider //
echo $this->Html->script('app/shared/ngSlider/slider');

// UI-Bootstrap
echo $this->Html->script('OpenEmis.lib/angular/ui-bootstrap-tpls.min');

// Scrollable Tabs
echo $this->Html->script('OpenEmis.../plugins/ng-scrolltabs/js/angular-ui-tab-scroll');

// Ag-Grid
echo $this->Html->script('OpenEmis.../plugins/ng-agGrid/js/ag-grid.min');

// // HTTP/HTTPS routing functions //
// echo $this->Html->script('app/shared/angularRoute/angular-route.min');

// // angular cookies functions //
// echo $this->Html->script('app/shared/angularCookies/angular-cookies.min');

// // Assessments specific controller
// echo $this->Html->script('Assessment.administration/assessments');

echo $this->Html->script('angular/kdModule/controllers/kd.ctrl');
echo $this->Html->script('angular/kdModule/directives/kd.drt');
echo $this->Html->script('angular/kdModule/services/kd.common.svc');
echo $this->Html->script('angular/kdModule/kd.module');

//JS use in Core
echo $this->Html->script('app');
echo $this->Html->script('app.table');
echo $this->Html->script('config');
