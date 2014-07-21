<?php
Router::connect('/FusionCharts/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/FusionCharts', array('plugin' => 'FusionCharts', 'controller' => 'FusionCharts'));
Router::connect('/FusionCharts/:action/*', array('controller' => 'FusionCharts', 'plugin'=>'FusionCharts'));
