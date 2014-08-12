<?php
Router::connect('/Datawarehouse/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Datawarehouse', array('plugin' => 'Datawarehouse', 'controller' => 'Datawarehouse'));
Router::connect('/Datawarehouse/:action/*', array('plugin' => 'Datawarehouse', 'controller' => 'Datawarehouse'));