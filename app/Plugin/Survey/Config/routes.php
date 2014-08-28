<?php
Router::connect('/Survey/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Survey/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Survey/Setup', array('controller' => 'Setup'));
Router::connect('/Survey', array('plugin' => 'Survey', 'controller' => 'Survey','action'=>'index'));
Router::connect('/Survey/:action/*', array('plugin' => 'Survey', 'controller' => 'Survey'));






