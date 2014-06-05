<?php
Router::connect('/Quality/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Quality', array('plugin' => 'Quality', 'controller' => 'Quality'));
Router::connect('/Quality/:action/*', array('controller' => 'Quality', 'plugin'=>'Quality'));
