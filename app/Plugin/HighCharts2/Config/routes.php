<?php
Router::connect('/HighCharts2', array('plugin' => 'HighCharts2', 'controller' => 'HighCharts2'));
Router::connect('/HighCharts2/:action/*', array('controller' => 'HighCharts2', 'plugin'=>'HighCharts2'));
