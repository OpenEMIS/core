<?php
echo $this->element('OpenEmis.scripts');

/*echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');*/

echo $this->element('ControllerAction.scripts');
echo $this->Html->script('doughnutchart/Chart.min');
echo $this->Html->script('doughnutchart/Chart.Doughnut');

// ui-bootstrap
echo $this->Html->script('app/shared/angularUI/ui-bootstrap.min');

// Slider //
echo $this->Html->script('app/shared/ngSlider/slider');

// HTTP/HTTPS routing functions //
echo $this->Html->script('app/shared/angularRoute/angular-route.min');

// Assessments specific controller
// echo $this->Html->script('Assessment.administration/assessments');

//Angular Controller
echo $this->Html->script('app/app');

//JS use in Core
echo $this->Html->script('app');
echo $this->Html->script('app.table');
echo $this->Html->script('config');

