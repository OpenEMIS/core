<?php
Router::connect('/Visualizer', array('plugin' => 'Visualizer', 'controller' => 'Visualizer'));
Router::connect('/Visualizer/:action/*', array('controller' => 'Visualizer', 'plugin'=>'Visualizer'));
