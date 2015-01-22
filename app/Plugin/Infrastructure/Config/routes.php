<?php
Router::connect('/InfrastructureLevels', array('plugin' => 'Infrastructure', 'controller' => 'InfrastructureLevels'));
Router::connect('/InfrastructureLevels/:action/*', array('plugin'=>'Infrastructure', 'controller' => 'InfrastructureLevels'));

Router::connect('/InfrastructureTypes', array('plugin' => 'Infrastructure', 'controller' => 'InfrastructureTypes'));
Router::connect('/InfrastructureTypes/:action/*', array('plugin'=>'Infrastructure', 'controller' => 'InfrastructureTypes'));

Router::connect('/InfrastructureCustomFields', array('plugin' => 'Infrastructure', 'controller' => 'InfrastructureCustomFields'));
Router::connect('/InfrastructureCustomFields/:action/*', array('plugin'=>'Infrastructure', 'controller' => 'InfrastructureCustomFields'));
