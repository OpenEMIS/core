<?php
Router::connect('/Rest/:action/*', array('controller' => 'Rest', 'plugin' => 'Restful'));
