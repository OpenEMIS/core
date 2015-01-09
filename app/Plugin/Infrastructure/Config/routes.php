<?php
Router::connect('/InfrastructureCategories', array('plugin' => 'Infrastructure', 'controller' => 'InfrastructureCategories'));
Router::connect('/InfrastructureCategories/:action/*', array('plugin'=>'Infrastructure', 'controller' => 'InfrastructureCategories'));

Router::connect('/InfrastructureTypes', array('plugin' => 'Infrastructure', 'controller' => 'InfrastructureTypes'));
Router::connect('/InfrastructureTypes/:action/*', array('plugin'=>'Infrastructure', 'controller' => 'InfrastructureTypes'));
