<?php
Router::connect('/Database/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Database', array('plugin' => 'Database', 'controller' => 'Database'));
Router::connect('/Database/Setup', array('controller' => 'Setup'));
Router::connect('/Database/:action/*', array('plugin' => 'Database', 'controller' => 'Database'));