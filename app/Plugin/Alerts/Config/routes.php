<?php
Router::connect('/Alerts/Security/login', array('controller' => 'Security', 'action' => 'login', 'Plugin'=>''));
Router::connect('/Alerts', array('plugin' => 'Alerts', 'controller' => 'Alerts'));
Router::connect('/Alerts/:action/*', array('controller' => 'Alerts', 'plugin'=>'Alerts'));
