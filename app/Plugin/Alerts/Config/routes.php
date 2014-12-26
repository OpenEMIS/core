<?php
Router::connect('/Alerts', array('plugin' => 'Alerts', 'controller' => 'Alerts'));
Router::connect('/Alerts/:action/*', array('controller' => 'Alerts', 'plugin'=>'Alerts'));
