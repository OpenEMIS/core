<?php
Router::connect('/Staff/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Staff', array('plugin' => 'Staff', 'controller' => 'Staff'));
Router::connect('/Staff/:action/*', array('plugin' => 'Staff', 'controller' => 'Staff'));
