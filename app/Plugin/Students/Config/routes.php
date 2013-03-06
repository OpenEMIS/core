<?php
Router::connect('/Students/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Students', array('plugin' => 'Students', 'controller' => 'Students'));
Router::connect('/Students/:action/*', array('plugin' => 'Students', 'controller' => 'Students'));