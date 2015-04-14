<?php
Router::connect('/Quality', array('plugin' => 'Quality', 'controller' => 'Quality'));
Router::connect('/Quality/:action/*', array('controller' => 'Quality', 'plugin'=>'Quality'));
