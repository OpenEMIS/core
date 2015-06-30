<?php
echo $this->Html->script('OpenEmis.css_browser_selector');
echo $this->Html->script('OpenEmis.jquery.min');
echo $this->Html->script('OpenEmis.../plugins/bootstrap/js/bootstrap.min');

echo $this->Html->script('OpenEmis.../plugins/icheck/icheck');
//echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');

echo $this->Html->script('ControllerAction.controller.action');
echo $this->Html->script('OpenEmis.holder');

echo $this->Html->script('doughnutchart/Chart.min');
echo $this->Html->script('doughnutchart/Chart.Doughnut');

echo $this->Html->script('app');
echo $this->Html->script('app.table');
