<?php
echo $this->Html->script('jquery.min');
echo $this->Html->script('OpenEmis.../plugins/bootstrap/js/bootstrap.min');
echo $this->Html->script('css_browser_selector');

//echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');

echo $this->Html->script('ControllerAction.controller.action');
echo $this->Html->script('OpenEmis.holder');
echo $this->Html->script('OpenEmis.../plugins/scrolltabs/js/jquery.mousewheel');
echo $this->Html->script('OpenEmis.../plugins/scrolltabs/js/jquery.scrolltabs');

echo $this->Html->script('doughnutchart/Chart.min');
echo $this->Html->script('doughnutchart/Chart.Doughnut');
