<?php
Router::connect('/QualityRubrics', array('plugin' => 'Quality', 'controller' => 'QualityRubrics', 'action' => 'index'));
Router::connect('/QualityRubrics/:action/*', array('controller' => 'QualityRubrics', 'plugin' => 'Quality'));

Router::connect('/QualityStatuses', array('plugin' => 'Quality', 'controller' => 'QualityStatuses', 'action' => 'index'));
Router::connect('/QualityStatuses/:action/*', array('controller' => 'QualityStatuses', 'plugin' => 'Quality'));
