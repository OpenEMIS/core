<?php
Router::connect('/Staff/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Staff', array('plugin' => 'Staff', 'controller' => 'Staff'));
Router::connect('/Staff/:action/*', array('plugin' => 'Staff', 'controller' => 'Staff'));

Router::connect('/StaffReports/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/StaffReports', array('plugin' => 'Staff', 'controller' => 'StaffReports'));
Router::connect('/StaffReports/:action/*', array('plugin' => 'Staff', 'controller' => 'StaffReports'));
