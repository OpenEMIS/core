<?php
echo $this->element('OpenEmis.scripts');

/*echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');*/

echo $this->Html->script('ControllerAction.controller.action');
echo $this->Html->script('doughnutchart/Chart.min');
echo $this->Html->script('doughnutchart/Chart.Doughnut');

// ui-bootstrap
echo $this->Html->script('app/shared/angularUI/ui-bootstrap.min');

// Slider //
echo $this->Html->script('app/shared/ngSlider/slider');

//Angular Controller
echo $this->Html->script('app/app');

//JS use in Core
echo $this->Html->script('app');
echo $this->Html->script('app.table');
echo $this->Html->script('config');

