<?php
Router::connect('/HighCharts/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/HighCharts', array('plugin' => 'HighCharts', 'controller' => 'HighCharts'));
Router::connect('/HighCharts/:action/*', array('controller' => 'HighCharts', 'plugin'=>'HighCharts'));
