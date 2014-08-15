<?php
Router::connect('/Dashboards', array('plugin' => 'Dashboards', 'controller' => 'Dashboards'));
Router::connect('/Dashboards/:action/*', array('controller' => 'Dashboards', 'plugin'=>'Dashboards'));
