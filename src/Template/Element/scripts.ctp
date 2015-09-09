<?php
echo $this->element('OpenEmis.scripts');

/*echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');*/

echo $this->Html->script('ControllerAction.controller.action');
echo $this->Html->script('doughnutchart/Chart.min');
echo $this->Html->script('doughnutchart/Chart.Doughnut');


echo $this->Html->script('app');
echo $this->Html->script('app.table');


