<?php
echo $this->element('OpenEmis.scripts');

/*echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');*/

echo $this->Html->script('ControllerAction.controller.action');
echo $this->Html->script('doughnutchart/Chart.min');
echo $this->Html->script('doughnutchart/Chart.Doughnut');

// Slider //
echo $this->Html->script('app/shared/ngSlider/slider');

echo $this->Html->script('app/app.ctrl');
echo $this->Html->script('app/app.svc');
echo $this->Html->script('app/services/app/utils.svc');
echo $this->Html->script('app/services/app/kd.orm.svc');

//JS use in Core
echo $this->Html->script('app');
echo $this->Html->script('app.table');
echo $this->Html->script('config');
