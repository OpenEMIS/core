<?php
Router::connect('/Sms/Security/login', array('controller' => 'Security', 'action' => 'login', 'Plugin'=>''));
Router::connect('/Sms', array('plugin' => 'Sms', 'controller' => 'Sms'));
Router::connect('/Sms/:action/*', array('controller' => 'Sms', 'plugin'=>'Sms'));
Router::connect('/sms/receive/*', array('controller' => 'Sms', 'plugin'=>'Sms', 'action'=>'receive'));
