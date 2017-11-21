<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

namespace Angular\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;

class AngularComponent extends Component
{
    private $controller;
    public $resetConfig = true;
    protected $_defaultConfig = [
        'app' => 'Angular-App',
        'modules' => []
    ];

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
    }

    // Is called after the controller’s beforeFilter method but before the controller executes the current action handler.
    public function startup(Event $event)
    {
        $app = $this->config('app');
        $modules = $this->config('modules');

        $this->controller->set('ng_app', $app);
        $this->controller->set('ng_modules', json_encode($modules));
    }

    public function addModules($newModules = [])
    {
        $this->config('modules', $newModules);
    }
}
