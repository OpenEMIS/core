<?php
Router::connect('/reports/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Reports/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Reports', array('plugin' => 'Reports', 'controller' => 'Reports'));
/*
 * Router::connect('/Reports/Institution', array('plugin' => 'Reports', 'controller' => 'Reports', 'action'=>'index','Institution'));
Router::connect('/Reports/Student', array('plugin' => 'Reports', 'controller' => 'Reports', 'action'=>'index','Student'));
Router::connect('/Reports/Teacher', array('plugin' => 'Reports', 'controller' => 'Reports', 'action'=>'index','Teacher'));
Router::connect('/Reports/Staff', array('plugin' => 'Reports', 'controller' => 'Reports', 'action'=>'index','Staff'));
Router::connect('/Reports/Consolidated', array('plugin' => 'Reports', 'controller' => 'Reports', 'action'=>'index','Consolidated'));
Router::connect('/Reports/Indicator', array('plugin' => 'Reports', 'controller' => 'Reports', 'action'=>'index','Indicator'));
Router::connect('/Reports/Custom', array('plugin' => 'Reports', 'controller' => 'Reports', 'action'=>'index','Custom'));
Router::connect('/Reports/Data Quality', array('plugin' => 'Reports', 'controller' => 'Reports', 'action'=>'index','Data_Quality'));
 * 
 */
Router::connect('/Reports/:action/*', array('plugin' => 'Reports', 'controller' => 'Reports'));




