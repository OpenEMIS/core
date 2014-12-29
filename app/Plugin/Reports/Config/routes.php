<?php
Router::connect('/reports/Security/login', array('plugin' => false, 'controller' => 'Security', 'action' => 'login'));
Router::connect('/Reports/Security/login', array('plugin' => false, 'controller' => 'Security', 'action' => 'login'));
Router::connect('/Reports', array('plugin' => 'Reports', 'controller' => 'Reports'));
Router::connect('/Reports/:action/*', array('plugin' => 'Reports', 'controller' => 'Reports'));




