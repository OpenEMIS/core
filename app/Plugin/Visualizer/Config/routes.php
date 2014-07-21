<?php
Router::connect('/Visualizer/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Visualizer', array('plugin' => 'Visualizer', 'controller' => 'Visualizer'));
Router::connect('/Visualizer/:action/*', array('controller' => 'Visualizer', 'plugin'=>'Visualizer'));
