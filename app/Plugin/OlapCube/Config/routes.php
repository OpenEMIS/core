<?php
Router::connect('/OlapCube/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/OlapCube', array('plugin' => 'OlapCube', 'controller' => 'OlapCube'));
Router::connect('/OlapCube/:action/*', array('controller' => 'OlapCube', 'plugin'=>'OlapCube'));
