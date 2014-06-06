<?php
Router::connect('/Dashboards/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Dashboards', array('plugin' => 'Dashboards', 'controller' => 'Dashboards'));
Router::connect('/Dashboards/:action/*', array('controller' => 'Dashboards', 'plugin'=>'Dashboards'));
