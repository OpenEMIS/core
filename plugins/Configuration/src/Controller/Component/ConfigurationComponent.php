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

namespace Configuration\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Log\Log;

class ConfigurationComponent extends Component
{
    private $controller;
    private $configOptions = [
        'AdministrativeBoundaries'  => ['className' => 'Configuration.AdministrativeBoundaries'],
        'Authentication'            => ['className' => 'Configuration.Authentication'],
        'CustomValidation'          => ['className' => 'Configuration.CustomValidation'],
        'ExternalDataSource'        => ['className' => 'Configuration.ExternalDataSource'],
        'ProductLists'              => ['className' => 'Configuration.ProductLists'],
        'Webhooks'                  => ['className' => 'Configuration.Webhooks']
    ];

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $controller = $this->controller->name;
        $accessMap = [];
        foreach (array_keys($this->configOptions) as $key) {
            $accessMap["$controller.$key"] = "$controller.%s";
        }
        $this->request->addParams(['accessMap' => $accessMap]);
    }

    public function getConfigurationOptions()
    {
        return $this->configOptions;
    }

    public function getClassName($key)
    {
        return $this->configOptions[$key]['className'];
    }
}
