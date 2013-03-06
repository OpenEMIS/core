<?php
Router::connect('/dataprocessing/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/DataProcessing/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/DataProcessing', array('plugin' => 'DataProcessing', 'controller' => 'DataProcessing'));
Router::connect('/DataProcessing/:action/*', array('plugin' => 'DataProcessing', 'controller' => 'DataProcessing'));