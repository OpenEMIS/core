<?php
Router::connect('/Translations', array('plugin' => 'Translations', 'controller' => 'Translations'));
Router::connect('/Translations/:action/*', array('controller' => 'Translations', 'plugin'=>'Translations'));