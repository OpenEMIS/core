<?php
Router::connect('/Survey/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/Survey/Security/login', array('controller' => 'Security', 'action' => 'login'));

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
Router::connect('/Survey/Setup', array('controller' => 'Setup'));
Router::connect('/Survey', array('plugin' => 'Survey', 'controller' => 'Survey','action'=>'index'));
Router::connect('/Survey/:action/*', array('plugin' => 'Survey', 'controller' => 'Survey'));






