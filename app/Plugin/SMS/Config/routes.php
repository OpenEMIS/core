<?php
Router::connect('/Sms/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Sms', array('plugin' => 'Sms', 'controller' => 'Sms'));
Router::connect('/Sms/:action/*', array('plugin' => 'Sms', 'controller' => 'Sms'));
Router::connect('/sms', array('plugin' => 'Sms', 'controller' => 'Sms'));
Router::connect('/sms/:action/*', array('plugin' => 'Sms', 'controller' => 'Sms'));
