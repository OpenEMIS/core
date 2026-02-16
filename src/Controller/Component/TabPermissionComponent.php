<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Inflector;
use Cake\Core\Configure;

class TabPermissionComponent extends Component
{
    // Components are defined in the parent class as protected $components = []
    // We set them in initialize() method instead to avoid type declaration conflicts

    public function initialize(array $config): void
    {
        // Set components to avoid redeclaring the property (which causes type conflicts in CakePHP 5)
        $this->components = ['AccessControl'];
        
        // Manually populate _componentMap since we set components after constructor
        // This is needed for __get() to work properly in CakePHP 5
        if ($this->components) {
            $this->_componentMap = $this->_registry->normalizeArray($this->components);
        }
        
        $this->controller = $this->_registry->getController();
    }

    public function checkTabPermission(array $tabArray, array $roleId = [])
    {
        foreach ($tabArray as $key => $content) {
            if (isset($content['url'])) { //  POCOR-6353 remove profile comment controller condition
                $check = $this->AccessControl->check($content['url'], $roleId);
                if (!$check) {
                    unset($tabArray[$key]);
                }
            }

            if (isset($tabArray[$key]) && isset($content['url']['plugin']) && isset(Configure::read('plugins')[$content['url']['plugin']])) {
                $path = Configure::read('plugins')[$content['url']['plugin']];
                if (!file_exists($path)) {
                    unset($tabArray[$key]);
                }
            }

            $excludedPlugins = (array) Configure::read('School.excludedPlugins');
            if (isset($tabArray[$key]) && (array_key_exists($key, $excludedPlugins) || array_key_exists(Inflector::singularize($key), $excludedPlugins))) {
                unset($tabArray[$key]);
            }
        }

        return $tabArray;
    }
}
