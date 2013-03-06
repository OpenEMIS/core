<?php
Router::connect('/Teachers/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Teachers', array('plugin' => 'Teachers', 'controller' => 'Teachers'));
Router::connect('/Teachers/:action/*', array('plugin' => 'Teachers', 'controller' => 'Teachers'));