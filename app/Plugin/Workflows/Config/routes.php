<?php
Router::connect('/Workflows', array('plugin' => 'Workflows', 'controller' => 'Workflows', 'action' => 'index'));
Router::connect('/Workflows/:action/*', array('controller' => 'Workflows', 'plugin' => 'Workflows'));

Router::connect('/WorkflowSteps', array('plugin' => 'Workflows', 'controller' => 'WorkflowSteps', 'action' => 'index'));
Router::connect('/WorkflowSteps/:action/*', array('controller' => 'WorkflowSteps', 'plugin' => 'Workflows'));

Router::connect('/WorkflowLogs', array('plugin' => 'Workflows', 'controller' => 'WorkflowLogs', 'action' => 'index'));
Router::connect('/WorkflowLogs/:action/*', array('controller' => 'WorkflowLogs', 'plugin' => 'Workflows'));
