<?php
Router::connect('/Training/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Training', array('plugin' => 'Training', 'controller' => 'Training'));
Router::connect('/Training/:action/*', array('controller' => 'Training', 'plugin'=>'Training'));
