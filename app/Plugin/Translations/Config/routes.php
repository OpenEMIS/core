<?php
Router::connect('/Translations/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Translations', array('plugin' => 'Translations', 'controller' => 'Translations'));
Router::connect('/Translations/:action/*', array('controller' => 'Translations', 'plugin'=>'Translations'));